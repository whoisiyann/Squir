<?php

require_once __DIR__ . '/../../../includes/admin_auth.php';
require_once __DIR__ . '/../../models/admin/Admin.php';
require_once __DIR__ . '/../../controllers/admin/AdminDashboardController.php';

$adminId = requireAdminLogin();

$admin = (new Admin($dbh))->findById($adminId);
if (!$admin) {
    // Handle deleted admin session
    session_unset();
    session_destroy();
    header('Location: ' . url('login'));
    exit;
}

$data = (new AdminDashboardController($dbh))->index();
$stats = $data['stats'];
$recentUsers = $data['recentUsers'];
$recentActivity = $data['recentActivity'];

require __DIR__ . '/../../views/admin/dashboard.php';
