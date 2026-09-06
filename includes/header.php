<!-- Squir/includes/header.php -->

<header class="topbar">
    <div class="topbar-inner">
        <button class="mobile-toggle-btn" id="mobileBtn" type="button" aria-label="Open menu"><i class="ti ti-menu-2"></i></button>
        <div class="search-box"><i class="ti ti-search"></i><input type="search" placeholder="Search anything..." aria-label="Search"></div>
        <div class="topbar-actions">
            <button class="icon-btn" type="button" aria-label="Notifications"><i class="ti ti-bell"></i><span class="notification-dot"></span></button>
            <button class="theme-toggle" id="themeToggle" type="button" aria-label="Toggle theme"><i class="ti ti-moon"></i></button>
            <div class="profile"><span class="avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span><span class="profile-name"><?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?></span><i class="ti ti-chevron-down"></i></div>
        </div>
    </div>
</header>