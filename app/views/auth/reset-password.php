
<?php
// Prepare reset-password form data
$errors = $errors ?? [];
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
    <meta name="description" content="Create a new password for your Squir account.">
    <title>Squir - Reset password</title>
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="./assets/css/login.css?v=2">
    <link rel="stylesheet" href="./assets/css/forgot-password.css?v=1">
</head>
<body>
    <main class="login-shell">
        <section class="auth-card" aria-labelledby="resetTitle">
            <div class="auth-visual" aria-label="Squir mascot">
                <img src="./assets/images/squir-login-signin-left-image.png" alt="Squir mascot">
            </div>

            <div class="auth-form">
                <a class="auth-back" href="./verify-reset-code" aria-label="Back"><i class="ti ti-arrow-left" aria-hidden="true"></i></a>

                <header class="auth-brand">
                    <img src="./assets/images/squir.png" alt="">
                    <span>Squir</span>
                </header>

                <h1 id="resetTitle">Create a new password</h1>
                <p class="subtitle">Enter and confirm your new password</p>

                <?php if (!empty($errors['form'])): ?>
                    <div class="alert-squir alert-danger-squir" role="alert"><?= $escape($errors['form']) ?></div>
                <?php endif; ?>

                <form method="post" action="./reset-password">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">

                    <div class="input-group-squir">
                        <label class="visually-hidden" for="password">Password</label>
                        <i class="ti ti-lock" aria-hidden="true"></i>
                        <input id="password" name="password" type="password" autocomplete="new-password" placeholder="Password" required aria-describedby="password-error">
                        <button type="button" class="toggle-password" data-target="password" aria-label="Show password"><i class="ti ti-eye" aria-hidden="true"></i></button>
                    </div>
                    <?= $fieldError('password') ?>

                    <div class="input-group-squir">
                        <label class="visually-hidden" for="password_confirmation">Confirm Password</label>
                        <i class="ti ti-lock" aria-hidden="true"></i>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Confirm Password" required aria-describedby="password_confirmation-error">
                        <button type="button" class="toggle-password" data-target="password_confirmation" aria-label="Show password"><i class="ti ti-eye" aria-hidden="true"></i></button>
                    </div>
                    <?= $fieldError('password_confirmation') ?>

                    <button type="submit" class="btn-squir">Continue</button>
                </form>
            </div>
        </section>
    </main>
    <script src="./assets/js/login.js"></script>
    <script src="./assets/js/auth-transition.js"></script>
</body>
</html>
