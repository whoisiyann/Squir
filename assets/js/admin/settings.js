// Admin settings.
(function () {
    var root = document.getElementById('asRoot');
    if (!root) return;

    function $(selector, scope) { return (scope || document).querySelector(selector); }
    function $all(selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); }

    var endpoint = root.getAttribute('data-endpoint');
    var csrfToken = window.ADMIN_CSRF_TOKEN || '';

    // Send a settings request
    function postAjax(action, fields) {
        var body = new URLSearchParams();
        body.set('ajax', action);
        body.set('csrf_token', csrfToken);
        Object.keys(fields || {}).forEach(function (key) { body.set(key, fields[key]); });

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

    function notifySuccess(title, text) {
        if (window.AdminAlert) return AdminAlert.success(title, text);
        window.alert(title + (text ? '\n' + text : ''));
    }

    function notifyError(message) {
        if (window.AdminAlert) return AdminAlert.error(message);
        window.alert(message);
    }

    /* Appearance */
    var themeButtons = $all('.settings-theme-btn');
    if (themeButtons.length && window.SquirAdminTheme) {
        var highlightTheme = function (pref) {
            themeButtons.forEach(function (btn) {
                btn.classList.toggle('active', btn.getAttribute('data-theme') === pref);
            });
        };

        highlightTheme(window.SquirAdminTheme.get());

        themeButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                window.SquirAdminTheme.set(btn.getAttribute('data-theme'));
            });
        });

        // Keep in sync with the top bar toggle.
        document.addEventListener('squir-admin-theme', function (event) {
            highlightTheme(event.detail);
        });
    }

    /* Edit profile */
    var editBackdrop = document.getElementById('asEditModalBackdrop');
    var editForm = document.getElementById('asEditForm');

    if (editBackdrop && editForm) {
        var fullNameInput = document.getElementById('asEditFullName');
        var usernameInput = document.getElementById('asEditUsername');
        var emailInput = document.getElementById('asEditEmail');
        var passwordField = document.getElementById('asPasswordField');
        var passwordInput = document.getElementById('asEditPassword');
        var errorEl = document.getElementById('asEditError');
        var submitBtn = document.getElementById('asEditSubmit');

        var saved = {
            full_name: fullNameInput.value,
            username: usernameInput.value,
            email: emailInput.value
        };

        function emailChanged() {
            return emailInput.value.trim().toLowerCase() !== saved.email.toLowerCase();
        }

        function syncPasswordField() {
            var show = emailChanged();
            passwordField.hidden = !show;
            if (!show) passwordInput.value = '';
        }

        function resetEditForm() {
            fullNameInput.value = saved.full_name;
            usernameInput.value = saved.username;
            emailInput.value = saved.email;
            [fullNameInput, usernameInput, emailInput].forEach(function (input) {
                input.setAttribute('readonly', 'readonly');
            });
            passwordInput.value = '';
            passwordField.hidden = true;
            errorEl.textContent = '';
            submitBtn.disabled = false;
        }

        function openEdit() {
            resetEditForm();
            editBackdrop.classList.add('open');
        }

        function closeEdit() {
            editBackdrop.classList.remove('open');
        }

        function makeEditable(target) {
            if (!target || !target.hasAttribute('readonly')) return;
            target.removeAttribute('readonly');
            target.focus();
            target.select();
        }

        $('#asOpenEdit').addEventListener('click', openEdit);

        $all('[data-close-modal]', editBackdrop).forEach(function (btn) {
            btn.addEventListener('click', closeEdit);
        });
        editBackdrop.addEventListener('click', function (event) {
            if (event.target === editBackdrop) closeEdit();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && editBackdrop.classList.contains('open')) closeEdit();
        });

        $all('.settings-field-edit-link', editBackdrop).forEach(function (link) {
            link.addEventListener('click', function () {
                makeEditable(document.getElementById(link.getAttribute('data-edit-target')));
            });
        });
        [fullNameInput, usernameInput, emailInput].forEach(function (input) {
            input.addEventListener('focus', function () { makeEditable(input); });
            input.addEventListener('click', function () { makeEditable(input); });
        });
        emailInput.addEventListener('input', syncPasswordField);

        // Refresh the top bar after saving.
        function updateTopbar(data) {
            var name = $('.topbar .profile-name');
            var avatar = $('.topbar .profile .avatar');
            var dropdownName = $('.topbar .profile-dropdown-head strong');
            var dropdownEmail = $('.topbar .profile-dropdown-head small');
            if (name) name.textContent = data.full_name;
            if (avatar) avatar.textContent = data.initials;
            if (dropdownName) dropdownName.textContent = data.full_name;
            if (dropdownEmail) dropdownEmail.textContent = data.email;
        }

        editForm.addEventListener('submit', function (event) {
            event.preventDefault();
            errorEl.textContent = '';
            submitBtn.disabled = true;

            postAjax('update_profile', {
                full_name: fullNameInput.value.trim(),
                username: usernameInput.value.trim(),
                email: emailInput.value.trim(),
                current_password: passwordInput.value
            }).then(function (result) {
                submitBtn.disabled = false;

                if (!result.ok) {
                    errorEl.textContent = result.json.error || 'Could not save your changes.';
                    return;
                }

                var data = result.json;
                saved = { full_name: data.full_name, username: data.username, email: data.email };

                $('#asNameDisplay').textContent = data.full_name;
                $('#asFullNameField').value = data.full_name;
                $('#asUsernameField').value = data.username;
                $('#asEmailField').value = data.email;
                $('#asAvatar').textContent = data.initials;
                updateTopbar(data);

                closeEdit();
                notifySuccess('Profile Updated!', 'Your account information has been saved.');
            }).catch(function () {
                submitBtn.disabled = false;
                errorEl.textContent = 'Something went wrong. Please try again.';
            });
        });
    }

    /* Password visibility */
    $all('.settings-password-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-target'));
            if (!input) return;
            var icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) icon.className = 'ti ti-eye-off';
            } else {
                input.type = 'password';
                if (icon) icon.className = 'ti ti-eye';
            }
        });
    });

    /* Change password */
    var passwordForm = document.getElementById('changePasswordForm');
    if (passwordForm) {
        var newPasswordInput = document.getElementById('newPassword');
        var checklist = document.getElementById('passwordChecklist');

        function evaluatePassword(value) {
            var rules = {
                length: value.length >= 8,
                number: /\d/.test(value),
                special: /[^A-Za-z0-9]/.test(value)
            };
            Object.keys(rules).forEach(function (rule) {
                var item = checklist.querySelector('[data-rule="' + rule + '"]');
                if (item) item.classList.toggle('is-valid', rules[rule]);
            });
            return rules.length && rules.number && rules.special;
        }

        newPasswordInput.addEventListener('input', function () { evaluatePassword(newPasswordInput.value); });

        passwordForm.addEventListener('submit', function (event) {
            event.preventDefault();

            $all('.settings-form-error', passwordForm).forEach(function (el) { el.textContent = ''; });

            var submit = document.getElementById('changePasswordSubmit');
            submit.disabled = true;

            postAjax('change_password', {
                current_password: document.getElementById('currentPassword').value,
                new_password: newPasswordInput.value,
                confirm_password: document.getElementById('confirmPassword').value
            }).then(function (result) {
                submit.disabled = false;

                if (!result.ok) {
                    var errors = result.json.errors || {};
                    Object.keys(errors).forEach(function (field) {
                        var el = passwordForm.querySelector('[data-error-for="' + field + '"]');
                        if (el) el.textContent = errors[field];
                    });
                    if (Object.keys(errors).length === 0) {
                        document.getElementById('changePasswordFormError').textContent = result.json.error || 'Could not update your password.';
                    }
                    return;
                }

                passwordForm.reset();
                evaluatePassword('');

                var done = function () { window.location.href = endpoint; };
                if (window.Swal) {
                    Swal.fire({
                        title: 'Password Updated!',
                        text: 'Your password has been changed successfully. Please use your new password the next time you log in.',
                        icon: 'success',
                        confirmButtonText: 'Done',
                        confirmButtonColor: '#6b3f2a'
                    }).then(done);
                } else {
                    done();
                }
            }).catch(function () {
                submit.disabled = false;
                document.getElementById('changePasswordFormError').textContent = 'Something went wrong. Please try again.';
            });
        });
    }

    /* Clear activity log */
    var clearBtn = document.getElementById('asClearActivity');
    if (clearBtn) {
        var clearBusy = false;

        function showEmptyActivity() {
            var list = document.getElementById('settingsActivityList');
            if (list) list.remove();

            var card = document.getElementById('asActivityCard');
            if (card && !document.getElementById('asActivityEmpty')) {
                var empty = document.createElement('div');
                empty.className = 'empty-state';
                empty.id = 'asActivityEmpty';
                empty.innerHTML = '<i class="ti ti-history"></i><p>No activity yet</p><small>Your admin actions will show up here.</small>';
                card.appendChild(empty);
            }

            clearBtn.disabled = true;
        }

        function doClearActivity() {
            clearBusy = true;
            clearBtn.disabled = true;

            return postAjax('clear_activity_log', {}).then(function (result) {
                clearBusy = false;

                if (!result.ok) {
                    clearBtn.disabled = false;
                    return notifyError(result.json.error || 'Could not clear your activity log.');
                }

                showEmptyActivity();
                return notifySuccess('Activity log cleared', 'Your activity log is now empty.');
            }).catch(function () {
                clearBusy = false;
                clearBtn.disabled = false;
                notifyError('Something went wrong. Please try again.');
            });
        }

        clearBtn.addEventListener('click', function () {
            if (clearBusy || clearBtn.disabled) return;

            if (!window.AdminAlert) {
                if (window.confirm('Clear your entire activity log? This cannot be undone.')) doClearActivity();
                return;
            }

            AdminAlert.confirm({
                title: 'Clear activity log?',
                text: 'Are you sure you want to clear your entire activity log? This cannot be undone.',
                confirmText: 'Yes, clear it',
                danger: true
            }).then(function (confirmed) {
                if (confirmed) doClearActivity();
            });
        });
    }

    /* Export logs */
    var exportBtn = document.getElementById('asExportBtn');
    var userOnly = document.getElementById('asUserOnly');
    var USER_ONLY_KEY = 'squir-admin-export-user-only';

    if (userOnly) {
        try {
            var storedScope = window.localStorage.getItem(USER_ONLY_KEY);
            if (storedScope !== null) userOnly.checked = storedScope === '1';
        } catch (error) {}

        userOnly.addEventListener('change', function () {
            try { window.localStorage.setItem(USER_ONLY_KEY, userOnly.checked ? '1' : '0'); } catch (error) {}
        });
    }

    if (exportBtn) {
        var exportBusy = false;
        var exportIdleHtml = exportBtn.innerHTML;

        function setExportBusy(state) {
            exportBusy = state;
            exportBtn.disabled = state;
            exportBtn.innerHTML = state ? '<i class="ti ti-loader" aria-hidden="true"></i> Preparing...' : exportIdleHtml;
        }

        function exportFail(message) {
            var error = new Error(message);
            error.friendly = true;
            throw error;
        }

        function saveBlob(blob, filename) {
            var url = URL.createObjectURL(blob);
            var link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
        }

        exportBtn.addEventListener('click', function () {
            if (exportBusy) return;
            setExportBusy(true);

            var scope = userOnly && !userOnly.checked ? 'all' : 'users';

            fetch(endpoint + '?export=csv&scope=' + scope, { credentials: 'same-origin' }).then(function (response) {
                var type = response.headers.get('Content-Type') || '';

                if (!response.ok || type.indexOf('text/csv') === -1) {
                    return response.json().catch(function () { return {}; }).then(function (json) {
                        exportFail(json.error || 'Could not create the export. Please try again.');
                    });
                }

                var disposition = response.headers.get('Content-Disposition') || '';
                var match = /filename="?([^";]+)"?/i.exec(disposition);
                var filename = match ? match[1] : 'squir-logs.csv';

                return response.blob().then(function (blob) {
                    saveBlob(blob, filename);
                    notifySuccess('Export Complete', 'Your logs were downloaded as a CSV file.');
                });
            }).catch(function (error) {
                notifyError(error && error.friendly ? error.message : 'Something went wrong while creating the export. Please try again.');
            }).then(function () {
                setExportBusy(false);
            });
        });
    }
})();