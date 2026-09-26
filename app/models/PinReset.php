<?php

class PinReset
{
    public const CODE_LENGTH = 6;

    public const MAX_ATTEMPTS = 5;

    private PDO $dbh;

    public function __construct(PDO $dbh)
    {
        $this->dbh = $dbh;
    }

    // Return the configured code lifetime, in minutes
    public static function ttlMinutes(): int
    {
        return defined('PIN_RESET_CODE_TTL_MINUTES') ? (int) PIN_RESET_CODE_TTL_MINUTES : 10;
    }

    /** @return array{code: string, ttl_minutes: int} */
    public function createForUser(int $userId): array
    {
        $code = str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
        $ttl = self::ttlMinutes();

        // Keep one active code
        $clear = $this->dbh->prepare('DELETE FROM pin_resets WHERE user_id = :uid');
        $clear->execute(['uid' => $userId]);

        $stmt = $this->dbh->prepare(
            'INSERT INTO pin_resets (user_id, code_hash, expires_at)
             VALUES (:uid, :hash, DATE_ADD(NOW(), INTERVAL :ttl MINUTE))'
        );
        $stmt->execute([
            'uid'  => $userId,
            'hash' => password_hash($code, PASSWORD_DEFAULT),
            'ttl'  => $ttl,
        ]);

        return ['code' => $code, 'ttl_minutes' => $ttl];
    }

    /** @return array{ok: bool, error?: string, attempts_left?: int} */
    public function verify(int $userId, string $code): array
    {
        $stmt = $this->dbh->prepare(
            'SELECT reset_id, code_hash, expires_at, attempts, used_at
             FROM pin_resets WHERE user_id = :uid
             ORDER BY reset_id DESC LIMIT 1'
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
            $mark = $this->dbh->prepare('UPDATE pin_resets SET verified_at = NOW() WHERE reset_id = :id');
            $mark->execute(['id' => $row['reset_id']]);

            return ['ok' => true];
        }

        $attempts = ((int) $row['attempts']) + 1;
        $bump = $this->dbh->prepare('UPDATE pin_resets SET attempts = :attempts WHERE reset_id = :id');
        $bump->execute(['attempts' => $attempts, 'id' => $row['reset_id']]);

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

    // Check the verified code
    public function isVerified(int $userId): bool
    {
        $stmt = $this->dbh->prepare(
            'SELECT verified_at, used_at FROM pin_resets
             WHERE user_id = :uid ORDER BY reset_id DESC LIMIT 1'
        );
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (bool) $row && $row['verified_at'] !== null && $row['used_at'] === null;
    }

    // Mark the verified code as used
    public function markUsed(int $userId): void
    {
        $stmt = $this->dbh->prepare(
            'UPDATE pin_resets SET used_at = NOW()
             WHERE user_id = :uid AND used_at IS NULL
             ORDER BY reset_id DESC LIMIT 1'
        );
        $stmt->execute(['uid' => $userId]);
    }
}
