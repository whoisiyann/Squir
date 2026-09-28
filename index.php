<?php

require_once __DIR__ . '/includes/bootstrap.php';

$route = trim((string) ($_GET['route'] ?? ''), '/');
if ($route === '') {
    $route = 'login';
}

switch ($route) {
    case 'login':
        require __DIR__ . '/app/routes/login.php';
        break;

    case 'register':
        require __DIR__ . '/app/routes/register.php';
        break;

    case 'logout':
        require __DIR__ . '/app/routes/logout.php';
        break;

    case 'forgot-password':
        require __DIR__ . '/app/routes/forgot-password.php';
        break;

    case 'verify-reset-code':
        require __DIR__ . '/app/routes/verify-reset-code.php';
        break;

    case 'reset-password':
        require __DIR__ . '/app/routes/reset-password.php';
        break;

    case 'forgot-pin':
        require __DIR__ . '/app/routes/forgot-pin.php';
        break;

    case 'verify-pin-code':
        require __DIR__ . '/app/routes/verify-pin-code.php';
        break;

    case 'reset-pin-new':
        require __DIR__ . '/app/routes/reset-pin-new.php';
        break;

    case 'pin':
        require __DIR__ . '/app/routes/pin.php';
        break;

    case 'dashboard':
        require __DIR__ . '/app/routes/dashboard.php';
        break;

    case 'vault':
        require __DIR__ . '/app/routes/vault.php';
        break;

    case 'notes':
        require __DIR__ . '/app/routes/notes.php';
        break;

    case 'tasks':
        require __DIR__ . '/app/routes/tasks.php';
        break;

    case 'folders':
        require __DIR__ . '/app/routes/folders.php';
        break;

    case 'favorites':
        require __DIR__ . '/app/routes/favorites.php';
        break;

    case 'settings':
        require __DIR__ . '/app/routes/settings.php';
        break;

    // Support both admin route formats.
    case 'admin-logout':
    case 'admin/logout':
        require __DIR__ . '/app/routes/admin/logout.php';
        break;

    case 'admin-dashboard':
    case 'admin/dashboard':
        require __DIR__ . '/app/routes/admin/dashboard.php';
        break;

    case 'admin/users':
        require __DIR__ . '/app/routes/admin/users.php';
        break;

    case 'admin/user-details':
        require __DIR__ . '/app/routes/admin/user-details.php';
        break;

    case 'admin/activity-logs':
        require __DIR__ . '/app/routes/admin/activity-logs.php';
        break;

    default:
        http_response_code(404);
        echo '404 — Page not found.';
        break;
}