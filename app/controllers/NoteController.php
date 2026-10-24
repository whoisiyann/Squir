<?php
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../models/ActivityLog.php';

class NoteController
{
    private Note $noteModel;
    private PDO $dbh;
    private ActivityLog $activityLog;

    public function __construct(Note $noteModel, PDO $dbh, ActivityLog $activityLog)
    {
        $this->noteModel = $noteModel;
        $this->dbh = $dbh;
        $this->activityLog = $activityLog;
    }

    // Get notes
    public function index(int $userId, array $query): array
    {
        $folderId = isset($query['folder']) && $query['folder'] !== '' ? (int) $query['folder'] : null;
        if ($folderId !== null && $folderId <= 0) {
            $folderId = null;
        }
        $search = trim((string) ($query['q'] ?? ''));

        $items = $this->noteModel->searchForUser($userId, $folderId, $search);

        // Set favorite flags
        foreach ($items as &$item) {
            $item['is_favorite'] = (bool) $item['is_favorite'];
        }
        unset($item);

        return [
            'items' => $items,
            'total' => count($items),
            'folders' => $this->getFolders($userId),
            'folderCounts' => $this->noteModel->folderCountsForUser($userId),
            'activeFolder' => $folderId,
            'search' => $search,
        ];
    }

    // Find a note
    public function find(int $noteId, int $userId): ?array
    {
        $item = $this->noteModel->find($noteId, $userId);
        if (!$item) {
            return null;
        }
        $item['is_favorite'] = $this->isFavorite($userId, $noteId);
        return $item;
    }

    // Save a note
    public function store(int $userId, array $post): array
    {
        $result = $this->noteModel->create($userId, $this->extract($post));
        if ($result['errors'] === []) {
            $this->activityLog->log($userId, 'note_created', "Created note '" . $this->activityTitle($post['title'] ?? '') . "'", 'note', (int) $result['note_id']);
        }
        return $result;
    }

    // Update a note
    public function update(int $noteId, int $userId, array $post): array
    {
        $existing = $this->noteModel->find($noteId, $userId);
        $result = $this->noteModel->update($noteId, $userId, $this->extract($post));
        if ($result['errors'] === [] && $existing) {
            $this->activityLog->log($userId, 'note_updated', "Updated note '" . $this->activityTitle($post['title'] ?? $existing['title']) . "'", 'note', $noteId);
        }
        return $result;
    }

    // Delete a note
    public function destroy(int $noteId, int $userId): bool
    {
        $existing = $this->noteModel->find($noteId, $userId);
        $deleted = $this->noteModel->delete($noteId, $userId);
        if ($deleted && $existing) {
            $this->activityLog->log($userId, 'note_deleted', "Deleted note '" . $this->activityTitle($existing['title']) . "'", 'note', $noteId);
        }
        return $deleted;
    }

    // Delete several notes at once
    public function destroyMany(array $ids, int $userId): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        $ids = array_slice($ids, 0, 200);

        $titles = [];
        $deleted = 0;
        foreach ($ids as $noteId) {
            $existing = $this->noteModel->find($noteId, $userId);
            if (!$existing) {
                continue;
            }
            if ($this->noteModel->delete($noteId, $userId)) {
                $deleted++;
                $titles[] = $this->activityTitle($existing['title']);
            }
        }

        if ($deleted === 1) {
            $this->activityLog->log($userId, 'note_deleted', "Deleted note '" . $titles[0] . "'", 'note', $ids[0] ?? null);
        } elseif ($deleted > 1) {
            $preview = implode(', ', array_slice($titles, 0, 3)) . ($deleted > 3 ? ', ...' : '');
            $this->activityLog->log($userId, 'note_deleted', 'Deleted ' . $deleted . ' notes (' . mb_substr($preview, 0, 180) . ')', 'note', null);
        }

