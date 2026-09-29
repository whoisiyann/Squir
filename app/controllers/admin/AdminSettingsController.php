<?php
require_once __DIR__ . '/../../models/admin/Admin.php';
require_once __DIR__ . '/../../models/ActivityLog.php';

class AdminSettingsController
{
	private const EXPORT_BATCH = 1000;

	private const EXPORT_MAX = 50000;

	public function __construct(private Admin $admin, private ActivityLog $activityLog)
	{
	}

	// Update name, username, and email
	public function updateProfile(int $adminId, array $post): array
	{
		$current = $this->admin->findById($adminId);
		if (!$current) {
			return ['errors' => ['form' => 'Your admin account could not be found.']];
		}

		$fullName = trim((string) ($post['full_name'] ?? ''));
		$username = trim((string) ($post['username'] ?? ''));
		$email = strtolower(trim((string) ($post['email'] ?? '')));
		$password = (string) ($post['current_password'] ?? '');
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

		$emailChanged = false;
		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$errors['email'] = 'Please enter a valid email address.';
		} elseif (mb_strlen($email) > 100) {
			$errors['email'] = 'Email address must be 100 characters or fewer.';
		} elseif (strtolower((string) $current['email']) !== $email) {
			$emailChanged = true;
			if ($this->admin->emailExists($email, $adminId)) {
				$errors['email'] = 'An account with this email already exists.';
			}
		}

		// Admins sign in with email, so changing it needs the password.
		if ($emailChanged && !isset($errors['email'])) {
			if ($password === '') {
				$errors['current_password'] = 'Enter your current password to change your email.';
			} elseif (!password_verify($password, (string) $current['password_hash'])) {
				$errors['current_password'] = 'Your current password is incorrect.';
			}
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
		if ($emailChanged) {
			$changes[] = 'email';
		}

		if ($changes !== []) {
			$this->admin->updateProfile($adminId, $fullName, $username, $email);
			$this->activityLog->logAdmin($adminId, 'admin_profile_updated', 'Updated ' . implode(', ', $changes));
		}

		return ['errors' => [], 'admin' => $this->admin->findById($adminId)];
	}

	// Change the admin password
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

	// Recent activity of this admin
	public function listActivity(int $adminId, int $limit = 50): array
	{
		return $this->activityLog->listForAdminActor($adminId, $limit);
	}

	// Clear this admin's own activity log
	public function clearActivity(int $adminId): bool
	{
		return $this->activityLog->clearForAdminActor($adminId);
	}

	public function exportHeader(): array
	{
		return ['Type', 'Name', 'Date & Time', 'Activity', 'Details', 'Device', 'IP Address'];
	}

	// Yield CSV rows in batches so large exports stay light.
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

	// Format one CSV row
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