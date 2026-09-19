<?php
/** @var int $pinLength*/

$pinLength = $pinLength ?? (defined('PIN_LENGTH') ? (int) PIN_LENGTH : 6);
?>
<div class="pin-modal-backdrop" id="pinModalBackdrop" aria-hidden="true">
    <div class="pin-modal" role="dialog" aria-modal="true" aria-labelledby="pinModalTitle">
        <button type="button" class="pin-modal-close" id="pinModalClose" aria-label="Close">
            <i class="ti ti-x"></i>
        </button>

        <span class="pin-modal-icon" aria-hidden="true"><i class="ti ti-lock"></i></span>

        <h2 id="pinModalTitle">Enter PIN</h2>
        <p class="pin-modal-subtitle" id="pinModalSubtitle">Enter your PIN to view this password.</p>

        <div class="pin-modal-inputs" id="pinModalInputs" role="group" aria-label="PIN digits">
            <?php for ($i = 0; $i < $pinLength; $i++): ?>
                <input class="pin-modal-box"
                       type="password"
                       inputmode="numeric"
                       pattern="[0-9]*"
                       maxlength="1"
                       autocomplete="off"
                       aria-label="Digit <?= $i + 1 ?>">
            <?php endfor; ?>
        </div>

        <p class="pin-modal-error" id="pinModalError" role="alert"></p>

        <p class="pin-modal-note">Your information stays private and secure.</p>
    </div>
</div>