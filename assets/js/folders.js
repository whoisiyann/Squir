(function () {
    function $all(selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); }
    var csrfToken = window.FOLDERS_CSRF_TOKEN || '';
    var grid = document.getElementById('foldersGrid');
    var popover = document.getElementById('folderColorPopover');
    var popoverFolderId = null;

    /* ---------- Grid / List view toggle ---------- */
    var VIEW_KEY = 'squir-folder-view';
    var viewButtons = $all('.view-toggle-btn');

    function setView(view) {
        var isList = view === 'list';
        if (grid) grid.classList.toggle('list-view', isList);

        document.body.classList.toggle('folders-list-view', isList);
        document.documentElement.classList.remove('folders-list-view-preload');

        viewButtons.forEach(function (btn) {
            var isActive = btn.getAttribute('data-view') === view;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
        try { window.localStorage.setItem(VIEW_KEY, view); } catch (error) {}
    }

    viewButtons.forEach(function (btn) {
        btn.addEventListener('click', function () { setView(btn.getAttribute('data-view')); });
    });

    var savedView = null;
    try { savedView = window.localStorage.getItem(VIEW_KEY); } catch (error) {}
    setView(savedView === 'list' ? 'list' : 'grid');

    function isListView() {
        return grid && grid.classList.contains('list-view');
    }

    /* ---------- Color popover ---------- */
    function closePopover() {
        popover.classList.remove('open');
        popoverFolderId = null;
        $all('.folder-swatch', popover).forEach(function (btn) {
            btn.classList.toggle('active', false);
        });
    }

    function openPopoverFor(iconBtn, folderId) {
        popoverFolderId = folderId;
        var currentColor = iconBtn.getAttribute('data-color');
        $all('.folder-swatch', popover).forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-color') === currentColor);
        });

        var rect = iconBtn.getBoundingClientRect();
        popover.classList.add('open');
        var popWidth = popover.offsetWidth || 188;
        var left = Math.min(rect.left, window.innerWidth - popWidth - 8);
        popover.style.left = Math.max(8, left) + 'px';
        popover.style.top = (rect.bottom + 8) + 'px';
    }

    function updateFolderColor(folderId, color, iconBtn) {
        var body = new URLSearchParams();
        body.set('ajax', 'update_color');
        body.set('csrf_token', csrfToken);
        body.set('folder_id', folderId);
        body.set('color', color);

        return fetch('./folders', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).then(function (response) {
            if (!response.ok) throw new Error('Failed');
            return response.json();
        }).then(function () {
            iconBtn.setAttribute('data-color', color);
            iconBtn.querySelector('img').src = './assets/images/folderImages/Folder-' + color + '.png';
        });
    }

    $all('.folder-swatch', popover).forEach(function (swatch) {
        swatch.addEventListener('click', function () {
            if (!popoverFolderId) return;
            var card = grid.querySelector('.folder-card[data-folder-id="' + popoverFolderId + '"]');
            var iconBtn = card ? card.querySelector('.folder-icon-btn') : null;
            if (iconBtn) updateFolderColor(popoverFolderId, swatch.getAttribute('data-color'), iconBtn);
            closePopover();
        });
    });

    document.addEventListener('click', function (event) {
        if (popover.classList.contains('open') && !popover.contains(event.target) && !event.target.closest('.folder-icon-btn')) {
            closePopover();
        }
    });


    $all('.folder-icon-btn').forEach(function (iconBtn) {
        var card = iconBtn.closest('.folder-card');
        var folderId = card.getAttribute('data-folder-id');

        iconBtn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            openPopoverFor(iconBtn, folderId);
        });
    });

    $all('.folder-card').forEach(function (card) {
        var link = card.querySelector('.folder-card-link');
        if (!link) return;

        card.addEventListener('click', function (event) {
            if (
                event.target.closest('.folder-icon-wrap') ||
                event.target.closest('.folder-card-actions') ||
                event.target.closest('a')
            ) {
                return;
            }
            window.location.href = link.href;
        });
    });

    /* ---------- Create Folder modal ---------- */
    var createBackdrop = document.getElementById('createFolderModalBackdrop');
    var openCreateBtn = document.getElementById('openCreateFolderModal');
    var createColorInput = document.getElementById('createFolderColor');

    function openCreateModal() { createBackdrop.classList.add('open'); }
    function closeCreateModal() { createBackdrop.classList.remove('open'); }

    if (openCreateBtn) openCreateBtn.addEventListener('click', openCreateModal);
    $all('[data-close-folder-modal]').forEach(function (btn) {
        btn.addEventListener('click', closeCreateModal);
    });
    createBackdrop.addEventListener('click', function (event) {
        if (event.target === createBackdrop) closeCreateModal();
    });

    $all('#createFolderSwatches .folder-swatch').forEach(function (swatch) {
        swatch.addEventListener('click', function () {
            $all('#createFolderSwatches .folder-swatch').forEach(function (s) { s.classList.remove('active'); });
            swatch.classList.add('active');
            createColorInput.value = swatch.getAttribute('data-color');
        });
    });

    /* Auto-open the create modal when the page was reloaded with form errors.
       (Flag is set from PHP in views/folders/index.php via window.FOLDERS_HAS_ERRORS,
       since this is a plain .js file and can't contain <?php ?> tags directly.) */
    if (window.FOLDERS_HAS_ERRORS) {
        openCreateModal();
    }

    /* ---------- Favorite toggle ---------- */
    function toggleFavorite(folderId) {
        var body = new URLSearchParams();
        body.set('ajax', 'toggle_favorite');
        body.set('csrf_token', csrfToken);
        body.set('folder_id', folderId);
        return fetch('./folders', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).then(function (response) {
            if (!response.ok) throw new Error('Failed');
            return response.json();
        });
    }

    $all('.folder-favorite-btn').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            var card = btn.closest('.folder-card');
            var folderId = card.getAttribute('data-folder-id');
            var wasFav = card.getAttribute('data-favorite') === '1';

            card.setAttribute('data-favorite', wasFav ? '0' : '1');
            btn.classList.toggle('is-fav', !wasFav);

            toggleFavorite(folderId).catch(function () {
                card.setAttribute('data-favorite', wasFav ? '1' : '0');
                btn.classList.toggle('is-fav', wasFav);
            });
        });
    });

    /* ---------- 3-dot menu: rename ---------- */
    function closeAllMenus() {
        $all('.folder-menu-dropdown.open').forEach(function (menu) { menu.classList.remove('open'); });
    }

    $all('.folder-menu-btn').forEach(function (btn) {
        var menu = btn.parentElement.querySelector('.folder-menu-dropdown');
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            var willOpen = !menu.classList.contains('open');
            closeAllMenus();
            if (willOpen) menu.classList.add('open');
        });
    });
    document.addEventListener('click', closeAllMenus);

    $all('.folder-menu-rename').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            closeAllMenus();

            var card = btn.closest('.folder-card');
            var folderId = card.getAttribute('data-folder-id');
            var nameEl = card.querySelector('.folder-name');

            window.SquirDialogs.promptName({
                title: 'Rename Folder',
                label: 'Rename to',
                value: nameEl.textContent.trim(),
                onSubmit: function (newName) {
                    var body = new URLSearchParams();
                    body.set('ajax', 'rename');
                    body.set('csrf_token', csrfToken);
                    body.set('folder_id', folderId);
                    body.set('folder_name', newName);

                    return fetch('./folders', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: body.toString()
                    }).then(function (response) {
                        return response.json().catch(function () { return {}; }).then(function (json) {
                            if (!response.ok) throw new Error(json.error || 'Could not rename folder.');
                            nameEl.textContent = newName;
                        });
                    });
                }
            });
        });
    });
})();