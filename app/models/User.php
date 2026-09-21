<?php

class User
{
	// Initialize user data access
	public function __construct(private PDO $db)
	{
	}

	// Check username availability
	public function usernameExists(string $username): bool
	{
		$statement = $this->db->prepare('SELECT user_id FROM users WHERE username = :username LIMIT 1');
		$statement->execute(['username' => $username]);

		return (bool) $statement->fetch();
	}

	// Check email availability
	public function emailExists(string $email): bool
	{
		$statement = $this->db->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
		$statement->execute(['email' => $email]);

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
}