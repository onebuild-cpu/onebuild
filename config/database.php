<?php
// Load environment variables
\App\Core\Dotenv::load();

$host = \App\Core\Dotenv::required('DB_HOST');
$dbname = \App\Core\Dotenv::required('DB_NAME');
$username = \App\Core\Dotenv::required('DB_USER');
$password = \App\Core\Dotenv::required('DB_PASS');
$charset = \App\Core\Dotenv::get('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// Determine if we're in production
$isProduction = 'production';
if (isset($_ENV['APP_ENV'])) {
    $isProduction = $_ENV['APP_ENV'];
}
$isProduction = ($isProduction === 'production');

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    
    if (!$isProduction) {
        die('Database connection error: ' . $e->getMessage());
    } else {
        http_response_code(503);
        die('Database connection error. Please try again later.');
    }
}

// ✅ RETURN the PDO instance so index.php can receive it
return $pdo;