<?php
require_once __DIR__ . '/../../models/ActivityLog.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../../includes/DocxBuilder.php';
require_once __DIR__ . '/../../../includes/XlsxBuilder.php';

class AdminActivityLogController
{
	private const PAGE_SIZE = 30;

	private const EXPORT_LIMIT = 5000;

	// Smaller limit for docs and PDFs.
	private const REPORT_MAX = 2000;

	public const EXPORT_FORMATS = ['csv', 'xlsx', 'doc', 'pdf'];

	// Avatar colors.
	private const AVATAR_COLORS = ['', 'is-purple', 'is-blue', 'is-amber', 'is-green'];

	public function __construct(private ActivityLog $activityLog, private User $user)
	{
	}

	// Clean filters.
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

	// First page data.
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

	// Load one page.
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

	public function exportHeader(): array
	{
		return ['User', 'Date & Time', 'Activity', 'Details', 'Device', 'IP Address'];
	}

	// Load filtered rows.
	public function exportRows(array $filters, int $limit = self::EXPORT_LIMIT): array
	{
		$rows = [];
		foreach ($this->activityLog->searchForAdmin($filters, $limit, 0) as $row) {
			$log = $this->present($row);
			$rows[] = [
				(string) $log['actor'],
				$log['date'] . ' ' . $log['time'],
				(string) $log['label'],
				(string) $log['detail'],
				(string) $log['device'],
				(string) $log['ip'],
			];
		}

		return $rows;
	}

	// PDF support
	public function pdfAvailable(): bool
	{
		$autoload = __DIR__ . '/../../../vendor/autoload.php';
		if (is_readable($autoload)) {
			require_once $autoload;
		}

		return class_exists(\Dompdf\Dompdf::class);
	}

	// Docs support
	public function docxAvailable(): bool
	{
		return DocxBuilder::available();
	}

	// Excel support
	public function xlsxAvailable(): bool
	{
		return XlsxBuilder::available();
	}

	// Filter summary.
	public function filterSummary(array $filters): string
	{
		$parts = [];

		$action = (string) ($filters['action'] ?? 'all');
		if ($action !== 'all') {
			foreach (ActivityLog::actionGroups() as $key => $group) {
				if ($action === 'cat:' . $key) {
					$parts[] = 'Action: ' . $group['label'];
				} elseif (isset($group['actions'][$action])) {
					$parts[] = 'Action: ' . $group['actions'][$action];
				}
			}
		}

		$from = $filters['date_from'] ?? null;
		$to = $filters['date_to'] ?? null;
		if ($from !== null && $to !== null) {
			$parts[] = $from === $to
				? 'Date: ' . date('M j, Y', strtotime($from))
				: 'Date: ' . date('M j, Y', strtotime($from)) . ' - ' . date('M j, Y', strtotime($to));
		} elseif ($from !== null) {
			$parts[] = 'Date: from ' . date('M j, Y', strtotime($from));
		} elseif ($to !== null) {
			$parts[] = 'Date: until ' . date('M j, Y', strtotime($to));
		}

		if (($filters['q'] ?? '') !== '') {
			$parts[] = 'Search: "' . $filters['q'] . '"';
		}

		if (($filters['user_id'] ?? null) !== null) {
			$found = $this->user->findById((int) $filters['user_id']);
			$parts[] = 'User: ' . ($found ? (string) $found['full_name'] : 'Deleted user');
		}

		return $parts === [] ? 'All user activity' : implode(' · ', $parts);
	}

