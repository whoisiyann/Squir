<?php
/** @var array  $user */
/** @var string $csrfToken */

$escape = $escape ?? static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="vault-modal-backdrop" id="editAccountModalBackdrop">
    <div class="vault-modal settings-edit-modal" role="dialog" aria-modal="true" aria-labelledby="editAccountModalTitle">
        <div class="vault-modal-header">
            <h2 id="editAccountModalTitle">Account Information</h2>
            <button type="button" class="icon-btn" data-close-modal="editAccount" aria-label="Close"><i class="ti ti-x"></i></button>
        </div>

        <form id="editAccountForm" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <input type="hidden" id="editEmailOriginal" value="<?= $escape($user['email']) ?>">

            <div class="settings-modal-profile-row">
                <span class="settings-avatar" id="editAccountAvatar"><?= $escape(userInitials($user['full_name'])) ?></span>
                <div class="settings-profile-text">
                    <strong><?= $escape($user['full_name']) ?></strong>
                    <small>Member since <?= $escape(date('M j, Y', strtotime((string) $user['created_at']))) ?></small>
                </div>
                <button type="button" class="settings-delete-account-btn" id="deleteAccountBtn">
                    <i class="ti ti-trash"></i> Delete Account
                </button>
            </div>

            <div class="settings-editable-field">
                <div class="settings-editable-field-head">
                    <label for="editFullName">Full Name</label>
                    <button type="button" class="settings-field-edit-link" data-edit-target="editFullName"><i class="ti ti-pencil"></i> Edit</button>
                </div>
                <input type="text" id="editFullName" name="full_name" maxlength="100" value="<?= $escape($user['full_name']) ?>" readonly>
            </div>

            <div class="settings-editable-field">
                <div class="settings-editable-field-head">
                    <label for="editUsername">Username</label>
                    <button type="button" class="settings-field-edit-link" data-edit-target="editUsername"><i class="ti ti-pencil"></i> Edit</button>
                </div>
                <input type="text" id="editUsername" name="username" maxlength="50" value="<?= $escape($user['username']) ?>" readonly>
            </div>

            <div class="settings-editable-field">
                <div class="settings-editable-field-head">
                    <label for="editEmail">Email</label>
                    <button type="button" class="settings-field-edit-link" data-edit-target="editEmail"><i class="ti ti-pencil"></i> Edit</button>
                </div>
                <input type="email" id="editEmail" name="email" maxlength="100" value="<?= $escape($user['email']) ?>" readonly>
                <small class="settings-field-hint">Changing your email requires a verification code sent to the new address.</small>
            </div>

            <p class="settings-form-error" id="editAccountError" role="alert"></p>

            <div class="vault-modal-actions" id="editAccountActions">
                <button type="button" class="btn-outline-squir" data-close-modal="editAccount">Cancel</button>
                <button type="submit" class="btn-squir" id="editAccountSubmit">Done</button>
            </div>
        </form>

        <!-- Email verification step: shown after a new email is submitted -->
        <div id="emailChangeCodePanel" class="settings-email-code-panel" hidden>
            <span class="pin-lock" aria-hidden="true"><i class="ti ti-mail-opened"></i></span>
            <p class="settings-code-subtitle">
                We sent a 6-digit code to<br><strong id="emailChangeTargetEmail"></strong>
            </p>

            <p class="settings-form-note" id="emailChangeDevCode" hidden></p>

            <div class="pin-modal-inputs" id="emailChangeCodeInputs" role="group" aria-label="Verification code digits">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <input class="pin-modal-box"
                           type="text"
                           inputmode="numeric"
                           pattern="[0-9]*"
                           maxlength="1"
                           autocomplete="off"
                           aria-label="Digit <?= $i + 1 ?>">
                <?php endfor; ?>
            </div>

            <p class="settings-form-error" id="emailChangeCodeError" role="alert"></p>

            <div class="vault-modal-actions">
                <button type="button" class="btn-outline-squir" id="emailChangeCancelBtn">Cancel</button>
                <button type="button" class="btn-squir" id="emailChangeVerifyBtn">Verify</button>
            </div>
            <p class="auth-switch settings-code-resend">Didn't get a code? <button type="button" class="link-button" id="emailChangeResendBtn">Resend</button></p>
        </div>
    </div>
</div>