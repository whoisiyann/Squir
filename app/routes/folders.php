<?php

require_once __DIR__ . '/../models/Folder.php';
require_once __DIR__ . '/../controllers/FolderController.php';

$userId = requireLogin();
$folderModel = new Folder($dbh);
$controller = new FolderController($folderModel, $dbh);
$csrfToken = csrfToken();

// ---- AJAX: palitan ang kulay ng folder (single click sa grid / double-click sa list) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'update_color') {
    header('Content-Type: application/json');
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    $ok = $controller->updateColor((int) ($_POST['folder_id'] ?? 0), $userId, (string) ($_POST['color'] ?? ''));
    if (!$ok) {
        http_response_code(422);
        echo json_encode(['error' => 'Could not update color']);
        exit;
    }
    echo json_encode(['success' => true, 'color' => $_POST['color']]);
    exit;
}

// ---- AJAX: i-toggle ang favorite ng folder ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'toggle_favorite') {
    header('Content-Type: application/json');
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    $isFavorite = $controller->toggleFavorite((int) ($_POST['folder_id'] ?? 0), $userId);
    if ($isFavorite === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }
    echo json_encode(['is_favorite' => $isFavorite]);
    exit;
}

// ---- AJAX: rename folder (inline, mula sa 3-dot menu) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'rename') {
    header('Content-Type: application/json');
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    $result = $controller->rename((int) ($_POST['folder_id'] ?? 0), $userId, (string) ($_POST['folder_name'] ?? ''));
    if ($result['errors'] !== []) {
        http_response_code(422);
        echo json_encode(['error' => reset($result['errors'])]);
        exit;
    }
    echo json_encode(['success' => true]);
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax'])) {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $result = $controller->store($userId, $_POST);
            if ($result['errors'] === []) {
                header('Location: ./folders');
                exit;
            }
            $errors = $result['errors'];
        } elseif ($action === 'delete') {
            $controller->destroy((int) ($_POST['folder_id'] ?? 0), $userId);
            header('Location: ./folders');
            exit;
        }
    }
}

$data = $controller->index($userId, $_GET);

$user = currentUserSummary($dbh, $userId);
$initials = $user['initials'];

require __DIR__ . '/../views/folders/index.php';
