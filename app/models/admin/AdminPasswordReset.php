<?php

class AdminPasswordReset
{
	public const CODE_LENGTH = 6;

	public const MAX_ATTEMPTS = 5;

	// Verified reset window
	private const VERIFIED_WINDOW_MINUTES = 15;

	public function __construct(private PDO $db)
	{
	}

	// Code TTL
	public static function ttlMinutes(): int
	{
		return defined('PASSWORD_RESET_CODE_TTL_MINUTES') ? (int) PASSWORD_RESET_CODE_TTL_MINUTES : 10;
	}

	// New reset code
	/** @return array{code: string, ttl_minutes: int} */
	public function createForAdmin(int $adminId): array
	{
		$code = str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
		$ttl = self::ttlMinutes();

		$clear = $this->db->prepare('DELETE FROM admin_password_resets WHERE admin_id = :aid');
		$clear->execute(['aid' => $adminId]);

		$statement = $this->db->prepare(
			'INSERT INTO admin_password_resets (admin_id, code_hash, expires_at)
			 VALUES (:aid, :hash, DATE_ADD(NOW(), INTERVAL :ttl MINUTE))'
		);
		$statement->execute([
			'aid'  => $adminId,
			'hash' => password_hash($code, PASSWORD_DEFAULT),
			'ttl'  => $ttl,
		]);

		return ['code' => $code, 'ttl_minutes' => $ttl];
	}

	// Reset code
	/** @return array{ok: bool, error?: string} */
	public function verify(int $adminId, string $code): array
	{
		$statement = $this->db->prepare(
			'SELECT reset_id, code_hash, attempts, used_at, (expires_at < NOW()) AS is_expired
			 FROM admin_password_resets WHERE admin_id = :aid
			 ORDER BY reset_id DESC LIMIT 1'
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
			$mark = $this->db->prepare('UPDATE admin_password_resets SET verified_at = NOW() WHERE reset_id = :id');
			$mark->execute(['id' => $row['reset_id']]);

			return ['ok' => true];
		}

		$attempts = ((int) $row['attempts']) + 1;
		$bump = $this->db->prepare('UPDATE admin_password_resets SET attempts = :attempts WHERE reset_id = :id');
		$bump->execute(['attempts' => $attempts, 'id' => $row['reset_id']]);

		$left = self::MAX_ATTEMPTS - $attempts;
		if ($left <= 0) {
			return ['ok' => false, 'error' => 'Too many attempts. Please request a new code.'];
		}

		return [
			'ok'    => false,
			'error' => 'That code is incorrect. ' . $left . ' ' . ($left === 1 ? 'try' : 'tries') . ' left.',
		];
	}

	// Is there a verified, unused code that is still fresh?
	public function isVerified(int $adminId): bool
	{
		$statement = $this->db->prepare(
			'SELECT 1 FROM admin_password_resets
			 WHERE admin_id = :aid
			   AND verified_at IS NOT NULL
			   AND used_at IS NULL
			   AND verified_at > DATE_SUB(NOW(), INTERVAL ' . self::VERIFIED_WINDOW_MINUTES . ' MINUTE)
			 ORDER BY reset_id DESC LIMIT 1'
		);
		$statement->execute(['aid' => $adminId]);

		return (bool) $statement->fetchColumn();
	}

	// Mark the code as used
	public function markUsed(int $adminId): void
	{
		$statement = $this->db->prepare(
			'UPDATE admin_password_resets SET used_at = NOW() WHERE admin_id = :aid AND used_at IS NULL'
		);
		$statement->execute(['aid' => $adminId]);
	}
}
