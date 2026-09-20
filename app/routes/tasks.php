<?php

require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../controllers/TaskController.php';

$userId = requireLogin();
$controller = new TaskController(new Task($dbh));
$csrfToken = csrfToken();

function tasksRespond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

/** Successful response. */
function tasksOk(TaskController $controller, int $userId, array $extra = []): void
{
    tasksRespond($extra + ['tasks' => $controller->index($userId)]);
}

// ---- AJAX: lahat ng actions ng Kanban board (create, edit, drag, menus, etc.) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        tasksRespond(['error' => 'Your session expired. Please refresh the page.'], 403);
    }

    $taskId = (int) ($_POST['task_id'] ?? 0);

    try {
        switch ($_POST['ajax'] ?? '') {
            case 'create':
                $result = $controller->store($userId, $_POST);
                if ($result['errors'] !== []) {
                    tasksRespond(['error' => reset($result['errors']), 'errors' => $result['errors']], 422);
                }
                tasksOk($controller, $userId, ['task' => $result['task']]);

            case 'update':
                $result = $controller->update($taskId, $userId, $_POST);
                if (!empty($result['not_found'])) {
                    tasksRespond(['error' => 'Task not found.'], 404);
                }
                if ($result['errors'] !== []) {
                    tasksRespond(['error' => reset($result['errors']), 'errors' => $result['errors']], 422);
                }
                tasksOk($controller, $userId, ['task' => $result['task']]);

            case 'move':
                $task = $controller->move($taskId, $userId, (string) ($_POST['status'] ?? ''));
                if ($task === null) {
                    tasksRespond(['error' => 'Could not move the task.'], 404);
                }
                tasksOk($controller, $userId, ['task' => $task]);

            case 'reorder':
                $ok = $controller->reorder(
                    $taskId,
                    $userId,
                    (string) ($_POST['status'] ?? ''),
                    (string) ($_POST['ids'] ?? '')
                );
                if (!$ok) {
                    tasksRespond(['error' => 'Could not move the task.'], 422);
                }
                tasksOk($controller, $userId);

            case 'duplicate':
                $task = $controller->duplicate($taskId, $userId);
                if ($task === null) {
                    tasksRespond(['error' => 'Task not found.'], 404);
                }
                tasksOk($controller, $userId, ['task' => $task]);

            case 'delete':
                if (!$controller->destroy($taskId, $userId)) {
                    tasksRespond(['error' => 'Task not found.'], 404);
                }
                tasksOk($controller, $userId);

            case 'move_all':
                $moved = $controller->moveAll(
                    $userId,
                    (string) ($_POST['from'] ?? ''),
                    (string) ($_POST['to'] ?? '')
                );
                tasksOk($controller, $userId, ['moved' => $moved]);

            case 'clear_all':
                $cleared = $controller->clearAll($userId, (string) ($_POST['status'] ?? ''));
                tasksOk($controller, $userId, ['cleared' => $cleared]);

            default:
                tasksRespond(['error' => 'Unknown action.'], 400);
        }
    } catch (Throwable $e) {
        error_log('[tasks] ' . $e->getMessage());
        tasksRespond(['error' => 'Something went wrong. Please try again.'], 500);
    }
}

$tasks = $controller->index($userId);

$user = currentUserSummary($dbh, $userId);
$initials = $user['initials'];

require __DIR__ . '/../views/tasks/index.php';
