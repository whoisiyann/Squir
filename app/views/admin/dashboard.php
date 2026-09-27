<?php
// Expects $admin, $stats, $recentUsers, $recentActivity (set by app/routes/admin/dashboard.php)
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$adminName = $admin['full_name'] ?? 'Administrator';
$adminEmail = $admin['email'] ?? '';
$activeNav = 'dashboard';

$statCards = [
    [
        'label'    => 'Total Users',
        'value'    => $stats['total_users'],
        'subtitle' => 'Registered accounts',
        'icon'     => 'users',
        'color'    => 'primary',
    ],
    [
        'label'    => 'Total Password Entries',
        'value'    => $stats['total_passwords'],
        'subtitle' => 'Saved in vault',
        'icon'     => 'lock',
        'color'    => 'success',
    ],
    [
        'label'    => 'Total Notes',
        'value'    => $stats['total_notes'],
        'subtitle' => 'Personal notes',
        'icon'     => 'file-text',
        'color'    => 'secondary',
    ],
    [
        'label'    => 'Total Tasks',
        'value'    => $stats['total_tasks'],
        'subtitle' => 'To do / In progress / Completed',
        'icon'     => 'check-square',
        'color'    => 'warning',
    ],
];

$statusBadge = static function (string $status) use ($escape): string {
    $map = [
        'active'    => 'bg-success-100 text-success-600',
        'inactive'  => 'bg-secondary-100 text-secondary-600',
        'suspended' => 'bg-danger-100 text-danger-600',
    ];
    $classes = $map[$status] ?? 'bg-secondary-100 text-secondary-600';

    return '<span class="badge rounded-pill ' . $classes . ' capitalize">' . $escape($status) . '</span>';
};
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
<head>
  <title>Squir Admin - Dashboard</title>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="description" content="Squir Admin Panel" />
  <link rel="icon" href="./assets/images/squir.png" type="image/x-icon" />
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="./dist/assets/fonts/phosphor/duotone/style.css" />
  <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css" />
  <link rel="stylesheet" href="./dist/assets/fonts/feather.css" />
  <link rel="stylesheet" href="./dist/assets/fonts/fontawesome.css" />
  <link rel="stylesheet" href="./dist/assets/fonts/material.css" />
  <link rel="stylesheet" href="./dist/assets/css/style.css" id="main-style-link" />
  <link rel="stylesheet" href="./assets/css/admin/admin.css" />
