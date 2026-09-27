<?php

require_once __DIR__ . '/../../../includes/admin_auth.php';
require_once __DIR__ . '/../../models/admin/Admin.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/ActivityLog.php';
require_once __DIR__ . '/../../controllers/admin/AdminUserController.php';

$adminId = requireAdminLogin();

$admin = (new Admin($dbh))->findById($adminId);
if (!$admin) {
    // Admin account was deleted while the session was still active.
    session_unset();
    session_destroy();
    header('Location: ' . url('login'));
    exit;
}

$userModel = new User($dbh);
$activityLog = new ActivityLog($dbh);
$controller = new AdminUserController($userModel);

// Send a JSON response and stop
function adminUsersJson(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['ajax'] ?? '');

    if ($action === '') {
        adminUsersJson(['error' => 'Unknown request.'], 400);
    }

    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        adminUsersJson(['error' => 'Your session expired. Please refresh the page and try again.'], 403);
    }

    if ($action === 'add_user') {
        $result = $controller->create($_POST);
        if ($result['errors'] !== []) {
            adminUsersJson(['error' => reset($result['errors']), 'errors' => $result['errors']], 422);
        }

        $activityLog->logAdmin($adminId, 'user_created', 'Added ' . ($result['values']['fullName'] ?? 'a new user'));
        adminUsersJson(['success' => true]);
    }

    if ($action === 'update_user') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $result = $controller->update($userId, $_POST);
        if ($result['errors'] !== []) {
            adminUsersJson(['error' => reset($result['errors']), 'errors' => $result['errors']], 422);
        }

        $activityLog->logAdmin($adminId, 'user_updated', 'Updated ' . ($result['values']['fullName'] ?? 'a user account'));
        adminUsersJson(['success' => true]);
    }

    if ($action === 'update_status') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        $result = $controller->updateStatus($userId, $status);
        if ($result['errors'] !== []) {
            adminUsersJson(['error' => reset($result['errors'])], 422);
        }

        $actionCode = $status === 'suspended' ? 'user_suspended' : ($status === 'active' ? 'user_activated' : 'user_deactivated');
        $activityLog->logAdmin($adminId, $actionCode, ucfirst($status) . ' ' . $result['user']['full_name']);
        adminUsersJson(['success' => true]);
    }

    if ($action === 'delete_user') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $result = $controller->delete($userId);
        if ($result['errors'] !== []) {
            adminUsersJson(['error' => reset($result['errors'])], 422);
        }

        $activityLog->logAdmin($adminId, 'user_deleted', 'Deleted ' . $result['user']['full_name']);
        adminUsersJson(['success' => true]);
    }

    adminUsersJson(['error' => 'Unknown request.'], 400);
}

$data = $controller->index();
$stats = $data['stats'];
$users = $data['users'];
$csrfToken = csrfToken();

require __DIR__ . '/../../views/admin/users.php';
