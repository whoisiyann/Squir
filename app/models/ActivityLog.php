<?php

class ActivityLog
{
    // Known action codes -> display label / default detail line / icon
    private const META = [
        'vault_unlocked'   => ['label' => 'Vault unlocked',              'detail' => 'Master password verified',       'icon' => 'ti-lock',          'entity' => 'vault'],
        'vault_created'    => ['label' => 'Credential saved',            'detail' => null,                             'icon' => 'ti-key',           'entity' => 'vault'],
        'vault_updated'    => ['label' => 'Credential updated',          'detail' => null,                             'icon' => 'ti-key',           'entity' => 'vault'],
        'vault_deleted'    => ['label' => 'Credential deleted',          'detail' => null,                             'icon' => 'ti-key',           'entity' => 'vault'],
        'folder_created'   => ['label' => 'Credential folder created',   'detail' => null,                             'icon' => 'ti-folder',        'entity' => 'folder'],
        'folder_renamed'   => ['label' => 'Folder renamed',              'detail' => null,                             'icon' => 'ti-folder',        'entity' => 'folder'],
        'folder_deleted'   => ['label' => 'Folder deleted',              'detail' => null,                             'icon' => 'ti-folder',        'entity' => 'folder'],
        'note_created'     => ['label' => 'Note created',                'detail' => null,                             'icon' => 'ti-notes',         'entity' => 'note'],
        'note_updated'     => ['label' => 'Note updated',                'detail' => null,                             'icon' => 'ti-notes',         'entity' => 'note'],
        'note_deleted'     => ['label' => 'Note deleted',                'detail' => null,                             'icon' => 'ti-notes',         'entity' => 'note'],
        'task_created'     => ['label' => 'Task created',                'detail' => null,                             'icon' => 'ti-checkbox',      'entity' => 'task'],
        'task_updated'     => ['label' => 'Task updated',                'detail' => null,                             'icon' => 'ti-checkbox',      'entity' => 'task'],
        'task_deleted'     => ['label' => 'Task deleted',                'detail' => null,                             'icon' => 'ti-checkbox',      'entity' => 'task'],
        'profile_updated'  => ['label' => 'Profile updated',             'detail' => 'Account information changed',    'icon' => 'ti-user',          'entity' => 'user'],
        'password_changed' => ['label' => 'Password changed',            'detail' => 'Account password updated',       'icon' => 'ti-shield-check',  'entity' => 'user'],
        'pin_updated'      => ['label' => 'PIN updated',                 'detail' => 'Vault PIN changed',              'icon' => 'ti-lock-password', 'entity' => 'user'],
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
