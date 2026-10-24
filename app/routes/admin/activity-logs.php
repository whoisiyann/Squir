<?php

require_once __DIR__ . '/../../../includes/admin_auth.php';
require_once __DIR__ . '/../../models/admin/Admin.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/ActivityLog.php';
require_once __DIR__ . '/../../controllers/admin/AdminActivityLogController.php';

$adminId = requireAdminLogin();

$admin = (new Admin($dbh))->findById($adminId);
if (!$admin) {
    // Session check
    session_unset();
    session_destroy();
    header('Location: ' . url('login'));
    exit;
}

$activityLog = new ActivityLog($dbh);
$controller = new AdminActivityLogController($activityLog, new User($dbh));

// JSON response
function adminLogsJson(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode($payload);
    exit;
}

// Prevent CSV formula injection.
function adminLogsCsvCell(mixed $value): string
{
    $text = (string) $value;
    if ($text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        $text = "'" . $text;
    }

    return $text;
}

// Clear logs (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = (string) ($_POST['ajax'] ?? '');
    if (!in_array($postAction, ['clear_logs', 'delete_logs'], true)) {
        adminLogsJson(['error' => 'Unknown request.'], 400);
    }

    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        adminLogsJson(['error' => 'Your session expired. Please refresh the page and try again.'], 403);
    }

    // Delete only the selected logs
    if ($postAction === 'delete_logs') {
        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids)) {
            adminLogsJson(['error' => 'Please select at least one log.'], 422);
        }

        try {
            $result = $controller->deleteSelected($ids);
        } catch (PDOException $exception) {
            adminLogsJson(['error' => 'Could not delete the selected logs. Please try again.'], 500);
        }

        if ($result['errors'] !== []) {
            adminLogsJson(['error' => reset($result['errors'])], 422);
        }

        if ($result['deleted'] > 0) {
            $activityLog->logAdmin(
                $adminId,
                'logs_cleared',
                'Deleted ' . $result['deleted'] . ' selected user activity log' . ($result['deleted'] === 1 ? '' : 's')
            );
        }

        adminLogsJson(['success' => true, 'deleted' => $result['deleted']]);
    }

    try {
        $result = $controller->clear((string) ($_POST['range'] ?? ''));
    } catch (PDOException $exception) {
        adminLogsJson(['error' => 'Could not clear the activity logs. Please try again.'], 500);
    }

    if ($result['errors'] !== []) {
        adminLogsJson(['error' => reset($result['errors'])], 422);
    }

    if ($result['deleted'] > 0) {
        $activityLog->logAdmin(
            $adminId,
            'logs_cleared',
            'Cleared ' . $result['deleted'] . ' user activity log' . ($result['deleted'] === 1 ? '' : 's') . ' (' . $result['label'] . ')'
        );
    }

    adminLogsJson(['success' => true, 'deleted' => $result['deleted'], 'label' => $result['label']]);
}

$filters = $controller->filters($_GET);

// Export: same filters as the screen
if (isset($_GET['export'])) {
    // Hide PHP warnings in export.
    ini_set('display_errors', '0');

    $format = (string) $_GET['export'];
    if (!in_array($format, AdminActivityLogController::EXPORT_FORMATS, true)) {
        adminLogsJson(['error' => 'Please choose a valid file format.'], 400);
    }

    if ($format === 'pdf' && !$controller->pdfAvailable()) {
        adminLogsJson(['error' => 'PDF export is not set up yet. Run "composer require dompdf/dompdf" in the project folder.'], 500);
    }

    if ($format === 'xlsx' && !$controller->xlsxAvailable()) {
        adminLogsJson(['error' => 'Excel export needs the PHP zip extension. Enable extension=zip in php.ini.'], 500);
    }

    if ($format === 'doc' && !$controller->docxAvailable()) {
        adminLogsJson(['error' => 'Docs export is not set up yet. Run "composer require phpoffice/phpword" in the project folder.'], 500);
    }

    $formatLabels = ['csv' => 'CSV', 'xlsx' => 'Excel', 'doc' => 'Docs', 'pdf' => 'PDF'];
    $baseName = 'squir-activity-logs-' . date('Y-m-d-His');

    try {
        if ($format === 'csv') {
            $csvRows = $controller->exportRows($filters);
        } else {
            [$body, $mime, $extension] = $controller->exportFile($filters, $format, (string) ($admin['full_name'] ?? 'Administrator'));
        }
    } catch (Throwable $error) {
        error_log('Squir activity log ' . $format . ' export failed: ' . $error->getMessage());
        adminLogsJson(['error' => 'Could not create the export. Please try again.'], 500);
    }

    $activityLog->logAdmin(
        $adminId,
        'admin_logs_exported',
        'Exported user activity logs from the Activity Logs page (' . $formatLabels[$format] . ')'
    );

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $baseName . '.csv"');
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads the names correctly
        fputcsv($out, $controller->exportHeader(), ',', '"', '\\');
        foreach ($csvRows as $row) {
            fputcsv($out, array_map('adminLogsCsvCell', $row), ',', '"', '\\');
        }
        fclose($out);
        exit;
    }

    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $baseName . '.' . $extension . '"');
    header('Content-Length: ' . strlen($body));
    header('Cache-Control: no-store');
    echo $body;
    exit;
}

// Filters, search, refresh, and scrolling ask for rows only
if (($_GET['ajax'] ?? '') === 'rows') {
    try {
        $result = $controller->rows($filters, (int) ($_GET['page'] ?? 1));
    } catch (PDOException $exception) {
        adminLogsJson(['error' => 'Could not load the activity logs. Please try again.'], 500);
    }

    ob_start();
    $logs = $result['logs'];
    require __DIR__ . '/../../views/admin/partials/activity-log-rows.php';
    $html = ob_get_clean();

    adminLogsJson([
        'html'     => $html,
        'total'    => $result['total'],
        'count'    => count($result['logs']),
        'page'     => $result['page'],
        'has_more' => $result['has_more'],
    ]);
}

$data = $controller->index($_GET);
$logs = $data['logs'];
$total = $data['total'];
$hasMore = $data['has_more'];
$filters = $data['filters'];
$actionGroups = $data['actionGroups'];
$filterUser = $data['filterUser'];
$csrfToken = csrfToken();

require __DIR__ . '/../../views/admin/activity-logs.php';