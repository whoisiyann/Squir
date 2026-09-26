<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/PinReset.php';
require_once __DIR__ . '/../models/UserPin.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../controllers/PinResetController.php';

$userId = requireLogin();

// Require an active reset request
if (empty($_SESSION['pinreset_email'])) {
    header('Location: ./forgot-pin');
    exit;
}

$email = $_SESSION['pinreset_email'];
$errors = [];
$notice = null;
$devCode = $_SESSION['pinreset_dev_code'] ?? null;
$csrfToken = csrfToken();

// Process code verification or a resend request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        $errors['code'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $controller = new PinResetController(new User($dbh), new PinReset($dbh), new ActivityLog($dbh), new UserPin($dbh));
        $intent = $_POST['intent'] ?? 'verify';

        if ($intent === 'resend') {
            $cooldown = defined('PIN_RESET_RESEND_COOLDOWN_SECONDS') ? (int) PIN_RESET_RESEND_COOLDOWN_SECONDS : 60;
            $lastSent = (int) ($_SESSION['pinreset_last_sent'] ?? 0);

            if (time() - $lastSent < $cooldown) {
                $errors['code'] = 'Please wait a bit before requesting another code.';
            } else {
                $devCode = $controller->resendCode($userId);
                $_SESSION['pinreset_last_sent'] = time();
                $_SESSION['pinreset_dev_code'] = $devCode;
                $notice = 'We sent a new code to your email.';
            }
        } else {
            $result = $controller->verifyCode($userId, (string) ($_POST['code'] ?? ''));

            if ($result['ok']) {
                $_SESSION['pinreset_verified'] = true;
                header('Location: ./reset-pin-new');
                exit;
            }

            $errors['code'] = $result['error'];
        }
    }
}

// Render the code verification form
require __DIR__ . '/../views/settings/verify-pin-code.php';
