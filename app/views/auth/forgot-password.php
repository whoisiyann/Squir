<?php
// Prepare forgot-password form data
$errors = $errors ?? [];
$values = $values ?? ['email' => ''];
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
    <meta name="description" content="Reset your Squir account password.">
    <title>Squir - Forgot password</title>
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="./assets/css/login.css?v=2">
    <link rel="stylesheet" href="./assets/css/forgot-password.css?v=1">
</head>
<body>
    <main class="login-shell">
        <section class="auth-card" aria-labelledby="forgot-title">
            <div class="auth-visual" aria-label="Squir mascot">
                <img src="./assets/images/squir-login-signin-left-image.png" alt="Squir mascot">
            </div>

            <div class="auth-form">
                <a class="auth-back" href="<?= $escape($backUrl ?? './login') ?>" aria-label="Back"><i class="ti ti-arrow-left" aria-hidden="true"></i></a>

                <header class="auth-brand">
                    <img src="./assets/images/squir.png" alt="">
                    <span>Squir</span>
                </header>

                <h1 id="forgot-title">Forgot password?</h1>
                <p class="subtitle">No worries! Enter the email on your account and we'll send you a code to reset it.</p>

                <?php if (!empty($errors['form'])): ?>
                    <div class="alert-squir alert-danger-squir" role="alert"><?= $escape($errors['form']) ?></div>
                <?php endif; ?>

                <form method="post" action="./forgot-password">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">

                    <div class="input-group-squir">
                        <label class="visually-hidden" for="email">Email address</label>
                        <i class="ti ti-mail" aria-hidden="true"></i>
                        <input id="email" name="email" type="email" autocomplete="email" placeholder="Email address" required value="<?= $escape($values['email']) ?>" aria-describedby="email-error">
                    </div>
                    <?= $fieldError('email') ?>

                    <button type="submit" class="btn-squir">Send Code</button>
                </form>

                <p class="auth-switch">Remember your password? <a href="./login">Log in</a></p>
            </div>
        </section>
    </main>
    <script src="./assets/js/login.js"></script>
    <script src="./assets/js/auth-transition.js"></script>
</body>
</html>