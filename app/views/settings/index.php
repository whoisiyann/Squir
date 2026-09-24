<?php
/** @var array  $user */
/** @var string $csrfToken */

$escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$initials = userInitials($user['full_name']);
$memberSince = 'Member since ' . date('M j, Y', strtotime((string) $user['created_at']));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Squir - Settings</title>
    <script>
        (function () {
            try {
                if (window.localStorage.getItem('squir-dashboard-theme') === 'dark') {
                    document.documentElement.classList.add('dashboard-dark-preload');
                }
            } catch (error) {}
        })();
    </script>
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/dashboard.css">
    <link rel="stylesheet" href="./assets/css/vault.css">
    <link rel="stylesheet" href="./assets/css/pin-modal.css">
    <link rel="stylesheet" href="./assets/css/settings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body class="settings-page">
<div class="app-shell" id="appShell">
<script>
    try {
        if (window.localStorage.getItem('squir-sidebar-collapsed') === '1') {
            document.getElementById('appShell').classList.add('sidebar-collapsed');
        }
    } catch (error) {}
</script>
    <?php require __DIR__ . '/../../../includes/sidebar.php'; ?>

    <div class="main-area">
        <?php require __DIR__ . '/../../../includes/header.php'; ?>

        <main class="content-area">
            <div class="page-heading">
                <div>
                    <h1>Settings</h1>
                    <p>Manage your account, appearance, and security.</p>
                </div>
            </div>

            <div class="settings-grid">
                <section class="dash-card settings-card" aria-labelledby="acctInfoTitle">
                    <div class="dash-card-head">
                        <h2 id="acctInfoTitle"><i class="ti ti-user"></i> Account Information</h2>
                    </div>

                    <div class="settings-profile-row">
                        <span class="settings-avatar" id="settingsAvatar"><?= $escape($initials) ?></span>
                        <div class="settings-profile-text">
                            <strong id="settingsFullNameDisplay"><?= $escape($user['full_name']) ?></strong>
                            <small id="settingsMemberSince"><?= $escape($memberSince) ?></small>
                        </div>
                        <button type="button" class="btn-outline-squir settings-edit-btn" id="openEditAccountModal">
                            <i class="ti ti-pencil"></i> Edit
                        </button>
                    </div>

                    <div class="settings-field">
                        <label>Full Name</label>
                        <input type="text" value="<?= $escape($user['full_name']) ?>" id="settingsFullNameField" disabled>
                    </div>
                    <div class="settings-field">
                        <label>Username</label>
                        <input type="text" value="<?= $escape($user['username']) ?>" id="settingsUsernameField" disabled>
                    </div>
                    <div class="settings-field">
                        <label>Email</label>
                        <input type="text" value="<?= $escape($user['email']) ?>" id="settingsEmailField" disabled>
                    </div>
                </section>

                <div class="settings-side">
                    <section class="dash-card settings-card" aria-labelledby="appearanceTitle">
                        <div class="dash-card-head">
                            <h2 id="appearanceTitle"><i class="ti ti-palette"></i> Appearance</h2>
                        </div>
                        <p class="settings-card-subtitle">Customize the look and feel of the app.</p>

                        <span class="settings-label">Theme</span>
                        <div class="settings-theme-options" id="settingsThemeOptions" role="group" aria-label="Theme">
                            <button type="button" class="settings-theme-btn" data-theme="light">
                                <i class="ti ti-sun"></i><span>Light</span>
                            </button>
                            <button type="button" class="settings-theme-btn" data-theme="dark">
                                <i class="ti ti-moon"></i><span>Dark</span>
                            </button>
                            <button type="button" class="settings-theme-btn" data-theme="auto">
                                <i class="fa fa-adjust"></i><span>Auto</span>
                            </button>
                        </div>
                    </section>

                    <section class="dash-card settings-card" aria-labelledby="privacyTitle">
                        <div class="dash-card-head">
                            <h2 id="privacyTitle"><i class="ti ti-shield-lock"></i> Privacy &amp; Security</h2>
                        </div>

                        <a class="settings-row" href="./settings?panel=activity-log">
                            <span class="settings-row-icon"><i class="ti ti-history"></i></span>
                            <span class="settings-row-text">
                                <strong>Activity Log</strong>
                                <small>View your recent activities.</small>
                            </span>
                            <i class="ti ti-chevron-right settings-row-chevron"></i>
                        </a>

                        <a class="settings-row" href="./settings?panel=change-password">
                            <span class="settings-row-icon"><i class="ti ti-key"></i></span>
                            <span class="settings-row-text">
                                <strong>Change Password</strong>
                                <small>Update your account password.</small>
                            </span>
                            <i class="ti ti-chevron-right settings-row-chevron"></i>
                        </a>

                        <a class="settings-row" href="./settings?panel=reset-pin">
                            <span class="settings-row-icon"><i class="ti ti-rotate-clockwise-2"></i></span>
                            <span class="settings-row-text">
                                <strong>Reset PIN</strong>
                                <small>Set a new PIN for your vault.</small>
                            </span>
                            <i class="ti ti-chevron-right settings-row-chevron"></i>
                        </a>

                        <div class="settings-row settings-row-export">
                            <span class="settings-row-icon"><i class="ti ti-database-export"></i></span>
                            <span class="settings-row-text">
                                <strong>Export Data</strong>
                                <small>Download a PDF copy of your vault, notes, tasks, and folders.</small>
                            </span>
                            <button type="button" class="btn-outline-squir settings-export-btn" id="exportDataBtn">
                                <i class="ti ti-download"></i> Export
                            </button>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require __DIR__ . '/partials/edit-account-modal.php'; ?>
<?php require __DIR__ . '/partials/delete-account-modal.php'; ?>
<?php require __DIR__ . '/../vault/pin-modal.php'; ?>

<script>
    window.VAULT_CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="./assets/js/squir-dialogs.js"></script>
<script src="./assets/js/dashboard.js?v=3"></script>
<script src="./assets/js/pin-gate.js"></script>
<script src="./assets/js/settings.js"></script>
</body>
</html>