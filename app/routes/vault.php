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

// Check the vault PIN
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

// Log a password copy
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

// Delete the selected vault items
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'bulk_delete') {
    header('Content-Type: application/json');
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        echo json_encode(['error' => 'Your session expired. Please refresh the page and try again.']);
        exit;
    }

    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids) || $ids === []) {
        http_response_code(422);
        echo json_encode(['error' => 'Please select at least one password.']);
        exit;
    }

    $deleted = $controller->destroyMany($ids, $userId);
    if ($deleted === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'We could not delete the selected passwords. Please try again.']);
        exit;
    }

    $_SESSION['vault_flash_success'] = $deleted === 1 ? 'Password deleted.' : $deleted . ' passwords deleted.';
    echo json_encode(['success' => true, 'deleted' => $deleted]);
    exit;
}

$errors = [];
$oldInput = [];
$editFailedId = 0;
$flashSuccess = $_SESSION['vault_flash_success'] ?? null;
unset($_SESSION['vault_flash_success']);
$openModal = null;


// Check the return URL
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

// Handle vault form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $returnTo = vaultSafeReturnTo($_POST['return_to'] ?? null);

    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $submitToken = (string) ($_POST['submit_token'] ?? '');
            $usedTokens = $_SESSION['vault_used_tokens'] ?? [];

            if (!preg_match('/^[a-f0-9]{32}$/', $submitToken)) {
                $errors['form'] = 'Your session expired. Please refresh the page and try again.';
                $openModal = 'create';
            } elseif (in_array($submitToken, $usedTokens, true)) {
                // Duplicate submit of an already saved form
                header('Location: ' . $returnTo);
                exit;
            } else {
                $result = $controller->store($userId, $_POST);
                if ($result['errors'] === []) {
                    $_SESSION['vault_used_tokens'] = array_slice(array_merge($usedTokens, [$submitToken]), -20);
                    $_SESSION['vault_flash_success'] = 'Password saved to your vault.';
                    header('Location: ' . $returnTo);
                    exit;
                }
                $errors = $result['errors'];
                $oldInput = $_POST;
                $openModal = 'create';
            }
        } elseif ($action === 'update') {
            $vaultId = (int) ($_POST['vault_id'] ?? 0);
            $result = $controller->update($vaultId, $userId, $_POST);
            if ($result['errors'] === []) {
                $_SESSION['vault_flash_success'] = 'Password updated.';
                header('Location: ' . $returnTo);
                exit;
            }
            $errors = $result['errors'];
            $oldInput = $_POST;
            $editFailedId = $vaultId;
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
            if ($controller->destroy($vaultId, $userId)) {
                $_SESSION['vault_flash_success'] = 'Password deleted.';
                header('Location: ' . $returnTo);
                exit;
            }
            $errors['form'] = 'We could not delete this password. Please try again.';
        }
    }
}

$data = $controller->index($userId, $_GET);

$editItem = null;
$editTagsRaw = null;
$editPasswordRaw = null;
if (isset($_GET['edit'])) {
    $editItem = $controller->edit((int) $_GET['edit'], $userId);
} elseif ($editFailedId > 0) {
    // Show the edit form again with what the user typed and the error messages
    $editItem = $controller->edit($editFailedId, $userId);
    if ($editItem) {
        foreach (['title', 'account_username', 'website_url', 'notes'] as $field) {
            if (isset($oldInput[$field])) {
                $editItem[$field] = (string) $oldInput[$field];
            }
        }
        if (array_key_exists('folder_id', $oldInput)) {
            $editItem['folder_id'] = $oldInput['folder_id'] === '' ? null : (int) $oldInput['folder_id'];
        }
        $editItem['is_favorite'] = ($oldInput['is_favorite'] ?? '0') === '1';
        $editTagsRaw = (string) ($oldInput['tags'] ?? '');
        $editPasswordRaw = (string) ($oldInput['account_password'] ?? '');
    }
}

$pinLength = UserPin::length();
$pinUnlockSeconds = defined('VAULT_PIN_UNLOCK_SECONDS') ? (int) VAULT_PIN_UNLOCK_SECONDS : 0;


$user = currentUserSummary($dbh, $userId);
$initials = $user['initials'];

require __DIR__ . '/../views/vault/index.php';