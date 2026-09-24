(function () {
    function $all(selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); }

    var csrfToken = window.VAULT_CSRF_TOKEN || '';
    var wrap = document.getElementById('favoritesWrap');
    var itemType = wrap ? wrap.getAttribute('data-item-type') : null; // 'vault' | 'note' | 'folder'

    var CONFIG = {
        vault:  { action: './vault',   field: 'vault_id',  rowSelector: 'tr[data-item-id]',           favSelector: '.vault-favorite-btn',  label: 'password' },
        note:   { action: './notes',   field: 'note_id',   rowSelector: '.note-card[data-item-id]',   favSelector: '.note-favorite-btn',   label: 'note' },
        folder: { action: './folders', field: 'folder_id', rowSelector: '.folder-card[data-item-id]', favSelector: '.folder-favorite-btn', label: 'folder' }
    };
    var cfg = CONFIG[itemType] || null;

    var VIEW_KEY = 'squir-favorites-view';
    var viewButtons = $all('.view-toggle-btn');

    // Switch the favorites layout view
    function setView(view) {
        if (wrap) {
            if (itemType === 'folder') {
                wrap.classList.toggle('list-view', view === 'list');
            } else {
                wrap.classList.toggle('grid-view', view === 'grid');
            }
        }
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

    if (!wrap || !cfg) return;

    // Filter favorites as you type
    var searchInput = document.getElementById('favoritesSearchInput');
    var searchForm = searchInput ? searchInput.closest('form') : null;
    var noResultsEl = null;
    var lastSearchTerm = (searchInput && searchInput.value) ? searchInput.value.trim().toLowerCase() : '';

    // Build (once) the "no matches" placeholder shown when a search has zero hits
    function ensureNoResultsEl() {
        if (noResultsEl) return noResultsEl;
        noResultsEl = document.createElement('div');
        noResultsEl.className = 'empty-state favorites-empty favorites-no-search-results';
        noResultsEl.style.display = 'none';
        noResultsEl.innerHTML = '<i class="ti ti-search"></i><p>No matches</p><small>Try a different search term.</small>';
        wrap.parentNode.insertBefore(noResultsEl, wrap.nextSibling);
        return noResultsEl;
    }

    // Show/hide each favorite row based on whether its text matches the search term
    function applySearch(term) {
        lastSearchTerm = (term || '').trim().toLowerCase();
        var rows = $all(cfg.rowSelector, wrap);
        var matchCount = 0;

        rows.forEach(function (row) {
            var matches = lastSearchTerm === '' || row.textContent.toLowerCase().indexOf(lastSearchTerm) !== -1;
            row.style.display = matches ? '' : 'none';
            if (matches) matchCount++;
        });

        var countEl = document.querySelector('.vault-pagination-count');
        if (countEl) {
            countEl.innerHTML = '<i class="ti ti-star"></i> ' + matchCount + ' favorite ' + cfg.label + (matchCount === 1 ? '' : 's');
        }

        var showEmpty = rows.length > 0 && matchCount === 0;
        ensureNoResultsEl().style.display = showEmpty ? '' : 'none';
        wrap.style.display = showEmpty ? 'none' : '';
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            applySearch(searchInput.value);
        });

        // Enter/submit just re-applies the same live filter instead of reloading the page
        if (searchForm) {
            searchForm.addEventListener('submit', function (event) {
                event.preventDefault();
                applySearch(searchInput.value);
            });
        }

        if (lastSearchTerm) applySearch(searchInput.value);
    }

    // Remove a favorite
    function toggleFavoriteRequest(id) {
        var body = new URLSearchParams();
        body.set('ajax', 'toggle_favorite');
        body.set('csrf_token', csrfToken);
        body.set(cfg.field, id);
        return fetch(cfg.action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).then(function (response) {
            if (!response.ok) throw new Error('Failed to update favorite');
            return response.json();
        });
    }

    function updateCount() {
        var rows = $all(cfg.rowSelector, wrap);
        var remaining = rows.filter(function (row) { return row.style.display !== 'none'; }).length;
        var countEl = document.querySelector('.vault-pagination-count');
        if (countEl) {
            countEl.innerHTML = '<i class="ti ti-star"></i> ' + remaining + ' favorite ' + cfg.label + (remaining === 1 ? '' : 's');
        }
        if (rows.length === 0) {
            // Let the server render the correct empty state for this type
            window.location.reload();
        } else if (lastSearchTerm && remaining === 0) {
            ensureNoResultsEl().style.display = '';
            wrap.style.display = 'none';
        }
    }

    function removeRow(row) {
        row.style.transition = 'opacity .18s ease';
        row.style.opacity = '0';
        setTimeout(function () {
            row.parentNode && row.parentNode.removeChild(row);
            updateCount();
        }, 180);
    }

    $all(cfg.favSelector).forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var row = btn.closest(cfg.rowSelector);
            if (!row || btn.disabled) return;

            var id = row.getAttribute('data-item-id');
            btn.disabled = true;

            toggleFavoriteRequest(id).then(function (json) {
                if (json && json.is_favorite === false) {
                    removeRow(row);
                } else {
                    btn.disabled = false;
                }
            }).catch(function () {
                btn.disabled = false;
            });
        });
    });

    /* Open folder cards */
    if (itemType === 'folder') {
        $all(cfg.rowSelector, wrap).forEach(function (card) {
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
    }

    if (itemType === 'vault') {
        function revealById(id, reason) {
            return window.SquirPin.ensure(reason).then(function () {
                return fetch('./vault?ajax=reveal&id=' + encodeURIComponent(id) + '&token=' + encodeURIComponent(csrfToken))
                    .then(function (response) {
                        if (!response.ok) throw new Error('Failed to fetch password');
                        return response.json();
                    });
            });
        }

        function legacyCopy(text) {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.top = '-1000px';
            document.body.appendChild(textarea);
            textarea.select();
            textarea.setSelectionRange(0, text.length);
            var ok = false;
            try { ok = document.execCommand('copy'); } catch (error) { ok = false; }
            document.body.removeChild(textarea);
            return ok;
        }

        function copyPasswordToClipboard(passwordPromise, onSuccess) {
            passwordPromise.then(function (text) {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(onSuccess).catch(function () {
                        if (legacyCopy(text)) onSuccess();
                    });
                } else if (legacyCopy(text)) {
                    onSuccess();
                }
            }).catch(function () {
                alert('Could not get the password. Please try again.');
            });
        }

        $all('.vault-reveal-btn', wrap).forEach(function (btn) {
            btn.addEventListener('click', function () {
                var row = btn.closest('tr');
                var id = row.getAttribute('data-item-id');
                var cell = row.querySelector('.vault-password');
                var icon = btn.querySelector('i');

                if (cell.getAttribute('data-revealed') === 'true') {
                    cell.textContent = '**********';
                    cell.setAttribute('data-revealed', 'false');
                    icon.className = 'ti ti-eye';
                    btn.setAttribute('data-tooltip', 'Show password');
                    btn.setAttribute('aria-label', 'Show password');
                    return;
                }

                revealById(id, 'view').then(function (json) {
                    cell.textContent = json.password;
                    cell.setAttribute('data-revealed', 'true');
                    icon.className = 'ti ti-eye-off';
                    btn.setAttribute('data-tooltip', 'Hide password');
                    btn.setAttribute('aria-label', 'Hide password');
                }).catch(function () {
                    cell.textContent = 'Error';
                });
            });
        });

        $all('.vault-copy-btn', wrap).forEach(function (btn) {
            btn.addEventListener('click', function () {
                var row = btn.closest('tr');
                var id = row.getAttribute('data-item-id');
                var cell = row.querySelector('.vault-password');
                var icon = btn.querySelector('i');

                var passwordPromise;
                if (cell && cell.getAttribute('data-revealed') === 'true') {
                    passwordPromise = Promise.resolve(cell.textContent);
                } else {
                    passwordPromise = revealById(id, 'copy').then(function (json) { return json.password; });
                }

                copyPasswordToClipboard(passwordPromise, function () {
                    icon.className = 'ti ti-check';
                    btn.setAttribute('data-tooltip', 'Copied!');
                    setTimeout(function () {
                        icon.className = 'ti ti-copy';
                        btn.setAttribute('data-tooltip', 'Copy');
                    }, 1200);
                });
            });
        });

        $all('.vault-menu-copy-username', wrap).forEach(function (btn) {
            btn.addEventListener('click', function () {
                var username = btn.getAttribute('data-username') || '';
                if (!username) return;
                navigator.clipboard.writeText(username);
                var original = btn.innerHTML;
                btn.innerHTML = '<i class="ti ti-check"></i> Copied!';
                setTimeout(function () { btn.innerHTML = original; }, 1200);
            });
        });
    }

    var MENU_BTN_SELECTOR = '.vault-menu-btn, .note-menu-btn, .folder-menu-btn';
    var MENU_DROPDOWN_SELECTOR = '.vault-menu-dropdown, .note-menu-dropdown, .folder-menu-dropdown';
    var MENU_DROPDOWN_OPEN_SELECTOR = '.vault-menu-dropdown.open, .note-menu-dropdown.open, .folder-menu-dropdown.open';

    function closeAllMenus() {
        $all(MENU_DROPDOWN_OPEN_SELECTOR).forEach(function (menu) {
            menu.classList.remove('open');
            menu.style.top = '';
            menu.style.left = '';
            if (menu._favMenuPlaceholder && menu._favMenuPlaceholder.parentNode) {
                menu._favMenuPlaceholder.parentNode.replaceChild(menu, menu._favMenuPlaceholder);
                menu._favMenuPlaceholder = null;
            }
        });
    }

    function positionMenu(menu, btn) {
        var rect = btn.getBoundingClientRect();
        var menuWidth = menu.offsetWidth || 190;
        var left = Math.max(8, Math.min(rect.right - menuWidth, window.innerWidth - menuWidth - 8));
        var top = rect.bottom + 6;
        var menuHeight = menu.offsetHeight || 0;
        if (menuHeight && top + menuHeight > window.innerHeight - 8) {
            top = rect.top - menuHeight - 6;
        }
        menu.style.left = left + 'px';
        menu.style.top = top + 'px';
    }

    $all(MENU_BTN_SELECTOR, wrap).forEach(function (btn) {
        var menu = btn.parentElement.querySelector(MENU_DROPDOWN_SELECTOR);
        if (!menu) return;

        btn.addEventListener('click', function (event) {
            event.stopPropagation();
            var willOpen = !menu.classList.contains('open');
            closeAllMenus();
            if (willOpen) {
                var placeholder = document.createElement('span');
                placeholder.style.display = 'none';
                menu.parentNode.insertBefore(placeholder, menu);
                menu._favMenuPlaceholder = placeholder;
                document.body.appendChild(menu);

                menu.classList.add('open');
                positionMenu(menu, btn);
            }
        });
    });

    document.addEventListener('click', closeAllMenus);
    window.addEventListener('resize', closeAllMenus);
    wrap.addEventListener('scroll', closeAllMenus, { passive: true });
})();