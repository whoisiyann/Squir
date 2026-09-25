
(function () {
    'use strict';

    var cfg = window.SQUIR_DASH || {};
    var clocks = (cfg.clocks || []).slice();
    var tzLabels = cfg.tzLabels || {};

    // Normalize time zone names
    // Allow the click action to complete
    var TZ_ALIASES = {
        'Asia/Calcutta': 'Asia/Kolkata',
        'Asia/Saigon': 'Asia/Ho_Chi_Minh',
        'Asia/Katmandu': 'Asia/Kathmandu',
        'Asia/Rangoon': 'Asia/Yangon',
        'Europe/Kyiv': 'Europe/Kiev',
        'America/Buenos_Aires': 'America/Argentina/Buenos_Aires'
    };

    function $(id) { return document.getElementById(id); }

    function h(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined && text !== null) node.textContent = text;
        return node;
    }

    function icon(classes) {
        var node = document.createElement('i');
        node.className = 'ti ' + classes;
        node.setAttribute('aria-hidden', 'true');
        return node;
    }

    // Send a dashboard request
    function api(action, data) {
        var body = new URLSearchParams();
        body.set('ajax', action);
        body.set('csrf_token', cfg.csrf || '');
        Object.keys(data || {}).forEach(function (key) { body.set(key, data[key]); });

        return fetch('./dashboard', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            credentials: 'same-origin',
            body: body.toString()
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (json) {
                if (!response.ok) throw new Error(json.error || 'Something went wrong. Please try again.');
                return json;
            });
        });
    }


    var toastEl = null;
    var toastTimer = null;

    // Show a dashboard notification
    function toast(message) {
        if (!toastEl) {
            toastEl = h('div', 'dash-toast');
            toastEl.setAttribute('role', 'status');
            document.body.appendChild(toastEl);
        }
        toastEl.textContent = message;
        void toastEl.offsetWidth;
        toastEl.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toastEl.classList.remove('show'); }, 2800);
    }

    if (cfg.flash) toast(cfg.flash);


    var addMenu = $('addMenu');
    var addButton = $('addMenuBtn');

    // Toggle the quick-add menu
    function setAddMenu(open) {
        if (!addMenu || !addButton) return;
        addMenu.classList.toggle('open', open);
        addButton.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (addMenu && addButton) {
        addButton.addEventListener('click', function (event) {
            event.stopPropagation();
            setAddMenu(!addMenu.classList.contains('open'));
        });

        document.addEventListener('click', function (event) {
            if (!addMenu.contains(event.target)) {
                setAddMenu(false);
            } else if (event.target.closest('.add-menu-item')) {
                // let the click finish first (Vault opens the popup, Note submits the form)
                setTimeout(function () { setAddMenu(false); }, 0);
            }
        });
    }


    var clockList = $('clockList');
    var formatterCache = {};

    function formatters(zone) {
        if (!Object.prototype.hasOwnProperty.call(formatterCache, zone)) {
            try {
                formatterCache[zone] = {
                    date: new Intl.DateTimeFormat('en-US', { weekday: 'short', month: 'short', day: 'numeric', timeZone: zone }),
                    time: new Intl.DateTimeFormat('en-US', { hour: 'numeric', minute: '2-digit', hour12: true, timeZone: zone })
                };
            } catch (error) {
                formatterCache[zone] = null;
            }
        }
        return formatterCache[zone];
    }

    function localZone() {
        try {
            return Intl.DateTimeFormat().resolvedOptions().timeZone || 'Asia/Manila';
        } catch (error) {
            return 'Asia/Manila';
        }
    }

    function localLabel(zone) {
        var known = TZ_ALIASES[zone] || zone;
        if (tzLabels[known]) return tzLabels[known];
        return known.split('/').pop().replace(/_/g, ' ') || 'Local time';
    }

    function buildClockRow(entry) {
        var row = h('li', 'clock-row' + (entry.local ? ' is-local' : ''));
        row.setAttribute('data-tz', entry.zone);

        var name = h('div', 'clock-name');
        var label = h('strong', null, entry.label);
        if (entry.title) label.title = entry.title;
        name.appendChild(label);
        if (entry.local) name.appendChild(h('span', 'clock-chip', 'Local'));

        row.appendChild(name);
        row.appendChild(h('span', 'clock-date'));
        row.appendChild(h('span', 'clock-time'));

        if (entry.local) {
            row.appendChild(h('span', 'clock-remove-spacer'));
        } else {
            var remove = h('button', 'clock-remove');
            remove.type = 'button';
            remove.setAttribute('aria-label', 'Remove ' + entry.label);
            remove.appendChild(icon('ti-x'));
            remove.addEventListener('click', function () { removeClock(entry.cityId); });
            row.appendChild(remove);
        }

        return row;
    }

    function tick() {
        if (!clockList) return;
        var now = new Date();

        Array.prototype.forEach.call(clockList.children, function (row) {
            var f = formatters(row.getAttribute('data-tz'));
            var dateText = f ? f.date.format(now) : '';
            var timeText = f ? f.time.format(now) : '--';
            var dateEl = row.querySelector('.clock-date');
            var timeEl = row.querySelector('.clock-time');
            if (dateEl.textContent !== dateText) dateEl.textContent = dateText;
            if (timeEl.textContent !== timeText) timeEl.textContent = timeText;
        });
    }

    // Render dashboard clocks
    function renderClocks() {
        if (!clockList) return;
        clockList.textContent = '';

        var zone = localZone();
        clockList.appendChild(buildClockRow({ local: true, zone: zone, label: localLabel(zone) }));

        clocks.forEach(function (clock) {
            clockList.appendChild(buildClockRow({
                zone: clock.timezone,
                label: clock.city_name,
                title: clock.city_name + ', ' + clock.country_name + ' \u2022 ' + clock.timezone,
                cityId: clock.city_id
            }));
        });

        tick();
    }

    // Remove a dashboard clock
    function removeClock(cityId) {
        api('world_clock_remove', { city_id: cityId }).then(function (result) {
            clocks = result.clocks || [];
            renderClocks();
        }).catch(function (error) { toast(error.message); });
    }

    renderClocks();
    setInterval(tick, 1000);


    var modal = {
        backdrop: $('clockModalBackdrop'),
        search: $('clockSearchInput'),
        list: $('clockCatalog'),
        error: $('clockModalError')
    };
    var catalog = null;

    // Load available clock cities
    function loadCatalog() {
        if (catalog) return Promise.resolve(catalog);

        return fetch('./dashboard?ajax=world_clock_catalog', {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        }).then(function (response) {
            if (!response.ok) throw new Error('Could not load cities.');
            return response.json();
        }).then(function (json) {
            catalog = json.cities || [];
            return catalog;
        });
    }

    function buildCatalogRow(city, isAdded) {
        var row = h('li', 'catalog-row');
        row.appendChild(h('span', 'catalog-code', city.country_code));

        var text = h('div', 'catalog-text');
        text.appendChild(h('strong', null, city.city_name));
        text.appendChild(h('small', null, city.country_name + ' \u2022 ' + city.timezone));
        row.appendChild(text);

        var button = h('button', 'catalog-add', isAdded ? 'Added' : '+ Add');
        button.type = 'button';
        button.disabled = isAdded;
        button.addEventListener('click', function () {
            modal.error.textContent = '';
            button.disabled = true;

            api('world_clock_add', { city_id: city.city_id }).then(function (result) {
                clocks = result.clocks || clocks;
                renderClocks();
                button.textContent = 'Added';
                toast(city.city_name + ' added to your world clocks');
            }).catch(function (error) {
                button.disabled = false;
                modal.error.textContent = error.message;
            });
        });
        row.appendChild(button);

        return row;
    }

    // Render the clock city catalog
    function renderCatalog() {
        var term = modal.search.value.trim().toLowerCase();
        var added = {};
        clocks.forEach(function (clock) { added[clock.city_id] = true; });

        modal.list.textContent = '';
        var shown = 0;

        (catalog || []).forEach(function (city) {
            var haystack = (city.city_name + ' ' + city.country_name + ' ' + city.timezone).toLowerCase();
            if (term !== '' && haystack.indexOf(term) === -1) return;
            shown++;
            modal.list.appendChild(buildCatalogRow(city, !!added[city.city_id]));
        });

        if (shown === 0) {
            modal.list.appendChild(h('li', 'catalog-empty', 'No city matches that search.'));
        }
    }

    // Open the clock picker
    function openClockModal() {
        modal.error.textContent = '';
        modal.search.value = '';
        modal.list.textContent = '';
        modal.list.appendChild(h('li', 'catalog-empty', 'Loading cities...'));
        modal.backdrop.classList.add('open');
        modal.backdrop.setAttribute('aria-hidden', 'false');
        modal.search.focus();

        loadCatalog().then(renderCatalog).catch(function (error) {
            modal.list.textContent = '';
            modal.error.textContent = error.message;
        });
    }

    function closeClockModal() {
        modal.backdrop.classList.remove('open');
        modal.backdrop.setAttribute('aria-hidden', 'true');
    }

    var openClockButton = $('openClockModal');
    if (openClockButton && modal.backdrop) {
        openClockButton.addEventListener('click', openClockModal);
        modal.search.addEventListener('input', renderCatalog);
        modal.backdrop.addEventListener('click', function (event) {
            if (event.target === modal.backdrop) closeClockModal();
        });
        Array.prototype.forEach.call(modal.backdrop.querySelectorAll('[data-clock-close]'), function (button) {
            button.addEventListener('click', closeClockModal);
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (modal.backdrop && modal.backdrop.classList.contains('open')) {
            closeClockModal();
            return;
        }
        setAddMenu(false);
    });


    function applyStar(button, isFavorite) {
        button.classList.toggle('is-favorite', isFavorite);
        button.setAttribute('aria-pressed', isFavorite ? 'true' : 'false');
        button.setAttribute('aria-label', isFavorite ? 'Remove from favorites' : 'Add to favorites');
        var star = button.querySelector('i');
        if (star) star.className = isFavorite ? 'fa-solid fa-star' : 'ti ti-star';
    }

    Array.prototype.forEach.call(document.querySelectorAll('.recent-star-btn'), function (button) {
        button.addEventListener('click', function () {
            var wasFavorite = button.classList.contains('is-favorite');
            applyStar(button, !wasFavorite);

            var body = new URLSearchParams();
            body.set('ajax', 'toggle_favorite');
            body.set('csrf_token', cfg.csrf || '');
            body.set('vault_id', button.getAttribute('data-vault-id'));

            // Use the Vault favorite endpoint
            fetch('./vault', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'same-origin',
                body: body.toString()
            }).then(function (response) {
                if (!response.ok) throw new Error('Failed to toggle favorite');
                return response.json();
            }).then(function (json) {
                applyStar(button, !!json.is_favorite);
            }).catch(function () {
                applyStar(button, wasFavorite);
                toast('Could not update the favorite. Please try again.');
            });
        });
    });


    var recentList = document.querySelector('.recent-list');
    var seeMore = document.querySelector('.recent-see-more');

    if (recentList && seeMore) {
        var checkScrollEnd = function () {
            var reachedEnd = recentList.scrollTop + recentList.clientHeight >= recentList.scrollHeight - 4;
            seeMore.classList.toggle('is-visible', reachedEnd);
        };
        recentList.addEventListener('scroll', checkScrollEnd);
        checkScrollEnd();
    }
})();
