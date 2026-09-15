<?php

require_once __DIR__ . '/../models/Vault.php';
require_once __DIR__ . '/../controllers/VaultController.php';

$userId = requireLogin();
$vaultModel = new Vault($dbh);
$controller = new VaultController($vaultModel, $dbh);
$csrfToken = csrfToken();

// ---- AJAX: ibalik ang decrypted password para sa eye/copy button ----
// (hindi ito naka-embed sa HTML, kaya safe kahit view-source ang gawin ng user)
if (isset($_GET['ajax']) && $_GET['ajax'] === 'reveal') {
    header('Content-Type: application/json');
    if (!csrfValid($_GET['token'] ?? null)) {
        http_response_code(403);    
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    $password = $controller->revealPassword((int) ($_GET['id'] ?? 0), $userId);
    if ($password === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }
    echo json_encode(['password' => $password]);
    exit;
}

// ---- AJAX: i-toggle ang favorite ng isang vault item mula mismo sa listahan ----
// (star sa tabi ng item + star sa Actions column, walang kailangang mag-reload)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'toggle_favorite') {
    header('Content-Type: application/json');
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    $isFavorite = $controller->toggleFavorite((int) ($_POST['vault_id'] ?? 0), $userId);
    if ($isFavorite === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }
    echo json_encode(['is_favorite' => $isFavorite]);
    exit;
}

$errors = [];
$flashSuccess = $_SESSION['vault_flash_success'] ?? null;
unset($_SESSION['vault_flash_success']);
$openModal = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $result = $controller->store($userId, $_POST);
            if ($result['errors'] === []) {
                $_SESSION['vault_flash_success'] = 'Password saved to your vault.';
                header('Location: ./vault');
                exit;
            }
            $errors = $result['errors'];
            $openModal = 'create';
        } elseif ($action === 'update') {
            $vaultId = (int) ($_POST['vault_id'] ?? 0);
            $result = $controller->update($vaultId, $userId, $_POST);
            if ($result['errors'] === []) {
                $_SESSION['vault_flash_success'] = 'Password updated.';
                header('Location: ./vault');
                exit;
            }
            $errors = $result['errors'];
            $openModal = 'edit:' . $vaultId;
        } elseif ($action === 'delete') {
            $vaultId = (int) ($_POST['vault_id'] ?? 0);
            $controller->destroy($vaultId, $userId);
            $_SESSION['vault_flash_success'] = 'Password deleted.';
            header('Location: ./vault');
            exit;
        }
    }
}

$data = $controller->index($userId, $_GET);

$editItem = null;
if (isset($_GET['edit'])) {
    $editItem = $controller->edit((int) $_GET['edit'], $userId);
}

// para sa sidebar/header partials (parehong variable names gaya ng dashboard)
$user = currentUserSummary($dbh, $userId);
$initials = $user['initials'];

require __DIR__ . '/../views/vault/index.php';
