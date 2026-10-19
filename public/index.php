<?php
// Load environment variables FIRST
require_once __DIR__ . '/../app/Core/Autoloader.php';
App\Core\Autoloader::register();
App\Core\Autoloader::addNamespace('App', __DIR__ . '/../app');

App\Core\Dotenv::load();

// Load security configuration
$securityConfig = require __DIR__ . '/../config/security.php';

// Configure session
if ($securityConfig['cookie_httponly']) {
    ini_set('session.cookie_httponly', '1');
} else {
    ini_set('session.cookie_httponly', '0');
}
if ($securityConfig['cookie_secure']) {
    ini_set('session.cookie_secure', '1');
} else {
    ini_set('session.cookie_secure', '0');
}
if ($securityConfig['use_strict_mode']) {
    ini_set('session.use_strict_mode', '1');
} else {
    ini_set('session.use_strict_mode', '0');
}
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', $securityConfig['cookie_samesite']);
ini_set('session.gc_maxlifetime', (string)$securityConfig['session_timeout']);
//ini_set('session.name', 'AITRSID');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/', 
    'domain' => '',
    //'domain' => $_SERVER['HTTP_HOST'], // Dynamically set the domain
    'secure' => $securityConfig['cookie_secure'],
    'httponly' => $securityConfig['cookie_httponly'],
    'samesite' => $securityConfig['cookie_samesite']
]);

// Set session save path for shared hosting (must be writable)
$sessionPath = __DIR__ . '/../storage/sessions';
if (!is_dir($sessionPath)) {
    @mkdir($sessionPath, 0755, true);
}
if (is_writable($sessionPath)) {
    ini_set('session.save_path', $sessionPath);
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > $securityConfig['session_regenerate_interval']) {
        $sessionBackup = $_SESSION;
        session_regenerate_id(false);
        $_SESSION = $sessionBackup;
        $_SESSION['last_regeneration'] = time();
    }
}

// Load core files
require_once __DIR__ . '/../app/Core/FrameworkHeader.php';

// Get PDO connection
$pdo = require_once __DIR__ . '/../config/database.php';

// Initialize router
$router = new \App\Core\Router($pdo);

// Home routes
$router->addRoute('', 'HomeController', 'index');

// Auth routes
// $router->addRoute('register', 'AuthController', 'showRegister');        // GET /register
// $router->addRoute('login', 'AuthController', 'showLogin');              // GET /login
// $router->addRoute('register/submit', 'AuthController', 'register');     // POST /register
// $router->addRoute('login/submit', 'AuthController', 'login');           // POST /login
// $router->addRoute('logout', 'AuthController', 'logout');                // GET /logout
// Auth routes with registration is now dashboard-only
$router->addRoute('login', 'AuthController', 'showLogin');              // GET /login
$router->addRoute('login/submit', 'AuthController', 'login');           // POST /login
$router->addRoute('logout', 'AuthController', 'logout');                // POST /logout

// Protected route example
$router->addRoute('dashboard', 'DashboardController', 'index');
$router->addRoute('dashboard/profile', 'DashboardController', 'profile');

// Dispatch request - FIXED FOR SUBDIRECTORY
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
if ($scriptDir !== '/' && $scriptDir !== '\\') {
    if (stripos($requestUri, $scriptDir) === 0) {
        $requestUri = substr($requestUri, strlen($scriptDir));
    }
}

if (stripos($requestUri, '/public') === 0) {
    $requestUri = substr($requestUri, strlen('/public'));
}

$requestUri = trim($requestUri, '/');

// Make requestUri available to views
$GLOBALS['requestUri'] = $requestUri;

$router->dispatch($requestUri);