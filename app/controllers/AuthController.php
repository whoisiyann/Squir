<?php
require_once __DIR__ . '/../helpers/PasswordValidator.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/EmailValidator.php';
require_once __DIR__ . '/../helpers/NameValidator.php';

class AuthController
{
	private const MAX_ATTEMPTS = 5;
	private const LOCK_MINUTES = 15;

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

		$nameError = NameValidator::check($fullName);
		if ($nameError !== null) {
			$errors['full_name'] = $nameError;
		}

		$emailError = EmailValidator::check($email);
		if ($emailError !== null) {
			$errors['email'] = $emailError;
		} elseif ($this->user->emailExists($email)) {
			$errors['email'] = 'An account with this email already exists.';
		}

		if ($password === '') {
			$errors['password'] = 'Password is required.';
		} else {
			$passwordError = PasswordValidator::check($password, ['full_name' => $fullName, 'email' => $email]);
			if ($passwordError !== null) {
				$errors['password'] = $passwordError;
			}
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
				null, // Set after PIN verification
				$email,
				password_hash($password, PASSWORD_DEFAULT)
			);
		} catch (PDOException $exception) {
			error_log('[register] ' . $exception->getMessage());
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

		// Check whether the account is locked.
		if ($user) {
			$secondsLeft = $this->user->lockSecondsRemaining((int) $user['user_id']);
			if ($secondsLeft > 0) {
				return [
					'errors' => ['form' => 'Too many failed attempts. Please try again in ' . (int) ceil($secondsLeft / 60) . ' minute(s).'],
					'values' => ['email' => $email],
				];
			}
		}

		if (!$user || !password_verify($password, $user['password_hash'])) {
			if ($user) {
				$this->user->recordFailedLogin((int) $user['user_id'], self::MAX_ATTEMPTS, self::LOCK_MINUTES);
			}

			return [
				'errors' => ['form' => 'The email or password is incorrect.'],
				'values' => ['email' => $email],
			];
		}

		// Clear failed attempts after successful login.
		$this->user->clearFailedLogins((int) $user['user_id']);

		// Reactivate inactive accounts on login
		if ($user['status'] === 'inactive') {
			$this->user->updateStatus((int) $user['user_id'], 'active');
			$user['status'] = 'active';
		}

		// Suspended accounts stay blocked
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
