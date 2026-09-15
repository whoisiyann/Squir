
<?php


session_start();

require_once __DIR__ . '/dbconnect.php';
require_once __DIR__ . '/../config/config.php';


function requireLogin(): int
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ./login');
        exit;
    }

    return (int) $_SESSION['user_id'];
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


function currentUserSummary(PDO $dbh, int $userId): array
{
    $stmt = $dbh->prepare('SELECT full_name, username FROM users WHERE user_id = :uid');
    $stmt->execute(['uid' => $userId]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['full_name' => 'User', 'username' => 'user'];
    $user['initials'] = strtoupper(substr($user['full_name'], 0, 1));

    return $user;
}
