<?php

class ActivityLog
{
    // Activity metadata.
    private const META = [
        'logged_in'        => ['label' => 'Logged in',                   'detail' => null,                             'icon' => 'ti-login',         'entity' => 'user'],
        'logged_out'       => ['label' => 'Logged out',                  'detail' => null,                             'icon' => 'ti-logout',        'entity' => 'user'],
        'vault_unlocked'   => ['label' => 'Vault unlocked',              'detail' => 'PIN verified',                   'icon' => 'ti-lock',          'entity' => 'vault'],
        'vault_created'    => ['label' => 'Created credential',          'detail' => null,                             'icon' => 'ti-key',           'entity' => 'vault'],
        'vault_updated'    => ['label' => 'Updated credential',          'detail' => null,                             'icon' => 'ti-key',           'entity' => 'vault'],
        'vault_deleted'    => ['label' => 'Deleted credential',          'detail' => null,                             'icon' => 'ti-key',           'entity' => 'vault'],
        'password_viewed'  => ['label' => 'Viewed password',              'detail' => null,                             'icon' => 'ti-eye',           'entity' => 'vault'],
        'password_copied'  => ['label' => 'Copied password',              'detail' => null,                             'icon' => 'ti-copy',          'entity' => 'vault'],
        'folder_created'   => ['label' => 'Created folder',               'detail' => null,                             'icon' => 'ti-folder',        'entity' => 'folder'],
        'folder_renamed'   => ['label' => 'Folder renamed',              'detail' => null,                             'icon' => 'ti-folder',        'entity' => 'folder'],
        'folder_deleted'   => ['label' => 'Folder deleted',              'detail' => null,                             'icon' => 'ti-folder',        'entity' => 'folder'],
        'note_created'     => ['label' => 'Created note',                'detail' => null,                             'icon' => 'ti-notes',         'entity' => 'note'],
        'note_updated'     => ['label' => 'Updated note',                'detail' => null,                             'icon' => 'ti-notes',         'entity' => 'note'],
        'note_deleted'     => ['label' => 'Deleted note',                'detail' => null,                             'icon' => 'ti-notes',         'entity' => 'note'],
        'task_created'     => ['label' => 'Created task',                'detail' => null,                             'icon' => 'ti-checkbox',      'entity' => 'task'],
        'task_updated'     => ['label' => 'Updated task',                'detail' => null,                             'icon' => 'ti-checkbox',      'entity' => 'task'],
        'task_deleted'     => ['label' => 'Deleted task',                'detail' => null,                             'icon' => 'ti-checkbox',      'entity' => 'task'],
        'task_completed'   => ['label' => 'Completed task',              'detail' => null,                             'icon' => 'ti-checkbox',      'entity' => 'task'],
        'task_reopened'    => ['label' => 'Reopened task',               'detail' => null,                             'icon' => 'ti-checkbox',      'entity' => 'task'],
        'profile_updated'  => ['label' => 'Updated profile',             'detail' => null,                             'icon' => 'ti-user',          'entity' => 'user'],
        'username_changed' => ['label' => 'Updated username',            'detail' => null,                             'icon' => 'ti-user',          'entity' => 'user'],
        'email_changed'    => ['label' => 'Updated email',               'detail' => null,                             'icon' => 'ti-mail',          'entity' => 'user'],
        'password_changed' => ['label' => 'Changed password',             'detail' => null,                             'icon' => 'ti-shield-check',  'entity' => 'user'],
        'pin_updated'      => ['label' => 'Changed vault PIN',            'detail' => null,                             'icon' => 'ti ti-shield-lock','entity' => 'user'],
        'favorite_added'   => ['label' => 'Added favorite',              'detail' => null,                             'icon' => 'ti-star',          'entity' => 'favorite'],
        'favorite_removed' => ['label' => 'Removed favorite',            'detail' => null,                             'icon' => 'ti-star',          'entity' => 'favorite'],
        'activity_cleared' => ['label' => 'Activity log cleared',        'detail' => null,                             'icon' => 'ti-trash',         'entity' => 'system'],
        'data_exported'    => ['label' => 'Data exported',               'detail' => 'Downloaded a copy of your data', 'icon' => 'ti-download',      'entity' => 'system'],
        'admin_logged_in'  => ['label' => 'Admin login',                 'detail' => null,                             'icon' => 'ti-login',         'entity' => 'admin'],
        'admin_logged_out' => ['label' => 'Admin logout',                'detail' => null,                             'icon' => 'ti-logout',        'entity' => 'admin'],
        'user_created'     => ['label' => 'Added a user',                'detail' => null,                             'icon' => 'ti-user-plus',     'entity' => 'user'],
        'user_updated'     => ['label' => 'Updated a user',              'detail' => null,                             'icon' => 'ti-user-edit',     'entity' => 'user'],
        'user_activated'   => ['label' => 'Activated a user',            'detail' => null,                             'icon' => 'ti-user-check',    'entity' => 'user'],
        'user_deactivated' => ['label' => 'Deactivated a user',          'detail' => null,                             'icon' => 'ti-user-off',      'entity' => 'user'],
        'user_suspended'   => ['label' => 'Suspended a user',            'detail' => null,                             'icon' => 'ti-user-off',      'entity' => 'user'],
        'user_deleted'     => ['label' => 'Deleted a user',              'detail' => null,                             'icon' => 'ti-user-x',        'entity' => 'user'],
        'user_force_logout'=> ['label' => 'Forced a user logout',        'detail' => null,                             'icon' => 'ti-logout',        'entity' => 'user'],
    ];

