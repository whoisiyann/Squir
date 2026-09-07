<?php

class VaultController
{
    private Vault $vaultModel;
    private PDO $dbh;

    public function __construct(Vault $vaultModel, PDO $dbh)
    {
        $this->vaultModel = $vaultModel;
        $this->dbh = $dbh;
    }

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

    public function store(int $userId, array $post): array
    {
        $result = $this->vaultModel->create($userId, $this->extract($post));

        if ($result['errors'] === [] && ($post['is_favorite'] ?? '0') === '1') {
            $this->markFavorite($userId, (int) $result['vault_id']);
        }

        return $result;
    }

    public function edit(int $vaultId, int $userId): ?array
    {
        $item = $this->vaultModel->find($vaultId, $userId);
        if (!$item) {
            return null;
        }
        $item['is_favorite'] = $this->isFavorite($userId, $vaultId);
        return $item;
    }

    public function update(int $vaultId, int $userId, array $post): array
    {
        $result = $this->vaultModel->update($vaultId, $userId, $this->extract($post));

        if ($result['errors'] === []) {
            if (($post['is_favorite'] ?? '0') === '1') {
                $this->markFavorite($userId, $vaultId);
            } else {
                $this->unmarkFavorite($userId, $vaultId);
            }
        }

        return $result;
    }

    public function destroy(int $vaultId, int $userId): bool
    {
        return $this->vaultModel->delete($vaultId, $userId);
    }

    public function revealPassword(int $vaultId, int $userId): ?string
    {
        $item = $this->vaultModel->find($vaultId, $userId);
        if (!$item) {
            return null;
        }
        return $this->vaultModel->decryptSecret($item['account_password']);
    }
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

    private function getFolders(int $userId): array
    {
        $stmt = $this->dbh->prepare('SELECT folder_id, folder_name FROM folders WHERE user_id = :uid ORDER BY folder_name');
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

      private function favoriteVaultIds(int $userId): array
    {
        $stmt = $this->dbh->prepare('SELECT vault_id FROM favorites WHERE user_id = :uid AND vault_id IS NOT NULL');
        $stmt->execute(['uid' => $userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function isFavorite(int $userId, int $vaultId): bool
    {
        $stmt = $this->dbh->prepare('SELECT 1 FROM favorites WHERE user_id = :uid AND vault_id = :vid');
        $stmt->execute(['uid' => $userId, 'vid' => $vaultId]);
        return (bool) $stmt->fetchColumn();
    }

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

    private function unmarkFavorite(int $userId, int $vaultId): void
    {
        $stmt = $this->dbh->prepare('DELETE FROM favorites WHERE user_id = :uid AND vault_id = :vid');
        $stmt->execute(['uid' => $userId, 'vid' => $vaultId]);
    }
}