<?php

require_once __DIR__ . '/../models/Vault.php';
require_once __DIR__ . '/../models/WorldClock.php';
require_once __DIR__ . '/../controllers/DashboardController.php';

$userId = requireLogin();
$csrfToken = csrfToken();
$controller = new DashboardController($dbh);
$clockModel = new WorldClock($dbh);

// Send a dashboard JSON response
function dashboardJson(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

// Handle dashboard search
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['ajax'] ?? '') === 'search') {
    dashboardJson(['results' => $controller->search($userId, (string) ($_GET['q'] ?? ''))]);
}

// ---- AJAX: city list for the "Add World Clock City" popup ----
// Load world clock catalog
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['ajax'] ?? '') === 'world_clock_catalog') {
    dashboardJson(['cities' => $clockModel->catalog()]);
}

// ---- AJAX: add / remove a world clock ----
// Handle world clock updates
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && in_array($_POST['ajax'] ?? '', ['world_clock_add', 'world_clock_remove'], true)) {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        dashboardJson(['error' => 'Your session expired. Please refresh the page.'], 403);
    }

    $cityId = (int) ($_POST['city_id'] ?? 0);

    if ($_POST['ajax'] === 'world_clock_add') {
        $error = $clockModel->add($userId, $cityId);
        if ($error !== null) {
            dashboardJson(['error' => $error], 422);
        }
    } else {
        $clockModel->remove($userId, $cityId);
    }

    dashboardJson(['clocks' => $clockModel->forUser($userId)]);
}

// Load the dashboard view
$dashboard = $controller->index($userId);
if ($dashboard === []) {
    session_unset();
    session_destroy();
    header('Location: ./login');
    exit;
}

// "Password saved to your vault." after adding a credential from the dashboard popup
$flashSuccess = $_SESSION['vault_flash_success'] ?? null;
unset($_SESSION['vault_flash_success']);

$errors = [];
$returnTo = './dashboard';

require __DIR__ . '/../views/dashboard/index.php';
