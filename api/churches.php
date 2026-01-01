<?php
/**
 * Churches API Endpoint
 * Returns churches with filtering, search, and pagination
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=300'); // 5 minute cache
header('Vary: Accept-Encoding');

require_once __DIR__ . '/../config/database.php';

// Get parameters
$state = isset($_GET['state']) ? trim($_GET['state']) : '';
$denomination = isset($_GET['denomination']) ? trim($_GET['denomination']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$featured = isset($_GET['featured']) ? (int) $_GET['featured'] : 0;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, (int) $_GET['limit'])) : 12;
$offset = ($page - 1) * $limit;

// Try database first
$useDemoData = false;
$churches = [];
$total = 0;

// Build query
$sql = "SELECT 
            c.id, c.name, c.slug, c.city, c.address, c.phone, c.email,
            c.website, c.facebook, c.instagram, c.youtube, c.twitter,
            c.image_url, c.description, c.is_featured, c.latitude, c.longitude,
            c.service_times, c.service_languages,
            s.name as state_name, s.slug as state_slug,
            d.name as denomination_name, d.slug as denomination_slug
        FROM churches c
        LEFT JOIN states s ON c.state_id = s.id
        LEFT JOIN denominations d ON c.denomination_id = d.id
        WHERE c.status = 'active'";

$params = [];
$countParams = [];

// Filter by state
if (!empty($state) && $state !== 'all') {
    $sql .= " AND s.slug = ?";
    $params[] = $state;
    $countParams[] = $state;
}

// Filter by denomination
if (!empty($denomination) && $denomination !== 'all') {
    $sql .= " AND d.slug = ?";
    $params[] = $denomination;
    $countParams[] = $denomination;
}

// Search - Use FULLTEXT for indexed columns, LIKE for denomination
if (!empty($search)) {
    $sql .= " AND (MATCH(c.name, c.city, c.address) AGAINST(? IN BOOLEAN MODE) OR d.name LIKE ?)";
    $searchTerm = $search . '*'; // Prefix matching for partial words
    $likeTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $likeTerm;
    $countParams[] = $searchTerm;
    $countParams[] = $likeTerm;
}

// Featured only
if ($featured) {
    $sql .= " AND c.is_featured = 1";
}

// Try database query
$pdo = getDbConnection();

if ($pdo) {
    // Count total
    $countSql = preg_replace('/SELECT\s+.+?\s+FROM/is', 'SELECT COUNT(*) as total FROM', $sql);
    $countResult = dbQuerySingle($countSql, $countParams);
    $total = $countResult ? (int) $countResult['total'] : 0;

    // Order and paginate
    $sql .= " ORDER BY c.is_featured DESC, c.name ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    // Execute query
    $churches = dbQuery($sql, $params);

    if ($churches === false) {
        $useDemoData = true;
    }
} else {
    $useDemoData = true;
}

// Fall back to demo data
if ($useDemoData) {
    require_once __DIR__ . '/../config/demo_data.php';
    $allChurches = getDemoChurches();

    // Filter demo data
    if (!empty($state) && $state !== 'all') {
        $allChurches = array_filter($allChurches, function ($c) use ($state) {
            return $c['state_slug'] === $state;
        });
    }

    if (!empty($denomination) && $denomination !== 'all') {
        $allChurches = array_filter($allChurches, function ($c) use ($denomination) {
            return $c['denomination_slug'] === $denomination;
        });
    }

    if (!empty($search)) {
        $searchLower = strtolower($search);
        $allChurches = array_filter($allChurches, function ($c) use ($searchLower) {
            return strpos(strtolower($c['name']), $searchLower) !== false ||
                strpos(strtolower($c['city']), $searchLower) !== false ||
                strpos(strtolower($c['denomination_name'] ?? ''), $searchLower) !== false;
        });
    }

    if ($featured) {
        $allChurches = array_filter($allChurches, function ($c) {
            return $c['is_featured'] == 1;
        });
    }

    // Sort: featured first, then by name
    usort($allChurches, function ($a, $b) {
        if ($a['is_featured'] != $b['is_featured']) {
            return $b['is_featured'] - $a['is_featured'];
        }
        return strcmp($a['name'], $b['name']);
    });

    $total = count($allChurches);
    $churches = array_slice(array_values($allChurches), $offset, $limit);
}

// Format response
$response = [
    'success' => true,
    'data' => $churches,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'total_pages' => ceil($total / $limit)
    ],
    'demo_mode' => $useDemoData
];

echo json_encode($response);
