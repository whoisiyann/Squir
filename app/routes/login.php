<?php

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/admin/AdminAuthController.php';
require_once __DIR__ . '/../models/ActivityLog.php';

$errors = [];
$values = ['email' => ''];
$success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

$csrfToken = csrfToken();

// Login submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please refresh the page and try again.';
        $result = ['errors' => $errors, 'values' => ['email' => '']];
    } else {
        $result = (new AuthController(new User($dbh)))->login($_POST);
    }
    $errors = $result['errors'];
    $values = array_merge($values, $result['values']);

    if ($errors === []) {
        // Matched a regular user account.
        (new User($dbh))->touchLastLogin((int) $result['values']['user_id']);
        (new ActivityLog($dbh))->log((int) $result['values']['user_id'], 'logged_in');
        session_regenerate_id(true);
        unset($_SESSION['has_pin']);
        clearPinUnlock();
        $_SESSION['user_id'] = (int) $result['values']['user_id'];
        $_SESSION['login_at'] = time();
        header('Location: ./dashboard');
        exit;
    }

    // Admin login
    if (isset($errors['form']) && $errors['form'] === 'The email or password is incorrect.') {
        $adminResult = (new AdminAuthController(new Admin($dbh)))->login($_POST);

        if ($adminResult['errors'] === []) {
            $admin = $adminResult['values'];
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['admin_id'];
            $_SESSION['admin_name'] = $admin['full_name'];

            (new Admin($dbh))->touchLastLogin((int) $admin['admin_id']);
            (new ActivityLog($dbh))->logAdmin((int) $admin['admin_id'], 'admin_logged_in');

            header('Location: ./admin-dashboard');
            exit;
        }

        // Show admin login error
        if (isset($adminResult['errors']['form']) && $adminResult['errors']['form'] !== 'The email or password is incorrect.') {
            $errors = $adminResult['errors'];
        }
    }
}

// Login form
require __DIR__ . '/../views/auth/login.php';