        return $deleted;
    }

    // Move a note to a folder
    public function moveToFolder(int $noteId, int $userId, ?int $folderId): bool
    {
        return $this->noteModel->moveToFolder($noteId, $userId, $folderId);
    }

    // Duplicate a note
    public function duplicate(int $noteId, int $userId): ?int
    {
        $newNoteId = $this->noteModel->duplicate($noteId, $userId);
        if ($newNoteId !== null) {
            $created = $this->noteModel->find($newNoteId, $userId);
            if ($created) {
                $this->activityLog->log($userId, 'note_created', "Created note '" . $this->activityTitle($created['title']) . "'", 'note', $newNoteId);
            }
        }
        return $newNoteId;
    }

    // Toggle favorite
    public function toggleFavorite(int $noteId, int $userId): ?bool
    {
        $item = $this->noteModel->find($noteId, $userId);
        if (!$item) {
            return null;
        }

        if ($this->isFavorite($userId, $noteId)) {
            $this->unmarkFavorite($userId, $noteId);
            return false;
        }

        $this->markFavorite($userId, $noteId);
        return true;
    }

    // Get note form data
    private function extract(array $post): array
    {
        return [
            'title' => trim((string) ($post['title'] ?? '')),
            'content' => $this->sanitizeContent((string) ($post['content'] ?? '')),
            'folder_id' => $post['folder_id'] ?? null,
        ];
    }


    // Sanitize note HTML
    private function sanitizeContent(string $html): string
    {
        $allowed = '<h1><h2><h3><p><br><b><strong><i><em><u><s><strike><ul><ol><li><a><span><blockquote><div>';
        $clean = strip_tags($html, $allowed);
        $clean = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);
        $clean = preg_replace('/(href\s*=\s*)("|\')\s*javascript:[^"\']*\2/i', '$1$2#$2', $clean);

        $clean = preg_replace_callback(
            '/\sstyle\s*=\s*(["\'])(.*?)\1/i',
            static function (array $matches): string {
                $declarations = array_filter(array_map('trim', explode(';', $matches[2])));
                $safe = [];
                foreach ($declarations as $declaration) {
                    if (preg_match('/^(background-color|text-align)\s*:\s*[#a-zA-Z0-9(),.\s%-]+$/', $declaration)) {
                        $safe[] = $declaration;
                    }
                }
                return $safe !== [] ? ' style="' . htmlspecialchars(implode('; ', $safe), ENT_QUOTES, 'UTF-8') . '"' : '';
            },
            $clean
        );

        return trim($clean);
    }

    // Get note folders
    private function getFolders(int $userId): array
    {
        $stmt = $this->dbh->prepare(
            "SELECT folder_id, folder_name, color FROM folders WHERE user_id = :uid AND folder_type = 'notes' ORDER BY folder_name"
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    // Check if the note is a favorite
    private function isFavorite(int $userId, int $noteId): bool
    {
        $stmt = $this->dbh->prepare('SELECT 1 FROM favorites WHERE user_id = :uid AND note_id = :nid');
        $stmt->execute(['uid' => $userId, 'nid' => $noteId]);
        return (bool) $stmt->fetchColumn();
    }

    // Add the note to favorites
    private function markFavorite(int $userId, int $noteId): void
    {
        $check = $this->dbh->prepare('SELECT favorite_id FROM favorites WHERE user_id = :uid AND note_id = :nid');
        $check->execute(['uid' => $userId, 'nid' => $noteId]);
        if ($check->fetchColumn()) {
            return;
        }
        $stmt = $this->dbh->prepare('INSERT INTO favorites (user_id, note_id) VALUES (:uid, :nid)');
        $stmt->execute(['uid' => $userId, 'nid' => $noteId]);
    }

    // Remove the note from favorites
    private function unmarkFavorite(int $userId, int $noteId): void
    {
        $stmt = $this->dbh->prepare('DELETE FROM favorites WHERE user_id = :uid AND note_id = :nid');
        $stmt->execute(['uid' => $userId, 'nid' => $noteId]);
    }

    private function activityTitle(string $title): string
    {
        return mb_substr(trim($title) !== '' ? trim($title) : 'Untitled', 0, 140);
    }
}