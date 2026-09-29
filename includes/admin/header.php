<?php
$adminName = $adminName ?? 'Administrator';
$adminEmail = $adminEmail ?? '';
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$initials = adminInitials($adminName);
?>
<header class="topbar">
    <div class="topbar-inner">
        <button class="mobile-toggle-btn" id="mobileBtn" type="button" aria-label="Open menu"><i class="ti ti-menu-2"></i></button>
        <a class="topbar-brand" href="<?= url('admin/dashboard') ?>" aria-label="Squir admin dashboard"><img src="<?= url('assets/images/squir.png') ?>" alt=""><span class="topbar-brand-text">Squir<small class="topbar-brand-role">Administrator</small></span></a>
        <button class="search-toggle-btn" id="searchToggle" type="button" aria-label="Open search" aria-expanded="false"><i class="ti ti-search"></i></button>
        <div class="search-box" id="adminSearch">
            <i class="ti ti-search"></i>
            <input type="search" placeholder="Search users, activities..." aria-label="Search users, activities" autocomplete="off">
            <button class="search-close-btn" id="searchClose" type="button" aria-label="Close search"><i class="ti ti-x"></i></button>
        </div>
        <div class="topbar-actions">
            <button class="theme-toggle" id="themeToggle" type="button" aria-label="Toggle theme"><i class="ti ti-moon"></i></button>
            <div class="profile-menu" id="profileMenu">
                <button class="profile" id="profileBtn" type="button" aria-haspopup="menu" aria-expanded="false">
                    <span class="avatar"><?= $escape($initials) ?></span><span class="profile-name"><?= $escape($adminName) ?></span><i class="ti ti-chevron-down"></i>
                </button>
                <div class="profile-dropdown" role="menu">
                    <div class="profile-dropdown-head">
                        <strong><?= $escape($adminName) ?></strong>
                        <small><?= $escape($adminEmail) ?></small>
                    </div>
                    <a href="<?= url('admin/settings') ?>" role="menuitem"><i class="ti ti-settings"></i> Settings</a>
                    <a href="<?= url('admin/logout') ?>" role="menuitem" data-logout-trigger onclick="return confirm('Log out of the admin panel?')"><i class="ti ti-logout"></i> Log Out</a>
                </div>
            </div>
        </div>
    </div>
</header>