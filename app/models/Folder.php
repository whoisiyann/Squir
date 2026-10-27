<?php

class Folder
{
    public const COLORS = [
        'brown',
        'skyBlue', 'blue',
        'creamYellow', 'yellow',
        'coralRed', 'red',
        'green', 'darkGreen',
        'lavender', 'purple',
        'pink', 'magenta',
        'beige', 'teal', 'orange',
    ];

    public const TYPES = ['passwords', 'notes'];

    private PDO $dbh;

    public function __construct(PDO $dbh)
    {
        $this->dbh = $dbh;
    }

    // Search folders
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
                COUNT(DISTINCT v.vault_id) AS vault_count,
                COUNT(DISTINCT n.note_id)  AS note_count,
                MAX(fav.favorite_id IS NOT NULL) AS is_favorite
             FROM folders f
             LEFT JOIN vault v       ON v.folder_id = f.folder_id AND v.user_id = f.user_id
             LEFT JOIN notes n       ON n.folder_id = f.folder_id AND n.user_id = f.user_id
             LEFT JOIN favorites fav ON fav.folder_id = f.folder_id AND fav.user_id = f.user_id
             WHERE {$where}
             GROUP BY f.folder_id
             ORDER BY f.updated_at DESC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Find a folder
    public function find(int $folderId, int $userId): ?array
    {
        $stmt = $this->dbh->prepare('SELECT * FROM folders WHERE folder_id = :id AND user_id = :uid');
        $stmt->execute(['id' => $folderId, 'uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }


    // Get folders by type
    public function allForUserByType(int $userId, string $type): array
    {
        $type = in_array($type, self::TYPES, true) ? $type : 'passwords';
        $stmt = $this->dbh->prepare(
            'SELECT folder_id, folder_name FROM folders WHERE user_id = :uid AND folder_type = :type ORDER BY folder_name'
        );
        $stmt->execute(['uid' => $userId, 'type' => $type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Check for duplicate names
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

    // Create a folder
    public function create(int $userId, array $data): array
    {
        $errors = $this->validate($data, $userId, null);
        if ($errors !== []) {
            return ['errors' => $errors];
        }

        $color = in_array($data['color'], self::COLORS, true) ? $data['color'] : 'brown';
        $type = in_array($data['folder_type'] ?? '', self::TYPES, true) ? $data['folder_type'] : 'passwords';

        try {
            $stmt = $this->dbh->prepare(
                'INSERT INTO folders (user_id, folder_name, folder_type, color) VALUES (:uid, :name, :type, :color)'
            );
            $stmt->execute(['uid' => $userId, 'name' => $data['folder_name'], 'type' => $type, 'color' => $color]);
        } catch (PDOException $e) {
            return ['errors' => $this->writeError($e, 'Folder::create')];
        }

        return ['errors' => [], 'folder_id' => (int) $this->dbh->lastInsertId()];
    }

    // Rename a folder
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

        try {
            $stmt = $this->dbh->prepare(
                'UPDATE folders SET folder_name = :name, updated_at = CURRENT_TIMESTAMP WHERE folder_id = :id AND user_id = :uid'
            );
            $stmt->execute(['name' => trim($name), 'id' => $folderId, 'uid' => $userId]);
        } catch (PDOException $e) {
            return ['errors' => $this->writeError($e, 'Folder::rename')];
        }

        return ['errors' => []];
    }

    // Update folder color
    public function updateColor(int $folderId, int $userId, string $color): bool
    {
        if (!in_array($color, self::COLORS, true)) {
            return false;
        }

        try {
            $stmt = $this->dbh->prepare(
                'UPDATE folders SET color = :color, updated_at = CURRENT_TIMESTAMP WHERE folder_id = :id AND user_id = :uid'
            );
            return $stmt->execute(['color' => $color, 'id' => $folderId, 'uid' => $userId]);
        } catch (PDOException $e) {
            error_log('[Folder::updateColor] ' . $e->getMessage());
            return false;
        }
    }

    // Refresh a folder's Date modified
    public function touch(?int $folderId, int $userId): void
    {
        if ($folderId === null || $folderId <= 0) {
            return;
        }

        try {
            $stmt = $this->dbh->prepare(
                'UPDATE folders SET updated_at = CURRENT_TIMESTAMP WHERE folder_id = :id AND user_id = :uid'
            );
            $stmt->execute(['id' => $folderId, 'uid' => $userId]);
        } catch (PDOException $e) {
            error_log('[Folder::touch] ' . $e->getMessage());
        }
    }

    // Delete a folder
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
            error_log('[Folder::delete] ' . $e->getMessage());
            return false;
        }
    }

    // Show a friendly database error
    private function writeError(PDOException $e, string $context): array
    {
        error_log('[' . $context . '] ' . $e->getMessage());

        if ($e->getCode() === '23000') {
            return ['folder_name' => 'You already have a folder with this name.'];
        }

        return ['form' => 'We could not save your changes right now. Please try again.'];
    }

    // Validate folder data
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