<?php
/**
 * CrossConnect MY - Admin Content API
 * Handle status updates for churches and events
 */

require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json');

// Require admin
if (!isLoggedIn() || !isAdmin()) {
    jsonError('Unauthorized', 401);
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

// Verify CSRF token
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    jsonError('Invalid security token');
}

$id = intval($_POST['id'] ?? 0);
$type = $_POST['type'] ?? '';
$status = $_POST['status'] ?? '';

if (!$id) {
    jsonError('ID is required');
}

if (!in_array($type, ['church', 'event'])) {
    jsonError('Invalid type');
}

// Get database connection
$pdo = getDbConnection();
if (!$pdo) {
    jsonError('Database connection failed', 500);
}

try {
    $table = $type === 'church' ? 'churches' : 'events';

    // Verify item exists
    $stmt = $pdo->prepare("SELECT id, name FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        jsonError(ucfirst($type) . ' not found');
    }

    // Validate status
    if ($type === 'church') {
        if (!in_array($status, ['active', 'inactive', 'pending'])) {
            jsonError('Invalid status');
        }
    } else {
        if (!in_array($status, ['upcoming', 'ongoing', 'completed', 'cancelled'])) {
            jsonError('Invalid status');
        }
    }

    // Update status
    $stmt = $pdo->prepare("UPDATE $table SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $id]);

    logActivity("admin_{$type}_status", "Changed $type status to $status: " . $item['name'], $type, $id);

    jsonSuccess([], ucfirst($type) . ' status updated');

} catch (PDOException $e) {
    error_log("Admin content API error: " . $e->getMessage());
    jsonError('An error occurred', 500);
}
