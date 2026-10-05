<?php
require_once __DIR__ . '/../models/Task.php';

class CalendarController
{
    private Task $taskModel;

    public function __construct(Task $taskModel)
    {
        $this->taskModel = $taskModel;
    }

    // Tasks scheduled on the calendar (they have a due date)
    public function index(int $userId): array
    {
        return $this->taskModel->scheduledForUser($userId);
    }
}
