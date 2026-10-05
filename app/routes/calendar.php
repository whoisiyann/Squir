<?php

require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../controllers/CalendarController.php';

$userId = requireLogin();
$controller = new CalendarController(new Task($dbh));
$csrfToken = csrfToken();

$tasks = $controller->index($userId);

$user = currentUserSummary($dbh, $userId);
$initials = $user['initials'];

require __DIR__ . '/../views/calendar/index.php';
