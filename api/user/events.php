<?php
/**
 * CrossConnect MY - Events API (User)
 * Handle CRUD operations for user's events
 */

require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/upload.php';
require_once __DIR__ . '/../../config/telegram.php';

header('Content-Type: application/json');

// Require authentication
if (!isLoggedIn()) {
    jsonError(__('error_unauthorized'), 401);
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError(__('error_method_not_allowed'), 405);
}

// Verify CSRF token
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    jsonError(__('error_invalid_security_token'));
}

$user = getCurrentUser();

// Rate limit: 10 submissions per hour per user (skip for delete action)
$action = $_POST['action'] ?? 'save';
if ($action !== 'delete') {
    $rateLimitKey = 'event_submit_' . $user['id'];
    $attempts = $_SESSION[$rateLimitKey] ?? ['count' => 0, 'first_attempt' => time()];

    // Reset if 1 hour has passed
    if (time() - $attempts['first_attempt'] > 3600) {
        $attempts = ['count' => 0, 'first_attempt' => time()];
    }

    if ($attempts['count'] >= 10) {
        $remainingMinutes = ceil((3600 - (time() - $attempts['first_attempt'])) / 60);
        jsonError(str_replace('{minutes}', $remainingMinutes, __('error_too_many_submissions')));
    }

    $attempts['count']++;
    $_SESSION[$rateLimitKey] = $attempts;
}

$eventId = intval($_POST['id'] ?? 0);

// Get database connection
$pdo = getDbConnection();
if (!$pdo) {
    jsonError(__('error_database_failed'), 500);
}

