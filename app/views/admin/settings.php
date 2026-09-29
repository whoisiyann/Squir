<?php
// Admin settings.
$escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$adminName = $admin['full_name'] ?? 'Administrator';
$adminEmail = $admin['email'] ?? '';
$adminUsername = $admin['username'] ?? '';
$initials = adminInitials($adminName);
$activeNav = 'settings';

$titles = [
    'index'           => 'Settings',
    'activity-log'    => 'Activity Log',
    'change-password' => 'Change Password',
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Squir Admin - <?= $escape($titles[$panel]) ?></title>
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
  <link rel="stylesheet" href="<?= url('assets/css/settings.css') ?>" />
  <link rel="stylesheet" href="<?= url('assets/css/admin/settings.css') ?>" />
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

    <main class="content-area as-page" id="asRoot" data-endpoint="<?= $escape(url('admin/settings')) ?>">

<?php if ($panel === 'index'): ?>
      <div class="page-heading">
        <div>
          <h1>Settings</h1>
          <p>Manage your account, system settings, and application preferences.</p>
        </div>
      </div>

      <div class="settings-grid">
        <section class="dash-card settings-card" aria-labelledby="acctInfoTitle">
          <div class="dash-card-head">
            <h2 id="acctInfoTitle"><i class="ti ti-user"></i> Account Information</h2>
          </div>
          <p class="settings-card-subtitle">Update your admin profile and personal information.</p>

          <div class="settings-profile-row as-profile">
            <span class="settings-avatar" id="asAvatar"><?= $escape($initials) ?></span>
            <div class="settings-profile-text">
              <strong id="asNameDisplay"><?= $escape($adminName) ?></strong>
              <span class="as-role-chip">Administrator</span>
            </div>
            <button type="button" class="btn-admin btn-admin-outline" id="asOpenEdit">
              <i class="ti ti-pencil" aria-hidden="true"></i> Edit Profile
            </button>
          </div>

          <div class="settings-field">
            <label for="asFullNameField">Full Name</label>
            <input type="text" id="asFullNameField" value="<?= $escape($adminName) ?>" disabled>
          </div>
          <div class="settings-field">
            <label for="asUsernameField">Username</label>
            <input type="text" id="asUsernameField" value="<?= $escape($adminUsername) ?>" disabled>
          </div>
          <div class="settings-field">
            <label for="asEmailField">Email Address</label>
            <input type="text" id="asEmailField" value="<?= $escape($adminEmail) ?>" disabled>
          </div>
        </section>

        <div class="settings-side">
          <section class="dash-card settings-card" aria-labelledby="appearanceTitle">
            <div class="dash-card-head">
              <h2 id="appearanceTitle"><i class="ti ti-palette"></i> Appearance</h2>
            </div>
            <p class="settings-card-subtitle">Customize the look and feel of the admin panel.</p>

            <span class="settings-label">Theme</span>
            <div class="settings-theme-options" id="asThemeOptions" role="group" aria-label="Theme">
              <button type="button" class="settings-theme-btn" data-theme="light">
                <i class="ti ti-sun"></i><span>Light</span>
              </button>
              <button type="button" class="settings-theme-btn" data-theme="dark">
                <i class="ti ti-moon"></i><span>Dark</span>
              </button>
              <button type="button" class="settings-theme-btn" data-theme="auto">
                <i class="ti ti-contrast"></i><span>Auto</span>
              </button>
            </div>
          </section>

          <section class="dash-card settings-card" aria-labelledby="privacyTitle">
            <div class="dash-card-head">
              <h2 id="privacyTitle"><i class="ti ti-shield-lock"></i> Privacy &amp; Security</h2>
            </div>
            <p class="settings-card-subtitle">Manage your account security and activity logs.</p>

            <a class="settings-row" href="<?= $escape(url('admin/settings?panel=activity-log')) ?>">
              <span class="settings-row-icon"><i class="ti ti-history"></i></span>
              <span class="settings-row-text">
                <strong>Activity Log</strong>
                <small>View your recent activities.</small>
              </span>
              <i class="ti ti-chevron-right settings-row-chevron"></i>
            </a>

            <a class="settings-row" href="<?= $escape(url('admin/settings?panel=change-password')) ?>">
              <span class="settings-row-icon"><i class="ti ti-key"></i></span>
              <span class="settings-row-text">
                <strong>Change Password</strong>
                <small>Update your account password.</small>
              </span>
              <i class="ti ti-chevron-right settings-row-chevron"></i>
            </a>
          </section>

          <section class="dash-card settings-card" aria-labelledby="dataTitle">
            <div class="dash-card-head">
              <h2 id="dataTitle"><i class="ti ti-database"></i> Data Management</h2>
            </div>
            <p class="settings-card-subtitle">Export and manage your system data.</p>

            <div class="settings-row settings-row-export">
              <span class="settings-row-icon"><i class="ti ti-database-export"></i></span>
              <span class="settings-row-text">
                <strong>Export all data logs ( Admin &amp; Users )</strong>
                <small>Download system logs and user data.</small>
              </span>
              <button type="button" class="btn-admin btn-admin-outline btn-admin-sm" id="asExportBtn">
                <i class="ti ti-download" aria-hidden="true"></i> Export
              </button>
            </div>

            <div class="settings-row">
              <span class="settings-row-icon"><i class="ti ti-cloud-download"></i></span>
              <span class="settings-row-text">
                <strong>Export only user logs</strong>
                <small>Only export user activity logs.</small>
              </span>
              <label class="as-switch" title="Export only user logs">
                <input type="checkbox" id="asUserOnly" checked aria-label="Export only user logs">
                <span class="as-switch-track" aria-hidden="true"></span>
              </label>
            </div>
          </section>
        </div>
      </div>

<?php elseif ($panel === 'activity-log'): ?>
      <div class="page-heading settings-subpage-heading">
        <div class="settings-subpage-title">
          <a class="settings-back-btn" href="<?= $escape(url('admin/settings')) ?>" aria-label="Back to Settings"><i class="ti ti-arrow-left"></i></a>
          <div>
            <h1>Activity Log</h1>
            <p>View your recent activities.</p>
          </div>
        </div>
        <button type="button" class="btn-admin btn-admin-outline as-clear-btn" id="asClearActivity"<?= $activity === [] ? ' disabled' : '' ?>>
          <i class="ti ti-trash" aria-hidden="true"></i> Clear Activity Log
        </button>
      </div>

      <section class="dash-card settings-card settings-activity-card" id="asActivityCard" aria-labelledby="recentActivityTitle">
        <div class="dash-card-head">
          <h2 id="recentActivityTitle">Recent Activity</h2>
        </div>

        <?php if ($activity !== []): ?>
          <ul class="settings-activity-list" id="settingsActivityList">
            <?php foreach ($activity as $entry): ?>
              <li class="settings-activity-item">
                <span class="settings-activity-icon"><i class="ti <?= $escape(preg_replace('/^ti\s+/', '', (string) $entry['icon'])) ?>"></i></span>
                <span class="settings-activity-text">
                  <strong><?= $escape($entry['label']) ?></strong>
                  <?php if ($entry['detail'] !== ''): ?><small><?= $escape($entry['detail']) ?></small><?php endif; ?>
                </span>
                <span class="settings-activity-time"><?= $escape($entry['time_label']) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="empty-state" id="asActivityEmpty">
            <i class="ti ti-history"></i>
            <p>No activity yet</p>
            <small>Your admin actions will show up here.</small>
          </div>
        <?php endif; ?>
      </section>

<?php else: ?>
      <div class="page-heading settings-subpage-heading">
        <div class="settings-subpage-title">
          <a class="settings-back-btn" href="<?= $escape(url('admin/settings')) ?>" aria-label="Back to Settings"><i class="ti ti-arrow-left"></i></a>
          <div>
            <h1>Change Password</h1>
            <p>Keep your account safe by using a strong password.</p>
          </div>
        </div>
      </div>

      <div class="settings-security-layout">
        <section class="dash-card settings-card">
          <form id="changePasswordForm" autocomplete="off" novalidate>
            <div class="settings-field settings-field-password">
              <label for="currentPassword">Current Password</label>
              <div class="settings-password-wrap">
                <input type="password" id="currentPassword" name="current_password" placeholder="Enter your current password" autocomplete="current-password">
                <button type="button" class="settings-password-toggle" data-target="currentPassword" aria-label="Show password"><i class="ti ti-eye"></i></button>
              </div>
              <p class="settings-form-error" data-error-for="current_password"></p>
            </div>

            <div class="settings-field settings-field-password">
              <label for="newPassword">New Password</label>
              <div class="settings-password-wrap">
                <input type="password" id="newPassword" name="new_password" placeholder="Enter your new password" autocomplete="new-password">
                <button type="button" class="settings-password-toggle" data-target="newPassword" aria-label="Show password"><i class="ti ti-eye"></i></button>
              </div>

              <ul class="settings-password-checklist" id="passwordChecklist">
                <li data-rule="length"><i class="ti ti-circle-check"></i> At least 8 characters</li>
                <li data-rule="number"><i class="ti ti-circle-check"></i> Include a number</li>
                <li data-rule="special"><i class="ti ti-circle-check"></i> Include a special character</li>
              </ul>
              <p class="settings-form-error" data-error-for="new_password"></p>
            </div>

            <div class="settings-field settings-field-password">
              <label for="confirmPassword">Confirm New Password</label>
              <div class="settings-password-wrap">
                <input type="password" id="confirmPassword" name="confirm_password" placeholder="Re-enter your new password" autocomplete="new-password">
                <button type="button" class="settings-password-toggle" data-target="confirmPassword" aria-label="Show password"><i class="ti ti-eye"></i></button>
              </div>
              <p class="settings-form-error" data-error-for="confirm_password"></p>
            </div>

            <p class="settings-form-error" id="changePasswordFormError" role="alert"></p>

            <button type="submit" class="btn-admin btn-admin-primary settings-submit-btn" id="changePasswordSubmit">Update Password</button>
          </form>
        </section>

        <aside class="settings-security-aside">
          <img src="<?= url('assets/images/squir-your-security-matter.png') ?>" alt="">
          <h3>Your Security Matters</h3>
          <p>A strong password helps protect your data and keeps account safe.</p>
        </aside>
      </div>
<?php endif; ?>
    </main>

  <?php require __DIR__ . '/../../../includes/admin/footer.php'; ?>

<?php if ($panel === 'index'): ?>
  <!-- Edit profile modal -->
  <div class="admin-modal-backdrop" id="asEditModalBackdrop">
    <div class="admin-modal" role="dialog" aria-modal="true" aria-labelledby="asEditModalTitle">
      <div class="admin-modal-header">
        <h2 id="asEditModalTitle">Account Information</h2>
        <button type="button" class="icon-btn" data-close-modal aria-label="Close"><i class="ti ti-x"></i></button>
      </div>

      <form id="asEditForm" autocomplete="off">
        <div class="settings-editable-field">
          <div class="settings-editable-field-head">
            <label for="asEditFullName">Full Name</label>
            <button type="button" class="settings-field-edit-link" data-edit-target="asEditFullName"><i class="ti ti-pencil"></i> Edit</button>
          </div>
          <input type="text" id="asEditFullName" name="full_name" maxlength="100" value="<?= $escape($adminName) ?>" readonly>
        </div>

        <div class="settings-editable-field">
          <div class="settings-editable-field-head">
            <label for="asEditUsername">Username</label>
            <button type="button" class="settings-field-edit-link" data-edit-target="asEditUsername"><i class="ti ti-pencil"></i> Edit</button>
          </div>
          <input type="text" id="asEditUsername" name="username" maxlength="50" value="<?= $escape($adminUsername) ?>" readonly>
        </div>

        <div class="settings-editable-field">
          <div class="settings-editable-field-head">
            <label for="asEditEmail">Email</label>
            <button type="button" class="settings-field-edit-link" data-edit-target="asEditEmail"><i class="ti ti-pencil"></i> Edit</button>
          </div>
          <input type="email" id="asEditEmail" name="email" maxlength="100" value="<?= $escape($adminEmail) ?>" readonly>
        </div>

        <div class="settings-editable-field" id="asPasswordField" hidden>
          <div class="settings-editable-field-head">
            <label for="asEditPassword">Current Password</label>
          </div>
          <input type="password" id="asEditPassword" name="current_password" placeholder="Enter your current password" autocomplete="current-password">
          <small class="settings-field-hint">Changing your email requires your current password.</small>
        </div>

        <p class="settings-form-error" id="asEditError" role="alert"></p>

        <div class="admin-modal-actions">
          <button type="button" class="btn-admin btn-admin-outline" data-close-modal>Cancel</button>
          <button type="submit" class="btn-admin btn-admin-primary" id="asEditSubmit">Done</button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

  <script>window.ADMIN_CSRF_TOKEN = <?= json_encode($csrfToken) ?>;</script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="<?= url('assets/js/admin/admin-alert.js') ?>"></script>
  <script src="<?= url('assets/js/admin/admin.js') ?>"></script>
  <script src="<?= url('assets/js/admin/settings.js') ?>"></script>
</body>
</html>