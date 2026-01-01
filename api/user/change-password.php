<?php
/**
 * CrossConnect MY - Change Password API
 */

require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json');

// Require authentication
if (!isLoggedIn()) {
    jsonError(__('error_unauthorized'), 401);
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError(__('error_method_not_allowed'), 405);
}

// Verify CSRF token
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    jsonError(__('error_invalid_security_token'));
}

$user = getCurrentUser();
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validation
if (empty($currentPassword)) {
    jsonError(__('error_current_password_required'));
}

if (empty($newPassword)) {
    jsonError(__('error_new_password_required'));
}

if (strlen($newPassword) < 8) {
    jsonError(__('error_password_too_short'));
}

if ($newPassword !== $confirmPassword) {
    jsonError(__('error_passwords_no_match'));
}

// Get database connection
$pdo = getDbConnection();
if (!$pdo) {
    jsonError(__('error_database_failed'), 500);
}

try {
    // Get user's current password hash
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        jsonError(__('error_user_not_found'));
    }

    // Verify current password
    if (!verifyPassword($currentPassword, $userData['password_hash'])) {
        logActivity('password_change_failed', 'Invalid current password', 'user', $user['id']);
        jsonError(__('error_current_password_incorrect'));
    }

    // Hash new password
    $newHash = hashPassword($newPassword);

    // Update password
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newHash, $user['id']]);

    logActivity('password_changed', 'Password changed successfully', 'user', $user['id']);

    jsonSuccess([], __('success_password_updated'));

} catch (PDOException $e) {
    error_log("Change password error: " . $e->getMessage());
    jsonError(__('error_generic'), 500);
}
