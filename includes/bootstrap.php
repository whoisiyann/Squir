<?php

if (ob_get_level() === 0) {
    ob_start();
}

// Secure session cookie settings.
$squirHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', $squirHttps ? '1' : '0');
unset($squirHttps);

session_start();

// Request phone model hints
if (!headers_sent()) {
    header('Accept-CH: Sec-CH-UA-Model');
}

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/dbconnect.php';
require_once __DIR__ . '/../config/config.php';

// Show users a generic error and log the details.
if (!function_exists('squirIsAjaxRequest')) {
    function squirIsAjaxRequest(): bool
    {
        if (isset($_POST['ajax']) || isset($_GET['ajax'])) {
            return true;
        }
        $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        return $requestedWith === 'xmlhttprequest' || str_contains($accept, 'application/json');
    }

    function squirRenderFatalError(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            http_response_code(500);
        }

        if (squirIsAjaxRequest()) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            echo json_encode(['error' => 'Something went wrong. Please try again.']);
            return;
        }

        echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Something went wrong</title></head><body>'
            . '<div style="position:fixed;top:0;right:0;bottom:0;left:0;z-index:2147483647;'
            . 'display:flex;align-items:center;justify-content:center;padding:1rem;box-sizing:border-box;'
            . 'background:#f6f1ea;font-family:system-ui,-apple-system,\'Segoe UI\',Roboto,sans-serif;color:#3b2f2a">'
            . '<div style="background:#fff;padding:2rem 2.5rem;border-radius:12px;max-width:420px;width:100%;'
            . 'box-sizing:border-box;text-align:center;box-shadow:0 4px 18px rgba(0,0,0,.08)">'
            . '<h1 style="font-size:1.4rem;margin:0 0 .75rem;color:#3b2f2a;font-weight:700">Something went wrong</h1>'
            . '<p style="margin:0 0 1rem;color:#3b2f2a;line-height:1.5;font-size:1rem">'
            . 'We could not complete your request. Please try again in a moment.</p>'
            . '<p style="margin:0"><a href="javascript:history.back()" style="color:#8a5a3b;font-size:1rem">Go back</a></p>'
            . '</div></div></body></html>';
    }

    set_exception_handler(function (Throwable $e): void {
        error_log('[uncaught] ' . get_class($e) . ': ' . $e->getMessage()
            . ' in ' . $e->getFile() . ':' . $e->getLine());
        squirRenderFatalError();
    });

    register_shutdown_function(function (): void {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            error_log('[fatal] ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
            squirRenderFatalError();
        }
    });
}


// Resolve the app base path.
if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    define('BASE_URL', $scriptDir === '/' ? '' : rtrim($scriptDir, '/'));
}

