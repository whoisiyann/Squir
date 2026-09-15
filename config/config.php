<?php
// General application-wide settings and constants.

error_reporting(E_ALL);
ini_set('display_errors', '1'); // Palitan ng '0' pag production/live na ang site.

date_default_timezone_set('Asia/Manila');

define('APP_NAME', 'Squir');

// ============================================================
// VAULT ENCRYPTION KEY

// ang: php -r "echo bin2hex(random_bytes(32));"
// ============================================================
define('VAULT_ENCRYPTION_KEY', '224d55d4e1d879000f317dbfdec7b20d5b6205d152fd3cac557285af78c48eb1');
