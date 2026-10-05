(function () {
    'use strict';

    var cfg = window.SQUIR_CALENDAR || { csrf: '', tasks: [] };
    var tasks = (cfg.tasks || []).slice();
    var mainEl = document.getElementById('calMain');
    var sideEl = document.getElementById('calSide');
    if (!mainEl || !sideEl) return;

    var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    var MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var WEEKDAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    var DOW = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];
    var VIEWS = ['week', 'month', 'year'];

    var STATUSES = {
        todo: 'To Do',
        in_progress: 'In Progress',
        done: 'Done'
    };
    var PRIORITIES = {
        high: { label: 'High', icon: 'ti-arrow-up', rank: 0 },
        medium: { label: 'Medium', icon: 'ti-minus', rank: 1 },
        low: { label: 'Low', icon: 'ti-arrow-down', rank: 2 }
    };

    var state = {
        view: 'month',
        cursor: today(),
        term: ''
    };


    /* Small helpers */

    function $(id) { return document.getElementById(id); }

    function h(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined && text !== null) node.textContent = text;
        return node;
    }

    function icon(classes) {
        var node = document.createElement('i');
        node.className = classes.indexOf('fa-') === 0 ? classes : 'ti ' + classes;
        node.setAttribute('aria-hidden', 'true');
        return node;
    }

    function plural(count, one, many) {
        return count + ' ' + (count === 1 ? one : many);
    }


    /* Dates (everything is local time, tasks use YYYY-MM-DD strings) */

    function pad(n) { return n < 10 ? '0' + n : String(n); }

    function today() {
        var d = new Date();
        return new Date(d.getFullYear(), d.getMonth(), d.getDate());
    }

    function ymdOf(date) {
        return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
    }

    function todayYmd() { return ymdOf(today()); }

    function parseYmd(ymd) {
        var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(ymd || '');
        if (!m) return null;

        var date = new Date(+m[1], +m[2] - 1, +m[3]);
        return ymdOf(date) === ymd ? date : null;
    }

    function addDays(date, n) {
        return new Date(date.getFullYear(), date.getMonth(), date.getDate() + n);
    }

    function daysInMonth(year, month) {
        return new Date(year, month + 1, 0).getDate();
    }

    // Move by whole months and keep the day when the target month has it
    function addMonths(date, n) {
        var first = new Date(date.getFullYear(), date.getMonth() + n, 1);
        var day = Math.min(date.getDate(), daysInMonth(first.getFullYear(), first.getMonth()));
        return new Date(first.getFullYear(), first.getMonth(), day);
    }

    function startOfWeek(date) {
        return addDays(date, -date.getDay());
    }

    function fmtLong(ymd) {
        var p = ymd.split('-');
        return MONTHS[+p[1] - 1] + ' ' + (+p[2]) + ', ' + p[0];
    }

    function fmtShort(ymd) {
        var p = ymd.split('-');
        var text = MONTHS_SHORT[+p[1] - 1] + ' ' + (+p[2]);
        return +p[0] === new Date().getFullYear() ? text : text + ', ' + p[0];
    }

    function fmtDayTitle(ymd) {
        var date = parseYmd(ymd);
        return WEEKDAYS[date.getDay()] + ', ' + fmtLong(ymd);
    }

    function fmtCreated(stamp) {
        var m = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/.exec(stamp || '');
        if (!m) return '';
        var hour = +m[4];
        var suffix = hour >= 12 ? 'PM' : 'AM';
        var hour12 = hour % 12 || 12;
        return fmtLong(m[1] + '-' + m[2] + '-' + m[3]) + ' \u2022 ' + hour12 + ':' + m[5] + ' ' + suffix;
    }


    /* Periods */

    // First and last day shown by the current view
    function rangeFor(view, cursor) {
        var start;
        var end;

        if (view === 'week') {
            start = startOfWeek(cursor);
            end = addDays(start, 6);
        } else if (view === 'year') {
            start = new Date(cursor.getFullYear(), 0, 1);
            end = new Date(cursor.getFullYear(), 11, 31);
        } else {
            start = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
            end = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 0);
        }

        return { startDate: start, endDate: end, start: ymdOf(start), end: ymdOf(end) };
    }

    function periodLabel(view, range) {
        if (view === 'year') return String(range.startDate.getFullYear());
        if (view === 'month') return MONTHS[range.startDate.getMonth()] + ' ' + range.startDate.getFullYear();

        var s = range.startDate;
        var e = range.endDate;
        var left = MONTHS_SHORT[s.getMonth()] + ' ' + s.getDate() + (s.getFullYear() === e.getFullYear() ? '' : ', ' + s.getFullYear());
        var right = MONTHS_SHORT[e.getMonth()] + ' ' + e.getDate() + ', ' + e.getFullYear();
        return left + ' - ' + right;
    }

    function containsToday(range) {
        var t = todayYmd();
        return t >= range.start && t <= range.end;
    }

    function inRange(task, range) {
        return task.due_date >= range.start && task.due_date <= range.end;
    }


    /* Task data */

    function findTask(id) {
        for (var i = 0; i < tasks.length; i++) {
            if (tasks[i].task_id === id) return tasks[i];
        }
        return null;
    }

    function matchesSearch(task) {
        if (state.term === '') return true;
        return (task.title + ' ' + task.description).toLowerCase().indexOf(state.term) !== -1;
    }

    // Tasks shown on the calendar: they need a due date and must match the search
    function visibleTasks() {
        return tasks.filter(function (task) {
            return !!task.due_date && matchesSearch(task);
        });
    }

    function isOpen(task) { return task.status !== 'done'; }

    function isOverdue(task) {
        return isOpen(task) && task.due_date < todayYmd();
    }

    function rank(task) {
        return PRIORITIES[task.priority] ? PRIORITIES[task.priority].rank : 1;
    }

    // Open tasks first, then by priority and board position
    function compareInDay(a, b) {
        var byDone = (isOpen(a) ? 0 : 1) - (isOpen(b) ? 0 : 1);
        if (byDone !== 0) return byDone;
        return (rank(a) - rank(b)) || (a.position - b.position) || (a.task_id - b.task_id);
    }

    function compareByDue(a, b) {
        if (a.due_date !== b.due_date) return a.due_date < b.due_date ? -1 : 1;
        return compareInDay(a, b);
    }

    function groupByDate(list) {
        var map = {};
        list.forEach(function (task) {
            (map[task.due_date] = map[task.due_date] || []).push(task);
        });
        Object.keys(map).forEach(function (key) { map[key].sort(compareInDay); });
        return map;
    }

    function countIn(list, range) {
        var count = { open: 0, done: 0, total: 0 };
        list.forEach(function (task) {
            if (!inRange(task, range)) return;
            count.total++;
            if (isOpen(task)) count.open++;
            else count.done++;
        });
        return count;
    }


    /* Server */

    function api(action, data) {
        var body = new URLSearchParams();
        body.set('ajax', action);
        body.set('csrf_token', cfg.csrf);
        Object.keys(data || {}).forEach(function (key) { body.set(key, data[key]); });

        return fetch('./tasks', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            credentials: 'same-origin',
            body: body.toString()
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (json) {
                if (!response.ok) {
                    var error = new Error(json.error || 'Something went wrong. Please try again.');
                    error.fields = json.errors || {};
                    throw error;
                }
                return json;
            });
        });
    }

    function applyServer(result) {
        if (result && Array.isArray(result.tasks)) tasks = result.tasks;

        if (viewingId !== null) {
            var open = findTask(viewingId);
            if (open) fillView(open);
            else closeView();
        }
    }


    var toastEl = null;
    var toastTimer = null;

    function toast(message) {
        if (!toastEl) {
            toastEl = h('div', 'task-toast');
            toastEl.setAttribute('role', 'status');
            document.body.appendChild(toastEl);
        }
        toastEl.textContent = message;

        void toastEl.offsetWidth;
        toastEl.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toastEl.classList.remove('show'); }, 2600);
    }

    function confirmAction(message, action) {
        if (window.SquirDialogs && window.SquirDialogs.confirmDelete) {
            window.SquirDialogs.confirmDelete({ message: message, onConfirm: action });
            return;
        }
        if (window.confirm(message)) {
            action().catch(function (error) { toast(error.message); });
        }
    }


    /* Building blocks */

    function toneOf(task) {
        if (task.status === 'done') return 'done';
        if (task.status === 'in_progress') return 'progress';
        return 'todo';
    }

    function subLabel(task) {
        if (!isOpen(task)) return 'Complete';
        return PRIORITIES[task.priority] ? PRIORITIES[task.priority].label : '';
    }

    // Task chip
    function buildChip(task) {
        var chip = h('button', 'cal-chip tone-' + toneOf(task) + (isOverdue(task) ? ' is-overdue' : ''));
        chip.type = 'button';
        chip.setAttribute('data-id', String(task.task_id));
        chip.title = task.title + ' \u2022 ' + subLabel(task) + (isOverdue(task) ? ' \u2022 Overdue' : '');

        var text = h('span', 'cal-chip-text');
        var title = h('span', 'cal-chip-title', task.title);
        var sub = h('span', 'cal-chip-sub', subLabel(task));
        text.appendChild(title);
        text.appendChild(sub);

        chip.appendChild(h('span', 'cal-chip-dot'));
        chip.appendChild(text);

        chip.addEventListener('click', function (event) {
            event.stopPropagation();
            openView(task);
        });

        return chip;
    }

    function dayHandlers(node, ymd) {
        node.setAttribute('data-date', ymd);
        node.setAttribute('role', 'group');
        node.tabIndex = 0;
        node.addEventListener('click', function () { openDay(ymd); });
        node.addEventListener('keydown', function (event) {
            if (event.target !== node) return;
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openDay(ymd);
            }
        });
    }


    /* Month view */

    function buildMonth(byDate) {
        var wrap = h('div', 'cal-month');

        var head = h('div', 'cal-dow-row');
        DOW.forEach(function (name) { head.appendChild(h('div', 'cal-dow', name)); });
        wrap.appendChild(head);

        var first = new Date(state.cursor.getFullYear(), state.cursor.getMonth(), 1);
        var start = startOfWeek(first);
        var todayKey = todayYmd();
        var grid = h('div', 'cal-month-grid');

        for (var i = 0; i < 42; i++) {
            var date = addDays(start, i);
            var key = ymdOf(date);
            var items = byDate[key] || [];

            var cell = h('div', 'cal-cell');
            if (date.getMonth() !== first.getMonth()) cell.classList.add('is-outside');
            if (key === todayKey) cell.classList.add('is-today');
            cell.setAttribute('aria-label', fmtDayTitle(key) + (items.length ? ', ' + plural(items.length, 'task', 'tasks') : ''));
            dayHandlers(cell, key);

            cell.appendChild(h('span', 'cal-daynum', String(date.getDate())));

            if (items.length) {
                var list = h('div', 'cal-cell-tasks');
                items.forEach(function (task) { list.appendChild(buildChip(task)); });

                if (items.length > 2) {
                    var more = h('span', 'cal-more', '+' + (items.length - 2) + ' more');
                    list.appendChild(more);
                }
                cell.appendChild(list);
            }

            grid.appendChild(cell);
        }

        wrap.appendChild(grid);
        return wrap;
    }


    /* Week view */

    function buildWeek(byDate) {
        var wrap = h('div', 'cal-week');
        var start = startOfWeek(state.cursor);
        var todayKey = todayYmd();

        for (var i = 0; i < 7; i++) {
            var date = addDays(start, i);
            var key = ymdOf(date);
            var items = byDate[key] || [];

            var col = h('div', 'cal-day-col' + (key === todayKey ? ' is-today' : ''));
            col.setAttribute('aria-label', fmtDayTitle(key) + (items.length ? ', ' + plural(items.length, 'task', 'tasks') : ', no plans'));
            dayHandlers(col, key);

            var head = h('div', 'cal-day-col-head');
            head.appendChild(h('span', 'cal-day-col-dow', DOW[date.getDay()]));
            head.appendChild(h('span', 'cal-daynum', String(date.getDate())));
            col.appendChild(head);

            if (items.length === 0) {
                col.appendChild(h('span', 'cal-noplans', 'No plans'));
            } else {
                var list = h('div', 'cal-week-tasks');
                items.forEach(function (task) { list.appendChild(buildChip(task)); });
                col.appendChild(list);
            }

            wrap.appendChild(col);
        }

        return wrap;
    }


    /* Year view */

    function buildYear(list) {
        var wrap = h('div', 'cal-year');
        var year = state.cursor.getFullYear();
        var now = today();

        for (var m = 0; m < 12; m++) {
            var range = rangeFor('month', new Date(year, m, 1));
            var count = countIn(list, range);
            var isCurrent = now.getFullYear() === year && now.getMonth() === m;

            var card = h('button', 'cal-month-card' + (isCurrent ? ' is-current' : ''));
            card.type = 'button';
            card.setAttribute('data-month', String(m));
            card.appendChild(h('strong', null, MONTHS[m]));
            card.appendChild(h('span', 'cal-month-open', plural(count.open, 'open task', 'open tasks')));
            card.appendChild(h('span', 'cal-month-done', count.done + ' completed'));

            card.addEventListener('click', openMonthFromYear(year, m, isCurrent));
            wrap.appendChild(card);
        }

        return wrap;
    }

    function openMonthFromYear(year, month, isCurrent) {
        return function () {
            if (isCurrent) {
                state.cursor = today();
            } else {
                var day = Math.min(state.cursor.getDate(), daysInMonth(year, month));
                state.cursor = new Date(year, month, day);
            }
            state.view = 'month';
            render();
        };
    }


    /* Card around the current view */

    function buildHead(range, count) {
        var head = h('div', 'cal-card-head');
        head.appendChild(h('h2', 'cal-card-title', periodLabel(state.view, range)));

        var counts = h('div', 'cal-card-counts');
        counts.appendChild(h('span', null, count.open + ' open'));
        counts.appendChild(h('span', null, count.done + ' completed'));
        head.appendChild(counts);

        return head;
    }

    function buildSummary(count) {
        var labels = {
            month: ['Month summary', 'Tasks planned', 'this calendar view'],
            week: ['Week Summary', 'Activity planned', 'this week'],
            year: ['Year Summary', 'Activity planned', 'this calendar view']
        };
        var text = labels[state.view];

        var bar = h('div', 'cal-summary');
        var info = h('div', 'cal-summary-info');
        info.appendChild(icon('ti-calendar-event cal-summary-icon'));

        var copy = h('div', 'cal-summary-copy');
        copy.appendChild(h('small', null, text[0]));

        var title = h('strong', null, text[1]);
        title.appendChild(h('span', 'cal-summary-count', String(count.total)));
        copy.appendChild(title);

        copy.appendChild(h('p', null, 'A quick snapshot of what is on ' + text[2] + '.'));
        info.appendChild(copy);

        var add = h('button', 'cal-summary-btn');
        add.type = 'button';
        add.appendChild(icon('ti-plus'));
        add.appendChild(h('span', null, 'Add task'));
        add.addEventListener('click', function () { openForm(null, defaultDate()); });

        bar.appendChild(info);
        bar.appendChild(add);
        return bar;
    }

    // Date used when adding from the toolbar
    function defaultDate() {
        var range = rangeFor(state.view, state.cursor);
        return containsToday(range) ? todayYmd() : range.start;
    }


    /* Right side panel */

    function sideCard(iconName, title) {
        var card = h('section', 'cal-side-card');
        var heading = h('h3', 'cal-side-title');
        heading.appendChild(icon(iconName));
        heading.appendChild(h('span', null, title));
        card.appendChild(heading);
        return card;
    }

    function statRow(label, value, alert) {
        var row = h('div', 'cal-stat' + (alert ? ' is-alert' : ''));
        row.appendChild(h('span', null, label));
        row.appendChild(h('strong', null, String(value)));
        return row;
    }

    function buildSide(list, range, count) {
        sideEl.textContent = '';

        var names = { week: 'This week', month: 'This month', year: 'This year' };
        var periodTitle = containsToday(range) ? names[state.view] : periodLabel(state.view, range);

        var period = sideCard('ti-calendar', periodTitle);
        period.appendChild(statRow('Open tasks', count.open));
        period.appendChild(statRow('Completed', count.done));
        sideEl.appendChild(period);

        var upcoming = list.filter(function (task) {
            return isOpen(task) && task.due_date >= todayYmd();
        }).sort(compareByDue).slice(0, 10);

        var up = sideCard('ti-clock', 'Upcoming tasks');

        // Same "View all" link as Recent Credentials
        var viewAll = h('a', 'cal-view-all');
        viewAll.href = './tasks';
        viewAll.appendChild(h('span', null, 'View all'));
        viewAll.appendChild(icon('ti-arrow-right'));
        up.querySelector('.cal-side-title').appendChild(viewAll);

        if (upcoming.length === 0) {
            up.appendChild(h('p', 'cal-empty', 'Nothing coming up.'));
        }

        var upList = h('div', 'cal-up-list');
        upcoming.forEach(function (task) {
            var item = h('button', 'cal-up-item tone-' + toneOf(task));
            item.type = 'button';
            item.title = task.title;
            item.appendChild(h('span', 'cal-chip-dot'));
            item.appendChild(h('span', 'cal-up-title', task.title));
            item.appendChild(h('span', 'cal-up-date', fmtShort(task.due_date)));
            item.addEventListener('click', function () { openView(task); });
            upList.appendChild(item);
        });

        if (upcoming.length) {
            up.appendChild(upList);

            // Shows only after scrolling to the bottom of the list (like Recent Credentials)
            var seeMore = h('a', 'cal-see-more', 'See more');
            seeMore.href = './tasks';
            up.appendChild(seeMore);
        }
        sideEl.appendChild(up);

        if (upcoming.length) {
            var checkScrollEnd = function () {
                var hasOverflow = upList.scrollHeight > upList.clientHeight + 1;
                var reachedEnd = hasOverflow
                    && upList.scrollTop > 0
                    && upList.scrollTop + upList.clientHeight >= upList.scrollHeight - 4;
                seeMore.classList.toggle('is-visible', reachedEnd);
            };
            upList.addEventListener('scroll', checkScrollEnd);
            checkScrollEnd();
        }

        var overdue = list.filter(isOverdue).sort(compareByDue);
        var attention = sideCard('fa-solid fa-clock-rotate-left', 'Needs attention');
        var row = overdue.length ? h('button', 'cal-stat cal-stat-link is-alert') : h('div', 'cal-stat');
        if (overdue.length) {
            row.type = 'button';
            row.title = 'Go to the earliest overdue task';
            row.addEventListener('click', function () { jumpToTask(overdue[0]); });
        }
        row.appendChild(h('span', null, 'Overdue'));
        row.appendChild(h('strong', null, String(overdue.length)));
        attention.appendChild(row);
        sideEl.appendChild(attention);
    }

    function jumpToTask(task) {
        var date = parseYmd(task.due_date);
        if (!date) return;
        state.cursor = date;
        state.view = 'month';
        render();
        openView(task);
    }


    /* Render */

    function render() {
        var list = visibleTasks();
        var range = rangeFor(state.view, state.cursor);
        var count = countIn(list, range);

        mainEl.textContent = '';
        var card = h('div', 'cal-card');
        card.appendChild(buildHead(range, count));

        if (state.view === 'week') {
            card.appendChild(buildWeek(groupByDate(list)));
        } else if (state.view === 'year') {
            card.appendChild(buildYear(list));
        } else {
            card.appendChild(buildMonth(groupByDate(list)));
        }

        card.appendChild(buildSummary(count));
        mainEl.appendChild(card);

        buildSide(list, range, count);
        syncToolbar(edgeCounts(list, range));
        syncUrl();
    }

    var segButtons = document.querySelectorAll('.cal-seg-btn');
    var prevBtn = $('calPrev');
    var nextBtn = $('calNext');

    // Dots beside the arrows: unfinished tasks before (red) and after (yellow) the visible period
    var prevDot = h('span', 'cal-nav-dot');
    var nextDot = h('span', 'cal-nav-dot');
    prevDot.setAttribute('aria-hidden', 'true');
    nextDot.setAttribute('aria-hidden', 'true');
    prevBtn.appendChild(prevDot);
    nextBtn.insertBefore(nextDot, nextBtn.firstChild);

    function edgeCounts(list, range) {
        var edge = { behind: 0, ahead: 0 };
        list.forEach(function (task) {
            if (!isOpen(task)) return;
            if (task.due_date < range.start) edge.behind++;
            else if (task.due_date > range.end) edge.ahead++;
        });
        return edge;
    }

    function syncToolbar(edge) {
        for (var i = 0; i < segButtons.length; i++) {
            var on = segButtons[i].getAttribute('data-view') === state.view;
            segButtons[i].classList.toggle('is-active', on);
            segButtons[i].setAttribute('aria-pressed', on ? 'true' : 'false');
        }

        var unit = state.view;
        prevDot.classList.toggle('is-behind', edge.behind > 0);
        nextDot.classList.toggle('is-ahead', edge.ahead > 0);
        prevBtn.setAttribute('aria-label', 'Previous ' + unit + (edge.behind ? ', ' + plural(edge.behind, 'unfinished task', 'unfinished tasks') + ' before' : ''));
        nextBtn.setAttribute('aria-label', 'Next ' + unit + (edge.ahead ? ', ' + plural(edge.ahead, 'task', 'tasks') + ' to do after' : ''));
    }

    // Keep the view in the address so a refresh stays on the same page
    function syncUrl() {
        var isDefault = state.view === 'month' && ymdOf(state.cursor) === todayYmd();
        var query = isDefault ? '' : '?view=' + state.view + '&date=' + ymdOf(state.cursor);

        try {
            window.history.replaceState({}, '', window.location.pathname + query);
        } catch (error) { /* ignore */ }
    }

    function shift(direction) {
        if (state.view === 'week') state.cursor = addDays(state.cursor, 7 * direction);
        else if (state.view === 'year') state.cursor = addMonths(state.cursor, 12 * direction);
        else state.cursor = addMonths(state.cursor, direction);
        render();
    }

    prevBtn.addEventListener('click', function () { shift(-1); });
    nextBtn.addEventListener('click', function () { shift(1); });

    $('calToday').addEventListener('click', function () {
        state.cursor = today();
        render();
    });

    $('calAddBtn').addEventListener('click', function () { openForm(null, defaultDate()); });

    for (var s = 0; s < segButtons.length; s++) {
        segButtons[s].addEventListener('click', function (event) {
            var view = event.currentTarget.getAttribute('data-view');
            if (VIEWS.indexOf(view) === -1 || view === state.view) return;
            state.view = view;
            render();
        });
    }

    var searchInput = $('calSearchInput');
    searchInput.addEventListener('input', function () {
        state.term = searchInput.value.trim().toLowerCase();
        render();
    });


    /* Modals */

    function showModal(backdrop) {
        backdrop.classList.add('open');
        backdrop.setAttribute('aria-hidden', 'false');
    }

    function hideModal(backdrop) {
        backdrop.classList.remove('open');
        backdrop.setAttribute('aria-hidden', 'true');
    }

    function isShown(backdrop) {
        return backdrop.classList.contains('open');
    }


    /* Day modal */

    var dayEl = {
        backdrop: $('calDayBackdrop'),
        title: $('calDayTitle'),
        list: $('calDayList'),
        add: $('calDayAdd')
    };
    var dayKey = null;

    function openDay(ymd) {
        dayKey = ymd;
        dayEl.title.textContent = fmtDayTitle(ymd);
        dayEl.list.textContent = '';

        var items = visibleTasks().filter(function (task) { return task.due_date === ymd; }).sort(compareInDay);
        if (items.length === 0) {
            dayEl.list.appendChild(h('p', 'cal-empty', 'No tasks on this day.'));
        }
        items.forEach(function (task) { dayEl.list.appendChild(buildChip(task)); });

        showModal(dayEl.backdrop);
    }

    function closeDay() {
        hideModal(dayEl.backdrop);
        dayKey = null;
    }

    dayEl.add.addEventListener('click', function () {
        var date = dayKey;
        closeDay();
        openForm(null, date);
    });


    /* Task form (shared markup with the Tasks page) */

    var formEl = {
        backdrop: $('taskFormBackdrop'),
        form: $('taskForm'),
        heading: $('taskFormTitle'),
        title: $('taskTitleInput'),
        description: $('taskDescriptionInput'),
        due: $('taskDueInput'),
        priority: $('taskPriorityInput'),
        status: $('taskStatusInput'),
        submit: $('taskFormSubmit'),
        error: $('taskFormError')
    };
    var editingId = null;
    var saving = false;

    function syncSubmit() {
        formEl.submit.disabled = saving || formEl.title.value.trim() === '';
    }

    function clearFormErrors() {
        formEl.error.textContent = '';
        var fields = formEl.form.querySelectorAll('[data-error-for]');
        for (var i = 0; i < fields.length; i++) fields[i].textContent = '';
    }

    function openForm(task, dueDate) {
        closeDay();
        closeView();
        clearFormErrors();

        editingId = task ? task.task_id : null;
        saving = false;

        formEl.heading.textContent = task ? 'Edit Task' : 'Create New Task';
        formEl.submit.textContent = task ? 'Save Changes' : 'Create Task';
        formEl.title.value = task ? task.title : '';
        formEl.description.value = task ? task.description : '';
        formEl.due.value = task ? (task.due_date || '') : (dueDate || '');
        formEl.priority.value = task ? task.priority : 'medium';
        formEl.status.value = task ? task.status : 'todo';

        syncSubmit();
        showModal(formEl.backdrop);
        formEl.title.focus();
    }

    function closeForm() {
        hideModal(formEl.backdrop);
        editingId = null;
    }

    formEl.title.addEventListener('input', syncSubmit);

    formEl.form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (saving || formEl.title.value.trim() === '') return;

        clearFormErrors();
        saving = true;
        syncSubmit();

        var payload = {
            title: formEl.title.value.trim(),
            description: formEl.description.value.trim(),
            due_date: formEl.due.value,
            priority: formEl.priority.value,
            status: formEl.status.value
        };
        var wasEditing = editingId !== null;
        if (wasEditing) payload.task_id = editingId;

        api(wasEditing ? 'update' : 'create', payload).then(function (result) {
            saving = false;
            closeForm();
            applyServer(result);
            render();

            var saved = result && result.task;
            var message = wasEditing ? 'Task updated' : 'Task created';
            if (saved && !saved.due_date) {
                message += ' \u2022 no due date, so it is not on the calendar';
            } else if (saved && !inRange(saved, rangeFor(state.view, state.cursor))) {
                message += ' \u2022 due ' + fmtShort(saved.due_date);
            }
            toast(message);
        }).catch(function (error) {
            saving = false;
            syncSubmit();

            var shown = false;
            Object.keys(error.fields || {}).forEach(function (field) {
                var target = formEl.form.querySelector('[data-error-for="' + field + '"]');
                if (target) { target.textContent = error.fields[field]; shown = true; }
            });
            if (!shown) formEl.error.textContent = error.message;
        });
    });


    /* Task details */

    var viewEl = {
        backdrop: $('taskViewBackdrop'),
        title: $('viewTaskTitle'),
        priority: $('viewTaskPriority'),
        description: $('viewTaskDescription'),
        due: $('viewTaskDue'),
        status: $('viewTaskStatus'),
        created: $('viewTaskCreated'),
        edit: $('viewTaskEdit')
    };
    var viewingId = null;

    // Delete button next to Edit
    var viewDelete = h('button', 'task-btn task-btn-outline task-btn-delete', 'Delete');
    viewDelete.type = 'button';
    viewEl.edit.parentNode.insertBefore(viewDelete, viewEl.edit);

    function fillView(task) {
        viewEl.title.textContent = task.title;

        var priority = PRIORITIES[task.priority];
        viewEl.priority.className = 'task-chip chip-' + task.priority;
        viewEl.priority.textContent = '';
        viewEl.priority.appendChild(icon(priority.icon));
        viewEl.priority.appendChild(document.createTextNode(priority.label));

        viewEl.status.className = 'task-chip status-' + task.status;
        viewEl.status.textContent = STATUSES[task.status] || task.status;

        if (task.description) {
            viewEl.description.textContent = task.description;
            viewEl.description.classList.remove('is-empty');
        } else {
            viewEl.description.textContent = 'No description.';
            viewEl.description.classList.add('is-empty');
        }

        viewEl.due.textContent = task.due_date ? fmtLong(task.due_date) : 'No due date';
        viewEl.created.textContent = fmtCreated(task.created_at);
    }

    function openView(task) {
        closeDay();
        viewingId = task.task_id;
        fillView(task);
        showModal(viewEl.backdrop);
    }

    function closeView() {
        hideModal(viewEl.backdrop);
        viewingId = null;
    }

    viewEl.edit.addEventListener('click', function () {
        var task = viewingId !== null ? findTask(viewingId) : null;
        if (task) openForm(task);
    });

    viewDelete.addEventListener('click', function () {
        var task = viewingId !== null ? findTask(viewingId) : null;
        if (!task) return;

        confirmAction('Delete \u201c' + task.title + '\u201d? This can\u2019t be undone.', function () {
            return api('delete', { task_id: task.task_id }).then(function (result) {
                closeView();
                applyServer(result);
                render();
                toast('Task deleted');
            });
        });
    });


    [
        { backdrop: dayEl.backdrop, close: closeDay },
        { backdrop: formEl.backdrop, close: closeForm },
        { backdrop: viewEl.backdrop, close: closeView }
    ].forEach(function (modal) {
        modal.backdrop.addEventListener('click', function (event) {
            if (event.target === modal.backdrop) modal.close();
        });
        var closers = modal.backdrop.querySelectorAll('[data-task-close]');
        for (var i = 0; i < closers.length; i++) closers[i].addEventListener('click', modal.close);
    });

    // Close active dialog on Escape
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (document.querySelector('.sqd-dialog-backdrop.open')) return;
        if (isShown(formEl.backdrop)) { closeForm(); return; }
        if (isShown(viewEl.backdrop)) { closeView(); return; }
        if (isShown(dayEl.backdrop)) closeDay();
    }, true);


    /* Start */

    (function () {
        var params = new URLSearchParams(window.location.search);
        var view = params.get('view');
        var date = parseYmd(params.get('date'));

        if (VIEWS.indexOf(view) !== -1) state.view = view;
        if (date) state.cursor = date;
    })();

    render();
})();