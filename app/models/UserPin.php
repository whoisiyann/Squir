<?php

class UserPin
{
    public const MAX_ATTEMPTS = 5;

    public const LOCK_MINUTES = 5;

    private PDO $dbh;

    // Initialize PIN data access
    public function __construct(PDO $dbh)
    {
        $this->dbh = $dbh;
    }

    // Return the required PIN length
    public static function length(): int
    {
        return defined('PIN_LENGTH') ? (int) PIN_LENGTH : 6;
    }

    // Check whether a user PIN exists
    public function exists(int $userId): bool
    {
        $stmt = $this->dbh->prepare('SELECT 1 FROM user_pins WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);
        return (bool) $stmt->fetchColumn();
    }

    // Store a user PIN
    public function create(int $userId, string $pin): bool
    {
        $stmt = $this->dbh->prepare(
            'INSERT INTO user_pins (user_id, pin_hash) VALUES (:uid, :hash)
             ON DUPLICATE KEY UPDATE pin_hash = VALUES(pin_hash),
                                     failed_attempts = 0,
                                     locked_until = NULL'
        );
        return $stmt->execute([
            'uid'  => $userId,
            'hash' => password_hash($pin, PASSWORD_DEFAULT),
        ]);
    }

    /**
     * @return array{ok: bool, error?: string, attempts_left?: int, locked_seconds?: int}
     */
    // Verify a user PIN
    public function verify(int $userId, string $pin): array
    {
        $stmt = $this->dbh->prepare(
            'SELECT pin_hash, failed_attempts, locked_until FROM user_pins WHERE user_id = :uid'
        );
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ['ok' => false, 'error' => 'No PIN has been set for this account yet.'];
        }

        if ($row['locked_until'] !== null && strtotime($row['locked_until']) > time()) {
            $seconds = strtotime($row['locked_until']) - time();
            return [
                'ok' => false,
                'error' => 'Too many wrong tries. Try again in ' . ceil($seconds / 60) . ' minute(s).',
                'locked_seconds' => $seconds,
            ];
        }

        if (password_verify($pin, $row['pin_hash'])) {
            $reset = $this->dbh->prepare(
                'UPDATE user_pins
                 SET failed_attempts = 0, locked_until = NULL, last_verified_at = NOW()
                 WHERE user_id = :uid'
            );
            $reset->execute(['uid' => $userId]);
            return ['ok' => true];
        }

        $attempts = ((int) $row['failed_attempts']) + 1;

        if ($attempts >= self::MAX_ATTEMPTS) {
            $lock = $this->dbh->prepare(
                'UPDATE user_pins
                 SET failed_attempts = 0,
                     locked_until = DATE_ADD(NOW(), INTERVAL :mins MINUTE)
                 WHERE user_id = :uid'
            );
            $lock->execute(['mins' => self::LOCK_MINUTES, 'uid' => $userId]);

            return [
                'ok' => false,
                'error' => 'Too many wrong tries. Try again in ' . self::LOCK_MINUTES . ' minutes.',
                'locked_seconds' => self::LOCK_MINUTES * 60,
            ];
        }

        $bump = $this->dbh->prepare(
            'UPDATE user_pins SET failed_attempts = :attempts WHERE user_id = :uid'
        );
        $bump->execute(['attempts' => $attempts, 'uid' => $userId]);

        $left = self::MAX_ATTEMPTS - $attempts;

        return [
            'ok' => false,
            'error' => 'That PIN is incorrect. ' . $left . ' ' . ($left === 1 ? 'try' : 'tries') . ' left.',
            'attempts_left' => $left,
        ];
    }


    // Change a user PIN
    public function change(int $userId, string $currentPin, string $newPin): array
    {
        $check = $this->verify($userId, $currentPin);
        if (!$check['ok']) {
            return $check;
        }

        $this->create($userId, $newPin);
        return ['ok' => true];
    }
}