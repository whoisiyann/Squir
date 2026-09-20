<?php
require_once __DIR__ . '/../models/Task.php';

class TaskController
{
    private Task $taskModel;

    // Initialize task service
    public function __construct(Task $taskModel)
    {
        $this->taskModel = $taskModel;
    }

    // Load user tasks
    public function index(int $userId): array
    {
        return $this->taskModel->allForUser($userId);
    }

    // Create a task
    public function store(int $userId, array $post): array
    {
        return $this->taskModel->create($userId, $post);
    }

    // Update a task
    public function update(int $taskId, int $userId, array $post): array
    {
        return $this->taskModel->update($taskId, $userId, $post);
    }

    // Move a task to another status
    public function move(int $taskId, int $userId, string $status): ?array
    {
        return $this->taskModel->setStatus($taskId, $userId, $status);
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
        return $this->taskModel->duplicate($taskId, $userId);
    }

    // Delete a task
    public function destroy(int $taskId, int $userId): bool
    {
        return $this->taskModel->delete($taskId, $userId);
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
}
