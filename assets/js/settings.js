(function () {
    function $(selector, scope) { return (scope || document).querySelector(selector); }
    function $all(selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); }

    var csrfToken = window.VAULT_CSRF_TOKEN || '';

    // Settings request
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

    /* Modal controls */
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
        // Keep modal open
        var deleteModal = document.getElementById('deleteAccountModalBackdrop');
        if (deleteModal && deleteModal.classList.contains('open')) return;
        $all('.vault-modal-backdrop.open').forEach(function (backdrop) {
            backdrop.classList.remove('open');
        });
    });

    var openEditBtn = document.getElementById('openEditAccountModal');
    if (openEditBtn) {
        openEditBtn.addEventListener('click', function () {
            resetEditAccountModal();
            openModal('editAccount');
        });
    }

    /* Delete account */
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
                // Fallback to browser prompt
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

    /* Edit account */
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

    /* Email verification */
    var editAccountFormEl = document.getElementById('editAccountForm');
    var emailCodePanel = document.getElementById('emailChangeCodePanel');
    var emailCodeBoxes = $all('#emailChangeCodeInputs .pin-modal-box');
    var emailCodeError = document.getElementById('emailChangeCodeError');
    var emailChangeDevCodeEl = document.getElementById('emailChangeDevCode');
    var emailChangeVerifyBtn = document.getElementById('emailChangeVerifyBtn');
    var emailChangeResendBtn = document.getElementById('emailChangeResendBtn');
    var emailChangeCancelBtn = document.getElementById('emailChangeCancelBtn');
    var pendingNewEmail = '';
    var emailCooldownTimer = null;

    // Count down before the code can be sent again
    function startEmailCooldown(seconds) {
        if (!emailChangeResendBtn) return;
        clearInterval(emailCooldownTimer);
        var left = Math.max(0, parseInt(seconds, 10) || 0);

        function paint() {
            if (left > 0) {
                emailChangeResendBtn.disabled = true;
                emailChangeResendBtn.textContent = 'Resend in ' + left + 's';
            } else {
                clearInterval(emailCooldownTimer);
                emailChangeResendBtn.disabled = false;
                emailChangeResendBtn.textContent = 'Resend';
            }
        }

        paint();
        if (left > 0) {
            emailCooldownTimer = setInterval(function () { left -= 1; paint(); }, 1000);
        }
    }

    // Show email verification step
    function showEmailCodeStep(newEmail, devCode, retryAfter) {
        pendingNewEmail = newEmail;
        document.getElementById('emailChangeTargetEmail').textContent = newEmail;

        if (devCode) {
            emailChangeDevCodeEl.hidden = false;
            emailChangeDevCodeEl.textContent = "Dev mode — email isn't configured yet, so here's your code: " + devCode;
        } else {
            emailChangeDevCodeEl.hidden = true;
            emailChangeDevCodeEl.textContent = '';
        }

        emailCodeError.textContent = '';
        emailCodeBoxes.forEach(function (box) { box.value = ''; box.classList.remove('filled'); });

        editAccountFormEl.hidden = true;
        emailCodePanel.hidden = false;
        startEmailCooldown(retryAfter);
        if (emailCodeBoxes[0]) emailCodeBoxes[0].focus();
    }

    // Return to account form
    function resetEditAccountModal() {
        pendingNewEmail = '';
        clearInterval(emailCooldownTimer);
        if (emailChangeResendBtn) {
            emailChangeResendBtn.disabled = false;
            emailChangeResendBtn.textContent = 'Resend';
        }
        if (editAccountFormEl) editAccountFormEl.hidden = false;
        if (emailCodePanel) emailCodePanel.hidden = true;
        if (emailCodeError) emailCodeError.textContent = '';
        if (emailChangeDevCodeEl) { emailChangeDevCodeEl.hidden = true; emailChangeDevCodeEl.textContent = ''; }
        emailCodeBoxes.forEach(function (box) { box.value = ''; box.classList.remove('filled'); });
        var errorEl = document.getElementById('editAccountError');
        if (errorEl) errorEl.textContent = '';
        var submitBtn = document.getElementById('editAccountSubmit');
        if (submitBtn) submitBtn.disabled = false;
        var emailInput = document.getElementById('editEmail');
        var originalEmailInput = document.getElementById('editEmailOriginal');
        if (emailInput && originalEmailInput) emailInput.value = originalEmailInput.value;
    }

    function currentEmailCode() {
        return emailCodeBoxes.map(function (box) { return box.value; }).join('');
    }

    emailCodeBoxes.forEach(function (box, index) {
        box.addEventListener('input', function () {
            box.value = box.value.replace(/\D/g, '').slice(0, 1);
            box.classList.toggle('filled', box.value !== '');
            if (box.value !== '' && index < emailCodeBoxes.length - 1) {
                emailCodeBoxes[index + 1].focus();
            }
            emailCodeError.textContent = '';
        });

        box.addEventListener('keydown', function (event) {
            if (event.key === 'Backspace' && box.value === '' && index > 0) {
                event.preventDefault();
                emailCodeBoxes[index - 1].value = '';
                emailCodeBoxes[index - 1].classList.remove('filled');
                emailCodeBoxes[index - 1].focus();
            }
        });

        box.addEventListener('paste', function (event) {
            event.preventDefault();
            var digits = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            emailCodeBoxes.forEach(function (b, i) {
                b.value = digits[i] || '';
                b.classList.toggle('filled', b.value !== '');
            });
            emailCodeBoxes[Math.min(digits.length, emailCodeBoxes.length - 1)].focus();
        });

        box.addEventListener('focus', function () { box.select(); });
    });

    if (emailChangeVerifyBtn) {
        emailChangeVerifyBtn.addEventListener('click', function () {
            var code = currentEmailCode();
            if (code.length !== emailCodeBoxes.length) {
                emailCodeError.textContent = 'Enter all ' + emailCodeBoxes.length + ' digits.';
                return;
            }

            emailChangeVerifyBtn.disabled = true;
            postAjax('verify_email_change', { code: code }).then(function (result) {
                emailChangeVerifyBtn.disabled = false;

                if (!result.ok) {
                    emailCodeError.textContent = result.json.error || 'That code is incorrect or has expired.';
                    return;
                }

                var newEmail = result.json.email;
                document.getElementById('settingsEmailField').value = newEmail;
                document.getElementById('editEmail').value = newEmail;
                document.getElementById('editEmailOriginal').value = newEmail;

                resetEditAccountModal();
                closeModal('editAccount');

                if (window.Swal) {
                    Swal.fire({
                        title: 'Email Updated!',
                        text: 'Your login email has been changed to ' + newEmail + '.',
                        icon: 'success',
                        confirmButtonText: 'Done',
                        confirmButtonColor: '#6b3f2a'
                    });
                }
            }).catch(function () {
                emailChangeVerifyBtn.disabled = false;
                emailCodeError.textContent = 'Something went wrong. Please try again.';
            });
        });
    }

    if (emailChangeResendBtn) {
        emailChangeResendBtn.addEventListener('click', function () {
            if (emailChangeResendBtn.disabled) return;
            emailChangeResendBtn.disabled = true;
            emailCodeError.textContent = '';

            postAjax('resend_email_change', {}).then(function (result) {
                if (!result.ok) {
                    emailCodeError.textContent = result.json.error || 'Could not resend the code.';
                    // A code went out a moment ago: keep the timer honest.
                    startEmailCooldown(result.json.retry_after || 0);
                    return;
                }

                emailCodeBoxes.forEach(function (box) { box.value = ''; box.classList.remove('filled'); });
                if (result.json.dev_code) {
                    emailChangeDevCodeEl.hidden = false;
                    emailChangeDevCodeEl.textContent = "Dev mode \u2014 email isn't configured yet, so here's your code: " + result.json.dev_code;
                }

                startEmailCooldown(result.json.retry_after);
                if (emailCodeBoxes[0]) emailCodeBoxes[0].focus();
            }).catch(function () {
                emailCodeError.textContent = 'Something went wrong. Please try again.';
                startEmailCooldown(0);
            });
        });
    }

    if (emailChangeCancelBtn) {
        emailChangeCancelBtn.addEventListener('click', function () { resetEditAccountModal(); });
    }

    /* Edit account */
    if (editAccountFormEl) {
        editAccountFormEl.addEventListener('submit', function (event) {
            event.preventDefault();
            var errorEl = document.getElementById('editAccountError');
            var submitBtn = document.getElementById('editAccountSubmit');
            errorEl.textContent = '';
            submitBtn.disabled = true;

            var newEmail = document.getElementById('editEmail').value.trim();
            var originalEmail = document.getElementById('editEmailOriginal').value.trim();
            var emailChanged = newEmail !== '' && newEmail.toLowerCase() !== originalEmail.toLowerCase();

            postAjax('update_profile', {
                full_name: document.getElementById('editFullName').value.trim(),
                username: document.getElementById('editUsername').value.trim()
            }).then(function (result) {
                if (!result.ok) {
                    submitBtn.disabled = false;
                    errorEl.textContent = result.json.error || 'Could not save your changes.';
                    return;
                }

                var data = result.json;
                document.getElementById('settingsFullNameDisplay').textContent = data.full_name;
                document.getElementById('settingsFullNameField').value = data.full_name;
                document.getElementById('settingsUsernameField').value = data.username;
                var avatar = document.getElementById('settingsAvatar');
                if (avatar) avatar.textContent = data.initials;

                if (!emailChanged) {
                    submitBtn.disabled = false;
                    closeModal('editAccount');
                    return;
                }

                // Start email verification.
                postAjax('request_email_change', { new_email: newEmail }).then(function (emailResult) {
                    submitBtn.disabled = false;

                    if (!emailResult.ok) {
                        errorEl.textContent = emailResult.json.error || 'Could not send a verification code.';
                        return;
                    }

                    showEmailCodeStep(emailResult.json.new_email, emailResult.json.dev_code, emailResult.json.retry_after);
                }).catch(function () {
                    submitBtn.disabled = false;
                    errorEl.textContent = 'Something went wrong. Please try again.';
                });
            }).catch(function () {
                submitBtn.disabled = false;
                errorEl.textContent = 'Something went wrong. Please try again.';
            });
        });
    }

    /* Appearance */
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

    /* Password and PIN visibility */
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

    /* Reset PIN */
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

    /* Export data */
    var exportBtn = document.getElementById('exportDataBtn');
    if (exportBtn) {
        var exportBusy = false;
        var exportIdleHtml = exportBtn.innerHTML;

        function setExportBusy(state) {
            exportBusy = state;
            exportBtn.disabled = state;
            exportBtn.innerHTML = state ? '<i class="ti ti-loader-2"></i> Preparing...' : exportIdleHtml;
        }

        function exportFail(message) {
            var error = new Error(message);
            error.friendly = true;
            throw error;
        }

        function showExportError(message) {
            if (window.Swal) {
                Swal.fire({
                    title: 'Export failed',
                    text: message,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#6b3f2a'
                });
            } else {
                window.alert(message);
            }
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

        // Download the PDF after PIN verification
        function downloadExport() {
            setExportBusy(true);

            return fetch('./settings?export=pdf', { credentials: 'same-origin' }).then(function (response) {
                var type = response.headers.get('Content-Type') || '';

                if (!response.ok || type.indexOf('application/pdf') === -1) {
                    return response.json().catch(function () { return {}; }).then(function (json) {
                        if (json.error === 'pin_required') {
                            exportFail('Your PIN check expired. Please try again.');
                        }
                        exportFail(json.error || 'Could not create your PDF. Please try again.');
                    });
                }

                var disposition = response.headers.get('Content-Disposition') || '';
                var match = /filename="?([^";]+)"?/i.exec(disposition);
                var filename = match ? match[1] : 'squir-export.pdf';

                return response.blob().then(function (blob) {
                    saveBlob(blob, filename);

                    if (window.Swal) {
                        Swal.fire({
                            title: 'Export Complete',
                            text: 'Your data was downloaded as a PDF.',
                            icon: 'success',
                            confirmButtonText: 'Done',
                            confirmButtonColor: '#6b3f2a'
                        });
                    }
                });
            }).catch(function (error) {
                showExportError(error && error.friendly ? error.message : 'Something went wrong while creating your PDF. Please try again.');
            }).then(function () {
                setExportBusy(false);
            });
        }

        exportBtn.addEventListener('click', function () {
            if (exportBusy) return;

            if (!window.SquirPin) {
                showExportError('The PIN check could not load. Please refresh the page and try again.');
                return;
            }

            window.SquirPin.ensure('export').then(downloadExport);
        });
    }

    /* Clear activity log */
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
            if (list) {
                list.innerHTML = '';
                list.classList.add('is-empty');
            }

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