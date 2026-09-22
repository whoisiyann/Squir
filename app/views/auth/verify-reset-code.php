
<?php
/** @var array   $errors */
/** @var ?string $notice */
/** @var ?string $devCode */
/** @var string  $email */
/** @var string  $csrfToken */

$errors = $errors ?? [];
$notice = $notice ?? null;
$devCode = $devCode ?? null;
$codeLength = PasswordReset::CODE_LENGTH;
$escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en" data-pc-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
    <meta name="description" content="Enter the verification code we sent to your email.">
    <title>Squir - Verify code</title>
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="./assets/css/login.css?v=2">
    <link rel="stylesheet" href="./assets/css/pin.css?v=2">
    <link rel="stylesheet" href="./assets/css/forgot-password.css?v=1">
</head>
<body class="pin-page">
    <main class="login-shell">
        <section class="auth-card" aria-labelledby="verifyTitle">
            <div class="auth-visual" aria-label="Squir mascot">
                <img src="./assets/images/squir-login-signin-left-image.png" alt="Squir mascot">
            </div>

            <div class="auth-form">
                <a class="auth-back" href="./forgot-password" aria-label="Back"><i class="ti ti-arrow-left" aria-hidden="true"></i></a>

                <header class="auth-brand">
                    <img src="./assets/images/squir.png" alt="">
                    <span>Squir</span>
                </header>

                <span class="pin-lock" aria-hidden="true"><i class="ti ti-mail-opened"></i></span>

                <h1 id="verifyTitle" class="pin-title">Enter Verification Code</h1>
                <p class="pin-subtitle">
                    We sent a <?= (int) $codeLength ?>-digit code to<br><strong><?= $escape($email) ?></strong>
                </p>

                <?php if ($notice): ?>
                    <div class="alert-squir alert-success-squir" role="status"><?= $escape($notice) ?></div>
                <?php endif; ?>

                <?php if ($devCode): ?>
                    <div class="alert-squir alert-success-squir" role="status">
                        Dev mode &mdash; email isn't configured yet, so here's your code: <strong><?= $escape($devCode) ?></strong>
                    </div>
                <?php endif; ?>

                <form method="post" action="./verify-reset-code" id="verifyCodeForm" autocomplete="off" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="intent" value="verify">
                    <input type="hidden" name="code" id="codeValue">

                    <div class="pin-inputs" id="codeInputs" role="group" aria-label="Verification code digits">
                        <?php for ($i = 0; $i < $codeLength; $i++): ?>
                            <input class="pin-box"
                                   type="text"
                                   inputmode="numeric"
                                   pattern="[0-9]*"
                                   maxlength="1"
                                   autocomplete="off"
                                   aria-label="Digit <?= $i + 1 ?>"
                                   <?= $i === 0 ? 'autofocus' : '' ?>>
                        <?php endfor; ?>
                    </div>

                    <p class="pin-hint <?= isset($errors['code']) ? 'is-error' : '' ?>" id="codeHint" role="status" aria-live="polite"><?= $escape($errors['code'] ?? '') ?></p>

                    <button type="submit" class="btn-squir pin-submit" id="codeSubmit" disabled>Verify</button>
                </form>

                <form method="post" action="./verify-reset-code" class="resend-form">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="intent" value="resend">
                    <p class="auth-switch">Didn't get a code? <button type="submit" class="link-button">Resend</button></p>
                </form>
            </div>
        </section>
    </main>

    <script>window.SQUIR_CODE_LENGTH = <?= (int) $codeLength ?>;</script>
    <script src="./assets/js/verify-reset-code.js?v=1"></script>
    <script src="./assets/js/auth-transition.js"></script>
</body>
</html>
