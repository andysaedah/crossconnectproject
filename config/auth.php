<?php
/**
 * CrossConnect MY - Authentication Configuration
 * Handles user sessions, authentication, and authorization
 */

// Include database for activity logging
require_once __DIR__ . '/database.php';

// Ensure session is started with security settings
if (session_status() === PHP_SESSION_NONE) {
    // Secure session cookie settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);

    // Only set secure cookie on HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }

    session_start();
}

/**
 * Set security headers for all responses
 */
function setSecurityHeaders()
{
    if (headers_sent())
        return;

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

// Apply security headers
setSecurityHeaders();

// Security settings
define('AUTH_SESSION_NAME', 'crossconnect_auth');
define('AUTH_COOKIE_LIFETIME', 60 * 60 * 24 * 30); // 30 days
define('PASSWORD_COST', 12);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 15 * 60); // 15 minutes
define('SESSION_IDLE_TIMEOUT', 60 * 60); // 60 minutes idle timeout

/**
 * Check and enforce session timeout
 * Logs out user if idle for too long
 */
function checkSessionTimeout()
{
    if (isset($_SESSION['user']) && isset($_SESSION['last_activity'])) {
        $idleTime = time() - $_SESSION['last_activity'];

        if ($idleTime > SESSION_IDLE_TIMEOUT) {
            // Session expired - clear session directly (can't call clearUserSession yet)
            unset($_SESSION['user']);
            unset($_SESSION['last_activity']);
            session_regenerate_id(true);

            // Redirect to login with timeout message
            if (!headers_sent()) {
                // Use relative path since url() may not be loaded yet
                $basePath = dirname($_SERVER['SCRIPT_NAME']);
                $basePath = rtrim($basePath, '/');
                // Go up directories to find auth/login.php
                $loginPath = preg_replace('#/(admin|dashboard)/.*$#', '', $_SERVER['SCRIPT_NAME']);
                $loginPath = dirname($loginPath) . '/auth/login.php?timeout=1';
                header('Location: ' . $loginPath);
                exit;
            }
        }
    }

    // Update last activity time
    if (isset($_SESSION['user'])) {
        $_SESSION['last_activity'] = time();
    }
}

// Check session timeout on every request
checkSessionTimeout();

/**
 * Hash a password securely
 */
function hashPassword($password)
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
}

/**
 * Verify a password against a hash
 */
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

/**
 * Generate a secure random token
 */
function generateToken($length = 32)
{
    return bin2hex(random_bytes($length));
}

/**
 * Get the currently authenticated user from session
 */
function getCurrentUser()
{
    return $_SESSION['user'] ?? null;
}

/**
 * Check if user is logged in
 */
function isLoggedIn()
{
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Check if current user is admin
 */
function isAdmin()
{
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

/**
 * Check if current user's email is verified
 */
function isEmailVerified()
{
    $user = getCurrentUser();
    return $user && !empty($user['email_verified_at']);
}

/**
 * Set user session after successful login
 */
function setUserSession($user)
{
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'name' => $user['name'],
        'church_name' => $user['church_name'] ?? null,
        'preferred_language' => $user['preferred_language'] ?? 'en',
        'role' => $user['role'],
        'email_verified_at' => $user['email_verified_at'],
        'avatar_color' => $user['avatar_color'] ?? '#0891b2',
        'logged_in_at' => time()
    ];

    // Set language preference
    if (!empty($user['preferred_language'])) {
        $_SESSION['lang'] = $user['preferred_language'];
        if (!headers_sent()) {
            setcookie('lang', $user['preferred_language'], time() + (365 * 24 * 60 * 60), '/');
        }
    }
}

/**
 * Clear user session (logout)
 */
function clearUserSession()
{
    unset($_SESSION['user']);
    session_regenerate_id(true);
}

/**
 * Require authentication - redirect to login if not logged in
 */
function requireAuth()
{
    if (!isLoggedIn()) {
        header('Location: ' . url('auth/login.php'));
        exit;
    }
}

/**
 * Require admin role - redirect if not admin
 */
function requireAdmin()
{
    requireAuth();
    if (!isAdmin()) {
        header('Location: ' . url('dashboard/'));
        exit;
    }
}

/**
 * Require email verification
 */
function requireVerifiedEmail()
{
    requireAuth();
    if (!isEmailVerified()) {
        header('Location: ' . url('auth/verify-pending.php'));
        exit;
    }
}

/**
 * Get user initials for avatar
 */
function getUserInitials($name)
{
    $words = explode(' ', trim($name));
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper(substr($word, 0, 1));
            if (strlen($initials) >= 2)
                break;
        }
    }
    return $initials ?: '?';
}

/**
 * Generate random avatar color
 */
function getRandomAvatarColor()
{
    $colors = [
        '#0891b2', // Cyan
        '#0d9488', // Teal
        '#059669', // Emerald
        '#16a34a', // Green
        '#ca8a04', // Yellow
        '#ea580c', // Orange
        '#dc2626', // Red
        '#db2777', // Pink
        '#9333ea', // Purple
        '#4f46e5', // Indigo
        '#2563eb', // Blue
        '#0284c7', // Sky
    ];
    return $colors[array_rand($colors)];
}

/**
 * Generate CSRF token
 */
function generateCsrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output CSRF hidden field for forms
 */
function csrfField()
{
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}

/**
 * Log an activity for audit trail
 */
function logActivity($action, $description = null, $entityType = null, $entityId = null, $oldValues = null, $newValues = null)
{
    try {
        $pdo = getDbConnection();
        if (!$pdo)
            return;

        $user = getCurrentUser();
        $userId = $user ? $user['id'] : null;

        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, old_values, new_values, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $description,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        // Silently fail - don't break the application for logging
        error_log("Activity log failed: " . $e->getMessage());
    }
}

/**
 * Send JSON response (for AJAX endpoints)
 */
function jsonResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Send error JSON response
 */
function jsonError($message, $statusCode = 400)
{
    jsonResponse(['success' => false, 'error' => $message], $statusCode);
}

/**
 * Send success JSON response
 */
function jsonSuccess($data = [], $message = null)
{
    $response = ['success' => true];
    if ($message)
        $response['message'] = $message;
    if (!empty($data))
        $response['data'] = $data;
    jsonResponse($response);
}
