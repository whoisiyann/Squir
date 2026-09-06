<!-- DashboardController.php -->
<?php

class DashboardController
{
	public function __construct(private PDO $db)
	{
	}

	public function index(int $userId): array
	{
		$userStatement = $this->db->prepare('SELECT user_id, full_name, username, email FROM users WHERE user_id = :user_id LIMIT 1');
		$userStatement->execute(['user_id' => $userId]);
		$user = $userStatement->fetch();

		if (!$user) {
			return [];
		}

		$counts = [];
		foreach (['vault' => 'vault', 'notes' => 'notes', 'tasks' => 'tasks', 'folders' => 'folders'] as $key => $table) {
			$statement = $this->db->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id = :user_id");
			$statement->execute(['user_id' => $userId]);
			$counts[$key] = (int) $statement->fetchColumn();
		}

		$pendingStatement = $this->db->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = :user_id AND status <> 'completed'");
		$pendingStatement->execute(['user_id' => $userId]);

		return [
			'user' => $user,
			'counts' => $counts,
			'pendingTasks' => (int) $pendingStatement->fetchColumn(),
		];
	}
}
