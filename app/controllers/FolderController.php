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

    public function index(int $userId, array $query): array
    {
        $search = trim((string) ($query['q'] ?? ''));
        $type = ($query['type'] ?? 'passwords') === 'notes' ? 'notes' : 'passwords';

        $folders = $this->folderModel->searchForUser($userId, $search);
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

    public function store(int $userId, array $post): array
    {
        return $this->folderModel->create($userId, [
            'folder_name' => trim((string) ($post['folder_name'] ?? '')),
            'color' => (string) ($post['color'] ?? 'blue'),
        ]);
    }

    public function rename(int $folderId, int $userId, string $name): array
    {
        return $this->folderModel->rename($folderId, $userId, $name);
    }

    public function updateColor(int $folderId, int $userId, string $color): bool
    {
        if (!$this->folderModel->find($folderId, $userId)) {
            return false;
        }
        return $this->folderModel->updateColor($folderId, $userId, $color);
    }

    public function destroy(int $folderId, int $userId): bool
    {
        return $this->folderModel->delete($folderId, $userId);
    }

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

    private function favoriteFolderIds(int $userId): array
    {
        $stmt = $this->dbh->prepare('SELECT folder_id FROM favorites WHERE user_id = :uid AND folder_id IS NOT NULL');
        $stmt->execute(['uid' => $userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function isFavorite(int $userId, int $folderId): bool
    {
        $stmt = $this->dbh->prepare('SELECT 1 FROM favorites WHERE user_id = :uid AND folder_id = :fid');
        $stmt->execute(['uid' => $userId, 'fid' => $folderId]);
        return (bool) $stmt->fetchColumn();
    }

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

    private function unmarkFavorite(int $userId, int $folderId): void
    {
        $stmt = $this->dbh->prepare('DELETE FROM favorites WHERE user_id = :uid AND folder_id = :fid');
        $stmt->execute(['uid' => $userId, 'fid' => $folderId]);
    }
}