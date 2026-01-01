<?php
/**
 * CrossConnect MY - Brevo Webhook Endpoint
 * Receives email status updates from Brevo (Sendinblue)
 * 
 * Configure webhook URL in Brevo Dashboard:
 * https://yoursite.com/api/webhook/brevo.php
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
error_log("Brevo Webhook received: " . $payload);

if (!$data) {
    http_response_code(400);
    exit('Invalid JSON');
}

try {
    // Brevo webhook format
    // https://developers.brevo.com/docs/transactional-webhooks

    $event = $data['event'] ?? null;
    $messageId = $data['message-id'] ?? $data['messageId'] ?? null;

    if (!$event || !$messageId) {
        http_response_code(200);
        echo json_encode(['success' => true, 'note' => 'No event or message ID']);
        exit;
    }

    // Map Brevo event types to our status
    $statusMap = [
        'delivered' => 'delivered',
        'soft_bounce' => 'bounced',
        'hard_bounce' => 'bounced',
        'blocked' => 'bounced',
        'invalid_email' => 'bounced',
        'deferred' => 'queued',
        'opened' => 'opened',
        'unique_opened' => 'opened',
        'click' => 'clicked',
        'spam' => 'spam',
        'complaint' => 'spam',
        'unsubscribed' => 'spam',
    ];

    $status = $statusMap[strtolower($event)] ?? null;

    if ($status) {
        $metadata = [
            'event' => $event,
            'timestamp' => $data['date'] ?? $data['ts'] ?? date('c'),
            'ip' => $data['ip'] ?? null,
            'tag' => $data['tag'] ?? null,
            'link' => $data['link'] ?? null, // For click events
            'reason' => $data['reason'] ?? null, // For bounce events
        ];

        // Remove null values
        $metadata = array_filter($metadata, fn($v) => $v !== null);

        updateEmailLogStatus($messageId, $status, $metadata);
    }

    // Respond with success
    http_response_code(200);
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Brevo Webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal error']);
}
