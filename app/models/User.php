<?php

class User
{
	public function __construct(private PDO $db)
	{
	}

	// Username check
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

	// Email check
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

		if ($statement->fetch()) {
			return true;
		}

		// Login checks users first, so an admin email must never belong to a user.
		$adminStatement = $this->db->prepare('SELECT admin_id FROM admins WHERE email = :email LIMIT 1');
		$adminStatement->execute(['email' => $email]);

		return (bool) $adminStatement->fetch();
	}

	// Find user by email
	public function findByEmail(string $email): ?array
	{
		$statement = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
		$statement->execute(['email' => $email]);
		$user = $statement->fetch();

		return $user ?: null;
	}

	// Find user by id
	public function findById(int $userId): ?array
	{
		$statement = $this->db->prepare('SELECT * FROM users WHERE user_id = :id LIMIT 1');
		$statement->execute(['id' => $userId]);
		$user = $statement->fetch();

		return $user ?: null;
	}

	// Create user
	public function create(string $fullName, ?string $username, string $email, string $passwordHash): int
	{
		$statement = $this->db->prepare(
			'INSERT INTO users (full_name, username, email, password_hash, status, last_login_at)
			 VALUES (:full_name, :username, :email, :password_hash, :status, NOW())'
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

	// Save the name Squir calls the user
	public function updateUsername(int $userId, string $username): bool
	{
		$statement = $this->db->prepare('UPDATE users SET username = :username WHERE user_id = :id');

		return $statement->execute([
			'username' => $username,
			'id' => $userId,
		]);
	}

	// Update password hash
	public function updatePassword(int $userId, string $passwordHash): bool
	{
		$statement = $this->db->prepare('UPDATE users SET password_hash = :password_hash WHERE user_id = :id');

		return $statement->execute([
			'password_hash' => $passwordHash,
			'id' => $userId,
		]);
	}

	// Update profile
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

	// Update email
	public function updateEmail(int $userId, string $email): bool
	{
		$statement = $this->db->prepare('UPDATE users SET email = :email WHERE user_id = :id');

		return $statement->execute([
			'email' => $email,
			'id' => $userId,
		]);
	}

	// Record last login
	public function touchLastLogin(int $userId): void
	{
		$statement = $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE user_id = :id');
		$statement->execute(['id' => $userId]);
	}

	// Delete the account and related data
	public function delete(int $userId): bool
	{
		$statement = $this->db->prepare('DELETE FROM users WHERE user_id = :id');

		return $statement->execute(['id' => $userId]);
	}



	// List users for admin
	public function allForAdmin(): array
	{
		$statement = $this->db->query(
			'SELECT user_id, full_name, username, email, status, last_login_at, created_at
			 FROM users
			 ORDER BY created_at DESC'
		);

		return $statement->fetchAll();
	}

	// Items per user for the User Summary Report (all users when $limit is null)
	public function summaryReport(?int $limit = null): array
	{
		$sql = 'SELECT u.user_id,
		               u.full_name,
		               u.email,
		               u.status,
		               COALESCE(v.total, 0)   AS credentials,
		               COALESCE(n.total, 0)   AS notes,
		               COALESCE(t.done, 0)    AS tasks_done,
		               COALESCE(t.pending, 0) AS tasks_pending
		        FROM users u
		        LEFT JOIN (SELECT user_id, COUNT(*) AS total
		                   FROM vault GROUP BY user_id) v ON v.user_id = u.user_id
		        LEFT JOIN (SELECT user_id, COUNT(*) AS total
		                   FROM notes GROUP BY user_id) n ON n.user_id = u.user_id
		        LEFT JOIN (SELECT user_id,
		                          SUM(status = \'done\')  AS done,
		                          SUM(status <> \'done\') AS pending
		                   FROM tasks GROUP BY user_id) t ON t.user_id = u.user_id
		        ORDER BY (COALESCE(v.total, 0) + COALESCE(n.total, 0)
		                  + COALESCE(t.done, 0) + COALESCE(t.pending, 0)) DESC,
		                 u.full_name ASC';

		if ($limit !== null) {
			$sql .= ' LIMIT :limit';
		}

		$statement = $this->db->prepare($sql);
		if ($limit !== null) {
			$statement->bindValue(':limit', $limit, PDO::PARAM_INT);
		}
		$statement->execute();

		$rows = [];
		foreach ($statement->fetchAll() as $row) {
			$row['credentials']   = (int) $row['credentials'];
			$row['notes']         = (int) $row['notes'];
			$row['tasks_done']    = (int) $row['tasks_done'];
			$row['tasks_pending'] = (int) $row['tasks_pending'];
			$rows[] = $row;
		}

		return $rows;
	}

	// Get admin user stats
	public function adminStats(): array
	{
		$total = (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
		$active = (int) $this->db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
		$suspended = (int) $this->db->query("SELECT COUNT(*) FROM users WHERE status = 'suspended'")->fetchColumn();
		$inactive = (int) $this->db->query("SELECT COUNT(*) FROM users WHERE status = 'inactive'")->fetchColumn();
		$newThisWeek = (int) $this->db->query(
			'SELECT COUNT(*) FROM users WHERE created_at >= (NOW() - INTERVAL 7 DAY)'
		)->fetchColumn();

		return [
			'total' => $total,
			'active' => $active,
			'suspended' => $suspended,
			'inactive' => $inactive,
			'new_this_week' => $newThisWeek,
		];
	}

	// Mark active users as inactive when they have not logged in for a while
	public const INACTIVE_AFTER_DAYS = 30;

	public function markInactiveUsers(int $days = self::INACTIVE_AFTER_DAYS): int
	{
		$statement = $this->db->prepare(
			"UPDATE users
			 SET status = 'inactive'
			 WHERE status = 'active'
			   AND COALESCE(last_login_at, created_at) < DATE_SUB(NOW(), INTERVAL :days DAY)"
		);
		$statement->execute(['days' => max(1, $days)]);

		return $statement->rowCount();
	}

	// Update user status
	public function updateStatus(int $userId, string $status): bool
	{
		$statement = $this->db->prepare('UPDATE users SET status = :status WHERE user_id = :id');

		return $statement->execute(['status' => $status, 'id' => $userId]);
	}

	// Invalidate existing sessions
	public function forceLogout(int $userId): bool
	{
		$statement = $this->db->prepare('UPDATE users SET force_logout_at = NOW() WHERE user_id = :id');

		return $statement->execute(['id' => $userId]);
	}

	// Get user detail stats
	public function adminDetailStats(int $userId): array
	{
		$statement = $this->db->prepare(
			"SELECT
				(SELECT COUNT(*) FROM vault   WHERE user_id = :vault_user)   AS credentials,
				(SELECT COUNT(*) FROM notes   WHERE user_id = :notes_user)   AS notes,
				(SELECT COUNT(*) FROM tasks   WHERE user_id = :tasks_user AND status <> 'done') AS tasks,
				(SELECT COUNT(*) FROM folders WHERE user_id = :folders_user) AS folders"
		);
		$statement->execute([
			'vault_user'   => $userId,
			'notes_user'   => $userId,
			'tasks_user'   => $userId,
			'folders_user' => $userId,
		]);
		$row = $statement->fetch() ?: [];

		return [
			'credentials' => (int) ($row['credentials'] ?? 0),
			'notes'       => (int) ($row['notes'] ?? 0),
			'tasks'       => (int) ($row['tasks'] ?? 0),
			'folders'     => (int) ($row['folders'] ?? 0),
		];
	}

	// Update user from admin panel
	public function adminUpdate(int $userId, string $fullName, string $username, string $email): bool
	{
		$ok = $this->updateProfile($userId, $fullName, $username);

		return $this->updateEmail($userId, $email) && $ok;
	}

	// Save theme and accent color
	public function updatePreferences(int $userId, ?string $theme, ?string $accent): void
	{
		$sets = [];
		$params = ['id' => $userId];

		if ($theme !== null) {
			$sets[] = 'theme_pref = :theme';
			$params['theme'] = $theme;
		}
		if ($accent !== null) {
			$sets[] = 'accent_color = :accent';
			$params['accent'] = $accent;
		}
		if ($sets === []) {
			return;
		}

		$statement = $this->db->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE user_id = :id');
		$statement->execute($params);
	}
}