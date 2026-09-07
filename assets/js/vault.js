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

    var openBtn = document.getElementById('openCreateModal');
    if (openBtn) openBtn.addEventListener('click', function () { openModal('create'); });

    $all('[data-close-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var backdrop = document.getElementById(btn.getAttribute('data-close-modal') + 'ModalBackdrop');
            if (backdrop) backdrop.classList.remove('open');
        });
    });

    $all('.vault-modal-backdrop').forEach(function (backdrop) {
        backdrop.addEventListener('click', function (event) {
            if (event.target === backdrop) backdrop.classList.remove('open');
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

    $all('.vault-copy-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = btn.closest('tr');
            var id = row.getAttribute('data-vault-id');
            revealById(id).then(function (json) {
                navigator.clipboard.writeText(json.password);
                var icon = btn.querySelector('i');
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
            revealById(id).then(function (json) {
                navigator.clipboard.writeText(json.password);
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
    /*
     * Dati, `.vault-menu-dropdown` ay `position: absolute` relative sa
     * `.vault-menu` (ang parent nito). Problema: dahil naka-scroll na
     * ngayon ang #vaultTableWrap (overflow-y: auto) at grid cards naman
     * ang magkakatabi sa grid view, na-cli-clip / natatakpan ang dropdown
     * ng ibang laman (kasunod na row/card, o ng scroll boundary mismo).
     *
     * Ayos: sa pag-open, kino-compute natin ang exact position ng button
     * gamit ang getBoundingClientRect(), tapos ilalagay natin ang dropdown
     * bilang `position: fixed` sa eksaktong lugar na iyon (via inline
     * style). Dahil `position: fixed` ay relative sa buong viewport (hindi
     * sa alinmang scrollable na ninuno), hindi na ito ma-c-clip pa ng
     * #vaultTableWrap o matatakpan ng kahit anong card/row.
     *
     * Para hindi "lumutang" sa lugar ang dropdown habang naka-scroll ang
     * user (dahil naka-fix na ito sa screen samantalang gumagalaw naman
     * ang button sa ilalim ng scroll), isinasara na lang natin agad ang
     * bukas na dropdown sa sandaling mag-scroll o mag-resize.
     */
    function closeAllMenus() {
        $all('.vault-menu-dropdown.open').forEach(function (menu) {
            menu.classList.remove('open');
            menu.style.top = '';
            menu.style.left = '';
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
            // kung mauubusan na ng puwang sa ibaba, ilagay na lang sa itaas ng button
            top = rect.top - menuHeight - 6;
        }

        menu.style.left = left + 'px';
        menu.style.top = top + 'px';
    }

    $all('.vault-menu-btn').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.stopPropagation();
            var menu = btn.parentElement.querySelector('.vault-menu-dropdown');
            var willOpen = !menu.classList.contains('open');
            closeAllMenus();
            if (willOpen) {
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
            // Hindi natin binabago yung icon class (ti-star lang palagi) — yung
            // .is-fav class na sa CSS ang bahala sa pagpalit ng kulay papuntang gold.
        });
    }

    $all('.vault-favorite-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = btn.closest('tr');
            if (!row) return;
            var id = row.getAttribute('data-vault-id');
            var wasFavorite = row.getAttribute('data-favorite') === '1';

            applyFavoriteState(row, !wasFavorite); // optimistic update, agad-agad ang feel
            toggleFavorite(id).then(function (json) {
                applyFavoriteState(row, !!json.is_favorite);
            }).catch(function () {
                applyFavoriteState(row, wasFavorite); // ibalik kung nabigo
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
        // ang paglipat ng view ay pwedeng magbago ng laki ng laman
        // (list vs. cards), kaya kailangan i-recheck ang scroll hint
        requestAnimationFrame(updateScrollHint);
    }

    viewButtons.forEach(function (btn) {
        btn.addEventListener('click', function () { setView(btn.getAttribute('data-view')); });
    });

    var savedView = null;
    try { savedView = window.localStorage.getItem(VIEW_KEY); } catch (error) {}
    if (savedView === 'grid') setView('grid');

    /* ---------- Bottom info bar: "Scroll down to see more items" hint ---------- */
    /*
     * Palagi nakikita ang "N vault items" (server-rendered na sa index.php).
     * Yung "Scroll down to see more items" naman ay lalabas lang kapag:
     *   1. May overflow talaga ang #vaultTableWrap (mas mataas ang laman
     *      kaysa sa max-height nito), AT
     *   2. Hindi pa naka-scroll ang user hanggang sa dulo.
     * Nawawala ito kapag na-reach na ang ibaba ng listahan.
     */
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
})();