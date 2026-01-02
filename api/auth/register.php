<?php
/**
 * CrossConnect MY - Register API
 * Handles user registration via AJAX
 */

require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/email.php';
require_once __DIR__ . '/../../config/telegram.php';

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError(__('error_method_not_allowed'), 405);
}

// Verify CSRF token
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    jsonError(__('error_invalid_security_token'));
}

// Honeypot check - bots will fill this hidden field
if (!empty($_POST['website_url'])) {
    // Bot detected - silently reject with fake success to confuse bots
    error_log("Bot registration attempt blocked from: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    jsonSuccess([], 'Account created! Please check your email to verify your account.');
}

// Rate limiting for registration - prevent mass account creation
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimitKey = 'reg_attempts_' . md5($ip);
$attempts = $_SESSION[$rateLimitKey] ?? ['count' => 0, 'first_attempt' => time()];

// Reset if 1 hour has passed
if (time() - $attempts['first_attempt'] > 3600) {
    $attempts = ['count' => 0, 'first_attempt' => time()];
}

// Allow 5 registration attempts per hour
if ($attempts['count'] >= 5) {
    $remainingMinutes = ceil((3600 - (time() - $attempts['first_attempt'])) / 60);
    jsonError(str_replace('{minutes}', $remainingMinutes, __('error_too_many_registrations')));
}

// Increment attempt counter
$attempts['count']++;
$_SESSION[$rateLimitKey] = $attempts;

$name = trim($_POST['name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$churchName = trim($_POST['church_name'] ?? '');

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = __('error_fullname_required');
} elseif (strlen($name) < 2) {
    $errors[] = __('error_name_too_short');
}

if (empty($username)) {
    $errors[] = __('error_username_required');
} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors[] = __('error_username_invalid');
} elseif (strlen($username) < 3) {
    $errors[] = __('error_username_too_short');
}

if (empty($email)) {
    $errors[] = __('error_email_required');
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = __('error_email_invalid');
} else {
    // Check for disposable email domains
    $emailDomain = strtolower(substr(strrchr($email, "@"), 1));
    $disposableListPath = __DIR__ . '/../../config/disposable_emails.txt';

    if (file_exists($disposableListPath)) {
        $disposableDomains = file($disposableListPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (in_array($emailDomain, $disposableDomains)) {
            $errors[] = __('error_disposable_email');
        }
    }
}

if (empty($password)) {
    $errors[] = __('error_password_required');
} elseif (strlen($password) < 8) {
    $errors[] = __('error_password_too_short');
}

if (!empty($errors)) {
    jsonError(implode('. ', $errors));
}

// Get database connection
$pdo = getDbConnection();
if (!$pdo) {
    jsonError(__('error_database_failed'), 500);
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonError(__('error_email_already_registered'));
    }

    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        jsonError(__('error_username_taken'));
    }

    // Generate verification token
    $verificationToken = generateToken();

    // Hash password
    $passwordHash = hashPassword($password);

    // Get random avatar color
    $avatarColor = getRandomAvatarColor();

    // Insert user
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password_hash, name, church_name, verification_token, avatar_color, preferred_language)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $username,
        $email,
        $passwordHash,
        $name,
        $churchName ?: null,
        $verificationToken,
        $avatarColor,
        getCurrentLanguage()
    ]);

    $userId = $pdo->lastInsertId();

    // Log registration
    logActivity('register', 'New user registered', 'user', $userId);

    // Send Telegram notification
    sendTelegramNotification(
        "New User Registration",
        "👤 *{$name}*\n📧 {$email}\n🏷️ @{$username}" . ($churchName ? "\n⛪ {$churchName}" : ""),
        "success"
    );

    // Send verification email
    $emailResult = sendVerificationEmail($email, $name, $verificationToken);

    if (!$emailResult['success']) {
        error_log("Failed to send verification email to: $email");
    }

    // Set partial session for verification pending page
    $_SESSION['pending_verification'] = [
        'email' => $email,
        'name' => $name
    ];

    jsonSuccess([
        'user_id' => $userId,
        'email' => $email
    ], 'Account created! Please check your email to verify your account.');

} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    jsonError(__('error_generic'), 500);
}
