<?php

class Favorite
{
    private PDO $dbh;

    public function __construct(PDO $dbh)
    {
        $this->dbh = $dbh;
    }


    // Fetch favorited vault entries for a user
    public function vaultForUser(int $userId, string $search = ''): array
    {
        $conditions = ['fav.user_id = :uid', 'fav.vault_id IS NOT NULL'];
        $params = ['uid' => $userId];

        if ($search !== '') {
            $conditions[] = '(v.title LIKE :search OR v.account_username LIKE :search OR v.website_url LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $where = implode(' AND ', $conditions);

        $stmt = $this->dbh->prepare(
            "SELECT v.*,
                    GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ',') AS tags
             FROM favorites fav
             JOIN vault v ON v.vault_id = fav.vault_id AND v.user_id = fav.user_id
             LEFT JOIN vault_tags vt ON vt.vault_id = v.vault_id
             LEFT JOIN tags t        ON t.tag_id = vt.tag_id
             WHERE {$where}
             GROUP BY v.vault_id
             ORDER BY fav.created_at DESC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // Fetch favorited notes for a user
    public function notesForUser(int $userId, string $search = ''): array
    {
        $conditions = ['fav.user_id = :uid', 'fav.note_id IS NOT NULL'];
        $params = ['uid' => $userId];

        if ($search !== '') {
            $conditions[] = '(n.title LIKE :search1 OR n.content LIKE :search2)';
            $params['search1'] = '%' . $search . '%';
            $params['search2'] = '%' . $search . '%';
        }

        $where = implode(' AND ', $conditions);

        $stmt = $this->dbh->prepare(
            "SELECT n.*, f.folder_name
             FROM favorites fav
             JOIN notes n        ON n.note_id = fav.note_id AND n.user_id = fav.user_id
             LEFT JOIN folders f ON f.folder_id = n.folder_id
             WHERE {$where}
             ORDER BY fav.created_at DESC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // Fetch favorited folders for a user
    public function foldersForUser(int $userId, string $search = ''): array
    {
        $conditions = ['fav.user_id = :uid', 'fav.folder_id IS NOT NULL'];
        $params = ['uid' => $userId];

        if ($search !== '') {
            $conditions[] = 'f.folder_name LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $where = implode(' AND ', $conditions);

        $stmt = $this->dbh->prepare(
            "SELECT f.*,
                (SELECT COUNT(*) FROM vault v WHERE v.folder_id = f.folder_id AND v.user_id = f.user_id) AS vault_count,
                (SELECT COUNT(*) FROM notes n WHERE n.folder_id = f.folder_id AND n.user_id = f.user_id) AS note_count
             FROM favorites fav
             JOIN folders f ON f.folder_id = fav.folder_id AND f.user_id = fav.user_id
             WHERE {$where}
             ORDER BY fav.created_at DESC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // Count favorites per type for a user
    public function countsForUser(int $userId): array
    {
        $stmt = $this->dbh->prepare(
            'SELECT
                SUM(vault_id IS NOT NULL)  AS passwords,
                SUM(note_id  IS NOT NULL)  AS notes,
                SUM(folder_id IS NOT NULL) AS folders
             FROM favorites
             WHERE user_id = :uid'
        );
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'passwords' => (int) ($row['passwords'] ?? 0),
            'notes'     => (int) ($row['notes'] ?? 0),
            'folders'   => (int) ($row['folders'] ?? 0),
        ];
    }
}
