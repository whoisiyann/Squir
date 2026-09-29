<?php
/** @var array  $errors */
/** @var array  $values */
/** @var string $csrfToken */

$errors = $errors ?? [];
$values = $values ?? ['username' => ''];
$escape = $escape ?? static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en" data-pc-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
    <meta name="description" content="Tell Squir what to call you.">
    <title>Squir - What should we call you?</title>
    <link rel="icon" href="./assets/images/squir.png" type="image/x-icon" />
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="./assets/css/login.css?v=2">
    <link rel="stylesheet" href="./assets/css/pin.css?v=2">
</head>
<body class="pin-page">
    <main class="login-shell">
        <section class="auth-card" aria-labelledby="usernameTitle">
            <div class="auth-visual" aria-hidden="true">
                <img src="./assets/images/squirPIN.png" alt="">
            </div>

            <div class="auth-form">
                <header class="auth-brand">
                    <img src="./assets/images/squir.png" alt="">
                    <span>Squir</span>
                </header>
                <p class="auth-tagline">Start organizing your digital life.</p>

                <span class="pin-lock" aria-hidden="true"><i class="ti ti-user"></i></span>

                <h1 id="usernameTitle" class="pin-title">What should Squir call you?</h1>
                <p class="pin-subtitle">
                    This is the name we'll use to greet you<br>on your dashboard.
                </p>

                <?php if (isset($errors['form'])): ?>
                    <div class="alert-squir alert-danger-squir" role="alert"><?= $escape($errors['form']) ?></div>
                <?php endif; ?>

                <form method="post" action="./username" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">

                    <div class="input-group-squir">
                        <label class="visually-hidden" for="username">Username</label>
                        <i class="ti ti-user" aria-hidden="true"></i>
                        <input id="username" name="username" type="text" maxlength="50" placeholder="Enter your username" required autofocus value="<?= $escape($values['username']) ?>" aria-describedby="username-error">
                    </div>
                    <?php if (isset($errors['username'])): ?>
                        <p class="invalid-feedback-squir" id="username-error"><?= $escape($errors['username']) ?></p>
                    <?php endif; ?>

                    <button type="submit" class="btn-squir">Continue</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
