<?php
require_once __DIR__ . '/../models/Folder.php';
require_once __DIR__ . '/../models/ActivityLog.php';

class FolderController
{
    private Folder $folderModel;
    private PDO $dbh;
    private ActivityLog $activityLog;

    public function __construct(Folder $folderModel, PDO $dbh, ActivityLog $activityLog)
    {
        $this->folderModel = $folderModel;
        $this->dbh = $dbh;
        $this->activityLog = $activityLog;
    }

    // Load user folders
    public function index(int $userId, array $query): array
    {
        $search = trim((string) ($query['q'] ?? ''));
        $type = ($query['type'] ?? 'passwords') === 'notes' ? 'notes' : 'passwords';

        $folders = $this->folderModel->searchForUser($userId, $search, $type);
        $favoriteIds = $this->favoriteFolderIds($userId);

        foreach ($folders as &$folder) {
            $folder['is_favorite'] = in_array((int) $folder['folder_id'], $favoriteIds, true);
            $folder['item_count'] = $type === 'notes' ? (int) $folder['note_count'] : (int) $folder['vault_count'];
        }
        unset($folder);

        return [
            'folders' => $folders,
            'total' => count($folders),
            'search' => $search,
            'activeType' => $type,
        ];
    }

    // Create a folder
    public function store(int $userId, array $post): array
    {
        $type = ($post['folder_type'] ?? 'passwords') === 'notes' ? 'notes' : 'passwords';

        $result = $this->folderModel->create($userId, [
            'folder_name' => trim((string) ($post['folder_name'] ?? '')),
            'folder_type' => $type,
            'color' => (string) ($post['color'] ?? 'blue'),
        ]);
        if ($result['errors'] === []) {
            $this->activityLog->log($userId, 'folder_created', "Created folder '" . $this->activityTitle($post['folder_name'] ?? '') . "'", 'folder', (int) $result['folder_id']);
        }
        return $result;
    }

    // Rename a folder
    public function rename(int $folderId, int $userId, string $name): array
    {
        $existing = $this->folderModel->find($folderId, $userId);
        $result = $this->folderModel->rename($folderId, $userId, $name);
        if ($result['errors'] === [] && $existing) {
            $this->activityLog->log($userId, 'folder_renamed', "Renamed folder '" . $this->activityTitle($name) . "'", 'folder', $folderId);
        }
        return $result;
    }

    // Update folder color
    public function updateColor(int $folderId, int $userId, string $color): bool
    {
        if (!$this->folderModel->find($folderId, $userId)) {
            return false;
        }
        return $this->folderModel->updateColor($folderId, $userId, $color);
    }

    // Delete a folder
    public function destroy(int $folderId, int $userId): bool
    {
        $existing = $this->folderModel->find($folderId, $userId);
        $deleted = $this->folderModel->delete($folderId, $userId);
        if ($deleted && $existing) {
            $this->activityLog->log($userId, 'folder_deleted', "Deleted folder '" . $this->activityTitle($existing['folder_name']) . "'", 'folder', $folderId);
        }
        return $deleted;
    }

    // Toggle folder favorite state
    public function toggleFavorite(int $folderId, int $userId): ?bool
    {
        $folder = $this->folderModel->find($folderId, $userId);
        if (!$folder) {
            return null;
        }

        if ($this->isFavorite($userId, $folderId)) {
            $this->unmarkFavorite($userId, $folderId);
            return false;
        }

        $this->markFavorite($userId, $folderId);
        return true;
    }

    // Fetch favorite folder IDs
    private function favoriteFolderIds(int $userId): array
    {
        $stmt = $this->dbh->prepare('SELECT folder_id FROM favorites WHERE user_id = :uid AND folder_id IS NOT NULL');
        $stmt->execute(['uid' => $userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    // Check folder favorite state
    private function isFavorite(int $userId, int $folderId): bool
    {
        $stmt = $this->dbh->prepare('SELECT 1 FROM favorites WHERE user_id = :uid AND folder_id = :fid');
        $stmt->execute(['uid' => $userId, 'fid' => $folderId]);
        return (bool) $stmt->fetchColumn();
    }

    // Mark a folder favorite
    private function markFavorite(int $userId, int $folderId): void
    {
        $check = $this->dbh->prepare('SELECT favorite_id FROM favorites WHERE user_id = :uid AND folder_id = :fid');
        $check->execute(['uid' => $userId, 'fid' => $folderId]);
        if ($check->fetchColumn()) {
            return;
        }
        $stmt = $this->dbh->prepare('INSERT INTO favorites (user_id, folder_id) VALUES (:uid, :fid)');
        $stmt->execute(['uid' => $userId, 'fid' => $folderId]);
    }

    // Remove folder favorite
    private function unmarkFavorite(int $userId, int $folderId): void
    {
        $stmt = $this->dbh->prepare('DELETE FROM favorites WHERE user_id = :uid AND folder_id = :fid');
        $stmt->execute(['uid' => $userId, 'fid' => $folderId]);
    }

    private function activityTitle(string $title): string
    {
        return mb_substr(trim($title) !== '' ? trim($title) : 'Untitled', 0, 140);
    }
}