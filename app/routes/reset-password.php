<?php

require_once __DIR__ . '/../controllers/PasswordResetController.php';

// Require a verified reset code
if (empty($_SESSION['pwreset_email']) || empty($_SESSION['pwreset_verified'])) {
    header('Location: ./forgot-password');
    exit;
}

$email = $_SESSION['pwreset_email'];
$errors = [];
$csrfToken = csrfToken();

// Process the new password submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $controller = new PasswordResetController(new User($dbh), new PasswordReset($dbh));
        $result = $controller->resetPassword($email, $_POST);
        $errors = $result['errors'];

        if ($errors === []) {
            unset(
                $_SESSION['pwreset_email'],
                $_SESSION['pwreset_verified'],
                $_SESSION['pwreset_dev_code'],
                $_SESSION['pwreset_last_sent']
            );
            $_SESSION['flash_success'] = 'Your password has been reset. Please log in with your new password.';
            header('Location: ./login');
            exit;
        }
    }
}

// Render the new-password form
require __DIR__ . '/../views/auth/reset-password.php';
