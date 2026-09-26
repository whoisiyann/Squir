<?php

class ActivityLog
{
    // Known action codes -> display label / default detail line / icon
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
    ];

    public function __construct(private PDO $db)
    {
    }

    // Record an activity entry for a user
    public function log(int $userId, string $action, ?string $description = null, ?string $entityType = null, ?int $entityId = null): void
    {
        $meta = self::META[$action] ?? null;

        $statement = $this->db->prepare(
            'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent)
             VALUES (:user_id, :action, :entity_type, :entity_id, :description, :ip_address, :user_agent)'
        );

        $statement->execute([
            'user_id'     => $userId,
            'action'      => $action,
            'entity_type' => $entityType ?? ($meta['entity'] ?? null),
            'entity_id'   => $entityId,
            'description' => $description ?? ($meta['detail'] ?? $meta['label'] ?? $action),
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent'  => isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
        ]);
    }

    // Load recent activity for a user, formatted for display
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

    // Whether a user has any activity recorded
    public function hasEntries(int $userId): bool
    {
        $statement = $this->db->prepare('SELECT 1 FROM activity_logs WHERE user_id = :user_id LIMIT 1');
        $statement->execute(['user_id' => $userId]);

        return (bool) $statement->fetchColumn();
    }

    // Remove all activity entries for a user
    public function clearForUser(int $userId): bool
    {
        $statement = $this->db->prepare('DELETE FROM activity_logs WHERE user_id = :user_id');

        return $statement->execute(['user_id' => $userId]);
    }

    // Format a timestamp as a short relative label ("15m ago", "2d ago", ...)
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
