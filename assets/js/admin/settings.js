(function () {
    var root = document.getElementById('asRoot');
    if (!root) return;

    function $(selector, scope) { return (scope || document).querySelector(selector); }
    function $all(selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); }

    var endpoint = root.getAttribute('data-endpoint');
    var csrfToken = window.ADMIN_CSRF_TOKEN || '';

    // Settings request
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

    /* Advanced appearance + accent color */
    var advancedBox = document.getElementById('settingsAdvanced');
    var advancedToggle = document.getElementById('settingsAdvancedToggle');
    var advancedPanel = document.getElementById('settingsAdvancedPanel');

    if (advancedBox && advancedToggle && advancedPanel) {
        var accountCard = document.querySelector('.settings-grid > .settings-card');
        var lockTimer = null;

        // Keep Account Information at its closed height while the dropdown is open
        function lockAccountHeight() {
            if (!accountCard || !window.matchMedia('(min-width: 1001px)').matches) return;
            accountCard.style.height = accountCard.offsetHeight + 'px';
        }

        function unlockAccountHeight() {
            if (accountCard) accountCard.style.height = '';
        }

        advancedToggle.addEventListener('click', function () {
            var willOpen = !advancedBox.classList.contains('open');
            window.clearTimeout(lockTimer);

            if (willOpen) {
                lockAccountHeight();
            } else {
                // Release after the close animation so both cards match again
                lockTimer = window.setTimeout(unlockAccountHeight, 450);
            }

            advancedBox.classList.toggle('open', willOpen);
            advancedToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            advancedPanel.setAttribute('aria-hidden', willOpen ? 'false' : 'true');
        });
        advancedPanel.setAttribute('aria-hidden', 'true');

        window.addEventListener('resize', function () {
            if (!advancedBox.classList.contains('open')) return;
            unlockAccountHeight();
        });
    }

    var accentButtons = $all('#settingsAccentOptions .settings-accent-btn');
    if (accentButtons.length && window.SquirAdminTheme) {
        var highlightAccent = function (name) {
            accentButtons.forEach(function (btn) {
                var isActive = btn.getAttribute('data-accent') === name;
                btn.classList.toggle('active', isActive);
                btn.setAttribute('aria-checked', isActive ? 'true' : 'false');
            });
        };

        highlightAccent(window.SquirAdminTheme.getAccent());

        accentButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var name = btn.getAttribute('data-accent');
                window.SquirAdminTheme.setAccent(name);
                highlightAccent(name);
            });
        });
    }

    /* Edit profile */
    var editBackdrop = document.getElementById('asEditModalBackdrop');
    var editForm = document.getElementById('asEditForm');

    if (editBackdrop && editForm) {
        var fullNameInput = document.getElementById('asEditFullName');
        var usernameInput = document.getElementById('asEditUsername');
        var emailInput = document.getElementById('asEditEmail');
        var errorEl = document.getElementById('asEditError');
        var submitBtn = document.getElementById('asEditSubmit');

        // Email verification step
        var emailPanel = document.getElementById('asEmailCodePanel');
        var emailTarget = document.getElementById('asEmailTarget');
        var emailDevCode = document.getElementById('asEmailDevCode');
        var emailCodeWrap = document.getElementById('asEmailCodeInputs');
        var emailCodeBoxes = $all('.as-code-box', emailCodeWrap);
        var emailCodeError = document.getElementById('asEmailCodeError');
        var emailVerifyBtn = document.getElementById('asEmailVerifyBtn');
        var emailResendBtn = document.getElementById('asEmailResendBtn');
        var emailCancelBtn = document.getElementById('asEmailCancelBtn');
        var emailCooldownTimer = null;

        var saved = {
            full_name: fullNameInput.value,
            username: usernameInput.value,
            email: emailInput.value
        };

        function emailChanged() {
            var value = emailInput.value.trim().toLowerCase();
            return value !== '' && value !== saved.email.toLowerCase();
        }

        function emailCodeValue() {
            return emailCodeBoxes.map(function (box) { return box.value; }).join('');
        }

        function refreshEmailCode() {
            emailCodeBoxes.forEach(function (box) { box.classList.toggle('filled', box.value !== ''); });
            emailVerifyBtn.disabled = emailCodeValue().length !== emailCodeBoxes.length;
        }

        function clearEmailCode() {
            emailCodeBoxes.forEach(function (box) { box.value = ''; });
            refreshEmailCode();
        }

        function shakeEmailCode() {
            emailCodeWrap.classList.remove('shake');
            void emailCodeWrap.offsetWidth;
            emailCodeWrap.classList.add('shake');
        }

        function showEmailDevCode(code) {
            emailDevCode.textContent = '';
            if (!code) {
                emailDevCode.hidden = true;
                return;
            }
            emailDevCode.appendChild(document.createTextNode("Dev mode \u2014 email isn't configured yet, so here's your code: "));
            var strong = document.createElement('strong');
            strong.textContent = code;
            emailDevCode.appendChild(strong);
            emailDevCode.hidden = false;
        }

        // Count down before the code can be sent again
        function startEmailCooldown(seconds) {
            clearInterval(emailCooldownTimer);
            var left = Math.max(0, parseInt(seconds, 10) || 0);

            function paint() {
                if (left > 0) {
                    emailResendBtn.disabled = true;
                    emailResendBtn.textContent = 'Resend in ' + left + 's';
                } else {
                    clearInterval(emailCooldownTimer);
                    emailResendBtn.disabled = false;
                    emailResendBtn.textContent = 'Resend';
                }
            }

            paint();
            if (left > 0) {
                emailCooldownTimer = setInterval(function () { left -= 1; paint(); }, 1000);
            }
        }

        // Show the "enter the code" step
        function showEmailCodeStep(newEmail, devCode, retryAfter) {
            emailTarget.textContent = newEmail;
            showEmailDevCode(devCode);
            emailCodeError.textContent = '';
            clearEmailCode();

            editForm.hidden = true;
            emailPanel.hidden = false;
            startEmailCooldown(retryAfter);
            emailCodeBoxes[0].focus();
        }

        // Back to the account form
        function resetEditForm() {
            clearInterval(emailCooldownTimer);
            clearEmailCode();
            showEmailDevCode('');
            emailCodeError.textContent = '';
            emailResendBtn.disabled = false;
            emailResendBtn.textContent = 'Resend';
            emailPanel.hidden = true;
            editForm.hidden = false;

            fullNameInput.value = saved.full_name;
            usernameInput.value = saved.username;
            emailInput.value = saved.email;
            [fullNameInput, usernameInput, emailInput].forEach(function (input) {
                input.setAttribute('readonly', 'readonly');
            });
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

        // Refresh top bar
        function updateTopbar(data) {
            var name = $('.topbar .profile-name');
            var avatar = $('.topbar .profile .avatar');
            var dropdownName = $('.topbar .profile-dropdown-head strong');
            if (name) name.textContent = data.full_name;
            if (avatar) avatar.textContent = data.initials;
            if (dropdownName) dropdownName.textContent = data.full_name;
        }

        function updateTopbarEmail(email) {
            var dropdownEmail = $('.topbar .profile-dropdown-head small');
            if (dropdownEmail) dropdownEmail.textContent = email;
        }

        // Save profile
        editForm.addEventListener('submit', function (event) {
            event.preventDefault();
            errorEl.textContent = '';

            var fullName = fullNameInput.value.trim();
            var username = usernameInput.value.trim();
            var newEmail = emailInput.value.trim();

            var profileChanged = fullName !== saved.full_name || username !== saved.username;
            var wantsEmailChange = emailChanged();

            // Nothing was edited: just close the modal, no request and no notification.
            if (!profileChanged && !wantsEmailChange) {
                closeEdit();
                return;
            }

            submitBtn.disabled = true;

            // Only save name/username when one of them changed.
            var saveProfile = profileChanged
                ? postAjax('update_profile', { full_name: fullName, username: username })
                : Promise.resolve(null);

            saveProfile.then(function (result) {
                if (result) {
                    if (!result.ok) {
                        submitBtn.disabled = false;
                        errorEl.textContent = result.json.error || 'Could not save your changes.';
                        return;
                    }

                    var data = result.json;
                    saved.full_name = data.full_name;
                    saved.username = data.username;

                    $('#asNameDisplay').textContent = data.full_name;
                    $('#asFullNameField').value = data.full_name;
                    $('#asUsernameField').value = data.username;
                    $('#asAvatar').textContent = data.initials;
                    updateTopbar(data);
                }

                if (!wantsEmailChange) {
                    submitBtn.disabled = false;
                    closeEdit();
                    notifySuccess('Profile Updated!', 'Your account information has been saved.');
                    return;
                }

                // Start email verification.
                return postAjax('request_email_change', { new_email: newEmail }).then(function (emailResult) {
                    submitBtn.disabled = false;

                    if (!emailResult.ok) {
                        errorEl.textContent = emailResult.json.error || 'Could not send a verification code.';
                        return;
                    }

                    showEmailCodeStep(emailResult.json.new_email, emailResult.json.dev_code, emailResult.json.retry_after);
                });
            }).catch(function () {
                submitBtn.disabled = false;
                errorEl.textContent = 'Something went wrong. Please try again.';
            });
        });

        // 6 digit boxes
        emailCodeBoxes.forEach(function (box, index) {
            box.addEventListener('input', function () {
                box.value = box.value.replace(/\D/g, '').slice(0, 1);
                if (box.value !== '' && index < emailCodeBoxes.length - 1) emailCodeBoxes[index + 1].focus();
                emailCodeError.textContent = '';
                refreshEmailCode();
            });

            box.addEventListener('keydown', function (event) {
                if (event.key === 'Backspace' && box.value === '' && index > 0) {
                    event.preventDefault();
                    emailCodeBoxes[index - 1].value = '';
                    emailCodeBoxes[index - 1].focus();
                    refreshEmailCode();
                } else if (event.key === 'ArrowLeft' && index > 0) {
                    event.preventDefault();
                    emailCodeBoxes[index - 1].focus();
                } else if (event.key === 'ArrowRight' && index < emailCodeBoxes.length - 1) {
                    event.preventDefault();
                    emailCodeBoxes[index + 1].focus();
                } else if (event.key === 'Enter' && !emailVerifyBtn.disabled) {
                    event.preventDefault();
                    emailVerifyBtn.click();
                }
            });

            box.addEventListener('paste', function (event) {
                event.preventDefault();
                var digits = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                emailCodeBoxes.forEach(function (b, i) { b.value = digits[i] || ''; });
                refreshEmailCode();
                emailCodeBoxes[Math.min(digits.length, emailCodeBoxes.length - 1)].focus();
            });

            box.addEventListener('focus', function () { box.select(); });
        });

        emailVerifyBtn.addEventListener('click', function () {
            var code = emailCodeValue();
            if (code.length !== emailCodeBoxes.length) {
                shakeEmailCode();
                emailCodeError.textContent = 'Enter all ' + emailCodeBoxes.length + ' digits.';
                return;
            }

            emailVerifyBtn.disabled = true;
            emailCodeError.textContent = '';

            postAjax('verify_email_change', { code: code }).then(function (result) {
                if (!result.ok) {
                    emailCodeError.textContent = result.json.error || 'That code is incorrect or has expired.';
                    shakeEmailCode();
                    refreshEmailCode();
                    return;
                }

                var newEmail = result.json.email;
                saved.email = newEmail;
                $('#asEmailField').value = newEmail;
                emailInput.value = newEmail;
                updateTopbarEmail(newEmail);

                closeEdit();
                notifySuccess('Email Updated!', 'Your login email has been changed to ' + newEmail + '.');
            }).catch(function () {
                emailCodeError.textContent = 'Something went wrong. Please try again.';
                refreshEmailCode();
            });
        });

        emailResendBtn.addEventListener('click', function () {
            if (emailResendBtn.disabled) return;
            emailCodeError.textContent = '';
            emailResendBtn.disabled = true;

            postAjax('resend_email_change', {}).then(function (result) {
                if (!result.ok) {
                    emailCodeError.textContent = result.json.error || 'Could not resend the code.';
                    // A code went out a moment ago: keep the timer honest.
                    startEmailCooldown(result.json.retry_after || 0);
                    return;
                }

                clearEmailCode();
                showEmailDevCode(result.json.dev_code);
                startEmailCooldown(result.json.retry_after);
                emailCodeBoxes[0].focus();
                notifySuccess('Code sent', 'We sent a new code to ' + result.json.new_email + '.');
            }).catch(function () {
                emailCodeError.textContent = 'Something went wrong. Please try again.';
                startEmailCooldown(0);
            });
        });

        emailCancelBtn.addEventListener('click', resetEditForm);
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
                        confirmButtonColor: (window.SquirAdminTheme && window.SquirAdminTheme.swalColor) ? window.SquirAdminTheme.swalColor() : '#6b3f2a'
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

    /* Forgot password (email code, then set a new password) */
    var forgotOpen = document.getElementById('asForgotOpen');
    var forgotPanel = document.getElementById('asForgotPanel');
    if (forgotOpen && forgotPanel && passwordForm) {
        var steps = {
            send: $('[data-step="send"]', forgotPanel),
            verify: $('[data-step="verify"]', forgotPanel),
            reset: $('[data-step="reset"]', forgotPanel)
        };
        var sendBtn = document.getElementById('asForgotSend');
        var sendError = document.getElementById('asForgotSendError');
        var codeWrap = document.getElementById('asCodeInputs');
        var codeBoxes = $all('.as-code-box', codeWrap);
        var codeError = document.getElementById('asCodeError');
        var verifyBtn = document.getElementById('asVerifyBtn');
        var resendBtn = document.getElementById('asResend');
        var devBox = document.getElementById('asDevCode');
        var devValue = document.getElementById('asDevCodeValue');
        var resetForm = document.getElementById('asResetForm');
        var resetPasswordInput = document.getElementById('resetNewPassword');
        var resetChecklist = document.getElementById('resetChecklist');
        var resetError = document.getElementById('asResetFormError');
        var cooldownTimer = null;

        function showStep(name) {
            Object.keys(steps).forEach(function (key) { steps[key].hidden = key !== name; });
        }

        function codeValue() {
            return codeBoxes.map(function (box) { return box.value; }).join('');
        }

        function refreshCode() {
            codeBoxes.forEach(function (box) { box.classList.toggle('filled', box.value !== ''); });
            verifyBtn.disabled = codeValue().length !== codeBoxes.length;
        }

        function clearCode() {
            codeBoxes.forEach(function (box) { box.value = ''; });
            refreshCode();
        }

        function shakeCode() {
            codeWrap.classList.remove('shake');
            void codeWrap.offsetWidth;
            codeWrap.classList.add('shake');
        }

        function showDevCode(code) {
            if (code) {
                devValue.textContent = code;
                devBox.hidden = false;
            } else {
                devBox.hidden = true;
            }
        }

        function startCooldown(seconds) {
            clearInterval(cooldownTimer);
            var left = Math.max(0, parseInt(seconds, 10) || 0);

            function paint() {
                if (left > 0) {
                    resendBtn.disabled = true;
                    resendBtn.textContent = 'Resend in ' + left + 's';
                } else {
                    clearInterval(cooldownTimer);
                    resendBtn.disabled = false;
                    resendBtn.textContent = 'Resend';
                }
            }

            paint();
            if (left > 0) {
                cooldownTimer = setInterval(function () { left -= 1; paint(); }, 1000);
            }
        }

        function evaluateReset(value) {
            var rules = {
                length: value.length >= 8,
                number: /\d/.test(value),
                special: /[^A-Za-z0-9]/.test(value)
            };
            Object.keys(rules).forEach(function (rule) {
                var item = resetChecklist.querySelector('[data-rule="' + rule + '"]');
                if (item) item.classList.toggle('is-valid', rules[rule]);
            });
        }

        function resetForgotState() {
            clearInterval(cooldownTimer);
            clearCode();
            showDevCode('');
            sendError.textContent = '';
            codeError.textContent = '';
            resetError.textContent = '';
            $all('.settings-form-error', resetForm).forEach(function (el) { el.textContent = ''; });
            resetForm.reset();
            evaluateReset('');
            sendBtn.disabled = false;
            resendBtn.disabled = false;
            resendBtn.textContent = 'Resend';
            showStep('send');
        }

        forgotOpen.addEventListener('click', function (event) {
            event.preventDefault();
            passwordForm.hidden = true;
            forgotPanel.hidden = false;
            resetForgotState();
        });

        document.getElementById('asForgotBack').addEventListener('click', function (event) {
            event.preventDefault();
            forgotPanel.hidden = true;
            passwordForm.hidden = false;
            resetForgotState();
        });

        // Ask the server to email a code (also used by Resend)
        function requestCode(isResend) {
            var trigger = isResend ? resendBtn : sendBtn;
            var errorEl = isResend ? codeError : sendError;
            errorEl.textContent = '';
            trigger.disabled = true;

            return postAjax('forgot_send_code', {}).then(function (result) {
                if (result.ok) {
                    clearCode();
                    codeError.textContent = '';
                    showDevCode(result.json.dev_code);
                    showStep('verify');
                    startCooldown(result.json.retry_after);
                    codeBoxes[0].focus();
                    if (isResend) notifySuccess('Code sent', 'We sent a new code to your email.');
                    return;
                }

                // A code went out a moment ago: let the admin enter it.
                if (result.json.retry_after) {
                    showStep('verify');
                    startCooldown(result.json.retry_after);
                    codeError.textContent = result.json.error || '';
                    codeBoxes[0].focus();
                    return;
                }

                trigger.disabled = false;
                errorEl.textContent = result.json.error || 'Could not send the code.';
            }).catch(function () {
                trigger.disabled = false;
                errorEl.textContent = 'Something went wrong. Please try again.';
            });
        }

        sendBtn.addEventListener('click', function () { requestCode(false); });
        resendBtn.addEventListener('click', function () { if (!resendBtn.disabled) requestCode(true); });

        // 6 digit boxes
        codeBoxes.forEach(function (box, index) {
            box.addEventListener('input', function () {
                box.value = box.value.replace(/\D/g, '').slice(0, 1);
                if (box.value !== '' && index < codeBoxes.length - 1) codeBoxes[index + 1].focus();
                codeError.textContent = '';
                refreshCode();
            });

            box.addEventListener('keydown', function (event) {
                if (event.key === 'Backspace' && box.value === '' && index > 0) {
                    event.preventDefault();
                    codeBoxes[index - 1].value = '';
                    codeBoxes[index - 1].focus();
                    refreshCode();
                } else if (event.key === 'ArrowLeft' && index > 0) {
                    event.preventDefault();
                    codeBoxes[index - 1].focus();
                } else if (event.key === 'ArrowRight' && index < codeBoxes.length - 1) {
                    event.preventDefault();
                    codeBoxes[index + 1].focus();
                } else if (event.key === 'Enter' && !verifyBtn.disabled) {
                    event.preventDefault();
                    verifyBtn.click();
                }
            });

            box.addEventListener('paste', function (event) {
                event.preventDefault();
                var digits = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                codeBoxes.forEach(function (b, i) { b.value = digits[i] || ''; });
                refreshCode();
                codeBoxes[Math.min(digits.length, codeBoxes.length - 1)].focus();
            });

            box.addEventListener('focus', function () { box.select(); });
        });

        verifyBtn.addEventListener('click', function () {
            var code = codeValue();
            if (code.length !== codeBoxes.length) {
                shakeCode();
                codeError.textContent = 'Enter all ' + codeBoxes.length + ' digits.';
                return;
            }

            verifyBtn.disabled = true;
            codeError.textContent = '';

            postAjax('forgot_verify_code', { code: code }).then(function (result) {
                if (!result.ok) {
                    codeError.textContent = result.json.error || 'That code is incorrect.';
                    shakeCode();
                    verifyBtn.disabled = false;
                    return;
                }

                showStep('reset');
                resetPasswordInput.focus();
            }).catch(function () {
                verifyBtn.disabled = false;
                codeError.textContent = 'Something went wrong. Please try again.';
            });
        });

        resetPasswordInput.addEventListener('input', function () { evaluateReset(resetPasswordInput.value); });

        resetForm.addEventListener('submit', function (event) {
            event.preventDefault();
            $all('.settings-form-error', resetForm).forEach(function (el) { el.textContent = ''; });

            var submit = document.getElementById('asResetSubmit');
            submit.disabled = true;

            postAjax('forgot_reset_password', {
                new_password: resetPasswordInput.value,
                confirm_password: document.getElementById('resetConfirmPassword').value
            }).then(function (result) {
                submit.disabled = false;

                if (!result.ok) {
                    var errors = result.json.errors || {};
                    Object.keys(errors).forEach(function (field) {
                        var el = resetForm.querySelector('[data-error-for="' + field + '"]');
                        if (el) el.textContent = errors[field];
                    });
                    if (!errors.new_password && !errors.confirm_password) {
                        resetError.textContent = result.json.error || 'Could not reset your password.';
                    }
                    return;
                }

                var done = function () { window.location.href = endpoint; };
                if (window.Swal) {
                    Swal.fire({
                        title: 'Password Reset!',
                        text: 'Your password has been changed successfully. Please use your new password the next time you log in.',
                        icon: 'success',
                        confirmButtonText: 'Done',
                        confirmButtonColor: (window.SquirAdminTheme && window.SquirAdminTheme.swalColor) ? window.SquirAdminTheme.swalColor() : '#6b3f2a'
                    }).then(done);
                } else {
                    done();
                }
            }).catch(function () {
                submit.disabled = false;
                resetError.textContent = 'Something went wrong. Please try again.';
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