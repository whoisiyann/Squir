<?php

require_once __DIR__ . '/../controllers/PasswordResetController.php';
require_once __DIR__ . '/../models/ActivityLog.php';

// Remember where to send the back link if we arrived from Settings > Change Password
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['from'] ?? '') === 'settings') {
    $_SESSION['pwreset_from'] = 'settings';
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['from'])) {
    unset($_SESSION['pwreset_from']);
}
$backUrl = ($_SESSION['pwreset_from'] ?? '') === 'settings' ? './settings?panel=change-password' : './login';

$errors = [];
$prefillEmail = $_SESSION['pwreset_email'] ?? '';
if ($prefillEmail === '' && $_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['email'])) {
    $prefillEmail = trim((string) $_GET['email']);
}
$values = ['email' => $prefillEmail];
$csrfToken = csrfToken();

// Process the "forgot password" email submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $cooldown = defined('PASSWORD_RESET_RESEND_COOLDOWN_SECONDS') ? (int) PASSWORD_RESET_RESEND_COOLDOWN_SECONDS : 60;
        $lastSent = (int) ($_SESSION['pwreset_last_sent'] ?? 0);

        if (time() - $lastSent < $cooldown) {
            $errors['form'] = 'Please wait a bit before requesting another code.';
            $values['email'] = trim((string) ($_POST['email'] ?? ''));
        } else {
            $controller = new PasswordResetController(new User($dbh), new PasswordReset($dbh), new ActivityLog($dbh));
            $result = $controller->requestCode($_POST);
            $errors = $result['errors'];
            $values['email'] = $result['email'];

            if ($errors === []) {
                $_SESSION['pwreset_email'] = $result['email'];
                $_SESSION['pwreset_last_sent'] = time();
                $_SESSION['pwreset_dev_code'] = $result['dev_code'];
                unset($_SESSION['pwreset_verified']);
                header('Location: ./verify-reset-code');
                exit;
            }
        }
    }
}

// Render the "forgot password" form
require __DIR__ . '/../views/auth/forgot-password.php';