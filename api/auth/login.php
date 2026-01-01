<?php
/**
 * CrossConnect MY - Login API
 * Handles user authentication via AJAX
 */

require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError(__('error_method_not_allowed'), 405);
}

// Verify CSRF token
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    jsonError(__('error_invalid_security_token'));
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validation
if (empty($email)) {
    jsonError(__('error_email_required'));
}

if (empty($password)) {
    jsonError(__('error_password_required'));
}

// Rate limiting check
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimitKey = 'login_attempts_' . md5($ip);
$attempts = $_SESSION[$rateLimitKey] ?? ['count' => 0, 'first_attempt' => time()];

// Reset if lockout period has passed
if ($attempts['count'] >= MAX_LOGIN_ATTEMPTS) {
    $timeSinceFirst = time() - $attempts['first_attempt'];
    if ($timeSinceFirst < LOGIN_LOCKOUT_TIME) {
        $remainingTime = ceil((LOGIN_LOCKOUT_TIME - $timeSinceFirst) / 60);
        jsonError(str_replace('{minutes}', $remainingTime, __('error_too_many_attempts')));
    } else {
        // Reset after lockout period
        $attempts = ['count' => 0, 'first_attempt' => time()];
        $_SESSION[$rateLimitKey] = $attempts;
    }
}

// Get database connection
$pdo = getDbConnection();
if (!$pdo) {
    jsonError(__('error_database_failed'), 500);
}

try {
    // Find user by email or username
    $stmt = $pdo->prepare("
        SELECT id, username, email, password_hash, name, church_name, 
               preferred_language, role, email_verified_at, is_active, avatar_color
        FROM users 
        WHERE email = ? OR username = ?
        LIMIT 1
    ");
    $stmt->execute([$email, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Increment failed attempts
        $attempts['count']++;
        $attempts['first_attempt'] = $attempts['first_attempt'] ?: time();
        $_SESSION[$rateLimitKey] = $attempts;

        logActivity('login_failed', 'Invalid credentials: ' . $email);
        jsonError(__('error_invalid_credentials'));
    }

    // Check if account is active
    if (!$user['is_active']) {
        logActivity('login_failed', 'Inactive account: ' . $email);
        jsonError(__('error_account_deactivated'));
    }

    // Verify password
    if (!verifyPassword($password, $user['password_hash'])) {
        // Increment failed attempts
        $attempts['count']++;
        $attempts['first_attempt'] = $attempts['first_attempt'] ?: time();
        $_SESSION[$rateLimitKey] = $attempts;

        logActivity('login_failed', 'Invalid password for: ' . $email);
        jsonError(__('error_invalid_credentials'));
    }

    // Check email verification
    if (empty($user['email_verified_at'])) {
        logActivity('login_failed', 'Unverified email: ' . $email);
        jsonError(__('error_email_not_verified'));
    }

    // Clear failed attempts on successful login
    unset($_SESSION[$rateLimitKey]);

    // Set user session
    setUserSession($user);

    // Update last login
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);

    // Log successful login
    logActivity('login', 'User logged in', 'user', $user['id']);

    // Determine redirect based on role
    $redirect = $user['role'] === 'admin' ? url('admin/') : url('dashboard/');

    jsonSuccess([
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'role' => $user['role']
        ],
        'redirect' => $redirect
    ], 'Login successful');

} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    jsonError(__('error_generic'), 500);
}
