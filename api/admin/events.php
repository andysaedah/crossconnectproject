<?php
/**
 * CrossConnect MY - Admin Events API
 * Handle admin operations for events (delete, update status)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/language.php';

header('Content-Type: application/json');

// Require admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => __('error_unauthorized')]);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => __('error_method_not_allowed')]);
    exit;
}

// Verify CSRF
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => __('error_invalid_security_token')]);
    exit;
}

$action = $_POST['action'] ?? '';
$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => __('error_event_id_required')]);
    exit;
}

$pdo = getDbConnection();

try {
    switch ($action) {
        case 'delete':
            // Permanently delete the event
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $result = $stmt->execute([$id]);

            if ($result && $stmt->rowCount() > 0) {
                logActivity('event_deleted', 'Deleted event ID: ' . $id, 'event', $id);
                echo json_encode(['success' => true, 'message' => __('success_event_deleted')]);
            } else {
                echo json_encode(['success' => false, 'error' => __('error_event_not_found')]);
            }
            break;

        case 'update_status':
            $status = $_POST['status'] ?? '';
            $validStatuses = ['upcoming', 'ongoing', 'completed', 'cancelled'];

            if (!in_array($status, $validStatuses)) {
                echo json_encode(['success' => false, 'error' => __('error_invalid_status')]);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE events SET status = ? WHERE id = ?");
            $result = $stmt->execute([$status, $id]);

            if ($result) {
                logActivity('event_status_updated', "Updated event ID: {$id} to status: {$status}", 'event', $id);
                echo json_encode(['success' => true, 'message' => __('success_status_updated')]);
            } else {
                echo json_encode(['success' => false, 'error' => __('error_status_update_failed')]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => __('error_invalid_action')]);
            break;
    }
} catch (PDOException $e) {
    error_log("Admin events API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => __('error_generic')]);
}
