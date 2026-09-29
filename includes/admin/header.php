<?php
$adminName = $adminName ?? 'Administrator';
$adminEmail = $adminEmail ?? '';
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$initials = adminInitials($adminName);
?>
<header class="topbar">
    <div class="topbar-inner">
        <button class="mobile-toggle-btn" id="mobileBtn" type="button" aria-label="Open menu"><i class="ti ti-menu-2"></i></button>
        <div class="search-box" id="adminSearch">
            <i class="ti ti-search"></i>
            <input type="search" placeholder="Search users, activities..." aria-label="Search users, activities" autocomplete="off">
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
