<?php
require_once __DIR__ . '/../../models/ActivityLog.php';
require_once __DIR__ . '/../../models/User.php';

class AdminActivityLogController
{
	private const PAGE_SIZE = 30;

	private const EXPORT_LIMIT = 5000;

	// Match the user list avatar colors.
	private const AVATAR_COLORS = ['', 'is-purple', 'is-blue', 'is-amber', 'is-green'];

	public function __construct(private ActivityLog $activityLog, private User $user)
	{
	}

	// Normalize request filters.
	public function filters(array $query): array
	{
		$action = trim((string) ($query['action'] ?? 'all'));
		if (str_starts_with($action, 'cat:')) {
			if (!ActivityLog::isKnownCategory(substr($action, 4))) {
				$action = 'all';
			}
		} elseif ($action !== 'all' && !ActivityLog::isKnownAction($action)) {
			$action = 'all';
		}

		$from = self::validDate($query['date_from'] ?? null);
		$to = self::validDate($query['date_to'] ?? null);
		if ($from !== null && $to !== null && $from > $to) {
			[$from, $to] = [$to, $from];
		}

		$userId = (int) ($query['user_id'] ?? 0);

		return [
			'action'    => $action,
			'date_from' => $from,
			'date_to'   => $to,
			'q'         => mb_substr(trim((string) ($query['q'] ?? '')), 0, 100),
			'user_id'   => $userId > 0 ? $userId : null,
		];
	}

	// Data for the first page load.
	public function index(array $query): array
	{
		$filters = $this->filters($query);
		$page = $this->rows($filters, 1);

		$filterUser = null;
		if ($filters['user_id'] !== null) {
			$found = $this->user->findById($filters['user_id']);
			$filterUser = $found ? (string) $found['full_name'] : 'Deleted user';
		}

		return $page + [
			'filters'      => $filters,
			'actionGroups' => ActivityLog::actionGroups(),
			'filterUser'   => $filterUser,
		];
	}

	// Load one page of rows.
	public function rows(array $filters, int $page): array
	{
		$page = max(1, $page);
		$offset = ($page - 1) * self::PAGE_SIZE;

		$total = $this->activityLog->countForAdmin($filters);
		$items = array_map([$this, 'present'], $this->activityLog->searchForAdmin($filters, self::PAGE_SIZE, $offset));

		return [
			'logs'     => $items,
			'total'    => $total,
			'page'     => $page,
			'has_more' => ($offset + count($items)) < $total,
		];
	}

	// Load rows for CSV export.
	public function exportRows(array $filters): array
	{
		$rows = [];
		foreach ($this->activityLog->searchForAdmin($filters, self::EXPORT_LIMIT, 0) as $row) {
			$log = $this->present($row);
			$rows[] = [
				$log['actor'],
				$log['date'] . ' ' . $log['time'],
				$log['label'],
				$log['detail'],
				$log['device'],
				$log['ip'],
			];
		}

		return $rows;
	}

	// Format a table row.
	private function present(array $row): array
	{
		$action = (string) $row['action'];
		$timestamp = strtotime((string) $row['created_at']);

		// Detect devices for older logs.
		$device = (string) ($row['device'] ?? '');
		if ($device === '') {
			$device = ActivityLog::detectDevice($row['user_agent'] ?? null);
		}

		$detail = trim((string) ($row['description'] ?? ''));

		// Build avatar data.
		$actorName = (string) ($row['user_name'] ?? '');
		$hasActor = $actorName !== '' && !empty($row['user_id']);
		$avatarColor = $hasActor
			? self::AVATAR_COLORS[crc32((string) $row['user_id']) % count(self::AVATAR_COLORS)]
			: '';

		return [
			'id'          => (int) $row['log_id'],
			'actor'       => $row['user_name'] ?? 'Deleted user',
			'user_id'     => $hasActor ? (int) $row['user_id'] : null,
			'initials'    => $hasActor ? userInitials($actorName) : '?',
			'avatar'      => $avatarColor,
			'date'        => date('M j, Y', $timestamp),
			'time'        => date('g:iA', $timestamp),
			'label'       => ActivityLog::adminLabel($action),
			'tone'        => ActivityLog::tone($action),
			'detail'      => $detail !== '' ? $detail : '—',
			'device'      => $device,
			'device_icon' => ActivityLog::deviceIcon($row['user_agent'] ?? null),
			'ip'          => ActivityLog::displayIp($row['ip_address'] ?? null),
		];
	}

	private static function validDate(mixed $value): ?string
	{
		$value = trim((string) $value);
		if ($value === '') {
			return null;
		}

		$date = DateTime::createFromFormat('!Y-m-d', $value);

		return ($date && $date->format('Y-m-d') === $value) ? $value : null;
	}
}
