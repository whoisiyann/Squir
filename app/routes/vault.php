<?php

require_once __DIR__ . '/../models/Vault.php';
require_once __DIR__ . '/../models/UserPin.php';
require_once __DIR__ . '/../controllers/VaultController.php';
require_once __DIR__ . '/../controllers/PinController.php';

$userId = requireLogin();
$vaultModel = new Vault($dbh);
$controller = new VaultController($vaultModel, $dbh);
$pinController = new PinController(new UserPin($dbh));
$csrfToken = csrfToken();

// ---- AJAX: i-check ang PIN bago payagan ang reveal/copy ----
// Verify the vault PIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'verify_pin') {
    header('Content-Type: application/json');
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        http_response_code(403);    
        echo json_encode(['ok' => false, 'error' => 'Your session expired. Please refresh the page.']);
        exit;
    }

    $result = $pinController->verify($userId, (string) ($_POST['pin'] ?? ''));

    if (!empty($result['ok'])) {
        grantPinUnlock();
        echo json_encode(['ok' => true]);
        exit;
    }

    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => $result['error'] ?? 'That PIN is incorrect.']);
    exit;
}

// ---- AJAX: ibalik ang decrypted password para sa eye/copy button ----
// Reveal a vault password
if (isset($_GET['ajax']) && $_GET['ajax'] === 'reveal') {
    header('Content-Type: application/json');
    if (!csrfValid($_GET['token'] ?? null)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }

    if (!pinUnlocked()) {
        http_response_code(423); // Locked
        echo json_encode(['error' => 'pin_required']);
        exit;
    }
    consumePinUnlock();

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
// Toggle vault favorite
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


// Validate a safe return URL
function vaultSafeReturnTo(?string $value): string
{
    $default = './vault';
    if (!is_string($value) || $value === '') {
        return $default;
    }
    if (preg_match('~^\./(vault|folders|dashboard|favorites)(\?[A-Za-z0-9=&%._\-]*)?$~', $value)) {
        return $value;
    }
    return $default;
}

// Process vault form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $returnTo = vaultSafeReturnTo($_POST['return_to'] ?? null);

    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $result = $controller->store($userId, $_POST);
            if ($result['errors'] === []) {
                $_SESSION['vault_flash_success'] = 'Password saved to your vault.';
                header('Location: ' . $returnTo);
                exit;
            }
            $errors = $result['errors'];
            $openModal = 'create';
        } elseif ($action === 'update') {
            $vaultId = (int) ($_POST['vault_id'] ?? 0);
            $result = $controller->update($vaultId, $userId, $_POST);
            if ($result['errors'] === []) {
                $_SESSION['vault_flash_success'] = 'Password updated.';
                header('Location: ' . $returnTo);
                exit;
            }
            $errors = $result['errors'];
            $openModal = 'edit:' . $vaultId;
        } elseif ($action === 'move_folder') {
            $vaultId = (int) ($_POST['vault_id'] ?? 0);
            $destination = $controller->moveToFolder($vaultId, $userId, $_POST['target_folder'] ?? null);
            if ($destination !== null) {
                $_SESSION['vault_flash_success'] = $destination === 'No folder'
                    ? 'Password removed from its folder.'
                    : 'Password moved to "' . $destination . '".';
            }
            header('Location: ' . $returnTo);
            exit;
        } elseif ($action === 'delete') {
            $vaultId = (int) ($_POST['vault_id'] ?? 0);
            $controller->destroy($vaultId, $userId);
            $_SESSION['vault_flash_success'] = 'Password deleted.';
            header('Location: ' . $returnTo);
            exit;
        }
    }
}

$data = $controller->index($userId, $_GET);

$editItem = null;
if (isset($_GET['edit'])) {
    $editItem = $controller->edit((int) $_GET['edit'], $userId);
}

$pinLength = UserPin::length();
$pinUnlockSeconds = defined('VAULT_PIN_UNLOCK_SECONDS') ? (int) VAULT_PIN_UNLOCK_SECONDS : 0;


$user = currentUserSummary($dbh, $userId);
$initials = $user['initials'];

require __DIR__ . '/../views/vault/index.php';