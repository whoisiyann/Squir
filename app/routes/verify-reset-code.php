<?php

require_once __DIR__ . '/../controllers/PasswordResetController.php';

// Must have started the flow from step 1
if (empty($_SESSION['pwreset_email'])) {
    header('Location: ./forgot-password');
    exit;
}

$email = $_SESSION['pwreset_email'];
$errors = [];
$notice = null;
$devCode = $_SESSION['pwreset_dev_code'] ?? null;
$csrfToken = csrfToken();

// Process code verification or a resend request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        $errors['code'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $controller = new PasswordResetController(new User($dbh), new PasswordReset($dbh));
        $intent = $_POST['intent'] ?? 'verify';

        if ($intent === 'resend') {
            $cooldown = defined('PASSWORD_RESET_RESEND_COOLDOWN_SECONDS') ? (int) PASSWORD_RESET_RESEND_COOLDOWN_SECONDS : 60;
            $lastSent = (int) ($_SESSION['pwreset_last_sent'] ?? 0);

            if (time() - $lastSent < $cooldown) {
                $errors['code'] = 'Please wait a bit before requesting another code.';
            } else {
                $devCode = $controller->resendCode($email);
                $_SESSION['pwreset_last_sent'] = time();
                $_SESSION['pwreset_dev_code'] = $devCode;
                $notice = 'We sent a new code to your email.';
            }
        } else {
            $result = $controller->verifyCode($email, (string) ($_POST['code'] ?? ''));

            if ($result['ok']) {
                $_SESSION['pwreset_verified'] = true;
                header('Location: ./reset-password');
                exit;
            }

            $errors['code'] = $result['error'];
        }
    }
}

// Render the code verification form
require __DIR__ . '/../views/auth/verify-reset-code.php';
