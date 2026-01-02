<?php
/**
 * CrossConnect MY - Churches API (User)
 * Handle CRUD operations for user's churches
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
    $rateLimitKey = 'church_submit_' . $user['id'];
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

$churchId = intval($_POST['id'] ?? 0);

// Get database connection
$pdo = getDbConnection();
if (!$pdo) {
    jsonError(__('error_database_failed'), 500);
}

try {
    // DELETE action
    if ($action === 'delete') {
        if (!$churchId) {
            jsonError(__('error_church_id_required'));
        }

        // Verify ownership
        $stmt = $pdo->prepare("SELECT id, name FROM churches WHERE id = ? AND created_by = ?");
        $stmt->execute([$churchId, $user['id']]);
        $church = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$church) {
            jsonError(__('error_church_not_found'));
        }

        // Delete
        $stmt = $pdo->prepare("DELETE FROM churches WHERE id = ?");
        $stmt->execute([$churchId]);

        logActivity('church_deleted', 'Deleted church: ' . $church['name'], 'church', $churchId);

        jsonSuccess([], __('success_church_deleted'));
    }

    // SAVE action (add or update)
    $name = trim($_POST['name'] ?? '');
    $stateId = intval($_POST['state_id'] ?? 0);
    $denominationId = intval($_POST['denomination_id'] ?? 0) ?: null;
    $city = trim($_POST['city'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $facebook = trim($_POST['facebook'] ?? '');
    $instagram = trim($_POST['instagram'] ?? '');
    $youtube = trim($_POST['youtube'] ?? '');
    $twitter = trim($_POST['twitter'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $serviceTimes = trim($_POST['service_times'] ?? '');

    // Handle service_languages - can be array of checkboxes or comma-separated string
    $serviceLanguages = '';
    if (isset($_POST['service_languages'])) {
        if (is_array($_POST['service_languages'])) {
            $serviceLanguages = implode(',', array_filter($_POST['service_languages']));
        } else {
            $serviceLanguages = trim($_POST['service_languages']);
        }
    }

    // Validation
    if (empty($name)) {
        jsonError(__('error_church_name_required'));
    }

    if (!$stateId) {
        jsonError(__('error_state_required'));
    }

    // Generate slug
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
    $slug = trim($slug, '-');

    // Make slug unique
    $baseSlug = $slug;
    $counter = 1;
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM churches WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $churchId]);
        if (!$stmt->fetch())
            break;
        $slug = $baseSlug . '-' . $counter++;
    }

    // Handle image upload
    $imageUrl = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = uploadImage($_FILES['photo'], 'church', $churchId ?: uniqid());
        if (!$uploadResult['success']) {
            jsonError($uploadResult['error']);
        }
        $imageUrl = $uploadResult['path'];
    }

    if ($churchId) {
        // UPDATE - verify ownership first (admins can update any)
        if (isAdmin()) {
            $stmt = $pdo->prepare("SELECT id, image_url FROM churches WHERE id = ?");
            $stmt->execute([$churchId]);
        } else {
            $stmt = $pdo->prepare("SELECT id, image_url FROM churches WHERE id = ? AND created_by = ?");
            $stmt->execute([$churchId, $user['id']]);
        }
        $existingChurch = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingChurch) {
            jsonError(__('error_church_not_found'));
        }

        // If new image uploaded, delete old one
        if ($imageUrl && $existingChurch['image_url']) {
            deleteUploadedImage($existingChurch['image_url']);
        }

        // Use existing image if no new one uploaded
        if (!$imageUrl) {
            $imageUrl = $existingChurch['image_url'];
        }

        // Check if service_languages column exists (for backwards compatibility)
        $hasServiceLangsColumn = false;
        try {
            $checkCol = $pdo->query("SHOW COLUMNS FROM churches LIKE 'service_languages'");
            $hasServiceLangsColumn = $checkCol->rowCount() > 0;
        } catch (Exception $e) {
            // Column check failed, assume it doesn't exist
        }

        if ($hasServiceLangsColumn) {
            $stmt = $pdo->prepare("
                UPDATE churches SET
                    name = ?, slug = ?, state_id = ?, denomination_id = ?,
                    city = ?, address = ?, phone = ?, email = ?, website = ?,
                    facebook = ?, instagram = ?, youtube = ?, twitter = ?,
                    description = ?, service_times = ?, service_languages = ?, image_url = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $name,
                $slug,
                $stateId,
                $denominationId,
                $city ?: null,
                $address ?: null,
                $phone ?: null,
                $email ?: null,
                $website ?: null,
                $facebook ?: null,
                $instagram ?: null,
                $youtube ?: null,
                $twitter ?: null,
                $description ?: null,
                $serviceTimes ?: null,
                $serviceLanguages ?: null,
                $imageUrl,
                $churchId
            ]);
        } else {
            // Fallback without service_languages column
            $stmt = $pdo->prepare("
                UPDATE churches SET
                    name = ?, slug = ?, state_id = ?, denomination_id = ?,
                    city = ?, address = ?, phone = ?, email = ?, website = ?,
                    facebook = ?, instagram = ?, youtube = ?, twitter = ?,
                    description = ?, service_times = ?, image_url = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $name,
                $slug,
                $stateId,
                $denominationId,
                $city ?: null,
                $address ?: null,
                $phone ?: null,
                $email ?: null,
                $website ?: null,
                $facebook ?: null,
                $instagram ?: null,
                $youtube ?: null,
                $twitter ?: null,
                $description ?: null,
                $serviceTimes ?: null,
                $imageUrl,
                $churchId
            ]);
        }

        logActivity('church_updated', 'Updated church: ' . $name, 'church', $churchId);

        jsonSuccess(['id' => $churchId], __('success_church_updated'));
    } else {
        // INSERT - reuse the column check from update block if not already done
        if (!isset($hasServiceLangsColumn)) {
            $hasServiceLangsColumn = false;
            try {
                $checkCol = $pdo->query("SHOW COLUMNS FROM churches LIKE 'service_languages'");
                $hasServiceLangsColumn = $checkCol->rowCount() > 0;
            } catch (Exception $e) {
                // Column check failed, assume it doesn't exist
            }
        }

        if ($hasServiceLangsColumn) {
            $stmt = $pdo->prepare("
                INSERT INTO churches (name, slug, state_id, denomination_id, city, address, phone, email, website, facebook, instagram, youtube, twitter, description, service_times, service_languages, image_url, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
            ");
            $stmt->execute([
                $name,
                $slug,
                $stateId,
                $denominationId,
                $city ?: null,
                $address ?: null,
                $phone ?: null,
                $email ?: null,
                $website ?: null,
                $facebook ?: null,
                $instagram ?: null,
                $youtube ?: null,
                $twitter ?: null,
                $description ?: null,
                $serviceTimes ?: null,
                $serviceLanguages ?: null,
                $imageUrl,
                $user['id']
            ]);
        } else {
            // Fallback without service_languages column
            $stmt = $pdo->prepare("
                INSERT INTO churches (name, slug, state_id, denomination_id, city, address, phone, email, website, facebook, instagram, youtube, twitter, description, service_times, image_url, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
            ");
            $stmt->execute([
                $name,
                $slug,
                $stateId,
                $denominationId,
                $city ?: null,
                $address ?: null,
                $phone ?: null,
                $email ?: null,
                $website ?: null,
                $facebook ?: null,
                $instagram ?: null,
                $youtube ?: null,
                $twitter ?: null,
                $description ?: null,
                $serviceTimes ?: null,
                $imageUrl,
                $user['id']
            ]);
        }

        $newId = $pdo->lastInsertId();

        // Rename file with actual ID if we uploaded one
        if ($imageUrl) {
            $extension = pathinfo($imageUrl, PATHINFO_EXTENSION);
            $newFilename = 'church_' . $newId . '_' . time() . '.' . $extension;
            $oldPath = __DIR__ . '/../../' . ltrim($imageUrl, '/');
            $newPath = __DIR__ . '/../../uploads/church/' . $newFilename;
            if (file_exists($oldPath) && rename($oldPath, $newPath)) {
                $imageUrl = '/uploads/church/' . $newFilename;
                $pdo->prepare("UPDATE churches SET image_url = ? WHERE id = ?")->execute([$imageUrl, $newId]);
            }
        }

        logActivity('church_created', 'Created church: ' . $name, 'church', $newId);

        // Send Telegram notification
        sendTelegramNotification(
            "⛪ New Church Added",
            "*{$name}*\n📍 {$city}, {$stateName}" . ($user['name'] ? "\n👤 By: {$user['name']}" : ""),
            "success"
        );

        jsonSuccess(['id' => $newId], __('success_church_added'));
    }

} catch (PDOException $e) {
    error_log("Church API error: " . $e->getMessage());
    jsonError(__('error_generic'), 500);
}
