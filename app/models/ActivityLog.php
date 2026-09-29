<?php

class ActivityLog
{
    // Activity metadata
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
        'user_updated'     => ['label' => 'Updated a user',              'detail' => null,                             'icon' => 'ti-user',          'entity' => 'user'],
        'user_activated'   => ['label' => 'Activated a user',            'detail' => null,                             'icon' => 'ti-user-check',    'entity' => 'user'],
        'user_deactivated' => ['label' => 'Deactivated a user',          'detail' => null,                             'icon' => 'ti-user-off',      'entity' => 'user'],
        'user_suspended'   => ['label' => 'Suspended a user',            'detail' => null,                             'icon' => 'ti-user-off',      'entity' => 'user'],
        'user_deleted'     => ['label' => 'Deleted a user',              'detail' => null,                             'icon' => 'ti-user-x',        'entity' => 'user'],
        'user_force_logout'=> ['label' => 'Forced a user logout',        'detail' => null,                             'icon' => 'ti-logout',        'entity' => 'user'],
        'logs_cleared'     => ['label' => 'Cleared activity logs',       'detail' => null,                             'icon' => 'ti-trash',         'entity' => 'system'],
        'admin_profile_updated'  => ['label' => 'Updated profile',       'detail' => null,                             'icon' => 'ti-user',          'entity' => 'admin'],
        'admin_email_changed'    => ['label' => 'Updated email',         'detail' => null,                             'icon' => 'ti-mail',          'entity' => 'admin'],
        'admin_password_changed' => ['label' => 'Changed password',      'detail' => null,                             'icon' => 'ti-shield-check',  'entity' => 'admin'],
        'admin_password_reset'   => ['label' => 'Reset password',        'detail' => null,                             'icon' => 'ti-key',           'entity' => 'admin'],
        'admin_logs_exported'    => ['label' => 'Exported activity logs', 'detail' => null,                            'icon' => 'ti-download',      'entity' => 'system'],
    ];

    // Action groups
    private const CATEGORIES = [
        'auth'      => ['label' => 'Sign in & out',   'actions' => ['logged_in', 'logged_out']],
        'vault'     => ['label' => 'Vault',           'actions' => ['vault_unlocked', 'vault_created', 'vault_updated', 'vault_deleted', 'password_viewed', 'password_copied']],
        'notes'     => ['label' => 'Notes',           'actions' => ['note_created', 'note_updated', 'note_deleted']],
        'tasks'     => ['label' => 'Tasks',           'actions' => ['task_created', 'task_updated', 'task_deleted', 'task_completed', 'task_reopened']],
        'folders'   => ['label' => 'Folders',         'actions' => ['folder_created', 'folder_renamed', 'folder_deleted']],
        'favorites' => ['label' => 'Favorites',       'actions' => ['favorite_added', 'favorite_removed']],
        'account'   => ['label' => 'Account',         'actions' => ['profile_updated', 'username_changed', 'email_changed', 'password_changed', 'pin_updated', 'data_exported', 'activity_cleared']],
    ];

    // Activity labels
    private const ADMIN_LABELS = [
        'logged_in'         => 'Log in',
        'logged_out'        => 'Log out',
        'admin_logged_in'   => 'Admin log in',
        'admin_logged_out'  => 'Admin log out',
        'favorite_added'    => 'Added to favorites',
        'favorite_removed'  => 'Removed from favorites',
        'user_created'      => 'Account created',
        'user_updated'      => 'Account updated',
        'user_activated'    => 'Activated',
        'user_deactivated'  => 'Deactivated',
        'user_suspended'    => 'Suspended',
        'user_deleted'      => 'Deleted',
        'user_force_logout' => 'Forced logout',
    ];

    // Activity column tones
    private const TONES = [
        'danger'  => ['user_suspended', 'user_deleted', 'user_deactivated', 'user_force_logout', 'vault_deleted', 'note_deleted', 'task_deleted', 'folder_deleted', 'activity_cleared'],
        'warning' => ['favorite_added', 'favorite_removed', 'password_viewed', 'password_copied', 'vault_unlocked', 'pin_updated', 'password_changed', 'data_exported'],
        'info'    => ['logged_in', 'logged_out', 'admin_logged_in', 'admin_logged_out'],
        'success' => ['user_created', 'user_activated', 'vault_created', 'note_created', 'task_created', 'task_completed', 'folder_created'],
    ];

    public function __construct(private PDO $db)
    {
    }

    // Log activity
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

    // Log admin activity
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
             WHERE user_id = :user_id AND user_cleared_at IS NULL
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

    // Admin log helpers

    // Label for the Activity column / dropdown.
    public static function adminLabel(string $action): string
    {
        if (isset(self::ADMIN_LABELS[$action])) {
            return self::ADMIN_LABELS[$action];
        }

        return self::META[$action]['label'] ?? ucfirst(str_replace('_', ' ', $action));
    }

    // Action color group.
    public static function tone(string $action): string
    {
        foreach (self::TONES as $tone => $actions) {
            if (in_array($action, $actions, true)) {
                return $tone;
            }
        }

        return 'neutral';
    }

    // Action groups.
    public static function actionGroups(): array
    {
        $groups = [];
        foreach (self::CATEGORIES as $key => $category) {
            $actions = [];
            foreach ($category['actions'] as $action) {
                $actions[$action] = self::adminLabel($action);
            }
            $groups[$key] = ['label' => $category['label'], 'actions' => $actions];
        }

        return $groups;
    }

    public static function isKnownAction(string $action): bool
    {
        foreach (self::CATEGORIES as $category) {
            if (in_array($action, $category['actions'], true)) {
                return true;
            }
        }

        return false;
    }

    public static function isKnownCategory(string $key): bool
    {
        return isset(self::CATEGORIES[$key]);
    }

    // Show the IP the same way as the rest of the admin pages.
    public static function displayIp(?string $ip): string
    {
        $ip = self::normalizeIp($ip);

        return $ip !== null && $ip !== '' ? $ip : '—';
    }

    // Build shared filters.
    // Supported filters: action, dates, search, and user ID.
    private function adminWhere(array $filters, array &$params): string
    {
        // Users only. Admin activity will get its own page.
        $where = ['al.user_id IS NOT NULL', 'al.admin_id IS NULL'];

        $action = (string) ($filters['action'] ?? 'all');
        if (str_starts_with($action, 'cat:')) {
            $key = substr($action, 4);
            if (isset(self::CATEGORIES[$key])) {
                $names = [];
                foreach (self::CATEGORIES[$key]['actions'] as $i => $code) {
                    $names[] = ':cat' . $i;
                    $params['cat' . $i] = $code;
                }
                $where[] = 'al.action IN (' . implode(', ', $names) . ')';
            }
        } elseif ($action !== '' && $action !== 'all') {
            $where[] = 'al.action = :action';
            $params['action'] = $action;
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'al.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            // Include the whole end day.
            $where[] = 'al.created_at < :date_to';
            $params['date_to'] = date('Y-m-d', strtotime($filters['date_to'] . ' +1 day')) . ' 00:00:00';
        }

        if (!empty($filters['user_id'])) {
            $where[] = 'al.user_id = :user_id';
            $params['user_id'] = (int) $filters['user_id'];
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            // Escape LIKE wildcards.
            $like = '%' . addcslashes($q, '\\%_') . '%';
            $columns = ['u.full_name', 'u.username', 'u.email'];
            $parts = [];
            foreach ($columns as $i => $column) {
                $parts[] = $column . ' LIKE :q' . $i;
                $params['q' . $i] = $like;
            }
            $where[] = '(' . implode(' OR ', $parts) . ')';
        }

        return 'WHERE ' . implode(' AND ', $where);
    }

    // Search admin logs.
    public function searchForAdmin(array $filters, int $limit = 30, int $offset = 0): array
    {
        $params = [];
        $where = $this->adminWhere($filters, $params);

        $statement = $this->db->prepare(
            "SELECT al.log_id, al.action, al.description, al.ip_address, al.device, al.user_agent, al.created_at,
                    al.user_id, al.admin_id,
                    u.full_name AS user_name
             FROM activity_logs al
             LEFT JOIN users  u ON u.user_id  = al.user_id
             $where
             ORDER BY al.created_at DESC, al.log_id DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $name => $value) {
            $statement->bindValue(':' . $name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    // Total rows for the same filters.
    public function countForAdmin(array $filters): int
    {
        $params = [];
        $where = $this->adminWhere($filters, $params);

        $statement = $this->db->prepare(
            "SELECT COUNT(*)
             FROM activity_logs al
             LEFT JOIN users  u ON u.user_id  = al.user_id
             $where"
        );
        foreach ($params as $name => $value) {
            $statement->bindValue(':' . $name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    // The signed-in admin's own recent activity.
    public function listForAdminActor(int $adminId, int $limit = 50): array
    {
        $statement = $this->db->prepare(
            'SELECT action, description, created_at
             FROM activity_logs
             WHERE admin_id = :admin_id AND user_cleared_at IS NULL
             ORDER BY created_at DESC, log_id DESC
             LIMIT :limit'
        );
        $statement->bindValue(':admin_id', $adminId, PDO::PARAM_INT);
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
                'detail'     => (string) $row['description'],
                'icon'       => $meta['icon'],
                'time_label' => self::timeAgo($row['created_at']),
            ];
        }

        return $items;
    }

    // Hide the admin's own activity from their Activity Log page.
    // Rows are kept, so the export still includes them.
    public function clearForAdminActor(int $adminId): bool
    {
        $statement = $this->db->prepare(
            'UPDATE activity_logs SET user_cleared_at = NOW() WHERE admin_id = :admin_id AND user_cleared_at IS NULL'
        );

        return $statement->execute(['admin_id' => $adminId]);
    }

    // One batch of logs for the Settings export.
    // $userOnly = true: user activity only. false: users and admins.
    public function exportBatch(bool $userOnly, int $limit, int $offset): array
    {
        $where = $userOnly ? 'WHERE al.user_id IS NOT NULL AND al.admin_id IS NULL' : '';

        $statement = $this->db->prepare(
            "SELECT al.log_id, al.action, al.description, al.ip_address, al.device, al.user_agent, al.created_at,
                    al.user_id, al.admin_id,
                    u.full_name AS user_name,
                    a.full_name AS admin_name
             FROM activity_logs al
             LEFT JOIN users  u ON u.user_id  = al.user_id
             LEFT JOIN admins a ON a.admin_id = al.admin_id
             $where
             ORDER BY al.created_at DESC, al.log_id DESC
             LIMIT :limit OFFSET :offset"
        );
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    // Date ranges the admin can clear.
    public static function clearRanges(): array
    {
        return [
            'today'     => 'Today',
            'yesterday' => 'Yesterday',
            'last7'     => 'Last 7 days',
            'last30'    => 'Last 30 days',
            'older30'   => 'Older than 30 days',
            'all'       => 'All logs',
        ];
    }

    // Permanently delete user logs in a date range. Returns the number deleted.
    public function clearForAdmin(string $range): int
    {
        // Same base filter as the admin list: user activity only.
        $where = ['user_id IS NOT NULL', 'admin_id IS NULL'];
        $params = [];
        $startOfDay = static fn (int $daysAgo): string => date('Y-m-d', strtotime('-' . $daysAgo . ' day')) . ' 00:00:00';

        switch ($range) {
            case 'today':
                $where[] = 'created_at >= :from';
                $params['from'] = $startOfDay(0);
                break;
            case 'yesterday':
                $where[] = 'created_at >= :from';
                $where[] = 'created_at < :to';
                $params['from'] = $startOfDay(1);
                $params['to'] = $startOfDay(0);
                break;
            case 'last7':
                $where[] = 'created_at >= :from';
                $params['from'] = $startOfDay(6);
                break;
            case 'last30':
                $where[] = 'created_at >= :from';
                $params['from'] = $startOfDay(29);
                break;
            case 'older30':
                $where[] = 'created_at < :to';
                $params['to'] = $startOfDay(29);
                break;
            case 'all':
                break;
            default:
                throw new InvalidArgumentException('Unknown date range.');
        }

        $statement = $this->db->prepare('DELETE FROM activity_logs WHERE ' . implode(' AND ', $where));
        $statement->execute($params);

        return $statement->rowCount();
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

        // Browser names
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

    // Activity check
    public function hasEntries(int $userId): bool
    {
        $statement = $this->db->prepare('SELECT 1 FROM activity_logs WHERE user_id = :user_id AND user_cleared_at IS NULL LIMIT 1');
        $statement->execute(['user_id' => $userId]);

        return (bool) $statement->fetchColumn();
    }

    // Hide activity from the user.
    public function clearForUser(int $userId): bool
    {
        $statement = $this->db->prepare(
            'UPDATE activity_logs SET user_cleared_at = NOW() WHERE user_id = :user_id AND user_cleared_at IS NULL'
        );

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