<?php

require_once __DIR__ . '/../../../includes/admin_auth.php';
require_once __DIR__ . '/../../models/admin/Admin.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../controllers/admin/AdminDashboardController.php';

$adminId = requireAdminLogin();

$admin = (new Admin($dbh))->findById($adminId);
if (!$admin) {
    // Session check
    session_unset();
    session_destroy();
    header('Location: ' . url('login'));
    exit;
}

// Header search (used on every admin page)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['ajax'] ?? '') === 'search') {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode(['results' => (new AdminDashboardController($dbh))->search((string) ($_GET['q'] ?? ''))]);
    exit;
}

// Users who have not logged in for 30 days become inactive
(new User($dbh))->markInactiveUsers();

$data = (new AdminDashboardController($dbh))->index();
$stats = $data['stats'];
$recentUsers = $data['recentUsers'];
$recentActivity = $data['recentActivity'];
$userReport = $data['userReport'];

require __DIR__ . '/../../views/admin/dashboard.php';
