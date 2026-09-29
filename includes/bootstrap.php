<?php

session_start();

// Request phone model hints
if (!headers_sent()) {
    header('Accept-CH: Sec-CH-UA-Model');
}

require_once __DIR__ . '/dbconnect.php';
require_once __DIR__ . '/../config/config.php';


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


if (!defined('PREF_THEMES')) {
    define('PREF_THEMES', ['light', 'dark', 'auto']);
    define('PREF_ACCENTS', ['brown', 'blue', 'green', 'purple', 'orange', 'rose']);
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
        // Columns missing until the migration is run. Keep the choice saved in the browser.
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
    $stmt = $dbh->prepare('SELECT full_name, username FROM users WHERE user_id = :uid');
    $stmt->execute(['uid' => $userId]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['full_name' => 'User', 'username' => 'user'];
    $user['initials'] = userInitials($user['full_name']);

    return $user;
}