<?php

class Note
{
    private PDO $dbh;

    public function __construct(PDO $dbh)
    {
        $this->dbh = $dbh;
    }

    /* ================= QUERIES ================= */

    public function searchForUser(int $userId, ?int $folderId = null, string $search = ''): array
    {
        $conditions = ['n.user_id = :uid'];
        $params = ['uid' => $userId];

        if ($folderId !== null) {
            $conditions[] = 'n.folder_id = :folder_id';
            $params['folder_id'] = $folderId;
        }
        if ($search !== '') {
            $conditions[] = '(n.title LIKE :search OR n.content LIKE :search)';
            $params['search'] = '%' . $search . '%';
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

    public function countForUser(int $userId): int
    {
        $stmt = $this->dbh->prepare('SELECT COUNT(*) FROM notes WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function folderCountsForUser(int $userId): array
    {
        $stmt = $this->dbh->prepare(
            'SELECT f.folder_id, f.folder_name, COUNT(n.note_id) AS note_count
             FROM folders f
             LEFT JOIN notes n ON n.folder_id = f.folder_id AND n.user_id = f.user_id
             WHERE f.user_id = :uid
             GROUP BY f.folder_id, f.folder_name
             ORDER BY f.folder_name'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ================= MUTATIONS ================= */

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
            'folder_id' => $data['folder_id'] ?: null,
            'title' => $data['title'],
            'content' => $data['content'] !== '' ? $data['content'] : null,
        ]);

        return ['errors' => [], 'note_id' => (int) $this->dbh->lastInsertId()];
    }

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

    public function delete(int $noteId, int $userId): bool
    {
        $stmt = $this->dbh->prepare('DELETE FROM notes WHERE note_id = :id AND user_id = :uid');
        return $stmt->execute(['id' => $noteId, 'uid' => $userId]);
    }


    public function moveToFolder(int $noteId, int $userId, ?int $folderId): bool
    {
        $stmt = $this->dbh->prepare(
            'UPDATE notes SET folder_id = :folder_id WHERE note_id = :id AND user_id = :uid'
        );
        return $stmt->execute([
            'folder_id' => $folderId,
            'id' => $noteId,
            'uid' => $userId,
        ]);
    }

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

    /* ================= HELPERS ================= */

    /** Plain-text preview for the list card (strips HTML tags, collapses whitespace). */
    public static function excerptOf(?string $htmlContent, int $length = 90): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $htmlContent)));
        if ($text === '') {
            return 'No text';
        }
        return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '…' : $text;
    }


    public static function titleOrDefault(?string $title): string
    {
        $title = trim((string) $title);
        return $title !== '' ? $title : 'Untitled note';
    }

    private function validate(array $data): array
    {
        $errors = [];

        if (mb_strlen((string) ($data['title'] ?? '')) > 100) {
            $errors['title'] = 'Title must be 100 characters or less.';
        }

        return $errors;
    }
}