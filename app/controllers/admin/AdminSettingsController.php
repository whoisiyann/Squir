<?php
require_once __DIR__ . '/../../models/admin/Admin.php';
require_once __DIR__ . '/../../models/ActivityLog.php';
require_once __DIR__ . '/../../models/admin/AdminPasswordReset.php';
require_once __DIR__ . '/../../models/admin/AdminEmailChange.php';
require_once __DIR__ . '/../../../includes/mailer.php';

class AdminSettingsController
{
	private const EXPORT_BATCH = 1000;

	private const EXPORT_MAX = 50000;

	public function __construct(
		private Admin $admin,
		private ActivityLog $activityLog,
		private ?AdminPasswordReset $passwordReset = null,
		private ?AdminEmailChange $emailChange = null
	) {
	}

	// Profile update
	public function updateProfile(int $adminId, array $post): array
	{
		$current = $this->admin->findById($adminId);
		if (!$current) {
			return ['errors' => ['form' => 'Your admin account could not be found.']];
		}

		$fullName = trim((string) ($post['full_name'] ?? ''));
		$username = trim((string) ($post['username'] ?? ''));
		$errors = [];

		if ($fullName === '') {
			$errors['full_name'] = 'Full name is required.';
		} elseif (mb_strlen($fullName) > 100) {
			$errors['full_name'] = 'Full name must be 100 characters or fewer.';
		}

		if ($username === '') {
			$errors['username'] = 'Username is required.';
		} elseif (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
			$errors['username'] = 'Username must be 3-50 letters, numbers, dots, dashes, or underscores.';
		} elseif ($this->admin->usernameExists($username, $adminId)) {
			$errors['username'] = 'Username is already taken.';
		}

		if ($errors !== []) {
			return ['errors' => $errors];
		}

		$changes = [];
		if ($current['full_name'] !== $fullName) {
			$changes[] = 'name';
		}
		if ($current['username'] !== $username) {
			$changes[] = 'username';
		}

		if ($changes !== []) {
			$this->admin->updateProfile($adminId, $fullName, $username);
			$this->activityLog->logAdmin($adminId, 'admin_profile_updated', 'Updated ' . implode(', ', $changes));
		}

		return ['errors' => [], 'admin' => $this->admin->findById($adminId)];
	}

	// Send email code
	public function requestEmailChange(int $adminId, array $post): array
	{
		if ($this->emailChange === null) {
			return ['errors' => ['form' => 'Email change is not available right now.']];
		}

		$newEmail = strtolower(trim((string) ($post['new_email'] ?? '')));

		if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
			return ['errors' => ['new_email' => 'Please enter a valid email address.']];
		}
		if (mb_strlen($newEmail) > 100) {
			return ['errors' => ['new_email' => 'Email address must be 100 characters or fewer.']];
		}

		$current = $this->admin->findById($adminId);
		if (!$current) {
			return ['errors' => ['form' => 'Your admin account could not be found.']];
		}
		if (strtolower((string) $current['email']) === $newEmail) {
			return ['errors' => ['new_email' => 'That is already your current email address.']];
		}
		if ($this->admin->emailExists($newEmail, $adminId)) {
			return ['errors' => ['new_email' => 'An account with this email already exists.']];
		}

