// Shared dashboard shell

(function () {
    var shell = document.getElementById('appShell');
    var collapseButton = document.getElementById('collapseBtn');
    var mobileButton = document.getElementById('mobileBtn');
    var backdrop = document.getElementById('sidebarBackdrop');
    var themeButton = document.getElementById('themeToggle');
    var themeStorageKey = 'squir-dashboard-theme';
    var sidebarStorageKey = 'squir-sidebar-collapsed';

    document.querySelectorAll('.sidebar-nav .nav-link').forEach(function (link) {
        link.addEventListener('click', function () {
            document.querySelectorAll('.sidebar-nav .nav-link.active').forEach(function (activeLink) {
                activeLink.classList.remove('active');
                activeLink.removeAttribute('aria-current');
            });
            link.classList.add('active');
            link.setAttribute('aria-current', 'page');
        });
    });

    /* ---------- Theme switch ---------- */
    // Apply the selected theme
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

    var savedTheme = null;
    try {
        savedTheme = window.localStorage.getItem(themeStorageKey);
    } catch (error) {
        savedTheme = null;
    }
    applyTheme(savedTheme === 'dark');

    if (themeButton) {
        themeButton.addEventListener('click', function () {
            var isDark = !document.body.classList.contains('dashboard-dark');
            applyTheme(isDark);
            try {
                window.localStorage.setItem(themeStorageKey, isDark ? 'dark' : 'light');
            } catch (error) {
                // Apply the current theme
            }
        });
    }

    /* ---------- Sidebar ---------- */
    if (collapseButton) {
        collapseButton.addEventListener('click', function () {
            if (window.matchMedia('(max-width: 700px)').matches) {
                shell.classList.remove('mobile-open');
                return;
            }
            var isCollapsed = shell.classList.toggle('sidebar-collapsed');
            try {
                window.localStorage.setItem(sidebarStorageKey, isCollapsed ? '1' : '0');
            } catch (error) {

            }
        });
    }

    if (mobileButton) {
        mobileButton.addEventListener('click', function () {
            shell.classList.toggle('mobile-open');
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            shell.classList.remove('mobile-open');
        });
    }

    /* ---------- Profile dropdown ---------- */
    var profileMenu = document.getElementById('profileMenu');
    var profileButton = document.getElementById('profileBtn');

    // Toggle the profile menu
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

    /* ---------- Header search ("Search anything...") ---------- */
    (function () {
        var input = document.getElementById('globalSearchInput');
        var panel = document.getElementById('globalSearchResults');
        var box = document.getElementById('globalSearch');
        if (!input || !panel || !box) return;

        var ICONS = { vault: 'ti-key', note: 'ti-notes', task: 'ti-checkbox', folder: 'ti-folder' };
        var timer = null;
        var requestId = 0;
        var links = [];
        var active = -1;

        // Close search results
        function close() {
            panel.classList.remove('open');
            panel.textContent = '';
            input.setAttribute('aria-expanded', 'false');
            links = [];
            active = -1;
        }

        function setActive(index) {
            links.forEach(function (link, i) { link.classList.toggle('active', i === index); });
            active = index;
            if (links[index]) links[index].scrollIntoView({ block: 'nearest' });
        }

        // Render search results
        function render(results, term) {
            panel.textContent = '';
            links = [];
            active = -1;

            if (results.length === 0) {
                var empty = document.createElement('div');
                empty.className = 'search-empty';
                empty.textContent = 'No matches for \u201c' + term + '\u201d.';
                panel.appendChild(empty);
            }

            results.forEach(function (result) {
                var link = document.createElement('a');
                link.className = 'search-result';
                link.href = result.url;
                link.setAttribute('role', 'option');

                var iconWrap = document.createElement('span');
                iconWrap.className = 'search-result-icon';
                var icon = document.createElement('i');
                icon.className = 'ti ' + (ICONS[result.type] || 'ti-search');
                iconWrap.appendChild(icon);

                var text = document.createElement('span');
                text.className = 'search-result-text';
                var title = document.createElement('strong');
                title.textContent = result.title;
                var sub = document.createElement('small');
                sub.textContent = result.subtitle;
                text.appendChild(title);
                text.appendChild(sub);

                link.appendChild(iconWrap);
                link.appendChild(text);
                panel.appendChild(link);
                links.push(link);
            });

            panel.classList.add('open');
            input.setAttribute('aria-expanded', 'true');
        }

        // Search dashboard content
        function run(term) {
            var current = ++requestId;
            fetch('./dashboard?ajax=search&q=' + encodeURIComponent(term), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            }).then(function (response) {
                if (!response.ok) throw new Error('Search failed');
                return response.json();
            }).then(function (json) {
                if (current === requestId) render(json.results || [], term);
            }).catch(function () {
                if (current === requestId) close();
            });
        }

        input.addEventListener('input', function () {
            clearTimeout(timer);
            var term = input.value.trim();
            if (term.length < 2) {
                requestId++;
                close();
                return;
            }
            timer = setTimeout(function () { run(term); }, 220);
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') { close(); return; }
            if (links.length === 0) return;

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                var next = active + (event.key === 'ArrowDown' ? 1 : -1);
                if (next < 0) next = links.length - 1;
                if (next >= links.length) next = 0;
                setActive(next);
            } else if (event.key === 'Enter') {
                event.preventDefault();
                window.location.href = (links[active] || links[0]).href;
            }
        });

        document.addEventListener('click', function (event) {
            if (!box.contains(event.target)) close();
        });
    })();
})();
