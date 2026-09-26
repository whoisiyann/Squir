<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/UserPin.php';
require_once __DIR__ . '/../models/PinReset.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../controllers/PinResetController.php';

$userId = requireLogin();

// Require a verified reset code
if (empty($_SESSION['pinreset_email']) || empty($_SESSION['pinreset_verified'])) {
    header('Location: ./forgot-pin');
    exit;
}

$errors = [];
$csrfToken = csrfToken();
$pinLength = UserPin::length();

// Process the new PIN submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $controller = new PinResetController(new User($dbh), new PinReset($dbh), new ActivityLog($dbh), new UserPin($dbh));
        $result = $controller->applyNewPin($userId, $_POST);
        $errors = $result['errors'];

        if ($errors === []) {
            $backUrl = ($_SESSION['pinreset_from'] ?? '') === 'settings' ? './settings?panel=reset-pin' : './settings';

            unset(
                $_SESSION['pinreset_email'],
                $_SESSION['pinreset_verified'],
                $_SESSION['pinreset_dev_code'],
                $_SESSION['pinreset_last_sent'],
                $_SESSION['pinreset_from']
            );

            header('Location: ' . $backUrl . (str_contains($backUrl, '?') ? '&' : '?') . 'pin_reset=1');
            exit;
        }
    }
}

// Render the new-PIN form
require __DIR__ . '/../views/settings/reset-pin-new.php';
