<?php
require_once __DIR__ . '/../models/Task.php';

class TaskController
{
    private Task $taskModel;

    public function __construct(Task $taskModel)
    {
        $this->taskModel = $taskModel;
    }

    public function index(int $userId): array
    {
        return $this->taskModel->allForUser($userId);
    }

    public function store(int $userId, array $post): array
    {
        return $this->taskModel->create($userId, $post);
    }

    public function update(int $taskId, int $userId, array $post): array
    {
        return $this->taskModel->update($taskId, $userId, $post);
    }

    public function move(int $taskId, int $userId, string $status): ?array
    {
        return $this->taskModel->setStatus($taskId, $userId, $status);
    }

    public function reorder(int $taskId, int $userId, string $status, string $idsCsv): bool
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', explode(',', $idsCsv)),
            static fn (int $id): bool => $id > 0
        )));

        return $this->taskModel->reorder($taskId, $userId, $status, array_slice($ids, 0, 1000));
    }

    public function duplicate(int $taskId, int $userId): ?array
    {
        return $this->taskModel->duplicate($taskId, $userId);
    }

    public function destroy(int $taskId, int $userId): bool
    {
        return $this->taskModel->delete($taskId, $userId);
    }

    public function moveAll(int $userId, string $from, string $to): int
    {
        return $this->taskModel->moveAll($userId, $from, $to);
    }

    public function clearAll(int $userId, string $status): int
    {
        return $this->taskModel->clearAll($userId, $status);
    }
}
