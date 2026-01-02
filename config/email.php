<?php
/**
 * CrossConnect MY - Email Service Configuration
 * Supports multiple email providers with configurable fallback:
 * - SMTP2GO (Always Primary)
 * - Brevo (Optional Fallback #1)
 * - PHP Mail (Optional Fallback #2 / Last resort)
 * 
 * Settings are managed via Admin > API Integration
 */

require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/paths.php';

/**
 * Get email service configuration from database
 */
function getEmailConfig()
{
    return [
        'admin_email' => getSetting('admin_notification_email', 'admin@crossconnect.my'),
        // Fallback settings - which providers are enabled
        'enable_brevo_fallback' => getSetting('enable_brevo_fallback', '0') === '1',
        'enable_phpmail_fallback' => getSetting('enable_phpmail_fallback', '0') === '1',
        'smtp2go' => [
            'api_key' => getSetting('smtp2go_api_key', ''),
            'sender_email' => getSetting('smtp2go_sender_email', 'noreply@crossconnect.my'),
            'sender_name' => getSetting('smtp2go_sender_name', 'CrossConnect MY'),
        ],
        'brevo' => [
            'api_key' => getSetting('brevo_api_key', ''),
            'sender_email' => getSetting('brevo_sender_email', 'noreply@crossconnect.my'),
            'sender_name' => getSetting('brevo_sender_name', 'CrossConnect MY'),
        ],
    ];
}

/**
 * Get SMTP2GO settings (backward compatibility)
 */
function getSmtp2goSettings()
{
    $config = getEmailConfig();
    return $config['smtp2go'];
}

/**
 * Log email to database
 * 
 * @param string $recipient Email recipient
 * @param string $subject Email subject
 * @param string $provider Provider name
 * @param string $status Initial status
 * @param string|null $messageId Provider message ID
 * @param string|null $error Error message if failed
 * @return int|false Log ID or false on failure
 */
function logEmailSent($recipient, $subject, $provider, $status = 'sent', $messageId = null, $error = null)
{
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            INSERT INTO email_logs (recipient, subject, provider, status, message_id, error_message, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$recipient, $subject, $provider, $status, $messageId, $error]);
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("Failed to log email: " . $e->getMessage());
        return false;
    }
}

/**
 * Update email log status (called by webhooks)
 * 
 * @param string $messageId Provider message ID
 * @param string $status New status
 * @param array $metadata Additional data
 * @return bool Success
 */
