<?php

// Admin login check
function requireAdminLogin(): int
{
	global $dbh;

	$adminId = (int) ($_SESSION['admin_id'] ?? 0);

	// Confirm the admin account is still active.
	if ($adminId > 0) {
		$check = $dbh->prepare('SELECT status FROM admins WHERE admin_id = :id LIMIT 1');
		$check->execute(['id' => $adminId]);

		if ($check->fetchColumn() === 'active') {
			return $adminId;
		}

		// Deleted or deactivated: end the admin session only.
		unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['prefs_admin']);
	}

	if (function_exists('squirIsAjaxRequest') && squirIsAjaxRequest()) {
		http_response_code(401);
		header('Content-Type: application/json');
		echo json_encode(['error' => 'Your session has ended. Please sign in again.']);
		exit;
	}

	header('Location: ' . url('login'));
	exit;
}

// Admin session check
function adminLoggedIn(): bool
{
	return !empty($_SESSION['admin_id']);
}

// Admin initials
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
