<?php
session_start();

require_once __DIR__ . '/includes/dbconnect.php';
require_once __DIR__ . '/app/controllers/AuthController.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$values = ['fullName' => '', 'username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
        $errors['form'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $result = (new AuthController(new User($dbh)))->register($_POST);
        $errors = $result['errors'];
        $values = array_merge($values, $result['values']);

        if ($errors === []) {
            $_SESSION['flash_success'] = 'Account created successfully. You can now log in.';
            header('Location: index.php');
            exit;
        }
    }
}

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$fieldError = static fn (string $field): string => isset($errors[$field])
    ? '<p class="invalid-feedback-squir" id="' . $field . '-error">' . $escape($errors[$field]) . '</p>'
    : '';

require __DIR__ . '/app/views/auth/register.php';
