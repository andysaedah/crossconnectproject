<?php
/**
 * States API Endpoint
 * Returns list of Malaysian states for filtering
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=3600'); // 1 hour cache (states rarely change)
header('Vary: Accept-Encoding');

require_once __DIR__ . '/../config/database.php';

// Try database first, fall back to demo data
$states = dbQuery("SELECT id, name, slug, region FROM states ORDER BY name ASC");

if ($states === false) {
    // Fall back to demo data
    require_once __DIR__ . '/../config/demo_data.php';
    $states = getDemoStates();
}

echo json_encode([
    'success' => true,
    'data' => $states,
    'count' => count($states)
]);