if (!function_exists('url')) {
    // Build a base-aware URL.
    function url(string $path = ''): string
    {
        return BASE_URL . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    // Version assets by modification time.
    function asset(string $path): string
    {
        $file = __DIR__ . '/../' . ltrim($path, '/');
        $version = is_file($file) ? filemtime($file) : time();
        return url($path) . '?v=' . $version;
    }
}


if (!defined('PREF_THEMES')) {
    define('PREF_THEMES', ['light', 'dark', 'auto']);
    define('PREF_ACCENTS', ['brown', 'blue', 'green', 'purple', 'yellow', 'rose']);
}

// Saved appearance (theme + accent), cached in the session.
function sessionPreferences(string $role = 'user'): array
{
    global $dbh;

    $key = 'prefs_' . $role;
    if (isset($_SESSION[$key]) && is_array($_SESSION[$key])) {
        return $_SESSION[$key];
    }

    $prefs = ['theme' => null, 'accent' => null];
    $idKey = $role === 'admin' ? 'admin_id' : 'user_id';
    $table = $role === 'admin' ? 'admins' : 'users';

    if (empty($_SESSION[$idKey])) {
        return $prefs;
    }

    try {
        $statement = $dbh->prepare("SELECT theme_pref, accent_color FROM {$table} WHERE {$idKey} = :id LIMIT 1");
        $statement->execute(['id' => (int) $_SESSION[$idKey]]);
        $row = $statement->fetch(PDO::FETCH_ASSOC) ?: [];

        $prefs['theme'] = in_array($row['theme_pref'] ?? null, PREF_THEMES, true) ? $row['theme_pref'] : null;
        $prefs['accent'] = in_array($row['accent_color'] ?? null, PREF_ACCENTS, true) ? $row['accent_color'] : null;
        $_SESSION[$key] = $prefs;
    } catch (Throwable $exception) {
        // Preferences may be unavailable until the database migration runs.
    }

    return $prefs;
}

function preferencesJson(string $role = 'user'): string
{
    return json_encode(sessionPreferences($role), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

// Validate submitted appearance values.
function cleanPreferences(array $input): array
{
    $theme = $input['theme'] ?? null;
    $accent = $input['accent'] ?? null;

    return [
        'theme'  => in_array($theme, PREF_THEMES, true) ? $theme : null,
        'accent' => in_array($accent, PREF_ACCENTS, true) ? $accent : null,
    ];
}


function requireLogin(bool $requirePin = true, bool $requireUsername = true): int
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ./login');
        exit;
    }

    global $dbh;

    $userId = (int) $_SESSION['user_id'];

    // Enforce account and session status
    $check = $dbh->prepare('SELECT status, username, UNIX_TIMESTAMP(force_logout_at) AS force_logout_at FROM users WHERE user_id = :uid');
    $check->execute(['uid' => $userId]);
    $account = $check->fetch(PDO::FETCH_ASSOC);

    if (
        !$account
        || $account['status'] !== 'active'
        || ($account['force_logout_at'] !== null && (int) $account['force_logout_at'] >= (int) ($_SESSION['login_at'] ?? 0))
    ) {
        session_unset();
        session_destroy();
        header('Location: ./login');
        exit;
    }

    if ($requirePin && empty($_SESSION['has_pin'])) {
        $stmt = $dbh->prepare('SELECT 1 FROM user_pins WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);

        if ($stmt->fetchColumn()) {
            $_SESSION['has_pin'] = true;
        } else {
            header('Location: ./pin');
            exit;
        }
    }

    // New accounts pick their username right after creating a PIN
    if ($requirePin && $requireUsername && trim((string) $account['username']) === '') {
        header('Location: ./username');
        exit;
    }

    return $userId;
}


function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}


function csrfValid(?string $submitted): bool
{
    return hash_equals(csrfToken(), (string) $submitted);
}


// PIN unlock

function grantPinUnlock(): void
{
    $seconds = defined('VAULT_PIN_UNLOCK_SECONDS') ? (int) VAULT_PIN_UNLOCK_SECONDS : 0;

    if ($seconds > 0) {
        $_SESSION['pin_unlocked_until'] = time() + $seconds;
    } else {
        $_SESSION['pin_unlock_once'] = true;
    }
}


function pinUnlocked(): bool
{
    $seconds = defined('VAULT_PIN_UNLOCK_SECONDS') ? (int) VAULT_PIN_UNLOCK_SECONDS : 0;

    if ($seconds > 0) {
        return !empty($_SESSION['pin_unlocked_until'])
            && (int) $_SESSION['pin_unlocked_until'] > time();
    }

    return !empty($_SESSION['pin_unlock_once']);
}


function consumePinUnlock(): void
{
    unset($_SESSION['pin_unlock_once']);
}


function clearPinUnlock(): void
{
    unset($_SESSION['pin_unlock_once'], $_SESSION['pin_unlocked_until']);
}


function userInitials(string $fullName): string
{
    $parts = preg_split('/\s+/', trim($fullName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if ($parts === []) {
        return 'U';
    }

    $initials = mb_substr($parts[0], 0, 1);
    if (count($parts) > 1) {
        $initials .= mb_substr($parts[count($parts) - 1], 0, 1);
    }

    return mb_strtoupper($initials);
}


function currentUserSummary(PDO $dbh, int $userId): array
{
    $stmt = $dbh->prepare('SELECT full_name, username, email FROM users WHERE user_id = :uid');
    $stmt->execute(['uid' => $userId]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['full_name' => 'User', 'username' => 'user', 'email' => ''];
    $user['initials'] = userInitials($user['full_name']);

    return $user;
}