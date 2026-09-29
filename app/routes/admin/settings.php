<?php

require_once __DIR__ . '/../../../includes/admin_auth.php';
require_once __DIR__ . '/../../models/admin/Admin.php';
require_once __DIR__ . '/../../models/ActivityLog.php';
require_once __DIR__ . '/../../controllers/admin/AdminSettingsController.php';

$adminId = requireAdminLogin();

$adminModel = new Admin($dbh);
$admin = $adminModel->findById($adminId);
if (!$admin) {
    // Handle deleted admin session
    session_unset();
    session_destroy();
    header('Location: ' . url('login'));
    exit;
}

$activityLog = new ActivityLog($dbh);
$controller = new AdminSettingsController($adminModel, $activityLog);
$csrfToken = csrfToken();

// Send a JSON response and stop
function adminSettingsJson(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ajax = (string) ($_POST['ajax'] ?? '');

    if ($ajax === '') {
        adminSettingsJson(['error' => 'Unknown request.'], 400);
    }

    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        adminSettingsJson(['error' => 'Your session expired. Please refresh the page.'], 403);
    }

    // Update account info
    if ($ajax === 'update_profile') {
        try {
            $result = $controller->updateProfile($adminId, $_POST);
        } catch (PDOException $exception) {
            adminSettingsJson(['error' => 'Could not save your changes. Please try again.'], 500);
        }

        if ($result['errors'] !== []) {
            adminSettingsJson(['error' => reset($result['errors']), 'errors' => $result['errors']], 422);
        }

        $updated = $result['admin'];
        $_SESSION['admin_name'] = $updated['full_name'];

        adminSettingsJson([
            'success'   => true,
            'full_name' => $updated['full_name'],
            'username'  => $updated['username'],
            'email'     => $updated['email'],
            'initials'  => adminInitials($updated['full_name']),
        ]);
    }

    // Change account password
    if ($ajax === 'change_password') {
        try {
            $result = $controller->changePassword($adminId, $_POST);
        } catch (PDOException $exception) {
            adminSettingsJson(['error' => 'Could not update your password. Please try again.'], 500);
        }

        if ($result['errors'] !== []) {
            adminSettingsJson(['error' => reset($result['errors']), 'errors' => $result['errors']], 422);
        }

        adminSettingsJson(['success' => true]);
    }

    // Clear the admin's own activity log
    if ($ajax === 'clear_activity_log') {
        try {
            $controller->clearActivity($adminId);
        } catch (PDOException $exception) {
            adminSettingsJson(['error' => 'Could not clear your activity log. Please try again.'], 500);
        }

        adminSettingsJson(['success' => true]);
    }

    adminSettingsJson(['error' => 'Unknown request.'], 400);
}

// Export logs as CSV
if (($_GET['export'] ?? '') === 'csv') {
    // Keep warnings out of the file.
    ini_set('display_errors', '0');

    $userOnly = ($_GET['scope'] ?? 'users') !== 'all';

    $batches = $controller->exportBatches($userOnly);
    try {
        $first = $batches->current();
    } catch (Throwable $error) {
        error_log('Squir log export failed: ' . $error->getMessage());
        adminSettingsJson(['error' => 'Could not create the export. Please try again.'], 500);
    }

    $activityLog->logAdmin(
        $adminId,
        'admin_logs_exported',
        $userOnly ? 'Exported user activity logs (CSV)' : 'Exported all activity logs (CSV)'
    );

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $filename = 'squir-' . ($userOnly ? 'user' : 'all') . '-logs-' . date('Y-m-d-His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads the names correctly
    fputcsv($out, $controller->exportHeader(), ',', '"', '\\');

    if ($first !== null) {
        foreach ($first as $row) {
            fputcsv($out, $row, ',', '"', '\\');
        }
        $batches->next();
        while ($batches->valid()) {
            foreach ($batches->current() as $row) {
                fputcsv($out, $row, ',', '"', '\\');
            }
            $batches->next();
        }
    }

    fclose($out);
    exit;
}

// Select settings panel
$panel = (string) ($_GET['panel'] ?? 'index');
if (!in_array($panel, ['index', 'activity-log', 'change-password'], true)) {
    $panel = 'index';
}

$activity = $panel === 'activity-log' ? $controller->listActivity($adminId) : [];

require __DIR__ . '/../../views/admin/settings.php';