function updateEmailLogStatus($messageId, $status, $metadata = [])
{
    try {
        $pdo = getDbConnection();

        $updates = ['status = ?', 'updated_at = NOW()'];
        $params = [$status];

        if ($status === 'opened') {
            $updates[] = 'opened_count = opened_count + 1';
        } elseif ($status === 'clicked') {
            $updates[] = 'clicked_count = clicked_count + 1';
        }

        if (!empty($metadata)) {
            $updates[] = 'metadata = JSON_MERGE_PATCH(COALESCE(metadata, \'{}\'), ?)';
            $params[] = json_encode($metadata);
        }

        $params[] = $messageId;

        $stmt = $pdo->prepare("
            UPDATE email_logs SET " . implode(', ', $updates) . " WHERE message_id = ?
        ");
        return $stmt->execute($params);
    } catch (Exception $e) {
        error_log("Failed to update email log: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email with configurable fallback between providers
 * 
 * Fallback chain (SMTP2GO is always primary):
 * - Option 1: SMTP2GO only
 * - Option 2: SMTP2GO -> Brevo
 * - Option 3: SMTP2GO -> Brevo -> PHP Mail
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $htmlBody HTML content
 * @param string $textBody Plain text content (optional)
 * @param string|null $replyTo Optional Reply-To email address
 * @return array Result with success status
 */
function sendEmail($to, $subject, $htmlBody, $textBody = null, $replyTo = null)
{
    $config = getEmailConfig();

    // Build provider chain based on settings
    // SMTP2GO is always first (primary)
    $providers = ['smtp2go'];

    // Add Brevo as fallback if enabled
    if ($config['enable_brevo_fallback']) {
        $providers[] = 'brevo';
    }

    // Add PHP Mail as last resort if enabled
    if ($config['enable_phpmail_fallback']) {
        $providers[] = 'phpmail';
    }

    $lastError = 'No email providers configured';
    $attemptedProviders = [];

    foreach ($providers as $p) {
        $attemptedProviders[] = $p;
        $result = sendViaProvider($p, $to, $subject, $htmlBody, $textBody, $config, $replyTo);

        if ($result['success']) {
            return $result;
        }

        $lastError = $result['error'] ?? 'Unknown error';

        // Only log fallback message if there are more providers to try
        if (count($providers) > count($attemptedProviders)) {
            error_log("Email provider '$p' failed: $lastError - Trying next provider...");
        }
    }

    // All providers failed
    error_log("All email providers failed. Last error: $lastError");
    return ['success' => false, 'error' => $lastError];
}

/**
 * Send email via specific provider
 * 
 * @param string $provider Provider name (smtp2go, brevo, phpmail)
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $htmlBody HTML content
 * @param string $textBody Plain text content (optional)
 * @param array $config Email configuration
 * @param string|null $replyTo Reply-To email address (optional)
 * @return array Result with success status
 */
function sendViaProvider($provider, $to, $subject, $htmlBody, $textBody = null, $config = null, $replyTo = null)
{
    if (!$config) {
        $config = getEmailConfig();
    }

    switch ($provider) {
        case 'smtp2go':
            return sendViaSmtp2go($to, $subject, $htmlBody, $textBody, $config['smtp2go'], $replyTo);
        case 'brevo':
            return sendViaBrevo($to, $subject, $htmlBody, $textBody, $config['brevo'], $replyTo);
        case 'phpmail':
            return sendViaPHPMail($to, $subject, $htmlBody, $textBody, $config, $replyTo);
        default:
            return ['success' => false, 'error' => 'Unknown provider: ' . $provider];
    }
}

/**
 * Send email via SMTP2GO API
 */
function sendViaSmtp2go($to, $subject, $htmlBody, $textBody = null, $settings = null, $replyTo = null)
{
    if (!$settings) {
        $settings = getSmtp2goSettings();
    }

    $apiKey = $settings['api_key'];

    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'SMTP2GO API key not configured'];
    }

    $data = [
        'api_key' => $apiKey,
        'to' => [$to],
        'sender' => $settings['sender_name'] . ' <' . $settings['sender_email'] . '>',
        'subject' => $subject,
        'html_body' => $htmlBody,
    ];

    if ($textBody) {
        $data['text_body'] = $textBody;
    }

    // Add Reply-To header if provided
    if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $data['custom_headers'] = [
            ['header' => 'Reply-To', 'value' => $replyTo]
        ];
    }

    $ch = curl_init('https://api.smtp2go.com/v3/email/send');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        logEmailSent($to, $subject, 'smtp2go', 'failed', null, 'cURL error: ' . $error);
        return ['success' => false, 'error' => 'SMTP2GO cURL error: ' . $error];
    }

    $result = json_decode($response, true);

    if ($httpCode === 200 && isset($result['data']['succeeded']) && $result['data']['succeeded'] > 0) {
        // Extract message ID from response
        $messageId = $result['data']['email_id'] ?? null;
        logEmailSent($to, $subject, 'smtp2go', 'sent', $messageId);
        return ['success' => true, 'provider' => 'smtp2go', 'message_id' => $messageId];
    }

    $errorMsg = $result['data']['error'] ?? 'Unknown error';
    logEmailSent($to, $subject, 'smtp2go', 'failed', null, $errorMsg);
    return ['success' => false, 'error' => 'SMTP2GO: ' . $errorMsg];
}

