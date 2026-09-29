<?php
require_once __DIR__ . '/../models/User.php';

class AuthController
{
	public function __construct(private User $user)
	{
	}

	// Registration
	public function register(array $input): array
	{
		$fullName = trim((string) ($input['full_name'] ?? ''));
		$email = strtolower(trim((string) ($input['email'] ?? '')));
		$password = (string) ($input['password'] ?? '');
		$passwordConfirmation = (string) ($input['password_confirmation'] ?? '');
		$errors = [];

		if ($fullName === '') {
			$errors['full_name'] = 'Full name is required.';
		} elseif (mb_strlen($fullName) > 100) {
			$errors['full_name'] = 'Full name must be 100 characters or fewer.';
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
			return ['errors' => $errors, 'values' => compact('fullName', 'email')];
		}

		try {

			$userId = $this->user->create(
				$fullName,
				null, // Username is asked after the PIN step
				$email,
				password_hash($password, PASSWORD_DEFAULT)
			);
		} catch (PDOException $exception) {
			return [
				'errors' => ['form' => 'We could not create your account right now. Please try again.'],
				'values' => compact('fullName', 'email'),
			];
		}

		return ['errors' => [], 'values' => [], 'user_id' => $userId];
	}

	// Login
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

	// Save the name Squir uses to greet the user
	public function setUsername(int $userId, array $input): array
	{
		$username = trim((string) ($input['username'] ?? ''));
		$errors = [];

		if ($username === '') {
			$errors['username'] = 'Please tell Squir what to call you.';
		} elseif (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
			$errors['username'] = 'Use 3-50 letters, numbers, dots, dashes, or underscores.';
		} elseif ($this->user->usernameExists($username, $userId)) {
			$errors['username'] = 'That name is already taken. Try another one.';
		}

		if ($errors !== []) {
			return ['errors' => $errors, 'values' => ['username' => $username]];
		}

		try {
			$this->user->updateUsername($userId, $username);
		} catch (PDOException $exception) {
			return [
				'errors' => ['username' => 'We could not save that name right now. Please try again.'],
				'values' => ['username' => $username],
			];
		}

		return ['errors' => [], 'values' => ['username' => $username]];
	}
}
