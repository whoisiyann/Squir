<?php

require_once __DIR__ . '/../../../includes/admin_auth.php';
require_once __DIR__ . '/../../models/ActivityLog.php';

if (!empty($_SESSION['admin_id'])) {
    (new ActivityLog($dbh))->logAdmin((int) $_SESSION['admin_id'], 'admin_logged_out');
}

unset($_SESSION['admin_id'], $_SESSION['admin_name']);
session_regenerate_id(true);

header('Location: ' . url('login'));
exit;