try {
    // DELETE action
    if ($action === 'delete') {
        if (!$eventId) {
            jsonError(__('error_event_id_required'));
        }

        // Verify ownership
        $stmt = $pdo->prepare("SELECT id, name FROM events WHERE id = ? AND created_by = ?");
        $stmt->execute([$eventId, $user['id']]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event) {
            jsonError(__('error_event_not_found'));
        }

        // Delete
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$eventId]);

        logActivity('event_deleted', 'Deleted event: ' . $event['name'], 'event', $eventId);

        jsonSuccess([], __('success_event_deleted'));
    }

    // SAVE action (add or update)
    $name = trim($_POST['name'] ?? '');
    $eventDate = $_POST['event_date'] ?? '';
    $eventEndDate = $_POST['event_end_date'] ?? '';
    $eventTime = trim($_POST['event_time'] ?? '');
    $eventType = trim($_POST['event_type'] ?? '');
    $eventFormat = trim($_POST['event_format'] ?? 'in_person');
    $meetingUrl = trim($_POST['meeting_url'] ?? '');
    $livestreamUrl = trim($_POST['livestream_url'] ?? '');
    $stateId = intval($_POST['state_id'] ?? 0) ?: null;
    $venue = trim($_POST['venue'] ?? '');
    $venueAddress = trim($_POST['venue_address'] ?? '');
    $organizer = trim($_POST['organizer'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $websiteUrl = trim($_POST['website_url'] ?? '');
    $registrationUrl = trim($_POST['registration_url'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');

    // Validate event_format
    if (!in_array($eventFormat, ['in_person', 'online', 'hybrid'])) {
        $eventFormat = 'in_person';
    }

    // Validation
    if (empty($name)) {
        jsonError(__('error_event_name_required'));
    }

    if (empty($eventDate)) {
        jsonError(__('error_event_date_required'));
    }

    // Generate slug
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
    $slug = trim($slug, '-');

    // Make slug unique
    $baseSlug = $slug;
    $counter = 1;
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM events WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $eventId]);
        if (!$stmt->fetch())
            break;
        $slug = $baseSlug . '-' . $counter++;
    }

    // Handle image upload
    $imageUrl = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = uploadImage($_FILES['photo'], 'event', $eventId ?: uniqid());
        if (!$uploadResult['success']) {
            jsonError($uploadResult['error']);
        }
        $imageUrl = $uploadResult['path'];
    }

    if ($eventId) {
        // UPDATE - verify ownership first (admins can update any)
        if (isAdmin()) {
            $stmt = $pdo->prepare("SELECT id, poster_url FROM events WHERE id = ?");
            $stmt->execute([$eventId]);
        } else {
            $stmt = $pdo->prepare("SELECT id, poster_url FROM events WHERE id = ? AND created_by = ?");
            $stmt->execute([$eventId, $user['id']]);
        }
        $existingEvent = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingEvent) {
            jsonError(__('error_event_not_found'));
        }

        // If new image uploaded, delete old one
        if ($imageUrl && $existingEvent['poster_url']) {
            deleteUploadedImage($existingEvent['poster_url']);
        }

        // Use existing image if no new one uploaded
        if (!$imageUrl) {
            $imageUrl = $existingEvent['poster_url'];
        }

        $stmt = $pdo->prepare("
            UPDATE events SET
                name = ?, slug = ?, event_date = ?, event_end_date = ?, event_time = ?,
                event_type = ?, event_format = ?, meeting_url = ?, livestream_url = ?,
                state_id = ?, venue = ?, venue_address = ?, organizer = ?,
                description = ?, website_url = ?, registration_url = ?, email = ?, whatsapp = ?,
                poster_url = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $name,
            $slug,
            $eventDate,
            $eventEndDate ?: null,
            $eventTime ?: null,
            $eventType ?: null,
            $eventFormat,
            $meetingUrl ?: null,
            $livestreamUrl ?: null,
            $stateId,
            $venue ?: null,
            $venueAddress ?: null,
            $organizer ?: null,
            $description ?: null,
            $websiteUrl ?: null,
            $registrationUrl ?: null,
            $email ?: null,
            $whatsapp ?: null,
            $imageUrl,
            $eventId
        ]);

        logActivity('event_updated', 'Updated event: ' . $name, 'event', $eventId);

        jsonSuccess(['id' => $eventId], __('success_event_updated'));
    } else {
        // INSERT
        $stmt = $pdo->prepare("
            INSERT INTO events (name, slug, event_date, event_end_date, event_time, event_type, event_format, meeting_url, livestream_url, state_id, venue, venue_address, organizer, description, website_url, registration_url, email, whatsapp, poster_url, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'upcoming', ?)
        ");
        $stmt->execute([
            $name,
            $slug,
            $eventDate,
            $eventEndDate ?: null,
            $eventTime ?: null,
            $eventType ?: null,
            $eventFormat,
            $meetingUrl ?: null,
            $livestreamUrl ?: null,
            $stateId,
            $venue ?: null,
            $venueAddress ?: null,
            $organizer ?: null,
            $description ?: null,
            $websiteUrl ?: null,
            $registrationUrl ?: null,
            $email ?: null,
            $whatsapp ?: null,
            $imageUrl,
            $user['id']
        ]);

        $newId = $pdo->lastInsertId();

        // Rename file with actual ID if we uploaded one
        if ($imageUrl) {
            $extension = pathinfo($imageUrl, PATHINFO_EXTENSION);
            $newFilename = 'event_' . $newId . '_' . time() . '.' . $extension;
            $oldPath = __DIR__ . '/../../' . ltrim($imageUrl, '/');
            $newPath = __DIR__ . '/../../uploads/event/' . $newFilename;
            if (file_exists($oldPath) && rename($oldPath, $newPath)) {
                $imageUrl = '/uploads/event/' . $newFilename;
                $pdo->prepare("UPDATE events SET poster_url = ? WHERE id = ?")->execute([$imageUrl, $newId]);
            }
        }

        logActivity('event_created', 'Created event: ' . $name, 'event', $newId);

        // Send Telegram notification
        sendTelegramNotification(
            "📅 New Event Added",
            "*{$name}*\n📍 {$venue}\n🗓️ {$eventDate}" . ($user['name'] ? "\n👤 By: {$user['name']}" : ""),
            "success"
        );

        jsonSuccess(['id' => $newId], __('success_event_added'));
    }

} catch (PDOException $e) {
    error_log("Event API error: " . $e->getMessage());
    jsonError(__('error_generic'), 500);
}
