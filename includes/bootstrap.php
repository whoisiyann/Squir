<?php

session_start();

require_once __DIR__ . '/dbconnect.php';
require_once __DIR__ . '/../config/config.php';


/**
 * @param bool $requirePin 
 */

function requireLogin(bool $requirePin = true): int
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ./login');
        exit;
    }

    $userId = (int) $_SESSION['user_id'];

    if ($requirePin && empty($_SESSION['has_pin'])) {
        global $dbh;

        $stmt = $dbh->prepare('SELECT 1 FROM user_pins WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);

        if ($stmt->fetchColumn()) {
            $_SESSION['has_pin'] = true;
        } else {
            header('Location: ./pin');
            exit;
        }
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


/* ===================== PIN UNLOCK (vault reveal) ===================== */

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