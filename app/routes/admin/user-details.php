<?php

require_once __DIR__ . '/../../../includes/admin_auth.php';
require_once __DIR__ . '/../../models/admin/Admin.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/ActivityLog.php';
require_once __DIR__ . '/../../controllers/admin/AdminUserController.php';

$adminId = requireAdminLogin();

$admin = (new Admin($dbh))->findById($adminId);
if (!$admin) {
    // Session check
    session_unset();
    session_destroy();
    header('Location: ' . url('login'));
    exit;
}

$userId = (int) ($_GET['id'] ?? 0);
$details = (new AdminUserController(new User($dbh), new ActivityLog($dbh)))->show($userId);

    // Redirect unknown users
if ($details === null) {
    header('Location: ' . url('admin/users'));
    exit;
}

$user = $details['user'];
$stats = $details['stats'];
$activity = $details['activity'];
$csrfToken = csrfToken();

require __DIR__ . '/../../views/admin/user-details.php';