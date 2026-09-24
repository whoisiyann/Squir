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
            </div>

            <p class="settings-form-error" id="editAccountError" role="alert"></p>

            <div class="vault-modal-actions">
                <button type="button" class="btn-outline-squir" data-close-modal="editAccount">Cancel</button>
                <button type="submit" class="btn-squir" id="editAccountSubmit">Done</button>
            </div>
        </form>
    </div>
</div>