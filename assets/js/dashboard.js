(function () {
    var shell = document.getElementById('appShell');
    var collapseButton = document.getElementById('collapseBtn');
    var mobileButton = document.getElementById('mobileBtn');
    var backdrop = document.getElementById('sidebarBackdrop');
    var themeButton = document.getElementById('themeToggle');
    var themeStorageKey = 'squir-dashboard-theme';

    function applyTheme(isDark) {
        document.body.classList.toggle('dashboard-dark', isDark);
        document.documentElement.classList.remove('dashboard-dark-preload');
        themeButton.querySelector('i').className = isDark ? 'ti ti-sun' : 'ti ti-moon';
        themeButton.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        themeButton.setAttribute('title', isDark ? 'Light Mode' : 'Dark Mode');
    }

    var savedTheme = null;
    try {
        savedTheme = window.localStorage.getItem(themeStorageKey);
    } catch (error) {
        savedTheme = null;
    }
    applyTheme(savedTheme === 'dark');

    collapseButton.addEventListener('click', function () {
        if (window.matchMedia('(max-width: 700px)').matches) {
            shell.classList.remove('mobile-open');
            return;
        }
        shell.classList.toggle('sidebar-collapsed');
    });

    mobileButton.addEventListener('click', function () {
        shell.classList.toggle('mobile-open');
    });

    backdrop.addEventListener('click', function () {
        shell.classList.remove('mobile-open');
    });

    themeButton.addEventListener('click', function () {
        var isDark = !document.body.classList.contains('dashboard-dark');
        applyTheme(isDark);
        try {
            window.localStorage.setItem(themeStorageKey, isDark ? 'dark' : 'light');
        } catch (error) {
            // Theme still works for the current page if storage is unavailable.
        }
    });
})();
