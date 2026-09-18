<?php

class Vault
{
    public const PER_PAGE = 8;
    public const MAX_TAGS = 5;
    public const MAX_TAG_LENGTH = 30;

    private PDO $dbh;

    public function __construct(PDO $dbh)
    {
        $this->dbh = $dbh;
    }

    /* ================= ENCRYPTION ================= */

    private function encryptSecret(string $plain): string
    {   
        $key = $this->encryptionKey();
        $iv = random_bytes(16);
        $cipherText = openssl_encrypt($plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $cipherText);
    }

    public function decryptSecret(string $encoded): string
    {
        $key = $this->encryptionKey();
        $raw = base64_decode($encoded);
        $iv = substr($raw, 0, 16);
        $cipherText = substr($raw, 16);
        $plain = openssl_decrypt($cipherText, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return $plain === false ? '' : $plain;
    }

    private function encryptionKey(): string
    {
        $key = defined('VAULT_ENCRYPTION_KEY') ? VAULT_ENCRYPTION_KEY : '';
        if ($key === '') {
            throw new RuntimeException('VAULT_ENCRYPTION_KEY is not configured in config/config.php');
        }
        return substr(hash('sha256', $key, true), 0, 32);
    }

    /* ================= FAVICON ================= */

    public static function faviconUrlFor(?string $websiteUrl): ?string
    {
        $websiteUrl = trim((string) $websiteUrl);
        if ($websiteUrl === '') {
            return null;
        }
        if (!preg_match('~^https?://~i', $websiteUrl)) {
            $websiteUrl = 'https://' . $websiteUrl;
        }
        $host = parse_url($websiteUrl, PHP_URL_HOST);
        if (!$host) {
            return null;
        }
        return 'https://www.google.com/s2/favicons?sz=64&domain=' . rawurlencode($host);
    }

    /* ================= TAGS =================
       Ang tags ay nasa sariling `tags` table na ngayon, at naka-link sa
       vault items sa pamamagitan ng `vault_tags` bridge table (M:N).
       Para hindi na magbago ang mga view, ibinabalik pa rin ng queries
       ang isang `tags` key na CSV (galing sa GROUP_CONCAT).
       ======================================== */

    /** "  #Dev , cloud, dev " -> ['dev', 'cloud'] */
    public static function normalizeTags(string $raw): array
    {
        $tags = [];
        foreach (explode(',', $raw) as $piece) {
            $tag = strtolower(trim(ltrim(trim($piece), '#')));
            if ($tag === '' || in_array($tag, $tags, true)) {
                continue;
            }
            $tags[] = mb_substr($tag, 0, self::MAX_TAG_LENGTH);
            if (count($tags) >= self::MAX_TAGS) {
                break;
            }
        }
        return $tags;
    }

    /** "dev,cloud" -> "dev, cloud" (para sa display sa input field) */
    public static function tagsToString(?string $stored): string
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return '';
        }
        return implode(', ', array_filter(array_map('trim', explode(',', $stored))));
    }

    /** ['dev' => 3, 'cloud' => 1, ...] para sa "All Tags" dropdown. */
    public function tagCountsForUser(int $userId): array
    {
        $stmt = $this->dbh->prepare(
            'SELECT t.tag_name, COUNT(vt.vault_id) AS total
             FROM tags t
             JOIN vault_tags vt ON vt.tag_id = t.tag_id
             WHERE t.user_id = :uid
             GROUP BY t.tag_id, t.tag_name
             ORDER BY t.tag_name'
        );
        $stmt->execute(['uid' => $userId]);

        $counts = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $counts[$row['tag_name']] = (int) $row['total'];
        }
        return $counts;
    }

    /** Pinapalitan ang buong tag set ng isang vault item. */
    private function syncTags(int $vaultId, int $userId, array $tags): void
    {
        $clear = $this->dbh->prepare('DELETE FROM vault_tags WHERE vault_id = :vid');
        $clear->execute(['vid' => $vaultId]);

        if ($tags !== []) {
            $find = $this->dbh->prepare(
                'SELECT tag_id FROM tags WHERE user_id = :uid AND tag_name = :name LIMIT 1'
            );
            $insertTag = $this->dbh->prepare(
                'INSERT INTO tags (user_id, tag_name) VALUES (:uid, :name)'
            );
            $link = $this->dbh->prepare(
                'INSERT IGNORE INTO vault_tags (vault_id, tag_id) VALUES (:vid, :tid)'
            );

            foreach ($tags as $tagName) {
                $find->execute(['uid' => $userId, 'name' => $tagName]);
                $tagId = $find->fetchColumn();

                if ($tagId === false) {
                    $insertTag->execute(['uid' => $userId, 'name' => $tagName]);
                    $tagId = $this->dbh->lastInsertId();
                }

                $link->execute(['vid' => $vaultId, 'tid' => (int) $tagId]);
            }
        }

        $this->deleteOrphanTags($userId);
    }

    /** Linisin ang tags na wala nang kahit isang naka-link na item. */
    private function deleteOrphanTags(int $userId): void
    {
        $stmt = $this->dbh->prepare(
            'DELETE t FROM tags t
             LEFT JOIN vault_tags vt ON vt.tag_id = t.tag_id
             WHERE t.user_id = :uid AND vt.vault_id IS NULL'
        );
        $stmt->execute(['uid' => $userId]);
    }

    /* ================= QUERIES ================= */

    public function countForUser(int $userId): int
    {
        $stmt = $this->dbh->prepare('SELECT COUNT(*) FROM vault WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function searchForUser(int $userId, ?int $folderId = null, string $search = '', ?string $tag = null): array
    {
        $conditions = ['v.user_id = :uid'];
        $params = ['uid' => $userId];

        if ($folderId !== null) {
            $conditions[] = 'v.folder_id = :folder_id';
            $params['folder_id'] = $folderId;
        }
        if ($search !== '') {
            $conditions[] = '(v.title LIKE :search OR v.account_username LIKE :search OR v.website_url LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        if ($tag !== null && $tag !== '') {
            // EXISTS para lumabas pa rin ang LAHAT ng tags ng item sa GROUP_CONCAT
            $conditions[] = 'EXISTS (
                SELECT 1 FROM vault_tags vtf
                JOIN tags tf ON tf.tag_id = vtf.tag_id
                WHERE vtf.vault_id = v.vault_id AND tf.tag_name = :tag
            )';
            $params['tag'] = strtolower($tag);
        }

        $where = implode(' AND ', $conditions);

        $stmt = $this->dbh->prepare(
            "SELECT v.*,
                    GROUP_CONCAT(t.tag_name ORDER BY t.tag_name SEPARATOR ',') AS tags
             FROM vault v
             LEFT JOIN vault_tags vt ON vt.vault_id = v.vault_id
             LEFT JOIN tags t        ON t.tag_id = vt.tag_id
             WHERE {$where}
             GROUP BY v.vault_id
             ORDER BY v.created_at DESC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $vaultId, int $userId): ?array
    {
        $stmt = $this->dbh->prepare(
            "SELECT v.*,
                    GROUP_CONCAT(t.tag_name ORDER BY t.tag_name SEPARATOR ',') AS tags
             FROM vault v
             LEFT JOIN vault_tags vt ON vt.vault_id = v.vault_id
             LEFT JOIN tags t        ON t.tag_id = vt.tag_id
             WHERE v.vault_id = :id AND v.user_id = :uid
             GROUP BY v.vault_id"
        );
        $stmt->execute(['id' => $vaultId, 'uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /* ================= MUTATIONS ================= */

    public function create(int $userId, array $data): array
    {
        $errors = $this->validate($data, false);
        if ($errors !== []) {
            return ['errors' => $errors];
        }

        $tags = self::normalizeTags((string) ($data['tags'] ?? ''));

        $this->dbh->beginTransaction();
        try {
            $stmt = $this->dbh->prepare(
                'INSERT INTO vault (user_id, folder_id, title, account_username, account_password, website_url, notes)
                 VALUES (:user_id, :folder_id, :title, :username, :password, :website_url, :notes)'
            );
            $stmt->execute([
                'user_id'     => $userId,
                'folder_id'   => $this->resolveFolderId($data['folder_id'] ?? null, $userId),
                'title'       => $data['title'],
                'username'    => $data['account_username'] !== '' ? $data['account_username'] : null,
                'password'    => $this->encryptSecret($data['account_password']),
                'website_url' => $data['website_url'] !== '' ? $data['website_url'] : null,
                'notes'       => $data['notes'] !== '' ? $data['notes'] : null,
            ]);

            $vaultId = (int) $this->dbh->lastInsertId();
            $this->syncTags($vaultId, $userId, $tags);

            $this->dbh->commit();
        } catch (Throwable $exception) {
            $this->dbh->rollBack();
            return ['errors' => ['form' => 'Could not save this credential. Please try again.']];
        }

        return ['errors' => [], 'vault_id' => $vaultId];
    }

    public function update(int $vaultId, int $userId, array $data): array
    {
        $errors = $this->validate($data, true);
        if ($errors !== []) {
            return ['errors' => $errors];
        }

        $existing = $this->find($vaultId, $userId);
        if (!$existing) {
            return ['errors' => ['form' => 'Item not found.']];
        }

        $passwordValue = $data['account_password'] !== ''
            ? $this->encryptSecret($data['account_password'])
            : $existing['account_password'];

        $tags = self::normalizeTags((string) ($data['tags'] ?? ''));

        $this->dbh->beginTransaction();
        try {
            $stmt = $this->dbh->prepare(
                'UPDATE vault
                 SET folder_id = :folder_id, title = :title, account_username = :username,
                     account_password = :password, website_url = :website_url, notes = :notes
                 WHERE vault_id = :id AND user_id = :uid'
            );
            $stmt->execute([
                'folder_id'   => $this->resolveFolderId($data['folder_id'] ?? null, $userId),
                'title'       => $data['title'],
                'username'    => $data['account_username'] !== '' ? $data['account_username'] : null,
                'password'    => $passwordValue,
                'website_url' => $data['website_url'] !== '' ? $data['website_url'] : null,
                'notes'       => $data['notes'] !== '' ? $data['notes'] : null,
                'id'          => $vaultId,
                'uid'         => $userId,
            ]);

            $this->syncTags($vaultId, $userId, $tags);

            $this->dbh->commit();
        } catch (Throwable $exception) {
            $this->dbh->rollBack();
            return ['errors' => ['form' => 'Could not update this credential. Please try again.']];
        }

        return ['errors' => []];
    }

    public function delete(int $vaultId, int $userId): bool
    {
        // Ang vault_tags ay ON DELETE CASCADE, kaya awtomatikong mabubura ang links.
        $stmt = $this->dbh->prepare('DELETE FROM vault WHERE vault_id = :id AND user_id = :uid');
        $ok = $stmt->execute(['id' => $vaultId, 'uid' => $userId]);
        $this->deleteOrphanTags($userId);
        return $ok;
    }

    /* ================= HELPERS ================= */

    /**
     * Tinitiyak na ang folder ay (a) pag-aari ng user at (b) type = 'passwords'.
     * Hindi ito kayang i-enforce ng foreign key lang.
     */
    private function resolveFolderId($raw, int $userId): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $folderId = (int) $raw;
        if ($folderId <= 0) {
            return null;
        }

        $stmt = $this->dbh->prepare(
            "SELECT folder_id FROM folders
             WHERE folder_id = :id AND user_id = :uid AND folder_type = 'passwords'"
        );
        $stmt->execute(['id' => $folderId, 'uid' => $userId]);

        return $stmt->fetchColumn() ? $folderId : null;
    }

    private function validate(array $data, bool $allowBlankPassword): array
    {
        $errors = [];

        if (trim($data['title'] ?? '') === '') {
            $errors['title'] = 'Title is required.';
        } elseif (mb_strlen($data['title']) > 100) {
            $errors['title'] = 'Title must be 100 characters or less.';
        }

        if (!$allowBlankPassword && trim($data['account_password'] ?? '') === '') {
            $errors['account_password'] = 'Password is required.';
        }

        $websiteUrl = trim($data['website_url'] ?? '');
        if ($websiteUrl !== '') {
            $normalized = preg_match('~^https?://~i', $websiteUrl) ? $websiteUrl : 'https://' . $websiteUrl;
            if (!filter_var($normalized, FILTER_VALIDATE_URL)) {
                $errors['website_url'] = 'Please enter a valid website URL.';
            }
        }

        $rawTags = trim((string) ($data['tags'] ?? ''));
        if ($rawTags !== '') {
            $pieces = array_values(array_filter(array_map('trim', explode(',', $rawTags)), static fn ($t) => $t !== ''));
            if (count($pieces) > self::MAX_TAGS) {
                $errors['tags'] = 'You can add up to ' . self::MAX_TAGS . ' tags only.';
            } else {
                foreach ($pieces as $piece) {
                    if (mb_strlen($piece) > self::MAX_TAG_LENGTH) {
                        $errors['tags'] = 'Each tag must be ' . self::MAX_TAG_LENGTH . ' characters or fewer.';
                        break;
                    }
                }
            }
        }

        return $errors;
    }
}