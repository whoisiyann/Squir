<?php

class VaultController
{
    private Vault $vaultModel;
    private PDO $dbh;
    private ActivityLog $activityLog;

    public function __construct(Vault $vaultModel, PDO $dbh, ActivityLog $activityLog)
    {
        $this->vaultModel = $vaultModel;
        $this->dbh = $dbh;
        $this->activityLog = $activityLog;
    }

    // Load user vault entries
    public function index(int $userId, array $query): array
    {
        $folderId = isset($query['folder']) && $query['folder'] !== '' ? (int) $query['folder'] : null;
        if ($folderId !== null && $folderId <= 0) {
            $folderId = null;
        }
        $search = trim((string) ($query['q'] ?? ''));
        $tag = trim((string) ($query['tag'] ?? ''));

        $items = $this->vaultModel->searchForUser($userId, $folderId, $search, $tag !== '' ? $tag : null);


        $favoriteIds = $this->favoriteVaultIds($userId);
        foreach ($items as &$item) {
            $item['is_favorite'] = in_array((int) $item['vault_id'], $favoriteIds, true);
        }
        unset($item);

        return [
            'items' => $items,
            'total' => count($items),
            'folders' => $this->getFolders($userId),
            'tags' => $this->vaultModel->tagCountsForUser($userId), 
            'activeFolder' => $folderId,
            'activeTag' => $tag,
            'search' => $search,
        ];
    }

    // Create a vault entry
    public function store(int $userId, array $post): array
    {
        $result = $this->vaultModel->create($userId, $this->extract($post));

        if ($result['errors'] === [] && ($post['is_favorite'] ?? '0') === '1') {
            $this->markFavorite($userId, (int) $result['vault_id']);
            $this->activityLog->log($userId, 'favorite_added', "Added '" . $this->activityTitle($post['title'] ?? '') . "' to favorites", 'favorite', (int) $result['vault_id']);
        }
        if ($result['errors'] === []) {
            $this->activityLog->log($userId, 'vault_created', "Created credential '" . $this->activityTitle($post['title'] ?? '') . "'", 'vault', (int) $result['vault_id']);
        }

        return $result;
    }

    // Load a vault entry for editing
    public function edit(int $vaultId, int $userId): ?array
    {
        $item = $this->vaultModel->find($vaultId, $userId);
        if (!$item) {
            return null;
        }
        $item['is_favorite'] = $this->isFavorite($userId, $vaultId);
        return $item;
    }

    // Update a vault entry
    public function update(int $vaultId, int $userId, array $post): array
    {
        $existing = $this->vaultModel->find($vaultId, $userId);
        $wasFavorite = $existing ? $this->isFavorite($userId, $vaultId) : false;
        $result = $this->vaultModel->update($vaultId, $userId, $this->extract($post));

        if ($result['errors'] === []) {
            $shouldBeFavorite = ($post['is_favorite'] ?? '0') === '1';
            if ($shouldBeFavorite) {
                $this->markFavorite($userId, $vaultId);
            } else {
                $this->unmarkFavorite($userId, $vaultId);
            }
            if ($existing) {
                $this->activityLog->log($userId, 'vault_updated', "Updated credential '" . $this->activityTitle($post['title'] ?? $existing['title']) . "'", 'vault', $vaultId);
                if ($shouldBeFavorite && !$wasFavorite) {
                    $this->activityLog->log($userId, 'favorite_added', "Added '" . $this->activityTitle($post['title'] ?? $existing['title']) . "' to favorites", 'favorite', $vaultId);
                } elseif (!$shouldBeFavorite && $wasFavorite) {
                    $this->activityLog->log($userId, 'favorite_removed', "Removed '" . $this->activityTitle($post['title'] ?? $existing['title']) . "' from favorites", 'favorite', $vaultId);
                }
            }
        }

        return $result;
    }

    // Delete a vault entry
    public function destroy(int $vaultId, int $userId): bool
    {
        $existing = $this->vaultModel->find($vaultId, $userId);
        $deleted = $this->vaultModel->delete($vaultId, $userId);
        if ($deleted && $existing) {
            $this->activityLog->log($userId, 'vault_deleted', "Deleted credential '" . $this->activityTitle($existing['title']) . "'", 'vault', $vaultId);
        }
        return $deleted;
    }

