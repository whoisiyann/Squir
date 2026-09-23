<?php

require_once __DIR__ . '/../models/Folder.php';
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../controllers/NoteController.php';

$userId = requireLogin();
$noteModel = new Note($dbh);
$controller = new NoteController($noteModel, $dbh);
$csrfToken = csrfToken();

// Save note changes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'save') {
    header('Content-Type: application/json');
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }

    $noteId = (int) ($_POST['note_id'] ?? 0);
    $result = $controller->update($noteId, $userId, $_POST);
    if ($result['errors'] !== []) {
        http_response_code(422);
        echo json_encode(['error' => 'Could not save', 'errors' => $result['errors']]);
        exit;
    }

    $saved = $controller->find($noteId, $userId);
    if (!$saved) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }

    echo json_encode([
        'title' => Note::titleOrDefault($saved['title']),
        'excerpt' => Note::excerptOf($saved['content']),
        'updated_at' => date('F j, g:i A', strtotime($saved['updated_at'])),
        'folder_name' => $saved['folder_name'],
    ]);
    exit;
}

// Handle note folder actions
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && in_array($_POST['ajax'] ?? '', ['folder_create', 'folder_rename', 'folder_delete'], true)) {
    header('Content-Type: application/json');
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        echo json_encode(['error' => 'Your session expired. Please refresh the page.']);
        exit;
    }

    $folderModel = new Folder($dbh);
    $ajaxAction = $_POST['ajax'];

    if ($ajaxAction === 'folder_create') {
        $result = $folderModel->create($userId, [
            'folder_name' => trim((string) ($_POST['folder_name'] ?? '')),
            'folder_type' => 'notes',
            'color'       => 'brown',
        ]);
        if ($result['errors'] !== []) {
            http_response_code(422);
            echo json_encode(['error' => reset($result['errors'])]);
            exit;
        }
        echo json_encode(['success' => true, 'folder_id' => $result['folder_id']]);
        exit;
    }

    // rename / delete
    $targetFolder = $folderModel->find((int) ($_POST['folder_id'] ?? 0), $userId);
    if (!$targetFolder || $targetFolder['folder_type'] !== 'notes') {
        http_response_code(404);
        echo json_encode(['error' => 'Folder not found.']);
        exit;
    }

    if ($ajaxAction === 'folder_rename') {
        $result = $folderModel->rename((int) $targetFolder['folder_id'], $userId, (string) ($_POST['folder_name'] ?? ''));
        if ($result['errors'] !== []) {
            http_response_code(422);
            echo json_encode(['error' => reset($result['errors'])]);
            exit;
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // folder_delete 
    $folderModel->delete((int) $targetFolder['folder_id'], $userId);
    echo json_encode(['success' => true]);
    exit;
}

// Toggle note favorite
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'toggle_favorite') {
    header('Content-Type: application/json');
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    $isFavorite = $controller->toggleFavorite((int) ($_POST['note_id'] ?? 0), $userId);
    if ($isFavorite === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }
    echo json_encode(['is_favorite' => $isFavorite]);
    exit;
}

$errors = [];
$flashSuccess = $_SESSION['notes_flash_success'] ?? null;
unset($_SESSION['notes_flash_success']);

// Validate a safe return URL (used when the request came from Favorites)
function notesSafeReturnTo(?string $value): ?string
{
    if (!is_string($value) || $value === '') {
        return null;
    }
    if (preg_match('~^\./(notes|favorites)(\?[A-Za-z0-9=&%._\-]*)?$~', $value)) {
        return $value;
    }
    return null;
}

// Process note form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax'])) {
    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $returnTo = notesSafeReturnTo($_POST['return_to'] ?? null);

        $redirectParams = [];
        if (($_POST['folder'] ?? '') !== '') {
            $redirectParams['folder'] = (int) $_POST['folder'];
        }
        if (($_POST['q'] ?? '') !== '') {
            $redirectParams['q'] = $_POST['q'];
        }

        if ($action === 'create') {
            $result = $controller->store($userId, $_POST);
            if ($result['errors'] === []) {
                $redirectParams['note'] = $result['note_id'];
                $_SESSION['notes_flash_success'] = 'Note saved to your notes.';
                header('Location: ./notes?' . http_build_query($redirectParams));
                exit;
            }
            $errors = $result['errors'];
        } elseif ($action === 'delete') {
            $noteId = (int) ($_POST['note_id'] ?? 0);
            $controller->destroy($noteId, $userId);
            $_SESSION['notes_flash_success'] = 'Note deleted.';
            header('Location: ' . ($returnTo ?? ('./notes?' . http_build_query($redirectParams))));
            exit;
        } elseif ($action === 'move_folder') {

            $noteId = (int) ($_POST['note_id'] ?? 0);
            $targetFolder = ($_POST['target_folder'] ?? '') !== '' ? (int) $_POST['target_folder'] : null;
            $controller->moveToFolder($noteId, $userId, $targetFolder);
            if ($returnTo !== null) {
                header('Location: ' . $returnTo);
                exit;
            }
            $redirectParams['note'] = $noteId;
            header('Location: ./notes?' . http_build_query($redirectParams));
            exit;
        } elseif ($action === 'duplicate') {

            $noteId = (int) ($_POST['note_id'] ?? 0);
            $newNoteId = $controller->duplicate($noteId, $userId);
            if ($newNoteId !== null) {
                $redirectParams['note'] = $newNoteId;
            }
            header('Location: ./notes?' . http_build_query($redirectParams));
            exit;
        }
    }
}

// Load the notes page
$data = $controller->index($userId, $_GET);

$totalNotes = $noteModel->countForUser($userId);

$activeNoteId = isset($_GET['note']) ? (int) $_GET['note'] : null;
$activeNote = $activeNoteId ? $controller->find($activeNoteId, $userId) : null;

$user = currentUserSummary($dbh, $userId);
$initials = $user['initials'];

require __DIR__ . '/../views/notes/index.php';