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

    default:
        http_response_code(404);
        echo '404 — Page not found.';
        break;
}
