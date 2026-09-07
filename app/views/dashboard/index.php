<!-- views/dashboard/index.php -->

<?php
if (!isset($dashboard) || !is_array($dashboard) || empty($dashboard['user'])) {
    header('Location: ../../../index.php');
    exit;
}

$user = $dashboard['user'];
$counts = $dashboard['counts'];
$firstName = explode(' ', trim($user['full_name']))[0] ?: $user['username'];
$initials = strtoupper(substr($user['full_name'], 0, 1));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Squir - Dashboard</title>
    <script>
        (function () {
            try {
                if (window.localStorage.getItem('squir-dashboard-theme') === 'dark') {
                    document.documentElement.classList.add('dashboard-dark-preload');
                }
            } catch (error) {}
        })();
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/dashboard.css">
</head>
<body>
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
                <div><h1>What’s on your mind today, <?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?>!</h1><p>Here's what's happening with your vault today.</p></div>
                <button class="quick-add" type="button"><i class="ti ti-plus"></i> Quick Add <i class="ti ti-chevron-down"></i></button>
            </div>

            <section class="stat-grid" aria-label="Vault summary">
                <article class="stat-card"><span class="stat-icon"><i class="ti ti-key"></i></span><strong>Credentials</strong><b><?= $counts['vault'] ?></b><small>Total saved</small></article>
                <article class="stat-card"><span class="stat-icon"><i class="ti ti-notes"></i></span><strong>Notes</strong><b><?= $counts['notes'] ?></b><small>Total notes</small></article>
                <article class="stat-card"><span class="stat-icon"><i class="ti ti-checkbox"></i></span><strong>Tasks</strong><b><?= $dashboard['pendingTasks'] ?></b><small>Pending tasks</small></article>
                <article class="stat-card"><span class="stat-icon"><i class="ti ti-folder"></i></span><strong>Folders</strong><b><?= $counts['folders'] ?></b><small>Total folders</small></article>
            </section>

            <section class="dashboard-grid">
                <article class="app-card" id="tasks"><div class="card-heading"><h2>Tasks Overview</h2><a href="#tasks">View all</a></div><div class="empty-state"><i class="ti ti-list-check"></i><p>No tasks yet</p><small>Your upcoming tasks will appear here.</small></div></article>
                <article class="app-card" id="favorites"><div class="card-heading"><h2>Recent Items</h2><a href="#favorites">View all</a></div><div class="empty-state"><i class="ti ti-inbox"></i><p>No recent items</p><small>Saved passwords and notes will appear here.</small></div></article>
                <article class="app-card folder-card" id="folders"><div class="card-heading"><div><h2>Folders</h2><p>Organize your items with folders.</p></div><a class="small-action" href="#folders"><i class="ti ti-plus"></i> New Folder</a></div><div class="empty-state compact"><i class="ti ti-folder-plus"></i><p>No folders yet</p><small>Create a folder to organize your vault.</small></div></article>
            </section>
        </main>
    </div>
</div>
<script src="./assets/js/dashboard.js?v=2"></script>
</body>
</html>