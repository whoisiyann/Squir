<?php

require_once __DIR__ . '/../includes/env.php';
// App settings

error_reporting(E_ALL);
// Keep raw errors out of user responses.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

// Use the PHP default log if the project log directory is unavailable.
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


// local = this computer only (XAMPP). production = real hosting.
define('APP_ENV', strtolower((string) env('APP_ENV', 'production')));

// Read the vault key from .env; never commit it.
// Changing the key makes saved passwords unreadable.
define('VAULT_ENCRYPTION_KEY', (string) env('VAULT_ENCRYPTION_KEY', ''));

define('PIN_LENGTH', 6);

define('VAULT_PIN_UNLOCK_SECONDS', 0);


define('PASSWORD_RESET_CODE_TTL_MINUTES', 10);
define('PASSWORD_RESET_RESEND_COOLDOWN_SECONDS', 60);

define('PIN_RESET_CODE_TTL_MINUTES', 10);
define('PIN_RESET_RESEND_COOLDOWN_SECONDS', 60);

define('EMAIL_CHANGE_CODE_TTL_MINUTES', 10);
define('EMAIL_CHANGE_RESEND_COOLDOWN_SECONDS', 60);




define('SMTP_HOST', (string) env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', (int) env('SMTP_PORT', '587')); // TLS
define('SMTP_USERNAME', trim((string) env('SMTP_USERNAME', '')));
define('SMTP_PASSWORD', str_replace(' ', '', (string) env('SMTP_PASSWORD', '')));

// Show reset codes only on local requests when email delivery fails.
$squirIsLoopback = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
define('MAIL_DEV_FALLBACK', APP_ENV === 'local' && $squirIsLoopback && envBool('MAIL_DEV_FALLBACK', false));
unset($squirIsLoopback);

// Log full emails, including codes, for local debugging.
define('MAIL_DEBUG', APP_ENV === 'local' && envBool('MAIL_DEBUG', false));
define('MAIL_FROM_ADDRESS', SMTP_USERNAME !== '' ? SMTP_USERNAME : 'no-reply@squir.local');
define('MAIL_FROM_NAME', 'Squir');
