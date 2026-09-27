<?php
// Expects $adminName (string) and $activeNav (string: 'dashboard' | 'users' | 'activity-logs' | 'settings')
// to already be set by the including view.
$adminName = $adminName ?? 'Administrator';
$activeNav = $activeNav ?? '';
$escape = $escape ?? static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$initials = function_exists('adminInitials') ? adminInitials($adminName) : 'AD';
$isActive = static fn (string $key): string => $activeNav === $key ? ' active' : '';
?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<aside class="sidebar" id="appSidebar">
    <div class="brand-row">
        <a class="brand" href="./admin/dashboard">
            <img src="./assets/images/squir.png" alt="">
            <span class="brand-text"><span class="brand-name">Squir</span><small class="brand-role">Administrator</small></span>
        </a>
        <button class="sidebar-collapse-btn" id="collapseBtn" type="button" aria-label="Collapse sidebar">
            <i class="ti ti-menu-2 collapse-icon-menu"></i>
            <img class="collapse-icon-brand" src="./assets/images/squir.png" alt="">
        </button>
    </div>

    <nav class="sidebar-nav" aria-label="Admin navigation">
        <a class="nav-link<?= $isActive('dashboard') ?>" href="./admin/dashboard"<?= $activeNav === 'dashboard' ? ' aria-current="page"' : '' ?>><i class="ti ti-home"></i><span>Dashboard</span></a>
        <a class="nav-link<?= $isActive('users') ?>" href="./admin/users"<?= $activeNav === 'users' ? ' aria-current="page"' : '' ?>><i class="ti ti-users"></i><span>Users</span></a>
        <a class="nav-link<?= $isActive('activity-logs') ?>" href="./admin/activity-logs"<?= $activeNav === 'activity-logs' ? ' aria-current="page"' : '' ?>><i class="ti ti-clock"></i><span>Activity Logs</span></a>
        <a class="nav-link<?= $isActive('settings') ?>" href="./admin/settings"<?= $activeNav === 'settings' ? ' aria-current="page"' : '' ?>><i class="ti ti-settings"></i><span>Settings</span></a>
    </nav>

    <div class="sidebar-illustration"><img src="./assets/images/squirrel.gif" alt="Squir mascot"></div>

    <div class="admin-account">
        <span class="avatar admin-account-avatar"><?= $escape($initials) ?></span>
        <span class="admin-account-info">
            <strong><?= $escape($adminName) ?></strong>
            <small>Super Admin</small>
        </span>
        <a class="admin-account-logout" href="./admin/logout" title="Log out" aria-label="Log out" data-logout-trigger onclick="return confirm('Log out of the admin panel?')">
            <i class="ti ti-logout"></i>
        </a>
    </div>
</aside>
