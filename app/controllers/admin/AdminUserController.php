<?php
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/ActivityLog.php';

class AdminUserController
{
	private const STATUSES = ['active', 'inactive', 'suspended'];

	private const RECENT_ACTIVITY_LIMIT = 5;

	public function __construct(private User $user, private ?ActivityLog $activityLog = null)
	{
	}

	// Load user list data
	public function index(): array
	{
		return [
			'stats' => $this->user->adminStats(),
			'users' => $this->user->allForAdmin(),
		];
	}

	// Load user details
	public function show(int $userId): ?array
	{
		$user = $this->user->findById($userId);
		if (!$user) {
			return null;
		}

		return [
			'user'     => $user,
			'stats'    => $this->user->adminDetailStats($userId),
			'activity' => $this->activityLog ? $this->activityLog->recentForAdmin($userId, self::RECENT_ACTIVITY_LIMIT) : [],
		];
	}

	// Handle "Add User" form submission
	public function create(array $input): array
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

		if ($passwordConfirmation === '' || $password !== $passwordConfirmation) {
			$errors['password_confirmation'] = 'Passwords do not match.';
		}

		if ($errors !== []) {
			return ['errors' => $errors, 'values' => compact('fullName', 'username', 'email')];
		}

		try {
			$userId = $this->user->create($fullName, $username, $email, password_hash($password, PASSWORD_DEFAULT));
		} catch (PDOException $exception) {
			return [
				'errors' => ['form' => 'Could not create the account right now. Please try again.'],
				'values' => compact('fullName', 'username', 'email'),
			];
		}

		return ['errors' => [], 'values' => [], 'user_id' => $userId];
	}

	// User update
	public function update(int $userId, array $input): array
	{
		$fullName = trim((string) ($input['full_name'] ?? ''));
		$username = trim((string) ($input['username'] ?? ''));
		$email = strtolower(trim((string) ($input['email'] ?? '')));
		$errors = [];

		if (!$this->user->findById($userId)) {
			return ['errors' => ['form' => 'That user could not be found.'], 'values' => []];
		}

		if ($fullName === '') {
			$errors['full_name'] = 'Full name is required.';
		} elseif (mb_strlen($fullName) > 100) {
			$errors['full_name'] = 'Full name must be 100 characters or fewer.';
		}

		if ($username === '') {
			$errors['username'] = 'Username is required.';
		} elseif (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
			$errors['username'] = 'Username must be 3-50 letters, numbers, dots, dashes, or underscores.';
		} elseif ($this->user->usernameExists($username, $userId)) {
			$errors['username'] = 'Username is already taken.';
		}

		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$errors['email'] = 'Please enter a valid email address.';
		} elseif (mb_strlen($email) > 100) {
			$errors['email'] = 'Email address must be 100 characters or fewer.';
		} elseif ($this->user->emailExists($email, $userId)) {
			$errors['email'] = 'An account with this email already exists.';
		}

		if ($errors !== []) {
			return ['errors' => $errors, 'values' => compact('fullName', 'username', 'email')];
		}

		try {
			$this->user->adminUpdate($userId, $fullName, $username, $email);
		} catch (PDOException $exception) {
			return [
				'errors' => ['form' => 'Could not save these changes right now. Please try again.'],
				'values' => compact('fullName', 'username', 'email'),
			];
		}

		return ['errors' => [], 'values' => compact('fullName', 'username', 'email')];
	}

	// Update user status
	public function updateStatus(int $userId, string $status): array
	{
		if (!in_array($status, self::STATUSES, true)) {
			return ['errors' => ['form' => 'Invalid status.']];
		}

		$user = $this->user->findById($userId);
		if (!$user) {
			return ['errors' => ['form' => 'That user could not be found.']];
		}

		$this->user->updateStatus($userId, $status);

		return ['errors' => [], 'user' => $user];
	}

	// Force user logout
	public function forceLogout(int $userId): array
	{
		$user = $this->user->findById($userId);
		if (!$user) {
			return ['errors' => ['form' => 'That user could not be found.']];
		}

		$this->user->forceLogout($userId);

		return ['errors' => [], 'user' => $user];
	}

	// Delete a user account
	public function delete(int $userId): array
	{
		$user = $this->user->findById($userId);
		if (!$user) {
			return ['errors' => ['form' => 'That user could not be found.']];
		}

		$this->user->delete($userId);

		return ['errors' => [], 'user' => $user];
	}

	// Generate an available username.
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