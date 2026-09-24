<?php

class Note
{
    private PDO $dbh;

    public function __construct(PDO $dbh)
    {   
        $this->dbh = $dbh;
    }


    // Search user notes
    public function searchForUser(int $userId, ?int $folderId = null, string $search = ''): array
{
    $conditions = ['n.user_id = :uid'];
    $params = ['uid' => $userId];

    if ($folderId !== null) {
        $conditions[] = 'n.folder_id = :folder_id';
        $params['folder_id'] = $folderId;
    }
    if ($search !== '') {
        $conditions[] = '(n.title LIKE :search1 OR n.content LIKE :search2)';
        $params['search1'] = '%' . $search . '%';
        $params['search2'] = '%' . $search . '%';
    }

    $where = implode(' AND ', $conditions);

    $stmt = $this->dbh->prepare(
        "SELECT n.*, f.folder_name
         FROM notes n
         LEFT JOIN folders f ON f.folder_id = n.folder_id
         WHERE {$where}
         ORDER BY n.updated_at DESC"
    );
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    // Fetch a user note
    public function find(int $noteId, int $userId): ?array
    {
        $stmt = $this->dbh->prepare(
            'SELECT n.*, f.folder_name
             FROM notes n
             LEFT JOIN folders f ON f.folder_id = n.folder_id
             WHERE n.note_id = :id AND n.user_id = :uid'
        );
        $stmt->execute(['id' => $noteId, 'uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // Count user notes
    public function countForUser(int $userId): int
    {
        $stmt = $this->dbh->prepare('SELECT COUNT(*) FROM notes WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    // Count notes by folder
    public function folderCountsForUser(int $userId): array
    {
        $stmt = $this->dbh->prepare(
            "SELECT f.folder_id, f.folder_name, COUNT(n.note_id) AS note_count
            FROM folders f
            LEFT JOIN notes n ON n.folder_id = f.folder_id AND n.user_id = f.user_id
            WHERE f.user_id = :uid AND f.folder_type = 'notes'
            GROUP BY f.folder_id, f.folder_name
            ORDER BY f.folder_name"
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // Create a note
    public function create(int $userId, array $data): array
    {
        $errors = $this->validate($data);
        if ($errors !== []) {
            return ['errors' => $errors];
        }

        $stmt = $this->dbh->prepare(
            'INSERT INTO notes (user_id, folder_id, title, content)
             VALUES (:user_id, :folder_id, :title, :content)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'folder_id' => $this->resolveNotesFolderId($data['folder_id'] ?? null, $userId),
            'title' => $data['title'],
            'content' => $data['content'] !== '' ? $data['content'] : null,
        ]);

        return ['errors' => [], 'note_id' => (int) $this->dbh->lastInsertId()];
    }

    // Update a note
    public function update(int $noteId, int $userId, array $data): array
    {
        $errors = $this->validate($data);
        if ($errors !== []) {
            return ['errors' => $errors];
        }

        $stmt = $this->dbh->prepare(
            'UPDATE notes SET folder_id = :folder_id, title = :title, content = :content
             WHERE note_id = :id AND user_id = :uid'
        );
        $stmt->execute([
            'folder_id' => $data['folder_id'] ?: null,
            'title' => $data['title'],
            'content' => $data['content'] !== '' ? $data['content'] : null,
            'id' => $noteId,
            'uid' => $userId,
        ]);

        return ['errors' => []];
    }

    // Delete a note
    public function delete(int $noteId, int $userId): bool
    {
        $stmt = $this->dbh->prepare('DELETE FROM notes WHERE note_id = :id AND user_id = :uid');
        return $stmt->execute(['id' => $noteId, 'uid' => $userId]);
    }


    // Move a note to a folder
    public function moveToFolder(int $noteId, int $userId, ?int $folderId): bool
    {
        if ($folderId !== null) {
            $check = $this->dbh->prepare(
                "SELECT 1 FROM folders WHERE folder_id = :id AND user_id = :uid AND folder_type = 'notes'"
            );
            $check->execute(['id' => $folderId, 'uid' => $userId]);
            if (!$check->fetchColumn()) {
                return false;
            }
        }

        $stmt = $this->dbh->prepare(
            'UPDATE notes SET folder_id = :folder_id WHERE note_id = :id AND user_id = :uid'
        );
        return $stmt->execute([
            'folder_id' => $folderId,
            'id' => $noteId,
            'uid' => $userId,
        ]);
    }

    // Duplicate a note
    public function duplicate(int $noteId, int $userId): ?int
    {
        $original = $this->find($noteId, $userId);
        if (!$original) {
            return null;
        }

        $originalTitle = trim((string) $original['title']);

        $stmt = $this->dbh->prepare(
            'INSERT INTO notes (user_id, folder_id, title, content)
             VALUES (:user_id, :folder_id, :title, :content)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'folder_id' => $original['folder_id'],
            'title' => $originalTitle !== '' ? $originalTitle . ' (Copy)' : '',
            'content' => $original['content'],
        ]);

        return (int) $this->dbh->lastInsertId();
    }


    // Build a plain-text excerpt
    public static function excerptOf(?string $htmlContent, int $length = 90): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $htmlContent)));
        if ($text === '') {
            return 'No text';
        }
        return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '…' : $text;
    }


    // Provide a fallback note title
    public static function titleOrDefault(?string $title): string
    {
        $title = trim((string) $title);
        return $title !== '' ? $title : 'Untitled note';
    }


    // Resolve a notes folder ID
    private function resolveNotesFolderId($raw, int $userId): ?int
    {
        $folderId = (int) $raw;
        if ($folderId <= 0) {
            return null;
        }

        $stmt = $this->dbh->prepare(
            "SELECT folder_id FROM folders WHERE folder_id = :id AND user_id = :uid AND folder_type = 'notes'"
        );
        $stmt->execute(['id' => $folderId, 'uid' => $userId]);

        return $stmt->fetchColumn() ? $folderId : null;
    }

    // Validate note data
    private function validate(array $data): array
    {
        $errors = [];

        if (mb_strlen((string) ($data['title'] ?? '')) > 100) {
            $errors['title'] = 'Title must be 100 characters or less.';
        }

        return $errors;
    }
}