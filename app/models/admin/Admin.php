<?php

class Admin
{
	public function __construct(private PDO $db)
	{
	}

	// Admin by email
	public function findByEmail(string $email): ?array
	{
		$statement = $this->db->prepare('SELECT * FROM admins WHERE email = :email LIMIT 1');
		$statement->execute(['email' => $email]);
		$admin = $statement->fetch();

		return $admin ?: null;
	}

	// Admin by id
	public function findById(int $adminId): ?array
	{
		$statement = $this->db->prepare('SELECT * FROM admins WHERE admin_id = :id LIMIT 1');
		$statement->execute(['id' => $adminId]);
		$admin = $statement->fetch();

		return $admin ?: null;
	}

	// Username check
	public function usernameExists(string $username, ?int $excludeAdminId = null): bool
	{
		$sql = 'SELECT admin_id FROM admins WHERE username = :username';
		$params = ['username' => $username];

		if ($excludeAdminId !== null) {
			$sql .= ' AND admin_id != :exclude_id';
			$params['exclude_id'] = $excludeAdminId;
		}

		$statement = $this->db->prepare($sql . ' LIMIT 1');
		$statement->execute($params);

		return (bool) $statement->fetchColumn();
	}

	// Email check
	public function emailExists(string $email, ?int $excludeAdminId = null): bool
	{
		$sql = 'SELECT admin_id FROM admins WHERE email = :email';
		$params = ['email' => $email];

		if ($excludeAdminId !== null) {
			$sql .= ' AND admin_id != :exclude_id';
			$params['exclude_id'] = $excludeAdminId;
		}

		$statement = $this->db->prepare($sql . ' LIMIT 1');
		$statement->execute($params);
		if ($statement->fetchColumn()) {
			return true;
		}

		// Login checks users first, so an email must not exist there.
		$userStatement = $this->db->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
		$userStatement->execute(['email' => $email]);

		return (bool) $userStatement->fetchColumn();
	}

	// Update profile
	public function updateProfile(int $adminId, string $fullName, string $username): void
	{
		$statement = $this->db->prepare(
			'UPDATE admins SET full_name = :full_name, username = :username WHERE admin_id = :id'
		);
		$statement->execute([
			'full_name' => $fullName,
			'username'  => $username,
			'id'        => $adminId,
		]);
	}

	// Save a verified new email
	public function updateEmail(int $adminId, string $email): void
	{
		$statement = $this->db->prepare('UPDATE admins SET email = :email WHERE admin_id = :id');
		$statement->execute(['email' => $email, 'id' => $adminId]);
	}

	// Save a new password hash
	public function updatePassword(int $adminId, string $passwordHash): void
	{
		$statement = $this->db->prepare('UPDATE admins SET password_hash = :hash WHERE admin_id = :id');
		$statement->execute(['hash' => $passwordHash, 'id' => $adminId]);
	}

	// Record a successful login
	public function touchLastLogin(int $adminId): void
	{
		$statement = $this->db->prepare('UPDATE admins SET last_login_at = NOW() WHERE admin_id = :id');
		$statement->execute(['id' => $adminId]);
	}
}