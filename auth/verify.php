<?php
/**
 * CrossConnect MY - Email Verification Handler
 */

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/email.php';

$token = $_GET['token'] ?? '';
$success = false;
$message = '';

if (!empty($token)) {
    try {
        $pdo = getDbConnection();

        // Find user with this verification token
        $stmt = $pdo->prepare("
            SELECT id, email, name, email_verified_at 
            FROM users 
            WHERE verification_token = ?
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (!empty($user['email_verified_at'])) {
                $message = 'Your email has already been verified. You can log in now.';
                $success = true;
            } else {
                // Verify the email
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET email_verified_at = NOW(), verification_token = NULL 
                    WHERE id = ?
                ");
                $stmt->execute([$user['id']]);

                // Log the verification
                logActivity('email_verified', 'Email verified', 'user', $user['id']);

                // Send welcome email
                sendWelcomeEmail($user['email'], $user['name']);

                $success = true;
                $message = 'Your email has been verified successfully! You can now log in.';
            }
        } else {
            $message = 'Invalid or expired verification link.';
        }
    } catch (PDOException $e) {
        error_log("Verification error: " . $e->getMessage());
        $message = 'An error occurred. Please try again.';
    }
} else {
    $message = 'No verification token provided.';
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage() === 'bm' ? 'ms' : 'en'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - CrossConnect MY</title>
    <link rel="stylesheet" href="<?php echo asset('css/styles.css'); ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo asset('images/favicon.svg'); ?>">
    <style>
        .verify-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ecfeff 0%, #f0f9ff 50%, #faf5ff 100%);
            padding: 20px;
        }

        .verify-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 48px;
            text-align: center;
            max-width: 440px;
        }

        .verify-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }

        .verify-icon.success {
            background: #d1fae5;
            color: #059669;
        }

        .verify-icon.error {
            background: #fee2e2;
            color: #dc2626;
        }

        .verify-icon svg {
            width: 40px;
            height: 40px;
        }

        .verify-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .verify-message {
            color: var(--color-text-light);
            margin-bottom: 32px;
            line-height: 1.6;
        }

        .verify-btn {
            display: inline-block;
            padding: 14px 32px;
            background: var(--color-primary);
            color: white;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }

        .verify-btn:hover {
            background: var(--color-primary-dark);
            transform: translateY(-1px);
        }
    </style>
</head>

<body>
    <div class="verify-page">
        <div class="verify-card">
            <div class="verify-icon <?php echo $success ? 'success' : 'error'; ?>">
                <?php if ($success): ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 6L9 17l-5-5"></path>
                    </svg>
                <?php else: ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                <?php endif; ?>
            </div>
            <h1 class="verify-title"><?php echo $success ? 'Email Verified!' : 'Verification Failed'; ?></h1>
            <p class="verify-message"><?php echo htmlspecialchars($message); ?></p>
            <a href="<?php echo url('auth/login.php'); ?>" class="verify-btn">
                <?php echo $success ? 'Go to Login' : 'Back to Login'; ?>
            </a>
        </div>
    </div>
</body>

</html>