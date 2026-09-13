<?php
require_once __DIR__ . '/../models/Note.php';

class NoteController
{
    private Note $noteModel;
    private PDO $dbh;

    public function __construct(Note $noteModel, PDO $dbh)
    {
        $this->noteModel = $noteModel;
        $this->dbh = $dbh;
    }

    public function index(int $userId, array $query): array
    {
        $folderId = isset($query['folder']) && $query['folder'] !== '' ? (int) $query['folder'] : null;
        if ($folderId !== null && $folderId <= 0) {
            $folderId = null;
        }
        $search = trim((string) ($query['q'] ?? ''));

        $items = $this->noteModel->searchForUser($userId, $folderId, $search);

        $favoriteIds = $this->favoriteNoteIds($userId);
        foreach ($items as &$item) {
            $item['is_favorite'] = in_array((int) $item['note_id'], $favoriteIds, true);
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

    public function find(int $noteId, int $userId): ?array
    {
        $item = $this->noteModel->find($noteId, $userId);
        if (!$item) {
            return null;
        }
        $item['is_favorite'] = $this->isFavorite($userId, $noteId);
        return $item;
    }

    public function store(int $userId, array $post): array
    {
        return $this->noteModel->create($userId, $this->extract($post));
    }

    public function update(int $noteId, int $userId, array $post): array
    {
        return $this->noteModel->update($noteId, $userId, $this->extract($post));
    }

    public function destroy(int $noteId, int $userId): bool
    {
        return $this->noteModel->delete($noteId, $userId);
    }

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

    private function extract(array $post): array
    {
        return [
            'title' => trim((string) ($post['title'] ?? '')),
            'content' => $this->sanitizeContent((string) ($post['content'] ?? '')),
            'folder_id' => $post['folder_id'] ?? null,
        ];
    }

    /**
     * Bare-bones HTML sanitizer for note content coming from the
     * contenteditable editor. Keeps a small safe tag whitelist and
     * strips event-handler attributes / javascript: links.
     * NOTE: for a real production app, swap this for a proper library
     * like HTML Purifier — this is intentionally minimal.
     */
    private function sanitizeContent(string $html): string
    {
        $allowed = '<h1><h2><h3><p><br><b><strong><i><em><u><ul><ol><li><a>';
        $clean = strip_tags($html, $allowed);
        $clean = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);
        $clean = preg_replace('/(href\s*=\s*)("|\')\s*javascript:[^"\']*\2/i', '$1$2#$2', $clean);
        return trim($clean);
    }

    private function getFolders(int $userId): array
    {
        $stmt = $this->dbh->prepare('SELECT folder_id, folder_name FROM folders WHERE user_id = :uid ORDER BY folder_name');
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function favoriteNoteIds(int $userId): array
    {
        $stmt = $this->dbh->prepare('SELECT note_id FROM favorites WHERE user_id = :uid AND note_id IS NOT NULL');
        $stmt->execute(['uid' => $userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function isFavorite(int $userId, int $noteId): bool
    {
        $stmt = $this->dbh->prepare('SELECT 1 FROM favorites WHERE user_id = :uid AND note_id = :nid');
        $stmt->execute(['uid' => $userId, 'nid' => $noteId]);
        return (bool) $stmt->fetchColumn();
    }

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

    private function unmarkFavorite(int $userId, int $noteId): void
    {
        $stmt = $this->dbh->prepare('DELETE FROM favorites WHERE user_id = :uid AND note_id = :nid');
        $stmt->execute(['uid' => $userId, 'nid' => $noteId]);
    }
}
