<?php
/**
 * CrossConnect MY - SMTP2GO Webhook Endpoint
 * Receives email status updates from SMTP2GO
 * 
 * Configure webhook URL in SMTP2GO Dashboard:
 * https://yoursite.com/api/webhook/smtp2go.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/email.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Get raw POST data
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

// Log webhook for debugging
error_log("SMTP2GO Webhook received: " . $payload);

if (!$data) {
    http_response_code(400);
    exit('Invalid JSON');
}

try {
    // SMTP2GO sends events in different formats
    // Common event types: delivered, bounced, opened, clicked, spam_complaint

    $events = isset($data['events']) ? $data['events'] : [$data];

    foreach ($events as $event) {
        $eventType = $event['event'] ?? $event['type'] ?? null;
        $emailId = $event['email_id'] ?? $event['message_id'] ?? null;

        if (!$eventType || !$emailId) {
            continue;
        }

        // Map SMTP2GO event types to our status
        $statusMap = [
            'delivered' => 'delivered',
            'bounce' => 'bounced',
            'hard_bounce' => 'bounced',
            'soft_bounce' => 'bounced',
            'open' => 'opened',
            'opened' => 'opened',
            'click' => 'clicked',
            'clicked' => 'clicked',
            'spam' => 'spam',
            'spam_complaint' => 'spam',
            'unsubscribe' => 'spam',
        ];

        $status = $statusMap[strtolower($eventType)] ?? null;

        if ($status) {
            $metadata = [
                'event_type' => $eventType,
                'timestamp' => $event['timestamp'] ?? date('c'),
                'ip' => $event['ip'] ?? null,
                'user_agent' => $event['user_agent'] ?? null,
                'url' => $event['url'] ?? null, // For click events
            ];

            // Remove null values
            $metadata = array_filter($metadata, fn($v) => $v !== null);

            updateEmailLogStatus($emailId, $status, $metadata);
        }
    }

    // Respond with success
    http_response_code(200);
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("SMTP2GO Webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal error']);
}
