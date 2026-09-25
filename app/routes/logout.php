<?php

require_once __DIR__ . '/../models/ActivityLog.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId > 0) {
    (new ActivityLog($dbh))->log($userId, 'logged_out');
}

$_SESSION = [];
// Clear the authenticated session
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

// Return to login
header('Location: ./login');
exit;
