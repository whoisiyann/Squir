<!-- Squir/dashboard.php -->

<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ./index.php');
    exit;
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/dbconnect.php';
require_once __DIR__ . '/app/models/Vault.php';
require_once __DIR__ . '/app/controllers/DashboardController.php';

$dashboard = (new DashboardController($dbh))->index((int) $_SESSION['user_id']);
if ($dashboard === []) {
    session_unset();
    session_destroy();
    header('Location: ./index.php');
    exit;
}

require __DIR__ . '/app/views/dashboard/index.php';