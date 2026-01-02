<?php
/**
 * CrossConnect MY - Report Bug API
 * Handles bug/issue reports from contact page
 * 
 * Security measures:
 * - Rate limiting (5 requests per 10 minutes per IP)
 * - Honeypot field for bot detection
 * - Input sanitization and validation
 * - Message length limits
 * - Uses existing email service (SMTP2GO/Brevo) - no 3rd party form APIs
 */

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/telegram.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => __('error_method_not_allowed')]);
    exit;
}

// ============================================
// SECURITY: Rate Limiting
// ============================================
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimitKey = 'bug_report_' . md5($clientIP);
$maxRequests = 5;
$timeWindow = 600; // 10 minutes

// Check rate limit using session
if (!isset($_SESSION['rate_limits'])) {
    $_SESSION['rate_limits'] = [];
}

$now = time();
$requests = $_SESSION['rate_limits'][$rateLimitKey] ?? [];

// Remove old requests outside time window
$requests = array_filter($requests, function ($timestamp) use ($now, $timeWindow) {
    return ($now - $timestamp) < $timeWindow;
});

if (count($requests) >= $maxRequests) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => __('error_too_many_requests')]);
    exit;
}

// Add current request
$requests[] = $now;
$_SESSION['rate_limits'][$rateLimitKey] = $requests;

// ============================================
// SECURITY: Honeypot Check (Bot Detection)
// ============================================
// If honeypot field is filled, it's likely a bot
$honeypot = isset($_POST['website_url']) ? trim($_POST['website_url']) : '';
if (!empty($honeypot)) {
    // Silently reject - don't let bots know they were caught
    echo json_encode(['success' => true, 'message' => 'Report received']);
    exit;
}

// ============================================
// SECURITY: Input Sanitization & Validation
// ============================================
$subject = isset($_POST['subject']) ? trim(strip_tags($_POST['subject'])) : 'Bugs / Issues';
$message = isset($_POST['message']) ? trim(strip_tags($_POST['message'])) : '';
$reporterEmail = isset($_POST['email']) ? trim(strip_tags($_POST['email'])) : '';

// Limit subject length
$subject = substr($subject, 0, 100);

// Validate message
if (empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('error_describe_issue')]);
    exit;
}

if (strlen($message) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('error_details_too_short')]);
    exit;
}

// Limit message length (prevent abuse)
if (strlen($message) > 5000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('error_message_too_long')]);
    exit;
}

// Validate email if provided
if (!empty($reporterEmail) && !filter_var($reporterEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('error_email_invalid')]);
    exit;
}

// ============================================
// SECURITY: Check for spam patterns
// ============================================
$spamPatterns = [
    '/\b(viagra|cialis|casino|lottery|winner|congratulations|click here|buy now)\b/i',
    '/<script/i',
    '/javascript:/i',
    '/on\w+\s*=/i',  // onclick=, onload=, etc.
];

foreach ($spamPatterns as $pattern) {
    if (preg_match($pattern, $message)) {
        // Silently reject spam
        echo json_encode(['success' => true, 'message' => 'Report received']);
        exit;
    }
}

// ============================================
// Get IP Geolocation (Country & City)
// ============================================
function getIPLocation($ip)
{
    // Skip for localhost/private IPs
    if ($ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
        return ['country' => 'Local', 'city' => 'Development', 'region' => ''];
    }

    try {
        $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city");
        if ($response) {
            $data = json_decode($response, true);
            if ($data && $data['status'] === 'success') {
                return [
                    'country' => $data['country'] ?? 'Unknown',
                    'city' => $data['city'] ?? 'Unknown',
                    'region' => $data['regionName'] ?? ''
                ];
            }
        }
    } catch (Exception $e) {
        // Silently fail - geolocation is non-critical
    }
    return ['country' => 'Unknown', 'city' => 'Unknown', 'region' => ''];
}

$location = getIPLocation($clientIP);
$locationString = $location['city'];
if ($location['region'] && $location['region'] !== $location['city']) {
    $locationString .= ', ' . $location['region'];
}
$locationString .= ', ' . $location['country'];

// ============================================
// Send email via existing email service
// ============================================
try {
    // Get admin email from settings (not hardcoded)
    $adminEmail = getSetting('admin_notification_email', '');

    if (empty($adminEmail)) {
        // Fallback - log but don't fail
        error_log("Bug report received but no admin email configured");
        echo json_encode(['success' => true, 'message' => 'Report received']);
        exit;
    }

    // Format date in Malaysia timezone (already set in database.php)
    $reportedDate = date('j M Y, g:i A') . ' (GMT+8)';

    // Build email content
    $html = getEmailTemplate('admin_notification', [
        'title' => 'Bug / Issue Report',
        'content' => "
            <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
            <p><strong>Details:</strong></p>
            <div style='background: #f5f5f5; padding: 15px; border-radius: 6px; margin: 10px 0;'>
                " . nl2br(htmlspecialchars($message)) . "
            </div>
            " . ($reporterEmail ? "<p><strong>Reporter Email:</strong> " . htmlspecialchars($reporterEmail) . "</p>" : "<p><em>No email provided</em></p>") . "
            <hr style='border: none; border-top: 1px solid #e5e5e5; margin: 20px 0;'>
            <table style='font-size: 13px; color: #666;'>
                <tr><td style='padding: 4px 12px 4px 0;'><strong>Reported:</strong></td><td>" . $reportedDate . "</td></tr>
                <tr><td style='padding: 4px 12px 4px 0;'><strong>IP Address:</strong></td><td>" . htmlspecialchars($clientIP) . "</td></tr>
                <tr><td style='padding: 4px 12px 4px 0;'><strong>Location:</strong></td><td>" . htmlspecialchars($locationString) . "</td></tr>
            </table>
        ",
    ]);

    $textBody = "Bug Report\n\nSubject: $subject\n\nDetails:\n$message\n\n" .
        ($reporterEmail ? "Reporter: $reporterEmail" : "No email provided") .
        "\n\nReported: $reportedDate\nIP: $clientIP\nLocation: $locationString";

    // Send email to admin using existing email service (SMTP2GO/Brevo)
    // If reporter provided email, add it as Reply-To so admin can reply directly
    $replyTo = !empty($reporterEmail) ? $reporterEmail : null;
    $result = sendAdminNotification("Bug Report: $subject", $html, $textBody, $replyTo);

    if ($result['success']) {
        // Send Telegram notification
        sendTelegramNotification(
            "🐛 Bug Report Received",
            "*Subject:* {$subject}\n\n📝 " . substr($message, 0, 200) . (strlen($message) > 200 ? "..." : "") .
            ($reporterEmail ? "\n\n📧 Reply to: {$reporterEmail}" : ""),
            "warning"
        );

        echo json_encode(['success' => true, 'message' => 'Report submitted successfully']);
    } else {
        // Log error but show success to user (report was received)
        error_log("Failed to send bug report email: " . ($result['error'] ?? 'Unknown'));
        echo json_encode(['success' => true, 'message' => 'Report received']);
    }

} catch (Exception $e) {
    error_log("Report bug error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('error_generic')]);
}
