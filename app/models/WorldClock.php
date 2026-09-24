<?php
// Dashboard world clocks

class WorldClock
{
    public const MAX_PER_USER = 8;

    private PDO $dbh;

    // Initialize clock data access
    public function __construct(PDO $dbh)
    {
        $this->dbh = $dbh;
    }

    // Fetch user clocks
    public function forUser(int $userId): array
    {
        $stmt = $this->dbh->prepare(
            'SELECT c.city_id, c.city_name, c.timezone, co.country_code, co.country_name
             FROM user_world_clocks w
             JOIN cities c ON c.city_id = w.city_id
             JOIN countries co ON co.country_code = c.country_code
             WHERE w.user_id = :uid
             ORDER BY w.created_at ASC, c.city_id ASC'
        );
        $stmt->execute(['uid' => $userId]);

        return array_map([$this, 'normalize'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // Fetch available cities
    public function catalog(): array
    {
        $stmt = $this->dbh->query(
            'SELECT c.city_id, c.city_name, c.timezone, co.country_code, co.country_name
             FROM cities c
             JOIN countries co ON co.country_code = c.country_code
             ORDER BY c.city_name ASC, co.country_name ASC'
        );

        return array_map([$this, 'normalize'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // Resolve a city label
    public function timezoneLabels(): array
    {
        $rows = $this->dbh->query(
            'SELECT c.city_name, c.timezone, co.country_name
             FROM cities c
             JOIN countries co ON co.country_code = c.country_code
             ORDER BY c.city_name ASC'
        )->fetchAll(PDO::FETCH_ASSOC);

        $labels = [];
        foreach ($rows as $row) {
            $zone = (string) $row['timezone'];
            $slash = strrpos($zone, '/');
            $leaf = str_replace('_', ' ', $slash === false ? $zone : substr($zone, $slash + 1));

            if (!isset($labels[$zone]) || strcasecmp($leaf, (string) $row['city_name']) === 0) {
                $labels[$zone] = $row['city_name'] . ', ' . $row['country_name'];
            }
        }

        return $labels;
    }

    // Add a user clock
    public function add(int $userId, int $cityId): ?string
    {
        $exists = $this->dbh->prepare('SELECT 1 FROM cities WHERE city_id = :cid');
        $exists->execute(['cid' => $cityId]);
        if (!$exists->fetchColumn()) {
            return 'That city could not be found.';
        }

        $count = $this->dbh->prepare('SELECT COUNT(*) FROM user_world_clocks WHERE user_id = :uid');
        $count->execute(['uid' => $userId]);
        if ((int) $count->fetchColumn() >= self::MAX_PER_USER) {
            return 'You can keep up to ' . self::MAX_PER_USER . ' world clocks. Remove one to add another.';
        }

        // Ignore duplicate clocks
        $stmt = $this->dbh->prepare('INSERT IGNORE INTO user_world_clocks (user_id, city_id) VALUES (:uid, :cid)');
        $stmt->execute(['uid' => $userId, 'cid' => $cityId]);

        return null;
    }

    // Remove a user clock
    public function remove(int $userId, int $cityId): bool
    {
        $stmt = $this->dbh->prepare('DELETE FROM user_world_clocks WHERE user_id = :uid AND city_id = :cid');
        $stmt->execute(['uid' => $userId, 'cid' => $cityId]);

        return $stmt->rowCount() > 0;
    }

    // Normalize a clock row
    private function normalize(array $row): array
    {
        return [
            'city_id' => (int) $row['city_id'],
            'city_name' => (string) $row['city_name'],
            'country_code' => (string) $row['country_code'],
            'country_name' => (string) $row['country_name'],
            'timezone' => (string) $row['timezone'],
        ];
    }
}