    public function __construct(private PDO $db)
    {
    }

    // Log user activity
    public function log(int $userId, string $action, ?string $description = null, ?string $entityType = null, ?int $entityId = null): void
    {
        $meta = self::META[$action] ?? null;

        $statement = $this->db->prepare(
            'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, device, user_agent)
             VALUES (:user_id, :action, :entity_type, :entity_id, :description, :ip_address, :device, :user_agent)'
        );

        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 255) : null;

        $statement->execute([
            'user_id'     => $userId,
            'action'      => $action,
            'entity_type' => $entityType ?? ($meta['entity'] ?? null),
            'entity_id'   => $entityId,
            'description' => $description ?? ($meta['detail'] ?? $meta['label'] ?? $action),
            'ip_address'  => self::clientIp(),
            'device'      => self::detectDevice($userAgent, self::modelHint()),
            'user_agent'  => $userAgent,
        ]);
    }

    // Record admin activity.
    public function logAdmin(int $adminId, string $action, ?string $description = null): void
    {
        $meta = self::META[$action] ?? null;

        $statement = $this->db->prepare(
            'INSERT INTO activity_logs (admin_id, action, entity_type, description, ip_address, device, user_agent)
             VALUES (:admin_id, :action, :entity_type, :description, :ip_address, :device, :user_agent)'
        );

        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 255) : null;

        $statement->execute([
            'admin_id'    => $adminId,
            'action'      => $action,
            'entity_type' => $meta['entity'] ?? 'admin',
            'description' => $description ?? ($meta['detail'] ?? $meta['label'] ?? $action),
            'ip_address'  => self::clientIp(),
            'device'      => self::detectDevice($userAgent, self::modelHint()),
            'user_agent'  => $userAgent,
        ]);
    }

    // Get user activity logs.
    public function listForUser(int $userId, int $limit = 50): array
    {
        $statement = $this->db->prepare(
            'SELECT action, description, created_at
             FROM activity_logs
             WHERE user_id = :user_id
             ORDER BY created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        $items = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $meta = self::META[$row['action']] ?? [
                'label' => ucfirst(str_replace('_', ' ', (string) $row['action'])),
                'icon'  => 'ti-activity',
            ];

            $items[] = [
                'label'      => $meta['label'],
                'detail'     => $row['description'] !== '' ? $row['description'] : ($meta['detail'] ?? ''),
                'icon'       => $meta['icon'],
                'time_label' => self::timeAgo($row['created_at']),
            ];
        }

        return $items;
    }

    // Get recent user activity
    public function recentForAdmin(int $userId, int $limit = 5): array
    {
        $statement = $this->db->prepare(
            'SELECT action, description, ip_address, device, user_agent, created_at
             FROM activity_logs
             WHERE user_id = :user_id
             ORDER BY created_at DESC, log_id DESC
             LIMIT :limit'
        );
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        $items = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $meta = self::META[$row['action']] ?? [
                'label' => ucfirst(str_replace('_', ' ', (string) $row['action'])),
                'icon'  => 'ti-activity',
            ];

            // Detect device for older logs
            $device = (string) ($row['device'] ?? '');
            if ($device === '') {
                $device = self::detectDevice($row['user_agent'] ?? null);
            }

            $items[] = [
                'label'       => $meta['label'],
                'icon'        => preg_replace('/^ti\s+/', '', (string) $meta['icon']),
                'device'      => $device,
                'device_icon' => self::deviceIcon($row['user_agent'] ?? null),
                'ip_address'  => self::normalizeIp($row['ip_address']) ?: '—',
                'user_agent'  => (string) ($row['user_agent'] ?? ''),
                'created_at'  => date('M j, Y', strtotime($row['created_at'])) . ' • ' . date('g:i A', strtotime($row['created_at'])),
            ];
        }

        return $items;
    }

    // Get the client IP
    private static function clientIp(): ?string
    {
        return self::normalizeIp($_SERVER['REMOTE_ADDR'] ?? null);
    }

    // Map IPv6 localhost to IPv4
    private static function normalizeIp(?string $ip): ?string
    {
        return $ip === '::1' ? '127.0.0.1' : $ip;
    }

    // Detect device from user agent
    public static function detectDevice(?string $userAgent, ?string $modelHint = null): string
    {
        $ua = (string) $userAgent;
        if ($ua === '') {
            return 'Unknown device';
        }

        // Mobile UAs can match desktop OSes
        if (stripos($ua, 'Android') !== false) {
            // Use reported Android model
            $os = self::androidModel($ua, $modelHint) ?? 'Android';
        } elseif (stripos($ua, 'iPhone') !== false) {
            $os = 'iPhone';
        } elseif (stripos($ua, 'iPad') !== false) {
            $os = 'iPad';
        } elseif (stripos($ua, 'iPod') !== false) {
            $os = 'iPod';
        } elseif (stripos($ua, 'Windows') !== false) {
            $os = 'Windows';
        } elseif (preg_match('/Macintosh|Mac OS X/i', $ua)) {
            $os = 'macOS';
        } elseif (stripos($ua, 'CrOS') !== false) {
            $os = 'ChromeOS';
        } elseif (stripos($ua, 'Linux') !== false) {
            $os = 'Linux';
        } else {
            $os = 'Unknown';
        }

        // Check overlapping browser names first
        if (preg_match('/Edg(e|A|iOS)?\//i', $ua)) {
            $browser = 'Edge';
        } elseif (preg_match('/OPR\/|Opera/i', $ua)) {
            $browser = 'Opera';
        } elseif (preg_match('/Firefox\/|FxiOS\//i', $ua)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Chrome\/|CriOS\//i', $ua)) {
            $browser = 'Chrome';
        } elseif (stripos($ua, 'Safari/') !== false) {
            $browser = 'Safari';
        } else {
            $browser = 'Unknown';
        }

        if ($os === 'Unknown' && $browser === 'Unknown') {
            return 'Unknown device';
        }

        return mb_substr($os . ' (' . $browser . ')', 0, 60);
    }

    // Prefer client hint, then cookie
    private static function modelHint(): ?string
    {
        $raw = $_SERVER['HTTP_SEC_CH_UA_MODEL'] ?? $_COOKIE['sq_model'] ?? '';
        $raw = trim((string) $raw, " \t\n\r\0\x0B\"");

        // Sanitize the client model
        $clean = trim((string) preg_replace('/[^A-Za-z0-9 +\-_.()]/', '', $raw));

        return $clean === '' ? null : mb_substr($clean, 0, 40);
    }

    // Chrome reports "K"; prefer the model hint
    private static function androidModel(string $ua, ?string $modelHint): ?string
    {
        $model = trim((string) $modelHint, " \t\n\r\0\x0B\"");

        if ($model === '' && preg_match('/Android [\d.]+;([^)]*)\)/i', $ua, $match)) {
            // Parse models from older Android UAs
            foreach (array_reverse(explode(';', $match[1])) as $part) {
                $part = trim((string) preg_replace('/\s*Build\/.*$/i', '', $part));
                if ($part === '' || preg_match('/^(k|wv|[a-z]{2}(-[a-z]{2})?)$/i', $part)) {
                    continue;
                }
                $model = $part;
                break;
            }
        }

        if ($model === '' || strtolower($model) === 'k') {
            return null;
        }

        return self::friendlyModel($model);
    }

    // Format model names
    private static function friendlyModel(string $model): string
    {
        $model = trim($model);

        $known = [
            // Samsung model codes
            'SM-S928' => 'Samsung Galaxy S24 Ultra',
            'SM-S918' => 'Samsung Galaxy S23 Ultra',
            'SM-S916' => 'Samsung Galaxy S23+',
            'SM-S911' => 'Samsung Galaxy S23',
            'SM-G991' => 'Samsung Galaxy S21 5G',
            'SM-A556' => 'Samsung Galaxy A55 5G',
            'SM-A546' => 'Samsung Galaxy A54 5G',
            'SM-A356' => 'Samsung Galaxy A35 5G',
            // OPPO (full code)
            'CPH1803' => 'OPPO A3s',
            'CPH1853' => 'OPPO A3s',
        ];

        $upper = strtoupper($model);
        if (isset($known[$upper])) {
            return $known[$upper];
        }
        $key = substr($upper, 0, 7);
        if (isset($known[$key])) {
            return $known[$key];
        }

        // Add missing model brands
        if (preg_match('/^(Redmi|Mi|POCO)\b/i', $model)) {
            return 'Xiaomi ' . $model;
        }
        if (preg_match('/^(OPPO|realme|vivo|Infinix|TECNO|Xiaomi|Samsung|Google|OnePlus|HUAWEI|HONOR|Nokia|Motorola)\b/i', $model)) {
            return $model;
        }
        if (preg_match('/^Pixel\b/i', $model)) {
            return 'Google ' . $model;
        }

        // Match model code patterns
        if (preg_match('/^SM-[A-Z]\d{3}/i', $model)) {
            return 'Samsung ' . $model;
        }
        if (preg_match('/^CPH\d{4}/i', $model)) {
            return 'OPPO ' . $model;
        }
        if (preg_match('/^RMX\d{4}/i', $model)) {
            return 'realme ' . $model;
        }
        if (preg_match('/^V\d{4}/i', $model)) {
            return 'vivo ' . $model;
        }
        if (preg_match('/^\d{5,}[A-Z0-9]{3,}$/i', $model)) {
            return 'Xiaomi ' . $model;
        }

        return $model;
    }

    // Choose device icon
    public static function deviceIcon(?string $userAgent): string
    {
        $ua = (string) $userAgent;

        if (stripos($ua, 'iPad') !== false || (stripos($ua, 'Android') !== false && stripos($ua, 'Mobile') === false)) {
            return 'ti-device-tablet';
        }
        if (preg_match('/Android|iPhone|iPod|Mobile/i', $ua)) {
            return 'ti-device-mobile';
        }

        return 'ti-device-desktop';
    }

    // Check for activity.
    public function hasEntries(int $userId): bool
    {
        $statement = $this->db->prepare('SELECT 1 FROM activity_logs WHERE user_id = :user_id LIMIT 1');
        $statement->execute(['user_id' => $userId]);

        return (bool) $statement->fetchColumn();
    }

    // Clear user activity.
    public function clearForUser(int $userId): bool
    {
        $statement = $this->db->prepare('DELETE FROM activity_logs WHERE user_id = :user_id');

        return $statement->execute(['user_id' => $userId]);
    }

    // Format a relative time.
    public static function timeAgo(string $datetime): string
    {
        $seconds = time() - strtotime($datetime);

        if ($seconds < 60) {
            return 'Just now';
        }
        if ($seconds < 3600) {
            return (int) floor($seconds / 60) . 'm ago';
        }
        if ($seconds < 86400) {
            return (int) floor($seconds / 3600) . 'h ago';
        }
        if ($seconds < 7 * 86400) {
            return (int) floor($seconds / 86400) . 'd ago';
        }

        return date('M j', strtotime($datetime));
    }
}