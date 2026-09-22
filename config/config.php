<?php
// General application-wide settings and constants.

error_reporting(E_ALL);
ini_set('display_errors', '1');

date_default_timezone_set('Asia/Manila');

define('APP_NAME', 'Squir');

// ============================================================
// VAULT ENCRYPTION KEY

// php -r "echo bin2hex(random_bytes(32));"
// ============================================================
define('VAULT_ENCRYPTION_KEY', '224d55d4e1d879000f317dbfdec7b20d5b6205d152fd3cac557285af78c48eb1');

define('PIN_LENGTH', 6);

define('VAULT_PIN_UNLOCK_SECONDS', 0);

// ============================================================
// FORGOT PASSWORD
// ============================================================
define('PASSWORD_RESET_CODE_TTL_MINUTES', 10);
define('PASSWORD_RESET_RESEND_COOLDOWN_SECONDS', 60);

// ============================================================
// .ENV LOADER (walang external dependency — simpleng KEY=VALUE lines lang)
// Binabasa ang credentials mula sa .env sa project root, hindi hardcoded
// dito sa config.php, para hindi ito ma-commit sa git kasama ang totoong
// password. Tignan ang .gitignore — dapat naka-ignore na ang .env.
// ============================================================
if (!function_exists('squirLoadEnv')) {
    function squirLoadEnv(string $path): void
    {
        if (!is_readable($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\"'");

            if ($key !== '' && getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
            }
        }
    }
}

squirLoadEnv(__DIR__ . '/../.env');

// ============================================================
// MAIL — Gmail SMTP via PHPMailer
//
// sendPasswordResetEmail() (includes/mailer.php) sends through Gmail's
// SMTP relay using PHPMailer, authenticated with a Gmail App Password
// (kailangan naka-ON ang 2-Step Verification sa Gmail account, tapos
// gumawa ng App Password sa myaccount.google.com/apppasswords —
// HINDI yung regular na password ang gagamitin dito).
//
// Ilagay ang totoong credentials sa .env (SMTP_USERNAME, SMTP_PASSWORD),
// hindi dito, para hindi ma-commit sa git.
//
// Kung wala/mali ang credentials, mag-throw ng exception ang PHPMailer;
// kapag nangyari yun at MAIL_DEV_FALLBACK is true, ipinapakita lang ng
// app ang code on-screen (dev testing) imbes na mag-crash. I-set false
// ito once gumagana na ang totoong pag-email, para hindi na kailanman
// lumabas ang code on screen.
// ============================================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587); // TLS
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');

define('MAIL_DEV_FALLBACK', true);
define('MAIL_FROM_ADDRESS', SMTP_USERNAME !== '' ? SMTP_USERNAME : 'no-reply@squir.local');
define('MAIL_FROM_NAME', 'Squir');