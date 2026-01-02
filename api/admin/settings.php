<?php
/**
 * CrossConnect MY - Admin Settings API
 * Handles saving and retrieving settings
 */

require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/settings.php';

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

$action = isset($_POST['action']) ? trim($_POST['action']) : 'save';

try {
    $pdo = getDbConnection();

    switch ($action) {
        case 'save':
            $group = isset($_POST['group']) ? trim($_POST['group']) : 'general';
            $settings = isset($_POST['settings']) ? $_POST['settings'] : [];

            if (!is_array($settings)) {
                echo json_encode(['success' => false, 'error' => __('error_invalid_settings')]);
                exit;
            }

            foreach ($settings as $key => $value) {
                // Sanitize key
                $key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
                if (empty($key))
                    continue;

                // Determine if this is an encrypted field (API keys)
                $isEncrypted = strpos($key, 'api_key') !== false;

                $result = setSetting($key, trim($value), $group, $isEncrypted);
                if (!$result) {
                    // Return detailed error for debugging
                    echo json_encode(['success' => false, 'error' => 'Failed to save setting: ' . $key . '. Check if settings table exists.']);
                    exit;
                }
            }

            echo json_encode(['success' => true, 'message' => __('success_settings_saved')]);
            break;

        case 'get':
            $group = isset($_POST['group']) ? trim($_POST['group']) : null;

            if ($group) {
                $settings = getSettingsByGroup($group);
            } else {
                // Get all settings
                $stmt = $pdo->query("SELECT setting_key, setting_value, setting_group FROM settings ORDER BY setting_group, setting_key");
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $settings = [];
                foreach ($results as $row) {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
            }

            echo json_encode(['success' => true, 'data' => $settings]);
            break;

        case 'test_email':
            // Test email functionality
            require_once __DIR__ . '/../../config/email.php';

            $testEmail = isset($_POST['email']) ? trim($_POST['email']) : '';
            if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => __('error_email_invalid')]);
                exit;
            }

            // Check if specific provider is requested
            $provider = isset($_POST['provider']) ? trim($_POST['provider']) : '';

            $htmlContent = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                <h1 style="color: #0891b2;">Test Email</h1>
                <p>This is a test email from CrossConnect MY.</p>
                <p>If you received this, your email configuration is working correctly!</p>
                <p style="color: #666; font-size: 14px;">Sent at: ' . date('Y-m-d H:i:s') . '</p>
            </div>';

            if (!empty($provider) && in_array($provider, ['smtp2go', 'brevo', 'phpmail'])) {
                // Test specific provider only
                $result = sendViaProvider(
                    $provider,
                    $testEmail,
                    'CrossConnect MY - Test Email (' . strtoupper($provider) . ')',
                    $htmlContent,
                    'This is a test email from CrossConnect MY.'
                );
            } else {
                // Use normal send with fallback
                $result = sendEmail(
                    $testEmail,
                    'CrossConnect MY - Test Email',
                    $htmlContent,
                    'This is a test email from CrossConnect MY.'
                );
            }

            if ($result['success']) {
                $response = ['success' => true, 'message' => __('success_test_email_sent')];
                if (isset($result['provider'])) {
                    $response['provider'] = $result['provider'];
                }
                echo json_encode($response);
            } else {
                echo json_encode(['success' => false, 'error' => $result['error'] ?? __('error_test_email_failed')]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => __('error_invalid_action')]);
            break;
    }

} catch (Exception $e) {
    error_log("Admin settings API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('error_generic')]);
}
