<?php
// Admin user details data.
$escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$adminName = $admin['full_name'] ?? 'Administrator';
$adminEmail = $admin['email'] ?? '';
$activeNav = 'users';

$statusMap = ['active' => 'is-active', 'inactive' => '', 'suspended' => 'is-suspended'];
$statusClass = $statusMap[$user['status']] ?? '';

$joined = $user['created_at'] ? date('M j, Y', strtotime($user['created_at'])) : '—';
$lastLogin = $user['last_login_at']
    ? date('M j, Y', strtotime($user['last_login_at'])) . ' • ' . date('g:i A', strtotime($user['last_login_at']))
    : 'Never';

// Suspend / Activate button.
$isSuspended = $user['status'] === 'suspended';
$nextStatus  = $isSuspended ? 'active' : 'suspended';
$activityUrl = url('admin/activity-logs?user_id=' . (int) $user['user_id']);

$statCards = [
    ['label' => 'Credentials', 'value' => $stats['credentials'], 'caption' => 'Passwords saved', 'icon' => 'ti-lock'],
    ['label' => 'Notes',       'value' => $stats['notes'],       'caption' => 'Notes saved',     'icon' => 'ti-notes'],
    ['label' => 'Tasks',       'value' => $stats['tasks'],       'caption' => 'Tasks to finish', 'icon' => 'ti-checkbox'],
    ['label' => 'Folders',     'value' => $stats['folders'],     'caption' => 'Total folders',   'icon' => 'ti-folder'],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Squir Admin - User Details</title>
  <link rel="icon" href="<?= url('assets/images/squir.png') ?>" type="image/x-icon" />
  <script>
    (function () {
      try {
        if (window.localStorage.getItem('squir-admin-theme') === 'dark') {
          document.documentElement.classList.add('dashboard-dark-preload');
        }
      } catch (error) {}
    })();
  </script>
  <link rel="stylesheet" href="<?= url('dist/assets/fonts/tabler-icons.min.css') ?>" />
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>" />
  <link rel="stylesheet" href="<?= url('assets/css/dashboard.css') ?>" />
  <link rel="stylesheet" href="<?= url('assets/css/admin/admin.css') ?>" />
  <link rel="stylesheet" href="<?= url('assets/css/admin/users.css') ?>" />
  <link rel="stylesheet" href="<?= url('assets/css/admin/user-details.css') ?>" />
</head>
<body class="admin-page">
<div class="app-shell" id="adminShell">
<script>
  try {
    if (window.localStorage.getItem('squir-admin-sidebar-collapsed') === '1') {
      document.getElementById('adminShell').classList.add('sidebar-collapsed');
    }
  } catch (error) {}
</script>
  <?php require __DIR__ . '/../../../includes/admin/sidebar.php'; ?>

  <div class="main-area">
    <?php require __DIR__ . '/../../../includes/admin/header.php'; ?>

    <main class="content-area">
      <div class="page-heading ud-heading">
        <div>
          <a class="ud-back" href="<?= url('admin/users') ?>"><i class="ti ti-chevron-left"></i> Back to Users</a>
          <h1>User Details</h1>
          <p>View user information and account statistics.</p>
        </div>

        <div class="ud-actions" data-user-id="<?= (int) $user['user_id'] ?>" data-user-name="<?= $escape($user['full_name']) ?>" data-users-url="<?= $escape(url('admin/users')) ?>">
          <button type="button" class="btn-admin btn-admin-primary" id="udStatusBtn" data-next-status="<?= $escape($nextStatus) ?>">
            <i class="ti <?= $isSuspended ? 'ti-user-check' : 'ti-ban' ?>"></i>
            <span><?= $isSuspended ? 'Activate Account' : 'Suspend Account' ?></span>
          </button>
          <div class="admin-action-menu">
            <button type="button" class="btn-admin btn-admin-outline btn-admin-icon" id="udMenuToggle" aria-haspopup="menu" aria-expanded="false" aria-label="More actions"><i class="ti ti-dots-vertical"></i></button>
            <div class="admin-action-dropdown" role="menu">
              <a role="menuitem" href="<?= $escape($activityUrl) ?>"><i class="ti ti-eye"></i> View Activity</a>
              <button type="button" role="menuitem" id="udForceLogout"><i class="ti ti-logout"></i> Force Logout</button>
              <button type="button" role="menuitem" class="is-danger" id="udDeleteUser"><i class="ti ti-trash"></i> Delete User</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Profile summary -->
      <section class="dash-card ud-profile">
        <div class="ud-profile-main">
          <span class="ud-avatar"><?= $escape(userInitials($user['full_name'])) ?></span>
          <div class="ud-profile-text">
            <strong><?= $escape($user['full_name']) ?></strong>
            <small><?= $escape($user['username']) ?></small>
          </div>
        </div>

        <div class="ud-profile-meta">
          <div class="ud-meta">
            <span class="ud-meta-icon"><i class="ti ti-calendar"></i></span>
            <div><small>Date Joined</small><strong><?= $escape($joined) ?></strong></div>
          </div>
          <div class="ud-meta">
            <span class="ud-meta-icon"><i class="ti ti-clock"></i></span>
            <div><small>Last logged in</small><strong><?= $escape($lastLogin) ?></strong></div>
          </div>
        </div>
      </section>

      <div class="ud-grid">
        <!-- Account information -->
        <section class="dash-card admin-panel">
          <div class="dash-card-head">
            <h2><i class="ti ti-user"></i> Account Information</h2>
          </div>
          <dl class="ud-info">
            <div class="ud-info-row"><dt>Full Name</dt><dd><?= $escape($user['full_name']) ?></dd></div>
            <div class="ud-info-row"><dt>Username</dt><dd><?= $escape($user['username']) ?></dd></div>
            <div class="ud-info-row"><dt>Email</dt><dd><a href="mailto:<?= $escape($user['email']) ?>"><?= $escape($user['email']) ?></a></dd></div>
            <div class="ud-info-row"><dt>Status</dt><dd><span class="admin-badge <?= $statusClass ?>"><?= $escape($user['status']) ?></span></dd></div>
          </dl>
        </section>

        <!-- User statistics -->
        <section class="dash-card admin-panel">
          <div class="dash-card-head">
            <h2><i class="ti ti-chart-bar"></i> User Statistics</h2>
          </div>
          <div class="ud-stats">
            <?php foreach ($statCards as $card): ?>
              <div class="ud-stat">
                <span class="ud-stat-icon"><i class="ti <?= $escape($card['icon']) ?>"></i></span>
                <div class="ud-stat-text">
                  <small><?= $escape($card['label']) ?></small>
                  <strong><?= (int) $card['value'] ?></strong>
                  <em><?= $escape($card['caption']) ?></em>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      </div>

      <!-- Recent activity -->
      <section class="dash-card admin-panel admin-panel--flush ud-activity">
        <div class="dash-card-head">
          <h2><i class="ti ti-clock"></i> Recent Activity</h2>
          <a class="ud-view-all" href="<?= $escape($activityUrl) ?>">View All Activity <i class="ti ti-arrow-right"></i></a>
        </div>

        <?php if ($activity === []): ?>
          <p class="admin-empty">No activity recorded for this user yet.</p>
        <?php else: ?>
          <div class="admin-table-wrap ud-activity-wrap">
            <table class="admin-table ud-activity-table">
              <thead>
                <tr>
                  <th>Date &amp; Time</th>
                  <th>Action</th>
                  <th>Device</th>
                  <th>IP Address</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($activity as $item): ?>
                  <tr>
                    <td data-label="Date &amp; Time"><span class="ud-cell"><i class="ti ti-calendar"></i><?= $escape($item['created_at']) ?></span></td>
                    <td data-label="Action"><span class="ud-cell"><span class="ud-action-icon"><i class="ti <?= $escape($item['icon']) ?>"></i></span><?= $escape($item['label']) ?></span></td>
                    <td data-label="Device"><span class="ud-cell"><span class="ud-action-icon"><i class="ti <?= $escape($item['device_icon']) ?>"></i></span><span class="ud-device"><span class="ud-device-name" title="<?= $escape($item['user_agent']) ?>"><?= $escape($item['device']) ?></span></span></span></td>
                    <td data-label="IP Address"><span class="ud-cell"><i class="ti ti-map-pin"></i><?= $escape($item['ip_address']) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    </main>

  <?php require __DIR__ . '/../../../includes/admin/footer.php'; ?>

  <script>window.ADMIN_CSRF_TOKEN = <?= json_encode($csrfToken) ?>;</script>
  <script src="<?= url('assets/js/admin/admin.js') ?>"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="<?= url('assets/js/admin/admin-alert.js') ?>"></script>
  <script src="<?= url('assets/js/admin/user-details.js') ?>"></script>
</body>
</html>