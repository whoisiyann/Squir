<?php
$route = $route ?? '';
$isActiveRoute = static fn (string $r): string => $route === $r ? ' active' : '';


$accountType = !empty($_SESSION['admin_id']) ? 'admin' : 'user';
?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<aside class="sidebar" id="appSidebar">
    <div class="brand-row">
        <button class="sidebar-collapse-btn" id="collapseBtn" type="button" aria-label="Collapse sidebar"><i class="ti ti-menu-2"></i></button>
        <a class="brand" href="./dashboard"><img src="./assets/images/squir.png" alt=""><span>Squir</span></a>
    </div>
    <div class="account-badge account-badge-<?= $accountType ?>">
        <i class="ti ti-<?= $accountType === 'admin' ? 'shield-check' : 'user' ?>"></i>
        <span><?= $accountType === 'admin' ? 'Admin Account' : 'User Account' ?></span>
    </div>
    <nav class="sidebar-nav" aria-label="Main navigation">
        <a class="nav-link<?= $isActiveRoute('dashboard') ?>" href="./dashboard"<?= $route === 'dashboard' ? ' aria-current="page"' : '' ?>><i class="ti ti-home"></i><span>Dashboard</span></a>
        <a class="nav-link<?= $isActiveRoute('vault') ?>" href="./vault"<?= $route === 'vault' ? ' aria-current="page"' : '' ?>><i class="ti ti-shield-lock"></i><span>Vault</span></a>
        <a class="nav-link<?= $isActiveRoute('notes') ?>" href="./notes"<?= $route === 'notes' ? ' aria-current="page"' : '' ?>><i class="ti ti-notes"></i><span>Notes</span></a>
        <a class="nav-link<?= $isActiveRoute('tasks') ?>" href="./tasks"<?= $route === 'tasks' ? ' aria-current="page"' : '' ?>><i class="ti ti-checkbox"></i><span>Tasks</span></a>
        <a class="nav-link<?= $isActiveRoute('folders') ?>" href="./folders"<?= $route === 'folders' ? ' aria-current="page"' : '' ?>><i class="ti ti-folder"></i><span>Folders</span></a>
        <a class="nav-link<?= $isActiveRoute('favorites') ?>" href="./favorites"<?= $route === 'favorites' ? ' aria-current="page"' : '' ?>><i class="ti ti-star"></i><span>Favorites</span></a>
    </nav>
    <nav class="sidebar-nav sidebar-bottom" aria-label="Account navigation">
        <hr class="nav-separator">
        <a class="nav-link<?= $isActiveRoute('settings') ?>" href="./settings"<?= $route === 'settings' ? ' aria-current="page"' : '' ?>><i class="ti ti-settings"></i><span>Settings</span></a>
        <a class="nav-link" href="./logout" data-logout-trigger><i class="ti ti-logout"></i><span>Logout</span></a>
    </nav>
    <div class="sidebar-illustration"><img src="./assets/images/squirrel.gif" alt="Squir mascot"></div>
</aside>

<div class="sq-lo-backdrop" id="logoutModalBackdrop" aria-hidden="true">
    <div class="sq-lo-modal" role="alertdialog" aria-modal="true" aria-labelledby="logoutModalTitle" aria-describedby="logoutModalText">
        <img class="sq-lo-image" src="./assets/images/squir-logout.png" alt="" width="167" height="176">

        <h2 class="sq-lo-title" id="logoutModalTitle"><i class="ti ti-door-exit" aria-hidden="true"></i> Log Out?</h2>
        <p class="sq-lo-text" id="logoutModalText">Are you sure you want to log out?</p>

        <div class="sq-lo-actions">
            <button type="button" class="sq-lo-btn sq-lo-btn-cancel" id="logoutModalCancel">Cancel</button>
            <a class="sq-lo-btn sq-lo-btn-confirm" id="logoutModalConfirm" href="./logout">Log out</a>
        </div>
    </div>
</div>