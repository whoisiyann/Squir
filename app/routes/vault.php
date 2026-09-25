<?php

require_once __DIR__ . '/../models/Vault.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../models/UserPin.php';
require_once __DIR__ . '/../controllers/VaultController.php';
require_once __DIR__ . '/../controllers/PinController.php';

$userId = requireLogin();
$vaultModel = new Vault($dbh);
$activityLog = new ActivityLog($dbh);
$controller = new VaultController($vaultModel, $dbh, $activityLog);
$pinController = new PinController(new UserPin($dbh));
$csrfToken = csrfToken();

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

// Reveal the decrypted password
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

    $vaultId = (int) ($_GET['id'] ?? 0);
    $item = $vaultModel->find($vaultId, $userId);
    $password = $controller->revealPassword($vaultId, $userId);
    if ($password === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }
    $action = ($_GET['reason'] ?? 'view') === 'copy' ? 'password_copied' : 'password_viewed';
    $activityLog->log($userId, $action, ucfirst(str_replace('_', ' ', $action)) . " '" . mb_substr(trim((string) $item['title']) ?: 'Untitled', 0, 140) . "'", 'vault', $vaultId);
    echo json_encode(['password' => $password]);
    exit;
}

// Record a successful copy when the password was already visible in the page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'log_password_copy') {
    header('Content-Type: application/json');
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }

    $vaultId = (int) ($_POST['vault_id'] ?? 0);
    $item = $vaultModel->find($vaultId, $userId);
    if (!$item) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }

    $activityLog->log($userId, 'password_copied', "Copied password '" . mb_substr(trim((string) $item['title']) ?: 'Untitled', 0, 140) . "'", 'vault', $vaultId);
    echo json_encode(['success' => true]);
    exit;
}

// Toggle a vault favorite
// Toggle vault favorite
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'toggle_favorite') {
    header('Content-Type: application/json');
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    $vaultId = (int) ($_POST['vault_id'] ?? 0);
    $item = $vaultModel->find($vaultId, $userId);
    $isFavorite = $controller->toggleFavorite($vaultId, $userId);
    if ($isFavorite === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }
    $action = $isFavorite ? 'favorite_added' : 'favorite_removed';
    $verb = $isFavorite ? 'Added' : 'Removed';
    (new ActivityLog($dbh))->log($userId, $action, $verb . " '" . mb_substr(trim((string) $item['title']) ?: 'Untitled', 0, 140) . "' " . ($isFavorite ? 'to' : 'from') . ' favorites', 'favorite', $vaultId);
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