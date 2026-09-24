<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/UserPin.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../controllers/SettingsController.php';
require_once __DIR__ . '/../controllers/PinController.php';

$userId = requireLogin();
$userModel = new User($dbh);
$pinModel = new UserPin($dbh);
$activityLogModel = new ActivityLog($dbh);
$controller = new SettingsController($dbh, $userModel, $pinModel, $activityLogModel);
$pinController = new PinController($pinModel);
$csrfToken = csrfToken();

// Send a settings JSON response
function settingsJson(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

// Update account information (Edit modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'update_profile') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        settingsJson(['error' => 'Your session expired. Please refresh the page.'], 403);
    }

    $result = $controller->updateProfile($userId, $_POST);
    if ($result['errors'] !== []) {
        settingsJson(['error' => reset($result['errors']), 'errors' => $result['errors']], 422);
    }

    settingsJson([
        'success'   => true,
        'full_name' => $result['user']['full_name'],
        'username'  => $result['user']['username'],
        'email'     => $result['user']['email'],
        'initials'  => userInitials($result['user']['full_name']),
    ]);
}

// Change account password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'change_password') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        settingsJson(['error' => 'Your session expired. Please refresh the page.'], 403);
    }

    $result = $controller->changePassword($userId, $_POST);
    if ($result['errors'] !== []) {
        settingsJson(['error' => reset($result['errors']), 'errors' => $result['errors']], 422);
    }

    settingsJson(['success' => true]);
}

// Reset the vault PIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'reset_pin') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        settingsJson(['error' => 'Your session expired. Please refresh the page.'], 403);
    }

    $result = $controller->resetPin($userId, $_POST);
    if ($result['errors'] !== []) {
        settingsJson(['error' => reset($result['errors']), 'errors' => $result['errors']], 422);
    }

    settingsJson(['success' => true]);
}

// Clear the activity log
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'clear_activity_log') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        settingsJson(['error' => 'Your session expired. Please refresh the page.'], 403);
    }

    $controller->clearActivityLog($userId);
    settingsJson(['success' => true]);
}

// Delete the account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'delete_account') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        settingsJson(['error' => 'Your session expired. Please refresh the page.'], 403);
    }

    $deleted = $controller->deleteAccount($userId);
    if (!$deleted) {
        settingsJson(['error' => 'Could not delete your account. Please try again.'], 500);
    }

    // End the session
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();

    settingsJson(['success' => true]);
}

// Verify the export PIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'verify_export_pin') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        settingsJson(['ok' => false, 'error' => 'Your session expired. Please refresh the page.'], 403);
    }

    $result = $pinController->verify($userId, (string) ($_POST['pin'] ?? ''));
    if (!empty($result['ok'])) {
        $_SESSION['export_unlocked_until'] = time() + 120;
        settingsJson(['ok' => true]);
    }

    settingsJson(['ok' => false, 'error' => $result['error'] ?? 'That PIN is incorrect.'], 401);
}

// Export account data as a PIN-protected PDF download
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['export'] ?? '') === 'pdf') {
    // Keep warnings out of the PDF response
    ini_set('display_errors', '0');

    // Consume the one-time unlock
    $unlockedUntil = (int) ($_SESSION['export_unlocked_until'] ?? 0);
    unset($_SESSION['export_unlocked_until']);
    if ($unlockedUntil < time()) {
        settingsJson(['error' => 'pin_required'], 423);
    }

    if (!$controller->pdfAvailable()) {
        settingsJson(['error' => 'PDF export is not set up yet. Run "composer require dompdf/dompdf" in the project folder.'], 500);
    }

    try {
        $pdf = $controller->exportPdf($userId);
    } catch (Throwable $error) {
        error_log('Squir PDF export failed: ' . $error->getMessage());
        settingsJson(['error' => 'Could not create your PDF. Please try again.'], 500);
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="squir-export-' . date('Y-m-d') . '.pdf"');
    header('Content-Length: ' . strlen($pdf));
    header('Cache-Control: private, no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    echo $pdf;
    exit;
}

// Determine which settings panel to render
$panel = $_GET['panel'] ?? 'index';
$allowedPanels = ['index', 'activity-log', 'change-password', 'reset-pin'];
if (!in_array($panel, $allowedPanels, true)) {
    $panel = 'index';
}

$user = $userModel->findById($userId);
if (!$user) {
    session_unset();
    session_destroy();
    header('Location: ./login');
    exit;
}

$initials = userInitials($user['full_name']);
$route = 'settings';
$errors = [];

switch ($panel) {
    case 'activity-log':
        $activity = $controller->listActivity($userId);
        require __DIR__ . '/../views/settings/activity-log.php';
        break;

    case 'change-password':
        require __DIR__ . '/../views/settings/change-password.php';
        break;

    case 'reset-pin':
        $pinLength = UserPin::length();
        require __DIR__ . '/../views/settings/reset-pin.php';
        break;

    default:
        require __DIR__ . '/../views/settings/index.php';
        break;
}