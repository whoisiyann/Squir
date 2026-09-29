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
    if ((string) ($_POST['ajax'] ?? '') !== 'clear_logs') {
        adminLogsJson(['error' => 'Unknown request.'], 400);
    }

    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        adminLogsJson(['error' => 'Your session expired. Please refresh the page and try again.'], 403);
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
if (($_GET['export'] ?? '') === 'csv') {
    $filename = 'squir-activity-logs-' . date('Y-m-d-His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads the names correctly
    fputcsv($out, ['User', 'Date & Time', 'Activity', 'Details', 'Device', 'IP Address'], ',', '"', '\\');
    foreach ($controller->exportRows($filters) as $row) {
        fputcsv($out, array_map('adminLogsCsvCell', $row), ',', '"', '\\');
    }
    fclose($out);
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