<?php
// Render activity log rows.
$escape = $escape ?? static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<?php foreach ($logs as $log): ?>
  <?php $userLink = $log['user_id'] ? url('admin/user-details?id=' . (int) $log['user_id']) : ''; ?>
  <tr data-log-row data-log-id="<?= (int) $log['id'] ?>"<?php if ($userLink !== ''): ?>
      class="is-clickable" data-href="<?= $escape($userLink) ?>" tabindex="0" role="link"
      aria-label="View details of <?= $escape($log['actor']) ?>"<?php endif; ?>>
    <td class="al-user" data-label="User">
      <div class="admin-name-cell al-user-cell">
        <span class="admin-avatar-chip <?= $escape($log['avatar']) ?>" aria-hidden="true"><?= $escape($log['initials']) ?></span>
        <span class="al-user-name" title="<?= $escape($log['actor']) ?>"><?= $escape($log['actor']) ?></span>
      </div>
    </td>
    <td class="al-date" data-label="Date &amp; Time"><?= $escape($log['date']) ?> &bull; <?= $escape($log['time']) ?></td>
    <td data-label="Activity"><span class="al-activity is-<?= $escape($log['tone']) ?>"><?= $escape($log['label']) ?></span></td>
    <td class="al-detail" data-label="Details"><span title="<?= $escape($log['detail']) ?>"><?= $escape($log['detail']) ?></span></td>
    <td data-label="Device">
      <span class="al-device">
        <span class="al-device-icon"><i class="ti <?= $escape($log['device_icon']) ?>" aria-hidden="true"></i></span>
        <span class="al-device-name"><?= $escape($log['device']) ?></span>
      </span>
    </td>
    <td class="al-ip" data-label="IP Address"><?= $escape($log['ip']) ?></td>
  </tr>
<?php endforeach; ?>