	// Build export file.
	public function exportFile(array $filters, string $format, string $preparedBy): array
	{
		if ($format === 'xlsx') {
			$body = XlsxBuilder::build($this->exportHeader(), $this->exportRows($filters), ['sheet' => 'Activity logs']);

			return [$body, XlsxBuilder::MIME, 'xlsx'];
		}

		if (!in_array($format, ['doc', 'pdf'], true)) {
			throw new InvalidArgumentException('Unsupported export format.');
		}

		$rows = $this->exportRows($filters, self::REPORT_MAX);
		$total = $this->activityLog->countForAdmin($filters);

		$report = [
			'rows'         => $rows,
			'scope_label'  => $this->filterSummary($filters),
			'generated_at' => date('F j, Y \a\t g:i A'),
			'prepared_by'  => $preparedBy,
			'limit_note'   => $total > count($rows)
				? 'Showing the latest ' . number_format(count($rows)) . ' of ' . number_format($total) . ' matching entries. Use CSV or Excel to export more.'
				: '',
		];

		if ($format === 'doc') {
			return [$this->renderDocx($report), DocxBuilder::MIME, 'docx'];
		}

		$html = $this->renderReportHtml($report);

		$options = new \Dompdf\Options();
		$options->set('defaultFont', 'DejaVu Sans');
		$options->set('isRemoteEnabled', false);
		$options->set('isPhpEnabled', false);

		$dompdf = new \Dompdf\Dompdf($options);
		$dompdf->loadHtml($html, 'UTF-8');
		$dompdf->setPaper('A4', 'landscape');
		$dompdf->render();

		$canvas = $dompdf->getCanvas();
		$font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
		$footerY = $canvas->get_height() - 28;
		$canvas->page_text(40, $footerY, 'Squir - Activity log report', $font, 8, [0.45, 0.45, 0.45]);
		$canvas->page_text($canvas->get_width() - 110, $footerY, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 8, [0.45, 0.45, 0.45]);

		return [$dompdf->output(), 'application/pdf', 'pdf'];
	}

	// Build doc export.
	private function renderDocx(array $report): string
	{
		$brown = '6B3F2A';
		$soft = '6F625B';
		$head = 'F5EDE6';

		$rows = $report['rows'] ?? [];

		$doc = new DocxBuilder(true, 'Squir - Activity log report');

		$doc->heading('Activity Log Report', 1);
		$doc->paragraph(
			(string) $report['scope_label'] . ' · Prepared by ' . (string) $report['prepared_by'] . ' · ' . (string) $report['generated_at'],
			['color' => $soft, 'size' => 17, 'after' => 160]
		);

		if (!empty($report['limit_note'])) {
			$doc->table([[[
				'text'  => (string) $report['limit_note'],
				'shade' => 'FBF1E3',
				'color' => $brown,
			]]], [100], ['border' => 'E0C39A', 'after' => 160]);
		}

		$doc->table([[[
			'align' => 'center',
			'paras' => [
				[[(string) count($rows), ['bold' => true, 'color' => $brown, 'size' => 30]]],
				[['Total entries', ['color' => $soft, 'size' => 16]]],
			],
		]]], [100], ['after' => 200]);

		if ($rows === []) {
			$doc->paragraph('No activity logs to export.', ['italic' => true, 'color' => '8A7D75']);

			return $doc->build();
		}

		$headCell = ['shade' => $head, 'bold' => true, 'color' => $brown, 'size' => 17];
		$table = [[]];
		foreach ($this->exportHeader() as $title) {
			$table[0][] = $headCell + ['text' => $title];
		}

		foreach ($rows as $row) {
			$cells = [];
			foreach (array_values($row) as $cell) {
				$cells[] = ['text' => (string) $cell, 'size' => 16];
			}
			$table[] = $cells;
		}

		$doc->table($table, [16, 16, 18, 28, 12, 10], ['headerRows' => 1]);

		return $doc->build();
	}

	private function renderReportHtml(array $report): string
	{
		ob_start();
		require __DIR__ . '/../../views/admin/export-activity-logs.php';

		return (string) ob_get_clean();
	}

	// Delete logs.
	public function clear(string $range): array
	{
		$ranges = ActivityLog::clearRanges();
		if (!isset($ranges[$range])) {
			return ['errors' => ['Please choose a valid date range.'], 'deleted' => 0, 'label' => ''];
		}

		return [
			'errors'  => [],
			'deleted' => $this->activityLog->clearForAdmin($range),
			'label'   => $ranges[$range],
		];
	}

	// Format row.
	private function present(array $row): array
	{
		$action = (string) $row['action'];
		$timestamp = strtotime((string) $row['created_at']);

		// Older logs may need device detection.
		$device = (string) ($row['device'] ?? '');
		if ($device === '') {
			$device = ActivityLog::detectDevice($row['user_agent'] ?? null);
		}

		$detail = trim((string) ($row['description'] ?? ''));

		// Make avatar data.
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