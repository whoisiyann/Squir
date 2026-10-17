<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/UserPin.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../models/Vault.php';
require_once __DIR__ . '/../../includes/DocxBuilder.php';
require_once __DIR__ . '/../../includes/XlsxBuilder.php';

class SettingsController
{
    public function __construct(
        private PDO $db,
        private User $user,
        private UserPin $pinModel,
        private ActivityLog $activityLog
    ) {
    }

    // Update profile details
    public function updateProfile(int $userId, array $post): array
    {
        $fullName = trim((string) ($post['full_name'] ?? ''));
        $username = trim((string) ($post['username'] ?? ''));
        $errors = [];

        if ($fullName === '') {
            $errors['full_name'] = 'Full name is required.';
        } elseif (mb_strlen($fullName) > 100) {
            $errors['full_name'] = 'Full name must be 100 characters or fewer.';
        }

        if ($username === '') {
            $errors['username'] = 'Username is required.';
        } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
            $errors['username'] = 'Username must be 3-50 letters, numbers, dots, dashes, or underscores.';
        } elseif ($this->user->usernameExists($username, $userId)) {
            $errors['username'] = 'Username is already taken.';
        }

        if ($errors !== []) {
            return ['errors' => $errors];
        }

        $currentUser = $this->user->findById($userId);
        $this->user->updateProfile($userId, $fullName, $username);

        if ($currentUser && $currentUser['username'] !== $username) {
            $this->activityLog->log($userId, 'username_changed');
        }
        if ($currentUser && $currentUser['full_name'] !== $fullName) {
            $this->activityLog->log($userId, 'profile_updated');
        }

