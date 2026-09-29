<?php

class EmailChange
{
    public const CODE_LENGTH = 6;

    public const MAX_ATTEMPTS = 5;

    private PDO $dbh;

    public function __construct(PDO $dbh)
    {
        $this->dbh = $dbh;
    }

    // Code TTL
    public static function ttlMinutes(): int
    {
        return defined('EMAIL_CHANGE_CODE_TTL_MINUTES') ? (int) EMAIL_CHANGE_CODE_TTL_MINUTES : 10;
    }

    /** @return array{code: string, ttl_minutes: int} */
    public function createForUser(int $userId, string $newEmail): array
    {
        $code = str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
        $ttl = self::ttlMinutes();

        // Keep one active code
        $clear = $this->dbh->prepare('DELETE FROM email_changes WHERE user_id = :uid');
        $clear->execute(['uid' => $userId]);

        $stmt = $this->dbh->prepare(
            'INSERT INTO email_changes (user_id, new_email, code_hash, expires_at)
             VALUES (:uid, :new_email, :hash, DATE_ADD(NOW(), INTERVAL :ttl MINUTE))'
        );
        $stmt->execute([
            'uid'       => $userId,
            'new_email' => $newEmail,
            'hash'      => password_hash($code, PASSWORD_DEFAULT),
            'ttl'       => $ttl,
        ]);

        return ['code' => $code, 'ttl_minutes' => $ttl];
    }

    // Pending request
    public function findPendingForUser(int $userId): ?array
    {
        $stmt = $this->dbh->prepare(
            'SELECT change_id, new_email, expires_at, attempts, verified_at, used_at
             FROM email_changes WHERE user_id = :uid
             ORDER BY change_id DESC LIMIT 1'
        );
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return array{ok: bool, error?: string, attempts_left?: int} */
    public function verify(int $userId, string $code): array
    {
        $stmt = $this->dbh->prepare(
            'SELECT change_id, new_email, code_hash, expires_at, attempts, used_at
             FROM email_changes WHERE user_id = :uid
             ORDER BY change_id DESC LIMIT 1'
        );
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || $row['used_at'] !== null) {
            return ['ok' => false, 'error' => 'That code is incorrect or has expired. Please request a new one.'];
        }

        if (strtotime($row['expires_at']) < time()) {
            return ['ok' => false, 'error' => 'This code has expired. Please request a new one.'];
        }

        if ((int) $row['attempts'] >= self::MAX_ATTEMPTS) {
            return ['ok' => false, 'error' => 'Too many attempts. Please request a new code.'];
        }

        if (password_verify($code, $row['code_hash'])) {
            $mark = $this->dbh->prepare('UPDATE email_changes SET verified_at = NOW() WHERE change_id = :id');
            $mark->execute(['id' => $row['change_id']]);

            return ['ok' => true, 'new_email' => $row['new_email']];
        }

        $attempts = ((int) $row['attempts']) + 1;
        $bump = $this->dbh->prepare('UPDATE email_changes SET attempts = :attempts WHERE change_id = :id');
        $bump->execute(['attempts' => $attempts, 'id' => $row['change_id']]);

        $left = self::MAX_ATTEMPTS - $attempts;
        if ($left <= 0) {
            return ['ok' => false, 'error' => 'Too many attempts. Please request a new code.'];
        }

        return [
            'ok' => false,
            'error' => 'That code is incorrect. ' . $left . ' ' . ($left === 1 ? 'try' : 'tries') . ' left.',
            'attempts_left' => $left,
        ];
    }

    // Mark code used
    public function markUsed(int $userId): void
    {
        $stmt = $this->dbh->prepare(
            'UPDATE email_changes SET used_at = NOW()
             WHERE user_id = :uid AND used_at IS NULL
             ORDER BY change_id DESC LIMIT 1'
        );
        $stmt->execute(['uid' => $userId]);
    }

    // Clear request
    public function clearForUser(int $userId): void
    {
        $stmt = $this->dbh->prepare('DELETE FROM email_changes WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);
    }
}
