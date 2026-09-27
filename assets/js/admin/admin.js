// Shared admin panel behavior.
(function () {
    var shell = document.getElementById('adminShell');
    var collapseButton = document.getElementById('collapseBtn');
    var mobileButton = document.getElementById('mobileBtn');
    var backdrop = document.getElementById('sidebarBackdrop');
    var themeButton = document.getElementById('themeToggle');
    var storageKey = 'squir-admin-sidebar-collapsed';
    var themeStorageKey = 'squir-admin-theme';

    // Apply the admin theme.
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

    function getThemePreference() {
        try {
            return window.localStorage.getItem(themeStorageKey) === 'dark' ? 'dark' : 'light';
        } catch (error) {
            return 'light';
        }
    }

    function setThemePreference(pref, animate) {
        var isDark = pref === 'dark';
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
    }

    setThemePreference(getThemePreference());

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

    // Reveal the link when the panel reaches the bottom.
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
