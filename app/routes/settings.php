<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/UserPin.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../models/EmailChange.php';
require_once __DIR__ . '/../controllers/SettingsController.php';
require_once __DIR__ . '/../controllers/PinController.php';
require_once __DIR__ . '/../controllers/EmailChangeController.php';

$userId = requireLogin();
$userModel = new User($dbh);
$pinModel = new UserPin($dbh);
$activityLogModel = new ActivityLog($dbh);
$emailChangeModel = new EmailChange($dbh);
$controller = new SettingsController($dbh, $userModel, $pinModel, $activityLogModel);
$pinController = new PinController($pinModel);
$emailChangeController = new EmailChangeController($userModel, $emailChangeModel, $activityLogModel);
$csrfToken = csrfToken();

// JSON response
function settingsJson(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

// Save theme + accent color
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'save_preferences') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        settingsJson(['error' => 'Your session expired. Please refresh the page.'], 403);
    }

    $clean = cleanPreferences($_POST);

    try {
        $userModel->updatePreferences($userId, $clean['theme'], $clean['accent']);
    } catch (PDOException $exception) {
        settingsJson(['error' => 'Could not save your appearance.'], 500);
    }

    $current = sessionPreferences('user');
    $_SESSION['prefs_user'] = [
        'theme'  => $clean['theme'] ?? $current['theme'],
        'accent' => $clean['accent'] ?? $current['accent'],
    ];

    settingsJson(['success' => true]);
}

// Profile update
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

// Request an email change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'request_email_change') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        settingsJson(['error' => 'Your session expired. Please refresh the page.'], 403);
    }

    $cooldown = defined('EMAIL_CHANGE_RESEND_COOLDOWN_SECONDS') ? (int) EMAIL_CHANGE_RESEND_COOLDOWN_SECONDS : 60;
    $lastSent = (int) ($_SESSION['email_change_last_sent'] ?? 0);
    $wait = $cooldown - (time() - $lastSent);
    if ($wait > 0) {
        settingsJson([
            'error'       => 'A code was sent recently. Please wait ' . $wait . ' seconds before requesting another.',
            'retry_after' => $wait,
        ], 429);
    }

    $result = $emailChangeController->requestChange($userId, $_POST);
    if ($result['errors'] !== []) {
        settingsJson(['error' => reset($result['errors']), 'errors' => $result['errors']], 422);
    }

    $_SESSION['email_change_last_sent'] = time();

    settingsJson([
        'success'     => true,
        'new_email'   => $result['new_email'],
        'dev_code'    => $result['dev_code'],
        'retry_after' => $cooldown,
    ]);
}

// Resend email verification code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'resend_email_change') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        settingsJson(['error' => 'Your session expired. Please refresh the page.'], 403);
    }

    $cooldown = defined('EMAIL_CHANGE_RESEND_COOLDOWN_SECONDS') ? (int) EMAIL_CHANGE_RESEND_COOLDOWN_SECONDS : 60;
    $lastSent = (int) ($_SESSION['email_change_last_sent'] ?? 0);
    $wait = $cooldown - (time() - $lastSent);
    if ($wait > 0) {
        settingsJson([
            'error'       => 'A code was sent recently. Please wait ' . $wait . ' seconds before requesting another.',
            'retry_after' => $wait,
        ], 429);
    }

    $result = $emailChangeController->resendCode($userId);
    if ($result['errors'] !== []) {
        settingsJson(['error' => reset($result['errors']), 'errors' => $result['errors']], 422);
    }

    $_SESSION['email_change_last_sent'] = time();

    settingsJson([
        'success'     => true,
        'new_email'   => $result['new_email'],
        'dev_code'    => $result['dev_code'],
        'retry_after' => $cooldown,
    ]);
}

// Apply email change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'verify_email_change') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        settingsJson(['error' => 'Your session expired. Please refresh the page.'], 403);
    }

    $result = $emailChangeController->verifyAndApply($userId, (string) ($_POST['code'] ?? ''));
    if ($result['errors'] !== []) {
        settingsJson(['error' => reset($result['errors']), 'errors' => $result['errors']], 422);
    }

    unset($_SESSION['email_change_last_sent']);

    settingsJson([
        'success' => true,
        'email'   => $result['email'],
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

    // Ask for the PIN before deleting
    $pinResult = $pinController->verify($userId, (string) ($_POST['pin'] ?? ''));
    if (empty($pinResult['ok'])) {
        settingsJson(['error' => $pinResult['error'] ?? 'That PIN is incorrect.'], 401);
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

// Export PIN check
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

// Items the user can pick from in the export dialog
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['export_options'])) {
    try {
        settingsJson(['ok' => true, 'options' => $controller->exportOptions($userId)]);
    } catch (PDOException $error) {
        error_log('Squir export options failed: ' . $error->getMessage());
        settingsJson(['error' => 'Could not load the export options. Please try again.'], 500);
    }
}

// Export account data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['export'])) {
    // Keep PHP warnings out of the export.
    ini_set('display_errors', '0');

    $format = (string) $_GET['export'];
    if (!in_array($format, SettingsController::EXPORT_FORMATS, true)) {
        settingsJson(['error' => 'Please choose a valid file format.'], 400);
    }

    // Read the selected export scope
    $scope = null;
    if (isset($_GET['scope'])) {
        $decodedScope = json_decode((string) $_GET['scope'], true);
        if (!is_array($decodedScope)) {
            settingsJson(['error' => 'Please choose what to export.'], 400);
        }

        try {
            $scope = $controller->normalizeScope($decodedScope);
        } catch (InvalidArgumentException $error) {
            settingsJson(['error' => $error->getMessage()], 400);
        }
    }

    // Consume the one-time unlock
    $unlockedUntil = (int) ($_SESSION['export_unlocked_until'] ?? 0);
    unset($_SESSION['export_unlocked_until']);
    if ($unlockedUntil < time()) {
        settingsJson(['error' => 'pin_required'], 423);
    }

    if ($format === 'pdf' && !$controller->pdfAvailable()) {
        settingsJson(['error' => 'PDF export is not set up yet. Run "composer require dompdf/dompdf" in the project folder.'], 500);
    }

    if ($format === 'xlsx' && !$controller->xlsxAvailable()) {
        settingsJson(['error' => 'Excel export needs the PHP zip extension. Enable extension=zip in php.ini.'], 500);
    }

    if ($format === 'doc' && !$controller->docxAvailable()) {
        settingsJson(['error' => 'Docs export is not set up yet. Run "composer require phpoffice/phpword" in the project folder.'], 500);
    }

    try {
        // Plain-text passwords require safe=0.
        $encryptPasswords = ($_GET['safe'] ?? '1') !== '0';
        [$body, $mime, $extension] = $controller->exportFile($userId, $format, $encryptPasswords, $scope);
    } catch (Throwable $error) {
        error_log('Squir ' . $format . ' export failed: ' . $error->getMessage());
        settingsJson(['error' => 'Could not create your export. Please try again.'], 500);
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="squir-export-' . date('Y-m-d') . '.' . $extension . '"');
    header('Content-Length: ' . strlen($body));
    header('Cache-Control: private, no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    echo $body;
    exit;
}

// Select settings panel
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