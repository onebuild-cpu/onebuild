<?php
// Usage: php add_root.php "phone_number" "password"

if ($argc < 3) {
    echo "Error: Missing arguments.\n";
    echo "Usage: php " . basename(__FILE__) . " \"phone_number\" \"password\"\n";
    exit(1);
}

$phone = trim($argv[1]);
$password = $argv[2];

// Validate phone number: allows an optional leading '+' followed by digits only
if (!preg_match('/^\+?[0-9]+$/', $phone)) {
    echo "Error: Invalid phone number. Only numbers are allowed (with an optional leading '+').\n";
    exit(1);
}

// Simple .env parser loader
$envFile = __DIR__ . '/../.env'; // Adjust path if your .env is elsewhere relative to bin/
if (!file_exists($envFile)) {
    // Try looking in the current directory if bin/ is a subfolder
    $envFile = __DIR__ . '/.env';
}

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2) + [null, null];
        if ($name && $value) {
            putenv(trim($name) . '=' . trim(trim($value), '"\''));
        }
    }
}

// Fetch DB credentials from environment variables (supporting common naming conventions)
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_name = getenv('DB_DATABASE') ?: (getenv('DB_NAME') ?: 'onebuild');
$db_user = getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: 'root');
$db_pass = getenv('DB_PASSWORD') ?: (getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
$db_charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Check if a root user already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'root'");
    $stmt->execute();
    $rootCount = $stmt->fetchColumn();

    if ($rootCount > 0) {
        echo "Error: A root user already exists in the database. Action blocked.\n";
        exit(1);
    }

    // Securely hash the password using Argon2id
    $hash = password_hash($password, PASSWORD_ARGON2ID);
    if ($hash === false) {
        echo "Error: Failed to generate password hash.\n";
        exit(1);
    }

    // Insert the root user record
    $stmt = $pdo->prepare("INSERT INTO users (phone, password, role, is_verified) VALUES (?, ?, 'root', 1)");
    $stmt->execute([$phone, $hash]);

    echo "Success: Root user has been created successfully!\n";
    echo "Phone: $phone\n";
    echo "Role: root\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
}