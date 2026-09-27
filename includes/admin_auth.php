<?php

// Admin-side session helpers.
// Kept separate from requireLogin()/csrf helpers in bootstrap.php on purpose:
// an admin session (admin_id) and a user session (user_id) are independent,
// so a person can never end up "logged in" as both from the same session.

// Require an authenticated admin, or redirect to the admin login page
function requireAdminLogin(): int
{
	if (empty($_SESSION['admin_id'])) {
		// One shared login form for both users and admins - see ./login.
		header('Location: ./login');
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
