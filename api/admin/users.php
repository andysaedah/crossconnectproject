<?php
/**
 * CrossConnect MY - Admin Users API
 */

require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json');

// Require admin
if (!isLoggedIn() || !isAdmin()) {
    jsonError('Unauthorized', 401);
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

// Verify CSRF token
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    jsonError('Invalid security token');
}

$userId = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$userId) {
    jsonError('User ID is required');
}

// Get database connection
$pdo = getDbConnection();
if (!$pdo) {
    jsonError('Database connection failed', 500);
}

try {
    // Get user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$targetUser) {
        jsonError('User not found');
    }

    switch ($action) {
        case 'verify':
            $stmt = $pdo->prepare("UPDATE users SET email_verified_at = NOW(), verification_token = NULL WHERE id = ?");
            $stmt->execute([$userId]);
            logActivity('admin_verify_user', 'Admin verified email for: ' . $targetUser['email'], 'user', $userId);
            jsonSuccess([], 'User email verified successfully');
            break;

        case 'activate':
            $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
            $stmt->execute([$userId]);
            logActivity('admin_activate_user', 'Admin activated user: ' . $targetUser['email'], 'user', $userId);
            jsonSuccess([], 'User activated successfully');
            break;

        case 'deactivate':
            // Can't deactivate yourself
            $currentUser = getCurrentUser();
            if ($currentUser['id'] == $userId) {
                jsonError('You cannot deactivate your own account');
            }
            $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
            $stmt->execute([$userId]);
            logActivity('admin_deactivate_user', 'Admin deactivated user: ' . $targetUser['email'], 'user', $userId);
            jsonSuccess([], 'User deactivated successfully');
            break;

        case 'reset_password':
            // Generate temporary password
            $tempPassword = substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789'), 0, 10);
            $hash = hashPassword($tempPassword);

            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $userId]);

            logActivity('admin_reset_password', 'Admin reset password for: ' . $targetUser['email'], 'user', $userId);

            // In production, you'd send an email. For now, show the temp password.
            jsonSuccess(['temp_password' => $tempPassword], "Password reset. Temporary password: $tempPassword");
            break;

        case 'make_admin':
            $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
            $stmt->execute([$userId]);
            logActivity('admin_promote_user', 'Admin promoted user to admin: ' . $targetUser['email'], 'user', $userId);
            jsonSuccess([], 'User promoted to admin');
            break;

        case 'remove_admin':
            $currentUser = getCurrentUser();
            if ($currentUser['id'] == $userId) {
                jsonError('You cannot remove your own admin privileges');
            }
            $stmt = $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ?");
            $stmt->execute([$userId]);
            logActivity('admin_demote_user', 'Admin demoted user from admin: ' . $targetUser['email'], 'user', $userId);
            jsonSuccess([], 'Admin privileges removed');
            break;

        default:
            jsonError('Invalid action');
    }

} catch (PDOException $e) {
    error_log("Admin users API error: " . $e->getMessage());
    jsonError('An error occurred', 500);
}
