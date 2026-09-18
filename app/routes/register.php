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
            // Naka-login na agad ang bagong account, tapos diretso sa PIN setup.
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $result['user_id'];
            $_SESSION['user_role'] = 'user';
            unset($_SESSION['has_pin']);
            clearPinUnlock();

            header('Location: ./pin');
            exit;
        }
    }
}

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$fieldError = static fn (string $field): string => isset($errors[$field])
    ? '<p class="invalid-feedback-squir" id="' . $field . '-error">' . $escape($errors[$field]) . '</p>'
    : '';

require __DIR__ . '/../views/auth/register.php';