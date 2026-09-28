// Admin user actions
(function () {
    var actions = document.querySelector('.ud-actions');
    if (!actions) return;

    var csrfToken = window.ADMIN_CSRF_TOKEN || '';
    var userId = actions.getAttribute('data-user-id');
    var userName = actions.getAttribute('data-user-name') || 'this user';
    var usersUrl = actions.getAttribute('data-users-url') || './users';

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

    function networkError() {
        AdminAlert.error('Could not reach the server. Please try again.');
    }

    /* ---- 3-dots menu ---- */
    var menuToggle = document.getElementById('udMenuToggle');
    var dropdown = actions.querySelector('.admin-action-dropdown');
    if (menuToggle && dropdown) {
        menuToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            var open = !dropdown.classList.contains('open');
            dropdown.classList.toggle('open', open);
            menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) placeDropdown(dropdown, menuToggle);
        });
        document.addEventListener('click', function () {
            dropdown.classList.remove('open');
            menuToggle.setAttribute('aria-expanded', 'false');
        });
        window.addEventListener('resize', closeMenu);
        window.addEventListener('scroll', closeMenu, true);
    }

    function closeMenu() {
        if (!dropdown || !dropdown.classList.contains('open')) return;
        dropdown.classList.remove('open');
        menuToggle.setAttribute('aria-expanded', 'false');
    }

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

    /* ---- Suspend / Activate ---- */
    var statusBtn = document.getElementById('udStatusBtn');
    if (statusBtn) {
        statusBtn.addEventListener('click', function () {
            var nextStatus = statusBtn.getAttribute('data-next-status');
            var suspending = nextStatus === 'suspended';

            AdminAlert.confirm({
                title: suspending ? 'Suspend account?' : 'Activate account?',
                text: 'Are you sure you want to ' + (suspending ? 'suspend ' : 'activate ') + userName + '?',
                confirmText: suspending ? 'Suspend' : 'Activate',
                danger: suspending
            }).then(function (ok) {
                if (!ok) return;
                statusBtn.disabled = true;
                postAjax('update_status', { user_id: userId, status: nextStatus }).then(function (result) {
                    if (!result.ok) {
                        statusBtn.disabled = false;
                        AdminAlert.error(result.json.error);
                        return;
                    }
                    window.location.reload();
                }).catch(function () {
                    statusBtn.disabled = false;
                    networkError();
                });
            });
        });
    }

    /* ---- Force Logout ---- */
    var forceBtn = document.getElementById('udForceLogout');
    if (forceBtn) {
        forceBtn.addEventListener('click', function () {
            AdminAlert.confirm({
                title: 'Force logout?',
                text: 'Sign ' + userName + ' out of all devices?',
                confirmText: 'Force Logout'
            }).then(function (ok) {
                if (!ok) return;
                postAjax('force_logout', { user_id: userId }).then(function (result) {
                    if (!result.ok) { AdminAlert.error(result.json.error); return; }
                    AdminAlert.success('Logged out', userName + ' has been signed out of all devices.');
                }).catch(networkError);
            });
        });
    }

    /* ---- Delete User ---- */
    var deleteBtn = document.getElementById('udDeleteUser');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
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
                        window.location.href = usersUrl;
                    });
                }).catch(networkError);
            });
        });
    }
})();