/**
 * Send email via Brevo (Sendinblue) API
 */
function sendViaBrevo($to, $subject, $htmlBody, $textBody = null, $settings = null, $replyTo = null)
{
    if (!$settings) {
        $config = getEmailConfig();
        $settings = $config['brevo'];
    }

    $apiKey = $settings['api_key'];

    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'Brevo API key not configured'];
    }

    $data = [
        'sender' => [
            'name' => $settings['sender_name'],
            'email' => $settings['sender_email'],
        ],
        'to' => [
            ['email' => $to]
        ],
        'subject' => $subject,
        'htmlContent' => $htmlBody,
    ];

    if ($textBody) {
        $data['textContent'] = $textBody;
    }

    // Add Reply-To if provided
    if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $data['replyTo'] = ['email' => $replyTo];
    }

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        logEmailSent($to, $subject, 'brevo', 'failed', null, 'cURL error: ' . $error);
        return ['success' => false, 'error' => 'Brevo cURL error: ' . $error];
    }

    $result = json_decode($response, true);

    // Brevo returns 201 on success with messageId
    if ($httpCode === 201 || $httpCode === 200) {
        $messageId = $result['messageId'] ?? null;
        logEmailSent($to, $subject, 'brevo', 'sent', $messageId);
        return ['success' => true, 'provider' => 'brevo', 'message_id' => $messageId];
    }

    $errorMsg = $result['message'] ?? 'Unknown error (HTTP ' . $httpCode . ')';
    logEmailSent($to, $subject, 'brevo', 'failed', null, $errorMsg);
    return ['success' => false, 'error' => 'Brevo: ' . $errorMsg];
}

/**
 * Send email via PHP mail() function (last resort)
 */
function sendViaPHPMail($to, $subject, $htmlBody, $textBody = null, $config = null, $replyTo = null)
{
    if (!$config) {
        $config = getEmailConfig();
    }

    // Use SMTP2GO sender settings as default for PHP mail
    $senderEmail = $config['smtp2go']['sender_email'] ?: 'noreply@crossconnect.my';
    $senderName = $config['smtp2go']['sender_name'] ?: 'CrossConnect MY';

    // Use replyTo if provided, otherwise use sender email
    $replyToEmail = ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) ? $replyTo : $senderEmail;

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $senderName . ' <' . $senderEmail . '>',
        'Reply-To: ' . $replyToEmail,
        'X-Mailer: PHP/' . phpversion(),
    ];

    $headerString = implode("\r\n", $headers);

    // Use error suppression and check result
    $result = @mail($to, $subject, $htmlBody, $headerString);

    if ($result) {
        logEmailSent($to, $subject, 'phpmail', 'sent');
        return ['success' => true, 'provider' => 'phpmail'];
    }

    logEmailSent($to, $subject, 'phpmail', 'failed', null, 'PHP mail() function failed');
    return ['success' => false, 'error' => 'PHP mail() function failed'];
}

/**
 * Send notification email to admin
 * 
 * @param string $subject Email subject
 * @param string $htmlBody HTML email content
 * @param string|null $textBody Plain text content (optional)
 * @param string|null $replyTo Reply-To email address (optional)
 */
function sendAdminNotification($subject, $htmlBody, $textBody = null, $replyTo = null)
{
    $config = getEmailConfig();
    $adminEmail = $config['admin_email'];

    if (empty($adminEmail)) {
        error_log("Admin notification skipped: No admin email configured");
        return ['success' => false, 'error' => 'No admin email configured'];
    }

    return sendEmail($adminEmail, $subject, $htmlBody, $textBody, $replyTo);
}

/**
 * Send verification email
 */
function sendVerificationEmail($email, $name, $token)
{
    $verifyUrl = getBaseUrl() . '/auth/verify.php?token=' . urlencode($token);

    $subject = __('email_verify_subject') !== 'email_verify_subject'
        ? __('email_verify_subject')
        : 'Verify your CrossConnect MY account';

    $html = getEmailTemplate('verification', [
        'name' => $name,
        'verify_url' => $verifyUrl,
    ]);

    return sendEmail($email, $subject, $html);
}

