<?php
/**
 * Path Configuration
 * CrossConnect MY - Auto-detects app base path for subdirectory installations
 * 
 * This makes the application portable - works in root or subdirectory
 */

// Include language config early (before any HTML output) to set cookies properly
require_once __DIR__ . '/language.php';

// Define the app root directory (where this config file lives, go up one level)
define('APP_ROOT_DIR', dirname(__DIR__));

/**
 * Get the base path of the application
 * Always returns the root of the app, not the current script's directory
 * 
 * @return string Base path with trailing slash (e.g., '/' or '/hebats/')
 */
function getBasePath()
{
    static $basePath = null;

    if ($basePath === null) {
        // Simple approach: detect from SCRIPT_NAME by finding the app folder
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

        // Look for common app folders in the path
        if (preg_match('#^(/[^/]*hebats[^/]*)/#i', $scriptName, $matches)) {
            $basePath = $matches[1] . '/';
        } elseif (preg_match('#^(/[^/]+/)#', $scriptName, $matches)) {
            // First directory segment
            $basePath = $matches[1];
        } else {
            $basePath = '/';
        }
    }

    return $basePath;
}

/**
 * Get the base URL including protocol and host
 * 
 * @param bool $withPath Include the base path
 * @return string Full base URL
 */
function getBaseUrl($withPath = true)
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'mychurchfind.my';
    $base = $protocol . '://' . $host;

    if ($withPath) {
        $base .= getBasePath();
    }

    return rtrim($base, '/');
}

/**
 * Generate a URL path relative to the application base
 * 
 * @param string $path The path (can start with / or without)
 * @return string Full path from root
 */
function url($path = '')
{
    $basePath = getBasePath();
    $path = ltrim($path, '/');
    return $basePath . $path;
}

/**
 * Check if clean URLs are enabled
 * 
 * @return bool
 */
function isCleanUrlsEnabled()
{
    // Check if settings are available (database.php and settings.php loaded)
    if (function_exists('getSetting')) {
        return getSetting('clean_urls', '0') === '1';
    }
    return false;
}

/**
 * Generate church URL (supports clean URLs when enabled)
 * 
 * @param string $slug Church slug
 * @return string Church page URL
 */
function churchUrl($slug)
{
    if (isCleanUrlsEnabled()) {
        return url('church/' . urlencode($slug));
    }
    return url('church.php?slug=' . urlencode($slug));
}

/**
 * Generate event URL (supports clean URLs when enabled)
 * 
 * @param string $slug Event slug
 * @return string Event page URL
 */
function eventUrl($slug)
{
    if (isCleanUrlsEnabled()) {
        return url('events/' . urlencode($slug));
    }
    return url('event.php?slug=' . urlencode($slug));
}

/**
 * Generate state URL (supports clean URLs when enabled)
 * 
 * @param string $slug State slug
 * @return string State page URL
 */
function stateUrl($slug)
{
    if (isCleanUrlsEnabled()) {
        return url('state/' . urlencode($slug));
    }
    return url('state.php?s=' . urlencode($slug));
}

/**
 * Generate asset URL (CSS, JS, images)
 * 
 * @param string $path Asset path relative to app root
 * @return string Full asset path
 */
function asset($path)
{
    return url($path);
}

/**
 * Generate API endpoint URL
 * 
 * @param string $endpoint API endpoint (e.g., 'events.php')
 * @return string Full API path
 */
function api($endpoint)
{
    return url('api/' . ltrim($endpoint, '/'));
}

/**
 * Output JavaScript configuration for client-side path handling
 * Call this in the HTML head or before scripts that need it
 */
function outputJsConfig()
{
    $basePath = getBasePath();
    $baseUrl = getBaseUrl();
    $cleanUrls = isCleanUrlsEnabled();
    $debugMode = function_exists('getSetting') ? getSetting('debug_mode', '0') === '1' : false;

    // Translations for JavaScript
    $translations = [
        'loadMoreChurches' => __('load_more_churches'),
        'allStates' => __('all_states'),
        'viewAll' => __('view_all'),
        'noChurchesFound' => __('no_churches_found'),
        'noChurchesDesc' => __('no_churches_search_desc'),
        'somethingWentWrong' => __('something_went_wrong'),
        'tryAgainLater' => __('try_again_later'),
    ];

    echo '<script>window.AppConfig={basePath:"' . addslashes($basePath) . '",baseUrl:"' . addslashes($baseUrl) . '",cleanUrls:' . ($cleanUrls ? 'true' : 'false') . ',debug:' . ($debugMode ? 'true' : 'false') . ',translations:' . json_encode($translations, JSON_UNESCAPED_UNICODE) . '};';
    echo 'function debugLog(...args){if(window.AppConfig&&window.AppConfig.debug){console.log(...args);}}';
    echo '</script>';
}

// Define constants for convenience
define('BASE_PATH', getBasePath());
define('BASE_URL', getBaseUrl());
