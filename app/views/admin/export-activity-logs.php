<?php
/** @var array $report Report data: rows, scope_label, generated_at, prepared_by, limit_note */
$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$rows = $report['rows'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Squir Activity Log Report</title>
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
    .grid tr { page-break-inside: avoid; }
</style>
</head>
<body>

<h1>Activity Log Report</h1>
<p class="subtitle">
    <?= $e($report['scope_label'] ?? '') ?> &middot;
    Prepared by <?= $e($report['prepared_by'] ?? '') ?> &middot;
    <?= $e($report['generated_at'] ?? '') ?>
</p>

<?php if (!empty($report['limit_note'])): ?>
    <div class="note"><?= $e($report['limit_note']) ?></div>
<?php endif; ?>

<table class="summary">
    <tr>
        <td><div class="count"><?= count($rows) ?></div><div class="name">Total entries</div></td>
    </tr>
</table>

<?php if ($rows === []): ?>
    <p class="empty">No activity logs to export.</p>
<?php else: ?>
    <table class="grid">
        <tr>
            <th style="width: 16%;">User</th>
            <th style="width: 16%;">Date &amp; Time</th>
            <th style="width: 18%;">Activity</th>
            <th style="width: 28%;">Details</th>
            <th style="width: 12%;">Device</th>
            <th style="width: 10%;">IP Address</th>
        </tr>
        <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($row as $cell): ?>
                    <td><?= $e($cell) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

</body>
</html>
