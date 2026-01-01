<?php
/**
 * CrossConnect MY - Verification Pending Page
 */

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/auth.php';

$pending = $_SESSION['pending_verification'] ?? null;
$email = $pending['email'] ?? 'your email';
$name = $pending['name'] ?? 'there';
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage() === 'bm' ? 'ms' : 'en'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email - CrossConnect MY</title>
    <link rel="stylesheet" href="<?php echo asset('css/styles.css'); ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo asset('images/favicon.svg'); ?>">
    <style>
        .pending-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ecfeff 0%, #f0f9ff 50%, #faf5ff 100%);
            padding: 20px;
        }

        .pending-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 48px;
            text-align: center;
            max-width: 480px;
        }

        .pending-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }

        .pending-icon svg {
            width: 40px;
            height: 40px;
        }

        .pending-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .pending-message {
            color: var(--color-text-light);
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .pending-email {
            display: inline-block;
            padding: 8px 16px;
            background: var(--color-bg);
            border-radius: 6px;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 24px;
        }

        .pending-btn {
            display: inline-block;
            padding: 12px 24px;
            background: var(--color-primary);
            color: white;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            margin: 6px;
            transition: all 0.2s;
        }

        .pending-btn:hover {
            background: var(--color-primary-dark);
        }

        .pending-btn.secondary {
            background: white;
            color: var(--color-text);
            border: 1px solid var(--color-border);
        }

        .pending-btn.secondary:hover {
            background: var(--color-bg);
        }

        .pending-note {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--color-border);
            font-size: 0.875rem;
            color: var(--color-text-light);
        }
    </style>
</head>

<body>
    <div class="pending-page">
        <div class="pending-card">
            <div class="pending-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
            </div>
            <h1 class="pending-title">Check Your Email</h1>
            <p class="pending-message">
                Hi <?php echo htmlspecialchars($name); ?>! We've sent a verification link to:
            </p>
            <div class="pending-email"><?php echo htmlspecialchars($email); ?></div>
            <p class="pending-message">
                Click the link in the email to verify your account and start using CrossConnect MY.
            </p>
            <div>
                <a href="<?php echo url('auth/login.php'); ?>" class="pending-btn secondary">Back to Login</a>
            </div>
            <div class="pending-note">
                <p>Didn't receive the email? Check your spam folder or <a href="#"
                        onclick="resendEmail(); return false;">click here to resend</a>.</p>
            </div>
        </div>
    </div>

    <script>
        function resendEmail() {
            // TODO: Implement resend verification email
            alert('Verification email resent! Please check your inbox.');
        }
    </script>
</body>

</html>