<?php
/** @var array $report Report data: header, rows, totals, generated_at, prepared_by, limit_note */

$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$rows = $report['rows'] ?? [];
$totals = $report['totals'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Squir User Summary Report</title>
<style>
    @page { margin: 48px 40px 60px 40px; }

    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 8pt;
        line-height: 1.4;
        color: #2b2320;
    }

    h1 { font-size: 19pt; color: #6b3f2a; margin: 0 0 4px 0; }
    .subtitle { color: #6f625b; font-size: 8.5pt; margin: 0 0 14px 0; }
    .note { background: #fbf1e3; border: 1px solid #e0c39a; color: #6b3f2a; padding: 7px 10px; margin: 0 0 12px 0; }
    .empty { color: #8a7d75; font-style: italic; }

    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    td, th { word-wrap: break-word; vertical-align: top; }

    .summary td { border: 1px solid #e6dcd3; padding: 7px 10px; text-align: center; }
    .summary .count { font-size: 15pt; font-weight: bold; color: #6b3f2a; }
    .summary .name { color: #6f625b; font-size: 8pt; }

    .grid { margin-top: 14px; }
    .grid th {
        background: #f5ede6;
        color: #6b3f2a;
        text-align: left;
        padding: 6px 8px;
        border: 1px solid #e6dcd3;
    }
    .grid td { padding: 5px 8px; border: 1px solid #e6dcd3; }
    .grid .num { text-align: center; }
    .grid tr { page-break-inside: avoid; }
</style>
</head>
<body>

<h1>User Summary Report</h1>
<p class="subtitle">
    Credentials, notes, and tasks per user &middot;
    Prepared by <?= $e($report['prepared_by'] ?? '') ?> &middot;
    <?= $e($report['generated_at'] ?? '') ?>
</p>

<?php if (!empty($report['limit_note'])): ?>
    <div class="note"><?= $e($report['limit_note']) ?></div>
<?php endif; ?>

<table class="summary">
    <tr>
        <td><div class="count"><?= count($rows) ?></div><div class="name">Users</div></td>
        <td><div class="count"><?= (int) ($totals['credentials'] ?? 0) ?></div><div class="name">Credentials</div></td>
        <td><div class="count"><?= (int) ($totals['notes'] ?? 0) ?></div><div class="name">Notes</div></td>
        <td><div class="count"><?= (int) ($totals['tasks_done'] ?? 0) ?></div><div class="name">Tasks done</div></td>
        <td><div class="count"><?= (int) ($totals['tasks_pending'] ?? 0) ?></div><div class="name">Tasks pending</div></td>
    </tr>
</table>

<?php if ($rows === []): ?>
    <p class="empty">No users to report yet.</p>
<?php else: ?>
    <table class="grid">
        <tr>
            <th style="width: 18%;">User</th>
            <th style="width: 26%;">Email</th>
            <th style="width: 10%;">Status</th>
            <th class="num" style="width: 11%;">Credentials</th>
            <th class="num" style="width: 8%;">Notes</th>
            <th class="num" style="width: 9%;">Tasks Done</th>
            <th class="num" style="width: 10%;">Tasks Pending</th>
            <th class="num" style="width: 8%;">Total</th>
        </tr>
        <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach (array_values($row) as $i => $cell): ?>
                    <td<?= $i >= 3 ? ' class="num"' : '' ?>><?= $e($cell) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

</body>
</html>
