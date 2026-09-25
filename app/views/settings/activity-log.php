<?php
/** @var array  $user */
/** @var array  $activity */
/** @var string $csrfToken */

$escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Squir - Activity Log</title>
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
    <link rel="stylesheet" href="./assets/css/squir-dialogs.css">
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
            <div class="page-heading settings-subpage-heading">
                <div class="settings-subpage-title">
                    <a class="settings-back-btn" href="./settings" aria-label="Back to Settings"><i class="ti ti-arrow-left"></i></a>
                    <div>
                        <h1>Activity Log</h1>
                        <p>View your recent activities.</p>
                    </div>
                </div>
                <button type="button" class="btn-outline-squir settings-danger-btn" id="clearActivityLogBtn"<?= $activity === [] ? ' disabled' : '' ?>>
                    <i class="ti ti-trash"></i> Clear Activity Log
                </button>
            </div>

            <section class="dash-card settings-card settings-activity-card" aria-labelledby="recentActivityTitle">
                <div class="dash-card-head">
                    <h2 id="recentActivityTitle">Recent Activity</h2>
                </div>

                <ul class="settings-activity-list<?= $activity === [] ? ' is-empty' : '' ?>" id="settingsActivityList">
                    <?php foreach ($activity as $entry): ?>
                        <li class="settings-activity-item">
                            <span class="settings-activity-icon"><i class="<?= str_starts_with($entry['icon'], 'fa-') || str_starts_with($entry['icon'], 'fa ') || str_starts_with($entry['icon'], 'ti ') ? $escape($entry['icon']) : 'ti ' . $escape($entry['icon']) ?>"></i></span>
                            <span class="settings-activity-text">
                                <strong><?= $escape($entry['label']) ?></strong>
                                <?php if ($entry['detail'] !== ''): ?><small><?= $escape($entry['detail']) ?></small><?php endif; ?>
                            </span>
                            <span class="settings-activity-time"><?= $escape($entry['time_label']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($activity === []): ?>
                    <div class="empty-state" id="settingsActivityEmpty">
                        <i class="ti ti-history"></i>
                        <p>No activity yet</p>
                        <small>Actions like password or PIN changes will show up here.</small>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<script>
    window.VAULT_CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
</script>
<script src="./assets/js/squir-dialogs.js"></script>
<script src="./assets/js/dashboard.js?v=3"></script>
<script src="./assets/js/settings.js"></script>
</body>
</html>
