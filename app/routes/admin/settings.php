<?php

require_once __DIR__ . '/../../../includes/admin_auth.php';
require_once __DIR__ . '/../../models/admin/Admin.php';
require_once __DIR__ . '/../../models/ActivityLog.php';
require_once __DIR__ . '/../../models/admin/AdminPasswordReset.php';
require_once __DIR__ . '/../../models/admin/AdminEmailChange.php';
require_once __DIR__ . '/../../controllers/admin/AdminSettingsController.php';

$adminId = requireAdminLogin();

$adminModel = new Admin($dbh);
$admin = $adminModel->findById($adminId);
if (!$admin) {
    // Deleted admin session
    session_unset();
    session_destroy();
    header('Location: ' . url('login'));
    exit;
}

$activityLog = new ActivityLog($dbh);
$controller = new AdminSettingsController($adminModel, $activityLog, new AdminPasswordReset($dbh), new AdminEmailChange($dbh));
$csrfToken = csrfToken();

// JSON response
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

    // Profile update
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

    // Send email code
    if ($ajax === 'request_email_change' || $ajax === 'resend_email_change') {
        $cooldown = defined('EMAIL_CHANGE_RESEND_COOLDOWN_SECONDS') ? (int) EMAIL_CHANGE_RESEND_COOLDOWN_SECONDS : 60;
        $wait = $cooldown - (time() - (int) ($_SESSION['admin_email_change_last_sent'] ?? 0));

        if ($wait > 0) {
            adminSettingsJson([
                'error'       => 'A code was sent recently. Please wait ' . $wait . ' seconds before requesting another.',
                'retry_after' => $wait,
            ], 429);
        }

        try {
            $result = $ajax === 'request_email_change'
                ? $controller->requestEmailChange($adminId, $_POST)
                : $controller->resendEmailChange($adminId);
        } catch (PDOException $exception) {
            adminSettingsJson(['error' => 'Could not send the code. Please try again.'], 500);
        }

        if ($result['errors'] !== []) {
            adminSettingsJson(['error' => reset($result['errors']), 'errors' => $result['errors']], 422);
        }

        $_SESSION['admin_email_change_last_sent'] = time();

        adminSettingsJson([
            'success'     => true,
            'new_email'   => $result['new_email'],
            'dev_code'    => $result['dev_code'],
            'retry_after' => $cooldown,
        ]);
    }

    // Email code
    if ($ajax === 'verify_email_change') {
        try {
            $result = $controller->verifyEmailChange($adminId, (string) ($_POST['code'] ?? ''));
        } catch (PDOException $exception) {
            adminSettingsJson(['error' => 'Could not verify the code. Please try again.'], 500);
        }

        if ($result['errors'] !== []) {
            adminSettingsJson(['error' => reset($result['errors']), 'errors' => $result['errors']], 422);
        }

        unset($_SESSION['admin_email_change_last_sent']);

        adminSettingsJson([
            'success' => true,
            'email'   => $result['email'],
        ]);
    }

    // Change password
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

    // Send reset code
    if ($ajax === 'forgot_send_code') {
        $cooldown = defined('PASSWORD_RESET_RESEND_COOLDOWN_SECONDS') ? (int) PASSWORD_RESET_RESEND_COOLDOWN_SECONDS : 60;
        $wait = $cooldown - (time() - (int) ($_SESSION['admin_pwreset_last_sent'] ?? 0));

        if ($wait > 0) {
            adminSettingsJson([
                'error'       => 'A code was sent recently. Please wait ' . $wait . ' seconds before requesting another.',
                'retry_after' => $wait,
            ], 429);
        }

        try {
            $result = $controller->sendResetCode($adminId);
        } catch (PDOException $exception) {
            adminSettingsJson(['error' => 'Could not send the code. Please try again.'], 500);
        }

        if ($result['errors'] !== []) {
            adminSettingsJson(['error' => reset($result['errors']), 'errors' => $result['errors']], 422);
        }

        $_SESSION['admin_pwreset_last_sent'] = time();

        adminSettingsJson([
            'success'     => true,
            'email'       => $result['email'],
            'dev_code'    => $result['dev_code'],
            'retry_after' => $cooldown,
        ]);
    }

    // Reset code
    if ($ajax === 'forgot_verify_code') {
        try {
            $result = $controller->verifyResetCode($adminId, (string) ($_POST['code'] ?? ''));
        } catch (PDOException $exception) {
            adminSettingsJson(['error' => 'Could not verify the code. Please try again.'], 500);
        }

        if (!$result['ok']) {
            adminSettingsJson(['error' => $result['error'] ?? 'That code is incorrect.'], 422);
        }

        adminSettingsJson(['success' => true]);
    }

    // Save new password
    if ($ajax === 'forgot_reset_password') {
        try {
            $result = $controller->resetPassword($adminId, $_POST);
        } catch (PDOException $exception) {
            adminSettingsJson(['error' => 'Could not update your password. Please try again.'], 500);
        }

        if ($result['errors'] !== []) {
            adminSettingsJson(['error' => reset($result['errors']), 'errors' => $result['errors']], 422);
        }

        unset($_SESSION['admin_pwreset_last_sent']);

        adminSettingsJson(['success' => true]);
    }

    // Clear activity log
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

// Export CSV
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