<?php
/**
 * Database Configuration
 * CrossConnect MY - Malaysia Church Directory
 */

// Set Malaysia timezone (GMT+8) for the entire application
date_default_timezone_set('Asia/Kuala_Lumpur');

// Load environment variables from .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue; // Skip comments
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }
}

// Database credentials - fallback to defaults if .env not loaded
if (!defined('DB_HOST'))
    define('DB_HOST', 'localhost');
if (!defined('DB_NAME'))
    define('DB_NAME', 'directory_db');
if (!defined('DB_USER'))
    define('DB_USER', 'root');
if (!defined('DB_PASS'))
    define('DB_PASS', '');
if (!defined('DB_CHARSET'))
    define('DB_CHARSET', 'utf8mb4');

// App configuration - fallback to production for safety
if (!defined('APP_ENV'))
    define('APP_ENV', 'production');
if (!defined('APP_DEBUG'))
    define('APP_DEBUG', 'false');

// Convert APP_DEBUG string to boolean
define('IS_DEBUG', APP_DEBUG === 'true' || APP_DEBUG === '1');
define('IS_PRODUCTION', APP_ENV === 'production');

// Configure error reporting based on environment
if (IS_DEBUG && !IS_PRODUCTION) {
    // Development: Show all errors
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    // Production: Hide errors, log them instead
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
}

/**
 * Get PDO Database Connection
 * @return PDO|null
 */
function getDbConnection()
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return null;
        }
    }

    return $pdo;
}

/**
 * Execute a query and return results
 * @param string $sql
 * @param array $params
 * @return array|false
 */
function dbQuery($sql, $params = [])
{
    $pdo = getDbConnection();
    if (!$pdo)
        return false;

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Query failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute a query and return single row
 * @param string $sql
 * @param array $params
 * @return array|false
 */
function dbQuerySingle($sql, $params = [])
{
    $pdo = getDbConnection();
    if (!$pdo)
        return false;

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Query failed: " . $e->getMessage());
        return false;
    }
}
