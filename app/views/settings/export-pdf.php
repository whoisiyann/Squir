<?php
/** @var array $data Export data */
/** @var array $user User data */

$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// Format a date for display
$fmt = static function ($value, string $format = 'M j, Y g:i A'): string {
    if ($value === null || $value === '') {
        return '';
    }

    $time = strtotime((string) $value);

    return $time === false ? '' : date($format, $time);
};

// Convert note HTML to plain text
$noteText = static function (?string $html): string {
    $html = (string) $html;
    if ($html === '') {
        return '';
    }

    $text = preg_replace('~<li(\s[^>]*)?>~i', '• ', $html);
    $text = preg_replace('~<br(\s[^>]*)?/?>~i', "\n", (string) $text);
    $text = preg_replace('~</(p|div|li|h[1-6]|ul|ol|blockquote)>~i', "\n", (string) $text);
    $text = strip_tags((string) $text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace("\xC2\xA0", ' ', $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);

    return trim((string) $text);
};

$statusLabels = ['todo' => 'To Do', 'in_progress' => 'In Progress', 'done' => 'Done'];
$priorityLabels = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'];
$folderTypeLabels = ['passwords' => 'Passwords', 'notes' => 'Notes'];

$vault = $data['vault'] ?? [];
$notes = $data['notes'] ?? [];
$tasks = $data['tasks'] ?? [];
$folders = $data['folders'] ?? [];

$exportedAt = $fmt($data['exported_at'] ?? null, 'F j, Y \a\t g:i A');
$ownerName = trim((string) ($user['full_name'] ?? ''));
$ownerEmail = trim((string) ($user['email'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Squir Data Export</title>
<style>
    @page { margin: 48px 40px 60px 40px; }

    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 8.5pt;
        line-height: 1.45;
        color: #2b2320;
    }

    h1 { font-size: 20pt; color: #6b3f2a; margin: 0 0 4px 0; }
    h2 {
        font-size: 12.5pt;
        color: #6b3f2a;
        margin: 26px 0 10px 0;
        padding-bottom: 4px;
        border-bottom: 2px solid #6b3f2a;
    }

    .subtitle { color: #6f625b; font-size: 8.5pt; margin: 0 0 14px 0; }

    .warning {
        background: #fbf1e3;
        border: 1px solid #e0c39a;
        color: #6b3f2a;
        padding: 8px 10px;
        margin: 0 0 14px 0;
    }

    .muted { color: #8a7d75; }
    .empty { color: #8a7d75; font-style: italic; margin: 4px 0 0 0; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; }

    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    td, th { word-wrap: break-word; vertical-align: top; }

    /* Summary */
    .summary td { border: 1px solid #e6dcd3; padding: 7px 10px; text-align: center; }
    .summary .count { font-size: 15pt; font-weight: bold; color: #6b3f2a; }
    .summary .name { color: #6f625b; font-size: 8pt; }

    /* Vault entries */
    .card { margin: 0 0 10px 0; border: 1px solid #e6dcd3; page-break-inside: avoid; }
    .card-title {
        background: #f5ede6;
        color: #6b3f2a;
        font-size: 9.5pt;
        font-weight: bold;
        padding: 6px 10px;
    }
    .card td { padding: 4px 10px; border-top: 1px solid #f0e8e1; }
    .card td.label { width: 22%; color: #6f625b; }

    /* Notes */
    .note { margin: 0 0 14px 0; padding: 0 0 10px 0; border-bottom: 1px solid #e6dcd3; }
    .note-title { font-size: 10.5pt; font-weight: bold; color: #2b2320; margin: 0 0 2px 0; }
    .note-meta { font-size: 7.5pt; color: #8a7d75; margin: 0 0 6px 0; }
    .note-body { word-wrap: break-word; }

    /* Tasks and folders */
    .grid th {
        background: #f5ede6;
        color: #6b3f2a;
        text-align: left;
        padding: 6px 8px;
        border: 1px solid #e6dcd3;
    }
    .grid td { padding: 6px 8px; border: 1px solid #e6dcd3; }
    .grid tr { page-break-inside: avoid; }
    .task-desc { color: #6f625b; font-size: 8pt; margin: 2px 0 0 0; }
</style>
</head>
<body>

<h1>Squir Data Export</h1>
<p class="subtitle">
    <?php if ($ownerName !== ''): ?>
        Prepared for <?= $e($ownerName) ?><?= $ownerEmail !== '' ? ' (' . $e($ownerEmail) . ')' : '' ?>
        <?= $exportedAt !== '' ? ' &middot; ' : '' ?>
    <?php endif; ?>
    <?= $e($exportedAt) ?>
</p>

<div class="warning">
    <strong>Keep this file safe.</strong> It contains your saved passwords in plain text.
    Store it somewhere private and delete it when you no longer need it.
</div>

<table class="summary">
    <tr>
        <td><div class="count"><?= count($vault) ?></div><div class="name">Vault entries</div></td>
        <td><div class="count"><?= count($notes) ?></div><div class="name">Notes</div></td>
        <td><div class="count"><?= count($tasks) ?></div><div class="name">Tasks</div></td>
        <td><div class="count"><?= count($folders) ?></div><div class="name">Folders</div></td>
    </tr>
</table>

<h2>Password Vault</h2>
<?php if ($vault === []): ?>
    <p class="empty">No vault entries to export.</p>
<?php endif; ?>
<?php foreach ($vault as $item): ?>
    <?php
    $title = trim((string) ($item['title'] ?? ''));
    $tagList = trim((string) ($item['tags'] ?? ''));
    $tagList = $tagList === '' ? '' : implode(', ', explode(',', $tagList));

    // Field values are already escaped
    $fields = [];
    if (($item['username'] ?? '') !== '') {
        $fields[] = ['Username', $e($item['username']), ''];
    }
    if (($item['password'] ?? '') !== '') {
        $fields[] = ['Password', $e($item['password']), 'mono'];
    }
    if (!empty($item['website_url'])) {
        $fields[] = ['Website', $e($item['website_url']), ''];
    }
    if ($tagList !== '') {
        $fields[] = ['Tags', $e($tagList), ''];
    }
    if (!empty($item['notes'])) {
        $fields[] = ['Notes', nl2br($e($item['notes'])), ''];
    }
    ?>
    <div class="card">
        <div class="card-title"><?= $e($title !== '' ? $title : 'Untitled entry') ?></div>
        <?php if ($fields !== []): ?>
            <table>
                <?php foreach ($fields as [$label, $valueHtml, $class]): ?>
                    <tr>
                        <td class="label"><?= $e($label) ?></td>
                        <td class="<?= $e($class) ?>"><?= $valueHtml ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<h2>Notes</h2>
<?php if ($notes === []): ?>
    <p class="empty">No notes to export.</p>
<?php endif; ?>
<?php foreach ($notes as $note): ?>
    <?php
    $title = trim((string) ($note['title'] ?? ''));
    $created = $fmt($note['created_at'] ?? null);
    $updated = $fmt($note['updated_at'] ?? null);
    $body = $noteText($note['content'] ?? null);
    ?>
    <div class="note">
        <div class="note-title"><?= $e($title !== '' ? $title : 'Untitled note') ?></div>
        <div class="note-meta">
            <?php if ($created !== ''): ?>Created <?= $e($created) ?><?php endif; ?>
            <?php if ($updated !== '' && $updated !== $created): ?> &middot; Updated <?= $e($updated) ?><?php endif; ?>
        </div>
        <?php if ($body !== ''): ?>
            <div class="note-body"><?= nl2br($e($body)) ?></div>
        <?php else: ?>
            <div class="muted">(No content)</div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<h2>Tasks</h2>
<?php if ($tasks === []): ?>
    <p class="empty">No tasks to export.</p>
<?php else: ?>
    <table class="grid">
        <tr>
            <th style="width: 50%;">Task</th>
            <th style="width: 16%;">Status</th>
            <th style="width: 14%;">Priority</th>
            <th style="width: 20%;">Due date</th>
        </tr>
        <?php foreach ($tasks as $task): ?>
            <?php
            $status = (string) ($task['status'] ?? '');
            $priority = (string) ($task['priority'] ?? '');
            $due = $fmt($task['due_date'] ?? null, 'M j, Y');
            ?>
            <tr>
                <td>
                    <strong><?= $e($task['title'] ?? '') ?></strong>
                    <?php if (!empty($task['description'])): ?>
                        <div class="task-desc"><?= nl2br($e($task['description'])) ?></div>
                    <?php endif; ?>
                </td>
                <td><?= $e($statusLabels[$status] ?? $status) ?></td>
                <td><?= $e($priorityLabels[$priority] ?? $priority) ?></td>
                <td><?= $due !== '' ? $e($due) : '<span class="muted">-</span>' ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h2>Folders</h2>
<?php if ($folders === []): ?>
    <p class="empty">No folders to export.</p>
<?php else: ?>
    <table class="grid">
        <tr>
            <th style="width: 50%;">Folder</th>
            <th style="width: 25%;">Type</th>
            <th style="width: 25%;">Color</th>
        </tr>
        <?php foreach ($folders as $folder): ?>
            <?php $type = (string) ($folder['folder_type'] ?? ''); ?>
            <tr>
                <td><?= $e($folder['folder_name'] ?? '') ?></td>
                <td><?= $e($folderTypeLabels[$type] ?? $type) ?></td>
                <td><?= $e($folder['color'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

</body>
</html>