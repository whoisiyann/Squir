<?php
// Admin users data.
$escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$adminName = $admin['full_name'] ?? 'Administrator';
$adminEmail = $admin['email'] ?? '';
$activeNav = 'users';

$statCards = [
    ['label' => 'Total Users',      'value' => $stats['total'],          'subtitle' => 'Registered accounts',  'icon' => 'ti-users',          'color' => 'orange'],
    ['label' => 'Active Users',     'value' => $stats['active'],         'subtitle' => 'Currently active',     'icon' => 'ti-user-check',     'color' => 'green'],
    ['label' => 'Suspended Users',  'value' => $stats['suspended'],      'subtitle' => 'Account suspended',    'icon' => 'ti-user-off',       'color' => 'red'],
    ['label' => 'New This Week',    'value' => $stats['new_this_week'],  'subtitle' => 'Recent registrations', 'icon' => 'ti-calendar-plus',  'color' => 'amber'],
];

$statusBadge = static function (string $status) use ($escape): string {
    $map = ['active' => 'is-active', 'inactive' => '', 'suspended' => 'is-suspended'];
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

$fmtJoined = static fn (?string $value): string => $value ? date('M j, Y', strtotime($value)) : '—';

$fmtLastLogin = static function (?string $value): string {
    if (!$value) {
        return 'Never';
    }

    $timestamp = strtotime($value);
    $today = date('Y-m-d');
    $day = date('Y-m-d', $timestamp);

    if ($day === $today) {
        return 'Today, ' . date('g:i A', $timestamp);
    }
    if ($day === date('Y-m-d', strtotime('-1 day'))) {
        return 'Yesterday, ' . date('g:i A', $timestamp);
    }

    return date('M j, Y', $timestamp);
};
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Squir Admin - Users</title>
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
          <h1>Users</h1>
          <p>Manage registered user accounts.</p>
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

      <!-- User list -->
      <section class="dash-card admin-panel admin-panel--flush">
        <div class="dash-card-head admin-users-head">
          <h2><i class="ti ti-users"></i> All Users</h2>

          <div class="admin-toolbar">
            <select class="admin-select" id="usersStatusFilter" aria-label="Filter by status">
              <option value="all">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
            </select>
            <div class="search-box">
              <i class="ti ti-search"></i>
              <input type="search" id="usersSearch" placeholder="Search by name or email..." aria-label="Search by name or email" autocomplete="off">
            </div>
            <button type="button" class="btn-admin btn-admin-primary" id="openAddUserModal"><i class="ti ti-plus"></i> Add User</button>
          </div>
        </div>

        <?php if ($users === []): ?>
          <p class="admin-empty">No users have registered yet.</p>
        <?php else: ?>
          <div class="admin-table-wrap admin-users-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Status</th>
                  <th>Joined</th>
                  <th>Last Login</th>
                  <th style="text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody id="usersTableBody">
                <?php foreach ($users as $user): ?>
                  <?php
                    $nextStatus = $user['status'] === 'suspended' ? 'active' : 'suspended';
                    $toggleLabel = $user['status'] === 'suspended' ? 'Activate' : 'Suspend';
                    $toggleIcon = $user['status'] === 'suspended' ? 'ti-user-check' : 'ti-ban';
                  ?>
                  <tr data-user-row
                      data-id="<?= (int) $user['user_id'] ?>"
                      data-name="<?= $escape($user['full_name']) ?>"
                      data-username="<?= $escape($user['username']) ?>"
                      data-email="<?= $escape($user['email']) ?>"
                      data-status="<?= $escape($user['status']) ?>"
                      data-joined="<?= $escape($fmtJoined($user['created_at'])) ?>"
                      data-lastlogin="<?= $escape($fmtLastLogin($user['last_login_at'])) ?>">
                    <td>
                      <div class="admin-name-cell">
                        <span class="admin-avatar-chip <?= $avatarColor($user) ?>"><?= $escape(userInitials($user['full_name'])) ?></span>
                        <span><?= $escape($user['full_name']) ?></span>
                      </div>
                    </td>
                    <td class="is-muted" data-label="Email"><?= $escape($user['email']) ?></td>
                    <td data-label="Status"><?= $statusBadge($user['status']) ?></td>
                    <td class="is-muted" data-label="Joined"><?= $escape($fmtJoined($user['created_at'])) ?></td>
                    <td class="is-muted" data-label="Last Login"><?= $escape($fmtLastLogin($user['last_login_at'])) ?></td>
                    <td data-label="Actions">
                      <div class="admin-row-actions">
                        <button type="button" class="btn-admin btn-admin-outline btn-admin-sm" data-view-user><i class="ti ti-eye"></i> View</button>
                        <div class="admin-action-menu">
                          <button type="button" class="btn-admin btn-admin-outline btn-admin-icon" data-toggle-menu aria-haspopup="menu" aria-expanded="false" aria-label="More actions"><i class="ti ti-chevron-down"></i></button>
                          <div class="admin-action-dropdown" role="menu">
                            <button type="button" role="menuitem" data-edit-user><i class="ti ti-pencil"></i> Edit</button>
                            <button type="button" role="menuitem" data-toggle-status data-next-status="<?= $escape($nextStatus) ?>"><i class="ti <?= $toggleIcon ?>"></i> <?= $escape($toggleLabel) ?></button>
                            <button type="button" role="menuitem" class="is-danger" data-delete-user><i class="ti ti-trash"></i> Delete</button>
                          </div>
                        </div>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <tr id="usersEmptyRow" class="is-hidden">
                  <td colspan="6" class="admin-empty">No users match your search.</td>
                </tr>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    </main>

  <?php require __DIR__ . '/../../../includes/admin/footer.php'; ?>

  <!-- Add user modal -->
  <div class="admin-modal-backdrop" id="addUserModalBackdrop">
    <div class="admin-modal" role="dialog" aria-modal="true" aria-labelledby="addUserModalTitle">
      <div class="admin-modal-header">
        <h2 id="addUserModalTitle">Add User</h2>
        <button type="button" class="icon-btn" data-close-modal="addUser" aria-label="Close"><i class="ti ti-x"></i></button>
      </div>
      <form id="addUserForm" autocomplete="off">
        <p class="admin-form-alert"></p>

        <div class="admin-field">
          <label for="addFullName">Full Name</label>
          <input type="text" id="addFullName" name="full_name" maxlength="100" required>
          <p class="admin-field-error" data-error-for="full_name"></p>
        </div>

        <div class="admin-field">
          <label for="addUsername">Username</label>
          <input type="text" id="addUsername" name="username" maxlength="50" placeholder="Leave blank to auto-generate">
          <p class="admin-field-error" data-error-for="username"></p>
        </div>

        <div class="admin-field">
          <label for="addEmail">Email</label>
          <input type="email" id="addEmail" name="email" maxlength="100" required>
          <p class="admin-field-error" data-error-for="email"></p>
        </div>

        <div class="admin-field">
          <label for="addPassword">Password</label>
          <input type="password" id="addPassword" name="password" autocomplete="new-password" required>
          <p class="admin-field-error" data-error-for="password"></p>
        </div>

        <div class="admin-field">
          <label for="addPasswordConfirmation">Confirm Password</label>
          <input type="password" id="addPasswordConfirmation" name="password_confirmation" autocomplete="new-password" required>
          <p class="admin-field-error" data-error-for="password_confirmation"></p>
        </div>

        <div class="admin-modal-actions">
          <button type="button" class="btn-admin btn-admin-outline" data-close-modal="addUser">Cancel</button>
          <button type="submit" class="btn-admin btn-admin-primary">Add User</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit user modal -->
  <div class="admin-modal-backdrop" id="editUserModalBackdrop">
    <div class="admin-modal" role="dialog" aria-modal="true" aria-labelledby="editUserModalTitle">
      <div class="admin-modal-header">
        <h2 id="editUserModalTitle">Edit User</h2>
        <button type="button" class="icon-btn" data-close-modal="editUser" aria-label="Close"><i class="ti ti-x"></i></button>
      </div>
      <form id="editUserForm" autocomplete="off">
        <input type="hidden" name="user_id">
        <p class="admin-form-alert"></p>

        <div class="admin-field">
          <label for="editFullName">Full Name</label>
          <input type="text" id="editFullName" name="full_name" maxlength="100" required>
          <p class="admin-field-error" data-error-for="full_name"></p>
        </div>

        <div class="admin-field">
          <label for="editUsername">Username</label>
          <input type="text" id="editUsername" name="username" maxlength="50" required>
          <p class="admin-field-error" data-error-for="username"></p>
        </div>

        <div class="admin-field">
          <label for="editEmail">Email</label>
          <input type="email" id="editEmail" name="email" maxlength="100" required>
          <p class="admin-field-error" data-error-for="email"></p>
        </div>

        <div class="admin-modal-actions">
          <button type="button" class="btn-admin btn-admin-outline" data-close-modal="editUser">Cancel</button>
          <button type="submit" class="btn-admin btn-admin-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <!-- View user modal -->
  <div class="admin-modal-backdrop" id="viewUserModalBackdrop">
    <div class="admin-modal" role="dialog" aria-modal="true" aria-labelledby="viewUserModalTitle">
      <div class="admin-modal-header">
        <h2 id="viewUserModalTitle">User Details</h2>
        <button type="button" class="icon-btn" data-close-modal="viewUser" aria-label="Close"><i class="ti ti-x"></i></button>
      </div>

      <div class="admin-view-profile">
        <span class="admin-view-avatar" id="viewUserAvatar"></span>
        <div class="admin-view-profile-text">
          <strong id="viewUserName"></strong>
          <small id="viewUserUsername"></small>
        </div>
      </div>

      <div class="admin-view-row"><span>Email</span><span id="viewUserEmail"></span></div>
      <div class="admin-view-row"><span>Status</span><span id="viewUserStatus"></span></div>
      <div class="admin-view-row"><span>Joined</span><span id="viewUserJoined"></span></div>
      <div class="admin-view-row"><span>Last Login</span><span id="viewUserLastLogin"></span></div>

      <div class="admin-modal-actions">
        <button type="button" class="btn-admin btn-admin-outline" data-close-modal="viewUser">Close</button>
      </div>
    </div>
  </div>

  <script>window.ADMIN_CSRF_TOKEN = <?= json_encode($csrfToken) ?>;</script>
  <script src="<?= url('assets/js/admin/admin.js') ?>"></script>
  <script src="<?= url('assets/js/admin/users.js') ?>"></script>
</body>
</html>
