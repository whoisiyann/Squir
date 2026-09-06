<?php
session_start();

require_once __DIR__ . '/includes/dbconnect.php';
require_once __DIR__ . '/app/controllers/AuthController.php';

$errors = [];
$values = ['email' => ''];
$success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

if (empty($_SESSION['login_csrf_token'])) {
    $_SESSION['login_csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['login_csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
        $errors['form'] = 'Your session expired. Please refresh the page and try again.';
        $result = ['errors' => $errors, 'values' => ['email' => '']];
    } else {
        $result = (new AuthController(new User($dbh)))->login($_POST);
    }
    $errors = $result['errors'];
    $values = array_merge($values, $result['values']);

    if ($errors === []) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $result['values']['user_id'];
        $_SESSION['user_role'] = $result['values']['role'];
        header('Location: ./dashboard.php');
        exit;
    }
}

require __DIR__ . '/app/views/auth/login.php';














// <?php
// // File path: index.php  (ilagay sa ROOT, papalit sa lumang index.php mo)
// // Kung naka-login (may user_id sa session) -> ipapakita ang dashboard.
// // Kung hindi naka-login -> ipapakita ang login form (kasama ang POST handling).

// session_start();

// require_once __DIR__ . '/includes/dbconnect.php';
// require_once __DIR__ . '/app/controllers/AuthController.php';
// require_once __DIR__ . '/app/controllers/DashboardController.php';

// if (!empty($_SESSION['user_id'])) {
//     $dashboard = (new DashboardController($dbh))->index((int) $_SESSION['user_id']);

//     if ($dashboard === []) {
//         session_unset();
//         session_destroy();
//         header('Location: ./index.php');
//         exit;
//     }

//     require __DIR__ . '/app/views/dashboard/index.php';
//     exit;
// }

// $errors = [];
// $values = ['email' => ''];
// $success = $_SESSION['flash_success'] ?? null;
// unset($_SESSION['flash_success']);

// if (empty($_SESSION['login_csrf_token'])) {
//     $_SESSION['login_csrf_token'] = bin2hex(random_bytes(32));
// }

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     if (!hash_equals($_SESSION['login_csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
//         $errors['form'] = 'Your session expired. Please refresh the page and try again.';
//         $result = ['errors' => $errors, 'values' => ['email' => '']];
//     } else {
//         $result = (new AuthController(new User($dbh)))->login($_POST);
//     }
//     $errors = $result['errors'];
//     $values = array_merge($values, $result['values']);

//     if ($errors === []) {
//         session_regenerate_id(true);
//         $_SESSION['user_id'] = (int) $result['values']['user_id'];
//         $_SESSION['user_role'] = $result['values']['role'];
//         header('Location: ./index.php');
//         exit;
//     }
// }

// require __DIR__ . '/app/views/auth/login.php';