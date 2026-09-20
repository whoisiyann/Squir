<?php

class Task
{
    public const STATUSES = ['todo', 'in_progress', 'done'];
    public const PRIORITIES = ['low', 'medium', 'high'];
    public const TITLE_MAX = 100;
    public const DESCRIPTION_MAX = 5000;

    private const PRIORITY_RANK = ['high' => 0, 'medium' => 1, 'low' => 2];
    private const COLUMNS = 'task_id, title, description, status, priority, due_date, position, completed_at, created_at';

    private PDO $dbh;

    // Initialize task data access
    public function __construct(PDO $dbh)
    {
        $this->dbh = $dbh;
    }


    // Fetch user tasks
    public function allForUser(int $userId): array
    {

        foreach (self::STATUSES as $status) {
            $this->columnRows($userId, $status);
        }

        $stmt = $this->dbh->prepare(
            'SELECT ' . self::COLUMNS . '
             FROM tasks
             WHERE user_id = :uid
             ORDER BY position ASC, task_id ASC'
        );
        $stmt->execute(['uid' => $userId]);

        return array_map([$this, 'normalize'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // Fetch a user task
    public function find(int $taskId, int $userId): ?array
    {
        $stmt = $this->dbh->prepare(
            'SELECT ' . self::COLUMNS . ' FROM tasks WHERE task_id = :id AND user_id = :uid'
        );
        $stmt->execute(['id' => $taskId, 'uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->normalize($row) : null;
    }

    /** Validate and normalize task input. */
    // Validate and normalize task input
    public function validate(array $input): array
    {
        $errors = [];

        $title = trim((string) ($input['title'] ?? ''));
        if ($title === '') {
            $errors['title'] = 'Task title is required.';
        } elseif (mb_strlen($title) > self::TITLE_MAX) {
            $errors['title'] = 'Task title must be ' . self::TITLE_MAX . ' characters or less.';
        }

        $description = trim((string) ($input['description'] ?? ''));
        if (mb_strlen($description) > self::DESCRIPTION_MAX) {
            $errors['description'] = 'Description is too long.';
        }

        $priority = (string) ($input['priority'] ?? 'medium');
        if (!in_array($priority, self::PRIORITIES, true)) {
            $errors['priority'] = 'Please choose a valid priority.';
        }

        $status = (string) ($input['status'] ?? 'todo');
        if (!in_array($status, self::STATUSES, true)) {
            $errors['status'] = 'Please choose a valid status.';
        }

        $dueDate = null;
        $due = trim((string) ($input['due_date'] ?? ''));
        if ($due !== '') {
            $parsed = DateTime::createFromFormat('!Y-m-d', $due);
            if (!$parsed || $parsed->format('Y-m-d') !== $due) {
                $errors['due_date'] = 'Please enter a valid due date.';
            } else {
                $dueDate = $due;
            }
        }

        return [$errors, [
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'status' => $status,
            'due_date' => $dueDate,
        ]];
    }


    // Create a task
    public function create(int $userId, array $input): array
    {
        [$errors, $clean] = $this->validate($input);
        if ($errors !== []) {
            return ['errors' => $errors, 'task' => null];
        }

        $taskId = $this->transactional(function () use ($userId, $clean): int {
            $stmt = $this->dbh->prepare(
                'INSERT INTO tasks (user_id, title, description, status, priority, due_date, completed_at)
                 VALUES (:uid, :title, :description, :status, :priority, :due, IF(:done = 1, NOW(), NULL))'
            );
            $stmt->execute([
                'uid' => $userId,
                'title' => $clean['title'],
                'description' => $clean['description'] !== '' ? $clean['description'] : null,
                'status' => $clean['status'],
                'priority' => $clean['priority'],
                'due' => $clean['due_date'],
                'done' => $clean['status'] === 'done' ? 1 : 0,
            ]);

            $id = (int) $this->dbh->lastInsertId();
            $this->placeAuto($userId, $id);

            return $id;
        });

        return ['errors' => [], 'task' => $this->find($taskId, $userId)];
    }


    // Update a task
    public function update(int $taskId, int $userId, array $input): array
    {
        $before = $this->find($taskId, $userId);
        if (!$before) {
            return ['errors' => ['form' => 'Task not found.'], 'task' => null, 'not_found' => true];
        }

        [$errors, $clean] = $this->validate($input);
        if ($errors !== []) {
            return ['errors' => $errors, 'task' => null];
        }

        $this->transactional(function () use ($taskId, $userId, $before, $clean): void {
            $stmt = $this->dbh->prepare(
                'UPDATE tasks
                 SET title = :title,
                     description = :description,
                     status = :status,
                     priority = :priority,
                     due_date = :due,
                     completed_at = IF(:done = 1, COALESCE(completed_at, NOW()), NULL)
                 WHERE task_id = :id AND user_id = :uid'
            );
            $stmt->execute([
                'title' => $clean['title'],
                'description' => $clean['description'] !== '' ? $clean['description'] : null,
                'status' => $clean['status'],
                'priority' => $clean['priority'],
                'due' => $clean['due_date'],
                'done' => $clean['status'] === 'done' ? 1 : 0,
                'id' => $taskId,
                'uid' => $userId,
            ]);

            $sortingChanged = $before['status'] !== $clean['status']
                || $before['priority'] !== $clean['priority']
                || $before['due_date'] !== $clean['due_date'];

            if ($sortingChanged) {
                $this->placeAuto($userId, $taskId);
            }
        });

        return ['errors' => [], 'task' => $this->find($taskId, $userId)];
    }

    /** Move a task to another status. */
    // Update task status
    public function setStatus(int $taskId, int $userId, string $status): ?array
    {
        $before = $this->find($taskId, $userId);
        if (!$before || !in_array($status, self::STATUSES, true)) {
            return null;
        }

        $this->transactional(function () use ($taskId, $userId, $status, $before): void {
            $this->applyStatus($taskId, $userId, $status);

            if ($before['status'] !== $status) {
                $this->placeAuto($userId, $taskId);
            }
        });

        return $this->find($taskId, $userId);
    }


    // Reorder tasks in a status
    public function reorder(int $taskId, int $userId, string $status, array $ids): bool
    {
        if (!in_array($status, self::STATUSES, true) || !$this->find($taskId, $userId)) {
            return false;
        }

        $this->transactional(function () use ($taskId, $userId, $status, $ids): void {
            $this->applyStatus($taskId, $userId, $status);

            $rows = $this->columnRows($userId, $status);
            $inColumn = [];
            foreach ($rows as $row) {
                $inColumn[(int) $row['task_id']] = true;
            }

            $ordered = [];
            foreach ($ids as $id) {
                $id = (int) $id;
                if (isset($inColumn[$id])) {
                    $ordered[] = $id;
                    unset($inColumn[$id]);
                }
            }

            foreach ($rows as $row) {
                $id = (int) $row['task_id'];
                if (isset($inColumn[$id])) {
                    $ordered[] = $id;
                }
            }

            $this->writePositions($userId, $ordered);
        });

        return true;
    }

    // Duplicate a task
    public function duplicate(int $taskId, int $userId): ?array
    {
        $original = $this->find($taskId, $userId);
        if (!$original) {
            return null;
        }

        $suffix = ' (copy)';
        $title = $original['title'];
        if (mb_strlen($title) + mb_strlen($suffix) > self::TITLE_MAX) {
            $title = mb_substr($title, 0, self::TITLE_MAX - mb_strlen($suffix));
        }

        $newId = $this->transactional(function () use ($userId, $taskId, $original, $title, $suffix): int {
            $stmt = $this->dbh->prepare(
                'INSERT INTO tasks (user_id, title, description, status, priority, due_date, completed_at)
                 VALUES (:uid, :title, :description, :status, :priority, :due, IF(:done = 1, NOW(), NULL))'
            );
            $stmt->execute([
                'uid' => $userId,
                'title' => $title . $suffix,
                'description' => $original['description'] !== '' ? $original['description'] : null,
                'status' => $original['status'],
                'priority' => $original['priority'],
                'due' => $original['due_date'],
                'done' => $original['status'] === 'done' ? 1 : 0,
            ]);
            $id = (int) $this->dbh->lastInsertId();

            $ids = array_map('intval', array_column($this->columnRows($userId, $original['status'], $id), 'task_id'));
            $at = array_search($taskId, $ids, true);
            array_splice($ids, $at === false ? count($ids) : $at + 1, 0, [$id]);
            $this->writePositions($userId, $ids);

            return $id;
        });

        return $this->find($newId, $userId);
    }

    // Delete a task
    public function delete(int $taskId, int $userId): bool
    {
        $stmt = $this->dbh->prepare('DELETE FROM tasks WHERE task_id = :id AND user_id = :uid');
        $stmt->execute(['id' => $taskId, 'uid' => $userId]);

        return $stmt->rowCount() > 0;
    }

    // Move all tasks between statuses
    public function moveAll(int $userId, string $from, string $to): int
    {
        if ($from === $to || !in_array($from, self::STATUSES, true) || !in_array($to, self::STATUSES, true)) {
            return 0;
        }

        return $this->transactional(function () use ($userId, $from, $to): int {
            $moving = $this->columnRows($userId, $from);
            if ($moving === []) {
                return 0;
            }

            $destination = $this->columnRows($userId, $to);
            foreach ($moving as $row) {
                $destination = $this->insertSorted($destination, $row);
            }

            $stmt = $this->dbh->prepare(
                'UPDATE tasks
                 SET status = :dest,
                     completed_at = IF(:done = 1, NOW(), NULL)
                 WHERE user_id = :uid AND status = :src'
            );
            $stmt->execute([
                'dest' => $to,
                'done' => $to === 'done' ? 1 : 0,
                'uid' => $userId,
                'src' => $from,
            ]);
            $count = $stmt->rowCount();

            $this->writePositions($userId, array_column($destination, 'task_id'));

            return $count;
        });
    }

    /** Clear tasks in a status. */
    // Clear tasks in a status
    public function clearAll(int $userId, string $status): int
    {
        if (!in_array($status, self::STATUSES, true)) {
            return 0;
        }

        $stmt = $this->dbh->prepare('DELETE FROM tasks WHERE user_id = :uid AND status = :status');
        $stmt->execute(['uid' => $userId, 'status' => $status]);

        return $stmt->rowCount();
    }

    /* ===================== ordering helpers ===================== */

    // Apply a task status
    private function applyStatus(int $taskId, int $userId, string $status): void
    {
        $stmt = $this->dbh->prepare(
            'UPDATE tasks
             SET status = :status,
                 completed_at = IF(:done = 1, COALESCE(completed_at, NOW()), NULL)
             WHERE task_id = :id AND user_id = :uid'
        );
        $stmt->execute([
            'status' => $status,
            'done' => $status === 'done' ? 1 : 0,
            'id' => $taskId,
            'uid' => $userId,
        ]);
    }


    // Compare automatic task ordering
    private function compareAuto(array $a, array $b): int
    {
        $byPriority = self::PRIORITY_RANK[$a['priority']] <=> self::PRIORITY_RANK[$b['priority']];
        if ($byPriority !== 0) {
            return $byPriority;
        }

        $dueA = $a['due_date'] ?? null;
        $dueB = $b['due_date'] ?? null;
        if ($dueA === $dueB) {
            return 0;
        }
        if ($dueA === null) {
            return 1;
        }
        if ($dueB === null) {
            return -1;
        }

        return strcmp((string) $dueA, (string) $dueB) < 0 ? -1 : 1;
    }


    // Insert a task into sorted rows
    private function insertSorted(array $rows, array $task): array
    {
        $rows = array_values($rows);
        $index = count($rows);

        foreach ($rows as $i => $row) {
            if ($this->compareAuto($row, $task) > 0) {
                $index = $i;
                break;
            }
        }

        array_splice($rows, $index, 0, [$task]);

        return $rows;
    }


    // Place a task automatically
    private function placeAuto(int $userId, int $taskId): void
    {
        $task = $this->find($taskId, $userId);
        if (!$task) {
            return;
        }

        $rows = $this->columnRows($userId, $task['status'], $taskId);
        $rows = $this->insertSorted($rows, $task);
        $this->writePositions($userId, array_column($rows, 'task_id'));
    }


    // Load tasks in a status column
    private function columnRows(int $userId, string $status, ?int $excludeId = null): array
    {
        $sql = 'SELECT task_id, priority, due_date, position, created_at
                FROM tasks
                WHERE user_id = :uid AND status = :status';
        $params = ['uid' => $userId, 'status' => $status];

        if ($excludeId !== null) {
            $sql .= ' AND task_id <> :ex';
            $params['ex'] = $excludeId;
        }

        $stmt = $this->dbh->prepare($sql . ' ORDER BY position ASC, task_id ASC');
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) > 1 && count(array_unique(array_column($rows, 'position'))) !== count($rows)) {
            usort($rows, function (array $a, array $b): int {
                $byAuto = $this->compareAuto($a, $b);
                if ($byAuto !== 0) {
                    return $byAuto;
                }

                return [(string) $a['created_at'], (int) $a['task_id']] <=> [(string) $b['created_at'], (int) $b['task_id']];
            });
            $this->writePositions($userId, array_column($rows, 'task_id'));
        }

        return $rows;
    }

  
    // Persist task positions
    private function writePositions(int $userId, array $ids): void
    {
        $stmt = $this->dbh->prepare('UPDATE tasks SET position = :pos WHERE task_id = :id AND user_id = :uid');

        foreach (array_values($ids) as $position => $id) {
            $stmt->execute(['pos' => $position, 'id' => (int) $id, 'uid' => $userId]);
        }
    }

    /** @template T @param callable():T $callback @return T */
    // Run a database transaction
    private function transactional(callable $callback)
    {
        $this->dbh->beginTransaction();

        try {
            $result = $callback();
            $this->dbh->commit();

            return $result;
        } catch (Throwable $e) {
            if ($this->dbh->inTransaction()) {
                $this->dbh->rollBack();
            }
            throw $e;
        }
    }

    // Normalize a task row
    private function normalize(array $row): array
    {
        return [
            'task_id' => (int) $row['task_id'],
            'title' => (string) $row['title'],
            'description' => (string) ($row['description'] ?? ''),
            'status' => (string) $row['status'],
            'priority' => (string) $row['priority'],
            'due_date' => $row['due_date'] !== null ? (string) $row['due_date'] : null,
            'position' => (int) $row['position'],
            'completed_at' => $row['completed_at'] !== null ? (string) $row['completed_at'] : null,
            'created_at' => (string) $row['created_at'],
        ];
    }
}
