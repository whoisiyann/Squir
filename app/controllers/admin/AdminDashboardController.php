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

	// Count each table per user before joining to avoid duplicate totals.
	private function loadUserReport(int $limit): array
	{
		$statement = $this->db->prepare(
			'SELECT u.user_id,
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
				// Avoid showing the same text twice.
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