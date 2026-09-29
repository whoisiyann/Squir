<?php

class AdminEmailChange
{
	public const CODE_LENGTH = 6;

	public const MAX_ATTEMPTS = 5;

	public function __construct(private PDO $db)
	{
	}

	// Code TTL
	public static function ttlMinutes(): int
	{
		return defined('EMAIL_CHANGE_CODE_TTL_MINUTES') ? (int) EMAIL_CHANGE_CODE_TTL_MINUTES : 10;
	}

	// New email code
	/** @return array{code: string, ttl_minutes: int} */
	public function createForAdmin(int $adminId, string $newEmail): array
	{
		$code = str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
		$ttl = self::ttlMinutes();

		$clear = $this->db->prepare('DELETE FROM admin_email_changes WHERE admin_id = :aid');
		$clear->execute(['aid' => $adminId]);

		$statement = $this->db->prepare(
			'INSERT INTO admin_email_changes (admin_id, new_email, code_hash, expires_at)
			 VALUES (:aid, :new_email, :hash, DATE_ADD(NOW(), INTERVAL :ttl MINUTE))'
		);
		$statement->execute([
			'aid'       => $adminId,
			'new_email' => $newEmail,
			'hash'      => password_hash($code, PASSWORD_DEFAULT),
			'ttl'       => $ttl,
		]);

		return ['code' => $code, 'ttl_minutes' => $ttl];
	}

	// Pending request
	public function findPendingForAdmin(int $adminId): ?array
	{
		$statement = $this->db->prepare(
			'SELECT change_id, new_email, expires_at, attempts, verified_at, used_at
			 FROM admin_email_changes WHERE admin_id = :aid
			 ORDER BY change_id DESC LIMIT 1'
		);
		$statement->execute(['aid' => $adminId]);
		$row = $statement->fetch(PDO::FETCH_ASSOC);

		return $row ?: null;
	}

	// Verification code
	/** @return array{ok: bool, error?: string, new_email?: string} */
	public function verify(int $adminId, string $code): array
	{
		$statement = $this->db->prepare(
			'SELECT change_id, new_email, code_hash, attempts, used_at, (expires_at < NOW()) AS is_expired
			 FROM admin_email_changes WHERE admin_id = :aid
			 ORDER BY change_id DESC LIMIT 1'
		);
		$statement->execute(['aid' => $adminId]);
		$row = $statement->fetch(PDO::FETCH_ASSOC);

		if (!$row || $row['used_at'] !== null) {
			return ['ok' => false, 'error' => 'That code is incorrect or has expired. Please request a new one.'];
		}

		if ((int) $row['is_expired'] === 1) {
			return ['ok' => false, 'error' => 'This code has expired. Please request a new one.'];
		}

		if ((int) $row['attempts'] >= self::MAX_ATTEMPTS) {
			return ['ok' => false, 'error' => 'Too many attempts. Please request a new code.'];
		}

		if (password_verify($code, (string) $row['code_hash'])) {
			$mark = $this->db->prepare('UPDATE admin_email_changes SET verified_at = NOW() WHERE change_id = :id');
			$mark->execute(['id' => $row['change_id']]);

			return ['ok' => true, 'new_email' => (string) $row['new_email']];
		}

		$attempts = ((int) $row['attempts']) + 1;
		$bump = $this->db->prepare('UPDATE admin_email_changes SET attempts = :attempts WHERE change_id = :id');
		$bump->execute(['attempts' => $attempts, 'id' => $row['change_id']]);

		$left = self::MAX_ATTEMPTS - $attempts;
		if ($left <= 0) {
			return ['ok' => false, 'error' => 'Too many attempts. Please request a new code.'];
		}

		return [
			'ok'    => false,
			'error' => 'That code is incorrect. ' . $left . ' ' . ($left === 1 ? 'try' : 'tries') . ' left.',
		];
	}

	// Mark as used
	public function markUsed(int $adminId): void
	{
		$statement = $this->db->prepare(
			'UPDATE admin_email_changes SET used_at = NOW() WHERE admin_id = :aid AND used_at IS NULL'
		);
		$statement->execute(['aid' => $adminId]);
	}

	// Remove pending request
	public function clearForAdmin(int $adminId): void
	{
		$statement = $this->db->prepare('DELETE FROM admin_email_changes WHERE admin_id = :aid');
		$statement->execute(['aid' => $adminId]);
	}
}
