<?php
if (!isset($dashboard) || !is_array($dashboard) || empty($dashboard['user'])) {
    header('Location: ../../../dashboard.php');
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
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="./assets/css/dashboard.css">
</head>
<body>
<div class="app-shell" id="appShell">
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    <aside class="sidebar" id="appSidebar">
        <div class="brand-row">
            <button class="sidebar-collapse-btn" id="collapseBtn" type="button" aria-label="Collapse sidebar"><i class="ti ti-menu-2"></i></button>
            <a class="brand" href="./dashboard.php"><img src="./assets/images/squir.png" alt=""><span>Squir</span></a>
        </div>
        <nav class="sidebar-nav" aria-label="Main navigation">
            <span class="nav-caption">Navigation</span>
            <a class="nav-link active" href="./dashboard.php"><i class="ti ti-home"></i><span>Dashboard</span></a>
            <a class="nav-link" href="#credentials"><i class="ti ti-key"></i><span>Vault</span></a>
            <a class="nav-link" href="#notes"><i class="ti ti-notes"></i><span>Notes</span></a>
            <a class="nav-link" href="#tasks"><i class="ti ti-edit"></i><span>Tasks</span></a>
            <a class="nav-link" href="#folders"><i class="ti ti-folder"></i><span>Folders</span></a>
            <a class="nav-link" href="#favorites"><i class="ti ti-star"></i><span>Favorites</span></a>
        </nav>
        <nav class="sidebar-nav sidebar-bottom" aria-label="Account navigation">
            <span class="nav-caption">Settings</span>
            <a class="nav-link" href="#settings"><i class="ti ti-settings"></i><span>Settings</span></a>
            <a class="nav-link" href="./logout.php"><i class="ti ti-logout"></i><span>Log Out</span></a>
        </nav>
        <div class="sidebar-illustration"><img src="./assets/images/squirrel.gif" alt="Squir mascot"></div>
    </aside>

    <div class="main-area">
        <header class="topbar">
            <button class="mobile-toggle-btn" id="mobileBtn" type="button" aria-label="Open menu"><i class="ti ti-menu-2"></i></button>
            <div class="search-box"><i class="ti ti-search"></i><input type="search" placeholder="Search anything..." aria-label="Search"></div>
            <div class="topbar-actions">
                <button class="icon-btn" type="button" aria-label="Notifications"><i class="ti ti-bell"></i><span class="notification-dot"></span></button>
                <button class="theme-toggle" id="themeToggle" type="button" aria-label="Toggle theme"><i class="ti ti-moon"></i></button>
                <div class="profile"><span class="avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span><span class="profile-name"><?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?></span><i class="ti ti-chevron-down"></i></div>
            </div>
        </header>

        <main class="content-area">
            <div class="page-heading">
                <div><h1>Good morning, <?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?>!</h1><p>Here's what's happening with your vault today.</p></div>
                <button class="quick-add" type="button"><i class="ti ti-plus"></i> Quick Add <i class="ti ti-chevron-down"></i></button>
            </div>

            <section class="stat-grid" aria-label="Vault summary">
                <article class="stat-card"><span class="stat-icon"><i class="ti ti-lock"></i></span><strong>Credentials</strong><b><?= $counts['vault'] ?></b><small>Total saved</small></article>
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
<script src="./assets/js/dashboard.js"></script>
</body>
</html>