		return $this->issueEmailChangeCode($adminId, $newEmail, (string) $current['full_name']);
	}

	// Resend email code
	public function resendEmailChange(int $adminId): array
	{
		if ($this->emailChange === null) {
			return ['errors' => ['form' => 'Email change is not available right now.']];
		}

		$pending = $this->emailChange->findPendingForAdmin($adminId);
		if (!$pending || $pending['used_at'] !== null) {
			return ['errors' => ['form' => 'There is no pending email change. Please start again.']];
		}

		$current = $this->admin->findById($adminId);
		if (!$current) {
			return ['errors' => ['form' => 'Your admin account could not be found.']];
		}

		// Email may be taken.
		if ($this->admin->emailExists((string) $pending['new_email'], $adminId)) {
			$this->emailChange->clearForAdmin($adminId);
			return ['errors' => ['form' => 'An account with this email already exists.']];
		}

		return $this->issueEmailChangeCode($adminId, (string) $pending['new_email'], (string) $current['full_name']);
	}

	// Email code
	public function verifyEmailChange(int $adminId, string $code): array
	{
		$code = trim($code);

		if ($this->emailChange === null || !preg_match('/^\d{' . AdminEmailChange::CODE_LENGTH . '}$/', $code)) {
			return ['errors' => ['code' => 'Enter all ' . AdminEmailChange::CODE_LENGTH . ' digits.']];
		}

		$result = $this->emailChange->verify($adminId, $code);
		if (!$result['ok']) {
			return ['errors' => ['code' => $result['error'] ?? 'That code is incorrect.']];
		}

		$newEmail = (string) $result['new_email'];

		// Recheck email.
		if ($this->admin->emailExists($newEmail, $adminId)) {
			$this->emailChange->clearForAdmin($adminId);
			return ['errors' => ['code' => 'An account with this email already exists.']];
		}

		$this->admin->updateEmail($adminId, $newEmail);
		$this->emailChange->markUsed($adminId);
		$this->activityLog->logAdmin($adminId, 'admin_email_changed', 'Updated email');

		return ['errors' => [], 'email' => $newEmail];
	}

	// Create email code
	private function issueEmailChangeCode(int $adminId, string $newEmail, string $fullName): array
	{
		$change = $this->emailChange->createForAdmin($adminId, $newEmail);
		$sent = sendEmailChangeVerificationEmail($newEmail, $fullName, $change['code'], $change['ttl_minutes']);

		$devCode = null;
		if (!$sent) {
			if (defined('MAIL_DEV_FALLBACK') && MAIL_DEV_FALLBACK) {
				// Show dev code when mail is off.
				$devCode = $change['code'];
			} else {
				$this->emailChange->clearForAdmin($adminId);
				return ['errors' => ['form' => 'We could not send the email. Please try again later.']];
			}
		}

		return ['errors' => [], 'new_email' => $newEmail, 'dev_code' => $devCode];
	}

	// Change password
	public function changePassword(int $adminId, array $post): array
	{
		$currentPassword = (string) ($post['current_password'] ?? '');
		$new = (string) ($post['new_password'] ?? '');
		$confirm = (string) ($post['confirm_password'] ?? '');
		$errors = [];

		$admin = $this->admin->findById($adminId);
		if (!$admin || $currentPassword === '' || !password_verify($currentPassword, (string) $admin['password_hash'])) {
			$errors['current_password'] = 'Your current password is incorrect.';
		}

		if (!preg_match('/^(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $new)) {
			$errors['new_password'] = 'Password must be at least 8 characters and include a number and a special character.';
		} elseif (isset($admin['password_hash']) && password_verify($new, (string) $admin['password_hash'])) {
			$errors['new_password'] = 'Your new password must be different from your current password.';
		}

		if ($confirm === '' || $confirm !== $new) {
			$errors['confirm_password'] = 'Passwords do not match.';
		}

		if ($errors !== []) {
			return ['errors' => $errors];
		}

		$this->admin->updatePassword($adminId, password_hash($new, PASSWORD_DEFAULT));
		$this->activityLog->logAdmin($adminId, 'admin_password_changed');

		return ['errors' => []];
	}

	// Send reset code
	public function sendResetCode(int $adminId): array
	{
		$admin = $this->admin->findById($adminId);
		if (!$admin || $this->passwordReset === null) {
			return ['errors' => ['form' => 'Your admin account could not be found.']];
		}

		$reset = $this->passwordReset->createForAdmin($adminId);
		$sent = sendPasswordResetEmail(
			(string) $admin['email'],
			(string) $admin['full_name'],
			$reset['code'],
			$reset['ttl_minutes']
		);

		$devCode = null;
		if (!$sent) {
			if (defined('MAIL_DEV_FALLBACK') && MAIL_DEV_FALLBACK) {
				// Show dev code when mail is off.
				$devCode = $reset['code'];
			} else {
				return ['errors' => ['form' => 'We could not send the email. Please try again later.']];
			}
		}

		return [
			'errors'   => [],
			'email'    => self::maskEmail((string) $admin['email']),
			'dev_code' => $devCode,
		];
	}

	// Reset code
	public function verifyResetCode(int $adminId, string $code): array
	{
		$code = trim($code);

		if ($this->passwordReset === null || !preg_match('/^\d{' . AdminPasswordReset::CODE_LENGTH . '}$/', $code)) {
			return ['ok' => false, 'error' => 'Enter all ' . AdminPasswordReset::CODE_LENGTH . ' digits.'];
		}

		return $this->passwordReset->verify($adminId, $code);
	}

	// Reset password
	public function resetPassword(int $adminId, array $post): array
	{
		$new = (string) ($post['new_password'] ?? '');
		$confirm = (string) ($post['confirm_password'] ?? '');
		$errors = [];

		if ($this->passwordReset === null || !$this->passwordReset->isVerified($adminId)) {
			return ['errors' => ['form' => 'Your reset session has expired. Please start again.']];
		}

		$admin = $this->admin->findById($adminId);
		if (!$admin) {
			return ['errors' => ['form' => 'Your admin account could not be found.']];
		}

		if (!preg_match('/^(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $new)) {
			$errors['new_password'] = 'Password must be at least 8 characters and include a number and a special character.';
		} elseif (password_verify($new, (string) $admin['password_hash'])) {
			$errors['new_password'] = 'Your new password must be different from your current password.';
		}

		if ($confirm === '' || $confirm !== $new) {
			$errors['confirm_password'] = 'Passwords do not match.';
		}

		if ($errors !== []) {
			return ['errors' => $errors];
		}

		$this->admin->updatePassword($adminId, password_hash($new, PASSWORD_DEFAULT));
		$this->passwordReset->markUsed($adminId);
		$this->activityLog->logAdmin($adminId, 'admin_password_reset', 'Reset password using an email code');

		return ['errors' => []];
	}

	// Mask email
	private static function maskEmail(string $email): string
	{
		$at = strpos($email, '@');
		if ($at === false || $at < 1) {
			return $email;
		}

		return mb_substr($email, 0, 1) . str_repeat('*', max(2, min($at - 1, 6))) . substr($email, $at);
	}

	// Recent admin activity
	public function listActivity(int $adminId, int $limit = 50): array
	{
		return $this->activityLog->listForAdminActor($adminId, $limit);
	}

	// Clear admin activity
	public function clearActivity(int $adminId): bool
	{
		return $this->activityLog->clearForAdminActor($adminId);
	}

	public function exportHeader(): array
	{
		return ['Type', 'Name', 'Date & Time', 'Activity', 'Details', 'Device', 'IP Address'];
	}

	// Export CSV rows
	public function exportBatches(bool $userOnly): Generator
	{
		$offset = 0;
		while ($offset < self::EXPORT_MAX) {
			$rows = $this->activityLog->exportBatch($userOnly, self::EXPORT_BATCH, $offset);
			if ($rows === []) {
				return;
			}

			$batch = [];
			foreach ($rows as $row) {
				$batch[] = $this->exportRow($row);
			}
			yield $batch;

			if (count($rows) < self::EXPORT_BATCH) {
				return;
			}
			$offset += self::EXPORT_BATCH;
		}
	}

	// Format CSV row
	private function exportRow(array $row): array
	{
		$action = (string) $row['action'];

		if (!empty($row['admin_id'])) {
			$type = 'Admin';
			$name = (string) ($row['admin_name'] ?? 'Deleted admin');
		} elseif (!empty($row['user_id'])) {
			$type = 'User';
			$name = (string) ($row['user_name'] ?? 'Deleted user');
		} else {
			$type = 'Deleted account';
			$name = 'Deleted account';
		}

		$device = (string) ($row['device'] ?? '');
		if ($device === '') {
			$device = ActivityLog::detectDevice($row['user_agent'] ?? null);
		}

		$detail = trim((string) ($row['description'] ?? ''));

		return array_map([self::class, 'csvCell'], [
			$type,
			$name,
			date('Y-m-d H:i:s', strtotime((string) $row['created_at'])),
			ActivityLog::adminLabel($action),
			$detail !== '' ? $detail : '—',
			$device,
			ActivityLog::displayIp($row['ip_address'] ?? null),
		]);
	}

	// Prevent CSV formula injection.
	private static function csvCell(mixed $value): string
	{
		$text = (string) $value;
		if ($text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
			$text = "'" . $text;
		}

		return $text;
	}
}