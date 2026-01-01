<?php
/**
 * CrossConnect MY - Admin Search API
 * AJAX-powered search for churches and events
 */

require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json');

// Require admin
if (!isLoggedIn() || !isAdmin()) {
    jsonError('Unauthorized', 401);
}

$type = $_GET['type'] ?? '';
$query = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

if (!in_array($type, ['churches', 'events', 'users'])) {
    jsonError('Invalid type');
}

$pdo = getDbConnection();
if (!$pdo) {
    jsonError('Database connection failed', 500);
}

try {
    $data = [];
    $total = 0;

    if ($type === 'churches') {
        $id = intval($_GET['id'] ?? 0);

        // Build where clause
        $where = [];
        $params = [];

        if ($id) {
            $where[] = "c.id = ?";
            $params[] = $id;
        }

        if ($query) {
            $where[] = "MATCH(c.name, c.city, c.address) AGAINST(? IN BOOLEAN MODE)";
            $params[] = $query . '*';
        }

        if ($status) {
            $where[] = "c.status = ?";
            $params[] = $status;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM churches c $whereClause");
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        // Get data with all fields for editing
        $params[] = $perPage;
        $params[] = $offset;
        $stmt = $pdo->prepare("
            SELECT c.id, c.name, c.slug, c.city, c.address, c.postal_code, c.phone, c.email, 
                   c.website, c.facebook, c.instagram, c.youtube, c.twitter, c.description, c.service_times, c.service_languages, c.image_url,
                   c.status, c.created_at, c.state_id, c.denomination_id,
                   c.amendment_notes, c.amendment_date, c.amendment_reporter_email,
                   s.name as state_name, d.name as denomination_name, u.name as creator_name
            FROM churches c
            LEFT JOIN states s ON c.state_id = s.id
            LEFT JOIN denominations d ON c.denomination_id = d.id
            LEFT JOIN users u ON c.created_by = u.id
            $whereClause
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($type === 'events') {
        $id = intval($_GET['id'] ?? 0);
        $showPast = $_GET['past'] ?? '';

        $where = [];
        $params = [];

        if ($id) {
            $where[] = "e.id = ?";
            $params[] = $id;
        }

        if ($query) {
            $where[] = "MATCH(e.name, e.organizer, e.venue) AGAINST(? IN BOOLEAN MODE)";
            $params[] = $query . '*';
        }

        if ($status) {
            $where[] = "e.status = ?";
            $params[] = $status;
        }

        // Filter by past/upcoming - use end_date if available for multi-day events
        if ($showPast === '1') {
            $where[] = "COALESCE(e.event_end_date, e.event_date) < CURDATE()";
        } elseif ($showPast === '0') {
            $where[] = "COALESCE(e.event_end_date, e.event_date) >= CURDATE()";
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM events e $whereClause");
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        // Get data with all fields for editing
        $params[] = $perPage;
        $params[] = $offset;
        $stmt = $pdo->prepare("
            SELECT e.id, e.name, e.slug, e.event_date, e.event_end_date, e.event_time, 
                   e.venue, e.venue_address, e.organizer, e.description,
                   e.website_url, e.registration_url, e.email, e.whatsapp, e.phone,
                   e.poster_url as image_url, e.status, e.created_at, e.state_id,
                   e.event_type, e.event_format, e.meeting_url, e.livestream_url,
                   s.name as state_name, u.name as creator_name,
                   CASE WHEN COALESCE(e.event_end_date, e.event_date) < CURDATE() THEN 1 ELSE 0 END as is_past
            FROM events e
            LEFT JOIN states s ON e.state_id = s.id
            LEFT JOIN users u ON e.created_by = u.id
            $whereClause
            ORDER BY e.event_date DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($type === 'users') {
        $where = [];
        $params = [];

        if ($query) {
            $where[] = "(name LIKE ? OR email LIKE ? OR username LIKE ?)";
            $params[] = "%$query%";
            $params[] = "%$query%";
            $params[] = "%$query%";
        }

        // Status filter
        if ($status === 'active') {
            $where[] = "is_active = 1 AND email_verified_at IS NOT NULL";
        } elseif ($status === 'unverified') {
            $where[] = "email_verified_at IS NULL";
        } elseif ($status === 'inactive') {
            $where[] = "is_active = 0";
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereClause");
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        // Get data
        $params[] = $perPage;
        $params[] = $offset;
        $stmt = $pdo->prepare("
            SELECT id, username, email, name, role, avatar_color, is_active, email_verified_at, created_at
            FROM users
            $whereClause
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $totalPages = ceil($total / $perPage);

    jsonSuccess([
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
        'has_more' => $page < $totalPages
    ]);

} catch (Exception $e) {
    error_log("Admin search error: " . $e->getMessage());
    jsonError('Search failed', 500);
}
