<?php
$route = $route ?? '';
$isActiveRoute = static fn (string $r): string => $route === $r ? ' active' : '';
?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<aside class="sidebar" id="appSidebar">
    <div class="brand-row">
        <button class="sidebar-collapse-btn" id="collapseBtn" type="button" aria-label="Collapse sidebar"><i class="ti ti-menu-2"></i></button>
        <a class="brand" href="./dashboard"><img src="./assets/images/squir.png" alt=""><span>Squir</span></a>
    </div>
    <nav class="sidebar-nav" aria-label="Main navigation">
        <a class="nav-link<?= $isActiveRoute('dashboard') ?>" href="./dashboard"<?= $route === 'dashboard' ? ' aria-current="page"' : '' ?>><i class="ti ti-home"></i><span>Dashboard</span></a>
        <a class="nav-link<?= $isActiveRoute('vault') ?>" href="./vault"<?= $route === 'vault' ? ' aria-current="page"' : '' ?>><i class="ti ti-key"></i><span>Vault</span></a>
        <a class="nav-link<?= $isActiveRoute('notes') ?>" href="./notes"<?= $route === 'notes' ? ' aria-current="page"' : '' ?>><i class="ti ti-notes"></i><span>Notes</span></a>
        <a class="nav-link<?= $isActiveRoute('tasks') ?>" href="./tasks"<?= $route === 'tasks' ? ' aria-current="page"' : '' ?>><i class="ti ti-checkbox"></i><span>Tasks</span></a>
        <a class="nav-link<?= $isActiveRoute('folders') ?>" href="./folders"<?= $route === 'folders' ? ' aria-current="page"' : '' ?>><i class="ti ti-folder"></i><span>Folders</span></a>
        <a class="nav-link<?= $isActiveRoute('favorites') ?>" href="./favorites"<?= $route === 'favorites' ? ' aria-current="page"' : '' ?>><i class="ti ti-star"></i><span>Favorites</span></a>
    </nav>
    <nav class="sidebar-nav sidebar-bottom" aria-label="Account navigation">
        <hr class="nav-separator">
        <a class="nav-link<?= $isActiveRoute('settings') ?>" href="./settings"<?= $route === 'settings' ? ' aria-current="page"' : '' ?>><i class="ti ti-settings"></i><span>Settings</span></a>
        <a class="nav-link" href="./logout"><i class="ti ti-logout"></i><span>Logout</span></a>
    </nav>
    <div class="sidebar-illustration"><img src="./assets/images/squirrel.gif" alt="Squir mascot"></div>
</aside>
