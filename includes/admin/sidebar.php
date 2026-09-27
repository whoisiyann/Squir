<?php
// Active admin navigation item.
$activeNav = $activeNav ?? '';
$isActive = static fn (string $key): string => $activeNav === $key ? ' active' : '';
?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<aside class="sidebar" id="appSidebar">
    <div class="brand-row">
        <a class="brand" href="<?= url('admin/dashboard') ?>">
            <img src="<?= url('assets/images/squir.png') ?>" alt="">
            <span class="brand-text"><span class="brand-name">Squir</span><small class="brand-role">Administrator</small></span>
        </a>
        <button class="sidebar-collapse-btn" id="collapseBtn" type="button" aria-label="Collapse sidebar">
            <i class="ti ti-menu-2 collapse-icon-menu"></i>
            <img class="collapse-icon-brand" src="<?= url('assets/images/squir.png') ?>" alt="">
        </button>
    </div>

    <nav class="sidebar-nav" aria-label="Admin navigation">
        <a class="nav-link<?= $isActive('dashboard') ?>" href="<?= url('admin/dashboard') ?>"<?= $activeNav === 'dashboard' ? ' aria-current="page"' : '' ?>><i class="ti ti-home"></i><span>Dashboard</span></a>
        <a class="nav-link<?= $isActive('users') ?>" href="<?= url('admin/users') ?>"<?= $activeNav === 'users' ? ' aria-current="page"' : '' ?>><i class="ti ti-users"></i><span>Users</span></a>
        <a class="nav-link<?= $isActive('activity-logs') ?>" href="<?= url('admin/activity-logs') ?>"<?= $activeNav === 'activity-logs' ? ' aria-current="page"' : '' ?>><i class="ti ti-clock"></i><span>Activity Logs</span></a>
        <a class="nav-link<?= $isActive('settings') ?>" href="<?= url('admin/settings') ?>"<?= $activeNav === 'settings' ? ' aria-current="page"' : '' ?>><i class="ti ti-settings"></i><span>Settings</span></a>
    </nav>

    <div class="sidebar-illustration"><img src="<?= url('assets/images/admin-squir.gif') ?>" alt="Squir mascot"></div>
</aside>
