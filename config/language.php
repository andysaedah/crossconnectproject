<?php
/**
 * CrossConnect MY - Language Configuration
 * Handles language detection, switching, and translation helper
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define supported languages
if (!defined('SUPPORTED_LANGUAGES')) {
    define('SUPPORTED_LANGUAGES', ['en', 'bm']);
}
if (!defined('DEFAULT_LANGUAGE')) {
    define('DEFAULT_LANGUAGE', 'en');
}

// Initialize language immediately (before any output)
// This sets the cookie/session early to avoid headers already sent errors
if (!isset($GLOBALS['_lang_initialized'])) {
    $GLOBALS['_lang_initialized'] = true;

    // Check if language is being switched via GET parameter
    if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGUAGES)) {
        $lang = $_GET['lang'];
        $_SESSION['lang'] = $lang;
        // Only set cookie if headers haven't been sent yet
        if (!headers_sent()) {
            setcookie('lang', $lang, time() + (365 * 24 * 60 * 60), '/');
        }
        $GLOBALS['_current_lang'] = $lang;
    } elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], SUPPORTED_LANGUAGES)) {
        $GLOBALS['_current_lang'] = $_SESSION['lang'];
    } elseif (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], SUPPORTED_LANGUAGES)) {
        $_SESSION['lang'] = $_COOKIE['lang'];
        $GLOBALS['_current_lang'] = $_COOKIE['lang'];
    } else {
        $GLOBALS['_current_lang'] = DEFAULT_LANGUAGE;
    }
}

/**
 * Get current language from session, cookie, or default
 */
function getCurrentLanguage()
{
    return $GLOBALS['_current_lang'] ?? DEFAULT_LANGUAGE;
}

/**
 * Load language strings
 */
function loadLanguageStrings($lang)
{
    static $strings = null;
    static $loadedLang = null;

    if ($strings !== null && $loadedLang === $lang) {
        return $strings;
    }

    $langFile = __DIR__ . '/lang/' . $lang . '.php';

    if (file_exists($langFile)) {
        $strings = require $langFile;
        $loadedLang = $lang;
    } else {
        // Fallback to English
        $strings = require __DIR__ . '/lang/en.php';
        $loadedLang = 'en';
    }

    return $strings;
}

/**
 * Translation helper function
 * Usage: __('key') or __('key', ['name' => 'John'])
 */
function __($key, $replacements = [])
{
    static $currentLang = null;
    static $strings = null;

    $lang = getCurrentLanguage();

    if ($currentLang !== $lang) {
        $strings = loadLanguageStrings($lang);
        $currentLang = $lang;
    }

    // Get string, fallback to key if not found
    $text = $strings[$key] ?? $key;

    // Replace placeholders like {name}
    foreach ($replacements as $placeholder => $value) {
        $text = str_replace('{' . $placeholder . '}', $value, $text);
    }

    return $text;
}

/**
 * Echo translation (shorthand for echo __())
 */
function _e($key, $replacements = [])
{
    echo __($key, $replacements);
}

/**
 * Get language switch URL
 */
function getLanguageSwitchUrl($lang)
{
    $currentUrl = $_SERVER['REQUEST_URI'];
    $parsedUrl = parse_url($currentUrl);
    $path = $parsedUrl['path'] ?? '/';

    // Parse existing query string
    $query = [];
    if (isset($parsedUrl['query'])) {
        parse_str($parsedUrl['query'], $query);
    }

    // Set new language
    $query['lang'] = $lang;

    return $path . '?' . http_build_query($query);
}

/**
 * Check if current language matches
 */
function isCurrentLanguage($lang)
{
    return getCurrentLanguage() === $lang;
}
