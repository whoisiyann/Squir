<?php
// Prepare reset-pin-new form data
$errors = $errors ?? [];
$pinLength = $pinLength ?? 6;
$escape = $escape ?? static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$fieldError = $fieldError ?? static fn (string $field): string => isset($errors[$field])
    ? '<p class="invalid-feedback-squir" id="' . $field . '-error">' . $escape($errors[$field]) . '</p>'
    : '';
?>
<!doctype html>
<html lang="en" data-pc-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
    <meta name="description" content="Create a new PIN for your Squir vault.">
    <title>Squir - Reset PIN</title>
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="./assets/css/login.css?v=2">
    <link rel="stylesheet" href="./assets/css/forgot-password.css?v=1">
</head>
<body>
    <main class="login-shell">
        <section class="auth-card" aria-labelledby="resetPinNewTitle">
            <div class="auth-visual" aria-label="Squir mascot">
                <img src="./assets/images/squir-login-signin-left-image.png" alt="Squir mascot">
            </div>

            <div class="auth-form">
                <a class="auth-back" href="./verify-pin-code" aria-label="Back"><i class="ti ti-arrow-left" aria-hidden="true"></i></a>

                <header class="auth-brand">
                    <img src="./assets/images/squir.png" alt="">
                    <span>Squir</span>
                </header>

                <h1 id="resetPinNewTitle">Create a new PIN</h1>
                <p class="subtitle">Enter and confirm your new <?= (int) $pinLength ?>-digit PIN</p>

                <?php if (!empty($errors['form'])): ?>
                    <div class="alert-squir alert-danger-squir" role="alert"><?= $escape($errors['form']) ?></div>
                <?php endif; ?>

                <form method="post" action="./reset-pin-new" id="resetPinNewForm" autocomplete="off" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">

                    <div class="input-group-squir">
                        <label class="visually-hidden" for="new_pin">New PIN</label>
                        <i class="ti ti-lock" aria-hidden="true"></i>
                        <input id="new_pin" name="new_pin" type="password" inputmode="numeric" pattern="[0-9]*" maxlength="<?= (int) $pinLength ?>" autocomplete="off" placeholder="Enter your new <?= (int) $pinLength ?>-digit PIN" required aria-describedby="new_pin-error">
                        <button type="button" class="toggle-password" data-target="new_pin" aria-label="Show PIN"><i class="ti ti-eye" aria-hidden="true"></i></button>
                    </div>
                    <?= $fieldError('new_pin') ?>

                    <div class="input-group-squir">
                        <label class="visually-hidden" for="confirm_pin">Confirm New PIN</label>
                        <i class="ti ti-lock" aria-hidden="true"></i>
                        <input id="confirm_pin" name="confirm_pin" type="password" inputmode="numeric" pattern="[0-9]*" maxlength="<?= (int) $pinLength ?>" autocomplete="off" placeholder="Re-enter your new PIN" required aria-describedby="confirm_pin-error">
                        <button type="button" class="toggle-password" data-target="confirm_pin" aria-label="Show PIN"><i class="ti ti-eye" aria-hidden="true"></i></button>
                    </div>
                    <?= $fieldError('confirm_pin') ?>

                    <button type="submit" class="btn-squir">Update PIN</button>
                </form>
            </div>
        </section>
    </main>
    <script>
        document.querySelectorAll('#resetPinNewForm input[inputmode="numeric"]').forEach(function (input) {
            input.addEventListener('input', function () {
                input.value = input.value.replace(/\D/g, '').slice(0, input.getAttribute('maxlength'));
            });
        });
    </script>
    <script src="./assets/js/login.js"></script>
    <script src="./assets/js/auth-transition.js"></script>
</body>
</html>
