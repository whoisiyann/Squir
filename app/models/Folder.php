<?php

class Folder
{
    public const COLORS = [
        'beige', 'blue', 'brown', 'coralRed', 'creamYellow', 'darkGreen',
        'green', 'lavender', 'magenta', 'orange', 'pink', 'purple',
        'red', 'skyBlue', 'teal', 'yellow',
    ];

    public const TYPES = ['passwords', 'notes'];

    private PDO $dbh;

    public function __construct(PDO $dbh)
    {
        $this->dbh = $dbh;
    }

    public function searchForUser(int $userId, string $search = '', string $type = 'passwords'): array
    {
        $type = in_array($type, self::TYPES, true) ? $type : 'passwords';

        $conditions = ['f.user_id = :uid', 'f.folder_type = :type'];
        $params = ['uid' => $userId, 'type' => $type];

        if ($search !== '') {
            $conditions[] = 'f.folder_name LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $where = implode(' AND ', $conditions);

        $stmt = $this->dbh->prepare(
            "SELECT f.*,
                (SELECT COUNT(*) FROM vault v WHERE v.folder_id = f.folder_id AND v.user_id = f.user_id) AS vault_count,
                (SELECT COUNT(*) FROM notes n WHERE n.folder_id = f.folder_id AND n.user_id = f.user_id) AS note_count
             FROM folders f
             WHERE {$where}
             ORDER BY f.updated_at DESC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $folderId, int $userId): ?array
    {
        $stmt = $this->dbh->prepare('SELECT * FROM folders WHERE folder_id = :id AND user_id = :uid');
        $stmt->execute(['id' => $folderId, 'uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Ginagamit sa <select> ng Vault (type=passwords) at Notes (type=notes) forms */
    public function allForUserByType(int $userId, string $type): array
    {
        $type = in_array($type, self::TYPES, true) ? $type : 'passwords';
        $stmt = $this->dbh->prepare(
            'SELECT folder_id, folder_name FROM folders WHERE user_id = :uid AND folder_type = :type ORDER BY folder_name'
        );
        $stmt->execute(['uid' => $userId, 'type' => $type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function nameExists(int $userId, string $name, string $type, ?int $excludeId = null): bool
    {
        $sql = 'SELECT folder_id FROM folders WHERE user_id = :uid AND folder_name = :name AND folder_type = :type';
        $params = ['uid' => $userId, 'name' => $name, 'type' => $type];
        if ($excludeId !== null) {
            $sql .= ' AND folder_id != :excludeId';
            $params['excludeId'] = $excludeId;
        }
        $stmt = $this->dbh->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function create(int $userId, array $data): array
    {
        $errors = $this->validate($data, $userId, null);
        if ($errors !== []) {
            return ['errors' => $errors];
        }

        $color = in_array($data['color'], self::COLORS, true) ? $data['color'] : 'brown';
        $type = in_array($data['folder_type'] ?? '', self::TYPES, true) ? $data['folder_type'] : 'passwords';

        $stmt = $this->dbh->prepare(
            'INSERT INTO folders (user_id, folder_name, folder_type, color) VALUES (:uid, :name, :type, :color)'
        );
        $stmt->execute(['uid' => $userId, 'name' => $data['folder_name'], 'type' => $type, 'color' => $color]);

        return ['errors' => [], 'folder_id' => (int) $this->dbh->lastInsertId()];
    }

    public function rename(int $folderId, int $userId, string $name): array
    {
        $existing = $this->find($folderId, $userId);
        if (!$existing) {
            return ['errors' => ['folder_name' => 'Folder not found.']];
        }

        $errors = $this->validate(
            ['folder_name' => $name, 'folder_type' => $existing['folder_type']],
            $userId,
            $folderId
        );
        if ($errors !== []) {
            return ['errors' => $errors];
        }

        $stmt = $this->dbh->prepare(
            'UPDATE folders SET folder_name = :name WHERE folder_id = :id AND user_id = :uid'
        );
        $stmt->execute(['name' => trim($name), 'id' => $folderId, 'uid' => $userId]);

        return ['errors' => []];
    }

    public function updateColor(int $folderId, int $userId, string $color): bool
    {
        if (!in_array($color, self::COLORS, true)) {
            return false;
        }

        $stmt = $this->dbh->prepare(
            'UPDATE folders SET color = :color WHERE folder_id = :id AND user_id = :uid'
        );
        return $stmt->execute(['color' => $color, 'id' => $folderId, 'uid' => $userId]);
    }

    public function delete(int $folderId, int $userId): bool
    {
        if (!$this->find($folderId, $userId)) {
            return false;
        }

        $this->dbh->beginTransaction();
        try {
            $params = ['id' => $folderId, 'uid' => $userId];

            $this->dbh->prepare('DELETE FROM vault WHERE folder_id = :id AND user_id = :uid')->execute($params);
            $this->dbh->prepare('DELETE FROM notes WHERE folder_id = :id AND user_id = :uid')->execute($params);


            $this->dbh->prepare(
                'DELETE t FROM tags t
                 LEFT JOIN vault_tags vt ON vt.tag_id = t.tag_id
                 WHERE t.user_id = :uid AND vt.vault_id IS NULL'
            )->execute(['uid' => $userId]);

            $this->dbh->prepare('DELETE FROM folders WHERE folder_id = :id AND user_id = :uid')->execute($params);

            $this->dbh->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->dbh->inTransaction()) {
                $this->dbh->rollBack();
            }
            return false;
        }
    }

    private function validate(array $data, int $userId, ?int $excludeId): array
    {
        $errors = [];
        $name = trim((string) ($data['folder_name'] ?? ''));
        $type = in_array($data['folder_type'] ?? '', self::TYPES, true) ? $data['folder_type'] : 'passwords';

        if ($name === '') {
            $errors['folder_name'] = 'Folder name is required.';
        } elseif (mb_strlen($name) > 100) {
            $errors['folder_name'] = 'Folder name must be 100 characters or fewer.';
        } elseif ($this->nameExists($userId, $name, $type, $excludeId)) {
            $errors['folder_name'] = 'You already have a folder with this name.';
        }

        return $errors;
    }
}