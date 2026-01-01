<?php
/**
 * CrossConnect MY - Settings Helper
 * Functions to get/set configuration from database
 */

// Cache for settings to avoid repeated DB queries
$settingsCache = null;

/**
 * Get a setting value from database
 * 
 * @param string $key Setting key
 * @param mixed $default Default value if not found
 * @return mixed Setting value or default
 */
function getSetting($key, $default = null)
{
    global $settingsCache;

    // Load all settings into cache on first call
    if ($settingsCache === null) {
        loadSettingsCache();
    }

    return $settingsCache[$key] ?? $default;
}

/**
 * Get all settings for a group
 * 
 * @param string $group Setting group name
 * @return array Settings key-value pairs
 */
function getSettingsByGroup($group)
{
    $pdo = getDbConnection();
    if (!$pdo)
        return [];

    try {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_group = ?");
        $stmt->execute([$group]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    } catch (Exception $e) {
        error_log("Settings error: " . $e->getMessage());
        return [];
    }
}

/**
 * Set a setting value in database
 * 
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @param string $group Setting group (default: 'general')
 * @param bool $isEncrypted Whether the value should be encrypted
 * @return bool Success status
 */
function setSetting($key, $value, $group = 'general', $isEncrypted = false)
{
    global $settingsCache;

    $pdo = getDbConnection();
    if (!$pdo)
        return false;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value, setting_group, is_encrypted) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group)
        ");
        $result = $stmt->execute([$key, $value, $group, $isEncrypted ? 1 : 0]);

        // Clear cache
        $settingsCache = null;

        return $result;
    } catch (Exception $e) {
        error_log("Settings error: " . $e->getMessage());
        return false;
    }
}

/**
 * Load all settings into cache
 */
function loadSettingsCache()
{
    global $settingsCache;
    $settingsCache = [];

    $pdo = getDbConnection();
    if (!$pdo)
        return;

    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $row) {
            $settingsCache[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
        // Table might not exist yet - silently fail
        error_log("Settings cache error: " . $e->getMessage());
    }
}

/**
 * Clear settings cache (call after updates)
 */
function clearSettingsCache()
{
    global $settingsCache;
    $settingsCache = null;
}

/**
 * Check if settings table exists
 * 
 * @return bool
 */
function settingsTableExists()
{
    $pdo = getDbConnection();
    if (!$pdo)
        return false;

    try {
        $result = $pdo->query("SHOW TABLES LIKE 'settings'");
        return $result->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}
