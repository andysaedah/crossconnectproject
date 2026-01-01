<?php
/**
 * CrossConnect MY - Report Amendment API
 * Handles reports of incorrect church information
 * 
 * Security measures:
 * - Rate limiting (5 requests per 10 minutes per IP)
 * - Honeypot field for bot detection
 * - Input sanitization and validation  
 * - Message length limits
 * - Spam pattern detection
 * - Uses existing email service (SMTP2GO/Brevo)
 */

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/language.php';
require_once __DIR__ . '/../config/email.php';

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
$rateLimitKey = 'amendment_report_' . md5($clientIP);
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
$honeypot = isset($_POST['website_url']) ? trim($_POST['website_url']) : '';
if (!empty($honeypot)) {
    // Silently reject - don't let bots know they were caught
    echo json_encode(['success' => true, 'message' => __('amendment_reported_success')]);
    exit;
}

// ============================================
// SECURITY: Input Sanitization & Validation
// ============================================
$churchId = isset($_POST['church_id']) ? intval($_POST['church_id']) : 0;
$notes = isset($_POST['notes']) ? trim(strip_tags($_POST['notes'])) : '';
$reporterEmail = isset($_POST['email']) ? trim(strip_tags($_POST['email'])) : '';

// Validation
if ($churchId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('invalid_church')]);
    exit;
}

if (empty($notes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('amendment_notes_required')]);
    exit;
}

if (strlen($notes) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('amendment_notes_too_short')]);
    exit;
}

// Limit notes length (prevent abuse)
if (strlen($notes) > 5000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('error_notes_too_long')]);
    exit;
}

if (!empty($reporterEmail) && !filter_var($reporterEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('invalid_email')]);
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
    if (preg_match($pattern, $notes)) {
        // Silently reject spam
        echo json_encode(['success' => true, 'message' => __('amendment_reported_success')]);
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
// Process Amendment Report
// ============================================
try {
    $pdo = getDbConnection();

    // Check if church exists
    $stmt = $pdo->prepare("SELECT id, name, status FROM churches WHERE id = ?");
    $stmt->execute([$churchId]);
    $church = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$church) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => __('church_not_found')]);
        exit;
    }

    // Update church status to needs_amendment
    $stmt = $pdo->prepare("
        UPDATE churches 
        SET status = 'needs_amendment',
            amendment_notes = ?,
            amendment_reporter_email = ?,
            amendment_date = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$notes, $reporterEmail ?: null, $churchId]);

    // Log the activity
    if (function_exists('logActivity')) {
        logActivity('amendment_reported', 'church', $churchId, 'Amendment reported for: ' . $church['name']);
    }

    // ============================================
    // Send email notification to admin
    // ============================================
    $adminUrl = getBaseUrl() . '/admin/churches.php';
    $subject = "Amendment Request: " . $church['name'];

    // Format date in Malaysia timezone (already set in database.php)
    $reportedDate = date('j M Y, g:i A') . ' (GMT+8)';

    $html = getEmailTemplate('admin_notification', [
        'title' => 'Church Amendment Request',
        'content' => "
            <p><strong>Church:</strong> " . htmlspecialchars($church['name']) . "</p>
            <p><strong>Church ID:</strong> " . $churchId . "</p>
            <p><strong>Reporter's Notes:</strong></p>
            <div style='background: #f5f5f5; padding: 15px; border-radius: 6px; margin: 10px 0;'>
                " . nl2br(htmlspecialchars($notes)) . "
            </div>
            " . ($reporterEmail ? "<p><strong>Reporter Email:</strong> " . htmlspecialchars($reporterEmail) . "</p>" : "<p><em>No email provided</em></p>") . "
            <hr style='border: none; border-top: 1px solid #e5e5e5; margin: 20px 0;'>
            <table style='font-size: 13px; color: #666;'>
                <tr><td style='padding: 4px 12px 4px 0;'><strong>Reported:</strong></td><td>" . $reportedDate . "</td></tr>
                <tr><td style='padding: 4px 12px 4px 0;'><strong>IP Address:</strong></td><td>" . htmlspecialchars($clientIP) . "</td></tr>
                <tr><td style='padding: 4px 12px 4px 0;'><strong>Location:</strong></td><td>" . htmlspecialchars($locationString) . "</td></tr>
            </table>
        ",
        'action_url' => $adminUrl,
        'action_text' => 'Review in Admin Panel',
    ]);

    $textBody = "Amendment Request\n\nChurch: " . $church['name'] . "\n\nNotes:\n" . $notes . "\n\n" .
        ($reporterEmail ? "Reporter: $reporterEmail" : "No email provided") .
        "\n\nReported: $reportedDate\nIP: $clientIP\nLocation: $locationString";

    // If reporter provided email, add it as Reply-To so admin can reply directly
    $replyTo = !empty($reporterEmail) ? $reporterEmail : null;
    $emailResult = sendAdminNotification($subject, $html, $textBody, $replyTo);

    if (!$emailResult['success']) {
        // Log email failure but don't fail the request
        error_log("Failed to send admin notification for amendment: " . ($emailResult['error'] ?? 'Unknown error'));
    }

    echo json_encode([
        'success' => true,
        'message' => __('amendment_reported_success')
    ]);

} catch (Exception $e) {
    error_log("Report amendment error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('something_went_wrong')]);
}
