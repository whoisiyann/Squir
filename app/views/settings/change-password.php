<?php
/** @var array  $user */
/** @var string $csrfToken */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Squir - Change Password</title>
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
                        <h1>Change Password</h1>
                        <p>Keep your account safe by using a strong password.</p>
                    </div>
                </div>
            </div>

            <div class="settings-security-layout">
                <section class="dash-card settings-card">
                    <form id="changePasswordForm" autocomplete="off" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                        <div class="settings-field settings-field-password">
                            <label for="currentPassword">Current Password</label>
                            <div class="settings-password-wrap">
                                <input type="password" id="currentPassword" name="current_password" placeholder="Enter your current password" autocomplete="current-password">
                                <button type="button" class="settings-password-toggle" data-target="currentPassword" aria-label="Show password"><i class="ti ti-eye"></i></button>
                            </div>
                            <p class="settings-form-error" data-error-for="current_password"></p>
                        </div>

                        <div class="settings-field settings-field-password">
                            <label for="newPassword">New Password</label>
                            <div class="settings-password-wrap">
                                <input type="password" id="newPassword" name="new_password" placeholder="Enter your new password" autocomplete="new-password">
                                <button type="button" class="settings-password-toggle" data-target="newPassword" aria-label="Show password"><i class="ti ti-eye"></i></button>
                            </div>

                            <ul class="settings-password-checklist" id="passwordChecklist">
                                <li data-rule="length"><i class="ti ti-circle-check"></i> At least 8 characters</li>
                                <li data-rule="number"><i class="ti ti-circle-check"></i> Include a number</li>
                                <li data-rule="special"><i class="ti ti-circle-check"></i> Include a special character</li>
                            </ul>
                            <p class="settings-form-error" data-error-for="new_password"></p>
                        </div>

                        <div class="settings-field settings-field-password">
                            <label for="confirmPassword">Confirm New Password</label>
                            <div class="settings-password-wrap">
                                <input type="password" id="confirmPassword" name="confirm_password" placeholder="Re-enter your new password" autocomplete="new-password">
                                <button type="button" class="settings-password-toggle" data-target="confirmPassword" aria-label="Show password"><i class="ti ti-eye"></i></button>
                            </div>
                            <p class="settings-form-error" data-error-for="confirm_password"></p>
                        </div>

                        <p class="settings-form-error" id="changePasswordFormError" role="alert"></p>

                        <button type="submit" class="btn-squir settings-submit-btn" id="changePasswordSubmit">Update Password</button>
                    </form>
                </section>

                <aside class="settings-security-aside">
                    <img src="./assets/images/squir-your-security-matter.png" alt="">
                    <h3>Your Security Matters</h3>
                    <p>A strong password helps protect your data and keeps account safe.</p>
                </aside>
            </div>
        </main>
    </div>
</div>

<script>
    window.VAULT_CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="./assets/js/dashboard.js?v=3"></script>
<script src="./assets/js/settings.js"></script>
</body>
</html>
