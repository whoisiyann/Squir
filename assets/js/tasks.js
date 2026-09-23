
(function () {
    'use strict';

    var cfg = window.SQUIR_TASKS || { csrf: '', tasks: [] };
    var tasks = (cfg.tasks || []).slice();
    var board = document.getElementById('tasksBoard');
    if (!board) return;

    var STATUSES = [
        { key: 'todo', label: 'To Do' },
        { key: 'in_progress', label: 'In Progress' },
        { key: 'done', label: 'Done' }
    ];
    var PRIORITIES = {
        high: { label: 'High', icon: 'ti-arrow-up' },
        medium: { label: 'Medium', icon: 'ti-minus' },
        low: { label: 'Low', icon: 'ti-arrow-down' }
    };
    var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    var searchTerm = '';
    var dragId = null;
    var indicatorEl = null;


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

    function statusLabel(key) {
        for (var i = 0; i < STATUSES.length; i++) {
            if (STATUSES[i].key === key) return STATUSES[i].label;
        }
        return key;
    }

    function findTask(id) {
        for (var i = 0; i < tasks.length; i++) {
            if (tasks[i].task_id === id) return tasks[i];
        }
        return null;
    }


    function pad(n) { return n < 10 ? '0' + n : String(n); }

    function todayYmd() {
        var d = new Date();
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }

    function fmtLong(ymd) {
        var p = ymd.split('-');
        return MONTHS[+p[1] - 1] + ' ' + (+p[2]) + ', ' + p[0];
    }

    function fmtShort(ymd) {
        var p = ymd.split('-');
        var text = MONTHS[+p[1] - 1].slice(0, 3) + ' ' + (+p[2]);
        return +p[0] === new Date().getFullYear() ? text : text + ', ' + p[0];
    }

    function fmtCreated(stamp) {
        var m = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/.exec(stamp || '');
        if (!m) return '';
        var hour = +m[4];
        var suffix = hour >= 12 ? 'PM' : 'AM';
        var hour12 = hour % 12 || 12;
        return fmtLong(m[1] + '-' + m[2] + '-' + m[3]) + ' \u2022 ' + hour12 + ':' + m[5] + ' ' + suffix;
    }


    var APPROACHING_DAYS = 7;

    function daysUntil(ymd) {
        var p = ymd.split('-');
        var due = new Date(+p[0], +p[1] - 1, +p[2]);
        var now = new Date();
        var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        return Math.round((due - today) / 86400000);
    }

    function dueState(task) {
        if (!task.due_date || task.status === 'done') return null;

        var days = daysUntil(task.due_date);
        if (days < 0) return 'overdue';
        if (days <= APPROACHING_DAYS) return 'approaching';
        return null;
    }


    function byPosition(a, b) {
        return (a.position - b.position) || (a.task_id - b.task_id);
    }

    function columnTasks(statusKey) {
        return tasks.filter(function (t) { return t.status === statusKey; }).sort(byPosition);
    }

    function matchesSearch(task) {
        if (searchTerm === '') return true;
        return (task.title + ' ' + task.description).toLowerCase().indexOf(searchTerm) !== -1;
    }


    // Send a task request
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


    // Apply the refreshed task board
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
        toastTimer = setTimeout(function () { toastEl.classList.remove('show'); }, 2400);
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


    // Render the task board
    function render() {
        closeMenu();
        board.textContent = '';

        STATUSES.forEach(function (status) {
            var items = columnTasks(status.key).filter(matchesSearch);
            board.appendChild(buildColumn(status, items));
        });
    }

    // Build a task column
    function buildColumn(status, items) {
        var column = h('section', 'task-column');
        column.setAttribute('data-status', status.key);

        var head = h('div', 'task-column-head');
        var title = h('div', 'task-column-title');
        title.appendChild(h('span', 'task-status-pill status-' + status.key, status.label));
        title.appendChild(h('span', 'task-count', String(items.length)));

        var actions = h('div', 'task-column-actions');

        var more = h('button', 'task-icon-btn');
        more.type = 'button';
        more.setAttribute('aria-label', status.label + ' column options');
        more.setAttribute('aria-haspopup', 'menu');
        more.setAttribute('aria-expanded', 'false');
        more.appendChild(icon('ti-dots'));
        more.addEventListener('click', function (event) {
            event.stopPropagation();
            toggleMenu(more, columnMenuItems(status), null, event.detail === 0);
        });

        var add = h('button', 'task-icon-btn is-add');
        add.type = 'button';
        add.setAttribute('aria-label', 'Add task to ' + status.label);
        add.appendChild(icon('ti-plus'));
        add.addEventListener('click', function () { openForm(null, status.key); });

        actions.appendChild(more);
        actions.appendChild(add);
        head.appendChild(title);
        head.appendChild(actions);

        var list = h('div', 'task-list');
        items.forEach(function (task) { list.appendChild(buildCard(task)); });

        var ghost = h('button', 'task-add-ghost');
        ghost.type = 'button';
        ghost.appendChild(icon('ti-plus'));
        ghost.appendChild(h('span', null, 'Add task'));
        ghost.addEventListener('click', function () { openForm(null, status.key); });
        list.appendChild(ghost);

        column.appendChild(head);
        column.appendChild(list);
        bindDropZone(column, status.key);

        return column;
    }

    // Build a task card
    function buildCard(task) {
        var done = task.status === 'done';
        var card = h('div', 'task-card' + (done ? ' is-done' : ''));
        card.draggable = true;
        card.tabIndex = 0;
        card.setAttribute('data-id', String(task.task_id));

        var grip = h('button', 'task-grip');
        grip.type = 'button';
        grip.setAttribute('aria-label', 'Task options');
        grip.setAttribute('aria-haspopup', 'menu');
        grip.setAttribute('aria-expanded', 'false');
        grip.appendChild(icon('ti-grip-vertical'));
        grip.addEventListener('click', function (event) {
            event.stopPropagation();
            toggleMenu(grip, cardMenuItems(task), card, event.detail === 0);
        });

        var main = h('div', 'task-card-main');
        var title = h('div', 'task-card-title', task.title);
        title.title = task.title;
        main.appendChild(title);

        if (!done) {
            var state = dueState(task);
            var dueText = task.due_date ? fmtShort(task.due_date) : 'No due date';
            if (state === 'overdue') dueText = '(Overdue) ' + dueText;
            else if (state === 'approaching') dueText = '(Approaching) ' + dueText;

            var due = h('div', 'task-card-due' + (state ? ' is-' + state : ''));
            due.appendChild(icon('ti-calendar-event'));
            due.appendChild(h('span', null, dueText));
            main.appendChild(due);
        }

        card.appendChild(grip);
        card.appendChild(main);
        if (!done) {
            card.appendChild(h('span', 'task-priority priority-' + task.priority, PRIORITIES[task.priority].label));
        }

        card.addEventListener('click', function (event) {
            if (event.target.closest('.task-grip')) return;
            openView(task);
        });
        card.addEventListener('keydown', function (event) {
            if (event.target !== card) return;
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openView(task);
            }
        });

        card.addEventListener('dragstart', function (event) {
            dragId = task.task_id;
            closeMenu();
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(task.task_id)); // Firefox requires this
            card.classList.add('is-dragging');
        });
        card.addEventListener('dragend', function () {
            dragId = null;
            card.classList.remove('is-dragging');
            clearDropTargets();
        });

        return card;
    }


    function clearIndicator() {
        if (indicatorEl) {
            indicatorEl.classList.remove('drop-before', 'drop-after');
            indicatorEl = null;
        }
    }

    function clearDropTargets() {
        clearIndicator();
        var targets = board.querySelectorAll('.is-drop-target');
        for (var i = 0; i < targets.length; i++) targets[i].classList.remove('is-drop-target');
    }


    function dropSpot(column, y) {
        var cards = column.querySelectorAll('.task-card:not(.is-dragging)');

        for (var i = 0; i < cards.length; i++) {
            var rect = cards[i].getBoundingClientRect();
            if (y < rect.top + rect.height / 2) {
                return { refId: +cards[i].getAttribute('data-id'), after: false, el: cards[i] };
            }
        }

        if (cards.length > 0) {
            var last = cards[cards.length - 1];
            return { refId: +last.getAttribute('data-id'), after: true, el: last };
        }

        return { refId: null, after: true, el: null };
    }

    function showIndicator(column, y) {
        var spot = dropSpot(column, y);
        clearIndicator();
        if (spot.el) {
            spot.el.classList.add(spot.after ? 'drop-after' : 'drop-before');
            indicatorEl = spot.el;
        }
    }

    function bindDropZone(column, statusKey) {
        var depth = 0;

        function isOtherColumn() {
            var dragged = dragId !== null ? findTask(dragId) : null;
            return !!dragged && dragged.status !== statusKey;
        }

        column.addEventListener('dragenter', function (event) {
            if (dragId === null) return;
            event.preventDefault();
            depth++;
            if (isOtherColumn()) column.classList.add('is-drop-target');
            showIndicator(column, event.clientY);
        });
        column.addEventListener('dragover', function (event) {
            if (dragId === null) return;
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            showIndicator(column, event.clientY);
        });
        column.addEventListener('dragleave', function () {
            if (dragId === null) return;
            depth = Math.max(0, depth - 1);
            if (depth === 0) {
                column.classList.remove('is-drop-target');
                clearIndicator();
            }
        });
        column.addEventListener('drop', function (event) {
            if (dragId === null) return;
            event.preventDefault();
            depth = 0;

            var spot = dropSpot(column, event.clientY);
            var id = dragId;
            dragId = null;
            clearDropTargets();

            setTimeout(function () { dropTask(id, statusKey, spot); }, 0);
        });
    }


    // Handle task drop placement
    function dropTask(id, statusKey, spot) {
        var task = findTask(id);
        if (!task) return;

        var others = columnTasks(statusKey).filter(function (t) { return t.task_id !== id; });
        var index = others.length;

        if (spot.refId !== null) {
            for (var i = 0; i < others.length; i++) {
                if (others[i].task_id === spot.refId) {
                    index = spot.after ? i + 1 : i;
                    break;
                }
            }
        }

        var ids = others.map(function (t) { return t.task_id; });
        ids.splice(index, 0, id);

        var current = columnTasks(statusKey).map(function (t) { return t.task_id; });
        if (task.status === statusKey && current.join(',') === ids.join(',')) return; // No change

        var snapshot = tasks.map(function (t) { return Object.assign({}, t); });

        task.status = statusKey;
        ids.forEach(function (taskId, position) {
            var t = findTask(taskId);
            if (t) t.position = position;
        });
        render();

        api('reorder', { task_id: id, status: statusKey, ids: ids.join(',') }).then(function (result) {
            applyServer(result);
            render();
        }).catch(function (error) {
            tasks = snapshot;
            render();
            toast(error.message);
        });
    }


    // Move a task to another status
    function moveTask(id, status) {
        var task = findTask(id);
        if (!task || task.status === status) return;

        api('move', { task_id: id, status: status }).then(function (result) {
            applyServer(result);
            render();
        }).catch(function (error) { toast(error.message); });
    }

    // Move all tasks between statuses
    function moveAll(from, to) {
        var count = columnTasks(from).length;
        if (count === 0) return;

        api('move_all', { from: from, to: to }).then(function (result) {
            applyServer(result);
            render();
            toast(count + (count === 1 ? ' task' : ' tasks') + ' moved to ' + statusLabel(to));
        }).catch(function (error) { toast(error.message); });
    }

    // Duplicate a task
    function duplicateTask(id) {
        api('duplicate', { task_id: id }).then(function (result) {
            applyServer(result);
            render();
            toast('Task duplicated');
        }).catch(function (error) { toast(error.message); });
    }

    // Confirm task deletion
    function confirmDeleteTask(task) {
        confirmAction('Delete \u201c' + task.title + '\u201d? This can\u2019t be undone.', function () {
            return api('delete', { task_id: task.task_id }).then(function (result) {
                applyServer(result);
                render();
                toast('Task deleted');
            });
        });
    }

    // Confirm clearing a task column
    function confirmClearColumn(status) {
        var count = columnTasks(status.key).length;
        if (count === 0) return;

        var noun = count === 1 ? 'task' : 'tasks';
        confirmAction('Delete all ' + count + ' ' + noun + ' in \u201c' + status.label + '\u201d? This can\u2019t be undone.', function () {
            return api('clear_all', { status: status.key }).then(function (result) {
                applyServer(result);
                render();
                toast('Cleared ' + status.label);
            });
        });
    }

    /* Task and column menus */
