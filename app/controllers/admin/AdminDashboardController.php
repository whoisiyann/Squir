<?php

class AdminDashboardController
{
	// Short, friendly labels for activity_logs.action values.
	// Anything not listed here falls back to a title-cased version
	// of the raw action string (see actionLabel()).
	private const ACTION_LABELS = [
		'logged_in'        => 'Logged in',
		'logged_out'       => 'Logged out',
		'vault_created'    => 'Added a password',
		'vault_updated'    => 'Updated a password',
		'vault_deleted'    => 'Deleted a password',
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
	];

	// Feather icon per activity entity, used as a fallback per-action below.
	private const ACTION_ICONS = [
		'logged_in'        => 'log-in',
		'logged_out'       => 'log-out',
		'vault_created'    => 'lock',
		'vault_updated'    => 'lock',
		'vault_deleted'    => 'lock',
		'note_created'     => 'file-text',
		'note_updated'     => 'file-text',
		'note_deleted'     => 'file-text',
		'task_created'     => 'check-square',
		'task_updated'     => 'check-square',
		'task_completed'   => 'check-square',
		'task_deleted'     => 'check-square',
		'folder_created'   => 'folder',
		'folder_deleted'   => 'folder',
		'profile_updated'  => 'user',
		'password_changed' => 'shield',
		'pin_updated'      => 'shield',
		'admin_logged_in'  => 'settings',
		'admin_logged_out' => 'settings',
	];

	public function __construct(private PDO $db)
	{
	}

	// Load every piece of data the admin dashboard view needs
	public function index(): array
	{
		return [
			'stats'           => $this->loadStats(),
			'recentUsers'     => $this->loadRecentUsers(5),
			'recentActivity'  => $this->loadRecentActivity(8),
		];
	}

	// Card counts: total users, saved passwords, notes, and a task status breakdown
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

	// Most recent activity across both users and admins
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
				// Skip the detail line when it just repeats the label (the
				// common case, since ActivityLog::log() defaults to that).
				'detail'     => $description !== $label ? $description : '',
				'icon'       => self::ACTION_ICONS[$row['action']] ?? 'activity',
				'time_label' => self::timeAgo((string) $row['created_at']),
			];
		}

		return $items;
	}

	// Fall back to a readable label built from the raw action string
	private static function actionLabel(string $action): string
	{
		return self::ACTION_LABELS[$action] ?? ucfirst(str_replace('_', ' ', $action));
	}

	// Short relative time label ("12 minutes ago", "2 hours ago", ...)
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
