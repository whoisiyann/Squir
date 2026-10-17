<?php
// App settings

error_reporting(E_ALL);
// Never show raw PHP/database errors to users. Errors go to the server log.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

// Save errors to logs/php-error.log inside the project.
// If the folder can't be created, PHP's default error log is used.
$squirLogDir = __DIR__ . '/../logs';
if (!is_dir($squirLogDir)) {
    @mkdir($squirLogDir, 0755, true);
}
if (is_dir($squirLogDir) && is_writable($squirLogDir)) {
    ini_set('error_log', $squirLogDir . '/php-error.log');
}
unset($squirLogDir);

date_default_timezone_set('Asia/Manila');

define('APP_NAME', 'Squir');


define('VAULT_ENCRYPTION_KEY', '224d55d4e1d879000f317dbfdec7b20d5b6205d152fd3cac557285af78c48eb1');

define('PIN_LENGTH', 6);

define('VAULT_PIN_UNLOCK_SECONDS', 0);


define('PASSWORD_RESET_CODE_TTL_MINUTES', 10);
define('PASSWORD_RESET_RESEND_COOLDOWN_SECONDS', 60);

define('PIN_RESET_CODE_TTL_MINUTES', 10);
define('PIN_RESET_RESEND_COOLDOWN_SECONDS', 60);

define('EMAIL_CHANGE_CODE_TTL_MINUTES', 10);
define('EMAIL_CHANGE_RESEND_COOLDOWN_SECONDS', 60);


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
define('SMTP_USERNAME', trim($_ENV['SMTP_USERNAME'] ?? getenv('SMTP_USERNAME') ?: ''));
define('SMTP_PASSWORD', str_replace(' ', '', $_ENV['SMTP_PASSWORD'] ?? getenv('SMTP_PASSWORD') ?: ''));

define('MAIL_DEV_FALLBACK', true);

// Enable SMTP debug logging
define('MAIL_DEBUG', true);
define('MAIL_FROM_ADDRESS', SMTP_USERNAME !== '' ? SMTP_USERNAME : 'no-reply@squir.local');
define('MAIL_FROM_NAME', 'Squir');
