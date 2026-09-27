<?php

class Admin
{
	public function __construct(private PDO $db)
	{
	}

	// Fetch an admin by email
	public function findByEmail(string $email): ?array
	{
		$statement = $this->db->prepare('SELECT * FROM admins WHERE email = :email LIMIT 1');
		$statement->execute(['email' => $email]);
		$admin = $statement->fetch();

		return $admin ?: null;
	}

	// Fetch an admin by id
	public function findById(int $adminId): ?array
	{
		$statement = $this->db->prepare('SELECT * FROM admins WHERE admin_id = :id LIMIT 1');
		$statement->execute(['id' => $adminId]);
		$admin = $statement->fetch();

		return $admin ?: null;
	}

	// Record a successful login
	public function touchLastLogin(int $adminId): void
	{
		$statement = $this->db->prepare('UPDATE admins SET last_login_at = NOW() WHERE admin_id = :id');
		$statement->execute(['id' => $adminId]);
	}
}
