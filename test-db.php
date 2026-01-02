<?php
// Step 1: Check if .env file exists
$envPath = __DIR__ . '/.env';
echo "<h2>Deployment Diagnostic</h2>";

echo "<h3>Step 1: Check .env file</h3>";
if (file_exists($envPath)) {
    echo "✅ .env file EXISTS<br>";
    echo "File size: " . filesize($envPath) . " bytes<br>";
} else {
    echo "❌ .env file MISSING!<br>";
    echo "Expected path: " . $envPath . "<br>";
    echo "<br><strong>FIX:</strong> Create a .env file with your database credentials.<br>";
    echo "<pre>
DB_HOST=localhost
DB_NAME=your_database
DB_USER=your_username
DB_PASS=your_password
DB_CHARSET=utf8mb4
APP_ENV=production
APP_DEBUG=false
</pre>";
    exit;
}

// Step 2: Parse .env manually
echo "<h3>Step 2: Parse .env</h3>";
$envContent = file_get_contents($envPath);
$lines = explode("\n", $envContent);
$env = [];
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || $line[0] === '#')
        continue;
    if (strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
}

echo "DB_HOST: " . ($env['DB_HOST'] ?? '❌ NOT SET') . "<br>";
echo "DB_NAME: " . ($env['DB_NAME'] ?? '❌ NOT SET') . "<br>";
echo "DB_USER: " . ($env['DB_USER'] ?? '❌ NOT SET') . "<br>";
echo "DB_PASS: " . (isset($env['DB_PASS']) ? '****** (set)' : '❌ NOT SET') . "<br>";

// Step 3: Try database connection
echo "<h3>Step 3: Test Database Connection</h3>";
try {
    $host = $env['DB_HOST'] ?? 'localhost';
    $dbname = $env['DB_NAME'] ?? '';
    $user = $env['DB_USER'] ?? '';
    $pass = $env['DB_PASS'] ?? '';
    $charset = $env['DB_CHARSET'] ?? 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "✅ Database connection SUCCESS!<br>";

    // Check tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . count($tables) . "<br>";

    if (in_array('churches', $tables)) {
        $count = $pdo->query("SELECT COUNT(*) FROM churches")->fetchColumn();
        echo "Churches in database: " . $count . "<br>";
    } else {
        echo "❌ 'churches' table not found. Did you import schema.sql?<br>";
    }

    if (in_array('settings', $tables)) {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key = 'enable_demo_data'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo "enable_demo_data: " . ($row['setting_value'] === '1' ? 'ON (showing demo)' : 'OFF') . "<br>";
            if ($row['setting_value'] === '1') {
                echo "<br><strong>To disable demo data, run:</strong><br>";
                echo "<code>UPDATE settings SET setting_value = '0' WHERE setting_key = 'enable_demo_data';</code>";
            }
        }
    }

} catch (PDOException $e) {
    echo "❌ Database connection FAILED<br>";
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<h3>Delete this file after testing!</h3>";