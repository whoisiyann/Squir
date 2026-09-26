<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/PinReset.php';
require_once __DIR__ . '/../models/UserPin.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../controllers/PinResetController.php';

$userId = requireLogin();

// Remember where to send the back link if we arrived from Settings > Reset PIN
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['from'] ?? '') === 'settings') {
    $_SESSION['pinreset_from'] = 'settings';
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['from'])) {
    unset($_SESSION['pinreset_from']);
}
$backUrl = ($_SESSION['pinreset_from'] ?? '') === 'settings' ? './settings?panel=reset-pin' : './settings';

$errors = [];
$prefillEmail = $_SESSION['pinreset_email'] ?? '';
if ($prefillEmail === '' && $_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['email'])) {
    $prefillEmail = trim((string) $_GET['email']);
}
$values = ['email' => $prefillEmail];
$csrfToken = csrfToken();

// Process the "forgot PIN" email submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $cooldown = defined('PIN_RESET_RESEND_COOLDOWN_SECONDS') ? (int) PIN_RESET_RESEND_COOLDOWN_SECONDS : 60;
        $lastSent = (int) ($_SESSION['pinreset_last_sent'] ?? 0);

        if (time() - $lastSent < $cooldown) {
            $errors['form'] = 'Please wait a bit before requesting another code.';
            $values['email'] = trim((string) ($_POST['email'] ?? ''));
        } else {
            $controller = new PinResetController(new User($dbh), new PinReset($dbh), new ActivityLog($dbh), new UserPin($dbh));
            $result = $controller->requestCode($userId, $_POST);
            $errors = $result['errors'];
            $values['email'] = trim((string) ($_POST['email'] ?? ''));

            if ($errors === []) {
                $_SESSION['pinreset_email'] = $values['email'];
                $_SESSION['pinreset_last_sent'] = time();
                $_SESSION['pinreset_dev_code'] = $result['dev_code'];
                unset($_SESSION['pinreset_verified']);
                header('Location: ./verify-pin-code');
                exit;
            }
        }
    }
}

// Render the "forgot PIN" form
require __DIR__ . '/../views/settings/forgot-pin.php';