/**
 * Send password reset email
 */
function sendPasswordResetEmail($email, $name, $token)
{
    $resetUrl = getBaseUrl() . '/auth/reset-password.php?token=' . urlencode($token);

    $subject = 'Reset your CrossConnect MY password';

    $html = getEmailTemplate('password_reset', [
        'name' => $name,
        'reset_url' => $resetUrl,
    ]);

    return sendEmail($email, $subject, $html);
}

/**
 * Send welcome email after verification
 */
function sendWelcomeEmail($email, $name)
{
    $loginUrl = getBaseUrl() . '/auth/login.php';

    $subject = 'Welcome to CrossConnect MY!';

    $html = getEmailTemplate('welcome', [
        'name' => $name,
        'login_url' => $loginUrl,
    ]);

    return sendEmail($email, $subject, $html);
}

/**
 * Get email template
 */
function getEmailTemplate($template, $variables = [])
{
    $templates = [
        'verification' => '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;background:#f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;padding:20px;">
        <tr>
            <td style="background:#0891b2;padding:30px;text-align:center;border-radius:8px 8px 0 0;">
                <h1 style="color:white;margin:0;font-size:24px;">CrossConnect MY</h1>
            </td>
        </tr>
        <tr>
            <td style="background:white;padding:40px 30px;">
                <!-- English Version -->
                <h2 style="color:#333;margin:0 0 15px;font-size:20px;">🇬🇧 Verify Your Email</h2>
                <p style="color:#666;line-height:1.6;margin:0 0 15px;">
                    Shalom {name},<br><br>
                    Thank you for registering with CrossConnect MY! Please verify your email address by clicking the button below:
                </p>
                <p style="text-align:center;margin:25px 0;">
                    <a href="{verify_url}" style="background:#0891b2;color:white;padding:14px 30px;text-decoration:none;border-radius:6px;display:inline-block;font-weight:bold;">
                        Verify Email Address
                    </a>
                </p>
                <p style="color:#999;font-size:13px;margin:0 0 25px;">
                    If you didn\'t create an account, you can safely ignore this email.
                </p>
                
                <!-- Divider -->
                <hr style="border:none;border-top:1px solid #e5e5e5;margin:25px 0;">
                
                <!-- Bahasa Malaysia Version -->
                <h2 style="color:#333;margin:0 0 15px;font-size:20px;">🇲🇾 Sahkan E-mel Anda</h2>
                <p style="color:#666;line-height:1.6;margin:0 0 15px;">
                    Shalom {name},<br><br>
                    Terima kasih kerana mendaftar dengan CrossConnect MY! Sila sahkan alamat e-mel anda dengan mengklik butang di bawah:
                </p>
                <p style="text-align:center;margin:25px 0;">
                    <a href="{verify_url}" style="background:#0891b2;color:white;padding:14px 30px;text-decoration:none;border-radius:6px;display:inline-block;font-weight:bold;">
                        Sahkan Alamat E-mel
                    </a>
                </p>
                <p style="color:#999;font-size:13px;margin:0;">
                    Jika anda tidak membuat akaun, anda boleh mengabaikan e-mel ini.
                </p>
            </td>
        </tr>
        <tr>
            <td style="background:#f9fafb;padding:15px;text-align:center;color:#999;font-size:11px;border-radius:0 0 8px 8px;">
                <p style="margin:0 0 5px;">Button not working? Copy and paste this link: | Butang tidak berfungsi? Salin dan tampal pautan ini:</p>
                <p style="margin:0;word-break:break-all;"><a href="{verify_url}" style="color:#0891b2;">{verify_url}</a></p>
            </td>
        </tr>
        <tr>
            <td style="padding:20px;text-align:center;color:#999;font-size:12px;">
                © ' . date('Y') . ' CrossConnect MY. A CoreFLAME Community Project.
            </td>
        </tr>
    </table>
</body>
</html>',

        'password_reset' => '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;background:#f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;padding:20px;">
        <tr>
            <td style="background:#0891b2;padding:30px;text-align:center;border-radius:8px 8px 0 0;">
                <h1 style="color:white;margin:0;font-size:24px;">CrossConnect MY</h1>
            </td>
        </tr>
        <tr>
            <td style="background:white;padding:40px 30px;">
                <!-- English Version -->
                <h2 style="color:#333;margin:0 0 15px;font-size:20px;">🇬🇧 Reset Your Password</h2>
                <p style="color:#666;line-height:1.6;margin:0 0 15px;">
                    Shalom {name},<br><br>
                    We received a request to reset your password. Click the button below to create a new password:
                </p>
                <p style="text-align:center;margin:25px 0;">
                    <a href="{reset_url}" style="background:#0891b2;color:white;padding:14px 30px;text-decoration:none;border-radius:6px;display:inline-block;font-weight:bold;">
                        Reset Password
                    </a>
                </p>
                <p style="color:#999;font-size:13px;margin:0 0 25px;">
                    This link expires in 1 hour. If you didn\'t request this, you can safely ignore this email.
                </p>
                
                <!-- Divider -->
                <hr style="border:none;border-top:1px solid #e5e5e5;margin:25px 0;">
                
                <!-- Bahasa Malaysia Version -->
                <h2 style="color:#333;margin:0 0 15px;font-size:20px;">🇲🇾 Tetapkan Semula Kata Laluan</h2>
                <p style="color:#666;line-height:1.6;margin:0 0 15px;">
                    Shalom {name},<br><br>
                    Kami menerima permintaan untuk menetapkan semula kata laluan anda. Klik butang di bawah untuk membuat kata laluan baharu:
                </p>
                <p style="text-align:center;margin:25px 0;">
                    <a href="{reset_url}" style="background:#0891b2;color:white;padding:14px 30px;text-decoration:none;border-radius:6px;display:inline-block;font-weight:bold;">
                        Tetapkan Semula Kata Laluan
                    </a>
                </p>
                <p style="color:#999;font-size:13px;margin:0;">
                    Pautan ini luput dalam 1 jam. Jika anda tidak membuat permintaan ini, anda boleh mengabaikan e-mel ini.
                </p>
            </td>
        </tr>
        <tr>
            <td style="background:#f9fafb;padding:15px;text-align:center;color:#999;font-size:11px;border-radius:0 0 8px 8px;">
                <p style="margin:0 0 5px;">Button not working? Copy and paste this link: | Butang tidak berfungsi? Salin dan tampal pautan ini:</p>
                <p style="margin:0;word-break:break-all;"><a href="{reset_url}" style="color:#0891b2;">{reset_url}</a></p>
            </td>
        </tr>
        <tr>
            <td style="padding:20px;text-align:center;color:#999;font-size:12px;">
                © ' . date('Y') . ' CrossConnect MY. A CoreFLAME Community Project.
            </td>
        </tr>
    </table>
</body>
</html>',


        'welcome' => '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;background:#f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;padding:20px;">
        <tr>
            <td style="background:#0891b2;padding:30px;text-align:center;border-radius:8px 8px 0 0;">
                <h1 style="color:white;margin:0;font-size:24px;">CrossConnect MY</h1>
            </td>
        </tr>
        <tr>
            <td style="background:white;padding:40px 30px;">
                <!-- English Version -->
                <h2 style="color:#333;margin:0 0 15px;font-size:20px;">🇬🇧 Welcome to CrossConnect MY!</h2>
                <p style="color:#666;line-height:1.6;margin:0 0 15px;">
                    Shalom {name},<br><br>
                    Your email has been verified and your account is now active! You can now:
                </p>
                <ul style="color:#666;line-height:1.8;padding-left:20px;margin:0 0 15px;">
                    <li>Add and manage your church listings</li>
                    <li>Create and promote church events</li>
                    <li>Connect with the Malaysian Christian community</li>
                </ul>
                <p style="text-align:center;margin:25px 0;">
                    <a href="{login_url}" style="background:#0891b2;color:white;padding:14px 30px;text-decoration:none;border-radius:6px;display:inline-block;font-weight:bold;">
                        Go to Dashboard
                    </a>
                </p>
                
                <!-- Divider -->
                <hr style="border:none;border-top:1px solid #e5e5e5;margin:25px 0;">
                
                <!-- Bahasa Malaysia Version -->
                <h2 style="color:#333;margin:0 0 15px;font-size:20px;">🇲🇾 Selamat Datang ke CrossConnect MY!</h2>
                <p style="color:#666;line-height:1.6;margin:0 0 15px;">
                    Shalom {name},<br><br>
                    E-mel anda telah disahkan dan akaun anda kini aktif! Anda kini boleh:
                </p>
                <ul style="color:#666;line-height:1.8;padding-left:20px;margin:0 0 15px;">
                    <li>Tambah dan urus senarai gereja anda</li>
                    <li>Cipta dan promosi acara gereja</li>
                    <li>Berhubung dengan komuniti Kristian Malaysia</li>
                </ul>
                <p style="text-align:center;margin:25px 0;">
                    <a href="{login_url}" style="background:#0891b2;color:white;padding:14px 30px;text-decoration:none;border-radius:6px;display:inline-block;font-weight:bold;">
                        Pergi ke Papan Pemuka
                    </a>
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding:20px;text-align:center;color:#999;font-size:12px;">
                © ' . date('Y') . ' CrossConnect MY. A CoreFLAME Community Project.
            </td>
        </tr>
    </table>
</body>
</html>',

        'admin_notification' => '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;background:#f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;padding:20px;">
        <tr>
            <td style="background:#dc2626;padding:30px;text-align:center;border-radius:8px 8px 0 0;">
                <h1 style="color:white;margin:0;font-size:24px;">CrossConnect MY - Admin</h1>
            </td>
        </tr>
        <tr>
            <td style="background:white;padding:40px 30px;border-radius:0 0 8px 8px;">
                <h2 style="color:#333;margin:0 0 20px;">{title}</h2>
                <div style="color:#666;line-height:1.6;margin:0 0 20px;">
                    {content}
                </div>
                <p style="text-align:center;margin:30px 0;">
                    <a href="{action_url}" style="background:#dc2626;color:white;padding:14px 30px;text-decoration:none;border-radius:6px;display:inline-block;font-weight:bold;">
                        {action_text}
                    </a>
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding:20px;text-align:center;color:#999;font-size:12px;">
                © ' . date('Y') . ' CrossConnect MY. A CoreFLAME Community Project.
            </td>
        </tr>
    </table>
</body>
</html>',
    ];

    $html = $templates[$template] ?? '';

    // Replace variables
    // Note: 'content' is allowed to contain HTML (for admin notifications)
    // Other variables are escaped for security
    foreach ($variables as $key => $value) {
        if ($key === 'content') {
            // Allow HTML in content (already sanitized by caller)
            $html = str_replace('{' . $key . '}', $value, $html);
        } else {
            // Escape other variables for security
            $html = str_replace('{' . $key . '}', htmlspecialchars($value), $html);
        }
    }

    // Remove any unreplaced action button if no action_url provided (only for admin_notification template)
    // Note: verification and password_reset templates use verify_url/reset_url, not action_url
    if ($template === 'admin_notification' && (!isset($variables['action_url']) || empty($variables['action_url']))) {
        $html = preg_replace('/<p style="text-align:center;margin:30px 0;">.*?<\/p>/s', '', $html);
    }

    return $html;
}
