<?php
// Prepare registration form data
$errors = $errors ?? [];
$values = $values ?? ['fullName' => '', 'username' => '', 'email' => ''];
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
    <meta name="description" content="Create your secure Squir digital vault account.">
    <title>Squir - Create your account</title>
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="./assets/css/register.css">
    <link rel="stylesheet" href="./assets/css/terms-view.css">
</head>
<body>
    <main class="registration-shell">
        <section class="auth-card" aria-labelledby="register-title">
            <div class="auth-visual" aria-label="Squir mascot">
                <img src="./assets/images/squir-login-signin-left-image.png" alt="Squir mascot">
            </div>

            <div class="auth-form<?= !empty($errors) ? ' has-errors' : '' ?>">
                <header class="auth-brand">
                    <img src="./assets/images/squir.png" alt="">
                    <div>
                        <span>Squir</span>
                    </div>
                </header>

                <p class="auth-tagline">Start organizing your digital life.</p>

                <div>
                    <h1 id="register-title">Create your account</h1>
                    <p class="subtitle">Create a secure account to access your digital vault.</p>
                </div>

                <?php if (isset($errors['form'])): ?>
                    <div class="form-alert" role="alert"><?= $escape($errors['form']) ?></div>
                <?php endif; ?>

                <form method="post" action="./register">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">

                    <div class="input-group-squir">
                        <label class="visually-hidden" for="full_name">Full Name</label>
                        <i class="ti ti-user" aria-hidden="true"></i>
                            <input id="full_name" name="full_name" type="text" autocomplete="name" maxlength="100" placeholder="Full Name" required value="<?= $escape($values['fullName']) ?>" aria-describedby="full_name-error">
                    </div>
                    <?= $fieldError('full_name') ?>

                    <div class="input-group-squir">
                        <label class="visually-hidden" for="email">Email address</label>
                        <i class="ti ti-mail" aria-hidden="true"></i>
                            <input id="email" name="email" type="email" autocomplete="email" maxlength="100" placeholder="Email address" required value="<?= $escape($values['email']) ?>" aria-describedby="email-error">
                    </div>
                    <?= $fieldError('email') ?>

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

                    <div class="form-check-squir">
                        <input class="form-check-input" id="terms" name="terms" type="checkbox" value="1" required <?= isset($_POST['terms']) ? 'checked' : '' ?> aria-describedby="terms-error">
                        <label for="terms">I agree to the <a href="#terms" data-terms-view>Terms of Service</a> and <a href="#privacy" data-terms-view>Privacy Policy</a></label>
                    </div>
                    <?= $fieldError('terms') ?>

                    <button type="submit" class="btn-squir">Create Account</button>
                </form>

                <p class="auth-switch">Already have an account? <a href="./login">Log in</a></p>
            </div>

            <!-- Swaps in over the sign-up form when "Terms of Service" / "Privacy Policy" is clicked -->
            <div class="terms-view" id="termsView" hidden>
                <button type="button" class="terms-view-close" id="termsViewClose" aria-label="Back to sign up"><i class="ti ti-arrow-left"></i></button>

                <header class="auth-brand">
                    <img src="./assets/images/squir.png" alt="">
                    <div>
                        <span>Squir</span>
                    </div>
                </header>

                <h1 id="termsViewTitle">Terms of Service and Privacy Policy</h1>

                <ul class="terms-view-list">
                    <li><strong>Acceptance &amp; Registration:</strong> By creating an account and using Squir, you agree to these terms and permit us to collect your registration details.</li>
                    <li><strong>Platform Usage:</strong> You are responsible for your account credentials, passwords, personal notes, tasks, and folders managed within the platform.</li>
                    <li><strong>Data Protection &amp; Use:</strong> We securely store and use your data strictly to operate the dashboard, search, and organization features, and we protect it from unauthorized access.</li>
                </ul>

                <p class="terms-view-footnote">By continuing to use Squir's services, you are agreeing to the <span class="terms-view-accent">Terms of Service</span> as of its effective date and acknowledging that you have read the <span class="terms-view-accent">Privacy Policy</span>.</p>

                <div class="terms-view-actions">
                    <button type="button" class="btn-squir" id="termsViewContinue">Continue</button>
                </div>
            </div>
        </section>
    </main>

    <script src="./assets/js/register.js"></script>
    <script src="./assets/js/terms-view.js"></script>
    <script src="./assets/js/auth-transition.js"></script>
</body>
</html>