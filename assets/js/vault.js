// assets/js/vault.js

(function () {
    function $all(selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); }
    var csrfToken = window.VAULT_CSRF_TOKEN || '';
    var tableWrap = document.getElementById('vaultTableWrap');

    /* ---------- Modal open/close ---------- */
    function openModal(name) {
        var backdrop = document.getElementById(name + 'ModalBackdrop');
        if (backdrop) backdrop.classList.add('open');
    }

    function resetCreateForm() {
        var form = document.querySelector('#createModalBackdrop form');
        if (form) form.reset();

        var favBtn = document.getElementById('createFavoriteToggle');
        var favInput = document.getElementById('createIsFavorite');
        if (favBtn && favInput) {
            favBtn.classList.remove('active');
            favBtn.setAttribute('aria-pressed', 'false');
            favInput.value = '0';
        }

        var faviconPreview = document.getElementById('createFaviconPreview');
        if (faviconPreview) faviconPreview.innerHTML = '<i class="ti ti-key"></i>';
    }

    function closeModal(name) {
        var backdrop = document.getElementById(name + 'ModalBackdrop');
        if (backdrop) backdrop.classList.remove('open');
        if (name === 'create') resetCreateForm();
    }

    var openBtn = document.getElementById('openCreateModal');
    if (openBtn) openBtn.addEventListener('click', function () { openModal('create'); });

    $all('[data-close-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            closeModal(btn.getAttribute('data-close-modal'));
        });
    });

    $all('.vault-modal-backdrop').forEach(function (backdrop) {
        backdrop.addEventListener('click', function (event) {
            if (event.target === backdrop) closeModal(backdrop.id.replace('ModalBackdrop', ''));
        });
    });

    var autoOpen = document.body.getAttribute('data-open-modal') || '';
    if (autoOpen === 'create') openModal('create');
    if (autoOpen.indexOf('edit:') === 0 || new URLSearchParams(window.location.search).has('edit')) {
        openModal('edit');
    }

    /* ---------- Show/hide password inside modals ---------- */
    $all('.toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-target'));
            if (!input) return;
            var isPassword = input.type === 'password';

            if (isPassword && input.value === '' && btn.getAttribute('data-vault-id')) {
                revealById(btn.getAttribute('data-vault-id')).then(function (json) {
                    input.value = json.password || '';
                    input.type = 'text';
                    btn.querySelector('i').className = 'ti ti-eye-off';
                    btn.setAttribute('aria-label', 'Hide password');
                });
                return;
            }

            input.type = isPassword ? 'text' : 'password';
            btn.querySelector('i').className = isPassword ? 'ti ti-eye-off' : 'ti ti-eye';
            btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });
    });

    /* ---------- Generate random password ---------- */
    $all('.generate-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-target'));
            if (!input) return;
            var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*';
            var array = new Uint32Array(16);
            window.crypto.getRandomValues(array);
            var value = '';
            for (var i = 0; i < array.length; i++) {
                value += chars[array[i] % chars.length];
            }
            input.value = value;
            input.type = 'text';
            var toggleBtn = input.parentElement.querySelector('.toggle-password');
            if (toggleBtn) {
                toggleBtn.querySelector('i').className = 'ti ti-eye-off';
                toggleBtn.setAttribute('aria-label', 'Hide password');
            }
        });
    });

    /* ---------- Live favicon preview while typing a website URL ---------- */
    function wireFaviconPreview(inputId, previewId) {
        var input = document.getElementById(inputId);
        var preview = document.getElementById(previewId);
        if (!input || !preview) return;

        var timer = null;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                var value = input.value.trim();
                if (value === '') { preview.innerHTML = '<i class="ti ti-key"></i>'; return; }
                var url = /^https?:\/\//i.test(value) ? value : 'https://' + value;
                var host;
                try { host = new URL(url).hostname; }
                catch (error) { preview.innerHTML = '<i class="ti ti-key"></i>'; return; }

                var img = new Image();
                img.onload = function () { preview.innerHTML = ''; preview.appendChild(img); };
                img.onerror = function () { preview.innerHTML = '<i class="ti ti-key"></i>'; };
                img.src = 'https://www.google.com/s2/favicons?sz=64&domain=' + encodeURIComponent(host);
            }, 300);
        });
    }
    wireFaviconPreview('v_url', 'createFaviconPreview');
    wireFaviconPreview('e_url', 'editFaviconPreview');

    /* ---------- Favorite toggle: Add Password modal ---------- */
    var createFavoriteBtn = document.getElementById('createFavoriteToggle');
    var createFavoriteInput = document.getElementById('createIsFavorite');
    if (createFavoriteBtn && createFavoriteInput) {
        createFavoriteBtn.addEventListener('click', function () {
            var isActive = createFavoriteBtn.classList.toggle('active');
            createFavoriteBtn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            createFavoriteInput.value = isActive ? '1' : '0';
        });
    }

    /* ---------- Favorite toggle: Edit Password modal ---------- */
    var editFavoriteBtn = document.getElementById('editFavoriteToggle');
    var editFavoriteInput = document.getElementById('editIsFavorite');
    if (editFavoriteBtn && editFavoriteInput) {
        editFavoriteBtn.addEventListener('click', function () {
            var isActive = editFavoriteBtn.classList.toggle('active');
            editFavoriteBtn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            editFavoriteInput.value = isActive ? '1' : '0';
        });
    }

    /* ---------- Reveal / copy password (server-side decrypt only) ---------- */
    function revealById(id) {
        return fetch('./vault.php?ajax=reveal&id=' + encodeURIComponent(id) + '&token=' + encodeURIComponent(csrfToken))
            .then(function (response) {
                if (!response.ok) throw new Error('Failed to fetch password');
                return response.json();
            });
    }

    $all('.vault-reveal-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = btn.closest('tr');
            var id = row.getAttribute('data-vault-id');
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

            revealById(id).then(function (json) {
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

    function copyPasswordToClipboard(passwordPromise, onSuccess) {
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

        if (window.ClipboardItem && navigator.clipboard && navigator.clipboard.write) {
            var item = new ClipboardItem({
                'text/plain': passwordPromise.then(function (text) {
                    return new Blob([text], { type: 'text/plain' });
                })
            });
            navigator.clipboard.write([item]).then(onSuccess).catch(function () {
                passwordPromise.then(function (text) {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(onSuccess).catch(function () {
                            if (legacyCopy(text)) { onSuccess(); } else { alert('Hindi ma-copy ang password. Subukang i-tap ulit.'); }
                        });
                    } else if (legacyCopy(text)) {
                        onSuccess();
                    } else {
                        alert('Hindi ma-copy ang password. Subukang i-tap ulit.');
                    }
                }).catch(function () {
                    alert('Hindi makuha ang password. Subukang i-tap ulit.');
                });
            });
        } else {
            passwordPromise.then(function (text) {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(onSuccess).catch(function () {
                        if (legacyCopy(text)) { onSuccess(); } else { alert('Hindi ma-copy ang password. Subukang i-tap ulit.'); }
                    });
                } else if (legacyCopy(text)) {
                    onSuccess();
                } else {
                    alert('Hindi ma-copy ang password. Subukang i-tap ulit.');
                }
            }).catch(function () {
                alert('Hindi makuha ang password. Subukang i-tap ulit.');
            });
        }
    }

    $all('.vault-copy-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = btn.closest('tr');
            var id = row.getAttribute('data-vault-id');
            var icon = btn.querySelector('i');
            var passwordPromise = revealById(id).then(function (json) { return json.password; });

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

    /* Copy password button inside the Edit Password modal */
    var editCopyBtn = document.querySelector('.vault-copy-password-btn');
    if (editCopyBtn) {
        editCopyBtn.addEventListener('click', function () {
            var id = editCopyBtn.getAttribute('data-vault-id');
            var passwordPromise = revealById(id).then(function (json) { return json.password; });

            copyPasswordToClipboard(passwordPromise, function () {
                var original = editCopyBtn.textContent;
                editCopyBtn.textContent = 'Copied!';
                setTimeout(function () { editCopyBtn.textContent = original; }, 1200);
            });
        });
    }

    /* ---------- Copy username (from the 3-dot menu) ---------- */
    $all('.vault-menu-copy-username').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var username = btn.getAttribute('data-username') || '';
            if (!username) return;
            navigator.clipboard.writeText(username);
            var original = btn.innerHTML;
            btn.innerHTML = '<i class="ti ti-check"></i> Copied!';
            setTimeout(function () { btn.innerHTML = original; }, 1200);
        });
    });

    /* ---------- 3-dot dropdown menu ---------- */
    function closeAllMenus() {
        $all('.vault-menu-dropdown.open').forEach(function (menu) {
            menu.classList.remove('open');
            menu.style.top = '';
            menu.style.left = '';

            if (menu._vaultMenuPlaceholder && menu._vaultMenuPlaceholder.parentNode) {
                menu._vaultMenuPlaceholder.parentNode.replaceChild(menu, menu._vaultMenuPlaceholder);
                menu._vaultMenuPlaceholder = null;
            }
        });
        $all('tr.vault-menu-open', tableWrap).forEach(function (row) {
            row.classList.remove('vault-menu-open');
        });
    }
    

    function positionMenu(menu, btn) {
        var rect = btn.getBoundingClientRect();
        var menuWidth = menu.offsetWidth || 190;
        var left = rect.right - menuWidth;
        left = Math.min(left, window.innerWidth - menuWidth - 8);
        left = Math.max(8, left);

        var top = rect.bottom + 6;
        var menuHeight = menu.offsetHeight || 0;
        if (menuHeight && top + menuHeight > window.innerHeight - 8) {
            top = rect.top - menuHeight - 6;
        }

        menu.style.left = left + 'px';
        menu.style.top = top + 'px';
    }

    $all('.vault-menu-btn').forEach(function (btn) {
        var menu = btn.parentElement.querySelector('.vault-menu-dropdown'); 

        btn.addEventListener('click', function (event) {
            event.stopPropagation();
            var row = btn.closest('tr');
            var willOpen = !menu.classList.contains('open');
            closeAllMenus();
            if (willOpen) {
                if (row) row.classList.add('vault-menu-open');

                var placeholder = document.createElement('span');
                placeholder.style.display = 'none';
                menu.parentNode.insertBefore(placeholder, menu);
                menu._vaultMenuPlaceholder = placeholder;
                document.body.appendChild(menu);

                menu.classList.add('open');
                positionMenu(menu, btn);
            }
        });
    });

    document.addEventListener('click', closeAllMenus);
    window.addEventListener('resize', closeAllMenus);
    if (tableWrap) tableWrap.addEventListener('scroll', closeAllMenus, { passive: true });
    /* ---------- Favorite star: toggle straight from the Vault list (Actions column) ---------- */
    function toggleFavorite(vaultId) {
        var body = new URLSearchParams();
        body.set('ajax', 'toggle_favorite');
        body.set('csrf_token', csrfToken);
        body.set('vault_id', vaultId);

        return fetch('./vault.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).then(function (response) {
            if (!response.ok) throw new Error('Failed to toggle favorite');
            return response.json();
        });
    }

    function applyFavoriteState(row, isFavorite) {
        row.setAttribute('data-favorite', isFavorite ? '1' : '0');
        $all('.vault-favorite-btn', row).forEach(function (el) {
            el.classList.toggle('is-fav', isFavorite);
            el.setAttribute('aria-pressed', isFavorite ? 'true' : 'false');
            el.setAttribute('aria-label', isFavorite ? 'Remove from favorites' : 'Add to favorites');
            el.setAttribute('data-tooltip', isFavorite ? 'Unfavorite' : 'Favorite');
        });
    }

    $all('.vault-favorite-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = btn.closest('tr');
            if (!row) return;
            var id = row.getAttribute('data-vault-id');
            var wasFavorite = row.getAttribute('data-favorite') === '1';

            applyFavoriteState(row, !wasFavorite); 
            toggleFavorite(id).then(function (json) {
                applyFavoriteState(row, !!json.is_favorite);
            }).catch(function () {
                applyFavoriteState(row, wasFavorite); 
            });
        });
    });

    /* ---------- Grid / List view toggle ---------- */
    var VIEW_KEY = 'squir-vault-view';
    var viewButtons = $all('.view-toggle-btn');

    function setView(view) {
        if (tableWrap) tableWrap.classList.toggle('grid-view', view === 'grid');
        viewButtons.forEach(function (btn) {
            var isActive = btn.getAttribute('data-view') === view;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
        try { window.localStorage.setItem(VIEW_KEY, view); } catch (error) {}
        requestAnimationFrame(updateScrollHint);
    }

    viewButtons.forEach(function (btn) {
        btn.addEventListener('click', function () { setView(btn.getAttribute('data-view')); });
    });

    var savedView = null;
    try { savedView = window.localStorage.getItem(VIEW_KEY); } catch (error) {}
    if (savedView === 'grid') setView('grid');


    var scrollHint = document.getElementById('vaultScrollHint');

    function updateScrollHint() {
        if (!tableWrap || !scrollHint) return;
        var hasOverflow = tableWrap.scrollHeight > tableWrap.clientHeight + 2;
        var atBottom = tableWrap.scrollTop + tableWrap.clientHeight >= tableWrap.scrollHeight - 4;
        scrollHint.classList.toggle('visible', hasOverflow && !atBottom);
    }

    if (tableWrap && scrollHint) {
        tableWrap.addEventListener('scroll', updateScrollHint, { passive: true });
        window.addEventListener('resize', updateScrollHint);
        updateScrollHint();
    }


    (function () {
        var form = document.getElementById('vaultSearchForm');
        var input = document.getElementById('vaultSearchInput');
        var box = document.getElementById('vaultSearchSuggestions');
        if (!form || !input || !box || !tableWrap) return;

        var activeIndex = -1;
        var currentMatches = [];

        function rowTitle(row) {
            var strong = row.querySelector('.vault-item-text strong');
            return strong ? strong.textContent.trim() : '';
        }

        function closeSuggestions() {
            box.classList.remove('open');
            box.innerHTML = '';
            activeIndex = -1;
            currentMatches = [];
        }

        function setActive(index) {
            $all('.vault-search-suggestion', box).forEach(function (el, i) {
                el.classList.toggle('active', i === index);
            });
            activeIndex = index;
        }

        function renderSuggestions(matches) {
            currentMatches = matches;
            box.innerHTML = '';

            if (matches.length === 0) {
                closeSuggestions();
                return;
            }

            matches.forEach(function (match) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'vault-search-suggestion';
                btn.setAttribute('data-vault-id', match.id);
                btn.setAttribute('data-title', match.title);
                btn.appendChild(document.createTextNode(match.title));
                box.appendChild(btn);
            });

            box.classList.add('open');
            setActive(0);
        }

        function highlightRow(row) {
            if (!row) return;
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.classList.remove('vault-item-highlight');
            void row.offsetWidth; // force reflow para paulit-ulit gumana ang transition
            row.classList.add('vault-item-highlight');
            setTimeout(function () { row.classList.remove('vault-item-highlight'); }, 1700);
        }

        function jumpToVaultId(id, title) {
            if (title !== undefined) input.value = title; // i-autocomplete ang search bar
            var row = tableWrap.querySelector('tr[data-vault-id="' + id + '"]');
            highlightRow(row);
            closeSuggestions();
            input.blur();
        }

        input.addEventListener('input', function () {
            var q = input.value.trim().toLowerCase();
            if (q === '') { closeSuggestions(); return; }

            var matches = [];
            $all('tr[data-vault-id]', tableWrap).forEach(function (row) {
                var title = rowTitle(row);
                if (title.toLowerCase().indexOf(q) !== -1) {
                    matches.push({ id: row.getAttribute('data-vault-id'), title: title });
                }
            });
            renderSuggestions(matches.slice(0, 6));
        });

        box.addEventListener('click', function (event) {
            var btn = event.target.closest('.vault-search-suggestion');
            if (btn) jumpToVaultId(btn.getAttribute('data-vault-id'), btn.getAttribute('data-title'));
        });

        input.addEventListener('keydown', function (event) {
            if (!box.classList.contains('open')) return;

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                var count = currentMatches.length;
                var next = activeIndex + (event.key === 'ArrowDown' ? 1 : -1);
                if (next < 0) next = count - 1;
                if (next >= count) next = 0;
                setActive(next);
                return;
            }

            if (event.key === 'Escape') {
                closeSuggestions();
            }
        });


        form.addEventListener('submit', function (event) {
            if (currentMatches.length === 0) return; 
            event.preventDefault();
            var pick = currentMatches[activeIndex] || currentMatches[0];
            jumpToVaultId(pick.id, pick.title);
        });

        document.addEventListener('click', function (event) {
            if (!form.contains(event.target)) closeSuggestions();
        });
    })();
})();