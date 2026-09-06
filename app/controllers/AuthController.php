<!-- AuthController.php -->

<?php
require_once __DIR__ . '/../models/User.php';

class AuthController
{
	public function __construct(private User $user)
	{
	}

	public function register(array $input): array
	{
		$fullName = trim((string) ($input['full_name'] ?? ''));
		$username = trim((string) ($input['username'] ?? ''));
		$email = strtolower(trim((string) ($input['email'] ?? '')));
		$password = (string) ($input['password'] ?? '');
		$passwordConfirmation = (string) ($input['password_confirmation'] ?? '');
		$errors = [];

		if ($fullName === '') {
			$errors['full_name'] = 'Full name is required.';
		} elseif (mb_strlen($fullName) > 100) {
			$errors['full_name'] = 'Full name must be 100 characters or fewer.';
		}

		if ($username === '' && $fullName !== '') {
			$username = $this->createUsernameFromName($fullName);
		}

		if ($username === '') {
			$errors['username'] = 'Username is required.';
		} elseif (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
			$errors['username'] = 'Username must be 3-50 letters, numbers, dots, dashes, or underscores.';
		} elseif ($this->user->usernameExists($username)) {
			$errors['username'] = 'Username is already taken.';
		}

		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$errors['email'] = 'Please enter a valid email address.';
		} elseif (mb_strlen($email) > 100) {
			$errors['email'] = 'Email address must be 100 characters or fewer.';
		} elseif ($this->user->emailExists($email)) {
			$errors['email'] = 'An account with this email already exists.';
		}

		if ($password === '') {
			$errors['password'] = 'Password is required.';
		} elseif (strlen($password) < 8) {
			$errors['password'] = 'Password must be at least 8 characters.';
		}

		if ($passwordConfirmation === '') {
			$errors['password_confirmation'] = 'Please confirm your password.';
		} elseif ($password !== $passwordConfirmation) {
			$errors['password_confirmation'] = 'Passwords do not match.';
		}

		if (!isset($input['terms'])) {
			$errors['terms'] = 'Please agree to the Terms of Service and Privacy Policy.';
		}

		if ($errors !== []) {
			return ['errors' => $errors, 'values' => compact('fullName', 'username', 'email')];
		}

		try {
			$this->user->create($fullName, $username, $email, password_hash($password, PASSWORD_DEFAULT));
		} catch (PDOException $exception) {
			return [
				'errors' => ['form' => 'We could not create your account right now. Please try again.'],
				'values' => compact('fullName', 'username', 'email'),
			];
		}

		return ['errors' => [], 'values' => []];
	}

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

		$user = $this->user->findByEmail($email);
		if (!$user || !password_verify($password, $user['password_hash'])) {
			return [
				'errors' => ['form' => 'The email or password is incorrect.'],
				'values' => ['email' => $email],
			];
		}

		if ($user['status'] !== 'active') {
			return [
				'errors' => ['form' => 'This account is not currently active.'],
				'values' => ['email' => $email],
			];
		}

		return ['errors' => [], 'values' => $user];
	}

	private function createUsernameFromName(string $fullName): string
	{
		$base = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $fullName), '-'));
		$base = substr($base ?: 'user', 0, 44);
		$username = $base;
		$suffix = 1;

		while ($this->user->usernameExists($username)) {
			$username = $base . '-' . $suffix;
			$suffix++;
		}

		return $username;
	}
}
