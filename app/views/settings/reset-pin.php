<?php
/** @var array  $user */
/** @var string $csrfToken */
/** @var int    $pinLength */

$pinLength = $pinLength ?? 6;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Squir - Reset PIN</title>
    <script>
        (function () {
            try {
                if (window.localStorage.getItem('squir-dashboard-theme') === 'dark') {
                    document.documentElement.classList.add('dashboard-dark-preload');
                }
            } catch (error) {}
        })();
    </script>
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/dashboard.css">
    <link rel="stylesheet" href="./assets/css/vault.css">
    <link rel="stylesheet" href="./assets/css/settings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body class="settings-page">
<div class="app-shell" id="appShell">
<script>
    try {
        if (window.localStorage.getItem('squir-sidebar-collapsed') === '1') {
            document.getElementById('appShell').classList.add('sidebar-collapsed');
        }
    } catch (error) {}
</script>
    <?php require __DIR__ . '/../../../includes/sidebar.php'; ?>

    <div class="main-area">
        <?php require __DIR__ . '/../../../includes/header.php'; ?>

        <main class="content-area">
            <div class="page-heading settings-subpage-heading">
                <div class="settings-subpage-title">
                    <a class="settings-back-btn" href="./settings" aria-label="Back to Settings"><i class="ti ti-arrow-left"></i></a>
                    <div>
                        <h1>Reset PIN</h1>
                        <p>Set a new pin for your vault.</p>
                    </div>
                </div>
            </div>

            <div class="settings-security-layout">
                <section class="dash-card settings-card">
                    <form id="resetPinForm" autocomplete="off" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                        <div class="settings-field settings-field-password">
                            <label for="currentPin">Current PIN</label>
                            <div class="settings-password-wrap">
                                <input type="password" id="currentPin" name="current_pin" inputmode="numeric" pattern="[0-9]*" maxlength="<?= (int) $pinLength ?>" placeholder="Enter your current PIN" autocomplete="off">
                                <button type="button" class="settings-password-toggle" data-target="currentPin" aria-label="Show PIN"><i class="ti ti-eye"></i></button>
                            </div>
                            <p class="settings-form-error" data-error-for="current_pin"></p>
                        </div>

                        <div class="settings-field settings-field-password">
                            <label for="newPin">New PIN</label>
                            <div class="settings-password-wrap">
                                <input type="password" id="newPin" name="new_pin" inputmode="numeric" pattern="[0-9]*" maxlength="<?= (int) $pinLength ?>" placeholder="Enter your new <?= (int) $pinLength ?>-digit PIN" autocomplete="off">
                                <button type="button" class="settings-password-toggle" data-target="newPin" aria-label="Show PIN"><i class="ti ti-eye"></i></button>
                            </div>
                            <p class="settings-form-error" data-error-for="new_pin"></p>
                        </div>

                        <div class="settings-field settings-field-password">
                            <label for="confirmPin">Confirm New PIN</label>
                            <div class="settings-password-wrap">
                                <input type="password" id="confirmPin" name="confirm_pin" inputmode="numeric" pattern="[0-9]*" maxlength="<?= (int) $pinLength ?>" placeholder="Re-enter your new PIN" autocomplete="off">
                                <button type="button" class="settings-password-toggle" data-target="confirmPin" aria-label="Show PIN"><i class="ti ti-eye"></i></button>
                            </div>
                            <p class="settings-form-error" data-error-for="confirm_pin"></p>
                        </div>

                        <p class="settings-form-error" id="resetPinFormError" role="alert"></p>

                        <button type="submit" class="btn-squir settings-submit-btn" id="resetPinSubmit">Update PIN</button>

                        <div class="settings-hint-box">
                            <i class="ti ti-alert-circle"></i>
                            <p>Your PIN is used to keep your account secure. Choose a new <?= (int) $pinLength ?>-digit PIN that you can remember.</p>
                        </div>
                    </form>
                </section>

                <aside class="settings-security-aside">
                    <img src="./assets/images/squir-your-security-matter.png" alt="">
                    <h3>Your Security Matters</h3>
                    <p>A strong PIN helps protect your data and keeps account safe.</p>
                </aside>
            </div>
        </main>
    </div>
</div>

<script>
    window.VAULT_CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
    window.SQUIR_PIN_LENGTH = <?= (int) $pinLength ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="./assets/js/dashboard.js?v=3"></script>
<script src="./assets/js/settings.js"></script>
</body>
</html>
