<?php
// Admin dashboard data.
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$adminName = $admin['full_name'] ?? 'Administrator';
$adminEmail = $admin['email'] ?? '';
$activeNav = 'dashboard';

$statCards = [
    [
        'label'    => 'Total Users',
        'value'    => $stats['total_users'],
        'subtitle' => 'Registered accounts',
        'icon'     => 'ti-users',
        'color'    => 'orange',
    ],
    [
        'label'    => 'Total Password Entries',
        'value'    => $stats['total_passwords'],
        'subtitle' => 'Saved in vault',
        'icon'     => 'ti-lock',
        'color'    => 'green',
    ],
    [
        'label'    => 'Total Notes',
        'value'    => $stats['total_notes'],
        'subtitle' => 'Personal notes',
        'icon'     => 'ti-notes',
        'color'    => 'purple',
    ],
    [
        'label'    => 'Total Tasks',
        'value'    => $stats['total_tasks'],
        'subtitle' => 'To do / In progress / Completed',
        'icon'     => 'ti-checkbox',
        'color'    => 'amber',
    ],
];

$statusBadge = static function (string $status) use ($escape): string {
    $map = [
        'active'    => 'is-active',
        'inactive'  => '',
        'suspended' => 'is-suspended',
    ];
    $class = $map[$status] ?? '';

    return '<span class="admin-badge ' . $class . '">' . $escape($status) . '</span>';
};

// Assign stable avatar colors.
$avatarPalette = ['', 'is-purple', 'is-blue', 'is-amber', 'is-green'];
$avatarColor = static function (array $user) use ($avatarPalette): string {
    $seed = (string) ($user['user_id'] ?? $user['full_name'] ?? '');
    $index = crc32($seed) % count($avatarPalette);

    return $avatarPalette[$index];
};

// Assign activity icon colors.
$activityColor = static function (string $icon): string {
    $map = [
        'lock'         => 'is-green',
        'file-text'    => 'is-purple',
        'check-square' => 'is-amber',
        'user'         => '',
        'shield'       => 'is-muted',
        'log-in'       => '',
        'log-out'      => '',
        'settings'     => 'is-muted',
        'folder'       => 'is-amber',
        'user-plus'    => 'is-green',
        'user-check'   => 'is-green',
        'user-x'       => 'is-muted',
        'slash'        => '',
        'trash-2'      => '',
    ];

    return $map[$icon] ?? 'is-muted';
};

// Map activity icons to Tabler names.
$activityIcon = static function (string $icon): string {
    $map = [
        'lock'         => 'ti-lock',
        'file-text'    => 'ti-notes',
        'check-square' => 'ti-checkbox',
        'user'         => 'ti-user',
        'shield'       => 'ti-shield-lock',
        'log-in'       => 'ti-login',
        'log-out'      => 'ti-logout',
        'settings'     => 'ti-settings',
        'folder'       => 'ti-folder',
        'user-plus'    => 'ti-user-plus',
        'user-check'   => 'ti-user-check',
        'user-x'       => 'ti-user-x',
        'slash'        => 'ti-ban',
        'trash-2'      => 'ti-trash',
    ];

    return $map[$icon] ?? 'ti-activity';
};

$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$firstName = trim(explode(' ', $adminName)[0] ?? $adminName) ?: 'Admin';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Squir Admin - Dashboard</title>
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
      <div class="page-heading">
        <div>
          <h1><?= $escape($greeting) ?>, <?= $escape($firstName) ?>.</h1>
          <p><?= $escape(date('l, F j, Y')) ?> &middot; here's what's happening with Squir today.</p>
        </div>
      </div>

      <!-- Stat cards -->
      <div class="admin-stats">
        <?php foreach ($statCards as $card): ?>
          <div class="stat-card">
            <span class="stat-icon is-<?= $card['color'] ?>"><i class="ti <?= $escape($card['icon']) ?>"></i></span>
            <span class="stat-text"><strong><?= $escape($card['label']) ?></strong><b><?= (int) $card['value'] ?></b></span>
            <small><?= $escape($card['subtitle']) ?></small>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Recent users and activity -->
      <div class="admin-layout">
        <section class="dash-card admin-panel admin-panel--flush">
          <div class="dash-card-head">
            <h2><i class="ti ti-users"></i> Recent Users</h2>
            <a href="<?= url('admin/users') ?>">View all users <i class="ti ti-arrow-right"></i></a>
          </div>

          <?php if ($recentUsers === []): ?>
            <p class="admin-empty">No users have registered yet.</p>
          <?php else: ?>
            <div class="admin-table-wrap" id="recentUsersScroll">
              <table class="admin-table">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Joined</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentUsers as $i => $user): ?>
                    <tr>
                      <td>
                        <div class="admin-name-cell">
                          <span class="admin-avatar-chip <?= $avatarColor($user) ?>"><?= $escape(userInitials($user['full_name'])) ?></span>
                          <span><?= $escape($user['full_name']) ?></span>
                        </div>
                      </td>
                      <td class="is-muted" data-label="Email"><?= $escape($user['email']) ?></td>
                      <td data-label="Status"><?= $statusBadge($user['status']) ?></td>
                      <td class="is-muted" data-label="Joined"><i class="ti ti-calendar-event" aria-hidden="true"></i><?= $escape(date('M j, Y', strtotime((string) $user['created_at']))) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="admin-panel-more" id="recentUsersMore">
              <a href="<?= url('admin/users') ?>">See more <i class="ti ti-arrow-down"></i></a>
            </div>
          <?php endif; ?>
        </section>

        <section class="dash-card admin-panel">
          <div class="dash-card-head">
            <h2><i class="ti ti-history"></i> Recent Activity</h2>
            <a href="<?= url('admin/activity-logs') ?>">View all <i class="ti ti-arrow-right"></i></a>
          </div>

          <?php if ($recentActivity === []): ?>
            <p class="admin-empty">No activity recorded yet.</p>
          <?php else: ?>
            <ul class="admin-activity-list" id="recentActivityScroll">
              <?php foreach ($recentActivity as $item): ?>
                <li class="admin-activity-item">
                  <span class="admin-activity-icon <?= $activityColor($item['icon']) ?>">
                    <i class="ti <?= $escape($activityIcon($item['icon'])) ?>"></i>
                  </span>
                  <div class="admin-activity-body">
                    <div class="admin-activity-row">
                      <strong><?= $escape($item['label']) ?></strong>
                      <time><?= $escape($item['time_label']) ?></time>
                    </div>
                    <p><?= $escape($item['actor']) ?><?= $item['detail'] !== '' ? ' &middot; ' . $escape($item['detail']) : '' ?></p>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
            <div class="admin-panel-more" id="recentActivityMore">
              <a href="<?= url('admin/activity-logs') ?>">See more <i class="ti ti-arrow-down"></i></a>
            </div>
          <?php endif; ?>
        </section>
      </div>
    </main>

  <?php require __DIR__ . '/../../../includes/admin/footer.php'; ?>

  <script src="<?= url('assets/js/admin/admin.js') ?>"></script>
</body>
</html>