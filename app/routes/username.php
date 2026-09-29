<?php

require_once __DIR__ . '/../controllers/AuthController.php';

// PIN is required, username is what this page collects
$userId = requireLogin(true, false);

$userModel = new User($dbh);
$current = $userModel->findById($userId);

// Skip this step once a username exists
if ($current && trim((string) $current['username']) !== '') {
    header('Location: ./dashboard');
    exit;
}

$csrfToken = csrfToken();
$errors = [];
$values = ['username' => ''];

// Username submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $result = (new AuthController($userModel))->setUsername($userId, $_POST);
        $errors = $result['errors'];
        $values = array_merge($values, $result['values']);

        if ($errors === []) {
            header('Location: ./dashboard');
            exit;
        }
    }
}

$escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

// Username form
require __DIR__ . '/../views/auth/username.php';
