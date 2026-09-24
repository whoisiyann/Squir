<?php

require_once __DIR__ . '/../models/Folder.php';
require_once __DIR__ . '/../controllers/FolderController.php';

$userId = requireLogin();
$folderModel = new Folder($dbh);
$controller = new FolderController($folderModel, $dbh);
$csrfToken = csrfToken();

// Validate the return URL
function foldersSafeReturnTo(?string $value): ?string
{
    if (!is_string($value) || $value === '') {
        return null;
    }
    if (preg_match('~^\./(folders|favorites)(\?[A-Za-z0-9=&%._\-]*)?$~', $value)) {
        return $value;
    }
    return null;
}


// Update folder color
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

// Toggle folder favorite
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

// Rename a folder
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

// Show folder details
$openFolderId = isset($_GET['folder']) && ctype_digit((string) $_GET['folder']) ? (int) $_GET['folder'] : null;

// Load an open folder
if ($openFolderId !== null) {
    $folder = $folderModel->find($openFolderId, $userId);
    if (!$folder) {
        header('Location: ./folders');
        exit;
    }

    // Load notes in the folder
    if ($folder['folder_type'] === 'notes') {
        require_once __DIR__ . '/../models/Note.php';
        require_once __DIR__ . '/../controllers/NoteController.php';

        $noteController = new NoteController(new Note($dbh), $dbh);
        $notesData = $noteController->index($userId, ['folder' => $openFolderId]);

        $user = currentUserSummary($dbh, $userId);
        $initials = $user['initials'];

        require __DIR__ . '/../views/folders/show-notes.php';
        exit;
    }

    require_once __DIR__ . '/../models/Vault.php';
    require_once __DIR__ . '/../models/UserPin.php';
    require_once __DIR__ . '/../controllers/VaultController.php';

    $vaultModel = new Vault($dbh);
    $vaultController = new VaultController($vaultModel, $dbh);

    $returnTo = './folders?folder=' . $openFolderId;
    $closeUrl = $returnTo;

    $flashSuccess = $_SESSION['vault_flash_success'] ?? null;
    unset($_SESSION['vault_flash_success']);
    $openModal = null;

    $data = $vaultController->index($userId, ['folder' => $openFolderId] + $_GET);

    $editItem = null;
    if (isset($_GET['edit'])) {
        $editItem = $vaultController->edit((int) $_GET['edit'], $userId);
    }

    $pinLength = UserPin::length();
    $pinUnlockSeconds = defined('VAULT_PIN_UNLOCK_SECONDS') ? (int) VAULT_PIN_UNLOCK_SECONDS : 0;

    $user = currentUserSummary($dbh, $userId);
    $initials = $user['initials'];

    require __DIR__ . '/../views/folders/show.php';
    exit;
}

// Process folder form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax'])) {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $result = $controller->store($userId, $_POST);
            if ($result['errors'] === []) {
                $redirectType = ($_POST['folder_type'] ?? 'passwords') === 'notes' ? 'notes' : 'passwords';
                header('Location: ./folders?type=' . $redirectType);
                exit;
            }
            $errors = $result['errors'];
        } elseif ($action === 'delete') {
            $deleteId = (int) ($_POST['folder_id'] ?? 0);
            $folderToDelete = $folderModel->find($deleteId, $userId);
            $controller->destroy($deleteId, $userId);

            $returnTo = foldersSafeReturnTo($_POST['return_to'] ?? null);
            if ($returnTo !== null) {
                header('Location: ' . $returnTo);
                exit;
            }

            $backType = ($folderToDelete && $folderToDelete['folder_type'] === 'notes') ? '?type=notes' : '';
            header('Location: ./folders' . $backType);
            exit;
        }
    }
}

// Load the folder list
$data = $controller->index($userId, $_GET);

$user = currentUserSummary($dbh, $userId);
$initials = $user['initials'];

require __DIR__ . '/../views/folders/index.php';
