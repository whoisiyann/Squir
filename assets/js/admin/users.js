// Squir Admin - Users page (search/filter, row actions, Add/Edit/View modals).
(function () {
    function $(selector, scope) { return (scope || document).querySelector(selector); }
    function $all(selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); }

    var csrfToken = window.ADMIN_CSRF_TOKEN || '';
    var tableBody = $('#usersTableBody');
    var emptyRow = $('#usersEmptyRow');

    function postAjax(action, fields) {
        var body = new URLSearchParams();
        body.set('ajax', action);
        body.set('csrf_token', csrfToken);
        Object.keys(fields || {}).forEach(function (key) { body.set(key, fields[key]); });

        return fetch('./users', {
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

    /* ---- search + status filter ---- */
    function applyFilters() {
        if (!tableBody) return;
        var query = ($('#usersSearch') ? $('#usersSearch').value : '').trim().toLowerCase();
        var status = $('#usersStatusFilter') ? $('#usersStatusFilter').value : 'all';
        var visibleCount = 0;

        $all('tr[data-user-row]', tableBody).forEach(function (row) {
            var name = (row.getAttribute('data-name') || '').toLowerCase();
            var email = (row.getAttribute('data-email') || '').toLowerCase();
            var rowStatus = row.getAttribute('data-status') || '';

            var matchesQuery = query === '' || name.indexOf(query) !== -1 || email.indexOf(query) !== -1;
            var matchesStatus = status === 'all' || rowStatus === status;
            var visible = matchesQuery && matchesStatus;

            row.classList.toggle('is-hidden', !visible);
            if (visible) visibleCount++;
        });

        if (emptyRow) emptyRow.classList.toggle('is-hidden', visibleCount !== 0);
    }

    var searchInput = $('#usersSearch');
    if (searchInput) searchInput.addEventListener('input', applyFilters);

    var statusFilter = $('#usersStatusFilter');
    if (statusFilter) statusFilter.addEventListener('change', applyFilters);

    /* ---- generic modal open/close ---- */
    function openModal(name) {
        var backdrop = document.getElementById(name + 'ModalBackdrop');
        if (backdrop) backdrop.classList.add('open');
    }
    function closeModal(name) {
        var backdrop = document.getElementById(name + 'ModalBackdrop');
        if (backdrop) backdrop.classList.remove('open');
    }
    $all('[data-close-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () { closeModal(btn.getAttribute('data-close-modal')); });
    });
    $all('.admin-modal-backdrop').forEach(function (backdrop) {
        backdrop.addEventListener('click', function (event) {
            if (event.target === backdrop) closeModal(backdrop.id.replace('ModalBackdrop', ''));
        });
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') $all('.admin-modal-backdrop.open').forEach(function (b) {
            closeModal(b.id.replace('ModalBackdrop', ''));
        });
    });

    function clearErrors(form) {
        $all('.admin-field-error', form).forEach(function (el) { el.textContent = ''; el.classList.remove('show'); });
        var alertBox = $('.admin-form-alert', form);
        if (alertBox) { alertBox.textContent = ''; alertBox.classList.remove('show'); }
    }

    function showErrors(form, errors, fallbackMessage) {
        clearErrors(form);
        var shown = false;
        Object.keys(errors || {}).forEach(function (field) {
            var el = $('[data-error-for="' + field + '"]', form);
            if (el) { el.textContent = errors[field]; el.classList.add('show'); shown = true; }
        });
        if (!shown) {
            var alertBox = $('.admin-form-alert', form);
            if (alertBox) { alertBox.textContent = fallbackMessage || 'Something went wrong.'; alertBox.classList.add('show'); }
        }
    }

    /* ---- Add User ---- */
    var addBtn = document.getElementById('openAddUserModal');
    if (addBtn) addBtn.addEventListener('click', function () { openModal('addUser'); });

    var addForm = document.getElementById('addUserForm');
    if (addForm) {
        addForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var submitBtn = $('button[type="submit"]', addForm);
            if (submitBtn) submitBtn.disabled = true;

            postAjax('add_user', {
                full_name: addForm.full_name.value,
                username: addForm.username.value,
                email: addForm.email.value,
                password: addForm.password.value,
                password_confirmation: addForm.password_confirmation.value
            }).then(function (result) {
                if (submitBtn) submitBtn.disabled = false;
                if (!result.ok) {
                    showErrors(addForm, result.json.errors, result.json.error);
                    return;
                }
                window.location.reload();
            }).catch(function () {
                if (submitBtn) submitBtn.disabled = false;
                showErrors(addForm, {}, 'Could not reach the server. Please try again.');
            });
        });
    }

    /* ---- row-level: view / edit / status toggle / delete ---- */
    if (tableBody) {
        tableBody.addEventListener('click', function (event) {
            var menuToggle = event.target.closest('[data-toggle-menu]');
            if (menuToggle) {
                var dropdown = menuToggle.parentElement.querySelector('.admin-action-dropdown');
                var isOpen = dropdown.classList.contains('open');
                $all('.admin-action-dropdown.open').forEach(function (d) { d.classList.remove('open'); });
                if (!isOpen) dropdown.classList.add('open');
                event.stopPropagation();
                return;
            }

            var row = event.target.closest('tr[data-user-row]');
            if (!row) return;
            var userId = row.getAttribute('data-id');

            if (event.target.closest('[data-view-user]')) {
                fillViewModal(row);
                openModal('viewUser');
                return;
            }

            if (event.target.closest('[data-edit-user]')) {
                fillEditModal(row);
                openModal('editUser');
                return;
            }

            if (event.target.closest('[data-toggle-status]')) {
                var button = event.target.closest('[data-toggle-status]');
                var nextStatus = button.getAttribute('data-next-status');
                var label = nextStatus === 'suspended' ? 'suspend' : 'activate';
                if (!window.confirm('Are you sure you want to ' + label + ' ' + row.getAttribute('data-name') + '?')) return;

                postAjax('update_status', { user_id: userId, status: nextStatus }).then(function (result) {
                    if (!result.ok) { window.alert(result.json.error || 'Something went wrong.'); return; }
                    window.location.reload();
                });
                return;
            }

            if (event.target.closest('[data-delete-user]')) {
                if (!window.confirm('Delete ' + row.getAttribute('data-name') + '? This cannot be undone.')) return;

                postAjax('delete_user', { user_id: userId }).then(function (result) {
                    if (!result.ok) { window.alert(result.json.error || 'Something went wrong.'); return; }
                    window.location.reload();
                });
            }
        });
    }

    document.addEventListener('click', function () {
        $all('.admin-action-dropdown.open').forEach(function (d) { d.classList.remove('open'); });
    });

    function fillViewModal(row) {
        $('#viewUserAvatar').textContent = initials(row.getAttribute('data-name'));
        $('#viewUserName').textContent = row.getAttribute('data-name');
        $('#viewUserUsername').textContent = '@' + row.getAttribute('data-username');
        $('#viewUserEmail').textContent = row.getAttribute('data-email');
        $('#viewUserStatus').textContent = capitalize(row.getAttribute('data-status'));
        $('#viewUserJoined').textContent = row.getAttribute('data-joined');
        $('#viewUserLastLogin').textContent = row.getAttribute('data-lastlogin');
    }

    function fillEditModal(row) {
        var form = document.getElementById('editUserForm');
        clearErrors(form);
        form.user_id.value = row.getAttribute('data-id');
        form.full_name.value = row.getAttribute('data-name');
        form.username.value = row.getAttribute('data-username');
        form.email.value = row.getAttribute('data-email');
    }

    function initials(name) {
        var parts = (name || '').trim().split(/\s+/).filter(Boolean);
        if (parts.length === 0) return 'U';
        var chars = parts[0].charAt(0);
        if (parts.length > 1) chars += parts[parts.length - 1].charAt(0);
        return chars.toUpperCase();
    }

    function capitalize(value) {
        value = value || '';
        return value.charAt(0).toUpperCase() + value.slice(1);
    }

    var editForm = document.getElementById('editUserForm');
    if (editForm) {
        editForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var submitBtn = $('button[type="submit"]', editForm);
            if (submitBtn) submitBtn.disabled = true;

            postAjax('update_user', {
                user_id: editForm.user_id.value,
                full_name: editForm.full_name.value,
                username: editForm.username.value,
                email: editForm.email.value
            }).then(function (result) {
                if (submitBtn) submitBtn.disabled = false;
                if (!result.ok) {
                    showErrors(editForm, result.json.errors, result.json.error);
                    return;
                }
                window.location.reload();
            }).catch(function () {
                if (submitBtn) submitBtn.disabled = false;
                showErrors(editForm, {}, 'Could not reach the server. Please try again.');
            });
        });
    }

    applyFilters();
})();
