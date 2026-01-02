<?php
/**
 * CrossConnect MY - Admin Churches API
 * Handles admin operations for churches (clear_amendment, status changes, etc.)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/language.php';

header('Content-Type: application/json');

// Check if user is admin
$user = getCurrentUser();
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => __('error_unauthorized')]);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => __('error_method_not_allowed')]);
    exit;
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => __('error_invalid_security_token')]);
    exit;
}

$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('error_church_id_required')]);
    exit;
}

try {
    $pdo = getDbConnection();

    switch ($action) {
        case 'clear_amendment':
            // Mark amendment as resolved - set status back to active and clear amendment fields
            $stmt = $pdo->prepare("
                UPDATE churches 
                SET status = 'active',
                    amendment_notes = NULL,
                    amendment_reporter_email = NULL,
                    amendment_date = NULL
                WHERE id = ?
            ");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                // Log the activity
                if (function_exists('logActivity')) {
                    logActivity('amendment_cleared', 'church', $id, 'Amendment cleared by admin');
                }
                echo json_encode(['success' => true, 'message' => 'Amendment marked as resolved']);
            } else {
                echo json_encode(['success' => false, 'error' => __('error_church_not_found')]);
            }
            break;

        case 'update_status':
            $newStatus = isset($_POST['status']) ? trim($_POST['status']) : '';
            $validStatuses = ['active', 'inactive', 'pending', 'needs_amendment'];

            if (!in_array($newStatus, $validStatuses)) {
                echo json_encode(['success' => false, 'error' => __('error_invalid_status')]);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE churches SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => __('success_status_updated')]);
            } else {
                echo json_encode(['success' => false, 'error' => __('error_church_not_found')]);
            }
            break;

        case 'delete':
            $stmt = $pdo->prepare("DELETE FROM churches WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                if (function_exists('logActivity')) {
                    logActivity('church_deleted', 'church', $id, 'Church deleted by admin');
                }
                echo json_encode(['success' => true, 'message' => __('success_church_deleted')]);
            } else {
                echo json_encode(['success' => false, 'error' => __('error_church_not_found')]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => __('error_invalid_action')]);
            break;
    }

} catch (Exception $e) {
    error_log("Admin churches API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('error_generic')]);
}
