<?php
/**
 * Events API Endpoint
 * Returns upcoming Christian events with filtering
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=300'); // 5 minute cache
header('Vary: Accept-Encoding');

require_once __DIR__ . '/../config/database.php';

// Get parameters
$state = isset($_GET['state']) ? trim($_GET['state']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$featured = isset($_GET['featured']) ? (int) $_GET['featured'] : 0;
$upcoming = isset($_GET['upcoming']) ? (int) $_GET['upcoming'] : 1;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, (int) $_GET['limit'])) : 12;
$offset = ($page - 1) * $limit;

// Try database first
$useDemoData = false;
$events = [];
$total = 0;

$pdo = getDbConnection();

if ($pdo) {
    // Build query
    $sql = "SELECT 
                e.*, s.name as state_name, s.slug as state_slug
            FROM events e
            LEFT JOIN states s ON e.state_id = s.id
            WHERE 1=1";

    $params = [];

    // Upcoming events only (within next month)
    // For multi-day events, use end_date if available, otherwise use start date
    if ($upcoming) {
        $sql .= " AND (COALESCE(e.event_end_date, e.event_date) >= CURDATE())";
        $sql .= " AND e.event_date <= DATE_ADD(CURDATE(), INTERVAL 1 MONTH)";
        $sql .= " AND e.status IN ('upcoming', 'ongoing')";
    }

    // Filter by state
    if (!empty($state) && $state !== 'all') {
        $sql .= " AND s.slug = ?";
        $params[] = $state;
    }

    // Featured only
    if ($featured) {
        $sql .= " AND e.is_featured = 1";
    }

    // Search - Use FULLTEXT for indexed columns
    if (!empty($search)) {
        $sql .= " AND MATCH(e.name, e.organizer, e.venue) AGAINST(? IN BOOLEAN MODE)";
        $searchTerm = $search . '*'; // Prefix matching for partial words
        $params[] = $searchTerm;
    }

    // Count
    $countSql = preg_replace('/SELECT\s+.+?\s+FROM/is', 'SELECT COUNT(*) as total FROM', $sql);
    $countResult = dbQuerySingle($countSql, $params);
    $total = $countResult ? (int) $countResult['total'] : 0;

    // Order and paginate
    $sql .= " ORDER BY e.is_featured DESC, e.event_date ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $events = dbQuery($sql, $params);

    if ($events === false) {
        $useDemoData = true;
    }
} else {
    $useDemoData = true;
}

// Fall back to demo data
if ($useDemoData) {
    require_once __DIR__ . '/../config/demo_data.php';

    if (function_exists('getDemoEvents')) {
        $allEvents = getDemoEvents();

        // In demo mode, skip date filtering since dates are static
        // Just show all demo events sorted by date

        // Filter by state
        if (!empty($state) && $state !== 'all') {
            $allEvents = array_filter($allEvents, function ($e) use ($state) {
                return ($e['state_slug'] ?? '') === $state;
            });
        }

        // Featured only
        if ($featured) {
            $allEvents = array_filter($allEvents, function ($e) {
                return !empty($e['is_featured']);
            });
        }

        // Search
        if (!empty($search)) {
            $searchLower = strtolower($search);
            $allEvents = array_filter($allEvents, function ($e) use ($searchLower) {
                return strpos(strtolower($e['name']), $searchLower) !== false ||
                    strpos(strtolower($e['organizer'] ?? ''), $searchLower) !== false ||
                    strpos(strtolower($e['venue'] ?? ''), $searchLower) !== false;
            });
        }

        // Sort: featured first, then by date
        usort($allEvents, function ($a, $b) {
            if (($a['is_featured'] ?? 0) != ($b['is_featured'] ?? 0)) {
                return ($b['is_featured'] ?? 0) - ($a['is_featured'] ?? 0);
            }
            return strcmp($a['event_date'], $b['event_date']);
        });

        $total = count($allEvents);
        $events = array_slice(array_values($allEvents), $offset, $limit);
    }
}

// Format response
$response = [
    'success' => true,
    'data' => $events ?: [],
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'total_pages' => ceil($total / $limit)
    ],
    'demo_mode' => $useDemoData
];

echo json_encode($response);
