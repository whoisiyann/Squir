<?php
require_once __DIR__ . '/../../models/admin/Admin.php';
require_once __DIR__ . '/../../models/ActivityLog.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/admin/AdminPasswordReset.php';
require_once __DIR__ . '/../../models/admin/AdminEmailChange.php';
require_once __DIR__ . '/../../../includes/mailer.php';
require_once __DIR__ . '/../../../includes/DocxBuilder.php';
require_once __DIR__ . '/../../../includes/XlsxBuilder.php';

class AdminSettingsController
{
	private const EXPORT_BATCH = 1000;

	private const EXPORT_MAX = 50000;

	// Limit Docs and PDFs because they use more memory.
	private const EXPORT_REPORT_MAX = 2000;

	public const EXPORT_FORMATS = ['csv', 'xlsx', 'doc', 'pdf'];

	public function __construct(
		private Admin $admin,
		private ActivityLog $activityLog,
		private ?AdminPasswordReset $passwordReset = null,
		private ?AdminEmailChange $emailChange = null,
		private ?User $users = null
	) {
	}

	// Profile update
	public function updateProfile(int $adminId, array $post): array
	{
		$current = $this->admin->findById($adminId);
		if (!$current) {
			return ['errors' => ['form' => 'Your admin account could not be found.']];
		}

		$fullName = trim((string) ($post['full_name'] ?? ''));
		$username = trim((string) ($post['username'] ?? ''));
		$errors = [];

		if ($fullName === '') {
			$errors['full_name'] = 'Full name is required.';
		} elseif (mb_strlen($fullName) > 100) {
			$errors['full_name'] = 'Full name must be 100 characters or fewer.';
		}

		if ($username === '') {
			$errors['username'] = 'Username is required.';
		} elseif (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
			$errors['username'] = 'Username must be 3-50 letters, numbers, dots, dashes, or underscores.';
		} elseif ($this->admin->usernameExists($username, $adminId)) {
			$errors['username'] = 'Username is already taken.';
		}

		if ($errors !== []) {
			return ['errors' => $errors];
		}

		$changes = [];
		if ($current['full_name'] !== $fullName) {
			$changes[] = 'name';
		}
		if ($current['username'] !== $username) {
			$changes[] = 'username';
		}

		if ($changes !== []) {
			$this->admin->updateProfile($adminId, $fullName, $username);
			$this->activityLog->logAdmin($adminId, 'admin_profile_updated', 'Updated ' . implode(', ', $changes));
		}

		return ['errors' => [], 'admin' => $this->admin->findById($adminId)];
	}

	// Send email code
	public function requestEmailChange(int $adminId, array $post): array
	{
		if ($this->emailChange === null) {
			return ['errors' => ['form' => 'Email change is not available right now.']];
		}

		$newEmail = strtolower(trim((string) ($post['new_email'] ?? '')));

		if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
			return ['errors' => ['new_email' => 'Please enter a valid email address.']];
		}
		if (mb_strlen($newEmail) > 100) {
			return ['errors' => ['new_email' => 'Email address must be 100 characters or fewer.']];
		}

		$current = $this->admin->findById($adminId);
		if (!$current) {
			return ['errors' => ['form' => 'Your admin account could not be found.']];
		}
		if (strtolower((string) $current['email']) === $newEmail) {
			return ['errors' => ['new_email' => 'That is already your current email address.']];
		}
		if ($this->admin->emailExists($newEmail, $adminId)) {
			return ['errors' => ['new_email' => 'An account with this email already exists.']];
		}

