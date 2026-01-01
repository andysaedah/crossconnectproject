<?php
/**
 * CrossConnect MY - Logout Handler
 */

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/auth.php';

// Log the logout if user was logged in
if (isLoggedIn()) {
    $user = getCurrentUser();
    logActivity('logout', 'User logged out', 'user', $user['id']);
}

// Clear the session
clearUserSession();

// Redirect to home
header('Location: ' . url('/'));
exit;
