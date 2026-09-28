<?php
require_once __DIR__ . '/../../models/admin/Admin.php';

class AdminAuthController
{
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

		// Keep login errors generic
		if (!$admin || !password_verify($password, $admin['password_hash'])) {
			return [
				'errors' => ['form' => 'The email or password is incorrect.'],
				'values' => ['email' => $email],
			];
		}

		if ($admin['status'] !== 'active') {
			return [
				'errors' => ['form' => 'This administrator account is not currently active.'],
				'values' => ['email' => $email],
			];
		}

		return ['errors' => [], 'values' => $admin];
	}
}
