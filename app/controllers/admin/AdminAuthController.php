<?php
require_once __DIR__ . '/../../models/admin/Admin.php';

class AdminAuthController
{
	private const MAX_ATTEMPTS = 5;
	private const LOCK_MINUTES = 15;

	public function __construct(private Admin $admin)
	{
	}

	// Handle admin login
	public function login(array $input): array
	{
		$email = strtolower(trim((string) ($input['email'] ?? '')));
		$password = (string) ($input['password'] ?? '');
		$errors = [];

		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$errors['email'] = 'Please enter a valid email address.';
		}

		if ($password === '') {
			$errors['password'] = 'Password is required.';
		}

		if ($errors !== []) {
			return ['errors' => $errors, 'values' => ['email' => $email]];
		}

		$admin = $this->admin->findByEmail($email);

		// Check whether the account is locked.
		if ($admin) {
			$secondsLeft = $this->admin->lockSecondsRemaining((int) $admin['admin_id']);
			if ($secondsLeft > 0) {
				return [
					'errors' => ['form' => 'Too many failed attempts. Please try again in ' . (int) ceil($secondsLeft / 60) . ' minute(s).'],
					'values' => ['email' => $email],
				];
			}
		}

		// Keep login errors generic
		if (!$admin || !password_verify($password, $admin['password_hash'])) {
			if ($admin) {
				$this->admin->recordFailedLogin((int) $admin['admin_id'], self::MAX_ATTEMPTS, self::LOCK_MINUTES);
			}

			return [
				'errors' => ['form' => 'The email or password is incorrect.'],
				'values' => ['email' => $email],
			];
		}

		// Clear failed attempts after successful login.
		$this->admin->clearFailedLogins((int) $admin['admin_id']);

		if ($admin['status'] !== 'active') {
			return [
				'errors' => ['form' => 'This administrator account is not currently active.'],
				'values' => ['email' => $email],
			];
		}

		return ['errors' => [], 'values' => $admin];
	}
}
