<?php
/**
 * CrossConnect MY - Add Listing Redirect
 * Checks login status and redirects to appropriate dashboard
 */

require_once __DIR__ . '/config/paths.php';
require_once __DIR__ . '/config/auth.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Logged in - redirect to appropriate dashboard
    if (isAdmin()) {
        // Admin can choose churches or events
        $type = $_GET['type'] ?? '';
        if ($type === 'event') {
            header('Location: ' . url('admin/events.php'));
        } else {
            header('Location: ' . url('admin/churches.php'));
        }
    } else {
        // Regular user - go to their dashboard
        $type = $_GET['type'] ?? '';
        if ($type === 'event') {
            header('Location: ' . url('dashboard/my-events.php'));
        } else {
            header('Location: ' . url('dashboard/my-churches.php'));
        }
    }
} else {
    // Not logged in - redirect to login with return URL
    $returnUrl = urlencode(url('add-listing.php'));
    header('Location: ' . url('auth/login.php?redirect=' . $returnUrl));
}

exit;
