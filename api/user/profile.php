<?php
/**
 * CrossConnect MY - Profile Update API
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
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$churchName = trim($_POST['church_name'] ?? '');
$preferredLanguage = $_POST['preferred_language'] ?? 'en';

// Validation
if (empty($name)) {
    jsonError(__('error_name_required'));
}

if (strlen($name) < 2) {
    jsonError(__('error_name_too_short'));
}

if (empty($email)) {
    jsonError(__('error_email_required'));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonError(__('error_email_invalid'));
}

if (!in_array($preferredLanguage, ['en', 'bm'])) {
    $preferredLanguage = 'en';
}

// Get database connection
$pdo = getDbConnection();
if (!$pdo) {
    jsonError(__('error_database_failed'), 500);
}

try {
    // Check if email is taken by another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user['id']]);
    if ($stmt->fetch()) {
        jsonError(__('error_email_in_use'));
    }

    // Get old values for logging
    $stmt = $pdo->prepare("SELECT name, email, church_name, preferred_language FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $oldValues = $stmt->fetch(PDO::FETCH_ASSOC);

    // Update profile
    $stmt = $pdo->prepare("
        UPDATE users SET
            name = ?, email = ?, church_name = ?, preferred_language = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([
        $name,
        $email,
        $churchName ?: null,
        $preferredLanguage,
        $user['id']
    ]);

    // Update session
    $_SESSION['user']['name'] = $name;
    $_SESSION['user']['email'] = $email;
    $_SESSION['user']['church_name'] = $churchName;
    $_SESSION['user']['preferred_language'] = $preferredLanguage;

    // Update language cookie
    if (!headers_sent()) {
        setcookie('lang', $preferredLanguage, time() + (365 * 24 * 60 * 60), '/');
    }
    $_SESSION['lang'] = $preferredLanguage;

    logActivity('profile_updated', 'Profile updated', 'user', $user['id'], $oldValues, [
        'name' => $name,
        'email' => $email,
        'church_name' => $churchName,
        'preferred_language' => $preferredLanguage
    ]);

    jsonSuccess([
        'name' => $name,
        'email' => $email
    ], __('success_profile_updated'));

} catch (PDOException $e) {
    error_log("Profile update error: " . $e->getMessage());
    jsonError(__('error_generic'), 500);
}
