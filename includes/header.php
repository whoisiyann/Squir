<header class="topbar">
    <div class="topbar-inner">
        <button class="mobile-toggle-btn" id="mobileBtn" type="button" aria-label="Open menu"><i class="ti ti-menu-2"></i></button>
        <a class="topbar-brand" href="./dashboard" aria-label="Squir dashboard"><img src="./assets/images/squir.png" alt=""><span class="topbar-brand-text">Squir</span></a>
        <button class="search-toggle-btn" id="searchToggle" type="button" aria-label="Open search" aria-expanded="false"><i class="ti ti-search"></i></button>
        <div class="search-box" id="globalSearch">
            <i class="ti ti-search"></i>
            <input type="search" id="globalSearchInput" placeholder="Search anything..." aria-label="Search" autocomplete="off" aria-controls="globalSearchResults" aria-expanded="false">
            <button class="search-close-btn" id="searchClose" type="button" aria-label="Close search"><i class="ti ti-x"></i></button>
            <div class="search-results" id="globalSearchResults" role="listbox" aria-label="Search results"></div>
        </div>
        <div class="topbar-actions">
            <button class="theme-toggle" id="themeToggle" type="button" aria-label="Toggle theme"><i class="ti ti-moon"></i></button>
            <div class="profile-menu" id="profileMenu">
                <button class="profile" id="profileBtn" type="button" aria-haspopup="menu" aria-expanded="false">
                    <span class="avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span><span class="profile-name"><?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?></span><i class="ti ti-chevron-down"></i>
                </button>
                <div class="profile-dropdown" role="menu">
                    <div class="profile-dropdown-head">
                        <strong><?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <small><?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                    <a href="./settings" role="menuitem"><i class="ti ti-settings"></i> Profile Settings</a>
                    <a href="./logout" role="menuitem" data-logout-trigger><i class="ti ti-logout"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>
</header>