/* Task and column menus */

    var menu = null;
    var sub = null;
    var subParent = null;
    var menuAnchor = null;
    var menuCard = null;

    function cardMenuItems(task) {
        return [
            {
                icon: 'ti-clipboard-check',
                label: 'Mark task as',
                submenu: STATUSES.map(function (s) {
                    return {
                        key: s.key,
                        label: s.label,
                        current: task.status === s.key,
                        onClick: function () { moveTask(task.task_id, s.key); }
                    };
                })
            },
            { icon: 'ti-edit', label: 'Edit', onClick: function () { openForm(task); } },
            { icon: 'ti-copy', label: 'Duplicate', onClick: function () { duplicateTask(task.task_id); } },
            { icon: 'ti-trash', label: 'Delete', danger: true, onClick: function () { confirmDeleteTask(task); } }
        ];
    }

    function columnMenuItems(status) {
        var total = columnTasks(status.key).length;
        var others = STATUSES.filter(function (s) { return s.key !== status.key; });

        return [
            {
                icon: 'ti-arrows-right',
                label: 'Move all to',
                disabled: total === 0,
                submenu: others.map(function (s) {
                    return {
                        key: s.key,
                        label: s.label,
                        current: false,
                        onClick: function () { moveAll(status.key, s.key); }
                    };
                })
            },
            {
                icon: 'ti-trash',
                label: 'Clear all',
                danger: true,
                disabled: total === 0,
                onClick: function () { confirmClearColumn(status); }
            }
        ];
    }

    function place(el, rect, mode) {
        el.style.left = '0px';
        el.style.top = '0px';

        var width = el.offsetWidth;
        var height = el.offsetHeight;
        var vw = window.innerWidth;
        var vh = window.innerHeight;
        var gap = 6;
        var left;
        var top;

        if (mode === 'side') {
            left = rect.right + gap;
            if (left + width > vw - 8) left = rect.left - width - gap;
            top = rect.top - 6;
            if (top + height > vh - 8) top = vh - height - 8;
        } else {
            left = rect.left;
            if (left + width > vw - 8) left = rect.right - width;
            top = rect.bottom + gap;
            if (top + height > vh - 8) top = rect.top - height - gap;
        }

        el.style.left = Math.max(8, left) + 'px';
        el.style.top = Math.max(8, top) + 'px';
    }

    function buildMenuItem(item) {
        var button = h('button', 'task-menu-item' + (item.danger ? ' is-danger' : '') + (item.disabled ? ' is-disabled' : ''));
        button.type = 'button';
        button.setAttribute('role', 'menuitem');
        button.appendChild(icon(item.icon));
        button.appendChild(h('span', null, item.label));

        if (item.disabled) button.setAttribute('aria-disabled', 'true');

        if (item.submenu) {
            button.setAttribute('aria-haspopup', 'true');
            button.appendChild(icon('ti-chevron-right task-menu-chevron'));
            button.addEventListener('mouseenter', function () {
                if (!item.disabled) openSub(button, item.submenu);
            });
            button.addEventListener('click', function (event) {
                event.stopPropagation();
                if (!item.disabled) openSub(button, item.submenu, event.detail === 0);
            });
        } else {
            button.addEventListener('mouseenter', closeSub);
            button.addEventListener('click', function (event) {
                event.stopPropagation();
                if (item.disabled) return;
                closeMenu();
                item.onClick();
            });
        }

        return button;
    }

    function openSub(parentButton, options, focusFirst) {
        if (subParent === parentButton && sub) return;
        closeSub();

        sub = h('div', 'task-submenu');
        sub.setAttribute('role', 'menu');

        options.forEach(function (option) {
            var button = h('button', 'task-status-option status-' + option.key);
            button.type = 'button';
            button.setAttribute('role', 'menuitem');
            button.appendChild(h('span', null, option.label));
            if (option.current) button.appendChild(icon('ti-check'));
            button.addEventListener('click', function (event) {
                event.stopPropagation();
                closeMenu();
                if (!option.current) option.onClick();
            });
            sub.appendChild(button);
        });

        document.body.appendChild(sub);
        parentButton.classList.add('is-open');
        subParent = parentButton;
        place(sub, parentButton.getBoundingClientRect(), 'side');

        if (focusFirst) sub.querySelector('button').focus();
    }

    function closeSub() {
        if (sub) { sub.remove(); sub = null; }
        if (subParent) { subParent.classList.remove('is-open'); subParent = null; }
    }

    function closeMenu() {
        closeSub();
        if (menu) { menu.remove(); menu = null; }
        if (menuAnchor) {
            menuAnchor.setAttribute('aria-expanded', 'false');
            menuAnchor = null;
        }
        if (menuCard) {
            menuCard.classList.remove('menu-open');
            menuCard = null;
        }
    }

    function openMenu(anchor, items, card, viaKeyboard) {
        closeMenu();

        menu = h('div', 'task-menu');
        menu.setAttribute('role', 'menu');
        items.forEach(function (item) { menu.appendChild(buildMenuItem(item)); });
        document.body.appendChild(menu);
        place(menu, anchor.getBoundingClientRect(), 'below');

        menuAnchor = anchor;
        anchor.setAttribute('aria-expanded', 'true');
        if (card) {
            menuCard = card;
            card.classList.add('menu-open');
        }
        if (viaKeyboard) menu.querySelector('button').focus();
    }

    function toggleMenu(anchor, items, card, viaKeyboard) {
        if (menu && menuAnchor === anchor) {
            closeMenu();
            return;
        }
        openMenu(anchor, items, card, viaKeyboard);
    }

    document.addEventListener('click', function (event) {
        if (!menu) return;
        if (menu.contains(event.target) || (sub && sub.contains(event.target))) return;
        closeMenu();
    });
    document.addEventListener('scroll', function (event) {
        if (!menu) return;
        if (menu.contains(event.target) || (sub && sub.contains(event.target))) return;
        closeMenu();
    }, true);
    window.addEventListener('resize', closeMenu);


    function showModal(backdrop) {
        backdrop.classList.add('open');
        backdrop.setAttribute('aria-hidden', 'false');
    }

    function hideModal(backdrop) {
        backdrop.classList.remove('open');
        backdrop.setAttribute('aria-hidden', 'true');
    }

    function isOpen(backdrop) {
        return backdrop.classList.contains('open');
    }


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

    function openForm(task, defaultStatus) {
        closeMenu();
        clearFormErrors();

        editingId = task ? task.task_id : null;
        saving = false;

        formEl.heading.textContent = task ? 'Edit Task' : 'Create New Task';
        formEl.submit.textContent = task ? 'Save Changes' : 'Create Task';
        formEl.title.value = task ? task.title : '';
        formEl.description.value = task ? task.description : '';
        formEl.due.value = task && task.due_date ? task.due_date : '';
        formEl.priority.value = task ? task.priority : 'medium';
        formEl.status.value = task ? task.status : (defaultStatus || 'todo');

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
            toast(wasEditing ? 'Task updated' : 'Task created');
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


    var viewEl = {
        backdrop: $('taskViewBackdrop'),
        title: $('viewTaskTitle'),
        priority: $('viewTaskPriority'),
        description: $('viewTaskDescription'),
        due: $('viewTaskDue'),
        status: $('viewTaskStatus'),
        created: $('viewTaskCreated'),
        remove: $('viewTaskDelete')
    };
    var viewingId = null;

    function fillView(task) {
        viewEl.title.textContent = task.title;

        var priority = PRIORITIES[task.priority];
        viewEl.priority.className = 'task-chip chip-' + task.priority;
        viewEl.priority.textContent = '';
        viewEl.priority.appendChild(icon(priority.icon));
        viewEl.priority.appendChild(document.createTextNode(priority.label));

        viewEl.status.className = 'task-chip status-' + task.status;
        viewEl.status.textContent = statusLabel(task.status);

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
        closeMenu();
        viewingId = task.task_id;
        fillView(task);
        showModal(viewEl.backdrop);
    }

    function closeView() {
        hideModal(viewEl.backdrop);
        viewingId = null;
    }

    viewEl.remove.addEventListener('click', function () {
        var task = viewingId !== null ? findTask(viewingId) : null;
        if (task) confirmDeleteTask(task);
    });


    [
        { backdrop: formEl.backdrop, close: closeForm },
        { backdrop: viewEl.backdrop, close: closeView }
    ].forEach(function (modal) {
        modal.backdrop.addEventListener('click', function (event) {
            if (event.target === modal.backdrop) modal.close();
        });
        var closers = modal.backdrop.querySelectorAll('[data-task-close]');
        for (var i = 0; i < closers.length; i++) closers[i].addEventListener('click', modal.close);
    });

    // Close only the active dialog on Escape
    // Close only the active dialog on Escape
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (menu) { closeMenu(); return; }
        if (document.querySelector('.sqd-dialog-backdrop.open')) return;
        if (isOpen(formEl.backdrop)) { closeForm(); return; }
        if (isOpen(viewEl.backdrop)) closeView();
    }, true);

    /* =============== Search =============== */

    var searchInput = $('tasksSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            searchTerm = searchInput.value.trim().toLowerCase();
            render();
        });
    }

    render();

    // ./tasks?new=1   -> open "Create New Task" right away
    // ./tasks?task=ID -> open that task's details
    // Open task dialogs from dashboard links
    (function () {
        var params = new URLSearchParams(window.location.search);
        var wantsNew = params.get('new') === '1';
        var openId = parseInt(params.get('task'), 10);

        if (wantsNew) {
            openForm(null, 'todo');
        } else if (openId > 0) {
            var linked = findTask(openId);
            if (linked) openView(linked);
        }

        if (wantsNew || params.has('task')) {
            params.delete('new');
            params.delete('task');
            var query = params.toString();
            window.history.replaceState({}, '', window.location.pathname + (query ? '?' + query : ''));
        }
    })();
})();
