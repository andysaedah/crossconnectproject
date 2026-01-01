<?php
/**
 * CrossConnect MY - Admin Language API
 * Save translations to language files
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json');

// Require admin
if (!isLoggedIn() || !isAdmin()) {
    jsonError('Unauthorized', 401);
}

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

// Get JSON data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonError('Invalid JSON data');
}

// Verify CSRF token
if (!isset($input['csrf_token']) || !verifyCsrfToken($input['csrf_token'])) {
    jsonError('Invalid security token');
}

$enStrings = $input['en'] ?? [];
$bmStrings = $input['bm'] ?? [];
$deletedKeys = $input['deleted'] ?? [];

// Validate data
if (!is_array($enStrings) || !is_array($bmStrings)) {
    jsonError('Invalid data format');
}

// Remove deleted keys
foreach ($deletedKeys as $key) {
    unset($enStrings[$key]);
    unset($bmStrings[$key]);
}

// Sort keys alphabetically for cleaner files
ksort($enStrings);
ksort($bmStrings);

// Generate PHP file content
function generateLangFile($strings, $language)
{
    $langName = $language === 'en' ? 'English' : 'Bahasa Malaysia';

    $content = "<?php\n";
    $content .= "/**\n * CrossConnect MY - {$langName} Language Strings\n */\n\n";
    $content .= "return [\n";

    $currentCategory = '';

    foreach ($strings as $key => $value) {
        // Get category from key prefix
        $parts = explode('_', $key);
        $category = $parts[0] ?? 'other';

        // Add category comment
        if ($category !== $currentCategory) {
            if ($currentCategory !== '') {
                $content .= "\n";
            }
            $content .= "    // " . ucfirst($category) . "\n";
            $currentCategory = $category;
        }

        // Properly escape the value for PHP string
        // First escape backslashes, then single quotes
        $escapedValue = str_replace("\\", "\\\\", $value);
        $escapedValue = str_replace("'", "\\'", $escapedValue);
        $content .= "    '{$key}' => '{$escapedValue}',\n";
    }

    $content .= "];\n";

    return $content;
}

try {
    $enFile = __DIR__ . '/../../config/lang/en.php';
    $bmFile = __DIR__ . '/../../config/lang/bm.php';

    // Backup existing files
    $backupDir = __DIR__ . '/../../config/lang/backup';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    $timestamp = date('Y-m-d_H-i-s');
    if (file_exists($enFile)) {
        copy($enFile, "{$backupDir}/en_{$timestamp}.php");
    }
    if (file_exists($bmFile)) {
        copy($bmFile, "{$backupDir}/bm_{$timestamp}.php");
    }

    // Generate and save new files
    $enContent = generateLangFile($enStrings, 'en');
    $bmContent = generateLangFile($bmStrings, 'bm');

    $enResult = file_put_contents($enFile, $enContent);
    $bmResult = file_put_contents($bmFile, $bmContent);

    if ($enResult === false || $bmResult === false) {
        jsonError('Failed to write language files. Check file permissions.');
    }

    // Log activity
    logActivity('language_update', 'language', null, 'Updated language translations');

    jsonSuccess([
        'message' => 'Translations saved successfully',
        'en_keys' => count($enStrings),
        'bm_keys' => count($bmStrings)
    ]);

} catch (Exception $e) {
    error_log("Language save error: " . $e->getMessage());
    jsonError('Failed to save translations: ' . $e->getMessage());
}
