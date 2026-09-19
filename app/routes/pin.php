<?php

require_once __DIR__ . '/../models/UserPin.php';
require_once __DIR__ . '/../controllers/PinController.php';


$userId = requireLogin(false);

$pinModel = new UserPin($dbh);
$controller = new PinController($pinModel);
$csrfToken = csrfToken();


if ($controller->hasPin($userId)) {
    $_SESSION['has_pin'] = true;
    header('Location: ./dashboard');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        $errors['pin'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $result = $controller->store($userId, $_POST);
        $errors = $result['errors'];

        if ($errors === []) {
            $_SESSION['has_pin'] = true;
            $_SESSION['flash_success'] = 'Your PIN is ready.';
            header('Location: ./dashboard');
            exit;
        }
    }
}

$pinLength = UserPin::length();

require __DIR__ . '/../views/auth/pin.php';