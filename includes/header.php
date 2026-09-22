<header class="topbar">
    <div class="topbar-inner">
        <button class="mobile-toggle-btn" id="mobileBtn" type="button" aria-label="Open menu"><i class="ti ti-menu-2"></i></button>
        <div class="search-box" id="globalSearch">
            <i class="ti ti-search"></i>
            <input type="search" id="globalSearchInput" placeholder="Search anything..." aria-label="Search" autocomplete="off" aria-controls="globalSearchResults" aria-expanded="false">
            <div class="search-results" id="globalSearchResults" role="listbox" aria-label="Search results"></div>
        </div>
        <div class="topbar-actions">
            <!-- <button class="icon-btn" type="button" aria-label="Notifications"><i class="ti ti-bell"></i><span class="notification-dot"></span></button> -->
            <button class="theme-toggle" id="themeToggle" type="button" aria-label="Toggle theme"><i class="ti ti-moon"></i></button>
            <div class="profile-menu" id="profileMenu">
                <button class="profile" id="profileBtn" type="button" aria-haspopup="menu" aria-expanded="false">
                    <span class="avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span><span class="profile-name"><?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?></span><i class="ti ti-chevron-down"></i>
                </button>
                <div class="profile-dropdown" role="menu">
                    <div class="profile-dropdown-head">
                        <strong><?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <small>@<?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                    <a href="./logout" role="menuitem"><i class="ti ti-logout"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>
</header>