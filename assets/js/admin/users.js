// Admin user list actions
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

    /* Row actions */
    if (tableBody) {
        tableBody.addEventListener('click', function (event) {
            var menuToggle = event.target.closest('[data-toggle-menu]');
            if (menuToggle) {
                var dropdown = menuToggle.parentElement.querySelector('.admin-action-dropdown');
                var isOpen = dropdown.classList.contains('open');
                $all('.admin-action-dropdown.open').forEach(function (d) { d.classList.remove('open'); });
                if (!isOpen) {
                    dropdown.classList.add('open');
                    placeDropdown(dropdown, menuToggle);
                }
                menuToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                event.stopPropagation();
                return;
            }

            var row = event.target.closest('tr[data-user-row]');
            if (!row) return;
            var userId = row.getAttribute('data-id');

            if (event.target.closest('[data-edit-user]')) {
                fillEditModal(row);
                openModal('editUser');
                return;
            }

            var userName = row.getAttribute('data-name');

            if (event.target.closest('[data-toggle-status]')) {
                var button = event.target.closest('[data-toggle-status]');
                var nextStatus = button.getAttribute('data-next-status');
                var suspending = nextStatus === 'suspended';

                AdminAlert.confirm({
                    title: suspending ? 'Suspend account?' : 'Activate account?',
                    text: 'Are you sure you want to ' + (suspending ? 'suspend ' : 'activate ') + userName + '?',
                    confirmText: suspending ? 'Suspend' : 'Activate',
                    danger: suspending
                }).then(function (ok) {
                    if (!ok) return;
                    postAjax('update_status', { user_id: userId, status: nextStatus }).then(function (result) {
                        if (!result.ok) { AdminAlert.error(result.json.error); return; }
                        window.location.reload();
                    }).catch(function () { AdminAlert.error('Could not reach the server. Please try again.'); });
                });
                return;
            }

            if (event.target.closest('[data-force-logout]')) {
                AdminAlert.confirm({
                    title: 'Force logout?',
                    text: 'Sign ' + userName + ' out of all devices?',
                    confirmText: 'Force Logout'
                }).then(function (ok) {
                    if (!ok) return;
                    postAjax('force_logout', { user_id: userId }).then(function (result) {
                        if (!result.ok) { AdminAlert.error(result.json.error); return; }
                        AdminAlert.success('Logged out', userName + ' has been signed out of all devices.');
                    }).catch(function () { AdminAlert.error('Could not reach the server. Please try again.'); });
                });
                return;
            }

            if (event.target.closest('[data-delete-user]')) {
                AdminAlert.confirm({
                    title: 'Delete user?',
                    text: 'Delete ' + userName + '? This cannot be undone.',
                    confirmText: 'Delete',
                    danger: true
                }).then(function (ok) {
                    if (!ok) return;
                    postAjax('delete_user', { user_id: userId }).then(function (result) {
                        if (!result.ok) { AdminAlert.error(result.json.error); return; }
                        AdminAlert.success('User deleted', userName + ' has been deleted.').then(function () {
                            window.location.reload();
                        });
                    }).catch(function () { AdminAlert.error('Could not reach the server. Please try again.'); });
                });
            }
        });
    }

    // Keep the menu above the scroll area.
    function placeDropdown(dropdown, button) {
        var rect = button.getBoundingClientRect();
        var width = dropdown.offsetWidth;
        var height = dropdown.offsetHeight;
        var left = Math.min(Math.max(8, rect.right - width), window.innerWidth - width - 8);
        var top = rect.bottom + 6;
        if (top + height > window.innerHeight - 8) top = Math.max(8, rect.top - height - 6);
        dropdown.style.left = left + 'px';
        dropdown.style.top = top + 'px';
        dropdown.style.right = 'auto';
    }

    function closeDropdowns() {
        $all('.admin-action-dropdown.open').forEach(function (d) { d.classList.remove('open'); });
        $all('[data-toggle-menu][aria-expanded="true"]').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
    }
    document.addEventListener('click', closeDropdowns);
    window.addEventListener('resize', closeDropdowns);
    document.addEventListener('scroll', closeDropdowns, true); // scrolling any list or the page

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