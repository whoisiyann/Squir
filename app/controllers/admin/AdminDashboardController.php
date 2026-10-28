<?php

require_once __DIR__ . '/../../models/ActivityLog.php';

class AdminDashboardController
{
	// Activity labels
	private const ACTION_LABELS = [
		'logged_in'        => 'Logged in',
		'logged_out'       => 'Logged out',
		'vault_created'    => 'Added a credential',
		'vault_updated'    => 'Updated a credential',
		'vault_deleted'    => 'Deleted a credential',
		'password_viewed'  => 'Password viewed',
		'note_created'     => 'Created a note',
		'note_updated'     => 'Updated a note',
		'note_deleted'     => 'Deleted a note',
		'task_created'     => 'Created a task',
		'task_updated'     => 'Updated a task',
		'task_completed'   => 'Completed a task',
		'task_deleted'     => 'Deleted a task',
		'folder_created'   => 'Created a folder',
		'folder_deleted'   => 'Deleted a folder',
		'profile_updated'  => 'Updated profile',
		'password_changed' => 'Changed account password',
		'pin_updated'      => 'Changed vault PIN',
		'admin_logged_in'  => 'Admin login',
		'admin_logged_out' => 'Admin logout',
		'user_created'     => 'Added a user',
		'user_updated'     => 'Updated a user',
		'user_activated'   => 'Activated a user',
		'user_deactivated' => 'Deactivated a user',
		'user_suspended'   => 'Suspended a user',
		'user_deleted'     => 'Deleted a user',
	];

	public function __construct(private PDO $db)
	{
	}

	// Header search: matching users and activity types.
	public function search(string $term): array
	{
		$term = mb_substr(trim($term), 0, 100);
		if (mb_strlen($term) < 2) {
			return [];
		}

		$results = [];

		// Users (name, username, email). Escape LIKE wildcards.
		$like = '%' . addcslashes($term, '\\%_') . '%';
		$statement = $this->db->prepare(
			'SELECT user_id, full_name, username, email
			 FROM users
			 WHERE full_name LIKE :q0 OR username LIKE :q1 OR email LIKE :q2
			 ORDER BY full_name ASC
			 LIMIT 5'
		);
		$statement->execute(['q0' => $like, 'q1' => $like, 'q2' => $like]);

		foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $user) {
			$results[] = [
				'type'     => 'user',
				'icon'     => 'ti-user',
				'title'    => (string) $user['full_name'],
				'subtitle' => (string) $user['email'],
				'url'      => url('admin/user-details?id=' . (int) $user['user_id']),
			];
		}

		// Activity types and groups. Each opens Activity Logs with that filter.
		$needle = mb_strtolower($term);

		// Icon per category (for the "All ..." results)
		$categoryIcons = [
			'auth'      => 'ti-login',
			'vault'     => 'ti-lock',
			'notes'     => 'ti-notes',
			'tasks'     => 'ti-checkbox',
			'folders'   => 'ti-folder',
			'favorites' => 'ti-star',
			'account'   => 'ti-user',
		];

		$activities = [];
		foreach (ActivityLog::actionGroups() as $key => $group) {
			if (mb_strpos(mb_strtolower((string) $group['label']), $needle) !== false) {
				$activities[] = [
					'type'     => 'activity',
					'icon'     => $categoryIcons[$key] ?? 'ti-activity',
					'title'    => 'All ' . $group['label'],
					'subtitle' => 'Activity logs',
					'url'      => url('admin/activity-logs?action=' . rawurlencode('cat:' . $key)),
				];
			}

			foreach ($group['actions'] as $code => $label) {
				if (mb_strpos(mb_strtolower((string) $label), $needle) !== false) {
					$activities[] = [
						'type'     => 'activity',
						'icon'     => ActivityLog::actionIcon((string) $code),
						'title'    => (string) $label,
						'subtitle' => (string) $group['label'] . ' activity',
						'url'      => url('admin/activity-logs?action=' . rawurlencode((string) $code)),
					];
				}
			}
		}

