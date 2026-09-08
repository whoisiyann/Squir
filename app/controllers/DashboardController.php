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
			'recentItems' => $this->recentItems($userId),
		];
	}

	private function recentItems(int $userId, int $limit = 5): array
	{
		$statement = $this->db->prepare(
			'SELECT v.vault_id, v.title, v.website_url, v.account_username, v.created_at,
					CASE WHEN f.favorite_id IS NULL THEN 0 ELSE 1 END AS is_favorite
			FROM vault v
			LEFT JOIN favorites f ON f.vault_id = v.vault_id AND f.user_id = v.user_id
			WHERE v.user_id = :user_id
			ORDER BY is_favorite DESC, v.created_at DESC
			LIMIT :limit'
		);
		$statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
		$statement->bindValue(':limit', $limit, PDO::PARAM_INT);
		$statement->execute();

		$items = [];
		foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$items[] = [
				'item_type'   => 'vault',
				'title'       => $row['title'],
				'subtitle'    => $row['account_username'] !== null && $row['account_username'] !== '' ? $row['account_username']: 'No username',
				'icon_url'    => Vault::faviconUrlFor($row['website_url']),
				'is_favorite' => (bool) $row['is_favorite'],
			];
		}

		return $items;
	}
}