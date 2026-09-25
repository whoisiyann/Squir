<?php
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/ActivityLog.php';

class TaskController
{
    private Task $taskModel;
    private ActivityLog $activityLog;

    public function __construct(Task $taskModel, ActivityLog $activityLog)
    {
        $this->taskModel = $taskModel;
        $this->activityLog = $activityLog;
    }

    // Load user tasks
    public function index(int $userId): array
    {
        return $this->taskModel->allForUser($userId);
    }

    // Create a task
    public function store(int $userId, array $post): array
    {
        $result = $this->taskModel->create($userId, $post);
        if ($result['errors'] === [] && $result['task']) {
            $this->activityLog->log($userId, 'task_created', "Created task '" . $this->activityTitle($result['task']['title']) . "'", 'task', (int) $result['task']['task_id']);
        }
        return $result;
    }

    // Update a task
    public function update(int $taskId, int $userId, array $post): array
    {
        $before = $this->taskModel->find($taskId, $userId);
        $result = $this->taskModel->update($taskId, $userId, $post);
        if ($result['errors'] === [] && $result['task']) {
            $action = $this->statusAction($before['status'] ?? null, $result['task']['status']);
            $this->activityLog->log($userId, $action, ucfirst(str_replace('_', ' ', $action)) . " '" . $this->activityTitle($result['task']['title']) . "'", 'task', $taskId);
        }
        return $result;
    }

    // Move a task to another status
    public function move(int $taskId, int $userId, string $status): ?array
    {
        $before = $this->taskModel->find($taskId, $userId);
        $task = $this->taskModel->setStatus($taskId, $userId, $status);
        if ($task && $before && $before['status'] !== $task['status']) {
            $action = $this->statusAction($before['status'], $task['status']);
            $this->activityLog->log($userId, $action, ucfirst(str_replace('_', ' ', $action)) . " '" . $this->activityTitle($task['title']) . "'", 'task', $taskId);
        }
        return $task;
    }

    // Reorder tasks in a status
    public function reorder(int $taskId, int $userId, string $status, string $idsCsv): bool
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', explode(',', $idsCsv)),
            static fn (int $id): bool => $id > 0
        )));

        return $this->taskModel->reorder($taskId, $userId, $status, array_slice($ids, 0, 1000));
    }

    // Duplicate a task
    public function duplicate(int $taskId, int $userId): ?array
    {
        $task = $this->taskModel->duplicate($taskId, $userId);
        if ($task) {
            $this->activityLog->log($userId, 'task_created', "Created task '" . $this->activityTitle($task['title']) . "'", 'task', (int) $task['task_id']);
        }
        return $task;
    }

    // Delete a task
    public function destroy(int $taskId, int $userId): bool
    {
        $existing = $this->taskModel->find($taskId, $userId);
        $deleted = $this->taskModel->delete($taskId, $userId);
        if ($deleted && $existing) {
            $this->activityLog->log($userId, 'task_deleted', "Deleted task '" . $this->activityTitle($existing['title']) . "'", 'task', $taskId);
        }
        return $deleted;
    }

    // Move all tasks between statuses
    public function moveAll(int $userId, string $from, string $to): int
    {
        return $this->taskModel->moveAll($userId, $from, $to);
    }

    // Clear tasks in a status
    public function clearAll(int $userId, string $status): int
    {
        return $this->taskModel->clearAll($userId, $status);
    }

    private function statusAction(?string $before, string $after): string
    {
        if ($before !== 'done' && $after === 'done') {
            return 'task_completed';
        }
        if ($before === 'done' && $after !== 'done') {
            return 'task_reopened';
        }
        return 'task_updated';
    }

    private function activityTitle(string $title): string
    {
        return mb_substr(trim($title) !== '' ? trim($title) : 'Untitled', 0, 140);
    }
}
