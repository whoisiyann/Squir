<?php

require_once __DIR__ . '/../models/Favorite.php';
require_once __DIR__ . '/../models/Vault.php';
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../models/UserPin.php';
require_once __DIR__ . '/../controllers/FavoriteController.php';

$userId = requireLogin();
$csrfToken = csrfToken();

$favoriteModel = new Favorite($dbh);
$controller = new FavoriteController($favoriteModel, $dbh);

$type = in_array($_GET['type'] ?? '', ['passwords', 'notes', 'folders'], true) ? $_GET['type'] : 'passwords';
$search = trim((string) ($_GET['q'] ?? ''));

$data = $controller->index($userId, $type, $search);

$pinLength = UserPin::length();
$pinUnlockSeconds = defined('VAULT_PIN_UNLOCK_SECONDS') ? (int) VAULT_PIN_UNLOCK_SECONDS : 0;

$user = currentUserSummary($dbh, $userId);
$initials = $user['initials'];

require __DIR__ . '/../views/favorites/index.php';
