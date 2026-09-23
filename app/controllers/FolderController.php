<?php
require_once __DIR__ . '/../models/Folder.php';

class FolderController
{
    private Folder $folderModel;
    private PDO $dbh;

    public function __construct(Folder $folderModel, PDO $dbh)
    {
        $this->folderModel = $folderModel;
        $this->dbh = $dbh;
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

        return $this->folderModel->create($userId, [
            'folder_name' => trim((string) ($post['folder_name'] ?? '')),
            'folder_type' => $type,
            'color' => (string) ($post['color'] ?? 'blue'),
        ]);
    }

    // Rename a folder
    public function rename(int $folderId, int $userId, string $name): array
    {
        return $this->folderModel->rename($folderId, $userId, $name);
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
        return $this->folderModel->delete($folderId, $userId);
    }

    // Toggle folder favorite state
    public function toggleFavorite(int $folderId, int $userId): ?bool
    {
        if (!$this->folderModel->find($folderId, $userId)) {
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
}