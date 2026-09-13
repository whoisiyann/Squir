<?php
session_start();

require_once __DIR__ . '/includes/dbconnect.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/models/Note.php';
require_once __DIR__ . '/app/controllers/NoteController.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ./index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$noteModel = new Note($dbh);
$controller = new NoteController($noteModel, $dbh);

if (empty($_SESSION['notes_csrf_token'])) {
    $_SESSION['notes_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['notes_csrf_token'];

// ---- AJAX: i-save ang title/content/folder habang nagta-type ang user ----
// (debounced sa notes.js, hindi kailangan mag-fully reload ang page)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'save') {
    header('Content-Type: application/json');
    if (!hash_equals($csrfToken, (string) ($_POST['csrf_token'] ?? ''))) {
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
        'title' => $saved['title'],
        'excerpt' => Note::excerptOf($saved['content']),
        'updated_at' => date('F j, g:i A', strtotime($saved['updated_at'])),
        'folder_name' => $saved['folder_name'],
    ]);
    exit;
}

// ---- AJAX: i-toggle ang favorite ng isang note mula sa listahan o editor ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'toggle_favorite') {
    header('Content-Type: application/json');
    if (!hash_equals($csrfToken, (string) ($_POST['csrf_token'] ?? ''))) {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax'])) {
    if (!hash_equals($csrfToken, (string) ($_POST['csrf_token'] ?? ''))) {
        $errors['form'] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

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
                header('Location: ./notes.php?' . http_build_query($redirectParams));
                exit;
            }
            $errors = $result['errors'];
        } elseif ($action === 'delete') {
            $noteId = (int) ($_POST['note_id'] ?? 0);
            $controller->destroy($noteId, $userId);
            header('Location: ./notes.php?' . http_build_query($redirectParams));
            exit;
        }
    }
}

$data = $controller->index($userId, $_GET);

$activeNoteId = isset($_GET['note']) ? (int) $_GET['note'] : null;
$activeNote = $activeNoteId ? $controller->find($activeNoteId, $userId) : null;

// para sa sidebar/header partials (parehong variable names gaya ng dashboard.php / vault.php)
$userStmt = $dbh->prepare('SELECT full_name, username FROM users WHERE user_id = :uid');
$userStmt->execute(['uid' => $userId]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC) ?: ['full_name' => 'User', 'username' => 'user'];
$initials = strtoupper(substr($user['full_name'], 0, 1));

require __DIR__ . '/app/views/notes/index.php';
