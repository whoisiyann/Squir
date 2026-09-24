(function () {
    function $(selector, scope) { return (scope || document).querySelector(selector); }
    function $all(selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); }

    var csrfToken = window.VAULT_CSRF_TOKEN || '';

    // Post a settings ajax action and resolve with the parsed JSON
    function postAjax(action, fields) {
        var body = new URLSearchParams();
        body.set('ajax', action);
        body.set('csrf_token', csrfToken);
        Object.keys(fields || {}).forEach(function (key) { body.set(key, fields[key]); });

        return fetch('./settings', {
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

    /* ---------------- Generic modal open/close ---------------- */
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

    $all('.vault-modal-backdrop').forEach(function (backdrop) {
        backdrop.addEventListener('click', function (event) {
            if (event.target === backdrop) closeModal(backdrop.id.replace('ModalBackdrop', ''));
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        // Kapag bukas ang Delete Account modal, siya lang ang isasara (hindi pati Edit Account modal)
        var deleteModal = document.getElementById('deleteAccountModalBackdrop');
        if (deleteModal && deleteModal.classList.contains('open')) return;
        $all('.vault-modal-backdrop.open').forEach(function (backdrop) {
            backdrop.classList.remove('open');
        });
    });

    var openEditBtn = document.getElementById('openEditAccountModal');
    if (openEditBtn) openEditBtn.addEventListener('click', function () { openModal('editAccount'); });

    /* ---------------- Delete Account (sariling modal, para lang sa button na ito) ---------------- */
    var deleteAccountBtn = document.getElementById('deleteAccountBtn');
    var deleteBackdrop = document.getElementById('deleteAccountModalBackdrop');
    var deleteConfirmBtn = document.getElementById('deleteAccountConfirm');
    var deleteCancelBtn = document.getElementById('deleteAccountCancel');
    var deleteErrorEl = document.getElementById('deleteAccountError');
    var deleteInProgress = false;

    function openDeleteAccountModal() {
        deleteErrorEl.textContent = '';
        deleteConfirmBtn.disabled = false;
        deleteCancelBtn.disabled = false;
        deleteConfirmBtn.textContent = 'Delete';
        deleteBackdrop.classList.add('open');
        deleteBackdrop.setAttribute('aria-hidden', 'false');
        deleteCancelBtn.focus();
    }

    function closeDeleteAccountModal() {
        if (deleteInProgress) return;
        deleteBackdrop.classList.remove('open');
        deleteBackdrop.setAttribute('aria-hidden', 'true');
    }

    if (deleteAccountBtn) {
        deleteAccountBtn.addEventListener('click', function () {
            if (deleteBackdrop && deleteConfirmBtn && deleteCancelBtn && deleteErrorEl) {
                openDeleteAccountModal();
            } else if (window.confirm('Are you sure you want to delete your account? This will permanently remove your vault, notes, tasks, and all related data. This cannot be undone.')) {
                // fallback lang kung wala ang modal markup
                postAjax('delete_account', {}).then(function (result) {
                    if (result.ok) window.location.href = './login';
                });
            }
        });
    }

    if (deleteBackdrop) {
        deleteCancelBtn.addEventListener('click', closeDeleteAccountModal);
        deleteConfirmBtn.addEventListener('click', doDeleteAccount);

        deleteBackdrop.addEventListener('click', function (event) {
            if (event.target === deleteBackdrop) closeDeleteAccountModal();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && deleteBackdrop.classList.contains('open')) closeDeleteAccountModal();
        });
    }

    function doDeleteAccount() {
        if (deleteInProgress) return;
        deleteInProgress = true;
        deleteErrorEl.textContent = '';
        deleteConfirmBtn.disabled = true;
        deleteCancelBtn.disabled = true;
        deleteConfirmBtn.textContent = 'Deleting...';

        function fail(message) {
            deleteInProgress = false;
            deleteConfirmBtn.disabled = false;
            deleteCancelBtn.disabled = false;
            deleteConfirmBtn.textContent = 'Delete';
            deleteErrorEl.textContent = message || 'Something went wrong. Please try again.';
        }

        postAjax('delete_account', {}).then(function (result) {
            if (!result.ok) {
                fail(result.json && result.json.error);
                return;
            }
            window.location.href = './login';
        }).catch(function () {
            fail('Something went wrong. Please try again.');
        });
    }

    /* ---------------- Edit Account form ---------------- */
    function makeEditable(target) {
        if (!target || !target.hasAttribute('readonly')) return;
        target.removeAttribute('readonly');
        target.focus();
        target.select();
    }

    $all('.settings-field-edit-link').forEach(function (link) {
        link.addEventListener('click', function () {
            makeEditable(document.getElementById(link.getAttribute('data-edit-target')));
        });
    });

    $all('.settings-editable-field input').forEach(function (input) {
        input.addEventListener('focus', function () { makeEditable(input); });
        input.addEventListener('click', function () { makeEditable(input); });
    });

    var editAccountForm = document.getElementById('editAccountForm');
    if (editAccountForm) {
        editAccountForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var errorEl = document.getElementById('editAccountError');
            var submitBtn = document.getElementById('editAccountSubmit');
            errorEl.textContent = '';
            submitBtn.disabled = true;

            postAjax('update_profile', {
                full_name: document.getElementById('editFullName').value.trim(),
                username: document.getElementById('editUsername').value.trim(),
                email: document.getElementById('editEmail').value.trim()
            }).then(function (result) {
                submitBtn.disabled = false;
                if (!result.ok) {
                    errorEl.textContent = result.json.error || 'Could not save your changes.';
                    return;
                }

                var data = result.json;
                document.getElementById('settingsFullNameDisplay').textContent = data.full_name;
                document.getElementById('settingsFullNameField').value = data.full_name;
                document.getElementById('settingsUsernameField').value = data.username;
                document.getElementById('settingsEmailField').value = data.email;
                var avatar = document.getElementById('settingsAvatar');
                if (avatar) avatar.textContent = data.initials;

                closeModal('editAccount');
            }).catch(function () {
                submitBtn.disabled = false;
                errorEl.textContent = 'Something went wrong. Please try again.';
            });
        });
    }

    /* ---------------- Appearance ---------------- */
    var themeButtons = $all('.settings-theme-btn');
    if (themeButtons.length) {
        function highlightTheme(pref) {
            themeButtons.forEach(function (btn) {
                btn.classList.toggle('active', btn.getAttribute('data-theme') === pref);
            });
        }

        var currentPref = (window.SquirTheme && window.SquirTheme.get()) || 'light';
        highlightTheme(currentPref);

        themeButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var pref = btn.getAttribute('data-theme');
                if (window.SquirTheme) window.SquirTheme.set(pref);
                highlightTheme(pref);
            });
        });
    }

    /* ---------------- Show / hide password & PIN fields ---------------- */
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

    /* ---------------- Change Password ---------------- */
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
            document.getElementById('changePasswordFormError').textContent = '';

            var submitBtn = document.getElementById('changePasswordSubmit');
            submitBtn.disabled = true;

            postAjax('change_password', {
                current_password: document.getElementById('currentPassword').value,
                new_password: newPasswordInput.value,
                confirm_password: document.getElementById('confirmPassword').value
            }).then(function (result) {
                submitBtn.disabled = false;

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

                if (window.Swal) {
                    Swal.fire({
                        title: 'Password Updated!',
                        text: 'Your Password has been changed successfully. For your security, please use your new password the next time you log in.',
                        icon: 'success',
                        confirmButtonText: 'Done',
                        confirmButtonColor: '#6b3f2a'
                    }).then(function () { window.location.href = './settings'; });
                } else {
                    window.location.href = './settings';
                }
            }).catch(function () {
                submitBtn.disabled = false;
                document.getElementById('changePasswordFormError').textContent = 'Something went wrong. Please try again.';
            });
        });
    }

    /* ---------------- Reset PIN ---------------- */
    var pinForm = document.getElementById('resetPinForm');
    if (pinForm) {
        $all('#resetPinForm input[inputmode="numeric"]').forEach(function (input) {
            input.addEventListener('input', function () {
                input.value = input.value.replace(/\D/g, '').slice(0, input.getAttribute('maxlength'));
            });
        });

        pinForm.addEventListener('submit', function (event) {
            event.preventDefault();

            $all('.settings-form-error', pinForm).forEach(function (el) { el.textContent = ''; });
            document.getElementById('resetPinFormError').textContent = '';

            var submitBtn = document.getElementById('resetPinSubmit');
            submitBtn.disabled = true;

            postAjax('reset_pin', {
                current_pin: document.getElementById('currentPin').value,
                new_pin: document.getElementById('newPin').value,
                confirm_pin: document.getElementById('confirmPin').value
            }).then(function (result) {
                submitBtn.disabled = false;

                if (!result.ok) {
                    var errors = result.json.errors || {};
                    Object.keys(errors).forEach(function (field) {
                        var el = pinForm.querySelector('[data-error-for="' + field + '"]');
                        if (el) el.textContent = errors[field];
                    });
                    if (Object.keys(errors).length === 0) {
                        document.getElementById('resetPinFormError').textContent = result.json.error || 'Could not update your PIN.';
                    }
                    return;
                }

                pinForm.reset();

                if (window.Swal) {
                    Swal.fire({
                        title: 'PIN Updated!',
                        text: 'Your PIN has been successfully updated.',
                        icon: 'success',
                        confirmButtonText: 'Done',
                        confirmButtonColor: '#6b3f2a'
                    }).then(function () { window.location.href = './settings'; });
                } else {
                    window.location.href = './settings';
                }
            }).catch(function () {
                submitBtn.disabled = false;
                document.getElementById('resetPinFormError').textContent = 'Something went wrong. Please try again.';
            });
        });
    }

    /* ---------------- Clear Activity Log ---------------- */
    var clearBtn = document.getElementById('clearActivityLogBtn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (clearBtn.disabled) return;

            if (window.SquirDialogs) {
                window.SquirDialogs.confirmDelete({
                    message: 'Are you sure you want to clear your entire activity log? This cannot be undone.',
                    onConfirm: function () { return doClearActivityLog(); }
                });
            } else if (window.confirm('Clear your entire activity log? This cannot be undone.')) {
                doClearActivityLog();
            }
        });
    }

    function doClearActivityLog() {
        return postAjax('clear_activity_log', {}).then(function (result) {
            if (!result.ok) return;

            var list = document.getElementById('settingsActivityList');
            if (list) list.innerHTML = '';

            var card = document.querySelector('.settings-activity-card');
            if (card && !document.getElementById('settingsActivityEmpty')) {
                var empty = document.createElement('div');
                empty.className = 'empty-state';
                empty.id = 'settingsActivityEmpty';
                empty.innerHTML = '<i class="ti ti-history"></i><p>No activity yet</p><small>Actions like password or PIN changes will show up here.</small>';
                card.appendChild(empty);
            }

            clearBtn.disabled = true;
        });
    }
})();