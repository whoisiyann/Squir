<?php
/** @var array  $errors */
/** @var string $csrfToken */
/** @var int    $pinLength */

$errors = $errors ?? [];
$pinLength = $pinLength ?? 6;
$escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en" data-pc-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
    <meta name="description" content="Create the PIN that unlocks your Squir vault.">
    <title>Squir - Create your PIN</title>
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="./assets/css/login.css?v=2">
    <link rel="stylesheet" href="./assets/css/pin.css?v=2">
</head>
<body class="pin-page">
    <main class="login-shell">
        <section class="auth-card" aria-labelledby="pinTitle">
            <div class="auth-visual" aria-hidden="true">
                <img src="./assets/images/squirPIN.png" alt="">
            </div>

            <div class="auth-form">
                <header class="auth-brand">
                    <img src="./assets/images/squir.png" alt="">
                    <span>Squir</span>
                </header>
                <p class="auth-tagline">Start organizing your digital life.</p>

                <span class="pin-lock" aria-hidden="true"><i class="ti ti-lock"></i></span>

                <h1 id="pinTitle" class="pin-title">Create Your PIN</h1>
                <p class="pin-subtitle" id="pinSubtitle">
                    This PIN will be used to unlock your vault<br>and view your saved passwords.
                </p>

                <?php if (isset($errors['pin'])): ?>
                    <div class="alert-squir alert-danger-squir" role="alert"><?= $escape($errors['pin']) ?></div>
                <?php endif; ?>

                <form method="post" action="./pin" id="pinSetupForm" autocomplete="off" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="pin" id="pinValue">
                    <input type="hidden" name="pin_confirmation" id="pinConfirmValue">

                    <div class="pin-inputs" id="pinInputs" role="group" aria-label="PIN digits">
                        <?php for ($i = 0; $i < $pinLength; $i++): ?>
                            <input class="pin-box"
                                   type="password"
                                   inputmode="numeric"
                                   pattern="[0-9]*"
                                   maxlength="1"
                                   autocomplete="off"
                                   aria-label="Digit <?= $i + 1 ?>"
                                   <?= $i === 0 ? 'autofocus' : '' ?>>
                        <?php endfor; ?>
                    </div>

                    <p class="pin-hint" id="pinHint" role="status" aria-live="polite"></p>

                    <button type="submit" class="btn-squir pin-submit" id="pinSubmit" disabled>Continue</button>
                </form>

                <p class="pin-note">
                    <i class="ti ti-shield-lock" aria-hidden="true"></i>
                    Keep your PIN safe and do not share it with anyone.
                </p>
            </div>
        </section>
    </main>

    <script>window.SQUIR_PIN_LENGTH = <?= (int) $pinLength ?>;</script>
    <script src="./assets/js/pin.js?v=2"></script>
</body>
</html>