</head>
<body>
  <!-- [ Pre-loader ] start -->
  <div class="loader-bg fixed inset-0 bg-white dark:bg-themedark-cardbg z-[1034]">
    <div class="loader-track h-[5px] w-full inline-block absolute overflow-hidden top-0">
      <div class="loader-fill w-[300px] h-[5px] bg-primary-500 absolute top-0 left-0 animate-[hitZak_0.6s_ease-in-out_infinite_alternate]"></div>
    </div>
  </div>
  <!-- [ Pre-loader ] End -->

  <?php require __DIR__ . '/../../../includes/admin/sidebar.php'; ?>
  <?php require __DIR__ . '/../../../includes/admin/header.php'; ?>

  <div class="pc-container">
    <div class="pc-content">
      <!-- [ breadcrumb ] start -->
      <div class="page-header">
        <div class="page-block">
          <div class="page-header-title">
            <h5 class="mb-0 font-medium">Dashboard</h5>
          </div>
          <p class="text-muted mb-0">Overview of your system</p>
        </div>
      </div>
      <!-- [ breadcrumb ] end -->

      <!-- [ Stat cards ] start -->
      <div class="grid grid-cols-12 gap-x-6 gap-y-6">
        <?php foreach ($statCards as $card): ?>
          <div class="col-span-12 sm:col-span-6 xl:col-span-3">
            <div class="card stat-card mb-0">
              <div class="card-body">
                <div class="flex items-center gap-3 mb-4">
                  <span class="stat-icon bg-<?= $card['color'] ?>-100 text-<?= $card['color'] ?>-600">
                    <i data-feather="<?= $escape($card['icon']) ?>"></i>
                  </span>
                  <span class="text-muted text-[13px]"><?= $escape($card['label']) ?></span>
                </div>
                <h3 class="font-semibold mb-1"><?= (int) $card['value'] ?></h3>
                <p class="text-muted text-[12px] mb-0"><?= $escape($card['subtitle']) ?></p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <!-- [ Stat cards ] end -->

      <!-- [ Recent users / activity ] start -->
      <div class="grid grid-cols-12 gap-x-6 gap-y-6 mt-6">
        <div class="col-span-12 xl:col-span-7">
          <div class="card mb-0 h-full">
            <div class="card-header flex items-center justify-between">
              <h5>Recent Users</h5>
              <a href="./admin-users" class="text-primary-500 text-[13px]">View all users &rarr;</a>
            </div>
            <div class="card-body !p-0">
              <?php if ($recentUsers === []): ?>
                <p class="text-muted text-center py-6 mb-0">No users have registered yet.</p>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table align-middle mb-0">
                    <thead>
                      <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Joined</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($recentUsers as $user): ?>
                        <tr>
                          <td>
                            <div class="flex items-center gap-2">
                              <span class="avatar-chip bg-primary-100 text-primary-600"><?= $escape(userInitials($user['full_name'])) ?></span>
                              <span><?= $escape($user['full_name']) ?></span>
                            </div>
                          </td>
                          <td class="text-muted"><?= $escape($user['email']) ?></td>
                          <td><?= $statusBadge($user['status']) ?></td>
                          <td class="text-muted"><?= $escape(date('M j, Y', strtotime((string) $user['created_at']))) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="col-span-12 xl:col-span-5">
          <div class="card mb-0 h-full">
            <div class="card-header flex items-center justify-between">
              <h5>Recent Activity</h5>
              <a href="./admin-activity-logs" class="text-primary-500 text-[13px]">View all &rarr;</a>
            </div>
            <div class="card-body">
              <?php if ($recentActivity === []): ?>
                <p class="text-muted text-center py-6 mb-0">No activity recorded yet.</p>
              <?php else: ?>
                <ul class="activity-feed">
                  <?php foreach ($recentActivity as $item): ?>
                    <li class="activity-feed-item">
                      <span class="activity-feed-icon <?= $item['is_admin'] ? 'bg-secondary-100 text-secondary-600' : 'bg-primary-100 text-primary-600' ?>">
                        <i data-feather="<?= $escape($item['icon']) ?>"></i>
                      </span>
                      <div class="grow">
                        <div class="flex items-center justify-between gap-2">
                          <span class="font-medium text-[14px]"><?= $escape($item['label']) ?></span>
                          <span class="text-muted text-[12px] shrink-0"><?= $escape($item['time_label']) ?></span>
                        </div>
                        <p class="text-muted text-[13px] mb-0"><?= $escape($item['actor']) ?><?= $item['detail'] !== '' ? ' &middot; ' . $escape($item['detail']) : '' ?></p>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <!-- [ Recent users / activity ] end -->
    </div>
  </div>

  <?php require __DIR__ . '/../../../includes/admin/footer.php'; ?>

  <!-- Required Js -->
  <script src="./dist/assets/js/plugins/simplebar.min.js"></script>
  <script src="./dist/assets/js/plugins/popper.min.js"></script>
  <script src="./dist/assets/js/icon/custom-icon.js"></script>
  <script src="./dist/assets/js/plugins/feather.min.js"></script>
  <script src="./dist/assets/js/component.js"></script>
  <script src="./dist/assets/js/theme.js"></script>
  <script src="./dist/assets/js/script.js"></script>
  <script src="./assets/js/admin/admin.js"></script>
</body>
</html>