		return $this->issueEmailChangeCode($adminId, $newEmail, (string) $current['full_name']);
	}

	// Resend email code
	public function resendEmailChange(int $adminId): array
	{
		if ($this->emailChange === null) {
			return ['errors' => ['form' => 'Email change is not available right now.']];
		}

		$pending = $this->emailChange->findPendingForAdmin($adminId);
		if (!$pending || $pending['used_at'] !== null) {
			return ['errors' => ['form' => 'There is no pending email change. Please start again.']];
		}

		$current = $this->admin->findById($adminId);
		if (!$current) {
			return ['errors' => ['form' => 'Your admin account could not be found.']];
		}

		// Check that the address is still available.
		if ($this->admin->emailExists((string) $pending['new_email'], $adminId)) {
			$this->emailChange->clearForAdmin($adminId);
			return ['errors' => ['form' => 'An account with this email already exists.']];
		}

		return $this->issueEmailChangeCode($adminId, (string) $pending['new_email'], (string) $current['full_name']);
	}

	// Email code
	public function verifyEmailChange(int $adminId, string $code): array
	{
		$code = trim($code);

		if ($this->emailChange === null || !preg_match('/^\d{' . AdminEmailChange::CODE_LENGTH . '}$/', $code)) {
			return ['errors' => ['code' => 'Enter all ' . AdminEmailChange::CODE_LENGTH . ' digits.']];
		}

		$result = $this->emailChange->verify($adminId, $code);
		if (!$result['ok']) {
			return ['errors' => ['code' => $result['error'] ?? 'That code is incorrect.']];
		}

		$newEmail = (string) $result['new_email'];

		// Check again before saving.
		if ($this->admin->emailExists($newEmail, $adminId)) {
			$this->emailChange->clearForAdmin($adminId);
			return ['errors' => ['code' => 'An account with this email already exists.']];
		}

		$this->admin->updateEmail($adminId, $newEmail);
		$this->emailChange->markUsed($adminId);
		$this->activityLog->logAdmin($adminId, 'admin_email_changed', 'Updated email');

		return ['errors' => [], 'email' => $newEmail];
	}

	// Create email code
	private function issueEmailChangeCode(int $adminId, string $newEmail, string $fullName): array
	{
		$change = $this->emailChange->createForAdmin($adminId, $newEmail);
		$sent = sendEmailChangeVerificationEmail($newEmail, $fullName, $change['code'], $change['ttl_minutes']);

		$devCode = null;
		if (!$sent) {
			if (defined('MAIL_DEV_FALLBACK') && MAIL_DEV_FALLBACK) {
				// Show the code if email is unavailable.
				$devCode = $change['code'];
			} else {
				$this->emailChange->clearForAdmin($adminId);
				return ['errors' => ['form' => 'We could not send the email. Please try again later.']];
			}
		}

		return ['errors' => [], 'new_email' => $newEmail, 'dev_code' => $devCode];
	}

	// Change password
	public function changePassword(int $adminId, array $post): array
	{
		$currentPassword = (string) ($post['current_password'] ?? '');
		$new = (string) ($post['new_password'] ?? '');
		$confirm = (string) ($post['confirm_password'] ?? '');
		$errors = [];

		$admin = $this->admin->findById($adminId);
		if (!$admin || $currentPassword === '' || !password_verify($currentPassword, (string) $admin['password_hash'])) {
			$errors['current_password'] = 'Your current password is incorrect.';
		}

		if (!preg_match('/^(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $new)) {
			$errors['new_password'] = 'Password must be at least 8 characters and include a number and a special character.';
		} elseif (isset($admin['password_hash']) && password_verify($new, (string) $admin['password_hash'])) {
			$errors['new_password'] = 'Your new password must be different from your current password.';
		}

		if ($confirm === '' || $confirm !== $new) {
			$errors['confirm_password'] = 'Passwords do not match.';
		}

		if ($errors !== []) {
			return ['errors' => $errors];
		}

		$this->admin->updatePassword($adminId, password_hash($new, PASSWORD_DEFAULT));
		$this->activityLog->logAdmin($adminId, 'admin_password_changed');

		return ['errors' => []];
	}

	// Send reset code
	public function sendResetCode(int $adminId): array
	{
		$admin = $this->admin->findById($adminId);
		if (!$admin || $this->passwordReset === null) {
			return ['errors' => ['form' => 'Your admin account could not be found.']];
		}

		$reset = $this->passwordReset->createForAdmin($adminId);
		$sent = sendPasswordResetEmail(
			(string) $admin['email'],
			(string) $admin['full_name'],
			$reset['code'],
			$reset['ttl_minutes']
		);

		$devCode = null;
		if (!$sent) {
			if (defined('MAIL_DEV_FALLBACK') && MAIL_DEV_FALLBACK) {
				// Show the code if email is unavailable.
				$devCode = $reset['code'];
			} else {
				return ['errors' => ['form' => 'We could not send the email. Please try again later.']];
			}
		}

		return [
			'errors'   => [],
			'email'    => self::maskEmail((string) $admin['email']),
			'dev_code' => $devCode,
		];
	}

	// Reset code
	public function verifyResetCode(int $adminId, string $code): array
	{
		$code = trim($code);

		if ($this->passwordReset === null || !preg_match('/^\d{' . AdminPasswordReset::CODE_LENGTH . '}$/', $code)) {
			return ['ok' => false, 'error' => 'Enter all ' . AdminPasswordReset::CODE_LENGTH . ' digits.'];
		}

		return $this->passwordReset->verify($adminId, $code);
	}

	// Reset password
	public function resetPassword(int $adminId, array $post): array
	{
		$new = (string) ($post['new_password'] ?? '');
		$confirm = (string) ($post['confirm_password'] ?? '');
		$errors = [];

		if ($this->passwordReset === null || !$this->passwordReset->isVerified($adminId)) {
			return ['errors' => ['form' => 'Your reset session has expired. Please start again.']];
		}

		$admin = $this->admin->findById($adminId);
		if (!$admin) {
			return ['errors' => ['form' => 'Your admin account could not be found.']];
		}

		if (!preg_match('/^(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $new)) {
			$errors['new_password'] = 'Password must be at least 8 characters and include a number and a special character.';
		} elseif (password_verify($new, (string) $admin['password_hash'])) {
			$errors['new_password'] = 'Your new password must be different from your current password.';
		}

		if ($confirm === '' || $confirm !== $new) {
			$errors['confirm_password'] = 'Passwords do not match.';
		}

		if ($errors !== []) {
			return ['errors' => $errors];
		}

		$this->admin->updatePassword($adminId, password_hash($new, PASSWORD_DEFAULT));
		$this->passwordReset->markUsed($adminId);
		$this->activityLog->logAdmin($adminId, 'admin_password_reset', 'Reset password using an email code');

		return ['errors' => []];
	}

	// Mask email
	private static function maskEmail(string $email): string
	{
		$at = strpos($email, '@');
		if ($at === false || $at < 1) {
			return $email;
		}

		return mb_substr($email, 0, 1) . str_repeat('*', max(2, min($at - 1, 6))) . substr($email, $at);
	}

	// Recent admin activity
	public function listActivity(int $adminId, int $limit = 50): array
	{
		return $this->activityLog->listForAdminActor($adminId, $limit);
	}

	// Clear admin activity
	public function clearActivity(int $adminId): bool
	{
		return $this->activityLog->clearForAdminActor($adminId);
	}

	public function exportHeader(): array
	{
		return ['Type', 'Name', 'Date & Time', 'Activity', 'Details', 'Device', 'IP Address'];
	}

	// Export CSV rows
	public function exportBatches(bool $userOnly, int $max = self::EXPORT_MAX, bool $forCsv = true): Generator
	{
		$offset = 0;
		while ($offset < $max) {
			$rows = $this->activityLog->exportBatch($userOnly, self::EXPORT_BATCH, $offset);
			if ($rows === []) {
				return;
			}

			$batch = [];
			foreach ($rows as $row) {
				$batch[] = $this->exportRow($row, $forCsv);
			}
			yield $batch;

			if (count($rows) < self::EXPORT_BATCH) {
				return;
			}
			$offset += self::EXPORT_BATCH;
		}
	}

	// Format CSV row
	private function exportRow(array $row, bool $forCsv = true): array
	{
		$action = (string) $row['action'];

		if (!empty($row['admin_id'])) {
			$type = 'Admin';
			$name = (string) ($row['admin_name'] ?? 'Deleted admin');
		} elseif (!empty($row['user_id'])) {
			$type = 'User';
			$name = (string) ($row['user_name'] ?? 'Deleted user');
		} else {
			$type = 'Deleted account';
			$name = 'Deleted account';
		}

		$device = (string) ($row['device'] ?? '');
		if ($device === '') {
			$device = ActivityLog::detectDevice($row['user_agent'] ?? null);
		}

		$detail = trim((string) ($row['description'] ?? ''));

		$cells = [
			$type,
			$name,
			date('Y-m-d H:i:s', strtotime((string) $row['created_at'])),
			ActivityLog::adminLabel($action),
			$detail !== '' ? $detail : '—',
			$device,
			ActivityLog::displayIp($row['ip_address'] ?? null),
		];

		// Protect CSV cells from formulas.
		return $forCsv ? array_map([self::class, 'csvCell'], $cells) : array_map('strval', $cells);
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

	// Docs support (composer require phpoffice/phpword)
	public function docxAvailable(): bool
	{
		return DocxBuilder::available();
	}

	// Excel support (needs the PHP zip extension)
	public function xlsxAvailable(): bool
	{
		return XlsxBuilder::available();
	}

	// Activity logs as an Excel file with auto-fitted columns
	public function exportLogsXlsx(bool $userOnly): string
	{
		return XlsxBuilder::build(
			$this->exportHeader(),
			$this->flattenBatches($this->exportBatches($userOnly, self::EXPORT_MAX, false)),
			['sheet' => $userOnly ? 'User activity logs' : 'All activity logs']
		);
	}

	private function flattenBatches(Generator $batches): Generator
	{
		foreach ($batches as $batch) {
			foreach ($batch as $row) {
				yield $row;
			}
		}
	}

	// Return the report content, type, and extension.
	public function exportReport(int $adminId, bool $userOnly, string $format): array
	{
		if (!in_array($format, ['doc', 'pdf'], true)) {
			throw new InvalidArgumentException('Unsupported report format.');
		}

		$rows = [];
		foreach ($this->exportBatches($userOnly, self::EXPORT_REPORT_MAX, false) as $batch) {
			foreach ($batch as $row) {
				$rows[] = $row;
			}
		}

		$adminRecord = $this->admin->findById($adminId) ?: [];
		$report = [
			'rows'         => $rows,
			'scope_label'  => $userOnly ? 'User activity logs' : 'All activity logs (Admin & Users)',
			'generated_at' => date('F j, Y \a\t g:i A'),
			'prepared_by'  => (string) ($adminRecord['full_name'] ?? 'Administrator'),
			'limit_note'   => count($rows) >= self::EXPORT_REPORT_MAX
				? 'Showing the latest ' . number_format(self::EXPORT_REPORT_MAX) . ' entries. Use CSV to export everything.'
				: '',
		];

		// Docs: build a real .docx file
		if ($format === 'doc') {
			return [$this->renderDocx($report), DocxBuilder::MIME, 'docx'];
		}

		$html = $this->renderReportHtml($report, $format);

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

	// Build the activity log report as a real .docx file.
	private function renderDocx(array $report): string
	{
		$brown = '6B3F2A';
		$soft = '6F625B';
		$head = 'F5EDE6';

		$rows = $report['rows'] ?? [];

		$userCount = 0;
		$adminCount = 0;
		foreach ($rows as $row) {
			if (($row[0] ?? '') === 'Admin') {
				$adminCount++;
			} elseif (($row[0] ?? '') === 'User') {
				$userCount++;
			}
		}

		$doc = new DocxBuilder(true, 'Squir - Activity log report');

		$doc->heading('Activity Log Report', 1);
		$doc->paragraph(
			(string) ($report['scope_label'] ?? '') . ' · Prepared by ' . (string) ($report['prepared_by'] ?? '')
			. ' · ' . (string) ($report['generated_at'] ?? ''),
			['color' => $soft, 'size' => 17, 'after' => 160]
		);

		if (!empty($report['limit_note'])) {
			$doc->table([[[
				'text'  => (string) $report['limit_note'],
				'shade' => 'FBF1E3',
				'color' => $brown,
			]]], [100], ['border' => 'E0C39A', 'after' => 160]);
		}

		$summary = [];
		foreach ([
			[count($rows), 'Total entries'],
			[$userCount, 'User actions'],
			[$adminCount, 'Admin actions'],
		] as [$number, $label]) {
			$summary[] = [
				'align' => 'center',
				'paras' => [
					[[(string) $number, ['bold' => true, 'color' => $brown, 'size' => 30]]],
					[[$label, ['color' => $soft, 'size' => 16]]],
				],
			];
		}
		$doc->table([$summary], [34, 33, 33], ['after' => 200]);

		if ($rows === []) {
			$doc->paragraph('No activity logs to export.', ['italic' => true, 'color' => '8A7D75']);

			return $doc->build();
		}

		$headCell = ['shade' => $head, 'bold' => true, 'color' => $brown, 'size' => 17];
		$table = [[
			$headCell + ['text' => 'Type'],
			$headCell + ['text' => 'Name'],
			$headCell + ['text' => 'Date & Time'],
			$headCell + ['text' => 'Activity'],
			$headCell + ['text' => 'Details'],
			$headCell + ['text' => 'Device'],
			$headCell + ['text' => 'IP Address'],
		]];

		foreach ($rows as $row) {
			$cells = [];
			foreach (array_values($row) as $cell) {
				$cells[] = ['text' => (string) $cell, 'size' => 16];
			}
			$table[] = $cells;
		}

		$doc->table($table, [8, 14, 14, 16, 26, 12, 10], ['headerRows' => 1]);

		return $doc->build();
	}

	// User Summary Report: credentials, notes, and tasks per user (csv, doc, or pdf).
	public function exportUserSummary(int $adminId, string $format): array
	{
		if (!in_array($format, self::EXPORT_FORMATS, true)) {
			throw new InvalidArgumentException('Unsupported report format.');
		}
		if ($this->users === null) {
			throw new RuntimeException('User model is not available.');
		}

		$limit = in_array($format, ['csv', 'xlsx'], true) ? self::EXPORT_MAX : self::EXPORT_REPORT_MAX;
		$users = $this->users->summaryReport($limit);

		$rows = [];
		$totals = ['credentials' => 0, 'notes' => 0, 'tasks_done' => 0, 'tasks_pending' => 0];
		foreach ($users as $user) {
			$totals['credentials']   += $user['credentials'];
			$totals['notes']         += $user['notes'];
			$totals['tasks_done']    += $user['tasks_done'];
			$totals['tasks_pending'] += $user['tasks_pending'];

			$rows[] = [
				(string) $user['full_name'],
				(string) $user['email'],
				ucfirst((string) $user['status']),
				(string) $user['credentials'],
				(string) $user['notes'],
				(string) $user['tasks_done'],
				(string) $user['tasks_pending'],
				(string) ($user['credentials'] + $user['notes'] + $user['tasks_done'] + $user['tasks_pending']),
			];
		}

		// Excel: columns sized to fit, numbers stored as numbers
		if ($format === 'xlsx') {
			return [
				XlsxBuilder::build($this->userSummaryHeader(), $rows, ['sheet' => 'User Summary', 'numeric' => [3, 4, 5, 6, 7]]),
				XlsxBuilder::MIME,
				'xlsx',
			];
		}

		// CSV: header and rows only
		if ($format === 'csv') {
			$stream = fopen('php://temp', 'w+');
			fwrite($stream, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads the names correctly
			fputcsv($stream, $this->userSummaryHeader(), ',', '"', '\\');
			foreach ($rows as $row) {
				fputcsv($stream, array_map([self::class, 'csvCell'], $row), ',', '"', '\\');
			}
			rewind($stream);
			$csv = (string) stream_get_contents($stream);
			fclose($stream);

			return [$csv, 'text/csv; charset=utf-8', 'csv'];
		}

		$adminRecord = $this->admin->findById($adminId) ?: [];
		$report = [
			'header'       => $this->userSummaryHeader(),
			'rows'         => $rows,
			'totals'       => $totals,
			'generated_at' => date('F j, Y \a\t g:i A'),
			'prepared_by'  => (string) ($adminRecord['full_name'] ?? 'Administrator'),
			'limit_note'   => count($rows) >= $limit
				? 'Showing the first ' . number_format($limit) . ' users. Use CSV to export everything.'
				: '',
		];

		if ($format === 'doc') {
			return [$this->renderUserSummaryDocx($report), DocxBuilder::MIME, 'docx'];
		}

		ob_start();
		require __DIR__ . '/../../views/admin/export-user-summary.php';
		$html = (string) ob_get_clean();

		$options = new \Dompdf\Options();
		$options->set('defaultFont', 'DejaVu Sans');
		$options->set('isRemoteEnabled', false);
		$options->set('isPhpEnabled', false);

		$dompdf = new \Dompdf\Dompdf($options);
		$dompdf->loadHtml($html, 'UTF-8');
		$dompdf->setPaper('A4', 'portrait');
		$dompdf->render();

		$canvas = $dompdf->getCanvas();
		$font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
		$footerY = $canvas->get_height() - 28;
		$canvas->page_text(40, $footerY, 'Squir - User summary report', $font, 8, [0.45, 0.45, 0.45]);
		$canvas->page_text($canvas->get_width() - 110, $footerY, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 8, [0.45, 0.45, 0.45]);

		return [$dompdf->output(), 'application/pdf', 'pdf'];
	}

	public function userSummaryHeader(): array
	{
		return ['User', 'Email', 'Status', 'Credentials', 'Notes', 'Tasks Done', 'Tasks Pending', 'Total Items'];
	}

	// Build the User Summary Report as a real .docx file.
	private function renderUserSummaryDocx(array $report): string
	{
		$brown = '6B3F2A';
		$soft = '6F625B';
		$head = 'F5EDE6';

		$rows = $report['rows'] ?? [];
		$totals = $report['totals'] ?? [];

		$doc = new DocxBuilder(false, 'Squir - User summary report');

		$doc->heading('User Summary Report', 1);
		$doc->paragraph(
			'Credentials, notes, and tasks per user · Prepared by ' . (string) ($report['prepared_by'] ?? '')
			. ' · ' . (string) ($report['generated_at'] ?? ''),
			['color' => $soft, 'size' => 17, 'after' => 160]
		);

		if (!empty($report['limit_note'])) {
			$doc->table([[[
				'text'  => (string) $report['limit_note'],
				'shade' => 'FBF1E3',
				'color' => $brown,
			]]], [100], ['border' => 'E0C39A', 'after' => 160]);
		}

		$summary = [];
		foreach ([
			[count($rows), 'Users'],
			[$totals['credentials'] ?? 0, 'Credentials'],
			[$totals['notes'] ?? 0, 'Notes'],
			[$totals['tasks_done'] ?? 0, 'Tasks done'],
			[$totals['tasks_pending'] ?? 0, 'Tasks pending'],
		] as [$number, $label]) {
			$summary[] = [
				'align' => 'center',
				'paras' => [
					[[(string) $number, ['bold' => true, 'color' => $brown, 'size' => 30]]],
					[[$label, ['color' => $soft, 'size' => 16]]],
				],
			];
		}
		$doc->table([$summary], [20, 20, 20, 20, 20], ['after' => 200]);

		if ($rows === []) {
			$doc->paragraph('No users to report yet.', ['italic' => true, 'color' => '8A7D75']);

			return $doc->build();
		}

		$headCell = ['shade' => $head, 'bold' => true, 'color' => $brown, 'size' => 17];
		$table = [[]];
		foreach ($report['header'] as $i => $label) {
			$table[0][] = $headCell + ['text' => $label, 'align' => $i >= 3 ? 'center' : null];
		}

		foreach ($rows as $row) {
			$cells = [];
			foreach (array_values($row) as $i => $cell) {
				$cells[] = ['text' => (string) $cell, 'size' => 16, 'align' => $i >= 3 ? 'center' : null];
			}
			$table[] = $cells;
		}

		$doc->table($table, [18, 26, 10, 11, 8, 9, 10, 8], ['headerRows' => 1]);

		return $doc->build();
	}

	private function renderReportHtml(array $report, string $format): string
	{
		ob_start();
		require __DIR__ . '/../../views/admin/export-logs.php';

		return (string) ob_get_clean();
	}

	// Prevent CSV formula injection.
	private static function csvCell(mixed $value): string
	{
		$text = (string) $value;
		if ($text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
			$text = "'" . $text;
		}

		return $text;
	}
}