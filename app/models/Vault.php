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

    /* ================= TAGS ================= */
    public static function normalizeTags(string $raw): array
    {
        $tags = [];
        foreach (explode(',', $raw) as $piece) {
            $tag = strtolower(trim(ltrim(trim($piece), '#')));
            if ($tag === '' || in_array($tag, $tags, true)) {
                continue;
            }
            $tags[] = $tag;
            if (count($tags) >= self::MAX_TAGS) {
                break;
            }
        }
        return $tags;
    }

    /** "dev,cloud" (stored) -> "dev, cloud" (para sa display sa input field) */
    public static function tagsToString(?string $stored): string
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return '';
        }
        return implode(', ', array_filter(array_map('trim', explode(',', $stored))));
    }

    /**
     * ['dev' => 3, 'cloud' => 1, ...] pababa sa a-z, para sa
     * "All Tags" dropdown sa toolbar (#dev (3), #cloud (1), ...).
     */
    public function tagCountsForUser(int $userId): array
    {
        $stmt = $this->dbh->prepare("SELECT tags FROM vault WHERE user_id = :uid AND tags IS NOT NULL AND tags <> ''");
        $stmt->execute(['uid' => $userId]);

        $counts = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $csv) {
            foreach (explode(',', (string) $csv) as $tag) {
                $tag = trim($tag);
                if ($tag === '') {
                    continue;
                }
                $counts[$tag] = ($counts[$tag] ?? 0) + 1;
            }
        }
        ksort($counts);
        return $counts;
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
        $conditions = ['user_id = :uid'];
        $params = ['uid' => $userId];

        if ($folderId !== null) {
            $conditions[] = 'folder_id = :folder_id';
            $params['folder_id'] = $folderId;
        }
        if ($search !== '') {
            $conditions[] = '(title LIKE :search OR account_username LIKE :search OR website_url LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        if ($tag !== null && $tag !== '') {
            // FIND_IN_SET
            $conditions[] = 'FIND_IN_SET(:tag, tags) > 0';
            $params['tag'] = $tag;
        }

        $where = implode(' AND ', $conditions);

        $stmt = $this->dbh->prepare("SELECT * FROM vault WHERE {$where} ORDER BY created_at DESC");
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $vaultId, int $userId): ?array
    {
        $stmt = $this->dbh->prepare('SELECT * FROM vault WHERE vault_id = :id AND user_id = :uid');
        $stmt->execute(['id' => $vaultId, 'uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(int $userId, array $data): array
    {
        $errors = $this->validate($data, false);
        if ($errors !== []) {
            return ['errors' => $errors];
        }

        $tags = self::normalizeTags((string) ($data['tags'] ?? ''));

        $stmt = $this->dbh->prepare(
            'INSERT INTO vault (user_id, folder_id, title, account_username, account_password, website_url, tags, notes)
             VALUES (:user_id, :folder_id, :title, :username, :password, :website_url, :tags, :notes)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'folder_id' => $data['folder_id'] ?: null,
            'title' => $data['title'],
            'username' => $data['account_username'] !== '' ? $data['account_username'] : null,
            'password' => $this->encryptSecret($data['account_password']),
            'website_url' => $data['website_url'] !== '' ? $data['website_url'] : null,
            'tags' => $tags !== [] ? implode(',', $tags) : null,
            'notes' => $data['notes'] !== '' ? $data['notes'] : null,
        ]);

        return ['errors' => [], 'vault_id' => (int) $this->dbh->lastInsertId()];
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

        $stmt = $this->dbh->prepare(
            'UPDATE vault SET folder_id = :folder_id, title = :title, account_username = :username,
             account_password = :password, website_url = :website_url, tags = :tags, notes = :notes
             WHERE vault_id = :id AND user_id = :uid'
        );
        $stmt->execute([
            'folder_id' => $data['folder_id'] ?: null,
            'title' => $data['title'],
            'username' => $data['account_username'] !== '' ? $data['account_username'] : null,
            'password' => $passwordValue,
            'website_url' => $data['website_url'] !== '' ? $data['website_url'] : null,
            'tags' => $tags !== [] ? implode(',', $tags) : null,
            'notes' => $data['notes'] !== '' ? $data['notes'] : null,
            'id' => $vaultId,
            'uid' => $userId,
        ]);

        return ['errors' => []];
    }

    public function delete(int $vaultId, int $userId): bool
    {
        $stmt = $this->dbh->prepare('DELETE FROM vault WHERE vault_id = :id AND user_id = :uid');
        return $stmt->execute(['id' => $vaultId, 'uid' => $userId]);
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