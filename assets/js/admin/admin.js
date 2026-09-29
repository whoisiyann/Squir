// Admin behavior
(function () {
    var shell = document.getElementById('adminShell');
    var collapseButton = document.getElementById('collapseBtn');
    var mobileButton = document.getElementById('mobileBtn');
    var backdrop = document.getElementById('sidebarBackdrop');
    var themeButton = document.getElementById('themeToggle');
    var storageKey = 'squir-admin-sidebar-collapsed';
    var themeStorageKey = 'squir-admin-theme';

    // Apply theme
    function applyTheme(isDark) {
        document.body.classList.toggle('dashboard-dark', isDark);
        document.documentElement.classList.remove('dashboard-dark-preload');
        if (!themeButton) return;

        var knob = themeButton.querySelector('i');
        if (knob) knob.className = isDark ? 'ti ti-sun' : 'ti ti-moon';
        themeButton.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        themeButton.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        themeButton.setAttribute('title', isDark ? 'Light Mode' : 'Dark Mode');
    }

    function prefersReducedMotion() {
        return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function runThemeTransition(applyFn) {
        if (!document.startViewTransition || prefersReducedMotion()) {
            applyFn();
            return;
        }
        var transition = document.startViewTransition(applyFn);
        transition.ready.then(function () {
            document.documentElement.animate(
                {
                    clipPath: [
                        'polygon(0% 0%, 0% 0%, 0% 0%)',
                        'polygon(0% 0%, 200% 0%, 0% 200%)'
                    ]
                },
                { duration: 650, easing: 'cubic-bezier(0.65, 0, 0.35, 1)', pseudoElement: '::view-transition-new(root)' }
            );
        }).catch(function () {});
    }

    var systemDark = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

    // Saved choice: light, dark, or auto (follow the device).
    function getThemePreference() {
        try {
            var stored = window.localStorage.getItem(themeStorageKey);
            return stored === 'dark' || stored === 'auto' ? stored : 'light';
        } catch (error) {
            return 'light';
        }
    }

    function resolveDark(pref) {
        if (pref === 'auto') return !!(systemDark && systemDark.matches);
        return pref === 'dark';
    }

    // Save the choice to the account so it survives logout and other browsers
    function savePrefsToServer(fields) {
        var token = window.ADMIN_CSRF_TOKEN || '';
        var link = document.querySelector('a[href*="admin/settings"]');
        if (!token || !link || !window.fetch) return;

        var body = new URLSearchParams();
        body.set('ajax', 'save_preferences');
        body.set('csrf_token', token);
        Object.keys(fields).forEach(function (key) { body.set(key, fields[key]); });

        fetch(link.getAttribute('href').split('?')[0], {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: body.toString()
        }).catch(function () {});
    }

    function setThemePreference(pref, animate) {
        var isDark = resolveDark(pref);
        if (animate) {
            runThemeTransition(function () { applyTheme(isDark); });
        } else {
            applyTheme(isDark);
        }
        try {
            window.localStorage.setItem(themeStorageKey, pref);
        } catch (error) {
            // Storage may be unavailable.
        }
        document.dispatchEvent(new CustomEvent('squir-admin-theme', { detail: pref }));
        // Only user actions pass animate
        if (animate) savePrefsToServer({ theme: pref });
    }

    setThemePreference(getThemePreference());

    // Follow the device theme while set to Auto.
    if (systemDark) {
        var onSystemThemeChange = function () {
            if (getThemePreference() === 'auto') applyTheme(resolveDark('auto'));
        };
        if (systemDark.addEventListener) systemDark.addEventListener('change', onSystemThemeChange);
        else if (systemDark.addListener) systemDark.addListener(onSystemThemeChange);
    }

    /* Accent color */
    var accentStorageKey = 'squir-admin-accent';
    var accentSwal = {
        brown: '#6b3f2a', blue: '#2b5c9e', green: '#2f7a5b',
        purple: '#7a4bc0', orange: '#c77700', rose: '#c2345f'
    };

    function getAccentPreference() {
        try {
            var saved = window.localStorage.getItem(accentStorageKey);
            return accentSwal[saved] ? saved : 'brown';
        } catch (error) {
            return 'brown';
        }
    }

    function applyAccent(name) {
        var root = document.documentElement;
        if (!accentSwal[name] || name === 'brown') {
            root.removeAttribute('data-accent');
        } else {
            root.setAttribute('data-accent', name);
        }
    }

    function setAccentPreference(name) {
        if (!accentSwal[name]) name = 'brown';
        applyAccent(name);
        try {
            window.localStorage.setItem(accentStorageKey, name);
        } catch (error) {
            // Storage may be unavailable.
        }
        savePrefsToServer({ accent: name });
    }

    applyAccent(getAccentPreference());

    // Used by the Settings page.
    window.SquirAdminTheme = {
        get: getThemePreference,
        set: function (pref) { setThemePreference(pref, true); },
        getAccent: getAccentPreference,
        setAccent: setAccentPreference,
        swalColor: function () { return accentSwal[getAccentPreference()]; }
    };

    if (themeButton) {
        themeButton.addEventListener('click', function () {
            var isDark = !document.body.classList.contains('dashboard-dark');
            setThemePreference(isDark ? 'dark' : 'light', true);
        });
    }

    function syncCollapseLabel(isCollapsed) {
        if (!collapseButton) return;
        var label = isCollapsed ? 'Expand sidebar' : 'Collapse sidebar';
        collapseButton.setAttribute('aria-label', label);
        collapseButton.setAttribute('title', label);
    }

    if (shell && collapseButton) {
        syncCollapseLabel(shell.classList.contains('sidebar-collapsed'));

        collapseButton.addEventListener('click', function () {
            if (window.matchMedia('(max-width: 700px)').matches) {
                shell.classList.remove('mobile-open');
                return;
            }
            var isCollapsed = shell.classList.toggle('sidebar-collapsed');
            syncCollapseLabel(isCollapsed);
            try {
                window.localStorage.setItem(storageKey, isCollapsed ? '1' : '0');
            } catch (error) {
                // Storage may be unavailable.
            }
        });
    }

    if (shell && mobileButton) {
        mobileButton.addEventListener('click', function () {
            shell.classList.toggle('mobile-open');
        });
    }

    if (shell && backdrop) {
        backdrop.addEventListener('click', function () {
            shell.classList.remove('mobile-open');
        });
    }

    var profileMenu = document.getElementById('profileMenu');
    var profileButton = document.getElementById('profileBtn');

    function setProfileMenu(open) {
        if (!profileMenu || !profileButton) return;
        profileMenu.classList.toggle('open', open);
        profileButton.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (profileMenu && profileButton) {
        profileButton.addEventListener('click', function (event) {
            event.stopPropagation();
            setProfileMenu(!profileMenu.classList.contains('open'));
        });
        document.addEventListener('click', function (event) {
            if (!profileMenu.contains(event.target)) setProfileMenu(false);
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') setProfileMenu(false);
        });
    }

    // Reveal link at panel bottom
    function setupScrollReveal(scrollId, moreId) {
        var scrollArea = document.getElementById(scrollId);
        var moreBlock = document.getElementById(moreId);
        if (!scrollArea || !moreBlock) return;

        function check() {
            var atBottom = scrollArea.scrollHeight - scrollArea.scrollTop - scrollArea.clientHeight <= 4;
            moreBlock.classList.toggle('is-visible', atBottom);
        }

        scrollArea.addEventListener('scroll', check);
        window.addEventListener('resize', check);
        check();
    }

    setupScrollReveal('recentUsersScroll', 'recentUsersMore');
    setupScrollReveal('recentActivityScroll', 'recentActivityMore');
})();

// Mobile topbar: hide on scroll down, show on scroll up, tap search icon to search
(function () {
    var topbar = document.querySelector('.topbar');
    if (!topbar) return;

    var mobileQuery = window.matchMedia('(max-width: 700px)');
    var lastY = 0;

    function showTopbar() {
        topbar.classList.remove('topbar-hidden');
        document.body.classList.remove('topbar-is-hidden');
    }

    function hideTopbar() {
        var profileMenu = document.getElementById('profileMenu');
        // Keep the header visible while a menu, the sidebar or search is open
        if (topbar.classList.contains('search-open')) return;
        if (profileMenu && profileMenu.classList.contains('open')) return;
        if (document.querySelector('.mobile-open')) return;
        topbar.classList.add('topbar-hidden');
        document.body.classList.add('topbar-is-hidden');
    }

    // Scroll events do not bubble, so listen in the capture phase
    document.addEventListener('scroll', function (event) {
        var target = event.target;
        var isPage = target === document;
        if (!isPage && !(target.classList && target.classList.contains('content-area'))) return;

        if (!mobileQuery.matches) {
            showTopbar();
            return;
        }

        var y = Math.max(0, isPage ? (window.pageYOffset || 0) : target.scrollTop);
        var delta = y - lastY;
        if (Math.abs(delta) < 8) return; // ignore tiny movements

        if (y <= 0 || delta < 0) {
            showTopbar();
        } else if (y > topbar.offsetHeight) {
            hideTopbar();
        }
        lastY = y;
    }, { passive: true, capture: true });

    if (mobileQuery.addEventListener) {
        mobileQuery.addEventListener('change', showTopbar);
    }

    // Search icon opens the search bar, X closes it
    var searchToggle = document.getElementById('searchToggle');
    var searchClose = document.getElementById('searchClose');
    var searchBox = topbar.querySelector('.search-box');
    var searchInput = searchBox ? searchBox.querySelector('input') : null;

    function setSearchOpen(open) {
        topbar.classList.toggle('search-open', open);
        if (searchToggle) searchToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            showTopbar();
            if (searchInput) searchInput.focus();
        } else if (searchInput) {
            searchInput.value = '';
            searchInput.blur();
            // let the existing search code clear its results panel
            searchInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    if (searchToggle) {
        searchToggle.addEventListener('click', function () { setSearchOpen(true); });
    }
    if (searchClose) {
        searchClose.addEventListener('click', function () { setSearchOpen(false); });
    }
    if (searchInput) {
        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && topbar.classList.contains('search-open')) setSearchOpen(false);
        });
    }
})();