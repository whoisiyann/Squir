<?php
$deleteAccountPinLength = class_exists('UserPin') ? UserPin::length() : 6;
?>
<div class="sq-da-backdrop" id="deleteAccountModalBackdrop" aria-hidden="true">
    <div class="sq-da-modal" role="alertdialog" aria-modal="true" aria-labelledby="deleteAccountTitle" aria-describedby="deleteAccountText">
        <img class="sq-da-image" src="./assets/images/squir-sleep.gif" alt="" width="167" height="176">

        <h2 class="sq-da-title" id="deleteAccountTitle"><i class="ti ti-trash" aria-hidden="true"></i> Delete Account</h2>
        <p class="sq-da-text" id="deleteAccountText">Are you sure you want to delete your account? This will permanently remove your vault, notes, tasks, and all related data. This cannot be undone.</p>

        <div class="sq-da-pin" id="deleteAccountPinStep" hidden>
            <p class="sq-da-pin-label" id="deleteAccountPinLabel">Enter your PIN to confirm</p>
            <div class="pin-modal-inputs" id="deleteAccountPinInputs" role="group" aria-labelledby="deleteAccountPinLabel">
                <?php for ($i = 0; $i < (int) $deleteAccountPinLength; $i++): ?>
                    <input class="pin-modal-box"
                           type="password"
                           inputmode="numeric"
                           pattern="[0-9]*"
                           maxlength="1"
                           autocomplete="off"
                           aria-label="PIN digit <?= $i + 1 ?>">
                <?php endfor; ?>
            </div>
        </div>

        <p class="sq-da-error" id="deleteAccountError" role="alert"></p>

        <div class="sq-da-actions">
            <button type="button" class="sq-da-btn sq-da-btn-cancel" id="deleteAccountCancel">Cancel</button>
            <button type="button" class="sq-da-btn sq-da-btn-danger" id="deleteAccountConfirm">Delete</button>
        </div>
    </div>
</div>