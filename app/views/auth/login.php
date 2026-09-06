<!-- view/auth/login.php -->

<?php
$errors = $errors ?? [];
$values = $values ?? ['email' => ''];
$success = $success ?? null;
$escape = $escape ?? static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$fieldError = static fn (string $field): string => isset($errors[$field])
    ? '<p class="invalid-feedback-squir" id="' . $field . '-error">' . $escape($errors[$field]) . '</p>'
    : '';
?>
<!doctype html>
<html lang="en" data-pc-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
    <meta name="description" content="Log in to your Squir digital vault.">
    <title>Squir - Log in</title>
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="./assets/css/login.css?v=2">
</head>
<body>
    <main class="login-shell">
        <section class="auth-card" aria-labelledby="login-title">
            <div class="auth-visual" aria-label="Squir mascot">
                <img src="./assets/images/squir-login-signin-left-image.png" alt="Squir mascot">
            </div>

            <div class="auth-form">
                <header class="auth-brand">
                    <img src="./assets/images/squir.png" alt="">
                    <span>Squir</span>
                </header>
                <p class="auth-tagline">Your Personal Digital Vault<br>and Productivity Companion</p>

                <h1 id="login-title">Welcome Back!</h1>
                <p class="subtitle">Log in to continue to your account.</p>

                <?php if (!empty($errors['form'])): ?>
                    <div class="alert-squir alert-danger-squir" role="alert"><?= $escape($errors['form']) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert-squir alert-success-squir" role="status"><?= $escape($success) ?></div>
                <?php endif; ?>

                <form method="post" action="./index.php">
                    <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['login_csrf_token'] ?? '') ?>">

                    <div class="input-group-squir">
                        <label class="visually-hidden" for="email">Email address</label>
                        <i class="ti ti-mail" aria-hidden="true"></i>
                        <input id="email" name="email" type="email" autocomplete="email" placeholder="Email address" required value="<?= $escape($values['email']) ?>" aria-describedby="email-error">
                    </div>
                    <?= $fieldError('email') ?>

                    <div class="input-group-squir">
                        <label class="visually-hidden" for="password">Password</label>
                        <i class="ti ti-lock" aria-hidden="true"></i>
                        <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Password" required aria-describedby="password-error">
                        <button type="button" class="toggle-password" data-target="password" aria-label="Show password"><i class="ti ti-eye" aria-hidden="true"></i></button>
                    </div>
                    <?= $fieldError('password') ?>

                    <div class="form-row-squir">
                        <div class="form-check-squir">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <a href="#forgot-password">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-squir">Log In</button>
                </form>

                <p class="auth-switch">Don't have an account? <a href="./register.php">Sign Up</a></p>
            </div>
        </section>
    </main>
    <script src="./assets/js/login.js"></script>
    <script src="./assets/js/auth-transition.js"></script>
</body>
</html>
