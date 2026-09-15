<?php

require_once __DIR__ . '/../controllers/AuthController.php';

$csrfToken = csrfToken();
$errors = [];
$values = ['fullName' => '', 'username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $result = (new AuthController(new User($dbh)))->register($_POST);
        $errors = $result['errors'];
        $values = array_merge($values, $result['values']);

        if ($errors === []) {
            $_SESSION['flash_success'] = 'Account created successfully. You can now log in.';
            header('Location: ./login');
            exit;
        }
    }
}

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$fieldError = static fn (string $field): string => isset($errors[$field])
    ? '<p class="invalid-feedback-squir" id="' . $field . '-error">' . $escape($errors[$field]) . '</p>'
    : '';

require __DIR__ . '/../views/auth/register.php';
