<?php

require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../models/Folder.php';
require_once __DIR__ . '/../models/WorldClock.php';

class DashboardController
{
	private const APPROACHING_DAYS = 7;

	private const TASK_STATUS_LABELS = [
		'todo' => 'To do',
		'in_progress' => 'In progress',
		'done' => 'Done',
	];

	// Initialize dashboard data access
	public function __construct(private PDO $db)
	{
	}

	// Load dashboard data
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

		$board = $this->taskBoard((new Task($this->db))->allForUser($userId));
		$clocks = new WorldClock($this->db);

		return [
			'user' => $user,
			'counts' => $counts,
			'pendingTasks' => $board['pending'],
			'taskBoard' => $board,
			'recentItems' => $this->recentItems($userId, 10),
			'vaultFolders' => (new Folder($this->db))->allForUserByType($userId, 'passwords'),
			'worldClocks' => $clocks->forUser($userId),
			'timezoneLabels' => $clocks->timezoneLabels(),
		];
	}


	// Group tasks by status
	private function taskBoard(array $tasks): array
	{
		$today = new DateTimeImmutable('today');
		$columns = ['todo' => [], 'in_progress' => [], 'done' => []];
		$overdue = 0;
		$approaching = 0;

		foreach ($tasks as $task) {
			$status = isset($columns[$task['status']]) ? $task['status'] : 'todo';
			$dueLabel = null;
			$dueState = null;

			if ($task['due_date'] !== null) {
				$due = DateTimeImmutable::createFromFormat('!Y-m-d', $task['due_date']);
				if ($due) {
					$dueLabel = $due->format('M j') . ($due->format('Y') !== $today->format('Y') ? ', ' . $due->format('Y') : '');

					if ($status !== 'done') {
						$days = (int) $today->diff($due)->format('%r%a');
						if ($days < 0) {
							$dueState = 'overdue';
							$overdue++;
						} elseif ($days <= self::APPROACHING_DAYS) {
							$dueState = 'approaching';
							$approaching++;
						}
					}
				}
			}

			$columns[$status][] = [
				'task_id' => (int) $task['task_id'],
				'title' => (string) $task['title'],
				'due_label' => $dueLabel,
				'due_state' => $dueState,
			];
		}

		return [
			'columns' => $columns,
			'counts' => [
				'todo' => count($columns['todo']),
				'in_progress' => count($columns['in_progress']),
				'done' => count($columns['done']),
			],
			'pending' => count($columns['todo']) + count($columns['in_progress']),
			'overdue' => $overdue,
			'approaching' => $approaching,
		];
	}

	// Load recent user items
	private function recentItems(int $userId, int $limit = 10): array
	{
		$statement = $this->db->prepare(
			'SELECT v.vault_id, v.title, v.website_url, v.account_username, v.updated_at,
					CASE WHEN f.favorite_id IS NULL THEN 0 ELSE 1 END AS is_favorite
			FROM vault v
			LEFT JOIN favorites f ON f.vault_id = v.vault_id AND f.user_id = v.user_id
			WHERE v.user_id = :user_id
			ORDER BY v.updated_at DESC
			LIMIT :limit'
		);
		$statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
		$statement->bindValue(':limit', $limit, PDO::PARAM_INT);
		$statement->execute();

		$items = [];
		foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$items[] = [
				'item_type'   => 'vault',
				'vault_id'    => (int) $row['vault_id'],
				'title'       => $row['title'],
				'subtitle'    => $row['account_username'] !== null && $row['account_username'] !== '' ? $row['account_username'] : 'No username',
				'icon_url'    => Vault::faviconUrlFor($row['website_url']),
				'is_favorite' => (bool) $row['is_favorite'],
			];
		}

		return $items;
	}


	// Search dashboard data
	public function search(int $userId, string $term): array
	{
		$term = trim($term);
		if (mb_strlen($term) < 2) {
			return [];
		}

		$like = '%' . addcslashes($term, '%_\\') . '%';
		$results = [];

		$stmt = $this->db->prepare(
			'SELECT vault_id, title, account_username FROM vault
			 WHERE user_id = :uid AND (title LIKE :q1 OR account_username LIKE :q2 OR website_url LIKE :q3)
			 ORDER BY updated_at DESC LIMIT 5'
		);
		$stmt->execute(['uid' => $userId, 'q1' => $like, 'q2' => $like, 'q3' => $like]);
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$results[] = [
				'type' => 'vault',
				'id' => (int) $row['vault_id'],
				'title' => (string) $row['title'],
				'subtitle' => $row['account_username'] !== null && $row['account_username'] !== '' ? (string) $row['account_username'] : 'Credential',
				'url' => './vault?highlight=' . (int) $row['vault_id'],
			];
		}

		$stmt = $this->db->prepare(
			'SELECT note_id, title, content FROM notes
			 WHERE user_id = :uid AND (title LIKE :q1 OR content LIKE :q2)
			 ORDER BY updated_at DESC LIMIT 5'
		);
		$stmt->execute(['uid' => $userId, 'q1' => $like, 'q2' => $like]);
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$results[] = [
				'type' => 'note',
				'id' => (int) $row['note_id'],
				'title' => Note::titleOrDefault($row['title']),
				'subtitle' => Note::excerptOf($row['content'], 60) ?: 'Note',
				'url' => './notes?note=' . (int) $row['note_id'],
			];
		}

		$stmt = $this->db->prepare(
			'SELECT task_id, title, status FROM tasks
			 WHERE user_id = :uid AND (title LIKE :q1 OR description LIKE :q2)
			 ORDER BY updated_at DESC LIMIT 5'
		);
		$stmt->execute(['uid' => $userId, 'q1' => $like, 'q2' => $like]);
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$results[] = [
				'type' => 'task',
				'id' => (int) $row['task_id'],
				'title' => (string) $row['title'],
				'subtitle' => self::TASK_STATUS_LABELS[$row['status']] ?? 'Task',
				'url' => './tasks?task=' . (int) $row['task_id'],
			];
		}

		$stmt = $this->db->prepare(
			'SELECT folder_id, folder_name, folder_type FROM folders
			 WHERE user_id = :uid AND folder_name LIKE :q1
			 ORDER BY folder_name ASC LIMIT 5'
		);
		$stmt->execute(['uid' => $userId, 'q1' => $like]);
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$results[] = [
				'type' => 'folder',
				'id' => (int) $row['folder_id'],
				'title' => (string) $row['folder_name'],
				'subtitle' => $row['folder_type'] === 'notes' ? 'Notes folder' : 'Passwords folder',
				'url' => './folders?folder=' . (int) $row['folder_id'],
			];
		}

		return $results;
	}
}
