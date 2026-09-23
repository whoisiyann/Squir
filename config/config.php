<?php
// General application-wide settings and constants.

error_reporting(E_ALL);
ini_set('display_errors', '1');

date_default_timezone_set('Asia/Manila');

define('APP_NAME', 'Squir');


define('VAULT_ENCRYPTION_KEY', '224d55d4e1d879000f317dbfdec7b20d5b6205d152fd3cac557285af78c48eb1');

define('PIN_LENGTH', 6);

define('VAULT_PIN_UNLOCK_SECONDS', 0);


define('PASSWORD_RESET_CODE_TTL_MINUTES', 10);
define('PASSWORD_RESET_RESEND_COOLDOWN_SECONDS', 60);


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


            if ($key !== '') {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
            }
        }
    }
}

squirLoadEnv(__DIR__ . '/../.env');


define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587); // TLS
define('SMTP_USERNAME', trim(getenv('SMTP_USERNAME') ?: ''));

define('SMTP_PASSWORD', str_replace(' ', '', getenv('SMTP_PASSWORD') ?: ''));

define('MAIL_DEV_FALLBACK', true);

// Set to false before going live — writes the full Gmail SMTP transcript to
// includes/logs/mail-debug.log. Never shown to the user, dev-only.
define('MAIL_DEBUG', true);
define('MAIL_FROM_ADDRESS', SMTP_USERNAME !== '' ? SMTP_USERNAME : 'no-reply@squir.local');
define('MAIL_FROM_NAME', 'Squir');