<?php

class User
{
	public function __construct(private PDO $db)
	{
	}

	// Check username availability
	public function usernameExists(string $username, ?int $excludeUserId = null): bool
	{
		$sql = 'SELECT user_id FROM users WHERE username = :username';
		$params = ['username' => $username];

		if ($excludeUserId !== null) {
			$sql .= ' AND user_id != :exclude_id';
			$params['exclude_id'] = $excludeUserId;
		}

		$statement = $this->db->prepare($sql . ' LIMIT 1');
		$statement->execute($params);

		return (bool) $statement->fetch();
	}

	// Check email availability
	public function emailExists(string $email, ?int $excludeUserId = null): bool
	{
		$sql = 'SELECT user_id FROM users WHERE email = :email';
		$params = ['email' => $email];

		if ($excludeUserId !== null) {
			$sql .= ' AND user_id != :exclude_id';
			$params['exclude_id'] = $excludeUserId;
		}

		$statement = $this->db->prepare($sql . ' LIMIT 1');
		$statement->execute($params);

		return (bool) $statement->fetch();
	}

	// Fetch a user by email
	public function findByEmail(string $email): ?array
	{
		$statement = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
		$statement->execute(['email' => $email]);
		$user = $statement->fetch();

		return $user ?: null;
	}

	// Fetch a user by id
	public function findById(int $userId): ?array
	{
		$statement = $this->db->prepare('SELECT * FROM users WHERE user_id = :id LIMIT 1');
		$statement->execute(['id' => $userId]);
		$user = $statement->fetch();

		return $user ?: null;
	}

	// Create a user record
	public function create(string $fullName, string $username, string $email, string $passwordHash): int
	{
		$statement = $this->db->prepare(
			'INSERT INTO users (full_name, username, email, password_hash, status)
			 VALUES (:full_name, :username, :email, :password_hash, :status)'
		);
		$statement->execute([
			'full_name' => $fullName,
			'username' => $username,
			'email' => $email,
			'password_hash' => $passwordHash,
			'status' => 'active',
		]);

		return (int) $this->db->lastInsertId();
	}

	// Update the password hash
	public function updatePassword(int $userId, string $passwordHash): bool
	{
		$statement = $this->db->prepare('UPDATE users SET password_hash = :password_hash WHERE user_id = :id');

		return $statement->execute([
			'password_hash' => $passwordHash,
			'id' => $userId,
		]);
	}

	// Update profile information (name and username only — email changes go through updateEmail())
	public function updateProfile(int $userId, string $fullName, string $username): bool
	{
		$statement = $this->db->prepare(
			'UPDATE users SET full_name = :full_name, username = :username WHERE user_id = :id'
		);

		return $statement->execute([
			'full_name' => $fullName,
			'username' => $username,
			'id' => $userId,
		]);
	}

	// Update the login email (called only after the new address is code-verified)
	public function updateEmail(int $userId, string $email): bool
	{
		$statement = $this->db->prepare('UPDATE users SET email = :email WHERE user_id = :id');

		return $statement->execute([
			'email' => $email,
			'id' => $userId,
		]);
	}

	// Delete the account and related data
	public function delete(int $userId): bool
	{
		$statement = $this->db->prepare('DELETE FROM users WHERE user_id = :id');

		return $statement->execute(['id' => $userId]);
	}



	// All users, newest first, for the admin Users table
	public function allForAdmin(): array
	{
		$statement = $this->db->query(
			'SELECT user_id, full_name, username, email, status, last_login_at, created_at
			 FROM users
			 ORDER BY created_at DESC'
		);

		return $statement->fetchAll();
	}

	// Card counts for the admin Users page: total / active / suspended / new this week
	public function adminStats(): array
	{
		$total = (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
		$active = (int) $this->db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
		$suspended = (int) $this->db->query("SELECT COUNT(*) FROM users WHERE status = 'suspended'")->fetchColumn();
		$newThisWeek = (int) $this->db->query(
			'SELECT COUNT(*) FROM users WHERE created_at >= (NOW() - INTERVAL 7 DAY)'
		)->fetchColumn();

		return [
			'total' => $total,
			'active' => $active,
			'suspended' => $suspended,
			'new_this_week' => $newThisWeek,
		];
	}

	// Change a user's account status (active / inactive / suspended)
	public function updateStatus(int $userId, string $status): bool
	{
		$statement = $this->db->prepare('UPDATE users SET status = :status WHERE user_id = :id');

		return $statement->execute(['status' => $status, 'id' => $userId]);
	}

	// Update a user from the admin panel.
	public function adminUpdate(int $userId, string $fullName, string $username, string $email): bool
	{
		$ok = $this->updateProfile($userId, $fullName, $username);

		return $this->updateEmail($userId, $email) && $ok;
	}
}