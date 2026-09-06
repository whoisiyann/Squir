<!-- Squir/index.php -->

<?php
session_start();

require_once __DIR__ . '/includes/dbconnect.php';
require_once __DIR__ . '/app/controllers/AuthController.php';

$errors = [];
$values = ['email' => ''];
$success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

if (empty($_SESSION['login_csrf_token'])) {
    $_SESSION['login_csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['login_csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
        $errors['form'] = 'Your session expired. Please refresh the page and try again.';
        $result = ['errors' => $errors, 'values' => ['email' => '']];
    } else {
        $result = (new AuthController(new User($dbh)))->login($_POST);
    }
    $errors = $result['errors'];
    $values = array_merge($values, $result['values']);

    if ($errors === []) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $result['values']['user_id'];
        $_SESSION['user_role'] = $result['values']['role'];
        header('Location: ./dashboard.php');
        exit;
    }
}

require __DIR__ . '/app/views/auth/login.php';


