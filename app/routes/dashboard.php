<?php

require_once __DIR__ . '/../models/Vault.php';
require_once __DIR__ . '/../controllers/DashboardController.php';

$userId = requireLogin();

$dashboard = (new DashboardController($dbh))->index($userId);
if ($dashboard === []) {
    session_unset();
    session_destroy();
    header('Location: ./login');
    exit;
}

require __DIR__ . '/../views/dashboard/index.php';