		return array_merge($results, array_slice($activities, 0, 5));
	}

	// Load admin dashboard data
	public function index(): array
	{
		return [
			'stats'           => $this->loadStats(),
			'recentUsers'     => $this->loadRecentUsers(10),
			'recentActivity'  => $this->loadRecentActivity(10),
			'userReport'      => $this->loadUserReport(10),
		];
	}

	// Dashboard stats
	private function loadStats(): array
	{
		$countUsers = (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
		$countVault = (int) $this->db->query('SELECT COUNT(*) FROM vault')->fetchColumn();
		$countNotes = (int) $this->db->query('SELECT COUNT(*) FROM notes')->fetchColumn();

		$taskBreakdown = ['todo' => 0, 'in_progress' => 0, 'done' => 0];
		$statement = $this->db->query('SELECT status, COUNT(*) AS total FROM tasks GROUP BY status');
		foreach ($statement->fetchAll() as $row) {
			$taskBreakdown[$row['status']] = (int) $row['total'];
		}
		$countTasks = array_sum($taskBreakdown);

		return [
			'total_users'     => $countUsers,
			'total_passwords' => $countVault,
			'total_notes'     => $countNotes,
			'total_tasks'     => $countTasks,
			'task_breakdown'  => $taskBreakdown,
		];
	}

	// Most recently registered users
	private function loadRecentUsers(int $limit): array
	{
		$statement = $this->db->prepare(
			'SELECT user_id, full_name, email, status, created_at
			 FROM users
			 ORDER BY created_at DESC
			 LIMIT :limit'
		);
		$statement->bindValue(':limit', $limit, PDO::PARAM_INT);
		$statement->execute();

		return $statement->fetchAll();
	}

	// Count each user's items without duplicates
	private function loadUserReport(int $limit): array
	{
		$statement = $this->db->prepare(
			'SELECT u.user_id,
			        u.full_name,
			        u.email,
			        u.status,
			        COUNT(DISTINCT v.vault_id) AS credentials,
			        COUNT(DISTINCT n.note_id)  AS notes,
			        COUNT(DISTINCT CASE WHEN t.status = \'done\' THEN t.task_id END) AS tasks_done,
			        COUNT(DISTINCT CASE WHEN t.status <> \'done\' THEN t.task_id END) AS tasks_pending
			 FROM users u
			 LEFT JOIN vault v ON v.user_id = u.user_id
			 LEFT JOIN notes n ON n.user_id = u.user_id
			 LEFT JOIN tasks t ON t.user_id = u.user_id
			 GROUP BY u.user_id, u.full_name, u.email, u.status
			 ORDER BY (COUNT(DISTINCT v.vault_id) + COUNT(DISTINCT n.note_id)
			           + COUNT(DISTINCT t.task_id)) DESC,
			          u.full_name ASC
			 LIMIT :limit'
		);
		$statement->bindValue(':limit', $limit, PDO::PARAM_INT);
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

	// Load user and admin activity
	private function loadRecentActivity(int $limit): array
	{
		$statement = $this->db->prepare(
			"SELECT al.action, al.description, al.created_at,
			        al.user_id, al.admin_id,
			        u.full_name AS user_name,
			        a.full_name AS admin_name
			 FROM activity_logs al
			 LEFT JOIN users  u ON u.user_id  = al.user_id
			 LEFT JOIN admins a ON a.admin_id = al.admin_id
			 WHERE al.user_id IS NOT NULL AND al.admin_id IS NULL
			 ORDER BY al.created_at DESC
			 LIMIT :limit"
		);
		$statement->bindValue(':limit', $limit, PDO::PARAM_INT);
		$statement->execute();

		$items = [];
		foreach ($statement->fetchAll() as $row) {
			$actorName = $row['admin_id'] !== null
				? ($row['admin_name'] ?? 'Administrator')
				: ($row['user_name'] ?? 'A user');

			$label = self::actionLabel((string) $row['action']);
			$description = (string) ($row['description'] ?? '');

			$items[] = [
				'actor'      => $actorName,
				'is_admin'   => $row['admin_id'] !== null,
				'label'      => $label,
				// Skip duplicate text
				'detail'     => $description !== $label ? $description : '',
				'icon'       => ActivityLog::actionIcon((string) $row['action']),
				'time_label' => self::timeAgo((string) $row['created_at']),
			];
		}

		return $items;
	}

	// Format unknown action labels
	private static function actionLabel(string $action): string
	{
		return self::ACTION_LABELS[$action] ?? ucfirst(str_replace('_', ' ', $action));
	}

	// Format relative time
	private static function timeAgo(string $datetime): string
	{
		$seconds = max(0, time() - strtotime($datetime));

		if ($seconds < 60) {
			return 'Just now';
		}
		if ($seconds < 3600) {
			$minutes = (int) floor($seconds / 60);
			return $minutes . ' minute' . ($minutes === 1 ? '' : 's') . ' ago';
		}
		if ($seconds < 86400) {
			$hours = (int) floor($seconds / 3600);
			return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
		}
		if ($seconds < 7 * 86400) {
			$days = (int) floor($seconds / 86400);
			return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
		}

		return date('M j, Y', strtotime($datetime));
	}
}