    // Move a vault entry to a folder
    public function moveToFolder(int $vaultId, int $userId, $targetFolder): ?string
    {
        $folderId = ($targetFolder === null || $targetFolder === '') ? null : (int) $targetFolder;
        if ($folderId !== null && $folderId <= 0) {
            $folderId = null;
        }

        if (!$this->vaultModel->find($vaultId, $userId)) {
            return null;
        }
        if (!$this->vaultModel->moveToFolder($vaultId, $userId, $folderId)) {
            return null;
        }
        if ($folderId === null) {
            return 'No folder';
        }

        $stmt = $this->dbh->prepare('SELECT folder_name FROM folders WHERE folder_id = :id AND user_id = :uid');
        $stmt->execute(['id' => $folderId, 'uid' => $userId]);
        return (string) $stmt->fetchColumn();
    }

    // Reveal a vault password
    public function revealPassword(int $vaultId, int $userId): ?string
    {
        $item = $this->vaultModel->find($vaultId, $userId);
        if (!$item) {
            return null;
        }
        return $this->vaultModel->decryptSecret($item['account_password']);
    }
    // Toggle vault favorite state
    public function toggleFavorite(int $vaultId, int $userId): ?bool
    {
        $item = $this->vaultModel->find($vaultId, $userId);
        if (!$item) {
            return null;
        }

        if ($this->isFavorite($userId, $vaultId)) {
            $this->unmarkFavorite($userId, $vaultId);
            return false;
        }

        $this->markFavorite($userId, $vaultId);
        return true;
    }

    // Extract vault input
    private function extract(array $post): array
    {
        return [
            'title' => trim((string) ($post['title'] ?? '')),
            'account_username' => trim((string) ($post['account_username'] ?? '')),
            'account_password' => (string) ($post['account_password'] ?? ''),
            'website_url' => trim((string) ($post['website_url'] ?? '')),
            'folder_id' => $post['folder_id'] ?? null,
            'tags' => trim((string) ($post['tags'] ?? '')),
            'notes' => trim((string) ($post['notes'] ?? '')),
        ];
    }

    // Load vault folders
    private function getFolders(int $userId): array
    {
        $stmt = $this->dbh->prepare(
            "SELECT folder_id, folder_name, color FROM folders WHERE user_id = :uid AND folder_type = 'passwords' ORDER BY folder_name"
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch favorite vault IDs
    private function favoriteVaultIds(int $userId): array
    {
        $stmt = $this->dbh->prepare('SELECT vault_id FROM favorites WHERE user_id = :uid AND vault_id IS NOT NULL');
        $stmt->execute(['uid' => $userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    // Check vault favorite state
    private function isFavorite(int $userId, int $vaultId): bool
    {
        $stmt = $this->dbh->prepare('SELECT 1 FROM favorites WHERE user_id = :uid AND vault_id = :vid');
        $stmt->execute(['uid' => $userId, 'vid' => $vaultId]);
        return (bool) $stmt->fetchColumn();
    }

    // Mark a vault entry favorite
    private function markFavorite(int $userId, int $vaultId): void
    {
        $check = $this->dbh->prepare('SELECT favorite_id FROM favorites WHERE user_id = :uid AND vault_id = :vid');
        $check->execute(['uid' => $userId, 'vid' => $vaultId]);
        if ($check->fetchColumn()) {
            return; 
        }

        $stmt = $this->dbh->prepare('INSERT INTO favorites (user_id, vault_id) VALUES (:uid, :vid)');
        $stmt->execute(['uid' => $userId, 'vid' => $vaultId]);
    }

    // Remove vault favorite
    private function unmarkFavorite(int $userId, int $vaultId): void
    {
        $stmt = $this->dbh->prepare('DELETE FROM favorites WHERE user_id = :uid AND vault_id = :vid');
        $stmt->execute(['uid' => $userId, 'vid' => $vaultId]);
    }

    private function activityTitle(string $title): string
    {
        return mb_substr(trim($title) !== '' ? trim($title) : 'Untitled', 0, 140);
    }
}