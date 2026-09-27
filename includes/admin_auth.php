<?php

// Admin session helpers.

// Require an authenticated admin, or redirect to the admin login page
function requireAdminLogin(): int
{
	if (empty($_SESSION['admin_id'])) {
		header('Location: ' . url('login'));
		exit;
	}

	return (int) $_SESSION['admin_id'];
}

// Whether the current session belongs to a logged-in admin
function adminLoggedIn(): bool
{
	return !empty($_SESSION['admin_id']);
}

// Initials for the admin avatar bubble in the header/sidebar
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
