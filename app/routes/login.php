<?php

require_once __DIR__ . '/../controllers/AuthController.php';

$errors = [];
$values = ['email' => ''];
$success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

$csrfToken = csrfToken();

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
        session_regenerate_id(true);
        unset($_SESSION['has_pin']);
        clearPinUnlock();
        $_SESSION['user_id'] = (int) $result['values']['user_id'];
        $_SESSION['user_role'] = $result['values']['role'];
        header('Location: ./dashboard');
        exit;
    }
}

require __DIR__ . '/../views/auth/login.php';
