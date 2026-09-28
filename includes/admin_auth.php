<?php

// Admin session helpers.

// Require admin login
function requireAdminLogin(): int
{
	if (empty($_SESSION['admin_id'])) {
		header('Location: ' . url('login'));
		exit;
	}

	return (int) $_SESSION['admin_id'];
}

// Check admin session
function adminLoggedIn(): bool
{
	return !empty($_SESSION['admin_id']);
}

// Get admin initials
function adminInitials(string $fullName): string
{
	$parts = preg_split('/\s+/', trim($fullName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
	if ($parts === []) {
		return 'A';
	}

	$initials = mb_substr($parts[0], 0, 1);
	if (count($parts) > 1) {
		$initials .= mb_substr($parts[count($parts) - 1], 0, 1);
	}

	return mb_strtoupper($initials);
}
