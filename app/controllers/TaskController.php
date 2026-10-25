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

    // Tasks
    public function index(int $userId): array
    {
        return $this->taskModel->allForUser($userId);
    }

    // Task
    public function store(int $userId, array $post): array
    {
        $result = $this->taskModel->create($userId, $post);
        if ($result['errors'] === [] && $result['task']) {
            $this->activityLog->log($userId, 'task_created', "Created task '" . $this->activityTitle($result['task']['title']) . "'", 'task', (int) $result['task']['task_id']);
        }
        return $result;
    }

    // Task update
    public function update(int $taskId, int $userId, array $post): array
    {
        $before = $this->taskModel->find($taskId, $userId);
        $result = $this->taskModel->update($taskId, $userId, $post);
        if ($result['errors'] === [] && $result['task']) {
            $action = $this->statusAction($before['status'] ?? null, $result['task']['status']);
            $this->activityLog->log($userId, $action, $this->statusDescription($action, $result['task']), 'task', $taskId);
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
            $this->activityLog->log($userId, $action, $this->statusDescription($action, $task), 'task', $taskId);
        }
        return $task;
    }

    // Reorder tasks in a status
    public function reorder(int $taskId, int $userId, string $status, string $idsCsv): bool
    {
        $before = $this->taskModel->find($taskId, $userId);
        $ids = array_values(array_unique(array_filter(
            array_map('intval', explode(',', $idsCsv)),
            static fn (int $id): bool => $id > 0
        )));

        $reordered = $this->taskModel->reorder($taskId, $userId, $status, array_slice($ids, 0, 1000));
        if ($reordered && $before && $before['status'] !== $status) {
            $task = $this->taskModel->find($taskId, $userId);
            if ($task) {
                $action = $this->statusAction($before['status'], $task['status']);
                $this->activityLog->log($userId, $action, $this->statusDescription($action, $task), 'task', $taskId);
            }
        }

        return $reordered;
    }

    // Move the selected tasks to a status
    public function bulkMove(int $userId, string $status, string $idsCsv, string $orderCsv): int
    {
        $ids = $this->parseIds($idsCsv, 200);
        $order = $this->parseIds($orderCsv, 1000);

        $before = [];
        foreach ($ids as $taskId) {
            $existing = $this->taskModel->find($taskId, $userId);
            if ($existing) {
                $before[$taskId] = $existing['status'];
            }
        }

        $moved = $this->taskModel->bulkMove($userId, $status, $ids, $order);

        $changed = [];
        foreach ($before as $taskId => $previous) {
            $task = $this->taskModel->find($taskId, $userId);
            if ($task && $previous !== $task['status']) {
                $changed[] = ['task' => $task, 'before' => $previous];
            }
        }

        if (count($changed) === 1) {
            $task = $changed[0]['task'];
            $action = $this->statusAction($changed[0]['before'], $task['status']);
            $this->activityLog->log($userId, $action, $this->statusDescription($action, $task), 'task', (int) $task['task_id']);
        } elseif (count($changed) > 1) {
            $action = $this->statusAction($changed[0]['before'], $status);
            $verb = $action === 'task_completed' ? 'Completed' : ($action === 'task_reopened' ? 'Reopened' : 'Moved');
            $titles = array_map(fn (array $c): string => $this->activityTitle($c['task']['title']), $changed);
            $preview = implode(', ', array_slice($titles, 0, 3)) . (count($titles) > 3 ? ', ...' : '');
            $text = $verb . ' ' . count($changed) . ' tasks' . ($action === 'task_moved' ? ' to ' . $this->statusLabel($status) : '');
            $this->activityLog->log($userId, $action, $text . ' (' . mb_substr($preview, 0, 150) . ')', 'task', null);
        }

        return $moved;
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
    public function destroyMany(array $ids, int $userId): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        $ids = array_slice($ids, 0, 200);

        $titles = [];
        $deleted = 0;
        foreach ($ids as $taskId) {
            $existing = $this->taskModel->find($taskId, $userId);
            if (!$existing) {
                continue;
            }
            if ($this->taskModel->delete($taskId, $userId)) {
                $deleted++;
                $titles[] = $this->activityTitle($existing['title']);
            }
        }

        if ($deleted === 1) {
            $this->activityLog->log($userId, 'task_deleted', "Deleted task '" . $titles[0] . "'", 'task', $ids[0] ?? null);
        } elseif ($deleted > 1) {
            $preview = implode(', ', array_slice($titles, 0, 3)) . ($deleted > 3 ? ', ...' : '');
            $this->activityLog->log($userId, 'task_deleted', 'Deleted ' . $deleted . ' tasks (' . mb_substr($preview, 0, 180) . ')', 'task', null);
        }

        return $deleted;
    }

    public function moveAll(int $userId, string $from, string $to): int
    {
        return $this->taskModel->moveAll($userId, $from, $to);
    }

    // Clear tasks in a status
    public function clearAll(int $userId, string $status): int
    {
        return $this->taskModel->clearAll($userId, $status);
    }

    private function parseIds(string $csv, int $limit): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', explode(',', $csv)),
            static fn (int $id): bool => $id > 0
        )));

        return array_slice($ids, 0, $limit);
    }

    private function statusAction(?string $before, string $after): string
    {
        if ($before !== 'done' && $after === 'done') {
            return 'task_completed';
        }
        if ($before === 'done' && $after !== 'done') {
            return 'task_reopened';
        }
        if ($before !== null && $before !== $after) {
            return 'task_moved';
        }
        return 'task_updated';
    }

    private function statusDescription(string $action, array $task): string
    {
        $title = "'" . $this->activityTitle($task['title']) . "'";
        if ($action === 'task_completed') {
            return 'Completed task ' . $title;
        }
        if ($action === 'task_reopened') {
            return 'Reopened task ' . $title;
        }
        if ($action === 'task_moved') {
            return 'Moved task ' . $title . ' to ' . $this->statusLabel($task['status']);
        }
        return 'Updated task ' . $title;
    }

    private function statusLabel(string $status): string
    {
        return [
            'todo' => 'To do',
            'in_progress' => 'In progress',
            'done' => 'Done',
        ][$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    private function activityTitle(string $title): string
    {
        return mb_substr(trim($title) !== '' ? trim($title) : 'Untitled', 0, 140);
    }
}
