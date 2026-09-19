(function () {
    function $all(selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); }
    var csrfToken = window.FOLDERS_CSRF_TOKEN || '';
    var grid = document.getElementById('foldersGrid');
    var popover = document.getElementById('folderColorPopover');
    var popoverFolderId = null;

    /* ---------- Folder sorting ---------- */
    var SORT_KEY = 'squir-folder-sort';
    var sortLabels = ['Click to sort A to Z', 'Click to sort Z to A', 'Click to sort by newest created', 'Click to sort by recently updated'];
    var sortState = -1;
    var isSorting = false;

    try {
        var savedSortState = parseInt(window.localStorage.getItem(SORT_KEY), 10);
        if (savedSortState >= 0 && savedSortState < sortLabels.length) sortState = savedSortState;
    } catch (error) {}

    function updateSortButton() {
        var sortButton = document.getElementById('foldersSortBtn');
        if (!sortButton) return;

        var nextLabel = sortLabels[(sortState + 1) % sortLabels.length];
        sortButton.setAttribute('aria-label', nextLabel);
        sortButton.setAttribute('data-tooltip', nextLabel);
    }

    function getSortValue(card) {
        var name = card.querySelector('.folder-name');
        return {
            name: name ? name.textContent.trim().toLocaleLowerCase() : '',
            created: Number(card.getAttribute('data-created-at')) || 0,
            updated: Number(card.getAttribute('data-updated-at')) || 0
        };
    }

    function sortCards() {
        if (!grid || sortState < 0) return;

        var cards = $all('.folder-card', grid);
        var sortedCards = cards.slice().sort(function (firstCard, secondCard) {
            var first = getSortValue(firstCard);
            var second = getSortValue(secondCard);
            var comparison;

            if (sortState === 0 || sortState === 1) {
                comparison = first.name.localeCompare(second.name, undefined, { sensitivity: 'base' });
                return sortState === 0 ? comparison : -comparison;
            }

            comparison = sortState === 2 ? second.created - first.created : second.updated - first.updated;
            return comparison || first.name.localeCompare(second.name, undefined, { sensitivity: 'base' });
        });

        if (cards.every(function (card, index) { return card === sortedCards[index]; })) return;

        isSorting = true;
        sortedCards.forEach(function (card) { grid.appendChild(card); });
        isSorting = false;
    }

    function advanceSort() {
        sortState = (sortState + 1) % sortLabels.length;
        try { window.localStorage.setItem(SORT_KEY, String(sortState)); } catch (error) {}
        updateSortButton();
        sortCards();
    }

    document.addEventListener('click', function (event) {
        if (event.target.closest('#foldersSortBtn')) advanceSort();
    });
    updateSortButton();
    sortCards();

    if (grid && window.MutationObserver) {
        new MutationObserver(function () {
            if (!isSorting) sortCards();
        }).observe(grid, { childList: true });
    }

    /* ---------- Search: debounce auto-submit ---------- */
    (function () {
        var searchForm = document.getElementById('foldersSearchForm');
        var searchInput = document.getElementById('foldersSearchInput');
        if (!searchForm || !searchInput) return;

        var searchTimer = null;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { searchForm.submit(); }, 450);
        });

        if (searchInput.value !== '') {
            searchInput.focus();
            var end = searchInput.value.length;
            searchInput.setSelectionRange(end, end);
        }
    })();

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

    /* ---------- 3-dot menu ---------- */
    function closeAllMenus() {
        $all('.folder-menu-dropdown.open').forEach(function (menu) {
            menu.classList.remove('open');
            menu.style.top = '';
            menu.style.left = '';
            if (menu._placeholder && menu._placeholder.parentNode) {
                menu._placeholder.parentNode.replaceChild(menu, menu._placeholder);
                menu._placeholder = null;
            }
        });
    }

    function positionMenu(menu, btn) {
        var rect = btn.getBoundingClientRect();
        var menuWidth = menu.offsetWidth || 170;
        var left = Math.max(8, Math.min(rect.right - menuWidth, window.innerWidth - menuWidth - 8));
        var top = rect.bottom + 6;
        var menuHeight = menu.offsetHeight || 0;
        if (menuHeight && top + menuHeight > window.innerHeight - 8) {
            top = rect.top - menuHeight - 6;
        }
        menu.style.left = left + 'px';
        menu.style.top = top + 'px';
    }

    $all('.folder-menu-btn').forEach(function (btn) {
        var menu = btn.parentElement.querySelector('.folder-menu-dropdown');
        if (!menu) return;

        btn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            var willOpen = !menu.classList.contains('open');
            closeAllMenus();
            if (willOpen) {
                menu._card = btn.closest('.folder-card');

                var placeholder = document.createElement('span');
                placeholder.style.display = 'none';
                menu.parentNode.insertBefore(placeholder, menu);
                menu._placeholder = placeholder;
                document.body.appendChild(menu);

                menu.classList.add('open');
                positionMenu(menu, btn);
            }
        });
    });
    document.addEventListener('click', closeAllMenus);
    window.addEventListener('resize', closeAllMenus);
    document.addEventListener('scroll', closeAllMenus, true); 

    $all('.folder-menu-rename').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();


            var menuEl = btn.closest('.folder-menu-dropdown');
            var card = btn.closest('.folder-card') || (menuEl && menuEl._card);
            closeAllMenus();
            if (!card) return;

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