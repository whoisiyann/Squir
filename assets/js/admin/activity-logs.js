(function () {
    var root = document.getElementById('alRoot');
    if (!root) return;

    function $(selector) { return document.querySelector(selector); }

    var endpoint = root.getAttribute('data-endpoint');
    var els = {
        action: $('#alAction'),
        menu: $('#alMenu'),
        menuBtn: $('#alMenuBtn'),
        menuLabel: $('#alMenuLabel'),
        menuPop: $('#alMenuPop'),
        dateBtn: $('#alDateBtn'),
        dateLabel: $('#alDateLabel'),
        datePop: $('#alDatePop'),
        dateFrom: $('#alDateFrom'),
        dateTo: $('#alDateTo'),
        dateError: $('#alDateError'),
        dateApply: $('#alDateApply'),
        dateClear: $('#alDateClear'),
        search: $('#alSearch'),
        refresh: $('#alRefresh'),
        card: $('#alCard'),
        scroll: $('#alScroll'),
        body: $('#alBody'),
        empty: $('#alEmpty'),
        emptyText: $('#alEmptyText'),
        emptyClear: $('#alEmptyClear'),
        error: $('#alError'),
        errorText: $('#alErrorText'),
        retry: $('#alRetry'),
        loading: $('#alLoading'),
        count: $('#alCount'),
        updated: $('#alUpdated'),
        exportLink: $('#alExport'),
        exportLinkTop: $('#alExportTop'),
        userChip: $('#alUserChip'),
        userChipName: $('#alUserChipName'),
        userChipClear: $('#alUserChipClear'),
        clearBtns: document.querySelectorAll('[data-al-clear]')
    };

    var initial = {};
    try { initial = JSON.parse(root.getAttribute('data-filters') || '{}'); } catch (e) { initial = {}; }

    var state = {
        action: initial.action || 'all',
        date_from: initial.date_from || '',
        date_to: initial.date_to || '',
        q: initial.q || '',
        user_id: initial.user_id || '',
        page: 1,
        total: parseInt((els.count.textContent.match(/of (\d+)/) || [0, 0])[1], 10) || 0,
        hasMore: root.getAttribute('data-has-more') === '1',
        loading: false
    };

    var requestId = 0;
    var controller = null;
    var searchTimer = null;

    /* ---------- helpers ---------- */
    var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function toISO(date) { return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()); }
    function prettyDate(iso) {
        var p = iso.split('-');
        return MONTHS[parseInt(p[1], 10) - 1] + ' ' + parseInt(p[2], 10) + ', ' + p[0];
    }
    function daysAgo(n) {
        var d = new Date();
        d.setDate(d.getDate() - n);
        return d;
    }

    function filterParams() {
        var params = new URLSearchParams();
        if (state.action && state.action !== 'all') params.set('action', state.action);
        if (state.date_from) params.set('date_from', state.date_from);
        if (state.date_to) params.set('date_to', state.date_to);
        if (state.q) params.set('q', state.q);
        if (state.user_id) params.set('user_id', state.user_id);
        return params;
    }

    function hasFilters() {
        return (state.action && state.action !== 'all') || state.date_from || state.date_to || state.q || state.user_id;
    }

    function rowCount() { return els.body.querySelectorAll('tr[data-log-row]').length; }

    /* ---------- UI sync ---------- */
    function syncControls() {
        syncMenu();

        var label = 'Select date range';
        if (state.date_from && state.date_to) {
            label = state.date_from === state.date_to
                ? prettyDate(state.date_from)
                : prettyDate(state.date_from) + ' – ' + prettyDate(state.date_to);
        } else if (state.date_from) {
            label = 'From ' + prettyDate(state.date_from);
        } else if (state.date_to) {
            label = 'Until ' + prettyDate(state.date_to);
        }
        els.dateLabel.textContent = label;
        els.dateBtn.classList.toggle('is-filtered', !!(state.date_from || state.date_to));

        els.userChip.hidden = !state.user_id;

        var exportParams = filterParams();
        exportParams.set('export', 'csv');
        var exportHref = endpoint + '?' + exportParams.toString();
        els.exportLink.setAttribute('href', exportHref);
        if (els.exportLinkTop) els.exportLinkTop.setAttribute('href', exportHref);

        var urlParams = filterParams();
        var currentRoute = new URLSearchParams(window.location.search).get('route');
        if (currentRoute) urlParams.set('route', currentRoute);
        var qs = urlParams.toString();
        if (window.history && history.replaceState) {
            history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
        }
    }

    function updateFooter() {
        var shown = rowCount();
        els.count.textContent = 'Showing ' + shown + ' of ' + state.total + ' log' + (state.total === 1 ? '' : 's');
    }

    function showUpdated() {
        var now = new Date();
        var h = now.getHours() % 12 || 12;
        els.updated.textContent = 'Updated ' + h + ':' + pad(now.getMinutes()) + (now.getHours() >= 12 ? ' PM' : ' AM');
    }

    function showEmptyIfNeeded() {
        var empty = rowCount() === 0 && els.error.hidden;
        els.empty.hidden = !empty;
        if (empty) {
            els.emptyText.textContent = hasFilters() ? 'No activity matches your filters.' : 'No activity has been recorded yet.';
            els.emptyClear.hidden = !hasFilters();
        }
    }

    function setLoading(on, append) {
        state.loading = on;
        els.card.setAttribute('aria-busy', on ? 'true' : 'false');
        els.loading.hidden = !(on && append);
    }

    /* ---------- loading rows ---------- */
    function load(options) {
        options = options || {};
        var append = !!options.append;

        if (append && (state.loading || !state.hasMore)) return Promise.resolve();

        if (controller) controller.abort();
        controller = window.AbortController ? new AbortController() : null;
        var myRequest = ++requestId;

        var page = append ? state.page + 1 : 1;
        var params = filterParams();
        params.set('ajax', 'rows');
        params.set('page', page);

        els.error.hidden = true;
        setLoading(true, append);

        return fetch(endpoint + '?' + params.toString(), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
            signal: controller ? controller.signal : undefined
        }).then(function (response) {
            return response.json().then(function (json) {
                return { ok: response.ok, json: json };
            });
        }).then(function (result) {
            if (myRequest !== requestId) return; // a newer request replaced this one
            if (!result.ok) throw new Error(result.json && result.json.error ? result.json.error : 'Request failed');

            var json = result.json;
            if (append) {
                appendRows(json.html);
            } else {
                els.body.innerHTML = json.html;
                els.scroll.scrollTop = 0;
            }

            state.page = json.page;
            state.total = json.total;
            state.hasMore = !!json.has_more;

            setLoading(false, append);
            updateFooter();
            showUpdated();
            showEmptyIfNeeded();
            fillIfShort();
        }).catch(function (error) {
            if (error && error.name === 'AbortError') return;
            if (myRequest !== requestId) return;

            setLoading(false, append);
            if (append) {
                state.hasMore = true;
                return;
            }
            els.body.innerHTML = '';
            els.empty.hidden = true;
            els.errorText.textContent = (error && error.message && error.message !== 'Request failed')
                ? error.message
                : 'Could not reach the server. Please try again.';
            els.error.hidden = false;
            updateFooter();
        });
    }

    function appendRows(html) {
        var template = document.createElement('template');
        template.innerHTML = html;
        var known = {};
        Array.prototype.forEach.call(els.body.querySelectorAll('tr[data-log-id]'), function (row) {
            known[row.getAttribute('data-log-id')] = true;
        });
        Array.prototype.forEach.call(template.content.querySelectorAll('tr[data-log-id]'), function (row) {
            if (!known[row.getAttribute('data-log-id')]) els.body.appendChild(row);
        });
    }

    function scrollsInside() {
        return window.getComputedStyle(els.scroll).overflowY !== 'visible';
    }
    function fillIfShort() {
        if (!state.hasMore || state.loading) return;
        var short = scrollsInside()
            ? els.scroll.scrollHeight <= els.scroll.clientHeight + 40
            : els.card.getBoundingClientRect().bottom < window.innerHeight + 40;
        if (short) load({ append: true });
    }

    function reload() {
        syncControls();
        return load();
    }

    /* ---------- All Actions (cascading menu) ---------- */
    var MOBILE = '(max-width: 700px)';
    function isMobile() { return window.matchMedia(MOBILE).matches; }
    function list(nodes) { return Array.prototype.slice.call(nodes); }

    function syncMenu() {
        els.action.value = state.action;
        var text = 'All Actions';
        list(els.menuPop.querySelectorAll('[data-value]')).forEach(function (item) {
            var on = item.getAttribute('data-value') === state.action;
            item.classList.toggle('is-selected', on);
            if (on) text = item.getAttribute('data-short') || item.textContent.trim();
        });
        list(els.menuPop.querySelectorAll('.al-menu-group')).forEach(function (group) {
            group.classList.toggle('has-selected', !!group.querySelector('.is-selected'));
        });
        els.menuLabel.textContent = text;
        els.menuBtn.classList.toggle('is-filtered', state.action !== 'all');
    }

    function closeSubs(except) {
        list(els.menuPop.querySelectorAll('.al-menu-group')).forEach(function (group) {
            if (group === except) return;
            group.classList.remove('is-open');
            var trigger = group.querySelector('.has-sub');
            if (trigger) trigger.setAttribute('aria-expanded', 'false');
        });
    }

    function placeSub(group) {
        var sub = group.querySelector('.al-submenu');
        if (!sub || isMobile()) return;
        sub.classList.remove('flip-left');
        sub.style.top = '';
        var rect = sub.getBoundingClientRect();
        if (rect.right > window.innerWidth - 8) sub.classList.add('flip-left');
        rect = sub.getBoundingClientRect();
        if (rect.bottom > window.innerHeight - 8) {
            sub.style.top = (-7 - (rect.bottom - window.innerHeight + 8)) + 'px';
        }
    }

    function openSub(group, focusFirst) {
        closeSubs(group);
        group.classList.add('is-open');
        group.querySelector('.has-sub').setAttribute('aria-expanded', 'true');
        placeSub(group);
        if (focusFirst) {
            var first = group.querySelector('.al-submenu .al-menu-item');
            if (first) first.focus();
        }
    }

    function openMenu() {
        closeDate();
        els.menuPop.hidden = false;
        els.menuBtn.setAttribute('aria-expanded', 'true');
        var current = els.menuPop.querySelector('.al-menu-group.has-selected');
        if (current && isMobile()) openSub(current, false);
    }
    function closeMenu() {
        if (els.menuPop.hidden) return;
        els.menuPop.hidden = true;
        els.menuBtn.setAttribute('aria-expanded', 'false');
        closeSubs(null);
    }

    function topItems() {
        return list(els.menuPop.querySelectorAll(':scope > .al-menu-item, :scope > .al-menu-group > .al-menu-item'));
    }

    els.menuBtn.addEventListener('click', function (event) {
        event.stopPropagation();
        if (els.menuPop.hidden) openMenu(); else closeMenu();
    });
    els.menuBtn.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (els.menuPop.hidden) openMenu();
            var items = topItems();
            if (items.length) items[event.key === 'ArrowDown' ? 0 : items.length - 1].focus();
        }
    });

    els.menuPop.addEventListener('click', function (event) {
        event.stopPropagation();
        var item = event.target.closest('.al-menu-item');
        if (!item) return;

        if (item.classList.contains('has-sub')) {
            var group = item.parentNode;
            if (group.classList.contains('is-open') && isMobile()) {
                closeSubs(null);
            } else {
                openSub(group, false);
            }
            return;
        }

        var value = item.getAttribute('data-value');
        if (value === null) return;
        closeMenu();
        if (value !== state.action) {
            state.action = value;
            reload();
        } else {
            syncMenu();
        }
    });

    // Fly-outs on hover (desktop)
    list(els.menuPop.querySelectorAll('.al-menu-group')).forEach(function (group) {
        group.addEventListener('mouseenter', function () {
            if (isMobile() || !window.matchMedia('(hover: hover)').matches) return;
            closeSubs(group);
            placeSub(group);
        });
    });
    els.menuPop.addEventListener('mouseleave', function () {
        if (!isMobile()) closeSubs(null);
    });

    els.menuPop.addEventListener('keydown', function (event) {
        var active = document.activeElement;
        var sub = active && active.closest('.al-submenu');
        var items = sub ? list(sub.querySelectorAll('.al-menu-item')) : topItems();
        var index = items.indexOf(active);

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            var next = event.key === 'ArrowDown' ? index + 1 : index - 1;
            if (next < 0) next = items.length - 1;
            if (next >= items.length) next = 0;
            items[next].focus();
        } else if (event.key === 'ArrowRight' && active.classList.contains('has-sub')) {
            event.preventDefault();
            openSub(active.parentNode, true);
        } else if (event.key === 'ArrowLeft' && sub) {
            event.preventDefault();
            var parent = sub.parentNode;
            parent.classList.remove('is-open');
            parent.querySelector('.has-sub').setAttribute('aria-expanded', 'false');
            parent.querySelector('.has-sub').focus();
        }
    });

    document.addEventListener('click', closeMenu);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !els.menuPop.hidden) {
            closeMenu();
            els.menuBtn.focus();
        }
    });

    /* ---------- Search user ---------- */
    els.search.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            var value = els.search.value.trim();
            if (value === state.q) return;
            state.q = value;
            reload();
        }, 300);
    });
    els.search.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter') return;
        clearTimeout(searchTimer);
        var value = els.search.value.trim();
        if (value === state.q) return;
        state.q = value;
        reload();
    });

    /* ---------- Select date range ---------- */
    function openDate() {
        els.dateFrom.value = state.date_from;
        els.dateTo.value = state.date_to;
        els.dateError.hidden = true;
        markPreset();
        closeMenu();
        els.datePop.hidden = false;
        els.dateBtn.setAttribute('aria-expanded', 'true');
    }
    function closeDate() {
        els.datePop.hidden = true;
        els.dateBtn.setAttribute('aria-expanded', 'false');
    }
    function presetRange(name) {
        var today = new Date();
        if (name === 'today') return [toISO(today), toISO(today)];
        if (name === 'yesterday') return [toISO(daysAgo(1)), toISO(daysAgo(1))];
        if (name === '7') return [toISO(daysAgo(6)), toISO(today)];
        if (name === '30') return [toISO(daysAgo(29)), toISO(today)];
        if (name === 'month') return [toISO(new Date(today.getFullYear(), today.getMonth(), 1)), toISO(today)];
        return ['', ''];
    }
    function markPreset() {
        Array.prototype.forEach.call(els.datePop.querySelectorAll('[data-preset]'), function (btn) {
            var range = presetRange(btn.getAttribute('data-preset'));
            btn.classList.toggle('is-active', range[0] === els.dateFrom.value && range[1] === els.dateTo.value && range[0] !== '');
        });
    }
    function applyDate(from, to) {
        if (from && to && from > to) {
            els.dateError.textContent = 'The start date must be before the end date.';
            els.dateError.hidden = false;
            return;
        }
        state.date_from = from;
        state.date_to = to;
        closeDate();
        reload();
    }

    els.dateBtn.addEventListener('click', function (event) {
        event.stopPropagation();
        if (els.datePop.hidden) openDate(); else closeDate();
    });
    els.datePop.addEventListener('click', function (event) { event.stopPropagation(); });
    document.addEventListener('click', closeDate);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !els.datePop.hidden) {
            closeDate();
            els.dateBtn.focus();
        }
    });

    Array.prototype.forEach.call(els.datePop.querySelectorAll('[data-preset]'), function (btn) {
        btn.addEventListener('click', function () {
            var range = presetRange(btn.getAttribute('data-preset'));
            applyDate(range[0], range[1]);
        });
    });
    els.dateFrom.addEventListener('change', function () { els.dateError.hidden = true; markPreset(); });
    els.dateTo.addEventListener('change', function () { els.dateError.hidden = true; markPreset(); });
    els.dateApply.addEventListener('click', function () { applyDate(els.dateFrom.value, els.dateTo.value); });
    els.dateClear.addEventListener('click', function () { applyDate('', ''); });

    /* ---------- Refresh ---------- */
    els.refresh.addEventListener('click', function () {
        clearTimeout(searchTimer);
        state.q = els.search.value.trim();
        els.refresh.disabled = true;
        els.refresh.classList.add('is-spinning');
        var started = Date.now();

        syncControls();
        load().then(function () {
            var wait = Math.max(0, 500 - (Date.now() - started));
            setTimeout(function () {
                els.refresh.disabled = false;
                els.refresh.classList.remove('is-spinning');
            }, wait);
        });
    });

    /* ---------- Clear activity logs ---------- */
    var clearRanges = {};
    try { clearRanges = JSON.parse(root.getAttribute('data-clear-ranges') || '{}'); } catch (e) { clearRanges = {}; }
    var csrfToken = window.ADMIN_CSRF_TOKEN || '';

    function setClearBusy(on) {
        Array.prototype.forEach.call(els.clearBtns, function (btn) { btn.disabled = on; });
    }

    // Step 1: pick which date range to clear.
    function chooseClearRange() {
        return Swal.fire({
            title: 'Clear activity logs',
            text: 'Choose which logs to delete.',
            input: 'radio',
            inputOptions: clearRanges,
            inputValue: 'today',
            inputValidator: function (value) { return value ? undefined : 'Please choose a date range.'; },
            showCancelButton: true,
            confirmButtonText: 'Continue',
            cancelButtonText: 'Cancel',
            confirmButtonColor: (window.SquirAdminTheme && window.SquirAdminTheme.swalColor) ? window.SquirAdminTheme.swalColor() : '#6b3f2a',
            reverseButtons: true,
            customClass: { popup: 'al-swal' }
        }).then(function (result) { return result.isConfirmed ? result.value : null; });
    }

    function postClear(range) {
        var body = new URLSearchParams();
        body.set('ajax', 'clear_logs');
        body.set('csrf_token', csrfToken);
        body.set('range', range);

        return fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: body.toString()
        }).then(function (response) {
            return response.json().then(function (json) {
                return { ok: response.ok, json: json };
            });
        });
    }

    function startClear() {
        if (!window.Swal || !window.AdminAlert) {
            window.alert('The dialog could not load. Please refresh the page and try again.');
            return;
        }

        chooseClearRange().then(function (range) {
            if (!range) return;
            var label = clearRanges[range] || range;

            return AdminAlert.confirm({
                title: 'Delete these logs?',
                text: 'This will permanently delete the user activity logs for: ' + label + '. This cannot be undone.',
                confirmText: 'Yes, delete',
                danger: true
            }).then(function (confirmed) {
                if (!confirmed) return;

                setClearBusy(true);
                return postClear(range).then(function (result) {
                    if (!result.ok) {
                        throw new Error(result.json && result.json.error ? result.json.error : 'Could not clear the activity logs.');
                    }

                    var deleted = result.json.deleted || 0;
                    reload();
                    if (deleted === 0) {
                        return Swal.fire({
                            icon: 'info',
                            title: 'Nothing to clear',
                            text: 'There are no logs for: ' + label + '.',
                            confirmButtonColor: (window.SquirAdminTheme && window.SquirAdminTheme.swalColor) ? window.SquirAdminTheme.swalColor() : '#6b3f2a'
                        });
                    }
                    return AdminAlert.success('Logs cleared', deleted + ' log' + (deleted === 1 ? '' : 's') + ' deleted.');
                });
            });
        }).catch(function (error) {
            AdminAlert.error(error && error.message ? error.message : 'Could not reach the server. Please try again.');
        }).then(function () {
            setClearBusy(false);
        });
    }

    Array.prototype.forEach.call(els.clearBtns, function (btn) {
        btn.addEventListener('click', startClear);
    });

    /* ---------- Clear filters and retry ---------- */
    function clearAll() {
        state.action = 'all';
        state.date_from = '';
        state.date_to = '';
        state.q = '';
        state.user_id = '';
        els.search.value = '';
        reload();
    }
    els.emptyClear.addEventListener('click', clearAll);
    els.retry.addEventListener('click', function () { reload(); });
    els.userChipClear.addEventListener('click', function () {
        state.user_id = '';
        reload();
    });

    /* ---------- Open user details ---------- */
    function openRow(row, newTab) {
        var href = row && row.getAttribute('data-href');
        if (!href) return;
        if (newTab) window.open(href, '_blank', 'noopener');
        else window.location.href = href;
    }
    els.body.addEventListener('click', function (event) {
        var row = event.target.closest('tr[data-href]');
        if (!row || !els.body.contains(row)) return;
        if (event.target.closest('a, button')) return;
        var selection = window.getSelection && window.getSelection().toString();
        if (selection) return;
        openRow(row, event.ctrlKey || event.metaKey);
    });
    els.body.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        var row = event.target.closest('tr[data-href]');
        if (!row || event.target !== row) return;
        event.preventDefault();
        openRow(row, event.ctrlKey || event.metaKey);
    });

    /* ---------- Infinite scroll ---------- */
    els.scroll.addEventListener('scroll', function () {
        if (!scrollsInside()) return;
        var nearBottom = els.scroll.scrollHeight - els.scroll.scrollTop - els.scroll.clientHeight < 120;
        if (nearBottom) load({ append: true });
    });
    root.addEventListener('scroll', function () {
        if (scrollsInside()) return;
        if (els.card.getBoundingClientRect().bottom - window.innerHeight < 240) load({ append: true });
    }, { passive: true });
    window.addEventListener('resize', fillIfShort);

    /* ---------- start ---------- */
    syncControls();
    updateFooter();
    showUpdated();
    showEmptyIfNeeded();
    fillIfShort();
})();