        return ['errors' => [], 'user' => $this->user->findById($userId)];
    }

    // Change account password
    public function changePassword(int $userId, array $post): array
    {
        $current = (string) ($post['current_password'] ?? '');
        $new = (string) ($post['new_password'] ?? '');
        $confirm = (string) ($post['confirm_password'] ?? '');
        $errors = [];

        $user = $this->user->findById($userId);
        if (!$user || $current === '' || !password_verify($current, $user['password_hash'])) {
            $errors['current_password'] = 'Your current password is incorrect.';
        }

        if (!preg_match('/^(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $new)) {
            $errors['new_password'] = 'Password must be at least 8 characters and include a number and a special character.';
        } elseif (isset($user['password_hash']) && password_verify($new, $user['password_hash'])) {
            $errors['new_password'] = 'Your new password must be different from your current password.';
        }

        if ($confirm === '' || $confirm !== $new) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if ($errors !== []) {
            return ['errors' => $errors];
        }

        $this->user->updatePassword($userId, password_hash($new, PASSWORD_DEFAULT));
        $this->activityLog->log($userId, 'password_changed');

        return ['errors' => []];
    }

    // Reset the vault PIN
    public function resetPin(int $userId, array $post): array
    {
        $current = trim((string) ($post['current_pin'] ?? ''));
        $new = trim((string) ($post['new_pin'] ?? ''));
        $confirm = trim((string) ($post['confirm_pin'] ?? ''));
        $length = UserPin::length();
        $errors = [];

        if (!preg_match('/^\d{' . $length . '}$/', $current)) {
            $errors['current_pin'] = 'Enter your current ' . $length . '-digit PIN.';
        }

        if (!preg_match('/^\d{' . $length . '}$/', $new)) {
            $errors['new_pin'] = 'Your new PIN must be exactly ' . $length . ' digits.';
        } elseif ($new === $current) {
            $errors['new_pin'] = 'Your new PIN must be different from your current PIN.';
        }

        if ($confirm === '' || $confirm !== $new) {
            $errors['confirm_pin'] = 'The two PINs do not match.';
        }

        if ($errors !== []) {
            return ['errors' => $errors];
        }

        $result = $this->pinModel->change($userId, $current, $new);
        if (!$result['ok']) {
            return ['errors' => ['current_pin' => $result['error'] ?? 'That PIN is incorrect.']];
        }

        $this->activityLog->log($userId, 'pin_updated');

        return ['errors' => []];
    }

    // Recent activity
    public function listActivity(int $userId, int $limit = 50): array
    {
        return $this->activityLog->listForUser($userId, $limit);
    }

    // Clear user activity log
    public function clearActivityLog(int $userId): bool
    {
        return $this->activityLog->clearForUser($userId);
    }

    // Delete the account and related data
    public function deleteAccount(int $userId): bool
    {
        return $this->user->delete($userId);
    }

    // Build the account export.
    public function exportData(int $userId): array
    {
        $vaultModel = new Vault($this->db);

        // Vault entries with their folder and tags
        $vaultStatement = $this->db->prepare(
            "SELECT v.title,
                    v.account_username,
                    v.account_password,
                    v.website_url,
                    v.notes,
                    v.created_at,
                    v.updated_at,
                    f.folder_name,
                    GROUP_CONCAT(t.tag_name ORDER BY t.tag_name SEPARATOR ',') AS tags
             FROM vault v
             LEFT JOIN folders    f  ON f.folder_id = v.folder_id
             LEFT JOIN vault_tags vt ON vt.vault_id = v.vault_id
             LEFT JOIN tags       t  ON t.tag_id    = vt.tag_id
             WHERE v.user_id = :uid
             GROUP BY v.vault_id, f.folder_name
             ORDER BY v.title ASC"
        );
        $vaultStatement->execute(['uid' => $userId]);

        $vaultOut = [];
        foreach ($vaultStatement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $vaultOut[] = [
                'title'       => $row['title'],
                'username'    => $row['account_username'],
                'password'    => $row['account_password'] !== null && $row['account_password'] !== ''
                    ? $vaultModel->decryptSecret($row['account_password'])
                    : null,
                'website_url' => $row['website_url'],
                'notes'       => $row['notes'],
                'tags'        => $row['tags'],
                'folder'      => $row['folder_name'],
                'created_at'  => $row['created_at'],
                'updated_at'  => $row['updated_at'],
            ];
        }

        // Notes with their folder
        $noteStatement = $this->db->prepare(
            'SELECT n.title, n.content, n.created_at, n.updated_at, f.folder_name AS folder
             FROM notes n
             LEFT JOIN folders f ON f.folder_id = n.folder_id
             WHERE n.user_id = :uid
             ORDER BY n.updated_at DESC'
        );
        $noteStatement->execute(['uid' => $userId]);

        $taskStatement = $this->db->prepare(
            'SELECT title, description, status, priority, due_date, created_at
             FROM tasks
             WHERE user_id = :uid
             ORDER BY created_at DESC'
        );
        $taskStatement->execute(['uid' => $userId]);

        // Include each folder's item count.
        $folderStatement = $this->db->prepare(
            'SELECT f.folder_name,
                    f.folder_type,
                    f.color,
                    COUNT(DISTINCT v.vault_id) + COUNT(DISTINCT n.note_id) AS item_count
             FROM folders f
             LEFT JOIN vault v ON v.folder_id = f.folder_id
             LEFT JOIN notes n ON n.folder_id = f.folder_id
             WHERE f.user_id = :uid
             GROUP BY f.folder_id, f.folder_name, f.folder_type, f.color
             ORDER BY f.folder_name ASC'
        );
        $folderStatement->execute(['uid' => $userId]);

        return [
            'exported_at' => date('c'),
            'vault'       => $vaultOut,
            'notes'       => $noteStatement->fetchAll(PDO::FETCH_ASSOC),
            'tasks'       => $taskStatement->fetchAll(PDO::FETCH_ASSOC),
            'folders'     => $folderStatement->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    // PDF support
    public function pdfAvailable(): bool
    {
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (is_readable($autoload)) {
            require_once $autoload;
        }

        return class_exists(\Dompdf\Dompdf::class);
    }

    // Docs support (composer require phpoffice/phpword)
    public function docxAvailable(): bool
    {
        return DocxBuilder::available();
    }

    // Excel support (needs the PHP zip extension)
    public function xlsxAvailable(): bool
    {
        return XlsxBuilder::available();
    }

    // Export formats the user can choose
    public const EXPORT_FORMATS = ['csv', 'xlsx', 'doc', 'pdf'];

    // Build the export file: returns [body, mime type, file extension]
    public function exportFile(int $userId, string $format): array
    {
        $data = $this->exportData($userId);
        $user = $this->user->findById($userId) ?: [];

        switch ($format) {
            case 'csv':
                $file = [$this->renderCsv($data), 'text/csv; charset=utf-8', 'csv'];
                break;

            case 'xlsx':
                // Excel file with one sheet per type and auto-fitted columns
                $file = [$this->renderXlsx($data), XlsxBuilder::MIME, 'xlsx'];
                break;

            case 'doc':
                // Real Word file (.docx)
                $file = [$this->renderDocx($data, $user), DocxBuilder::MIME, 'docx'];
                break;

            case 'pdf':
                $file = [$this->renderPdf($data, $user), 'application/pdf', 'pdf'];
                break;

            default:
                throw new InvalidArgumentException('Unsupported export format.');
        }

        $this->activityLog->log($userId, 'data_exported');

        return $file;
    }

    // Export PDF
    public function exportPdf(int $userId): string
    {
        return $this->exportFile($userId, 'pdf')[0];
    }

    private function renderPdf(array $data, array $user): string
    {
        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($this->renderExportHtml($data, $user, 'pdf'), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Add page numbers
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $footerY = $canvas->get_height() - 30;
        $canvas->page_text(40, $footerY, 'Squir - Confidential data export', $font, 8, [0.45, 0.45, 0.45]);
        $canvas->page_text($canvas->get_width() - 110, $footerY, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 8, [0.45, 0.45, 0.45]);

        return $dompdf->output();
    }

    // Write one CSV row per item.
    private function renderCsv(array $data): string
    {
        $statusLabels = ['todo' => 'To Do', 'in_progress' => 'In Progress', 'done' => 'Done'];
        $priorityLabels = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'];

        $rows = [[
            'Type', 'Title', 'Folder', 'Username', 'Password', 'Website', 'Tags',
            'Content', 'Status', 'Priority', 'Due Date', 'Items', 'Created', 'Updated',
        ]];

        foreach ($data['vault'] as $item) {
            $rows[] = [
                'Vault', $item['title'], $item['folder'], $item['username'], $item['password'],
                $item['website_url'], str_replace(',', ', ', (string) $item['tags']),
                $item['notes'], '', '', '', '', $item['created_at'], $item['updated_at'],
            ];
        }

        foreach ($data['notes'] as $note) {
            $rows[] = [
                'Note', $note['title'], $note['folder'], '', '', '', '',
                $this->plainText($note['content']), '', '', '', '', $note['created_at'], $note['updated_at'],
            ];
        }

        foreach ($data['tasks'] as $task) {
            $rows[] = [
                'Task', $task['title'], '', '', '', '', '',
                $task['description'],
                $statusLabels[$task['status']] ?? $task['status'],
                $priorityLabels[$task['priority']] ?? $task['priority'],
                $task['due_date'], '', $task['created_at'], '',
            ];
        }

        foreach ($data['folders'] as $folder) {
            $rows[] = [
                'Folder', $folder['folder_name'], '', '', '', '', '',
                ucfirst((string) $folder['folder_type']) . ' folder (' . $folder['color'] . ')',
                '', '', '', $folder['item_count'], '', '',
            ];
        }

        $out = fopen('php://temp', 'w+');
        fwrite($out, "\xEF\xBB\xBF"); // Help Excel read UTF-8 correctly.
        foreach ($rows as $row) {
            fputcsv($out, array_map([self::class, 'csvCell'], $row), ',', '"', '\\');
        }
        rewind($out);
        $csv = (string) stream_get_contents($out);
        fclose($out);

        return $csv;
    }

    // Build the Excel export: one sheet each for vault, notes, tasks, and folders.
    private function renderXlsx(array $data): string
    {
        $statusLabels = ['todo' => 'To Do', 'in_progress' => 'In Progress', 'done' => 'Done'];
        $priorityLabels = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'];

        $vault = [];
        foreach ($data['vault'] as $item) {
            $vault[] = [
                (string) $item['title'],
                (string) ($item['folder'] ?? ''),
                (string) ($item['username'] ?? ''),
                (string) ($item['password'] ?? ''),
                (string) ($item['website_url'] ?? ''),
                str_replace(',', ', ', (string) ($item['tags'] ?? '')),
                (string) ($item['notes'] ?? ''),
                (string) ($item['created_at'] ?? ''),
                (string) ($item['updated_at'] ?? ''),
            ];
        }

        $notes = [];
        foreach ($data['notes'] as $note) {
            $notes[] = [
                (string) $note['title'],
                (string) ($note['folder'] ?? ''),
                $this->plainText($note['content']),
                (string) ($note['created_at'] ?? ''),
                (string) ($note['updated_at'] ?? ''),
            ];
        }

        $tasks = [];
        foreach ($data['tasks'] as $task) {
            $tasks[] = [
                (string) $task['title'],
                (string) ($task['description'] ?? ''),
                $statusLabels[$task['status']] ?? (string) $task['status'],
                $priorityLabels[$task['priority']] ?? (string) $task['priority'],
                (string) ($task['due_date'] ?? ''),
                (string) ($task['created_at'] ?? ''),
            ];
        }

        $folders = [];
        foreach ($data['folders'] as $folder) {
            $folders[] = [
                (string) $folder['folder_name'],
                ucfirst((string) $folder['folder_type']),
                (string) ($folder['color'] ?? ''),
                (string) $folder['item_count'],
            ];
        }

        return XlsxBuilder::buildWorkbook([
            ['name' => 'Vault',   'header' => ['Title', 'Folder', 'Username', 'Password', 'Website', 'Tags', 'Notes', 'Created', 'Updated'], 'rows' => $vault],
            ['name' => 'Notes',   'header' => ['Title', 'Folder', 'Content', 'Created', 'Updated'], 'rows' => $notes],
            ['name' => 'Tasks',   'header' => ['Title', 'Description', 'Status', 'Priority', 'Due Date', 'Created'], 'rows' => $tasks],
            ['name' => 'Folders', 'header' => ['Folder', 'Type', 'Color', 'Items'], 'rows' => $folders, 'numeric' => [3]],
        ]);
    }

    // Prefix formula-like values to prevent CSV injection.
    private static function csvCell(mixed $value): string
    {
        $text = (string) ($value ?? '');
        if ($text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            $text = "'" . $text;
        }

        return $text;
    }

    // Convert note HTML to plain text.
    private function plainText(?string $html): string
    {
        $text = preg_replace('~<li(\s[^>]*)?>~i', '- ', (string) $html);
        $text = preg_replace('~<br(\s[^>]*)?/?>~i', "\n", (string) $text);
        $text = preg_replace('~</(p|div|li|h[1-6]|ul|ol|blockquote)>~i', "\n", (string) $text);
        $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xC2\xA0", ' ', $text);

        return trim((string) preg_replace('/\n{3,}/', "\n\n", $text));
    }

    // Format a date for the Docs export.
    private function docDate(mixed $value, string $format = 'M j, Y g:i A'): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $time = strtotime((string) $value);

        return $time === false ? '' : date($format, $time);
    }

    // Build the Docs export as a real .docx file.
    private function renderDocx(array $data, array $user): string
    {
        $brown = '6B3F2A';
        $muted = '8A7D75';
        $soft = '6F625B';
        $head = 'F5EDE6';

        $statusLabels = ['todo' => 'To Do', 'in_progress' => 'In Progress', 'done' => 'Done'];
        $priorityLabels = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'];
        $folderTypeLabels = ['passwords' => 'Passwords', 'notes' => 'Notes'];

        $vault = $data['vault'] ?? [];
        $notes = $data['notes'] ?? [];
        $tasks = $data['tasks'] ?? [];
        $folders = $data['folders'] ?? [];

        $exportedAt = $this->docDate($data['exported_at'] ?? null, 'F j, Y \a\t g:i A');
        $ownerName = trim((string) ($user['full_name'] ?? ''));
        $ownerEmail = trim((string) ($user['email'] ?? ''));

        $doc = new DocxBuilder(false, 'Squir - Confidential data export');

        $doc->heading('Squir Data Export', 1);

        $subtitle = $exportedAt;
        if ($ownerName !== '') {
            $subtitle = 'Prepared for ' . $ownerName . ($ownerEmail !== '' ? ' (' . $ownerEmail . ')' : '')
                . ($exportedAt !== '' ? ' · ' . $exportedAt : '');
        }
        $doc->paragraph($subtitle, ['color' => $soft, 'size' => 17, 'after' => 160]);

        // Warning box
        $doc->table([[[
            'shade' => 'FBF1E3',
            'paras' => [[
                ['Keep this file safe.', ['bold' => true, 'color' => $brown]],
                [' It contains your saved passwords in plain text. Store it somewhere private and delete it when you no longer need it.', ['color' => $brown]],
            ]],
        ]]], [100], ['border' => 'E0C39A', 'after' => 200]);

        // Summary counts
        $summary = [];
        foreach ([
            [count($vault), 'Vault entries'],
            [count($notes), 'Notes'],
            [count($tasks), 'Tasks'],
            [count($folders), 'Folders'],
        ] as [$number, $label]) {
            $summary[] = [
                'align' => 'center',
                'paras' => [
                    [[(string) $number, ['bold' => true, 'color' => $brown, 'size' => 30]]],
                    [[$label, ['color' => $soft, 'size' => 16]]],
                ],
            ];
        }
        $doc->table([$summary], [25, 25, 25, 25]);

        // Password vault
        $doc->heading('Password Vault', 2);
        if ($vault === []) {
            $doc->paragraph('No vault entries to export.', ['italic' => true, 'color' => $muted]);
        }
        foreach ($vault as $item) {
            $title = trim((string) ($item['title'] ?? ''));
            $tagList = trim((string) ($item['tags'] ?? ''));
            $tagList = $tagList === '' ? '' : implode(', ', explode(',', $tagList));

            $rows = [[[
                'text' => $title !== '' ? $title : 'Untitled entry',
                'span' => 2, 'shade' => $head, 'bold' => true, 'color' => $brown, 'size' => 20,
            ]]];

            $fields = [];
            if (($item['username'] ?? '') !== '') {
                $fields[] = ['Username', (string) $item['username'], false];
            }
            if (($item['password'] ?? '') !== '') {
                $fields[] = ['Password', (string) $item['password'], true];
            }
            if (!empty($item['website_url'])) {
                $fields[] = ['Website', (string) $item['website_url'], false];
            }
            if (!empty($item['folder'])) {
                $fields[] = ['Folder', (string) $item['folder'], false];
            }
            if ($tagList !== '') {
                $fields[] = ['Tags', $tagList, false];
            }
            if (!empty($item['notes'])) {
                $fields[] = ['Notes', (string) $item['notes'], false];
            }

            foreach ($fields as [$label, $value, $mono]) {
                $rows[] = [
                    ['text' => $label, 'color' => $soft],
                    ['text' => $value, 'mono' => $mono],
                ];
            }

            $doc->table($rows, [22, 78], ['after' => 120]);
        }

        // Notes
        $doc->heading('Notes', 2);
        if ($notes === []) {
            $doc->paragraph('No notes to export.', ['italic' => true, 'color' => $muted]);
        }
        foreach ($notes as $note) {
            $title = trim((string) ($note['title'] ?? ''));
            $created = $this->docDate($note['created_at'] ?? null);
            $updated = $this->docDate($note['updated_at'] ?? null);
            $body = $this->plainText($note['content'] ?? null);

            $meta = [];
            if ($created !== '') {
                $meta[] = 'Created ' . $created;
            }
            if ($updated !== '' && $updated !== $created) {
                $meta[] = 'Updated ' . $updated;
            }
            if (!empty($note['folder'])) {
                $meta[] = 'Folder: ' . $note['folder'];
            }

            $doc->paragraph($title !== '' ? $title : 'Untitled note', ['bold' => true, 'size' => 21, 'keepNext' => true, 'before' => 160, 'after' => 20]);
            if ($meta !== []) {
                $doc->paragraph(implode(' · ', $meta), ['color' => $muted, 'size' => 15, 'keepNext' => true, 'after' => 60]);
            }
            if ($body !== '') {
                $doc->paragraph($body, ['after' => 120]);
            } else {
                $doc->paragraph('(No content)', ['color' => $muted, 'after' => 120]);
            }
        }

        // Tasks
        $doc->heading('Tasks', 2);
        if ($tasks === []) {
            $doc->paragraph('No tasks to export.', ['italic' => true, 'color' => $muted]);
        } else {
            $head1 = ['shade' => $head, 'bold' => true, 'color' => $brown];
            $rows = [[
                $head1 + ['text' => 'Task'],
                $head1 + ['text' => 'Status'],
                $head1 + ['text' => 'Priority'],
                $head1 + ['text' => 'Due date'],
            ]];

            foreach ($tasks as $task) {
                $status = (string) ($task['status'] ?? '');
                $priority = (string) ($task['priority'] ?? '');
                $due = $this->docDate($task['due_date'] ?? null, 'M j, Y');

                $paras = [[[(string) ($task['title'] ?? ''), ['bold' => true]]]];
                if (!empty($task['description'])) {
                    $paras[] = [[(string) $task['description'], ['color' => $soft, 'size' => 16]]];
                }

                $rows[] = [
                    ['paras' => $paras],
                    ['text' => $statusLabels[$status] ?? $status],
                    ['text' => $priorityLabels[$priority] ?? $priority],
                    ['text' => $due !== '' ? $due : '-'],
                ];
            }

            $doc->table($rows, [50, 16, 14, 20], ['headerRows' => 1]);
        }

        // Folders
        $doc->heading('Folders', 2);
        if ($folders === []) {
            $doc->paragraph('No folders to export.', ['italic' => true, 'color' => $muted]);
        } else {
            $head1 = ['shade' => $head, 'bold' => true, 'color' => $brown];
            $rows = [[
                $head1 + ['text' => 'Folder'],
                $head1 + ['text' => 'Type'],
                $head1 + ['text' => 'Color'],
                $head1 + ['text' => 'Items'],
            ]];

            foreach ($folders as $folder) {
                $type = (string) ($folder['folder_type'] ?? '');
                $rows[] = [
                    ['text' => (string) ($folder['folder_name'] ?? '')],
                    ['text' => $folderTypeLabels[$type] ?? $type],
                    ['text' => (string) ($folder['color'] ?? '')],
                    ['text' => (string) (int) ($folder['item_count'] ?? 0)],
                ];
            }

            $doc->table($rows, [40, 20, 20, 20], ['headerRows' => 1]);
        }

        return $doc->build();
    }

    // Render the PDF view.
    private function renderExportHtml(array $data, array $user, string $format = 'pdf'): string
    {
        ob_start();
        require __DIR__ . '/../views/settings/export-pdf.php';

        return (string) ob_get_clean();
    }
}