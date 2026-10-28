# Project dump: onebuild

Total files: 54

---

## File tree

```
app/Controllers/AuthController.php
app/Controllers/DashboardController.php
app/Controllers/HomeController.php
app/Controllers/UserController.php
app/Core/Autoloader.php
app/Core/Controller.php
app/Core/CsrfProtection.php
app/Core/Dotenv.php
app/Core/FrameworkHeader.php
app/Core/Model.php
app/Core/RateLimiter.php
app/Core/RateLimitException.php
app/Core/Router.php
app/Core/SecurityLogger.php
app/Core/Validator.php
app/Models/HomeModel.php
app/Models/UserModel.php
bin/add_root.php
bin/change_pwd.php
bin/dump_file.cpp
bin/dump_file.php
config/database.php
config/security.php
database/initial_database.sql
public/assets/css/bootstrap-icons.min.css
public/assets/css/bootstrap.min.css
public/assets/css/dashboard.css
public/assets/css/summernote-bs5.min.css
public/assets/js/bootstrap.bundle.min.js
public/assets/js/jquery.slim.min.js
public/assets/js/summernote-bs5.min.js
public/favicon/site.webmanifest
public/uploads/avatars/.htaccess
public/uploads/documents/.htaccess
public/uploads/images/.htaccess
public/uploads/videos/.htaccess
public/.htaccess
public/index.php
public/robots.txt
tutorial/Claude - Build a Blog with Onebuild (CRUD Tutorial).md
tutorial/Gemini - step_by_step_guide_blog_category_crud_with_onebuild.md
views/auth/login.php
views/dashboard/_sidebar.php
views/dashboard/index.php
views/dashboard/profile.php
views/errors/404.php
views/errors/500.php
views/home/index.php
views/layouts/footer.php
views/layouts/header.php
views/layouts/navbar.php
.env.example
.gitignore
.htaccess
```

---

### app/Controllers/AuthController.php

```php
<?php
/**
 * Authentication Controller
 * Handles user registration, login, logout, and rate limiting
 */
namespace App\Controllers;

use PDO;
use App\Core\Controller;
use App\Models\UserModel;
use App\Core\CsrfProtection;
use App\Core\RateLimiter;
use App\Core\RateLimitException;
use App\Core\Validator;
use App\Core\SecurityLogger;
use Exception;

/**
 * AuthController manages authentication flows
 */
class AuthController extends Controller
{
    /** @var UserModel User model instance */
    private UserModel $model;

    /** @var RateLimiter Rate limiter instance (DB-backed) */
    private RateLimiter $rateLimiter;

    /**
     * Constructor
     * 
     * @param PDO $pdo Database connection
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->model       = new UserModel($pdo);
        $this->rateLimiter = new RateLimiter($pdo);
    }

    /**
     * Display registration form
     * 
     * Commented out because registration can only be done by root/admin
     * 
     * @return void
     */
    // public function showRegister(): void
    // {
    //     $this->view('auth/register', ['pageTitle' => 'Register']);
    // }

    /**
     * Display login form
     * 
     * @return void
     */
    public function showLogin(): void
    {
        $this->view('auth/login', ['pageTitle' => 'Sign In']);
    }

    /**
     * Process user registration
     * Validates input, checks CSRF, enforces rate limiting
     * Commented out because registration can only be done by root/admin
     * @return void
     */
    // public function register(): void
    // {
    //     if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    //         $this->redirect('/register');
    //         return;
    //     }

    //     // Rate limiting: 3 attempts per hour.
    //     // Throws RateLimitException when the caller has exceeded the budget.
    //     try {
    //         $this->rateLimiter->consume('register', 'register');
    //     } catch (RateLimitException $e) {
    //         $wait = RateLimiter::formatRetryAfter($e->getRetryAfter());
    //         $_SESSION['error'] = "Too many registration attempts. Please try again in {$wait}.";
    //         $this->redirect('/register');
    //         return;
    //     }

    //     // CSRF protection
    //     CsrfProtection::requireValidToken();

    //     $phone = Validator::sanitize($_POST['phone'] ?? '', 50);
    //     $password = $_POST['password'] ?? '';
    //     $confirm = $_POST['password_confirm'] ?? '';

    //     // Validate phone
    //     if (empty($phone)) {
    //         $_SESSION['error'] = 'Phone number is required.';
    //         $this->redirect('/register');
    //         return;
    //     }

    //     if (!Validator::phone($phone)) {
    //         $_SESSION['error'] = 'Invalid phone number format. Use E.164 format (e.g., +628123456789).';
    //         $this->redirect('/register');
    //         return;
    //     }

    //     // Validate password strength
    //     $pwCheck = Validator::passwordStrength($password);
    //     if (!$pwCheck['valid']) {
    //         $_SESSION['error'] = implode('; ', $pwCheck['errors']);
    //         $this->redirect('/register');
    //         return;
    //     }

    //     // Password match
    //     if ($password !== $confirm) {
    //         $_SESSION['error'] = 'Passwords do not match.';
    //         $this->redirect('/register');
    //         return;
    //     }

    //     try {
    //         $userId = $this->model->register($phone, $password);
            
    //         // Log successful registration
    //         if (class_exists(SecurityLogger::class)) {
    //             $logger = new SecurityLogger();
    //             $logger->logAuth('register', true, ['user_id' => $userId, 'phone' => $phone]);
    //         }
            
    //         $_SESSION['success'] = 'Registered! Please sign in.';
    //         CsrfProtection::invalidateToken();
    //         $this->redirect('/login');
    //     } catch (Exception $e) {
    //         $_SESSION['error'] = $e->getMessage();
    //         $this->redirect('/register');
    //     }
    // }

    /**
     * Process user login
     * Validates credentials, checks CSRF, enforces rate limiting
     * 
     * @return void
     */
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login');
            return;
        }

        // Rate limiting: 5 attempts per 5 minutes.
        // Throws RateLimitException when the caller has exceeded the budget.
        try {
            $this->rateLimiter->consume('login', 'login');
        } catch (RateLimitException $e) {
            $wait = RateLimiter::formatRetryAfter($e->getRetryAfter());
            $_SESSION['error'] = "Too many login attempts. Please try again in {$wait}.";
            $this->redirect('/login');
            return;
        }

        // CSRF protection
        CsrfProtection::requireValidToken();

        $phone = Validator::sanitize($_POST['phone'] ?? '', 50);
        $password = $_POST['password'] ?? '';

        // Validate phone
        if (empty($phone)) {
            $_SESSION['error'] = 'Phone number is required.';
            $this->redirect('/login');
            return;
        }

        if (!Validator::phone($phone)) {
            $_SESSION['error'] = 'Invalid phone number format.';
            $this->redirect('/login');
            return;
        }

        $user = $this->model->login($phone, $password);
        
        if ($user) {
            // Clear rate limit on successful login
            $this->rateLimiter->clear('login', 'login');

            // Log successful login
            if (class_exists(SecurityLogger::class)) {
                $logger = new SecurityLogger();
                $logger->logAuth('login', true, ['user_id' => $user['id'], 'phone' => $phone]);
            }

            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'] ?? null;
            $_SESSION['avatar'] = $user['avatar'] ?? null;
            $_SESSION['phone'] = $user['phone'] ?? null;
            
            // Regenerate session ID for security
            session_regenerate_id(true); 
            
            // Regenerate CSRF token after privilege elevation
            CsrfProtection::invalidateToken();
            
            $this->redirect('/dashboard');
        } else {
            // Log failed login
            if (class_exists(SecurityLogger::class)) {
                $logger = new SecurityLogger();
                $logger->logAuth('login', false, ['phone' => $phone, 'reason' => 'invalid_credentials']);
            }
            
            $_SESSION['error'] = 'Invalid phone number or password.';
            $this->redirect('/login');
        }
    }

    /**
     * Process user logout
     * Destroys session and regenerates CSRF token
     * 
     * @return void
     */
    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/dashboard');
            return;
        }
        \App\Core\CsrfProtection::requireValidToken();

        $message = 'You have been signed out.';

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        
        session_start();
        CsrfProtection::invalidateToken();
        $_SESSION['success'] = $message;
        $this->redirect('/login');
    }
}
```

### app/Controllers/DashboardController.php

```php
<?php
namespace App\Controllers;

use PDO;
use App\Core\Controller;
use App\Models\UserModel;
use Exception;

class DashboardController extends Controller
{
    private UserModel $userModel;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->userModel = new UserModel($pdo);
    }

    public function index(): void
    {
        $this->requireLogin();

        $this->view('dashboard/index', [
            'pageTitle' => 'Dashboard',
            'metaDescription' => 'Admin Dashboard - Overview of your application',
        ]);
    }

    public function profile(): void
    {
        $this->requireLogin();

        $userId = (int)$_SESSION['user_id'];
        $user = $this->userModel->findById($userId);

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \App\Core\CsrfProtection::requireValidToken();
            $action = $_POST['action'] ?? '';

            try {
                if ($action === 'update_username') {
                    $username = trim($_POST['username'] ?? '');
                    $this->userModel->updateUsername($userId, $username);
                    $_SESSION['username'] = $username ?: null;
                    $success = 'Username updated successfully';
                    $user = $this->userModel->findById($userId);
                } elseif ($action === 'change_password') {
                    $currentPassword = $_POST['current_password'] ?? '';
                    $newPassword = $_POST['new_password'] ?? '';
                    $confirmPassword = $_POST['confirm_password'] ?? '';

                    if ($newPassword !== $confirmPassword) {
                        throw new Exception('New passwords do not match');
                    }

                    $this->userModel->changePassword($userId, $currentPassword, $newPassword);
                    $success = 'Password changed successfully';
                } elseif ($action === 'update_avatar') {
                    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                        $avatarPath = $this->handleAvatarUpload($_FILES['avatar'], $userId);
                        $this->userModel->updateAvatar($userId, $avatarPath);
                        $_SESSION['avatar'] = $avatarPath;
                        $success = 'Avatar updated successfully';
                        $user = $this->userModel->findById($userId);
                    } else {
                        throw new Exception('Please select a valid image file');
                    }
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        $this->view('dashboard/profile', [
            'pageTitle' => 'Profile',
            'metaDescription' => 'User Profile',
            'user' => $user,
            'error' => $error,
            'success' => $success,
        ]);
    }

    private function handleAvatarUpload(array $file, int $userId): string
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if ($file['size'] > $maxSize) {
            throw new Exception('File size must be less than 2MB');
        }

        // Detect actual MIME type using file content (not user-supplied)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedMimes, true)) {
            throw new Exception('Invalid file type. Allowed: JPG, PNG, GIF, WebP');
        }

        // Map MIME to safe extension
        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        $ext = $extMap[$mime] ?? 'jpg';

        $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
        
        $uploadDir = __DIR__ . '/../../public/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to upload avatar');
        }

        return '/uploads/avatars/' . $filename;
    }
}
```

### app/Controllers/HomeController.php

```php
<?php
namespace App\Controllers;

use PDO;
use App\Core\Controller;

class HomeController extends Controller
{
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    public function index(): void
    {
        $this->view('home/index', [
            'pageTitle' => 'Welcome to NewSite',
            'metaDescription' => 'Build Your Amazing New Website',
        ]);
    }
}
```

### app/Controllers/UserController.php

```php

```

### app/Core/Autoloader.php

```php
<?php
/**
 * PSR-4 Compliant Autoloader
 * No Composer required - pure PHP implementation
 */
namespace App\Core;

class Autoloader
{
    private static array $prefixes = [];

    public static function register(): void
    {
        spl_autoload_register([self::class, 'loadClass']);
    }

    public static function addNamespace(string $prefix, string $baseDir): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        self::$prefixes[$prefix][] = $baseDir;
    }

    public static function loadClass(string $class): void
    {
        foreach (self::$prefixes as $prefix => $dirs) {
            if (str_starts_with($class, $prefix)) {
                $relativeClass = substr($class, strlen($prefix));
                $file = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

                foreach ($dirs as $dir) {
                    $fullPath = $dir . $file;
                    if (file_exists($fullPath)) {
                        require_once $fullPath;
                        return;
                    }
                }
            }
        }
    }
}
```

### app/Core/Controller.php

```php
<?php
/**
 * Base Controller Class
 * Provides common functionality for all controllers
 */
namespace App\Core;

use PDO;
use Exception;

/**
 * Abstract base controller providing view rendering, redirection, and auth helpers
 */
abstract class Controller
{
    /** @var PDO Database connection */
    protected PDO $pdo;

    /**
     * Constructor - injects database connection
     * 
     * @param PDO $pdo Database connection instance
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Render a view file with optional data
     * 
     * @param string $path View path relative to views/ directory
     * @param array $data Data to extract into view scope
     * @throws Exception If view file not found
     */
    protected function view(string $path, array $data = []): void
    {
        $file = __DIR__ . "/../../views/{$path}.php";

        if (!file_exists($file)) {
            throw new Exception("View not found: {$path}");
        }

        extract($data, EXTR_SKIP);
        require $file;
    }

    /**
     * Redirect to a URL
     * Prepends base URL if not an absolute URL
     * 
     * @param string $url Target URL (relative or absolute)
     * @return never
     */
    protected function redirect(string $url): void
    {
        // Prepend base URL if not absolute
        if (!preg_match('~^https?://~', $url)) {
            $base = $GLOBALS['base'] ?? '';
            $url = $base . '/' . ltrim($url, '/');
        }
        header("Location: {$url}");
        exit();
    }

    /**
     * Return JSON response with status code
     * 
     * @param array $data Response data
     * @param int $statusCode HTTP status code
     * @return never
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    /**
     * Require user to be logged in
     * Redirects to login if not authenticated
     * 
     * @return never if not logged in
     */
    protected function requireLogin(): void
    {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Please login first.';
            $this->redirect('/login');
        }
    }

    /**
     * Require admin/root role
     * Redirects to home if not authorized
     * 
     * @return never if not admin
     */
    protected function requireAdmin(): void
    {
        $this->requireLogin();

        $role = $_SESSION['role'] ?? '';
        if (!in_array($role, ['admin', 'root'], true)) {
            $_SESSION['error'] = 'Access denied. Admin privileges required.';
            $this->redirect('/');
        }
    }
}
```

### app/Core/CsrfProtection.php

```php
<?php
/**
 * Enhanced CSRF Protection
 * Provides secure token generation, validation, and management
 */
namespace App\Core;

class CsrfProtection {
    private static $tokenName = 'csrf_token';
    private static $tokenLifetime = 3600; // 1 hour
    
    /**
     * Generate CSRF token with expiration
     */
    public static function generateToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $expiry = time() + self::$tokenLifetime;
        
        $_SESSION[self::$tokenName] = [
            'token' => $token,
            'expiry' => $expiry,
            'created' => time()
        ];

        return $token;
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateToken(string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[self::$tokenName])) {
            return false;
        }
        
        $storedData = $_SESSION[self::$tokenName];
        
        // Check if token has expired
        if (time() > $storedData['expiry']) {
            unset($_SESSION[self::$tokenName]);
            return false;
        }
        
        // Validate token using timing-safe comparison
        return hash_equals($storedData['token'], $token);
    }
    
    /**
     * Get current token or generate new one
     */
    public static function getToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if current token is valid and not expired
        if (isset($_SESSION[self::$tokenName])) {
            $storedData = $_SESSION[self::$tokenName];
            if (time() <= $storedData['expiry']) {
                return $storedData['token'];
            }
        }
        
        // Generate new token if none exists or expired
        return self::generateToken();
    }
    
    /**
     * Invalidate current token
     */
    public static function invalidateToken(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION[self::$tokenName]);
    }
    
    /**
     * Check and validate CSRF token from the request.
     * Only accepts the token from the POST body or the X-CSRF-Token header.
     * GET-based tokens are rejected because they leak via Referer, history,
     * bookmarks, and access logs.
     */
    public static function checkRequest(): bool {
        $token = '';

        // 1. Prefer the token submitted in a form body.
        if (isset($_POST['csrf'])) {
            $token = $_POST['csrf'];
        }

        // 2. Fall back to the header used by AJAX/fetch calls.
        elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        // No token supplied through an allowed channel.
        if (empty($token)) {
            return false;
        }

        // Validate against the stored session token.
        return self::validateToken($token);
    }
    
    /**
     * Require valid CSRF token or die
     */
    public static function requireValidToken(): void {
        if (!self::checkRequest()) {
            // Re-read the submitted token for logging — POST body or header only.
            $token = '';
            if (isset($_POST['csrf'])) {
                $token = $_POST['csrf'];
            } elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
                $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
            }
            $sessionToken = null;
            if (isset($_SESSION[self::$tokenName])) {
                $sessionToken = $_SESSION[self::$tokenName];
            }
            
            $errorDetails = [
                'submitted_token' => $token ? 'present' : 'missing',
                'session_token' => $sessionToken ? 'present' : 'missing',
                'token_expired' => false,
                'referer' => 'none',
                'user_agent' => 'none'
            ];
            
            if (isset($_SERVER['HTTP_REFERER'])) {
                $errorDetails['referer'] = $_SERVER['HTTP_REFERER'];
            }
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $errorDetails['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            }
            
            if ($sessionToken && isset($sessionToken['expiry'])) {
                $errorDetails['token_expired'] = time() > $sessionToken['expiry'];
                $created = 0;
                if (isset($sessionToken['created'])) {
                    $created = $sessionToken['created'];
                }
                $errorDetails['token_age'] = time() - $created;
            }
            
            // Log CSRF failure with details
            if (class_exists(\App\Core\SecurityLogger::class)) {
                $logger = new \App\Core\SecurityLogger();
                $logger->logSuspicious('csrf_token_invalid', 'Invalid or missing CSRF token', $errorDetails);
            }
            
            http_response_code(419);
            die('CSRF token validation failed');
        }
    }
    
    /**
     * Generate HTML input field for CSRF token
     */
    public static function getHiddenInput(): string {
        $token = self::getToken();
        return '<input type="hidden" name="csrf" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Generate meta tag for CSRF token (for AJAX)
     */
    public static function getMetaTag(): string {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
}

// Legacy functions for backward compatibility
function generateCsrfToken(): string {
    return CsrfProtection::getToken();
}

function validateCsrfToken(string $token): bool {
    return CsrfProtection::validateToken($token);
}

function checkCsrf(): void {
    CsrfProtection::requireValidToken();
}

function unsetCsrfToken(): void {
    CsrfProtection::invalidateToken();
}
?>
```

### app/Core/Dotenv.php

```php
<?php
/**
 * Simple Dotenv Loader
 * Lightweight .env file parser - no dependencies required
 */
namespace App\Core;
use RuntimeException;

class Dotenv
{
    /**
     * Load environment variables from .env file
     */
    public static function load($path = null) {
        // 1. If no path is provided, calculate it relative to THIS file (Dotenv.php)
        // __DIR__ is 'domain_folder/app/core'
        // dirname(__DIR__, 1) goes up to 'domain_folder'
        if ($path === null) {
            $path = dirname(__DIR__, 2) . '/.env';
        }

        // 2. If the file doesn't exist, check a fallback (just in case)
        if (!file_exists($path)) {
            $docRoot = '';
            if (isset($_SERVER['DOCUMENT_ROOT'])) {
                $docRoot = $_SERVER['DOCUMENT_ROOT'];
            }
            $path = $docRoot . '/../.env';
        }

        // 3. If it still doesn't exist, exit early
        if (!file_exists($path)) {
            return;
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments and empty lines
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }
            
            // Parse key=value
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove surrounding quotes if present
                if (preg_match('/^["\'](.*)["\']$/', $value, $matches)) {
                    $value = $matches[1];
                }
                
                // Only set if not already set in environment
                if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
    }
    
    /**
     * Get environment variable with fallback
     */
    public static function get(string $key, $default = null)
    {
        $value = null;
        if (isset($_ENV[$key])) {
            $value = $_ENV[$key];
        } elseif (isset($_SERVER[$key])) {
            $value = $_SERVER[$key];
        } elseif (getenv($key) !== false) {
            $value = getenv($key);
        } else {
            $value = $default;
        }
        return $value;
    }
    
    /**
     * Get required environment variable (throws if not set)
     */
    public static function required(string $key)
    {
        $value = self::get($key);
        if ($value === null) {
            throw new RuntimeException("Required environment variable '$key' is not set");
        }
        return $value;
    }
}
```

### app/Core/FrameworkHeader.php

```php
<?php
/**
 * One Build - Security Headers & Configuration
 * Version: 1.0
 * Author: Awan
 *
 * This file sends all important HTTP headers for security & caching,
 * replacing .htaccess rules when using Nginx shared hosting.
 */

// Determine environment early
$appEnv = $_ENV['APP_ENV'] ?? 'production';
$isProduction = ($appEnv === 'production');

// Global error handler - prevent stack traces in production
if ($isProduction) {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../../storage/logs/php_errors.log');

    set_error_handler(function ($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        error_log("PHP Error [$severity]: $message in $file:$line");
        return true;
    });

    register_shutdown_function(function () {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            error_log("Fatal Error: {$error['message']} in {$error['file']}:{$error['line']}");
        }
    });
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Session cookie hardening (before session_start)
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = session_get_cookie_params();
    $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    
    session_set_cookie_params([
        'lifetime' => 3600,
        'path' => $cookieParams['path'] ?? '/',
        'domain' => $cookieParams['domain'] ?? '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $secure ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', '3600');
    ini_set('session.sid_length', '32');
    ini_set('session.sid_bits_per_character', '6');
}

// Generate CSP nonce
if (!isset($GLOBALS['csp_nonce'])) {
    $GLOBALS['csp_nonce'] = base64_encode(random_bytes(16));
}

// Load security configuration early so $isLocalhost is available below
$securityConfig = require __DIR__ . '/../../config/security.php';

// Utility: check if a header already exists (case-insensitive)
if (!function_exists('header_exists')) {
    function header_exists($name) {
        $name = trim($name);
        foreach (headers_list() as $header) {
            if (stripos($header, $name . ':') === 0) {
                return true;
            }
        }
        return false;
    }
}

// Add header only if not already set
if (!function_exists('safe_header')) {
    function safe_header($string) {
        [$name] = explode(":", $string, 2);
        if (!header_exists(trim($name))) {
            header($string);
        }
    }
}

// Detect the raw HTTP host (used for base URL and HTTPS redirect)
$httpHost = '';
if (isset($_SERVER['HTTP_HOST'])) {
    $httpHost = $_SERVER['HTTP_HOST'];
}
if (!preg_match('/^[a-zA-Z0-9.\-:\[\]]+$/', $httpHost)) {
    $httpHost = 'localhost';
}

// Localhost detection comes from config/security.php — single source of truth
$isLocalhost = $securityConfig['is_localhost'];

// Detect HTTPS (direct or via reverse proxy)
$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $isHttps = true;
}
// ------------------------------------------------------------------

// Force HTTPS redirect (only in production, not localhost, not already HTTPS)
if ($isProduction && !$isLocalhost && !$isHttps) {
    if (!headers_sent()) {
        $redirectURL = 'https://' . $httpHost . $_SERVER['REQUEST_URI'];
        header("Location: $redirectURL", true, 301);
        exit();
    }
}

// Security headers
safe_header("X-Powered-By: ");
safe_header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
safe_header("Pragma: no-cache");
safe_header("Expires: 0");
safe_header("X-Content-Type-Options: nosniff");
safe_header("X-Frame-Options: DENY");
safe_header("Referrer-Policy: strict-origin-when-cross-origin");
safe_header("X-Permitted-Cross-Domain-Policies: none");
safe_header("Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()");

// COOP only on HTTPS
if ($isHttps) {
    safe_header("Cross-Origin-Opener-Policy: same-origin");
}

// HSTS only on HTTPS
if ($isHttps) {
    safe_header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
}

// Content Security Policy (CSP)
$nonce = $GLOBALS['csp_nonce'];
$csp = "default-src 'self'; "
     . "base-uri 'self'; "
     . "object-src 'none'; "
     . "frame-ancestors 'self'; "

// Script sources - Nonce-based only
     . "script-src 'self' 'nonce-{$nonce}' "
     . "https://cdn.jsdelivr.net "
     . "https://www.googletagmanager.com "
     . "https://*.googletagmanager.com "
     . "https://googletagmanager.com "
     . "https://tagmanager.google.com "
     . "https://www.google-analytics.com "
     . "https://*.google-analytics.com "
     . "https://analytics.google.com "
     . "https://*.analytics.google.com "
     . "https://connect.facebook.net "
     . "https://www.instagram.com "
     . "https://platform.instagram.com "
     . "https://www.tiktok.com "
     . "https://s0.wp.com https://s1.wp.com https://s2.wp.com; "

// Style sources
     . "style-src 'self' 'nonce-{$nonce}' "
     . "https://cdn.jsdelivr.net "
     . "https://googletagmanager.com "
     . "https://tagmanager.google.com "
     . "https://www.googletagmanager.com "
     . "https://sf16-website-login.neutral.ttwstatic.com; "

// Image sources
     . "img-src 'self' data: blob: https: "
     . "https://www.googletagmanager.com "
     . "https://*.google-analytics.com "
     . "https://*.googletagmanager.com "
     . "https://ssl.gstatic.com "
     . "https://www.gstatic.com "
     . "https://*.g.doubleclick.net "
     . "https://*.google.com "
     . "https://www.google.com "
     . "https://p16-va.tiktokcdn.com "
     . "https://www.facebook.com "
     . "https://scontent.xx.fbcdn.net; "

// Connection sources
     . "connect-src 'self' "
     . "https://ipinfo.io "
     . "https://www.googletagmanager.com "
     . "https://*.googletagmanager.com "
     . "https://www.google-analytics.com "
     . "https://*.google-analytics.com "
     . "https://analytics.google.com "
     . "https://*.analytics.google.com "
     . "https://www.google.com "
     . "https://*.google.com "
     . "https://*.g.doubleclick.net "
     . "https://pagead2.googlesyndication.com "
     . "https://region1.google-analytics.com "
     . "https://region1.analytics.google.com "
     . "https://stats.g.doubleclick.net "
     . "https://www.instagram.com "
     . "https://www.tiktok.com "
     . "https://www.facebook.com; "

// Font sources
     . "font-src 'self' data: blob: https://fonts.gstatic.com https://cdn.jsdelivr.net; "

// Frame sources
     . "frame-src 'self' "
     . "https://www.googletagmanager.com "
     . "https://googletagmanager.com "
     . "https://tagmanager.google.com "
     . "https://www.google.com "
     . "https://www.youtube.com "
     . "https://youtube.com "
     . "https://www.facebook.com "
     . "https://connect.facebook.net "
     . "https://www.instagram.com "
     . "https://platform.instagram.com "
     . "https://www.tiktok.com "
     . "https://www.tiktokcdn.com; "

// Media sources
     . "media-src 'self' data: blob:; "

// CSP violation reporting
     . "report-uri /csp-report; "
     . "report-to csp-endpoint;";

safe_header("Content-Security-Policy: $csp");

// Report-To header for modern CSP reporting
safe_header('Report-To: {"group":"csp-endpoint","max_age":10886400,"endpoints":[{"url":"/csp-report"}]}');

// Define base URL
$protocol = $isHttps ? 'https' : 'http';
$host = $httpHost ?: 'localhost';
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$scriptDir = rtrim($scriptDir, '/\\');
$base = $protocol . '://' . $host . $scriptDir;

$baseImg = $base . '/uploads/media/';
$baseImgThumb = $base . '/uploads/media/thumbs/thumb_';

// Set as globals
$GLOBALS['base'] = $base;
$GLOBALS['baseImg'] = $baseImg;
$GLOBALS['baseImgThumb'] = $baseImgThumb;
$GLOBALS['is_localhost'] = $isLocalhost;
$GLOBALS['is_https'] = $isHttps;
$GLOBALS['is_production'] = $isProduction;
$GLOBALS['app_env'] = $appEnv;

// Unified exception handler (loads views/errors/500.php cleanly)
set_exception_handler(function ($exception) {
    error_log("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine());
    http_response_code(500);
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    
    $file = __DIR__ . '/../../views/errors/500.php';
    if (file_exists($file)) {
        $pageTitle = '500 Internal Server Error';
        require $file;
    } else {
        echo '<!DOCTYPE html><html><head><title>Server Error</title>'
             . '<meta name="viewport" content="width=device-width, initial-scale=1">'
             . '<style>body{font-family:sans-serif;max-width:600px;margin:50px auto;padding:20px;text-align:center}'
             . 'h1{color:#dc3545}</style></head><body>'
             . '<h1>500 - Internal Server Error</h1>'
             . '<p>Something went wrong. Please try again later.</p>'
             . '</body></html>';
    }
    exit();
});
```

### app/Core/Model.php

```php
<?php
/**
 * Base Model Class
 * Provides common database operations for all models
 */
namespace App\Core;

use PDO;
use Exception;

class Model
{
    protected PDO $pdo;
    protected string $table = '';
    protected bool $timestamps = true;

// Whitelist of allowed table names for security
    // Empty array means allow any valid table name (alphanumeric + underscore)
    protected static array $allowedTables = [];

    public function __construct(PDO $pdo)
    {
        if (!$pdo) {
            throw new Exception('PDO connection is required.');
        }
        $this->pdo = $pdo;
        
        // Validate table name on construction
        if ($this->table && !self::isValidTable($this->table)) {
            throw new Exception("Invalid table name: {$this->table}");
        }
    }

    /**
     * Validate table name against whitelist (if configured)
     */
    protected static function isValidTable(string $table): bool
    {
        // If whitelist is empty, allow any valid table name format
        if (empty(self::$allowedTables)) {
            return true;
        }
        return in_array($table, self::$allowedTables, true);
    }

    /**
     * Validate and sanitize table name for use in queries
     * Only allows alphanumeric and underscore
     */
    protected function validateTable(string $table): string
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new Exception("Invalid table name format: $table");
        }
        if (!self::isValidTable($table)) {
            throw new Exception("Table not in allowed list: $table");
        }
        return $table;
    }

    /**
     * Find all records
     */
    public function findAll(string $orderBy = 'id ASC'): array
    {
        $table = $this->validateTable($this->table);
        
        if (!preg_match('/^[a-zA-Z0-9_]+ (ASC|DESC)$/i', $orderBy)) {
            $orderBy = 'id ASC';
        }

        $sql = "SELECT * FROM {$table} ORDER BY {$orderBy}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find all published records
     */
    public function findAllPublished(string $orderBy = 'id ASC'): array
    {
        $table = $this->validateTable($this->table);
        
        if (!preg_match('/^[a-zA-Z0-9_]+ (ASC|DESC)$/i', $orderBy)) {
            $orderBy = 'id ASC';
        }

        $sql = "SELECT * FROM {$table} WHERE status = 'published' ORDER BY {$orderBy}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find record by ID
     */
    public function findById(int $id): ?array
    {
        $table = $this->validateTable($this->table);
        $sql = "SELECT * FROM {$table} WHERE id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Find record by slug
     */
    public function findBySlug(string $slug): ?array
    {
        $table = $this->validateTable($this->table);
        $sql = "SELECT * FROM {$table} WHERE slug = :slug LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Insert new record
     * Column names are validated before being interpolated into SQL,
     * so callers can safely build the data array dynamically.
     */
    public function insert(array $data): int
    {
        $table = $this->validateTable($this->table);

        // Auto-fill created_at / updated_at when timestamps are enabled.
        if ($this->timestamps) {
            $createdAt = 'created_at';
            $updatedAt = 'updated_at';
            if (!isset($data[$createdAt])) {
                $data[$createdAt] = date('Y-m-d H:i:s');
            }
            if (!isset($data[$updatedAt])) {
                $data[$updatedAt] = date('Y-m-d H:i:s');
            }
        }

        // Reject any column name that is not a plain identifier.
        // This prevents SQL injection through attacker-controlled keys.
        foreach ($data as $key => $value) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
                throw new Exception("Invalid column name: $key");
            }
        }

        // Build the INSERT statement from the validated column names.
        $columns      = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $columns);

        $sql = "INSERT INTO {$table} (" . implode(',', $columns) . ")
                VALUES (" . implode(',', $placeholders) . ")";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update record by ID
     */
    public function update(int $id, array $data): bool
    {
        $table = $this->validateTable($this->table);
        unset($data['id']);

        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $set = [];
        foreach ($data as $key => $value) {
            // Validate column names
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
                throw new Exception("Invalid column name: $key");
            }
            $set[] = "{$key} = :{$key}";
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE id = :id";
        $data['id'] = $id;

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Delete record by ID
     */
    public function delete(int $id): bool
    {
        $table = $this->validateTable($this->table);
        $sql = "DELETE FROM {$table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Execute custom query (returns multiple rows)
     * Table names in custom queries must be validated by caller
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute custom query (returns single row)
     * Table names in custom queries must be validated by caller
     */
    public function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}
```

### app/Core/RateLimiter.php

```php
<?php
/**
 * Rate Limiter
 * Provides rate limiting for sensitive endpoints.
 * Storage is the `rate_limits` database table, not the session, so the
 * limiter cannot be bypassed by dropping the session cookie.
 */
namespace App\Core;

use PDO;
use Exception;

class RateLimiter
{
    /** @var PDO Database connection */
    private PDO $pdo;

    /**
     * Attempt budgets per action.
     * Each entry is: max attempts, window length in seconds.
     */
    private static array $limits = [
        'login'          => ['max' => 5, 'window' => 300],
        'register'       => ['max' => 3, 'window' => 3600],
        'password_reset' => ['max' => 3, 'window' => 3600],
        'api'            => ['max' => 60, 'window' => 60],
    ];

    /**
     * Constructor
     *
     * @param PDO $pdo Database connection
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Check whether the caller is still allowed to attempt the action.
     * Does not record anything. Use consume() to check and record at once.
     *
     * @param string $key    Extra disambiguator (unused in identifier when
     *                       action is enough, but kept for signature parity
     *                       with older code)
     * @param string $action Action name (login, register, ...)
     * @return array{allowed: bool, remaining: int, retry_after: int|null}
     */
    public function check(string $key, string $action = 'login'): array
    {
        // Look up the budget for this action; fall back to a sane default.
        $config = self::$limits[$action] ?? ['max' => 10, 'window' => 60];
        $maxAttempts = $config['max'];
        $window      = $config['window'];

        // Compute the bucket identifier from IP + user agent + key.
        $bucket = $this->getIdentifier($key);

        // Fetch the current row for this (bucket, action) pair.
        $row = $this->fetchRow($bucket, $action);

        // If no row exists, the caller has made zero attempts.
        if ($row === null) {
            return [
                'allowed'     => true,
                'remaining'   => $maxAttempts,
                'retry_after' => null,
            ];
        }

        // If the stored window has already expired, treat it as a fresh start.
        $now = time();
        $windowStart = (int) $row['window_start'];
        if ($now - $windowStart >= $window) {
            return [
                'allowed'     => true,
                'remaining'   => $maxAttempts,
                'retry_after' => null,
            ];
        }

        // Otherwise count how many attempts are inside the current window.
        $used      = (int) $row['attempt_count'];
        $remaining = max(0, $maxAttempts - $used);
        $allowed   = $used < $maxAttempts;

        // If blocked, compute how many seconds remain until the window ends.
        $retryAfter = null;
        if (!$allowed) {
            $retryAfter = $window - ($now - $windowStart);
            if ($retryAfter < 0) {
                $retryAfter = 0;
            }
        }

        return [
            'allowed'     => $allowed,
            'remaining'   => $remaining,
            'retry_after' => $retryAfter,
        ];
    }

    /**
     * Record one attempt for the given action.
     * If the previous window has expired, starts a new window with count 1.
     * Otherwise increments the existing count in place.
     */
    public function record(string $key, string $action = 'login'): void
    {
        // Look up the window length for this action.
        $config = self::$limits[$action] ?? ['max' => 10, 'window' => 60];
        $window = $config['window'];

        // Compute the bucket identifier from IP + user agent + key.
        $bucket = $this->getIdentifier($key);

        // Fetch the current row, if any.
        $row = $this->fetchRow($bucket, $action);

        // No row yet: insert a new one with attempt_count = 1.
        if ($row === null) {
            $sql = "INSERT INTO rate_limits
                        (bucket, action, attempt_count, window_start, created_at, updated_at)
                    VALUES
                        (:bucket, :action, 1, :window_start, NOW(), NOW())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'bucket'       => $bucket,
                'action'       => $action,
                'window_start' => time(),
            ]);
            return;
        }

        // Row exists but its window has expired: reset to a fresh window.
        $now         = time();
        $windowStart = (int) $row['window_start'];
        if ($now - $windowStart >= $window) {
            $sql = "UPDATE rate_limits
                       SET attempt_count = 1,
                           window_start  = :window_start,
                           updated_at    = NOW()
                     WHERE bucket = :bucket
                       AND action = :action";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'window_start' => $now,
                'bucket'       => $bucket,
                'action'       => $action,
            ]);
            return;
        }

        // Row exists and window is still active: increment the counter.
        $sql = "UPDATE rate_limits
                   SET attempt_count = attempt_count + 1,
                       updated_at    = NOW()
                 WHERE bucket = :bucket
                   AND action = :action";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'bucket' => $bucket,
            'action' => $action,
        ]);
    }

    /**
     * Check and record in one call.
     * Throws RateLimitException when the caller has exceeded the budget.
     *
     * @throws RateLimitException
     */
    public function consume(string $key, string $action = 'login'): void
    {
        // Ask the DB whether the caller still has budget.
        $result = $this->check($key, $action);

        // If not allowed, log the event and throw with the wait time.
        if (!$result['allowed']) {
            $retryAfter = $result['retry_after'];
            if ($retryAfter === null) {
                $retryAfter = 60;
            }

            // Log the rate limit event for audit.
            if (class_exists(SecurityLogger::class)) {
                $logger = new SecurityLogger();
                $logger->logSuspicious('rate_limit_exceeded', "Rate limit exceeded for {$action}", [
                    'action'      => $action,
                    'identifier'  => $this->getIdentifier($key),
                    'retry_after' => $retryAfter,
                ]);
            }

            // Throw the exception; the controller will turn it into a redirect.
            throw new RateLimitException($action, (int) $retryAfter);
        }

        // Otherwise record the attempt and return.
        $this->record($key, $action);
    }

    /**
     * Clear the rate limit row for a given key + action.
     * Called after a successful login so the user is not punished for their
     * earlier typos.
     */
    public function clear(string $key, string $action = 'login'): void
    {
        // Compute the bucket identifier from IP + user agent + key.
        $bucket = $this->getIdentifier($key);

        // Delete the matching row from the DB.
        $sql = "DELETE FROM rate_limits
                 WHERE bucket = :bucket
                   AND action = :action";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'bucket' => $bucket,
            'action' => $action,
        ]);
    }

    /**
     * Format a retry-after value into a human-readable string.
     * Example: 300 -> "5 minutes", 45 -> "45 seconds", 7200 -> "2 hours".
     */
    public static function formatRetryAfter(int $seconds): string
    {
        // Below one minute: report seconds.
        if ($seconds < 60) {
            return $seconds . ' seconds';
        }

        // Below one hour: report whole minutes.
        if ($seconds < 3600) {
            $minutes = (int) ceil($seconds / 60);
            return $minutes . ' minutes';
        }

        // Otherwise: report whole hours.
        $hours = (int) ceil($seconds / 3600);
        return $hours . ' hours';
    }

    /**
     * Fetch one row from the rate_limits table for the given bucket + action.
     * Returns null if no matching row exists.
     */
    private function fetchRow(string $bucket, string $action): ?array
    {
        $sql = "SELECT attempt_count, window_start
                  FROM rate_limits
                 WHERE bucket = :bucket
                   AND action = :action
                 LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'bucket' => $bucket,
            'action' => $action,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }
        return $row;
    }

    /**
     * Build the identifier used as the `bucket` column.
     * Combines IP, user agent, and the caller-provided key, then hashes it.
     */
    private function getIdentifier(string $key): string
    {
        $ip = $this->getClientIp();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return hash('sha256', $ip . '|' . $ua . '|' . $key);
    }

    /**
     * Determine the caller's IP address, honouring common proxy headers.
     */
    private function getClientIp(): string
    {
        // Check the well-known proxy headers in order of reliability.
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                return trim($ips[0]);
            }
        }

        // Fall back to the direct remote address.
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
```

### app/Core/RateLimitException.php

```php
<?php
/**
 * RateLimitException
 * Thrown by RateLimiter::consume() when a caller has exceeded the
 * configured attempt budget for an action.
 */
namespace App\Core;

use Exception;

class RateLimitException extends Exception
{
    /** @var int Seconds the caller must wait before trying again */
    private int $retryAfter;

    /** @var string Action that was rate limited (login, register, ...) */
    private string $action;

    /**
     * Constructor
     *
     * @param string $action     The rate-limited action name
     * @param int    $retryAfter Seconds until the caller can retry
     */
    public function __construct(string $action, int $retryAfter)
    {
        // Store the details for the controller to read.
        $this->action     = $action;
        $this->retryAfter = $retryAfter;

        // Build a human-readable message for logs and debugging.
        $message = "Rate limit exceeded for {$action}. Retry after {$retryAfter} seconds.";

        // Call the parent Exception constructor.
        parent::__construct($message);
    }

    /**
     * Seconds the caller must wait.
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * The action name that was rate limited.
     */
    public function getAction(): string
    {
        return $this->action;
    }
}
```

### app/Core/Router.php

```php
<?php
/**
 * Router Class
 * Handles URL routing and dispatching to controllers
 */
namespace App\Core;

use PDO;

/**
 * Simple regex-based router with dependency injection
 */
class Router
{
    /** @var array Registered routes */
    protected array $routes = [];
    
    /** @var string Base path of the application */
    protected string $basePath;
    
    /** @var PDO Database connection */
    protected PDO $pdo;

    /**
     * Constructor
     * 
     * @param PDO $pdo Database connection for controller injection
     */
    public function __construct(PDO $pdo)
    {
        $this->basePath = dirname(__DIR__, 2);
        $this->pdo = $pdo;
    }

    /**
     * Register a route
     * 
     * @param string $route Route pattern (regex supported)
     * @param string $controller Controller class name (without namespace)
     * @param string $method Controller method name
     * @return void
     */
    public function addRoute(string $route, string $controller, string $method): void
    {
        $this->routes[$route] = [
            'controller' => $controller,
            'method' => $method
        ];
    }

    /**
     * Dispatch request to matching route
     * 
     * @param string $uri Request URI
     * @return void
     */
    public function dispatch(string $uri): void
    {
        $uri = $this->sanitizeUri($uri);

        foreach ($this->routes as $routePattern => $routeInfo) {
            $pattern = "~^" . $routePattern . "$~";
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);

                $controller = $routeInfo['controller'];
                $method = $routeInfo['method'];
                $controllerClass = "App\\Controllers\\{$controller}";

                if (class_exists($controllerClass)) {
                    $controllerInstance = new $controllerClass($this->pdo);
                    if (method_exists($controllerInstance, $method)) {
                        $controllerInstance->$method(...$matches);
                        return;
                    }
                }
            }
        }
        
        $this->show404();
    }

    /**
     * Sanitize URI by removing query string and trimming
     * 
     * @param string $uri Raw URI
     * @return string Clean URI
     */
    protected function sanitizeUri(string $uri): string
    {
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        return trim($uri, '/');
    }

    /**
     * Display 404 page
     * 
     * @return never
     */
    protected function show404(): void
    {
        http_response_code(404);
        $file = $this->basePath . '/views/errors/404.php';
        if (file_exists($file)) {
            $pageTitle = '404 Not Found';
            require $file;
        } else {
            echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body>
            <h1>404 - Page Not Found</h1><p>The requested URL was not found on this server.</p></body></html>';
        }
        exit();
    }
}
```

### app/Core/SecurityLogger.php

```php
<?php
/**
 * Security Logger
 * Logs security events for audit and monitoring
 */
namespace App\Core;

class SecurityLogger {
    private string $logDir;
    private string $logFile;
    private int $maxFileSize = 10485760; // 10MB

    public function __construct() {
        $baseDir = __DIR__ . '/../../storage/logs/security';
        $this->logDir = $baseDir;
        $this->logFile = $this->logDir . '/security.log';
        
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0750, true);
        }
        
        // Protect log directory
        $htaccessPath = $this->logDir . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Deny from all\n");
        }
    }

    public function log(string $level, string $event, array $details = []): void {
        $entry = [
            'timestamp' => date('c'),
            'level' => strtoupper($level),
            'event' => $event,
            'ip' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null,
            'session_id' => session_id(),
            'details' => $details,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
        ];

        $json = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        
        // Rotate log if too large
        if (file_exists($this->logFile) && filesize($this->logFile) > $this->maxFileSize) {
            $rotated = $this->logDir . '/security_' . date('Y-m-d_H-i-s') . '.log';
            rename($this->logFile, $rotated);
            @chmod($rotated, 0640);
        }
        
        file_put_contents($this->logFile, $json, FILE_APPEND | LOCK_EX);
        @chmod($this->logFile, 0640);
    }

    public function logSuspicious(string $event, string $message, array $details = []): void {
        $details['message'] = $message;
        $this->log('warning', $event, $details);
    }

    public function logInfo(string $event, array $details = []): void {
        $this->log('info', $event, $details);
    }

    public function logError(string $event, string $message, array $details = []): void {
        $details['message'] = $message;
        $this->log('error', $event, $details);
    }

    public function logAuth(string $event, bool $success, array $details = []): void {
        $details['success'] = $success;
        $this->log($success ? 'info' : 'warning', 'auth_' . $event, $details);
    }

    private function getClientIp(): string {
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                return trim($ips[0]);
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
```

### app/Core/Validator.php

```php
<?php
/**
 * Input Validator & Sanitizer
 * Provides validation and sanitization for user inputs
 */
namespace App\Core;

class Validator {
    /**
     * Sanitize string input
     */
    public static function sanitize(string $input, int $maxLength = 255, bool $allowHtml = false): string {
        $input = trim($input);
        
        if (!$allowHtml) {
            $input = strip_tags($input);
        }
        
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Limit length
        if (strlen($input) > $maxLength) {
            $input = substr($input, 0, $maxLength);
        }
        
        return $input;
    }

    /**
     * Sanitize for HTML output (prevents XSS)
     */
    public static function escape(string $input): string {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitize for SQL (use prepared statements instead, but useful for debugging)
     */
    public static function escapeSql(string $input): string {
        // This is for logging/debugging only - use prepared statements for queries
        return addslashes($input);
    }

    /**
     * Validate email
     */
    public static function email(string $email): bool {
        $email = trim($email);
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate phone number (E.164 format)
     */
    public static function phone(string $phone): bool {
        $phone = trim($phone);
        // E.164 format: + followed by 8-15 digits
        return preg_match('/^\+\d{8,15}$/', $phone) === 1;
    }

    /**
     * Validate URL
     */
    public static function url(string $url): bool {
        $url = trim($url);
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate integer
     * Accepts int or numeric string. Correctly allows 0.
     */
    public static function integer(mixed $value, ?int $min = null, ?int $max = null): bool {
        // Reject anything that is not a valid integer representation.
        // filter_var returns false for non-integers and int(0) for "0" or 0.
        // We must use strict === false so that 0 is treated as valid.
        $filtered = filter_var($value, FILTER_VALIDATE_INT);
        if ($filtered === false) {
            return false;
        }

        // Cast to int now that we know it is a valid integer value.
        $int = (int) $filtered;

        // Enforce optional minimum bound.
        if ($min !== null && $int < $min) {
            return false;
        }

        // Enforce optional maximum bound.
        if ($max !== null && $int > $max) {
            return false;
        }

        // Value passed all checks.
        return true;
    }

    /**
     * Validate string length
     */
    public static function length(string $value, int $min = 0, int $max = 255): bool {
        $len = strlen($value);
        return $len >= $min && $len <= $max;
    }

    /**
     * Validate required field
     */
    public static function required(mixed $value): bool {
        if (is_string($value)) {
            return trim($value) !== '';
        }
        return $value !== null && $value !== '';
    }

    /**
     * Validate against allowed values
     */
    public static function inArray(mixed $value, array $allowed): bool {
        return in_array($value, $allowed, true);
    }

    /**
     * Validate alphanumeric (with optional spaces, dashes, underscores)
     */
    public static function alphanumeric(string $value, bool $allowSpaces = true, bool $allowDash = true, bool $allowUnderscore = true): bool {
        $pattern = '/^[a-zA-Z0-9';
        if ($allowSpaces) $pattern .= '\s';
        if ($allowDash) $pattern .= '-';
        if ($allowUnderscore) $pattern .= '_';
        $pattern .= ']+$/';
        return preg_match($pattern, $value) === 1;
    }

    /**
     * Validate password strength
     */
    public static function passwordStrength(string $password): array {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        if (strlen($password) > 128) {
            $errors[] = 'Password must not exceed 128 characters';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        if (!preg_match('/\d/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'score' => self::calculatePasswordScore($password),
        ];
    }

    /**
     * Calculate password entropy score (0-4)
     */
    private static function calculatePasswordScore(string $password): int {
        $score = 0;
        $length = strlen($password);
        
        // Length scoring
        if ($length >= 12) $score++;
        if ($length >= 16) $score++;
        
        // Character variety
        $variety = 0;
        if (preg_match('/[a-z]/', $password)) $variety++;
        if (preg_match('/[A-Z]/', $password)) $variety++;
        if (preg_match('/\d/', $password)) $variety++;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $variety++;
        
        $score += min($variety, 2);
        
        return min($score, 4);
    }

    /**
     * Sanitize file name
     */
    public static function sanitizeFileName(string $filename): string {
        // Remove path components
        $filename = basename($filename);
        
        // Remove special characters except alphanumeric, dot, dash, underscore
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        // Prevent multiple dots
        $filename = preg_replace('/\.+/', '.', $filename);
        
        // Limit length
        if (strlen($filename) > 255) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 255 - strlen($ext) - 1) . '.' . $ext;
        }
        
        return $filename;
    }

    /**
     * Validate file upload
     */
    public static function validateFileUpload(array $file, array $allowedMimes = [], int $maxSize = 5242880): array {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error: ' . $file['error'];
            return ['valid' => false, 'errors' => $errors];
        }
        
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed (' . ($maxSize / 1024 / 1024) . 'MB)';
        }
        
        // Check MIME type
        if (!empty($allowedMimes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime, $allowedMimes, true)) {
                $errors[] = 'File type not allowed';
            }
        }
        
        // Check extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx'];
        if (!in_array($ext, $allowedExts, true)) {
            $errors[] = 'File extension not allowed';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'mime' => $mime ?? null,
            'extension' => $ext,
        ];
    }

    /**
     * Validate array of inputs against rules
     * Rules format: ['field' => ['rule' => value, ...]]
     */
    public static function validate(array $data, array $rules): array {
        $errors = [];
        $validated = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $required = $fieldRules['required'] ?? false;
            
            if ($required && !self::required($value)) {
                $errors[$field] = ucfirst($field) . ' is required';
                continue;
            }
            
            if (!self::required($value)) {
                $validated[$field] = $value;
                continue;
            }
            
            // Type-specific validation
            if (isset($fieldRules['type'])) {
                switch ($fieldRules['type']) {
                    case 'email':
                        if (!self::email($value)) {
                            $errors[$field] = 'Invalid email format';
                        }
                        break;
                    case 'phone':
                        if (!self::phone($value)) {
                            $errors[$field] = 'Invalid phone number format (use E.164: +628123456789)';
                        }
                        break;
                    case 'url':
                        if (!self::url($value)) {
                            $errors[$field] = 'Invalid URL format';
                        }
                        break;
                    case 'int':
                        $min = $fieldRules['min'] ?? null;
                        $max = $fieldRules['max'] ?? null;
                        if (!self::integer($value, $min, $max)) {
                            $errors[$field] = 'Must be a valid integer' . 
                                ($min !== null ? " >= $min" : '') . 
                                ($max !== null ? " <= $max" : '');
                        }
                        break;
                    case 'string':
                        $min = $fieldRules['min'] ?? 0;
                        $max = $fieldRules['max'] ?? 255;
                        if (!self::length($value, $min, $max)) {
                            $errors[$field] = "Must be between $min and $max characters";
                        }
                        break;
                }
            }
            
            // Custom validation callback
            if (isset($fieldRules['callback']) && is_callable($fieldRules['callback'])) {
                $result = $fieldRules['callback']($value);
                if ($result !== true) {
                    $errors[$field] = is_string($result) ? $result : 'Validation failed';
                }
            }
            
            // Allowed values
            if (isset($fieldRules['in'])) {
                if (!self::inArray($value, $fieldRules['in'])) {
                    $errors[$field] = 'Invalid value';
                }
            }
            
            // Sanitize
            $validated[$field] = self::sanitize($value, $fieldRules['max'] ?? 255, $fieldRules['allow_html'] ?? false);
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $validated,
        ];
    }
}
```

### app/Models/HomeModel.php

```php
<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class HomeModel extends Model
{
    protected string $table = 'home';
}
```

### app/Models/UserModel.php

```php
<?php
namespace App\Models;

use App\Core\Model;
use PDO;
use Exception;

class UserModel extends Model {
    protected string $table = 'users';

    private function passwordAlgorithm(): string {
        if (defined('PASSWORD_ARGON2ID')) {
            return PASSWORD_ARGON2ID;
        }
        return PASSWORD_DEFAULT;
    }
    
    /**
     * Normalize phone number to E.164 format (+628123456789)
     */
    private function normalizePhone(string $phone): string {
        $phone = trim($phone);
        
        // Remove all non-digit characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        // If starts with 0, replace with country code (assuming Indonesia +62)
        if (str_starts_with($phone, '0')) {
            $phone = '+62' . substr($phone, 1);
        }
        
        // If doesn't start with +, assume it's missing country code
        if (!str_starts_with($phone, '+')) {
            throw new Exception('Phone number must include country code (e.g., +628123456789)');
        }
        
        // Validate length (E.164 max 15 digits)
        $digitsOnly = substr($phone, 1);
        if (strlen($digitsOnly) < 8 || strlen($digitsOnly) > 15) {
            throw new Exception('Invalid phone number length');
        }
        
        return $phone;
    }

        public function register(string $phone, string $plainPassword, string $role = 'user'): int {
        // Validate password
        if (strlen($plainPassword) < 8) {
            throw new Exception('Password must be at least 8 characters');
        }

        // Validate and normalize phone
        try {
            $phone = $this->normalizePhone($phone);
        } catch (Exception $e) {
            throw new Exception('Invalid phone number: ' . $e->getMessage());
        }

        // Check duplicate
        if ($this->findByPhone($phone)) {
            throw new Exception('Phone number already registered');
        }

        // Whitelist role
        $allowedRoles = ['root', 'admin', 'user'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'user';
        }

        // Hash & store
        $hash = password_hash($plainPassword, $this->passwordAlgorithm());
        return $this->insert([
            'phone' => $phone,
            'password' => $hash,
            'role' => $role,
        ]);
    }

    public function login(string $phone, string $plainPassword): ?array {
        // Normalize phone for lookup
        try {
            $phone = $this->normalizePhone($phone);
        } catch (Exception $e) {
            return null;
        }
        
        $user = $this->queryOne("SELECT * FROM {$this->table} WHERE phone = ?", [$phone]);
        
        if ($user && password_verify($plainPassword, $user['password'])) {
            $passwordHash = $user['password'];
            unset($user['password']);
            
            // Rehash if the algorithm or cost has changed
            if (password_needs_rehash($passwordHash, $this->passwordAlgorithm())) {
                $newHash = password_hash($plainPassword, $this->passwordAlgorithm());
                $this->update($user['id'], ['password' => $newHash]);
            }
            
            // Update the last login timestamp
            $this->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);
            
            return $user;
        }
        return null;
    }

    public function findByPhone(string $phone): ?array {
        return $this->queryOne("SELECT * FROM {$this->table} WHERE phone = ?", [$phone]);
    }

    public function findById(int $id): ?array {
        return $this->queryOne("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    public function updateUsername(int $id, string $username): bool {
        $username = trim($username);

        if ($username === '') {
            return $this->update($id, ['username' => null]);
        }

        $valid = preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
        if (!$valid) {
            throw new Exception('Username must be 3-50 characters, using only letters, numbers, and underscores');
        }

        $existing = $this->queryOne("SELECT id FROM {$this->table} WHERE username = ? AND id != ?", [$username, $id]);
        if ($existing) {
            throw new Exception('Username already taken');
        }

        return $this->update($id, ['username' => $username]);
    }

    /**
     * Change a user's password after verifying the current one.
     * Enforces the same strength policy as registration so the two paths
     * cannot diverge.
     */
    public function changePassword(int $id, string $currentPassword, string $newPassword): bool {
        // Load the current row and verify the old password.
        $user = $this->findById($id);
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            throw new Exception('Current password is incorrect');
        }

        // Enforce the shared password strength policy.
        $pwCheck = \App\Core\Validator::passwordStrength($newPassword);
        if (!$pwCheck['valid']) {
            throw new Exception(implode('; ', $pwCheck['errors']));
        }

        // Hash with Argon2id and persist the new hash.
        $hash = password_hash($newPassword, $this->passwordAlgorithm());
        return $this->update($id, ['password' => $hash]);
    }

    public function updateAvatar(int $id, string $avatarPath): bool {
        return $this->update($id, ['avatar' => $avatarPath]);
    }

    // =====================================================================
    // New methods for Phase 3 (Multi-Auth)
    // =====================================================================

    /**
     * Update a user's role.
     * Only intended to be called by Root admins.
     */
    public function updateRole(int $id, string $role): bool {
        $allowedRoles = ['root', 'admin', 'user'];

        // Reject any role not in the whitelist.
        if (!in_array($role, $allowedRoles, true)) {
            throw new Exception('Invalid role');
        }

        return $this->update($id, ['role' => $role]);
    }

    /**
     * Mark a user as verified (useful for Phase 5 WhatsApp verification).
     */
    public function markVerified(int $id): bool {
        return $this->update($id, ['is_verified' => 1]);
    }

    /**
     * Count how many users have a specific role.
     * Useful for dashboard statistics.
     */
    public function countByRole(string $role): int {
        $sql = "SELECT COUNT(*) AS c FROM {$this->table} WHERE role = ?";
        $row = $this->queryOne($sql, [$role]);
        return (int) $row['c'];
    }

    /**
     * Return all users for the management list.
     */
    public function findAllUsers(string $orderBy = 'id ASC'): array {
        return $this->findAll($orderBy);
    }
}
```

### bin/add_root.php

```php
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
```

### bin/change_pwd.php

```php
<?php
// Usage: php change_pwd.php "your_new_password"

if ($argc < 2) {
    echo "Error: No password provided.\n";
    echo "Usage: php " . basename(__FILE__) . " \"new_password\"\n";
    exit(1);
}

$newPassword = $argv[1];

// Generate the Argon2id hash
$hash = password_hash($newPassword, PASSWORD_ARGON2ID);

if ($hash === false) {
    echo "Error: Failed to generate password hash.\n";
    exit(1);
}

echo "Generated Argon2id Hash:\n";
echo $hash . "\n\n";
echo "You can use this SQL query in your database:\n";
echo "UPDATE users SET password = '" . $hash . "' WHERE id = 1; // Adjust ID or email as needed\n";
```

### bin/dump_file.cpp

```cpp
#include <iostream>
#include <filesystem>
#include <fstream>
#include <vector>
#include <string>
#include <algorithm>
#include <unordered_set>

namespace fs = std::filesystem;

// Folders to skip entirely
const std::unordered_set<std::string> SKIP_DIRS = {
    ".git", "vendor", "node_modules", ".idea", ".vscode",
    "cache", "tmp", "logs", "__pycache__", ".pytest_cache",
    "dist", "build", ".next", ".nuxt", "storage"
};

// Files to skip by name
const std::unordered_set<std::string> SKIP_FILES = {
    ".DS_Store", "Thumbs.db", ".gitkeep", "dump_file.cpp", "dump_file.exe"
};

// Files to skip by extension
const std::unordered_set<std::string> SKIP_EXTS = {
    ".log", ".lock", ".sqlite", ".db", ".min.js", ".min.css",
    ".png", ".jpg", ".jpeg", ".gif", ".webp", ".ico", ".svg",
    ".woff", ".woff2", ".ttf", ".eot", ".pdf", ".zip", ".tar",
    ".gz", ".rar", ".7z", ".exe", ".dll", ".so", ".dylib",
    ".mp4", ".mp3", ".wav", ".webm"
};

// Files to skip for safety (secrets)
const std::unordered_set<std::string> SKIP_SECRETS = {
    ".env", ".env.local", ".env.production"
};

// Max file size to include (200 KB)
const std::size_t MAX_FILE_SIZE = 200 * 1024;

bool should_skip_dir(const std::string& name) {
    if (SKIP_DIRS.count(name)) return true;
    if (!name.empty() && name[0] == '.') return true;
    return false;
}

bool should_skip_file(const std::string& name) {
    if (SKIP_FILES.count(name) || SKIP_SECRETS.count(name)) return true;
    
    fs::path p(name);
    std::string ext = p.extension().string();
    std::transform(ext.begin(), ext.end(), ext.begin(), ::tolower);
    return SKIP_EXTS.count(ext);
}

std::string safe_read(const fs::path& path) {
    try {
        if (fs::file_size(path) > MAX_FILE_SIZE) {
            return "[skipped: file larger than 200 KB]";
        }
        std::ifstream file(path, std::ios::binary);
        if (!file) return "[could not read file]";
        std::string content((std::istreambuf_iterator<char>(file)), std::istreambuf_iterator<char>());
        return content;
    } catch (const std::exception& e) {
        return std::string("[could not read: ") + e.what() + "]";
    }
}

int main(int argc, char* argv[]) {
    std::string root_path = ".";
    std::string out_file = "project_dump.md";

    // Simple argument parsing
    for (int i = 1; i < argc; ++i) {
        std::string arg = argv[i];
        if (arg == "--root" && i + 1 < argc) {
            root_path = argv[++i];
        } else if (arg == "--out" && i + 1 < argc) {
            out_file = argv[++i];
        }
    }

    fs::path root;
    try {
        root = fs::absolute(root_path);
    } catch (...) {
        std::cerr << "Error: Invalid root path\n";
        return 1;
    }

    if (!fs::exists(root) || !fs::is_directory(root)) {
        std::cerr << "Error: " << root << " does not exist or is not a directory\n";
        return 1;
    }

    std::cout << "Scanning " << root << " ...\n";

    std::vector<std::pair<std::string, std::string>> files;

    try {
        for (auto it = fs::recursive_directory_iterator(root, fs::directory_options::skip_permission_denied);
             it != fs::recursive_directory_iterator(); ++it) {
            
            const auto& entry = *it;
            fs::path rel = fs::relative(entry.path(), root);
            
            // Check if any directory component should be skipped
            bool skip = false;
            for (const auto& part : rel) {
                std::string part_str = part.string();
                if (should_skip_dir(part_str)) {
                    skip = true;
                    if (entry.is_directory()) {
                        it.disable_recursion_pending();
                    }
                    break;
                }
            }

            if (skip) continue;

            if (entry.is_regular_file()) {
                std::string filename = entry.path().filename().string();
                if (should_skip_file(filename)) continue;

                std::string rel_str = rel.generic_string();
                std::string content = safe_read(entry.path());
                files.push_back({rel_str, content});
            }
        }
    } catch (const std::exception& e) {
        std::cerr << "Error during scan: " << e.what() << "\n";
    }

    // Sort files alphabetically by relative path
    std::sort(files.begin(), files.end(), [](const auto& a, const auto& b) {
        return a.first < b.first;
    });

    std::cout << "Found " << files.size() << " files\n";

    // Build Markdown content
    std::string md;
    md += "# Project dump: " + root.filename().string() + "\n\n";
    md += "Total files: " + std::to_string(files.size()) + "\n\n";
    md += "---\n\n## File tree\n\n```\n";
    for (const auto& f : files) {
        md += f.first + "\n";
    }
    md += "```\n\n---\n\n";

    for (const auto& [rel, content] : files) {
        md += "### " + rel + "\n\n";
        
        fs::path p(rel);
        std::string ext = p.extension().string();
        if (!ext.empty() && ext[0] == '.') ext = ext.substr(1);
        std::transform(ext.begin(), ext.end(), ext.begin(), ::tolower);
        
        std::string lang = ext;
        if (ext == "php") lang = "php";
        else if (ext == "js") lang = "javascript";
        else if (ext == "ts") lang = "typescript";
        else if (ext == "html") lang = "html";
        else if (ext == "css") lang = "css";
        else if (ext == "json") lang = "json";
        else if (ext == "md") lang = "markdown";
        else if (ext == "sql") lang = "sql";
        else if (ext == "cpp" || ext == "cc" || ext == "h") lang = "cpp";
        else if (ext == "sh") lang = "bash";
        else if (ext == "yml" || ext == "yaml") lang = "yaml";
        else if (ext == "xml") lang = "xml";
        else if (ext == "txt") lang = "text";
        else if (ext == "htaccess") lang = "apache";
        else if (ext == "env") lang = "bash";
        else if (ext == "gitignore") lang = "gitignore";
        else if (ext.empty()) lang = "text";

        md += "```" + lang + "\n";
        
        std::string trimmed_content = content;
        while (!trimmed_content.empty() && (trimmed_content.back() == '\n' || trimmed_content.back() == '\r')) {
            trimmed_content.pop_back();
        }
        md += trimmed_content + "\n```\n\n";
    }

    fs::path out_path = root / out_file;
    std::ofstream out(out_path, std::ios::binary);
    if (!out) {
        std::cerr << "Error: Could not write to " << out_path << "\n";
        return 1;
    }
    out.write(md.data(), md.size());

    double size_kb = static_cast<double>(fs::file_size(out_path)) / 1024.0;
    std::cout << "Written: " << out_path << " (" << size_kb << " KB)\n";

    return 0;
}
```

### bin/dump_file.php

```php
<?php
/**
 * dump_file.php — Walks a project folder and writes every file's path + content
 * into a single Markdown file, suitable for pasting into a chat.
 *
 * Usage:
 *     php dump_file.php
 *     php dump_file.php --root="C:\\xampp\\htdocs\\flow" --out="project_dump.md"
 */

// Parse command-line arguments manually
$options = getopt('', ['root:', 'out:']);
$rootPath = $options['root'] ?? '.';
$outFile = $options['out'] ?? 'project_dump.md';

// Resolve root path
$root = realpath($rootPath);
if ($root === false || !is_dir($root)) {
    fwrite(STDERR, "Error: {$rootPath} does not exist or is not a directory\n");
    exit(1);
}

// Folders to skip entirely
$skipDirs = [
    '.git', 'vendor', 'node_modules', '.idea', '.vscode',
    'cache', 'tmp', 'logs', '__pycache__', '.pytest_cache',
    'dist', 'build', '.next', '.nuxt',
    'storage',
];

// Files to skip by name
$skipFiles = [
    '.DS_Store', 'Thumbs.db', '.gitkeep',
    'dump_file.php', 'dump_file.py',
];

// Files to skip by extension
$skipExts = [
    'log', 'lock', 'sqlite', 'db', 'min.js', 'min.css',
    'png', 'jpg', 'jpeg', 'gif', 'webp', 'ico', 'svg',
    'woff', 'woff2', 'ttf', 'eot', 'pdf', 'zip', 'tar',
    'gz', 'rar', '7z', 'exe', 'dll', 'so', 'dylib',
    'mp4', 'mp3', 'wav', 'webm',
];

// Files to skip for safety (secrets)
$skipSecretFiles = ['.env', '.env.local', '.env.production'];

// Max file size to include (200 KB)
$maxFileSize = 200 * 1024;

// Helper function to check if directory should be skipped
$shouldSkipDir = function(string $name) use ($skipDirs): bool {
    if (in_array($name, $skipDirs, true)) {
        return true;
    }
    // Skip hidden folders starting with a dot (except if it's the root itself)
    if (str_starts_with($name, '.')) {
        return true;
    }
    return false;
};

// Helper function to check if file should be skipped
$shouldSkipFile = function(string $name) use ($skipFiles, $skipSecretFiles, $skipExts): bool {
    if (in_array($name, $skipFiles, true) || in_array($name, $skipSecretFiles, true)) {
        return true;
    }
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return in_array($ext, $skipExts, true);
};

// Helper function to safely read file content
$safeRead = function(SplFileInfo $file) use ($maxFileSize): string {
    if ($file->getSize() > $maxFileSize) {
        return "[skipped: file larger than " . ($maxFileSize // 1024) . " KB]";
    }
    try {
        $content = file_get_contents($file->getPathname());
        if ($content === false) {
            return "[could not read file]";
        }
        // Normalize line endings and handle encoding issues gracefully
        return mb_convert_encoding($content, 'UTF-8', 'UTF-8');
    } catch (\Throwable $e) {
        return "[could not read: " . $e->getMessage() . "]";
    }
};

echo "Scanning {$root} ...\n";

$files = [];

// Recursive directory iterator
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $path) {
    /** @var SplFileInfo $path */
    $subPathName = $iterator->getSubPathName();
    // Normalize path separators for cross-platform consistency
    $rel = str_replace('\\', '/', $subPathName);
    $name = $path->getFilename();

    if ($path->isDir()) {
        if ($shouldSkipDir($name)) {
            $iterator->next(); // or let the iterator handle via filter, but manual check works fine
        }
        continue;
    }

    if ($path->isFile()) {
        // Check if any parent directory in the path should be skipped
        $parts = explode('/', $rel);
        $skip = false;
        foreach ($parts as $part) {
            if ($shouldSkipDir($part)) {
                $skip = true;
                break;
            }
        }

        if ($skip || $shouldSkipFile($name)) {
            continue;
        }

        $content = $safeRead($path);
        $files[] = [$rel, $content];
    }
}

// Sort files alphabetically by relative path
usort($files, fn($a, $b) => strcasecmp($a[0], $b[0]));

echo "Found " . count($files) . " files\n";

// Build Markdown content
$lines = [];
$lines[] = "# Project dump: " . basename($root) . "\n";
$lines[] = "Total files: " . count($files) . "\n";
$lines[] = "---\n";

$lines[] = "## File tree\n";
$lines[] = "```";
foreach ($files as [$rel,]) {
    $lines[] =$rel;
}
$lines[] = "```\n";
$lines[] = "---\n";

$langMap = [
    'php' => 'php', 'js' => 'javascript', 'ts' => 'typescript',
    'html' => 'html', 'css' => 'css', 'json' => 'json',
    'md' => 'markdown', 'sql' => 'sql', 'sh' => 'bash',
    'yml' => 'yaml', 'yaml' => 'yaml', 'xml' => 'xml',
    'txt' => 'text', 'htaccess' => 'apache',
    'env' => 'bash', 'gitignore' => 'gitignore',
];

foreach ($files as [$rel, $content]) {
    $lines[] = "### {$rel}\n";
    $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
    $lang = $langMap[$ext] ?? ($ext !== '' ? $ext : 'text');
    
    $lines[] = "```{$lang}";
    $lines[] = rtrim($content, "\r\n");
    $lines[] = "```\n";
}

$markdown = implode("\n", $lines);
$outPath = $root . DIRECTORY_SEPARATOR . $outFile;

file_put_contents($outPath, $markdown);
$sizeKb = filesize($outPath) / 1024;

echo "Written: {$outPath} (" . number_format($sizeKb, 1) . " KB)\n";
```

### config/database.php

```php
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
```

### config/security.php

```php
<?php
/**
 * Security Configuration
 * 
 * This file contains security settings that can be adjusted based on environment.
 * For production, ensure all security features are enabled.
 */

// Detect environment
$httpHost = '';
if (isset($_SERVER['HTTP_HOST'])) {
    $httpHost = $_SERVER['HTTP_HOST'];
}
$isLocalhost = in_array($httpHost, [
    'localhost',
    '127.0.0.1',
    'onebuild.test',
    'www.onebuild.test',
    'gudangpc.test',
    'www.gudangpc.test',
    'localhost:8080',
    '127.0.0.1:8080',
]);

$isHTTPS = false;
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    $isHTTPS = true;
}
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $isHTTPS = true;
}

$config = [
    'cookie_secure' => !$isLocalhost && $isHTTPS,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'session_timeout' => 3600,
    'session_regenerate_interval' => 1800,
    'use_strict_mode' => true,
    'force_https' => !$isLocalhost,
    'hsts_enabled' => !$isLocalhost && $isHTTPS,
    'csp_enabled' => true,
    'csp_report_only' => $isLocalhost,
    'is_localhost' => $isLocalhost,
    'is_https' => $isHTTPS,
    'environment' => $isLocalhost ? 'development' : 'production'
];
return $config;
```

### database/initial_database.sql

```sql
CREATE TABLE `rate_limits` (
  `id` int(10) UNSIGNED NOT NULL,
  `bucket` varchar(64) NOT NULL,
  `action` varchar(64) NOT NULL,
  `attempt_count` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `window_start` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(32) NOT NULL DEFAULT 'buyer',
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `last_login_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_bucket_action` (`bucket`,`action`),
  ADD KEY `idx_window` (`window_start`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

ALTER TABLE `rate_limits`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
```

### public/assets/css/bootstrap-icons.min.css

```css
[skipped: file larger than 200 KB]
```

### public/assets/css/bootstrap.min.css

```css
[skipped: file larger than 200 KB]
```

### public/assets/css/dashboard.css

```css
/* Dashboard Sidebar Styles */
.sidebar {
    min-height: calc(100vh - 56px);
    border-right: 1px solid #dee2e6;
    background-color: #f8f9fa;
    transition: all 0.3s ease;
    z-index: 100;
}

.sidebar .nav-link {
    color: #333;
    padding: 0.75rem 1rem;
    border-radius: 0.375rem;
    margin: 0.25rem 0.5rem;
    transition: all 0.2s;
    white-space: nowrap;
}

.sidebar .nav-link:hover,
.sidebar .nav-link.active {
    color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.1);
}

.sidebar .nav-link i {
    width: 1.25rem;
    text-align: center;
    margin-right: 0.5rem;
}
.navbar-toggler-icon-2 {
    display: inline-block;
    width: 1.5em;
    height: 1.5em;
    vertical-align: middle;
    background-image: var(--bs-navbar-toggler-icon-bg);
    background-repeat: no-repeat;
    background-position: center;
    background-size: 100%;
}

/* Mobile Sidebar Styles */
@media (max-width: 767.98px) {
    .sidebar {
        position: fixed;
        top: 56px;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1030;
        min-height: calc(100vh - 56px);
        border-right: none;
        border-bottom: 1px solid #dee2e6;
        transform: translateX(-100%);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    
    .sidebar.show {
        transform: translateX(0);
    }
    
    .sidebar.collapse:not(.show) {
        display: block;
    }
    
    .sidebar-overlay {
        position: fixed;
        top: 56px;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1020;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    
    .sidebar-overlay.show {
        opacity: 1;
        visibility: visible;
    }
}

/* Desktop: always show sidebar */
@media (min-width: 768px) {
    .sidebar {
        position: relative;
        transform: none !important;
        display: block !important;
    }
    
    .sidebar-toggle {
        display: none !important;
    }
    
    .sidebar-overlay {
        display: none !important;
    }
}

/* Toggle button */
.sidebar-toggle {
    background: none;
    border: none;
    color: #fff !important;
    padding: 0.5rem;
    font-size: 1.5rem;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 44px;
    min-height: 44px;
}

.sidebar-toggle:hover {
    color: rgba(255, 255, 255, 0.8) !important;
}

.sidebar-toggle i {
    font-size: 1.5rem;
    font-weight: normal;
}

/* Main content adjustment */
main {
    min-height: calc(100vh - 56px);
}

/* Card styles */
.card {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
    border-radius: 0.5rem 0.5rem 0 0 !important;
    font-weight: 600;
}

.card-body {
    padding: 1.5rem;
}

/* Stats cards */
.stat-card {
    border-left: 0.25rem solid;
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-card .stat-icon {
    font-size: 2.5rem;
    opacity: 0.8;
}

/* Table styles */
.table-responsive {
    border-radius: 0.5rem;
}

.table th {
    font-weight: 600;
    background-color: #f8f9fc;
    border-top: none;
}

.table-hover tbody tr:hover {
    background-color: #f8f9fc;
}

/* Badge styles */
.badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
}

/* Quick action buttons */
.quick-action-btn {
    transition: all 0.2s;
    border-radius: 0.5rem;
}

.quick-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.quick-action-btn i {
    transition: transform 0.2s;
}

.quick-action-btn:hover i {
    transform: scale(1.1);
}

/* Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeIn 0.3s ease-out;
}

/* Scrollbar styling */
.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.sidebar::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

/* Table column width utilities */
.col-width-120 {
    width: 120px;
}
```

### public/assets/css/summernote-bs5.min.css

```css
@font-face{font-display:auto;font-family:summernote;font-style:normal;font-weight:400;src:url(font/summernote.eot?#iefix) format("embedded-opentype"),url(font/summernote.woff2) format("woff2"),url(font/summernote.woff) format("woff"),url(font/summernote.ttf) format("truetype")}[class*=" note-icon"]:before,[class^=note-icon]:before{display:inline-block;font-family:summernote;font-size:inherit;font-style:normal;text-decoration:inherit;text-rendering:auto;text-transform:none;vertical-align:middle;-moz-osx-font-smoothing:grayscale;-webkit-font-smoothing:antialiased;speak:none}.note-icon-fw{text-align:center;width:1.25em}.note-icon-border{border:.08em solid #eee;border-radius:.1em;padding:.2em .25em .15em}.note-icon-pull-left{float:left}.note-icon-pull-right{float:right}.note-icon.note-icon-pull-left{margin-right:.3em}.note-icon.note-icon-pull-right{margin-left:.3em}.note-icon-align:before{content:"\ea01"}.note-icon-align-center:before{content:"\ea02"}.note-icon-align-indent:before{content:"\ea03"}.note-icon-align-justify:before{content:"\ea04"}.note-icon-align-left:before{content:"\ea05"}.note-icon-align-outdent:before{content:"\ea06"}.note-icon-align-right:before{content:"\ea07"}.note-icon-arrow-circle-down:before{content:"\ea08"}.note-icon-arrow-circle-left:before{content:"\ea09"}.note-icon-arrow-circle-right:before{content:"\ea0a"}.note-icon-arrow-circle-up:before{content:"\ea0b"}.note-icon-arrows-alt:before{content:"\ea0c"}.note-icon-arrows-h:before{content:"\ea0d"}.note-icon-arrows-v:before{content:"\ea0e"}.note-icon-bold:before{content:"\ea0f"}.note-icon-caret:before{content:"\ea10"}.note-icon-chain-broken:before{content:"\ea11"}.note-icon-circle:before{content:"\ea12"}.note-icon-close:before{content:"\ea13"}.note-icon-code:before{content:"\ea14"}.note-icon-col-after:before{content:"\ea15"}.note-icon-col-before:before{content:"\ea16"}.note-icon-col-remove:before{content:"\ea17"}.note-icon-eraser:before{content:"\ea18"}.note-icon-float-left:before{content:"\ea19"}.note-icon-float-none:before{content:"\ea1a"}.note-icon-float-right:before{content:"\ea1b"}.note-icon-font:before{content:"\ea1c"}.note-icon-frame:before{content:"\ea1d"}.note-icon-italic:before{content:"\ea1e"}.note-icon-link:before{content:"\ea1f"}.note-icon-magic:before{content:"\ea20"}.note-icon-menu-check:before{content:"\ea21"}.note-icon-minus:before{content:"\ea22"}.note-icon-orderedlist:before{content:"\ea23"}.note-icon-pencil:before{content:"\ea24"}.note-icon-picture:before{content:"\ea25"}.note-icon-question:before{content:"\ea26"}.note-icon-redo:before{content:"\ea27"}.note-icon-rollback:before{content:"\ea28"}.note-icon-row-above:before{content:"\ea29"}.note-icon-row-below:before{content:"\ea2a"}.note-icon-row-remove:before{content:"\ea2b"}.note-icon-special-character:before{content:"\ea2c"}.note-icon-square:before{content:"\ea2d"}.note-icon-strikethrough:before{content:"\ea2e"}.note-icon-subscript:before{content:"\ea2f"}.note-icon-summernote:before{content:"\ea30"}.note-icon-superscript:before{content:"\ea31"}.note-icon-table:before{content:"\ea32"}.note-icon-text-height:before{content:"\ea33"}.note-icon-trash:before{content:"\ea34"}.note-icon-underline:before{content:"\ea35"}.note-icon-undo:before{content:"\ea36"}.note-icon-unorderedlist:before{content:"\ea37"}.note-icon-video:before{content:"\ea38"}.note-editor{position:relative}.note-editor .note-dropzone{background-color:#fff;color:#87cefa;display:none;opacity:.95;position:absolute;z-index:100}.note-editor .note-dropzone .note-dropzone-message{display:table-cell;font-size:28px;font-weight:700;text-align:center;vertical-align:middle}.note-editor .note-dropzone.hover{color:#098ddf}.note-editor.dragover .note-dropzone{display:table}.note-editor .note-editing-area{position:relative}.note-editor .note-editing-area .note-editable{outline:none}.note-editor .note-editing-area .note-editable sup{vertical-align:super}.note-editor .note-editing-area .note-editable sub{vertical-align:sub}.note-editor .note-editing-area .note-editable img.note-float-left{margin-right:10px}.note-editor .note-editing-area .note-editable img.note-float-right{margin-left:10px}.note-editor.note-airframe,.note-editor.note-frame{border:1px solid rgba(0,0,0,.196)}.note-editor.note-airframe.codeview .note-editing-area .note-editable,.note-editor.note-frame.codeview .note-editing-area .note-editable{display:none}.note-editor.note-airframe.codeview .note-editing-area .note-codable,.note-editor.note-frame.codeview .note-editing-area .note-codable{display:block}.note-editor.note-airframe .note-editing-area,.note-editor.note-frame .note-editing-area{overflow:hidden}.note-editor.note-airframe .note-editing-area .note-editable,.note-editor.note-frame .note-editing-area .note-editable{overflow:auto;padding:10px;word-wrap:break-word}.note-editor.note-airframe .note-editing-area .note-editable[contenteditable=false],.note-editor.note-frame .note-editing-area .note-editable[contenteditable=false]{background-color:hsla(0,0%,50%,.114)}.note-editor.note-airframe .note-editing-area .note-codable,.note-editor.note-frame .note-editing-area .note-codable{background-color:#222;border:none;border-radius:0;box-shadow:none;-ms-box-sizing:border-box;box-sizing:border-box;color:#ccc;display:none;font-family:Menlo,Monaco,monospace,sans-serif;font-size:14px;margin-bottom:0;outline:none;padding:10px;resize:none;width:100%}.note-editor.note-airframe.fullscreen,.note-editor.note-frame.fullscreen{left:0;position:fixed;top:0;width:100%!important;z-index:1050}.note-editor.note-airframe.fullscreen .note-resizebar,.note-editor.note-frame.fullscreen .note-resizebar{display:none}.note-editor.note-airframe .note-status-output,.note-editor.note-frame .note-status-output{border:0;border-top:1px solid #e2e2e2;color:#000;display:block;font-size:14px;height:20px;line-height:1.42857143;margin-bottom:0;width:100%}.note-editor.note-airframe .note-status-output:empty,.note-editor.note-frame .note-status-output:empty{border-top:0 solid transparent;height:0}.note-editor.note-airframe .note-status-output .pull-right,.note-editor.note-frame .note-status-output .pull-right{float:right!important}.note-editor.note-airframe .note-status-output .text-muted,.note-editor.note-frame .note-status-output .text-muted{color:#777}.note-editor.note-airframe .note-status-output .text-primary,.note-editor.note-frame .note-status-output .text-primary{color:#286090}.note-editor.note-airframe .note-status-output .text-success,.note-editor.note-frame .note-status-output .text-success{color:#3c763d}.note-editor.note-airframe .note-status-output .text-info,.note-editor.note-frame .note-status-output .text-info{color:#31708f}.note-editor.note-airframe .note-status-output .text-warning,.note-editor.note-frame .note-status-output .text-warning{color:#8a6d3b}.note-editor.note-airframe .note-status-output .text-danger,.note-editor.note-frame .note-status-output .text-danger{color:#a94442}.note-editor.note-airframe .note-status-output .alert,.note-editor.note-frame .note-status-output .alert{background-color:#f5f5f5;border-radius:0;color:#000;margin:-7px 0 0;padding:7px 10px 2px}.note-editor.note-airframe .note-status-output .alert .note-icon,.note-editor.note-frame .note-status-output .alert .note-icon{margin-right:5px}.note-editor.note-airframe .note-status-output .alert-success,.note-editor.note-frame .note-status-output .alert-success{background-color:#dff0d8!important;color:#3c763d!important}.note-editor.note-airframe .note-status-output .alert-info,.note-editor.note-frame .note-status-output .alert-info{background-color:#d9edf7!important;color:#31708f!important}.note-editor.note-airframe .note-status-output .alert-warning,.note-editor.note-frame .note-status-output .alert-warning{background-color:#fcf8e3!important;color:#8a6d3b!important}.note-editor.note-airframe .note-status-output .alert-danger,.note-editor.note-frame .note-status-output .alert-danger{background-color:#f2dede!important;color:#a94442!important}.note-editor.note-airframe .note-statusbar,.note-editor.note-frame .note-statusbar{background-color:hsla(0,0%,50%,.114);border-bottom-left-radius:4px;border-bottom-right-radius:4px;border-top:1px solid rgba(0,0,0,.196)}.note-editor.note-airframe .note-statusbar .note-resizebar,.note-editor.note-frame .note-statusbar .note-resizebar{cursor:ns-resize;height:9px;padding-top:1px;width:100%}.note-editor.note-airframe .note-statusbar .note-resizebar .note-icon-bar,.note-editor.note-frame .note-statusbar .note-resizebar .note-icon-bar{border-top:1px solid rgba(0,0,0,.196);margin:1px auto;width:20px}.note-editor.note-airframe .note-statusbar.locked .note-resizebar,.note-editor.note-frame .note-statusbar.locked .note-resizebar{cursor:default}.note-editor.note-airframe .note-statusbar.locked .note-resizebar .note-icon-bar,.note-editor.note-frame .note-statusbar.locked .note-resizebar .note-icon-bar{display:none}.note-editor.note-airframe .note-placeholder,.note-editor.note-frame .note-placeholder{padding:10px}.note-editor.note-airframe{border:0}.note-editor.note-airframe .note-editing-area .note-editable{padding:0}.note-popover.popover{display:none;max-width:none}.note-popover.popover .popover-content a{display:inline-block;max-width:200px;overflow:hidden;text-overflow:ellipsis;vertical-align:middle;white-space:nowrap}.note-popover.popover .arrow{left:20px!important}.note-toolbar{position:relative}.note-editor .note-toolbar,.note-popover .popover-content{margin:0;padding:0 0 5px 5px}.note-editor .note-toolbar>.note-btn-group,.note-popover .popover-content>.note-btn-group{margin-left:0;margin-right:5px;margin-top:5px}.note-editor .note-toolbar .note-btn-group .note-table,.note-popover .popover-content .note-btn-group .note-table{min-width:0;padding:5px}.note-editor .note-toolbar .note-btn-group .note-table .note-dimension-picker,.note-popover .popover-content .note-btn-group .note-table .note-dimension-picker{font-size:18px}.note-editor .note-toolbar .note-btn-group .note-table .note-dimension-picker .note-dimension-picker-mousecatcher,.note-popover .popover-content .note-btn-group .note-table .note-dimension-picker .note-dimension-picker-mousecatcher{cursor:pointer;height:10em;position:absolute!important;width:10em;z-index:3}.note-editor .note-toolbar .note-btn-group .note-table .note-dimension-picker .note-dimension-picker-unhighlighted,.note-popover .popover-content .note-btn-group .note-table .note-dimension-picker .note-dimension-picker-unhighlighted{background:url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASAgMAAAAroGbEAAAACVBMVEUAAIj4+Pjp6ekKlAqjAAAAAXRSTlMAQObYZgAAAAFiS0dEAIgFHUgAAAAJcEhZcwAACxMAAAsTAQCanBgAAAAHdElNRQfYAR0BKhmnaJzPAAAAG0lEQVQI12NgAAOtVatWMTCohoaGUY+EmIkEAEruEzK2J7tvAAAAAElFTkSuQmCC") repeat;height:5em;position:relative!important;width:5em;z-index:1}.note-editor .note-toolbar .note-btn-group .note-table .note-dimension-picker .note-dimension-picker-highlighted,.note-popover .popover-content .note-btn-group .note-table .note-dimension-picker .note-dimension-picker-highlighted{background:url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASAgMAAAAroGbEAAAACVBMVEUAAIjd6vvD2f9LKLW+AAAAAXRSTlMAQObYZgAAAAFiS0dEAIgFHUgAAAAJcEhZcwAACxMAAAsTAQCanBgAAAAHdElNRQfYAR0BKwNDEVT0AAAAG0lEQVQI12NgAAOtVatWMTCohoaGUY+EmIkEAEruEzK2J7tvAAAAAElFTkSuQmCC") repeat;height:1em;position:absolute!important;width:1em;z-index:2}.note-editor .note-toolbar .note-style .dropdown-style blockquote,.note-editor .note-toolbar .note-style .dropdown-style pre,.note-popover .popover-content .note-style .dropdown-style blockquote,.note-popover .popover-content .note-style .dropdown-style pre{margin:0;padding:5px 10px}.note-editor .note-toolbar .note-style .dropdown-style h1,.note-editor .note-toolbar .note-style .dropdown-style h2,.note-editor .note-toolbar .note-style .dropdown-style h3,.note-editor .note-toolbar .note-style .dropdown-style h4,.note-editor .note-toolbar .note-style .dropdown-style h5,.note-editor .note-toolbar .note-style .dropdown-style h6,.note-editor .note-toolbar .note-style .dropdown-style p,.note-popover .popover-content .note-style .dropdown-style h1,.note-popover .popover-content .note-style .dropdown-style h2,.note-popover .popover-content .note-style .dropdown-style h3,.note-popover .popover-content .note-style .dropdown-style h4,.note-popover .popover-content .note-style .dropdown-style h5,.note-popover .popover-content .note-style .dropdown-style h6,.note-popover .popover-content .note-style .dropdown-style p{margin:0;padding:0}.note-editor .note-toolbar .note-color-all .note-dropdown-menu,.note-popover .popover-content .note-color-all .note-dropdown-menu{min-width:337px}.note-editor .note-toolbar .note-color .dropdown-toggle,.note-popover .popover-content .note-color .dropdown-toggle{padding-left:5px;width:20px}.note-editor .note-toolbar .note-color .note-dropdown-menu .note-palette,.note-popover .popover-content .note-color .note-dropdown-menu .note-palette{display:inline-block;margin:0;width:160px}.note-editor .note-toolbar .note-color .note-dropdown-menu .note-palette:first-child,.note-popover .popover-content .note-color .note-dropdown-menu .note-palette:first-child{margin:0 5px}.note-editor .note-toolbar .note-color .note-dropdown-menu .note-palette .note-palette-title,.note-popover .popover-content .note-color .note-dropdown-menu .note-palette .note-palette-title{border-bottom:1px solid #eee;font-size:12px;margin:2px 7px;text-align:center}.note-editor .note-toolbar .note-color .note-dropdown-menu .note-palette .note-color-reset,.note-editor .note-toolbar .note-color .note-dropdown-menu .note-palette .note-color-select,.note-popover .popover-content .note-color .note-dropdown-menu .note-palette .note-color-reset,.note-popover .popover-content .note-color .note-dropdown-menu .note-palette .note-color-select{border-radius:5px;cursor:pointer;font-size:11px;margin:3px;padding:0 3px;width:100%}.note-editor .note-toolbar .note-color .note-dropdown-menu .note-palette .note-color-reset:hover,.note-editor .note-toolbar .note-color .note-dropdown-menu .note-palette .note-color-select:hover,.note-popover .popover-content .note-color .note-dropdown-menu .note-palette .note-color-reset:hover,.note-popover .popover-content .note-color .note-dropdown-menu .note-palette .note-color-select:hover{background:#eee}.note-editor .note-toolbar .note-color .note-dropdown-menu .note-palette .note-color-row,.note-popover .popover-content .note-color .note-dropdown-menu .note-palette .note-color-row{height:20px}.note-editor .note-toolbar .note-color .note-dropdown-menu .note-palette .note-color-select-btn,.note-popover .popover-content .note-color .note-dropdown-menu .note-palette .note-color-select-btn{display:none}.note-editor .note-toolbar .note-color .note-dropdown-menu .note-palette .note-holder-custom .note-color-btn,.note-popover .popover-content .note-color .note-dropdown-menu .note-palette .note-holder-custom .note-color-btn{border:1px solid #eee}.note-editor .note-toolbar .note-para .note-dropdown-menu,.note-popover .popover-content .note-para .note-dropdown-menu{min-width:228px;padding:5px}.note-editor .note-toolbar .note-para .note-dropdown-menu>div+div,.note-popover .popover-content .note-para .note-dropdown-menu>div+div{margin-left:5px}.note-editor .note-toolbar .note-dropdown-menu,.note-popover .popover-content .note-dropdown-menu{min-width:160px}.note-editor .note-toolbar .note-dropdown-menu.right,.note-popover .popover-content .note-dropdown-menu.right{left:auto;right:0}.note-editor .note-toolbar .note-dropdown-menu.right:before,.note-popover .popover-content .note-dropdown-menu.right:before{left:auto!important;right:9px}.note-editor .note-toolbar .note-dropdown-menu.right:after,.note-popover .popover-content .note-dropdown-menu.right:after{left:auto!important;right:10px}.note-editor .note-toolbar .note-dropdown-menu.note-check a i,.note-popover .popover-content .note-dropdown-menu.note-check a i{color:#00bfff;visibility:hidden}.note-editor .note-toolbar .note-dropdown-menu.note-check a.checked i,.note-popover .popover-content .note-dropdown-menu.note-check a.checked i{visibility:visible}.note-editor .note-toolbar .note-fontsize-10,.note-popover .popover-content .note-fontsize-10{font-size:10px}.note-editor .note-toolbar .note-color-palette,.note-popover .popover-content .note-color-palette{line-height:1}.note-editor .note-toolbar .note-color-palette div .note-color-btn,.note-popover .popover-content .note-color-palette div .note-color-btn{border:0;border-radius:0;height:20px;margin:0;padding:0;width:20px}.note-editor .note-toolbar .note-color-palette div .note-color-btn:hover,.note-popover .popover-content .note-color-palette div .note-color-btn:hover{transform:scale(1.2);transition:all .2s}.note-modal .modal-dialog{border-radius:5px;box-shadow:0 3px 9px rgba(0,0,0,.5);outline:0}.note-modal .form-group{margin-left:0;margin-right:0}.note-modal .note-modal-form{margin:0}.note-modal .note-image-dialog .note-dropzone{border:4px dashed #d3d3d3;color:#d3d3d3;font-size:30px;line-height:4;margin-bottom:10px;min-height:100px;text-align:center}@-moz-document url-prefix(){.note-modal .note-image-input{height:auto}}.note-placeholder{color:gray;display:none;position:absolute}.note-handle .note-control-selection{border:1px solid #000;display:none;position:absolute}.note-handle .note-control-selection>div{position:absolute}.note-handle .note-control-selection .note-control-selection-bg{background-color:#000;-ms-filter:progid:DXImageTransform.Microsoft.Alpha(opacity=30);filter:alpha(opacity=30);height:100%;-webkit-opacity:.3;-khtml-opacity:.3;-moz-opacity:.3;opacity:.3;width:100%}.note-handle .note-control-selection .note-control-handle,.note-handle .note-control-selection .note-control-holder,.note-handle .note-control-selection .note-control-sizing{border:1px solid #000;height:7px;width:7px}.note-handle .note-control-selection .note-control-sizing{background-color:#000}.note-handle .note-control-selection .note-control-nw{border-bottom:none;border-right:none;left:-5px;top:-5px}.note-handle .note-control-selection .note-control-ne{border-bottom:none;border-left:none;right:-5px;top:-5px}.note-handle .note-control-selection .note-control-sw{border-right:none;border-top:none;bottom:-5px;left:-5px}.note-handle .note-control-selection .note-control-se{bottom:-5px;cursor:se-resize;right:-5px}.note-handle .note-control-selection .note-control-se.note-control-holder{border-left:none;border-top:none;cursor:default}.note-handle .note-control-selection .note-control-selection-info{background-color:#000;border-radius:5px;bottom:0;color:#fff;-ms-filter:progid:DXImageTransform.Microsoft.Alpha(opacity=70);filter:alpha(opacity=70);font-size:12px;margin:5px;-webkit-opacity:.7;-khtml-opacity:.7;-moz-opacity:.7;opacity:.7;padding:5px;right:0}.note-hint-popover{min-width:100px;padding:2px}.note-hint-popover .popover-content{max-height:150px;overflow:auto;padding:3px}.note-hint-popover .popover-content .note-hint-group .note-hint-item{display:block!important;padding:3px}.note-hint-popover .popover-content .note-hint-group .note-hint-item.active,.note-hint-popover .popover-content .note-hint-group .note-hint-item:hover{background-color:#428bca;clear:both;color:#fff;cursor:pointer;display:block;font-weight:400;line-height:1.4;outline:0;text-decoration:none;white-space:nowrap}body .note-fullscreen-body,html .note-fullscreen-body{overflow:hidden!important}.note-editable ol li,.note-editable ul li{list-style-position:inside}.note-toolbar{background:hsla(0,0%,50%,.114)}.note-btn-group .note-btn{border-color:rgba(0,0,0,.196);font-size:13px;padding:.28rem .65rem}
```

### public/assets/js/bootstrap.bundle.min.js

```javascript
/*!
  * Bootstrap v5.3.8 (https://getbootstrap.com/)
  * Copyright 2011-2025 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
!function(t,e){"object"==typeof exports&&"undefined"!=typeof module?module.exports=e():"function"==typeof define&&define.amd?define(e):(t="undefined"!=typeof globalThis?globalThis:t||self).bootstrap=e()}(this,function(){"use strict";const t=new Map,e={set(e,i,n){t.has(e)||t.set(e,new Map);const s=t.get(e);s.has(i)||0===s.size?s.set(i,n):console.error(`Bootstrap doesn't allow more than one instance per element. Bound instance: ${Array.from(s.keys())[0]}.`)},get:(e,i)=>t.has(e)&&t.get(e).get(i)||null,remove(e,i){if(!t.has(e))return;const n=t.get(e);n.delete(i),0===n.size&&t.delete(e)}},i="transitionend",n=t=>(t&&window.CSS&&window.CSS.escape&&(t=t.replace(/#([^\s"#']+)/g,(t,e)=>`#${CSS.escape(e)}`)),t),s=t=>null==t?`${t}`:Object.prototype.toString.call(t).match(/\s([a-z]+)/i)[1].toLowerCase(),o=t=>{t.dispatchEvent(new Event(i))},r=t=>!(!t||"object"!=typeof t)&&(void 0!==t.jquery&&(t=t[0]),void 0!==t.nodeType),a=t=>r(t)?t.jquery?t[0]:t:"string"==typeof t&&t.length>0?document.querySelector(n(t)):null,l=t=>{if(!r(t)||0===t.getClientRects().length)return!1;const e="visible"===getComputedStyle(t).getPropertyValue("visibility"),i=t.closest("details:not([open])");if(!i)return e;if(i!==t){const e=t.closest("summary");if(e&&e.parentNode!==i)return!1;if(null===e)return!1}return e},c=t=>!t||t.nodeType!==Node.ELEMENT_NODE||!!t.classList.contains("disabled")||(void 0!==t.disabled?t.disabled:t.hasAttribute("disabled")&&"false"!==t.getAttribute("disabled")),h=t=>{if(!document.documentElement.attachShadow)return null;if("function"==typeof t.getRootNode){const e=t.getRootNode();return e instanceof ShadowRoot?e:null}return t instanceof ShadowRoot?t:t.parentNode?h(t.parentNode):null},d=()=>{},u=t=>{t.offsetHeight},f=()=>window.jQuery&&!document.body.hasAttribute("data-bs-no-jquery")?window.jQuery:null,p=[],m=()=>"rtl"===document.documentElement.dir,g=t=>{var e;e=()=>{const e=f();if(e){const i=t.NAME,n=e.fn[i];e.fn[i]=t.jQueryInterface,e.fn[i].Constructor=t,e.fn[i].noConflict=()=>(e.fn[i]=n,t.jQueryInterface)}},"loading"===document.readyState?(p.length||document.addEventListener("DOMContentLoaded",()=>{for(const t of p)t()}),p.push(e)):e()},_=(t,e=[],i=t)=>"function"==typeof t?t.call(...e):i,b=(t,e,n=!0)=>{if(!n)return void _(t);const s=(t=>{if(!t)return 0;let{transitionDuration:e,transitionDelay:i}=window.getComputedStyle(t);const n=Number.parseFloat(e),s=Number.parseFloat(i);return n||s?(e=e.split(",")[0],i=i.split(",")[0],1e3*(Number.parseFloat(e)+Number.parseFloat(i))):0})(e)+5;let r=!1;const a=({target:n})=>{n===e&&(r=!0,e.removeEventListener(i,a),_(t))};e.addEventListener(i,a),setTimeout(()=>{r||o(e)},s)},v=(t,e,i,n)=>{const s=t.length;let o=t.indexOf(e);return-1===o?!i&&n?t[s-1]:t[0]:(o+=i?1:-1,n&&(o=(o+s)%s),t[Math.max(0,Math.min(o,s-1))])},y=/[^.]*(?=\..*)\.|.*/,w=/\..*/,A=/::\d+$/,E={};let T=1;const C={mouseenter:"mouseover",mouseleave:"mouseout"},O=new Set(["click","dblclick","mouseup","mousedown","contextmenu","mousewheel","DOMMouseScroll","mouseover","mouseout","mousemove","selectstart","selectend","keydown","keypress","keyup","orientationchange","touchstart","touchmove","touchend","touchcancel","pointerdown","pointermove","pointerup","pointerleave","pointercancel","gesturestart","gesturechange","gestureend","focus","blur","change","reset","select","submit","focusin","focusout","load","unload","beforeunload","resize","move","DOMContentLoaded","readystatechange","error","abort","scroll"]);function x(t,e){return e&&`${e}::${T++}`||t.uidEvent||T++}function k(t){const e=x(t);return t.uidEvent=e,E[e]=E[e]||{},E[e]}function L(t,e,i=null){return Object.values(t).find(t=>t.callable===e&&t.delegationSelector===i)}function S(t,e,i){const n="string"==typeof e,s=n?i:e||i;let o=N(t);return O.has(o)||(o=t),[n,s,o]}function D(t,e,i,n,s){if("string"!=typeof e||!t)return;let[o,r,a]=S(e,i,n);if(e in C){const t=t=>function(e){if(!e.relatedTarget||e.relatedTarget!==e.delegateTarget&&!e.delegateTarget.contains(e.relatedTarget))return t.call(this,e)};r=t(r)}const l=k(t),c=l[a]||(l[a]={}),h=L(c,r,o?i:null);if(h)return void(h.oneOff=h.oneOff&&s);const d=x(r,e.replace(y,"")),u=o?function(t,e,i){return function n(s){const o=t.querySelectorAll(e);for(let{target:r}=s;r&&r!==this;r=r.parentNode)for(const a of o)if(a===r)return j(s,{delegateTarget:r}),n.oneOff&&P.off(t,s.type,e,i),i.apply(r,[s])}}(t,i,r):function(t,e){return function i(n){return j(n,{delegateTarget:t}),i.oneOff&&P.off(t,n.type,e),e.apply(t,[n])}}(t,r);u.delegationSelector=o?i:null,u.callable=r,u.oneOff=s,u.uidEvent=d,c[d]=u,t.addEventListener(a,u,o)}function $(t,e,i,n,s){const o=L(e[i],n,s);o&&(t.removeEventListener(i,o,Boolean(s)),delete e[i][o.uidEvent])}function I(t,e,i,n){const s=e[i]||{};for(const[o,r]of Object.entries(s))o.includes(n)&&$(t,e,i,r.callable,r.delegationSelector)}function N(t){return t=t.replace(w,""),C[t]||t}const P={on(t,e,i,n){D(t,e,i,n,!1)},one(t,e,i,n){D(t,e,i,n,!0)},off(t,e,i,n){if("string"!=typeof e||!t)return;const[s,o,r]=S(e,i,n),a=r!==e,l=k(t),c=l[r]||{},h=e.startsWith(".");if(void 0===o){if(h)for(const i of Object.keys(l))I(t,l,i,e.slice(1));for(const[i,n]of Object.entries(c)){const s=i.replace(A,"");a&&!e.includes(s)||$(t,l,r,n.callable,n.delegationSelector)}}else{if(!Object.keys(c).length)return;$(t,l,r,o,s?i:null)}},trigger(t,e,i){if("string"!=typeof e||!t)return null;const n=f();let s=null,o=!0,r=!0,a=!1;e!==N(e)&&n&&(s=n.Event(e,i),n(t).trigger(s),o=!s.isPropagationStopped(),r=!s.isImmediatePropagationStopped(),a=s.isDefaultPrevented());const l=j(new Event(e,{bubbles:o,cancelable:!0}),i);return a&&l.preventDefault(),r&&t.dispatchEvent(l),l.defaultPrevented&&s&&s.preventDefault(),l}};function j(t,e={}){for(const[i,n]of Object.entries(e))try{t[i]=n}catch(e){Object.defineProperty(t,i,{configurable:!0,get:()=>n})}return t}function M(t){if("true"===t)return!0;if("false"===t)return!1;if(t===Number(t).toString())return Number(t);if(""===t||"null"===t)return null;if("string"!=typeof t)return t;try{return JSON.parse(decodeURIComponent(t))}catch(e){return t}}function F(t){return t.replace(/[A-Z]/g,t=>`-${t.toLowerCase()}`)}const H={setDataAttribute(t,e,i){t.setAttribute(`data-bs-${F(e)}`,i)},removeDataAttribute(t,e){t.removeAttribute(`data-bs-${F(e)}`)},getDataAttributes(t){if(!t)return{};const e={},i=Object.keys(t.dataset).filter(t=>t.startsWith("bs")&&!t.startsWith("bsConfig"));for(const n of i){let i=n.replace(/^bs/,"");i=i.charAt(0).toLowerCase()+i.slice(1),e[i]=M(t.dataset[n])}return e},getDataAttribute:(t,e)=>M(t.getAttribute(`data-bs-${F(e)}`))};class W{static get Default(){return{}}static get DefaultType(){return{}}static get NAME(){throw new Error('You have to implement the static method "NAME", for each component!')}_getConfig(t){return t=this._mergeConfigObj(t),t=this._configAfterMerge(t),this._typeCheckConfig(t),t}_configAfterMerge(t){return t}_mergeConfigObj(t,e){const i=r(e)?H.getDataAttribute(e,"config"):{};return{...this.constructor.Default,..."object"==typeof i?i:{},...r(e)?H.getDataAttributes(e):{},..."object"==typeof t?t:{}}}_typeCheckConfig(t,e=this.constructor.DefaultType){for(const[i,n]of Object.entries(e)){const e=t[i],o=r(e)?"element":s(e);if(!new RegExp(n).test(o))throw new TypeError(`${this.constructor.NAME.toUpperCase()}: Option "${i}" provided type "${o}" but expected type "${n}".`)}}}class B extends W{constructor(t,i){super(),(t=a(t))&&(this._element=t,this._config=this._getConfig(i),e.set(this._element,this.constructor.DATA_KEY,this))}dispose(){e.remove(this._element,this.constructor.DATA_KEY),P.off(this._element,this.constructor.EVENT_KEY);for(const t of Object.getOwnPropertyNames(this))this[t]=null}_queueCallback(t,e,i=!0){b(t,e,i)}_getConfig(t){return t=this._mergeConfigObj(t,this._element),t=this._configAfterMerge(t),this._typeCheckConfig(t),t}static getInstance(t){return e.get(a(t),this.DATA_KEY)}static getOrCreateInstance(t,e={}){return this.getInstance(t)||new this(t,"object"==typeof e?e:null)}static get VERSION(){return"5.3.8"}static get DATA_KEY(){return`bs.${this.NAME}`}static get EVENT_KEY(){return`.${this.DATA_KEY}`}static eventName(t){return`${t}${this.EVENT_KEY}`}}const z=t=>{let e=t.getAttribute("data-bs-target");if(!e||"#"===e){let i=t.getAttribute("href");if(!i||!i.includes("#")&&!i.startsWith("."))return null;i.includes("#")&&!i.startsWith("#")&&(i=`#${i.split("#")[1]}`),e=i&&"#"!==i?i.trim():null}return e?e.split(",").map(t=>n(t)).join(","):null},R={find:(t,e=document.documentElement)=>[].concat(...Element.prototype.querySelectorAll.call(e,t)),findOne:(t,e=document.documentElement)=>Element.prototype.querySelector.call(e,t),children:(t,e)=>[].concat(...t.children).filter(t=>t.matches(e)),parents(t,e){const i=[];let n=t.parentNode.closest(e);for(;n;)i.push(n),n=n.parentNode.closest(e);return i},prev(t,e){let i=t.previousElementSibling;for(;i;){if(i.matches(e))return[i];i=i.previousElementSibling}return[]},next(t,e){let i=t.nextElementSibling;for(;i;){if(i.matches(e))return[i];i=i.nextElementSibling}return[]},focusableChildren(t){const e=["a","button","input","textarea","select","details","[tabindex]",'[contenteditable="true"]'].map(t=>`${t}:not([tabindex^="-"])`).join(",");return this.find(e,t).filter(t=>!c(t)&&l(t))},getSelectorFromElement(t){const e=z(t);return e&&R.findOne(e)?e:null},getElementFromSelector(t){const e=z(t);return e?R.findOne(e):null},getMultipleElementsFromSelector(t){const e=z(t);return e?R.find(e):[]}},q=(t,e="hide")=>{const i=`click.dismiss${t.EVENT_KEY}`,n=t.NAME;P.on(document,i,`[data-bs-dismiss="${n}"]`,function(i){if(["A","AREA"].includes(this.tagName)&&i.preventDefault(),c(this))return;const s=R.getElementFromSelector(this)||this.closest(`.${n}`);t.getOrCreateInstance(s)[e]()})},V=".bs.alert",K=`close${V}`,Q=`closed${V}`;class X extends B{static get NAME(){return"alert"}close(){if(P.trigger(this._element,K).defaultPrevented)return;this._element.classList.remove("show");const t=this._element.classList.contains("fade");this._queueCallback(()=>this._destroyElement(),this._element,t)}_destroyElement(){this._element.remove(),P.trigger(this._element,Q),this.dispose()}static jQueryInterface(t){return this.each(function(){const e=X.getOrCreateInstance(this);if("string"==typeof t){if(void 0===e[t]||t.startsWith("_")||"constructor"===t)throw new TypeError(`No method named "${t}"`);e[t](this)}})}}q(X,"close"),g(X);const Y='[data-bs-toggle="button"]';class U extends B{static get NAME(){return"button"}toggle(){this._element.setAttribute("aria-pressed",this._element.classList.toggle("active"))}static jQueryInterface(t){return this.each(function(){const e=U.getOrCreateInstance(this);"toggle"===t&&e[t]()})}}P.on(document,"click.bs.button.data-api",Y,t=>{t.preventDefault();const e=t.target.closest(Y);U.getOrCreateInstance(e).toggle()}),g(U);const G=".bs.swipe",J=`touchstart${G}`,Z=`touchmove${G}`,tt=`touchend${G}`,et=`pointerdown${G}`,it=`pointerup${G}`,nt={endCallback:null,leftCallback:null,rightCallback:null},st={endCallback:"(function|null)",leftCallback:"(function|null)",rightCallback:"(function|null)"};class ot extends W{constructor(t,e){super(),this._element=t,t&&ot.isSupported()&&(this._config=this._getConfig(e),this._deltaX=0,this._supportPointerEvents=Boolean(window.PointerEvent),this._initEvents())}static get Default(){return nt}static get DefaultType(){return st}static get NAME(){return"swipe"}dispose(){P.off(this._element,G)}_start(t){this._supportPointerEvents?this._eventIsPointerPenTouch(t)&&(this._deltaX=t.clientX):this._deltaX=t.touches[0].clientX}_end(t){this._eventIsPointerPenTouch(t)&&(this._deltaX=t.clientX-this._deltaX),this._handleSwipe(),_(this._config.endCallback)}_move(t){this._deltaX=t.touches&&t.touches.length>1?0:t.touches[0].clientX-this._deltaX}_handleSwipe(){const t=Math.abs(this._deltaX);if(t<=40)return;const e=t/this._deltaX;this._deltaX=0,e&&_(e>0?this._config.rightCallback:this._config.leftCallback)}_initEvents(){this._supportPointerEvents?(P.on(this._element,et,t=>this._start(t)),P.on(this._element,it,t=>this._end(t)),this._element.classList.add("pointer-event")):(P.on(this._element,J,t=>this._start(t)),P.on(this._element,Z,t=>this._move(t)),P.on(this._element,tt,t=>this._end(t)))}_eventIsPointerPenTouch(t){return this._supportPointerEvents&&("pen"===t.pointerType||"touch"===t.pointerType)}static isSupported(){return"ontouchstart"in document.documentElement||navigator.maxTouchPoints>0}}const rt=".bs.carousel",at=".data-api",lt="ArrowLeft",ct="ArrowRight",ht="next",dt="prev",ut="left",ft="right",pt=`slide${rt}`,mt=`slid${rt}`,gt=`keydown${rt}`,_t=`mouseenter${rt}`,bt=`mouseleave${rt}`,vt=`dragstart${rt}`,yt=`load${rt}${at}`,wt=`click${rt}${at}`,At="carousel",Et="active",Tt=".active",Ct=".carousel-item",Ot=Tt+Ct,xt={[lt]:ft,[ct]:ut},kt={interval:5e3,keyboard:!0,pause:"hover",ride:!1,touch:!0,wrap:!0},Lt={interval:"(number|boolean)",keyboard:"boolean",pause:"(string|boolean)",ride:"(boolean|string)",touch:"boolean",wrap:"boolean"};class St extends B{constructor(t,e){super(t,e),this._interval=null,this._activeElement=null,this._isSliding=!1,this.touchTimeout=null,this._swipeHelper=null,this._indicatorsElement=R.findOne(".carousel-indicators",this._element),this._addEventListeners(),this._config.ride===At&&this.cycle()}static get Default(){return kt}static get DefaultType(){return Lt}static get NAME(){return"carousel"}next(){this._slide(ht)}nextWhenVisible(){!document.hidden&&l(this._element)&&this.next()}prev(){this._slide(dt)}pause(){this._isSliding&&o(this._element),this._clearInterval()}cycle(){this._clearInterval(),this._updateInterval(),this._interval=setInterval(()=>this.nextWhenVisible(),this._config.interval)}_maybeEnableCycle(){this._config.ride&&(this._isSliding?P.one(this._element,mt,()=>this.cycle()):this.cycle())}to(t){const e=this._getItems();if(t>e.length-1||t<0)return;if(this._isSliding)return void P.one(this._element,mt,()=>this.to(t));const i=this._getItemIndex(this._getActive());if(i===t)return;const n=t>i?ht:dt;this._slide(n,e[t])}dispose(){this._swipeHelper&&this._swipeHelper.dispose(),super.dispose()}_configAfterMerge(t){return t.defaultInterval=t.interval,t}_addEventListeners(){this._config.keyboard&&P.on(this._element,gt,t=>this._keydown(t)),"hover"===this._config.pause&&(P.on(this._element,_t,()=>this.pause()),P.on(this._element,bt,()=>this._maybeEnableCycle())),this._config.touch&&ot.isSupported()&&this._addTouchEventListeners()}_addTouchEventListeners(){for(const t of R.find(".carousel-item img",this._element))P.on(t,vt,t=>t.preventDefault());const t={leftCallback:()=>this._slide(this._directionToOrder(ut)),rightCallback:()=>this._slide(this._directionToOrder(ft)),endCallback:()=>{"hover"===this._config.pause&&(this.pause(),this.touchTimeout&&clearTimeout(this.touchTimeout),this.touchTimeout=setTimeout(()=>this._maybeEnableCycle(),500+this._config.interval))}};this._swipeHelper=new ot(this._element,t)}_keydown(t){if(/input|textarea/i.test(t.target.tagName))return;const e=xt[t.key];e&&(t.preventDefault(),this._slide(this._directionToOrder(e)))}_getItemIndex(t){return this._getItems().indexOf(t)}_setActiveIndicatorElement(t){if(!this._indicatorsElement)return;const e=R.findOne(Tt,this._indicatorsElement);e.classList.remove(Et),e.removeAttribute("aria-current");const i=R.findOne(`[data-bs-slide-to="${t}"]`,this._indicatorsElement);i&&(i.classList.add(Et),i.setAttribute("aria-current","true"))}_updateInterval(){const t=this._activeElement||this._getActive();if(!t)return;const e=Number.parseInt(t.getAttribute("data-bs-interval"),10);this._config.interval=e||this._config.defaultInterval}_slide(t,e=null){if(this._isSliding)return;const i=this._getActive(),n=t===ht,s=e||v(this._getItems(),i,n,this._config.wrap);if(s===i)return;const o=this._getItemIndex(s),r=e=>P.trigger(this._element,e,{relatedTarget:s,direction:this._orderToDirection(t),from:this._getItemIndex(i),to:o});if(r(pt).defaultPrevented)return;if(!i||!s)return;const a=Boolean(this._interval);this.pause(),this._isSliding=!0,this._setActiveIndicatorElement(o),this._activeElement=s;const l=n?"carousel-item-start":"carousel-item-end",c=n?"carousel-item-next":"carousel-item-prev";s.classList.add(c),u(s),i.classList.add(l),s.classList.add(l),this._queueCallback(()=>{s.classList.remove(l,c),s.classList.add(Et),i.classList.remove(Et,c,l),this._isSliding=!1,r(mt)},i,this._isAnimated()),a&&this.cycle()}_isAnimated(){return this._element.classList.contains("slide")}_getActive(){return R.findOne(Ot,this._element)}_getItems(){return R.find(Ct,this._element)}_clearInterval(){this._interval&&(clearInterval(this._interval),this._interval=null)}_directionToOrder(t){return m()?t===ut?dt:ht:t===ut?ht:dt}_orderToDirection(t){return m()?t===dt?ut:ft:t===dt?ft:ut}static jQueryInterface(t){return this.each(function(){const e=St.getOrCreateInstance(this,t);if("number"!=typeof t){if("string"==typeof t){if(void 0===e[t]||t.startsWith("_")||"constructor"===t)throw new TypeError(`No method named "${t}"`);e[t]()}}else e.to(t)})}}P.on(document,wt,"[data-bs-slide], [data-bs-slide-to]",function(t){const e=R.getElementFromSelector(this);if(!e||!e.classList.contains(At))return;t.preventDefault();const i=St.getOrCreateInstance(e),n=this.getAttribute("data-bs-slide-to");return n?(i.to(n),void i._maybeEnableCycle()):"next"===H.getDataAttribute(this,"slide")?(i.next(),void i._maybeEnableCycle()):(i.prev(),void i._maybeEnableCycle())}),P.on(window,yt,()=>{const t=R.find('[data-bs-ride="carousel"]');for(const e of t)St.getOrCreateInstance(e)}),g(St);const Dt=".bs.collapse",$t=`show${Dt}`,It=`shown${Dt}`,Nt=`hide${Dt}`,Pt=`hidden${Dt}`,jt=`click${Dt}.data-api`,Mt="show",Ft="collapse",Ht="collapsing",Wt=`:scope .${Ft} .${Ft}`,Bt='[data-bs-toggle="collapse"]',zt={parent:null,toggle:!0},Rt={parent:"(null|element)",toggle:"boolean"};class qt extends B{constructor(t,e){super(t,e),this._isTransitioning=!1,this._triggerArray=[];const i=R.find(Bt);for(const t of i){const e=R.getSelectorFromElement(t),i=R.find(e).filter(t=>t===this._element);null!==e&&i.length&&this._triggerArray.push(t)}this._initializeChildren(),this._config.parent||this._addAriaAndCollapsedClass(this._triggerArray,this._isShown()),this._config.toggle&&this.toggle()}static get Default(){return zt}static get DefaultType(){return Rt}static get NAME(){return"collapse"}toggle(){this._isShown()?this.hide():this.show()}show(){if(this._isTransitioning||this._isShown())return;let t=[];if(this._config.parent&&(t=this._getFirstLevelChildren(".collapse.show, .collapse.collapsing").filter(t=>t!==this._element).map(t=>qt.getOrCreateInstance(t,{toggle:!1}))),t.length&&t[0]._isTransitioning)return;if(P.trigger(this._element,$t).defaultPrevented)return;for(const e of t)e.hide();const e=this._getDimension();this._element.classList.remove(Ft),this._element.classList.add(Ht),this._element.style[e]=0,this._addAriaAndCollapsedClass(this._triggerArray,!0),this._isTransitioning=!0;const i=`scroll${e[0].toUpperCase()+e.slice(1)}`;this._queueCallback(()=>{this._isTransitioning=!1,this._element.classList.remove(Ht),this._element.classList.add(Ft,Mt),this._element.style[e]="",P.trigger(this._element,It)},this._element,!0),this._element.style[e]=`${this._element[i]}px`}hide(){if(this._isTransitioning||!this._isShown())return;if(P.trigger(this._element,Nt).defaultPrevented)return;const t=this._getDimension();this._element.style[t]=`${this._element.getBoundingClientRect()[t]}px`,u(this._element),this._element.classList.add(Ht),this._element.classList.remove(Ft,Mt);for(const t of this._triggerArray){const e=R.getElementFromSelector(t);e&&!this._isShown(e)&&this._addAriaAndCollapsedClass([t],!1)}this._isTransitioning=!0,this._element.style[t]="",this._queueCallback(()=>{this._isTransitioning=!1,this._element.classList.remove(Ht),this._element.classList.add(Ft),P.trigger(this._element,Pt)},this._element,!0)}_isShown(t=this._element){return t.classList.contains(Mt)}_configAfterMerge(t){return t.toggle=Boolean(t.toggle),t.parent=a(t.parent),t}_getDimension(){return this._element.classList.contains("collapse-horizontal")?"width":"height"}_initializeChildren(){if(!this._config.parent)return;const t=this._getFirstLevelChildren(Bt);for(const e of t){const t=R.getElementFromSelector(e);t&&this._addAriaAndCollapsedClass([e],this._isShown(t))}}_getFirstLevelChildren(t){const e=R.find(Wt,this._config.parent);return R.find(t,this._config.parent).filter(t=>!e.includes(t))}_addAriaAndCollapsedClass(t,e){if(t.length)for(const i of t)i.classList.toggle("collapsed",!e),i.setAttribute("aria-expanded",e)}static jQueryInterface(t){const e={};return"string"==typeof t&&/show|hide/.test(t)&&(e.toggle=!1),this.each(function(){const i=qt.getOrCreateInstance(this,e);if("string"==typeof t){if(void 0===i[t])throw new TypeError(`No method named "${t}"`);i[t]()}})}}P.on(document,jt,Bt,function(t){("A"===t.target.tagName||t.delegateTarget&&"A"===t.delegateTarget.tagName)&&t.preventDefault();for(const t of R.getMultipleElementsFromSelector(this))qt.getOrCreateInstance(t,{toggle:!1}).toggle()}),g(qt);var Vt="top",Kt="bottom",Qt="right",Xt="left",Yt="auto",Ut=[Vt,Kt,Qt,Xt],Gt="start",Jt="end",Zt="clippingParents",te="viewport",ee="popper",ie="reference",ne=Ut.reduce(function(t,e){return t.concat([e+"-"+Gt,e+"-"+Jt])},[]),se=[].concat(Ut,[Yt]).reduce(function(t,e){return t.concat([e,e+"-"+Gt,e+"-"+Jt])},[]),oe="beforeRead",re="read",ae="afterRead",le="beforeMain",ce="main",he="afterMain",de="beforeWrite",ue="write",fe="afterWrite",pe=[oe,re,ae,le,ce,he,de,ue,fe];function me(t){return t?(t.nodeName||"").toLowerCase():null}function ge(t){if(null==t)return window;if("[object Window]"!==t.toString()){var e=t.ownerDocument;return e&&e.defaultView||window}return t}function _e(t){return t instanceof ge(t).Element||t instanceof Element}function be(t){return t instanceof ge(t).HTMLElement||t instanceof HTMLElement}function ve(t){return"undefined"!=typeof ShadowRoot&&(t instanceof ge(t).ShadowRoot||t instanceof ShadowRoot)}const ye={name:"applyStyles",enabled:!0,phase:"write",fn:function(t){var e=t.state;Object.keys(e.elements).forEach(function(t){var i=e.styles[t]||{},n=e.attributes[t]||{},s=e.elements[t];be(s)&&me(s)&&(Object.assign(s.style,i),Object.keys(n).forEach(function(t){var e=n[t];!1===e?s.removeAttribute(t):s.setAttribute(t,!0===e?"":e)}))})},effect:function(t){var e=t.state,i={popper:{position:e.options.strategy,left:"0",top:"0",margin:"0"},arrow:{position:"absolute"},reference:{}};return Object.assign(e.elements.popper.style,i.popper),e.styles=i,e.elements.arrow&&Object.assign(e.elements.arrow.style,i.arrow),function(){Object.keys(e.elements).forEach(function(t){var n=e.elements[t],s=e.attributes[t]||{},o=Object.keys(e.styles.hasOwnProperty(t)?e.styles[t]:i[t]).reduce(function(t,e){return t[e]="",t},{});be(n)&&me(n)&&(Object.assign(n.style,o),Object.keys(s).forEach(function(t){n.removeAttribute(t)}))})}},requires:["computeStyles"]};function we(t){return t.split("-")[0]}var Ae=Math.max,Ee=Math.min,Te=Math.round;function Ce(){var t=navigator.userAgentData;return null!=t&&t.brands&&Array.isArray(t.brands)?t.brands.map(function(t){return t.brand+"/"+t.version}).join(" "):navigator.userAgent}function Oe(){return!/^((?!chrome|android).)*safari/i.test(Ce())}function xe(t,e,i){void 0===e&&(e=!1),void 0===i&&(i=!1);var n=t.getBoundingClientRect(),s=1,o=1;e&&be(t)&&(s=t.offsetWidth>0&&Te(n.width)/t.offsetWidth||1,o=t.offsetHeight>0&&Te(n.height)/t.offsetHeight||1);var r=(_e(t)?ge(t):window).visualViewport,a=!Oe()&&i,l=(n.left+(a&&r?r.offsetLeft:0))/s,c=(n.top+(a&&r?r.offsetTop:0))/o,h=n.width/s,d=n.height/o;return{width:h,height:d,top:c,right:l+h,bottom:c+d,left:l,x:l,y:c}}function ke(t){var e=xe(t),i=t.offsetWidth,n=t.offsetHeight;return Math.abs(e.width-i)<=1&&(i=e.width),Math.abs(e.height-n)<=1&&(n=e.height),{x:t.offsetLeft,y:t.offsetTop,width:i,height:n}}function Le(t,e){var i=e.getRootNode&&e.getRootNode();if(t.contains(e))return!0;if(i&&ve(i)){var n=e;do{if(n&&t.isSameNode(n))return!0;n=n.parentNode||n.host}while(n)}return!1}function Se(t){return ge(t).getComputedStyle(t)}function De(t){return["table","td","th"].indexOf(me(t))>=0}function $e(t){return((_e(t)?t.ownerDocument:t.document)||window.document).documentElement}function Ie(t){return"html"===me(t)?t:t.assignedSlot||t.parentNode||(ve(t)?t.host:null)||$e(t)}function Ne(t){return be(t)&&"fixed"!==Se(t).position?t.offsetParent:null}function Pe(t){for(var e=ge(t),i=Ne(t);i&&De(i)&&"static"===Se(i).position;)i=Ne(i);return i&&("html"===me(i)||"body"===me(i)&&"static"===Se(i).position)?e:i||function(t){var e=/firefox/i.test(Ce());if(/Trident/i.test(Ce())&&be(t)&&"fixed"===Se(t).position)return null;var i=Ie(t);for(ve(i)&&(i=i.host);be(i)&&["html","body"].indexOf(me(i))<0;){var n=Se(i);if("none"!==n.transform||"none"!==n.perspective||"paint"===n.contain||-1!==["transform","perspective"].indexOf(n.willChange)||e&&"filter"===n.willChange||e&&n.filter&&"none"!==n.filter)return i;i=i.parentNode}return null}(t)||e}function je(t){return["top","bottom"].indexOf(t)>=0?"x":"y"}function Me(t,e,i){return Ae(t,Ee(e,i))}function Fe(t){return Object.assign({},{top:0,right:0,bottom:0,left:0},t)}function He(t,e){return e.reduce(function(e,i){return e[i]=t,e},{})}const We={name:"arrow",enabled:!0,phase:"main",fn:function(t){var e,i=t.state,n=t.name,s=t.options,o=i.elements.arrow,r=i.modifiersData.popperOffsets,a=we(i.placement),l=je(a),c=[Xt,Qt].indexOf(a)>=0?"height":"width";if(o&&r){var h=function(t,e){return Fe("number"!=typeof(t="function"==typeof t?t(Object.assign({},e.rects,{placement:e.placement})):t)?t:He(t,Ut))}(s.padding,i),d=ke(o),u="y"===l?Vt:Xt,f="y"===l?Kt:Qt,p=i.rects.reference[c]+i.rects.reference[l]-r[l]-i.rects.popper[c],m=r[l]-i.rects.reference[l],g=Pe(o),_=g?"y"===l?g.clientHeight||0:g.clientWidth||0:0,b=p/2-m/2,v=h[u],y=_-d[c]-h[f],w=_/2-d[c]/2+b,A=Me(v,w,y),E=l;i.modifiersData[n]=((e={})[E]=A,e.centerOffset=A-w,e)}},effect:function(t){var e=t.state,i=t.options.element,n=void 0===i?"[data-popper-arrow]":i;null!=n&&("string"!=typeof n||(n=e.elements.popper.querySelector(n)))&&Le(e.elements.popper,n)&&(e.elements.arrow=n)},requires:["popperOffsets"],requiresIfExists:["preventOverflow"]};function Be(t){return t.split("-")[1]}var ze={top:"auto",right:"auto",bottom:"auto",left:"auto"};function Re(t){var e,i=t.popper,n=t.popperRect,s=t.placement,o=t.variation,r=t.offsets,a=t.position,l=t.gpuAcceleration,c=t.adaptive,h=t.roundOffsets,d=t.isFixed,u=r.x,f=void 0===u?0:u,p=r.y,m=void 0===p?0:p,g="function"==typeof h?h({x:f,y:m}):{x:f,y:m};f=g.x,m=g.y;var _=r.hasOwnProperty("x"),b=r.hasOwnProperty("y"),v=Xt,y=Vt,w=window;if(c){var A=Pe(i),E="clientHeight",T="clientWidth";A===ge(i)&&"static"!==Se(A=$e(i)).position&&"absolute"===a&&(E="scrollHeight",T="scrollWidth"),(s===Vt||(s===Xt||s===Qt)&&o===Jt)&&(y=Kt,m-=(d&&A===w&&w.visualViewport?w.visualViewport.height:A[E])-n.height,m*=l?1:-1),s!==Xt&&(s!==Vt&&s!==Kt||o!==Jt)||(v=Qt,f-=(d&&A===w&&w.visualViewport?w.visualViewport.width:A[T])-n.width,f*=l?1:-1)}var C,O=Object.assign({position:a},c&&ze),x=!0===h?function(t,e){var i=t.x,n=t.y,s=e.devicePixelRatio||1;return{x:Te(i*s)/s||0,y:Te(n*s)/s||0}}({x:f,y:m},ge(i)):{x:f,y:m};return f=x.x,m=x.y,l?Object.assign({},O,((C={})[y]=b?"0":"",C[v]=_?"0":"",C.transform=(w.devicePixelRatio||1)<=1?"translate("+f+"px, "+m+"px)":"translate3d("+f+"px, "+m+"px, 0)",C)):Object.assign({},O,((e={})[y]=b?m+"px":"",e[v]=_?f+"px":"",e.transform="",e))}const qe={name:"computeStyles",enabled:!0,phase:"beforeWrite",fn:function(t){var e=t.state,i=t.options,n=i.gpuAcceleration,s=void 0===n||n,o=i.adaptive,r=void 0===o||o,a=i.roundOffsets,l=void 0===a||a,c={placement:we(e.placement),variation:Be(e.placement),popper:e.elements.popper,popperRect:e.rects.popper,gpuAcceleration:s,isFixed:"fixed"===e.options.strategy};null!=e.modifiersData.popperOffsets&&(e.styles.popper=Object.assign({},e.styles.popper,Re(Object.assign({},c,{offsets:e.modifiersData.popperOffsets,position:e.options.strategy,adaptive:r,roundOffsets:l})))),null!=e.modifiersData.arrow&&(e.styles.arrow=Object.assign({},e.styles.arrow,Re(Object.assign({},c,{offsets:e.modifiersData.arrow,position:"absolute",adaptive:!1,roundOffsets:l})))),e.attributes.popper=Object.assign({},e.attributes.popper,{"data-popper-placement":e.placement})},data:{}};var Ve={passive:!0};const Ke={name:"eventListeners",enabled:!0,phase:"write",fn:function(){},effect:function(t){var e=t.state,i=t.instance,n=t.options,s=n.scroll,o=void 0===s||s,r=n.resize,a=void 0===r||r,l=ge(e.elements.popper),c=[].concat(e.scrollParents.reference,e.scrollParents.popper);return o&&c.forEach(function(t){t.addEventListener("scroll",i.update,Ve)}),a&&l.addEventListener("resize",i.update,Ve),function(){o&&c.forEach(function(t){t.removeEventListener("scroll",i.update,Ve)}),a&&l.removeEventListener("resize",i.update,Ve)}},data:{}};var Qe={left:"right",right:"left",bottom:"top",top:"bottom"};function Xe(t){return t.replace(/left|right|bottom|top/g,function(t){return Qe[t]})}var Ye={start:"end",end:"start"};function Ue(t){return t.replace(/start|end/g,function(t){return Ye[t]})}function Ge(t){var e=ge(t);return{scrollLeft:e.pageXOffset,scrollTop:e.pageYOffset}}function Je(t){return xe($e(t)).left+Ge(t).scrollLeft}function Ze(t){var e=Se(t),i=e.overflow,n=e.overflowX,s=e.overflowY;return/auto|scroll|overlay|hidden/.test(i+s+n)}function ti(t){return["html","body","#document"].indexOf(me(t))>=0?t.ownerDocument.body:be(t)&&Ze(t)?t:ti(Ie(t))}function ei(t,e){var i;void 0===e&&(e=[]);var n=ti(t),s=n===(null==(i=t.ownerDocument)?void 0:i.body),o=ge(n),r=s?[o].concat(o.visualViewport||[],Ze(n)?n:[]):n,a=e.concat(r);return s?a:a.concat(ei(Ie(r)))}function ii(t){return Object.assign({},t,{left:t.x,top:t.y,right:t.x+t.width,bottom:t.y+t.height})}function ni(t,e,i){return e===te?ii(function(t,e){var i=ge(t),n=$e(t),s=i.visualViewport,o=n.clientWidth,r=n.clientHeight,a=0,l=0;if(s){o=s.width,r=s.height;var c=Oe();(c||!c&&"fixed"===e)&&(a=s.offsetLeft,l=s.offsetTop)}return{width:o,height:r,x:a+Je(t),y:l}}(t,i)):_e(e)?function(t,e){var i=xe(t,!1,"fixed"===e);return i.top=i.top+t.clientTop,i.left=i.left+t.clientLeft,i.bottom=i.top+t.clientHeight,i.right=i.left+t.clientWidth,i.width=t.clientWidth,i.height=t.clientHeight,i.x=i.left,i.y=i.top,i}(e,i):ii(function(t){var e,i=$e(t),n=Ge(t),s=null==(e=t.ownerDocument)?void 0:e.body,o=Ae(i.scrollWidth,i.clientWidth,s?s.scrollWidth:0,s?s.clientWidth:0),r=Ae(i.scrollHeight,i.clientHeight,s?s.scrollHeight:0,s?s.clientHeight:0),a=-n.scrollLeft+Je(t),l=-n.scrollTop;return"rtl"===Se(s||i).direction&&(a+=Ae(i.clientWidth,s?s.clientWidth:0)-o),{width:o,height:r,x:a,y:l}}($e(t)))}function si(t){var e,i=t.reference,n=t.element,s=t.placement,o=s?we(s):null,r=s?Be(s):null,a=i.x+i.width/2-n.width/2,l=i.y+i.height/2-n.height/2;switch(o){case Vt:e={x:a,y:i.y-n.height};break;case Kt:e={x:a,y:i.y+i.height};break;case Qt:e={x:i.x+i.width,y:l};break;case Xt:e={x:i.x-n.width,y:l};break;default:e={x:i.x,y:i.y}}var c=o?je(o):null;if(null!=c){var h="y"===c?"height":"width";switch(r){case Gt:e[c]=e[c]-(i[h]/2-n[h]/2);break;case Jt:e[c]=e[c]+(i[h]/2-n[h]/2)}}return e}function oi(t,e){void 0===e&&(e={});var i=e,n=i.placement,s=void 0===n?t.placement:n,o=i.strategy,r=void 0===o?t.strategy:o,a=i.boundary,l=void 0===a?Zt:a,c=i.rootBoundary,h=void 0===c?te:c,d=i.elementContext,u=void 0===d?ee:d,f=i.altBoundary,p=void 0!==f&&f,m=i.padding,g=void 0===m?0:m,_=Fe("number"!=typeof g?g:He(g,Ut)),b=u===ee?ie:ee,v=t.rects.popper,y=t.elements[p?b:u],w=function(t,e,i,n){var s="clippingParents"===e?function(t){var e=ei(Ie(t)),i=["absolute","fixed"].indexOf(Se(t).position)>=0&&be(t)?Pe(t):t;return _e(i)?e.filter(function(t){return _e(t)&&Le(t,i)&&"body"!==me(t)}):[]}(t):[].concat(e),o=[].concat(s,[i]),r=o[0],a=o.reduce(function(e,i){var s=ni(t,i,n);return e.top=Ae(s.top,e.top),e.right=Ee(s.right,e.right),e.bottom=Ee(s.bottom,e.bottom),e.left=Ae(s.left,e.left),e},ni(t,r,n));return a.width=a.right-a.left,a.height=a.bottom-a.top,a.x=a.left,a.y=a.top,a}(_e(y)?y:y.contextElement||$e(t.elements.popper),l,h,r),A=xe(t.elements.reference),E=si({reference:A,element:v,placement:s}),T=ii(Object.assign({},v,E)),C=u===ee?T:A,O={top:w.top-C.top+_.top,bottom:C.bottom-w.bottom+_.bottom,left:w.left-C.left+_.left,right:C.right-w.right+_.right},x=t.modifiersData.offset;if(u===ee&&x){var k=x[s];Object.keys(O).forEach(function(t){var e=[Qt,Kt].indexOf(t)>=0?1:-1,i=[Vt,Kt].indexOf(t)>=0?"y":"x";O[t]+=k[i]*e})}return O}function ri(t,e){void 0===e&&(e={});var i=e,n=i.placement,s=i.boundary,o=i.rootBoundary,r=i.padding,a=i.flipVariations,l=i.allowedAutoPlacements,c=void 0===l?se:l,h=Be(n),d=h?a?ne:ne.filter(function(t){return Be(t)===h}):Ut,u=d.filter(function(t){return c.indexOf(t)>=0});0===u.length&&(u=d);var f=u.reduce(function(e,i){return e[i]=oi(t,{placement:i,boundary:s,rootBoundary:o,padding:r})[we(i)],e},{});return Object.keys(f).sort(function(t,e){return f[t]-f[e]})}const ai={name:"flip",enabled:!0,phase:"main",fn:function(t){var e=t.state,i=t.options,n=t.name;if(!e.modifiersData[n]._skip){for(var s=i.mainAxis,o=void 0===s||s,r=i.altAxis,a=void 0===r||r,l=i.fallbackPlacements,c=i.padding,h=i.boundary,d=i.rootBoundary,u=i.altBoundary,f=i.flipVariations,p=void 0===f||f,m=i.allowedAutoPlacements,g=e.options.placement,_=we(g),b=l||(_!==g&&p?function(t){if(we(t)===Yt)return[];var e=Xe(t);return[Ue(t),e,Ue(e)]}(g):[Xe(g)]),v=[g].concat(b).reduce(function(t,i){return t.concat(we(i)===Yt?ri(e,{placement:i,boundary:h,rootBoundary:d,padding:c,flipVariations:p,allowedAutoPlacements:m}):i)},[]),y=e.rects.reference,w=e.rects.popper,A=new Map,E=!0,T=v[0],C=0;C<v.length;C++){var O=v[C],x=we(O),k=Be(O)===Gt,L=[Vt,Kt].indexOf(x)>=0,S=L?"width":"height",D=oi(e,{placement:O,boundary:h,rootBoundary:d,altBoundary:u,padding:c}),$=L?k?Qt:Xt:k?Kt:Vt;y[S]>w[S]&&($=Xe($));var I=Xe($),N=[];if(o&&N.push(D[x]<=0),a&&N.push(D[$]<=0,D[I]<=0),N.every(function(t){return t})){T=O,E=!1;break}A.set(O,N)}if(E)for(var P=function(t){var e=v.find(function(e){var i=A.get(e);if(i)return i.slice(0,t).every(function(t){return t})});if(e)return T=e,"break"},j=p?3:1;j>0&&"break"!==P(j);j--);e.placement!==T&&(e.modifiersData[n]._skip=!0,e.placement=T,e.reset=!0)}},requiresIfExists:["offset"],data:{_skip:!1}};function li(t,e,i){return void 0===i&&(i={x:0,y:0}),{top:t.top-e.height-i.y,right:t.right-e.width+i.x,bottom:t.bottom-e.height+i.y,left:t.left-e.width-i.x}}function ci(t){return[Vt,Qt,Kt,Xt].some(function(e){return t[e]>=0})}const hi={name:"hide",enabled:!0,phase:"main",requiresIfExists:["preventOverflow"],fn:function(t){var e=t.state,i=t.name,n=e.rects.reference,s=e.rects.popper,o=e.modifiersData.preventOverflow,r=oi(e,{elementContext:"reference"}),a=oi(e,{altBoundary:!0}),l=li(r,n),c=li(a,s,o),h=ci(l),d=ci(c);e.modifiersData[i]={referenceClippingOffsets:l,popperEscapeOffsets:c,isReferenceHidden:h,hasPopperEscaped:d},e.attributes.popper=Object.assign({},e.attributes.popper,{"data-popper-reference-hidden":h,"data-popper-escaped":d})}},di={name:"offset",enabled:!0,phase:"main",requires:["popperOffsets"],fn:function(t){var e=t.state,i=t.options,n=t.name,s=i.offset,o=void 0===s?[0,0]:s,r=se.reduce(function(t,i){return t[i]=function(t,e,i){var n=we(t),s=[Xt,Vt].indexOf(n)>=0?-1:1,o="function"==typeof i?i(Object.assign({},e,{placement:t})):i,r=o[0],a=o[1];return r=r||0,a=(a||0)*s,[Xt,Qt].indexOf(n)>=0?{x:a,y:r}:{x:r,y:a}}(i,e.rects,o),t},{}),a=r[e.placement],l=a.x,c=a.y;null!=e.modifiersData.popperOffsets&&(e.modifiersData.popperOffsets.x+=l,e.modifiersData.popperOffsets.y+=c),e.modifiersData[n]=r}},ui={name:"popperOffsets",enabled:!0,phase:"read",fn:function(t){var e=t.state,i=t.name;e.modifiersData[i]=si({reference:e.rects.reference,element:e.rects.popper,placement:e.placement})},data:{}},fi={name:"preventOverflow",enabled:!0,phase:"main",fn:function(t){var e=t.state,i=t.options,n=t.name,s=i.mainAxis,o=void 0===s||s,r=i.altAxis,a=void 0!==r&&r,l=i.boundary,c=i.rootBoundary,h=i.altBoundary,d=i.padding,u=i.tether,f=void 0===u||u,p=i.tetherOffset,m=void 0===p?0:p,g=oi(e,{boundary:l,rootBoundary:c,padding:d,altBoundary:h}),_=we(e.placement),b=Be(e.placement),v=!b,y=je(_),w="x"===y?"y":"x",A=e.modifiersData.popperOffsets,E=e.rects.reference,T=e.rects.popper,C="function"==typeof m?m(Object.assign({},e.rects,{placement:e.placement})):m,O="number"==typeof C?{mainAxis:C,altAxis:C}:Object.assign({mainAxis:0,altAxis:0},C),x=e.modifiersData.offset?e.modifiersData.offset[e.placement]:null,k={x:0,y:0};if(A){if(o){var L,S="y"===y?Vt:Xt,D="y"===y?Kt:Qt,$="y"===y?"height":"width",I=A[y],N=I+g[S],P=I-g[D],j=f?-T[$]/2:0,M=b===Gt?E[$]:T[$],F=b===Gt?-T[$]:-E[$],H=e.elements.arrow,W=f&&H?ke(H):{width:0,height:0},B=e.modifiersData["arrow#persistent"]?e.modifiersData["arrow#persistent"].padding:{top:0,right:0,bottom:0,left:0},z=B[S],R=B[D],q=Me(0,E[$],W[$]),V=v?E[$]/2-j-q-z-O.mainAxis:M-q-z-O.mainAxis,K=v?-E[$]/2+j+q+R+O.mainAxis:F+q+R+O.mainAxis,Q=e.elements.arrow&&Pe(e.elements.arrow),X=Q?"y"===y?Q.clientTop||0:Q.clientLeft||0:0,Y=null!=(L=null==x?void 0:x[y])?L:0,U=I+K-Y,G=Me(f?Ee(N,I+V-Y-X):N,I,f?Ae(P,U):P);A[y]=G,k[y]=G-I}if(a){var J,Z="x"===y?Vt:Xt,tt="x"===y?Kt:Qt,et=A[w],it="y"===w?"height":"width",nt=et+g[Z],st=et-g[tt],ot=-1!==[Vt,Xt].indexOf(_),rt=null!=(J=null==x?void 0:x[w])?J:0,at=ot?nt:et-E[it]-T[it]-rt+O.altAxis,lt=ot?et+E[it]+T[it]-rt-O.altAxis:st,ct=f&&ot?function(t,e,i){var n=Me(t,e,i);return n>i?i:n}(at,et,lt):Me(f?at:nt,et,f?lt:st);A[w]=ct,k[w]=ct-et}e.modifiersData[n]=k}},requiresIfExists:["offset"]};function pi(t,e,i){void 0===i&&(i=!1);var n,s,o=be(e),r=be(e)&&function(t){var e=t.getBoundingClientRect(),i=Te(e.width)/t.offsetWidth||1,n=Te(e.height)/t.offsetHeight||1;return 1!==i||1!==n}(e),a=$e(e),l=xe(t,r,i),c={scrollLeft:0,scrollTop:0},h={x:0,y:0};return(o||!o&&!i)&&(("body"!==me(e)||Ze(a))&&(c=(n=e)!==ge(n)&&be(n)?{scrollLeft:(s=n).scrollLeft,scrollTop:s.scrollTop}:Ge(n)),be(e)?((h=xe(e,!0)).x+=e.clientLeft,h.y+=e.clientTop):a&&(h.x=Je(a))),{x:l.left+c.scrollLeft-h.x,y:l.top+c.scrollTop-h.y,width:l.width,height:l.height}}function mi(t){var e=new Map,i=new Set,n=[];function s(t){i.add(t.name),[].concat(t.requires||[],t.requiresIfExists||[]).forEach(function(t){if(!i.has(t)){var n=e.get(t);n&&s(n)}}),n.push(t)}return t.forEach(function(t){e.set(t.name,t)}),t.forEach(function(t){i.has(t.name)||s(t)}),n}var gi={placement:"bottom",modifiers:[],strategy:"absolute"};function _i(){for(var t=arguments.length,e=new Array(t),i=0;i<t;i++)e[i]=arguments[i];return!e.some(function(t){return!(t&&"function"==typeof t.getBoundingClientRect)})}function bi(t){void 0===t&&(t={});var e=t,i=e.defaultModifiers,n=void 0===i?[]:i,s=e.defaultOptions,o=void 0===s?gi:s;return function(t,e,i){void 0===i&&(i=o);var s,r,a={placement:"bottom",orderedModifiers:[],options:Object.assign({},gi,o),modifiersData:{},elements:{reference:t,popper:e},attributes:{},styles:{}},l=[],c=!1,h={state:a,setOptions:function(i){var s="function"==typeof i?i(a.options):i;d(),a.options=Object.assign({},o,a.options,s),a.scrollParents={reference:_e(t)?ei(t):t.contextElement?ei(t.contextElement):[],popper:ei(e)};var r,c,u=function(t){var e=mi(t);return pe.reduce(function(t,i){return t.concat(e.filter(function(t){return t.phase===i}))},[])}((r=[].concat(n,a.options.modifiers),c=r.reduce(function(t,e){var i=t[e.name];return t[e.name]=i?Object.assign({},i,e,{options:Object.assign({},i.options,e.options),data:Object.assign({},i.data,e.data)}):e,t},{}),Object.keys(c).map(function(t){return c[t]})));return a.orderedModifiers=u.filter(function(t){return t.enabled}),a.orderedModifiers.forEach(function(t){var e=t.name,i=t.options,n=void 0===i?{}:i,s=t.effect;if("function"==typeof s){var o=s({state:a,name:e,instance:h,options:n});l.push(o||function(){})}}),h.update()},forceUpdate:function(){if(!c){var t=a.elements,e=t.reference,i=t.popper;if(_i(e,i)){a.rects={reference:pi(e,Pe(i),"fixed"===a.options.strategy),popper:ke(i)},a.reset=!1,a.placement=a.options.placement,a.orderedModifiers.forEach(function(t){return a.modifiersData[t.name]=Object.assign({},t.data)});for(var n=0;n<a.orderedModifiers.length;n++)if(!0!==a.reset){var s=a.orderedModifiers[n],o=s.fn,r=s.options,l=void 0===r?{}:r,d=s.name;"function"==typeof o&&(a=o({state:a,options:l,name:d,instance:h})||a)}else a.reset=!1,n=-1}}},update:(s=function(){return new Promise(function(t){h.forceUpdate(),t(a)})},function(){return r||(r=new Promise(function(t){Promise.resolve().then(function(){r=void 0,t(s())})})),r}),destroy:function(){d(),c=!0}};if(!_i(t,e))return h;function d(){l.forEach(function(t){return t()}),l=[]}return h.setOptions(i).then(function(t){!c&&i.onFirstUpdate&&i.onFirstUpdate(t)}),h}}var vi=bi(),yi=bi({defaultModifiers:[Ke,ui,qe,ye]}),wi=bi({defaultModifiers:[Ke,ui,qe,ye,di,ai,fi,We,hi]});const Ai=Object.freeze(Object.defineProperty({__proto__:null,afterMain:he,afterRead:ae,afterWrite:fe,applyStyles:ye,arrow:We,auto:Yt,basePlacements:Ut,beforeMain:le,beforeRead:oe,beforeWrite:de,bottom:Kt,clippingParents:Zt,computeStyles:qe,createPopper:wi,createPopperBase:vi,createPopperLite:yi,detectOverflow:oi,end:Jt,eventListeners:Ke,flip:ai,hide:hi,left:Xt,main:ce,modifierPhases:pe,offset:di,placements:se,popper:ee,popperGenerator:bi,popperOffsets:ui,preventOverflow:fi,read:re,reference:ie,right:Qt,start:Gt,top:Vt,variationPlacements:ne,viewport:te,write:ue},Symbol.toStringTag,{value:"Module"})),Ei="dropdown",Ti=".bs.dropdown",Ci=".data-api",Oi="ArrowUp",xi="ArrowDown",ki=`hide${Ti}`,Li=`hidden${Ti}`,Si=`show${Ti}`,Di=`shown${Ti}`,$i=`click${Ti}${Ci}`,Ii=`keydown${Ti}${Ci}`,Ni=`keyup${Ti}${Ci}`,Pi="show",ji='[data-bs-toggle="dropdown"]:not(.disabled):not(:disabled)',Mi=`${ji}.${Pi}`,Fi=".dropdown-menu",Hi=m()?"top-end":"top-start",Wi=m()?"top-start":"top-end",Bi=m()?"bottom-end":"bottom-start",zi=m()?"bottom-start":"bottom-end",Ri=m()?"left-start":"right-start",qi=m()?"right-start":"left-start",Vi={autoClose:!0,boundary:"clippingParents",display:"dynamic",offset:[0,2],popperConfig:null,reference:"toggle"},Ki={autoClose:"(boolean|string)",boundary:"(string|element)",display:"string",offset:"(array|string|function)",popperConfig:"(null|object|function)",reference:"(string|element|object)"};class Qi extends B{constructor(t,e){super(t,e),this._popper=null,this._parent=this._element.parentNode,this._menu=R.next(this._element,Fi)[0]||R.prev(this._element,Fi)[0]||R.findOne(Fi,this._parent),this._inNavbar=this._detectNavbar()}static get Default(){return Vi}static get DefaultType(){return Ki}static get NAME(){return Ei}toggle(){return this._isShown()?this.hide():this.show()}show(){if(c(this._element)||this._isShown())return;const t={relatedTarget:this._element};if(!P.trigger(this._element,Si,t).defaultPrevented){if(this._createPopper(),"ontouchstart"in document.documentElement&&!this._parent.closest(".navbar-nav"))for(const t of[].concat(...document.body.children))P.on(t,"mouseover",d);this._element.focus(),this._element.setAttribute("aria-expanded",!0),this._menu.classList.add(Pi),this._element.classList.add(Pi),P.trigger(this._element,Di,t)}}hide(){if(c(this._element)||!this._isShown())return;const t={relatedTarget:this._element};this._completeHide(t)}dispose(){this._popper&&this._popper.destroy(),super.dispose()}update(){this._inNavbar=this._detectNavbar(),this._popper&&this._popper.update()}_completeHide(t){if(!P.trigger(this._element,ki,t).defaultPrevented){if("ontouchstart"in document.documentElement)for(const t of[].concat(...document.body.children))P.off(t,"mouseover",d);this._popper&&this._popper.destroy(),this._menu.classList.remove(Pi),this._element.classList.remove(Pi),this._element.setAttribute("aria-expanded","false"),H.removeDataAttribute(this._menu,"popper"),P.trigger(this._element,Li,t)}}_getConfig(t){if("object"==typeof(t=super._getConfig(t)).reference&&!r(t.reference)&&"function"!=typeof t.reference.getBoundingClientRect)throw new TypeError(`${Ei.toUpperCase()}: Option "reference" provided type "object" without a required "getBoundingClientRect" method.`);return t}_createPopper(){if(void 0===Ai)throw new TypeError("Bootstrap's dropdowns require Popper (https://popper.js.org/docs/v2/)");let t=this._element;"parent"===this._config.reference?t=this._parent:r(this._config.reference)?t=a(this._config.reference):"object"==typeof this._config.reference&&(t=this._config.reference);const e=this._getPopperConfig();this._popper=wi(t,this._menu,e)}_isShown(){return this._menu.classList.contains(Pi)}_getPlacement(){const t=this._parent;if(t.classList.contains("dropend"))return Ri;if(t.classList.contains("dropstart"))return qi;if(t.classList.contains("dropup-center"))return"top";if(t.classList.contains("dropdown-center"))return"bottom";const e="end"===getComputedStyle(this._menu).getPropertyValue("--bs-position").trim();return t.classList.contains("dropup")?e?Wi:Hi:e?zi:Bi}_detectNavbar(){return null!==this._element.closest(".navbar")}_getOffset(){const{offset:t}=this._config;return"string"==typeof t?t.split(",").map(t=>Number.parseInt(t,10)):"function"==typeof t?e=>t(e,this._element):t}_getPopperConfig(){const t={placement:this._getPlacement(),modifiers:[{name:"preventOverflow",options:{boundary:this._config.boundary}},{name:"offset",options:{offset:this._getOffset()}}]};return(this._inNavbar||"static"===this._config.display)&&(H.setDataAttribute(this._menu,"popper","static"),t.modifiers=[{name:"applyStyles",enabled:!1}]),{...t,..._(this._config.popperConfig,[void 0,t])}}_selectMenuItem({key:t,target:e}){const i=R.find(".dropdown-menu .dropdown-item:not(.disabled):not(:disabled)",this._menu).filter(t=>l(t));i.length&&v(i,e,t===xi,!i.includes(e)).focus()}static jQueryInterface(t){return this.each(function(){const e=Qi.getOrCreateInstance(this,t);if("string"==typeof t){if(void 0===e[t])throw new TypeError(`No method named "${t}"`);e[t]()}})}static clearMenus(t){if(2===t.button||"keyup"===t.type&&"Tab"!==t.key)return;const e=R.find(Mi);for(const i of e){const e=Qi.getInstance(i);if(!e||!1===e._config.autoClose)continue;const n=t.composedPath(),s=n.includes(e._menu);if(n.includes(e._element)||"inside"===e._config.autoClose&&!s||"outside"===e._config.autoClose&&s)continue;if(e._menu.contains(t.target)&&("keyup"===t.type&&"Tab"===t.key||/input|select|option|textarea|form/i.test(t.target.tagName)))continue;const o={relatedTarget:e._element};"click"===t.type&&(o.clickEvent=t),e._completeHide(o)}}static dataApiKeydownHandler(t){const e=/input|textarea/i.test(t.target.tagName),i="Escape"===t.key,n=[Oi,xi].includes(t.key);if(!n&&!i)return;if(e&&!i)return;t.preventDefault();const s=this.matches(ji)?this:R.prev(this,ji)[0]||R.next(this,ji)[0]||R.findOne(ji,t.delegateTarget.parentNode),o=Qi.getOrCreateInstance(s);if(n)return t.stopPropagation(),o.show(),void o._selectMenuItem(t);o._isShown()&&(t.stopPropagation(),o.hide(),s.focus())}}P.on(document,Ii,ji,Qi.dataApiKeydownHandler),P.on(document,Ii,Fi,Qi.dataApiKeydownHandler),P.on(document,$i,Qi.clearMenus),P.on(document,Ni,Qi.clearMenus),P.on(document,$i,ji,function(t){t.preventDefault(),Qi.getOrCreateInstance(this).toggle()}),g(Qi);const Xi="backdrop",Yi="show",Ui=`mousedown.bs.${Xi}`,Gi={className:"modal-backdrop",clickCallback:null,isAnimated:!1,isVisible:!0,rootElement:"body"},Ji={className:"string",clickCallback:"(function|null)",isAnimated:"boolean",isVisible:"boolean",rootElement:"(element|string)"};class Zi extends W{constructor(t){super(),this._config=this._getConfig(t),this._isAppended=!1,this._element=null}static get Default(){return Gi}static get DefaultType(){return Ji}static get NAME(){return Xi}show(t){if(!this._config.isVisible)return void _(t);this._append();const e=this._getElement();this._config.isAnimated&&u(e),e.classList.add(Yi),this._emulateAnimation(()=>{_(t)})}hide(t){this._config.isVisible?(this._getElement().classList.remove(Yi),this._emulateAnimation(()=>{this.dispose(),_(t)})):_(t)}dispose(){this._isAppended&&(P.off(this._element,Ui),this._element.remove(),this._isAppended=!1)}_getElement(){if(!this._element){const t=document.createElement("div");t.className=this._config.className,this._config.isAnimated&&t.classList.add("fade"),this._element=t}return this._element}_configAfterMerge(t){return t.rootElement=a(t.rootElement),t}_append(){if(this._isAppended)return;const t=this._getElement();this._config.rootElement.append(t),P.on(t,Ui,()=>{_(this._config.clickCallback)}),this._isAppended=!0}_emulateAnimation(t){b(t,this._getElement(),this._config.isAnimated)}}const tn=".bs.focustrap",en=`focusin${tn}`,nn=`keydown.tab${tn}`,sn="backward",on={autofocus:!0,trapElement:null},rn={autofocus:"boolean",trapElement:"element"};class an extends W{constructor(t){super(),this._config=this._getConfig(t),this._isActive=!1,this._lastTabNavDirection=null}static get Default(){return on}static get DefaultType(){return rn}static get NAME(){return"focustrap"}activate(){this._isActive||(this._config.autofocus&&this._config.trapElement.focus(),P.off(document,tn),P.on(document,en,t=>this._handleFocusin(t)),P.on(document,nn,t=>this._handleKeydown(t)),this._isActive=!0)}deactivate(){this._isActive&&(this._isActive=!1,P.off(document,tn))}_handleFocusin(t){const{trapElement:e}=this._config;if(t.target===document||t.target===e||e.contains(t.target))return;const i=R.focusableChildren(e);0===i.length?e.focus():this._lastTabNavDirection===sn?i[i.length-1].focus():i[0].focus()}_handleKeydown(t){"Tab"===t.key&&(this._lastTabNavDirection=t.shiftKey?sn:"forward")}}const ln=".fixed-top, .fixed-bottom, .is-fixed, .sticky-top",cn=".sticky-top",hn="padding-right",dn="margin-right";class un{constructor(){this._element=document.body}getWidth(){const t=document.documentElement.clientWidth;return Math.abs(window.innerWidth-t)}hide(){const t=this.getWidth();this._disableOverFlow(),this._setElementAttributes(this._element,hn,e=>e+t),this._setElementAttributes(ln,hn,e=>e+t),this._setElementAttributes(cn,dn,e=>e-t)}reset(){this._resetElementAttributes(this._element,"overflow"),this._resetElementAttributes(this._element,hn),this._resetElementAttributes(ln,hn),this._resetElementAttributes(cn,dn)}isOverflowing(){return this.getWidth()>0}_disableOverFlow(){this._saveInitialAttribute(this._element,"overflow"),this._element.style.overflow="hidden"}_setElementAttributes(t,e,i){const n=this.getWidth();this._applyManipulationCallback(t,t=>{if(t!==this._element&&window.innerWidth>t.clientWidth+n)return;this._saveInitialAttribute(t,e);const s=window.getComputedStyle(t).getPropertyValue(e);t.style.setProperty(e,`${i(Number.parseFloat(s))}px`)})}_saveInitialAttribute(t,e){const i=t.style.getPropertyValue(e);i&&H.setDataAttribute(t,e,i)}_resetElementAttributes(t,e){this._applyManipulationCallback(t,t=>{const i=H.getDataAttribute(t,e);null!==i?(H.removeDataAttribute(t,e),t.style.setProperty(e,i)):t.style.removeProperty(e)})}_applyManipulationCallback(t,e){if(r(t))e(t);else for(const i of R.find(t,this._element))e(i)}}const fn=".bs.modal",pn=`hide${fn}`,mn=`hidePrevented${fn}`,gn=`hidden${fn}`,_n=`show${fn}`,bn=`shown${fn}`,vn=`resize${fn}`,yn=`click.dismiss${fn}`,wn=`mousedown.dismiss${fn}`,An=`keydown.dismiss${fn}`,En=`click${fn}.data-api`,Tn="modal-open",Cn="show",On="modal-static",xn={backdrop:!0,focus:!0,keyboard:!0},kn={backdrop:"(boolean|string)",focus:"boolean",keyboard:"boolean"};class Ln extends B{constructor(t,e){super(t,e),this._dialog=R.findOne(".modal-dialog",this._element),this._backdrop=this._initializeBackDrop(),this._focustrap=this._initializeFocusTrap(),this._isShown=!1,this._isTransitioning=!1,this._scrollBar=new un,this._addEventListeners()}static get Default(){return xn}static get DefaultType(){return kn}static get NAME(){return"modal"}toggle(t){return this._isShown?this.hide():this.show(t)}show(t){this._isShown||this._isTransitioning||P.trigger(this._element,_n,{relatedTarget:t}).defaultPrevented||(this._isShown=!0,this._isTransitioning=!0,this._scrollBar.hide(),document.body.classList.add(Tn),this._adjustDialog(),this._backdrop.show(()=>this._showElement(t)))}hide(){this._isShown&&!this._isTransitioning&&(P.trigger(this._element,pn).defaultPrevented||(this._isShown=!1,this._isTransitioning=!0,this._focustrap.deactivate(),this._element.classList.remove(Cn),this._queueCallback(()=>this._hideModal(),this._element,this._isAnimated())))}dispose(){P.off(window,fn),P.off(this._dialog,fn),this._backdrop.dispose(),this._focustrap.deactivate(),super.dispose()}handleUpdate(){this._adjustDialog()}_initializeBackDrop(){return new Zi({isVisible:Boolean(this._config.backdrop),isAnimated:this._isAnimated()})}_initializeFocusTrap(){return new an({trapElement:this._element})}_showElement(t){document.body.contains(this._element)||document.body.append(this._element),this._element.style.display="block",this._element.removeAttribute("aria-hidden"),this._element.setAttribute("aria-modal",!0),this._element.setAttribute("role","dialog"),this._element.scrollTop=0;const e=R.findOne(".modal-body",this._dialog);e&&(e.scrollTop=0),u(this._element),this._element.classList.add(Cn),this._queueCallback(()=>{this._config.focus&&this._focustrap.activate(),this._isTransitioning=!1,P.trigger(this._element,bn,{relatedTarget:t})},this._dialog,this._isAnimated())}_addEventListeners(){P.on(this._element,An,t=>{"Escape"===t.key&&(this._config.keyboard?this.hide():this._triggerBackdropTransition())}),P.on(window,vn,()=>{this._isShown&&!this._isTransitioning&&this._adjustDialog()}),P.on(this._element,wn,t=>{P.one(this._element,yn,e=>{this._element===t.target&&this._element===e.target&&("static"!==this._config.backdrop?this._config.backdrop&&this.hide():this._triggerBackdropTransition())})})}_hideModal(){this._element.style.display="none",this._element.setAttribute("aria-hidden",!0),this._element.removeAttribute("aria-modal"),this._element.removeAttribute("role"),this._isTransitioning=!1,this._backdrop.hide(()=>{document.body.classList.remove(Tn),this._resetAdjustments(),this._scrollBar.reset(),P.trigger(this._element,gn)})}_isAnimated(){return this._element.classList.contains("fade")}_triggerBackdropTransition(){if(P.trigger(this._element,mn).defaultPrevented)return;const t=this._element.scrollHeight>document.documentElement.clientHeight,e=this._element.style.overflowY;"hidden"===e||this._element.classList.contains(On)||(t||(this._element.style.overflowY="hidden"),this._element.classList.add(On),this._queueCallback(()=>{this._element.classList.remove(On),this._queueCallback(()=>{this._element.style.overflowY=e},this._dialog)},this._dialog),this._element.focus())}_adjustDialog(){const t=this._element.scrollHeight>document.documentElement.clientHeight,e=this._scrollBar.getWidth(),i=e>0;if(i&&!t){const t=m()?"paddingLeft":"paddingRight";this._element.style[t]=`${e}px`}if(!i&&t){const t=m()?"paddingRight":"paddingLeft";this._element.style[t]=`${e}px`}}_resetAdjustments(){this._element.style.paddingLeft="",this._element.style.paddingRight=""}static jQueryInterface(t,e){return this.each(function(){const i=Ln.getOrCreateInstance(this,t);if("string"==typeof t){if(void 0===i[t])throw new TypeError(`No method named "${t}"`);i[t](e)}})}}P.on(document,En,'[data-bs-toggle="modal"]',function(t){const e=R.getElementFromSelector(this);["A","AREA"].includes(this.tagName)&&t.preventDefault(),P.one(e,_n,t=>{t.defaultPrevented||P.one(e,gn,()=>{l(this)&&this.focus()})});const i=R.findOne(".modal.show");i&&Ln.getInstance(i).hide(),Ln.getOrCreateInstance(e).toggle(this)}),q(Ln),g(Ln);const Sn=".bs.offcanvas",Dn=".data-api",$n=`load${Sn}${Dn}`,In="show",Nn="showing",Pn="hiding",jn=".offcanvas.show",Mn=`show${Sn}`,Fn=`shown${Sn}`,Hn=`hide${Sn}`,Wn=`hidePrevented${Sn}`,Bn=`hidden${Sn}`,zn=`resize${Sn}`,Rn=`click${Sn}${Dn}`,qn=`keydown.dismiss${Sn}`,Vn={backdrop:!0,keyboard:!0,scroll:!1},Kn={backdrop:"(boolean|string)",keyboard:"boolean",scroll:"boolean"};class Qn extends B{constructor(t,e){super(t,e),this._isShown=!1,this._backdrop=this._initializeBackDrop(),this._focustrap=this._initializeFocusTrap(),this._addEventListeners()}static get Default(){return Vn}static get DefaultType(){return Kn}static get NAME(){return"offcanvas"}toggle(t){return this._isShown?this.hide():this.show(t)}show(t){this._isShown||P.trigger(this._element,Mn,{relatedTarget:t}).defaultPrevented||(this._isShown=!0,this._backdrop.show(),this._config.scroll||(new un).hide(),this._element.setAttribute("aria-modal",!0),this._element.setAttribute("role","dialog"),this._element.classList.add(Nn),this._queueCallback(()=>{this._config.scroll&&!this._config.backdrop||this._focustrap.activate(),this._element.classList.add(In),this._element.classList.remove(Nn),P.trigger(this._element,Fn,{relatedTarget:t})},this._element,!0))}hide(){this._isShown&&(P.trigger(this._element,Hn).defaultPrevented||(this._focustrap.deactivate(),this._element.blur(),this._isShown=!1,this._element.classList.add(Pn),this._backdrop.hide(),this._queueCallback(()=>{this._element.classList.remove(In,Pn),this._element.removeAttribute("aria-modal"),this._element.removeAttribute("role"),this._config.scroll||(new un).reset(),P.trigger(this._element,Bn)},this._element,!0)))}dispose(){this._backdrop.dispose(),this._focustrap.deactivate(),super.dispose()}_initializeBackDrop(){const t=Boolean(this._config.backdrop);return new Zi({className:"offcanvas-backdrop",isVisible:t,isAnimated:!0,rootElement:this._element.parentNode,clickCallback:t?()=>{"static"!==this._config.backdrop?this.hide():P.trigger(this._element,Wn)}:null})}_initializeFocusTrap(){return new an({trapElement:this._element})}_addEventListeners(){P.on(this._element,qn,t=>{"Escape"===t.key&&(this._config.keyboard?this.hide():P.trigger(this._element,Wn))})}static jQueryInterface(t){return this.each(function(){const e=Qn.getOrCreateInstance(this,t);if("string"==typeof t){if(void 0===e[t]||t.startsWith("_")||"constructor"===t)throw new TypeError(`No method named "${t}"`);e[t](this)}})}}P.on(document,Rn,'[data-bs-toggle="offcanvas"]',function(t){const e=R.getElementFromSelector(this);if(["A","AREA"].includes(this.tagName)&&t.preventDefault(),c(this))return;P.one(e,Bn,()=>{l(this)&&this.focus()});const i=R.findOne(jn);i&&i!==e&&Qn.getInstance(i).hide(),Qn.getOrCreateInstance(e).toggle(this)}),P.on(window,$n,()=>{for(const t of R.find(jn))Qn.getOrCreateInstance(t).show()}),P.on(window,zn,()=>{for(const t of R.find("[aria-modal][class*=show][class*=offcanvas-]"))"fixed"!==getComputedStyle(t).position&&Qn.getOrCreateInstance(t).hide()}),q(Qn),g(Qn);const Xn={"*":["class","dir","id","lang","role",/^aria-[\w-]*$/i],a:["target","href","title","rel"],area:[],b:[],br:[],col:[],code:[],dd:[],div:[],dl:[],dt:[],em:[],hr:[],h1:[],h2:[],h3:[],h4:[],h5:[],h6:[],i:[],img:["src","srcset","alt","title","width","height"],li:[],ol:[],p:[],pre:[],s:[],small:[],span:[],sub:[],sup:[],strong:[],u:[],ul:[]},Yn=new Set(["background","cite","href","itemtype","longdesc","poster","src","xlink:href"]),Un=/^(?!javascript:)(?:[a-z0-9+.-]+:|[^&:/?#]*(?:[/?#]|$))/i,Gn=(t,e)=>{const i=t.nodeName.toLowerCase();return e.includes(i)?!Yn.has(i)||Boolean(Un.test(t.nodeValue)):e.filter(t=>t instanceof RegExp).some(t=>t.test(i))},Jn={allowList:Xn,content:{},extraClass:"",html:!1,sanitize:!0,sanitizeFn:null,template:"<div></div>"},Zn={allowList:"object",content:"object",extraClass:"(string|function)",html:"boolean",sanitize:"boolean",sanitizeFn:"(null|function)",template:"string"},ts={entry:"(string|element|function|null)",selector:"(string|element)"};class es extends W{constructor(t){super(),this._config=this._getConfig(t)}static get Default(){return Jn}static get DefaultType(){return Zn}static get NAME(){return"TemplateFactory"}getContent(){return Object.values(this._config.content).map(t=>this._resolvePossibleFunction(t)).filter(Boolean)}hasContent(){return this.getContent().length>0}changeContent(t){return this._checkContent(t),this._config.content={...this._config.content,...t},this}toHtml(){const t=document.createElement("div");t.innerHTML=this._maybeSanitize(this._config.template);for(const[e,i]of Object.entries(this._config.content))this._setContent(t,i,e);const e=t.children[0],i=this._resolvePossibleFunction(this._config.extraClass);return i&&e.classList.add(...i.split(" ")),e}_typeCheckConfig(t){super._typeCheckConfig(t),this._checkContent(t.content)}_checkContent(t){for(const[e,i]of Object.entries(t))super._typeCheckConfig({selector:e,entry:i},ts)}_setContent(t,e,i){const n=R.findOne(i,t);n&&((e=this._resolvePossibleFunction(e))?r(e)?this._putElementInTemplate(a(e),n):this._config.html?n.innerHTML=this._maybeSanitize(e):n.textContent=e:n.remove())}_maybeSanitize(t){return this._config.sanitize?function(t,e,i){if(!t.length)return t;if(i&&"function"==typeof i)return i(t);const n=(new window.DOMParser).parseFromString(t,"text/html"),s=[].concat(...n.body.querySelectorAll("*"));for(const t of s){const i=t.nodeName.toLowerCase();if(!Object.keys(e).includes(i)){t.remove();continue}const n=[].concat(...t.attributes),s=[].concat(e["*"]||[],e[i]||[]);for(const e of n)Gn(e,s)||t.removeAttribute(e.nodeName)}return n.body.innerHTML}(t,this._config.allowList,this._config.sanitizeFn):t}_resolvePossibleFunction(t){return _(t,[void 0,this])}_putElementInTemplate(t,e){if(this._config.html)return e.innerHTML="",void e.append(t);e.textContent=t.textContent}}const is=new Set(["sanitize","allowList","sanitizeFn"]),ns="fade",ss="show",os=".tooltip-inner",rs=".modal",as="hide.bs.modal",ls="hover",cs="focus",hs="click",ds={AUTO:"auto",TOP:"top",RIGHT:m()?"left":"right",BOTTOM:"bottom",LEFT:m()?"right":"left"},us={allowList:Xn,animation:!0,boundary:"clippingParents",container:!1,customClass:"",delay:0,fallbackPlacements:["top","right","bottom","left"],html:!1,offset:[0,6],placement:"top",popperConfig:null,sanitize:!0,sanitizeFn:null,selector:!1,template:'<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',title:"",trigger:"hover focus"},fs={allowList:"object",animation:"boolean",boundary:"(string|element)",container:"(string|element|boolean)",customClass:"(string|function)",delay:"(number|object)",fallbackPlacements:"array",html:"boolean",offset:"(array|string|function)",placement:"(string|function)",popperConfig:"(null|object|function)",sanitize:"boolean",sanitizeFn:"(null|function)",selector:"(string|boolean)",template:"string",title:"(string|element|function)",trigger:"string"};class ps extends B{constructor(t,e){if(void 0===Ai)throw new TypeError("Bootstrap's tooltips require Popper (https://popper.js.org/docs/v2/)");super(t,e),this._isEnabled=!0,this._timeout=0,this._isHovered=null,this._activeTrigger={},this._popper=null,this._templateFactory=null,this._newContent=null,this.tip=null,this._setListeners(),this._config.selector||this._fixTitle()}static get Default(){return us}static get DefaultType(){return fs}static get NAME(){return"tooltip"}enable(){this._isEnabled=!0}disable(){this._isEnabled=!1}toggleEnabled(){this._isEnabled=!this._isEnabled}toggle(){this._isEnabled&&(this._isShown()?this._leave():this._enter())}dispose(){clearTimeout(this._timeout),P.off(this._element.closest(rs),as,this._hideModalHandler),this._element.getAttribute("data-bs-original-title")&&this._element.setAttribute("title",this._element.getAttribute("data-bs-original-title")),this._disposePopper(),super.dispose()}show(){if("none"===this._element.style.display)throw new Error("Please use show on visible elements");if(!this._isWithContent()||!this._isEnabled)return;const t=P.trigger(this._element,this.constructor.eventName("show")),e=(h(this._element)||this._element.ownerDocument.documentElement).contains(this._element);if(t.defaultPrevented||!e)return;this._disposePopper();const i=this._getTipElement();this._element.setAttribute("aria-describedby",i.getAttribute("id"));const{container:n}=this._config;if(this._element.ownerDocument.documentElement.contains(this.tip)||(n.append(i),P.trigger(this._element,this.constructor.eventName("inserted"))),this._popper=this._createPopper(i),i.classList.add(ss),"ontouchstart"in document.documentElement)for(const t of[].concat(...document.body.children))P.on(t,"mouseover",d);this._queueCallback(()=>{P.trigger(this._element,this.constructor.eventName("shown")),!1===this._isHovered&&this._leave(),this._isHovered=!1},this.tip,this._isAnimated())}hide(){if(this._isShown()&&!P.trigger(this._element,this.constructor.eventName("hide")).defaultPrevented){if(this._getTipElement().classList.remove(ss),"ontouchstart"in document.documentElement)for(const t of[].concat(...document.body.children))P.off(t,"mouseover",d);this._activeTrigger[hs]=!1,this._activeTrigger[cs]=!1,this._activeTrigger[ls]=!1,this._isHovered=null,this._queueCallback(()=>{this._isWithActiveTrigger()||(this._isHovered||this._disposePopper(),this._element.removeAttribute("aria-describedby"),P.trigger(this._element,this.constructor.eventName("hidden")))},this.tip,this._isAnimated())}}update(){this._popper&&this._popper.update()}_isWithContent(){return Boolean(this._getTitle())}_getTipElement(){return this.tip||(this.tip=this._createTipElement(this._newContent||this._getContentForTemplate())),this.tip}_createTipElement(t){const e=this._getTemplateFactory(t).toHtml();if(!e)return null;e.classList.remove(ns,ss),e.classList.add(`bs-${this.constructor.NAME}-auto`);const i=(t=>{do{t+=Math.floor(1e6*Math.random())}while(document.getElementById(t));return t})(this.constructor.NAME).toString();return e.setAttribute("id",i),this._isAnimated()&&e.classList.add(ns),e}setContent(t){this._newContent=t,this._isShown()&&(this._disposePopper(),this.show())}_getTemplateFactory(t){return this._templateFactory?this._templateFactory.changeContent(t):this._templateFactory=new es({...this._config,content:t,extraClass:this._resolvePossibleFunction(this._config.customClass)}),this._templateFactory}_getContentForTemplate(){return{[os]:this._getTitle()}}_getTitle(){return this._resolvePossibleFunction(this._config.title)||this._element.getAttribute("data-bs-original-title")}_initializeOnDelegatedTarget(t){return this.constructor.getOrCreateInstance(t.delegateTarget,this._getDelegateConfig())}_isAnimated(){return this._config.animation||this.tip&&this.tip.classList.contains(ns)}_isShown(){return this.tip&&this.tip.classList.contains(ss)}_createPopper(t){const e=_(this._config.placement,[this,t,this._element]),i=ds[e.toUpperCase()];return wi(this._element,t,this._getPopperConfig(i))}_getOffset(){const{offset:t}=this._config;return"string"==typeof t?t.split(",").map(t=>Number.parseInt(t,10)):"function"==typeof t?e=>t(e,this._element):t}_resolvePossibleFunction(t){return _(t,[this._element,this._element])}_getPopperConfig(t){const e={placement:t,modifiers:[{name:"flip",options:{fallbackPlacements:this._config.fallbackPlacements}},{name:"offset",options:{offset:this._getOffset()}},{name:"preventOverflow",options:{boundary:this._config.boundary}},{name:"arrow",options:{element:`.${this.constructor.NAME}-arrow`}},{name:"preSetPlacement",enabled:!0,phase:"beforeMain",fn:t=>{this._getTipElement().setAttribute("data-popper-placement",t.state.placement)}}]};return{...e,..._(this._config.popperConfig,[void 0,e])}}_setListeners(){const t=this._config.trigger.split(" ");for(const e of t)if("click"===e)P.on(this._element,this.constructor.eventName("click"),this._config.selector,t=>{const e=this._initializeOnDelegatedTarget(t);e._activeTrigger[hs]=!(e._isShown()&&e._activeTrigger[hs]),e.toggle()});else if("manual"!==e){const t=e===ls?this.constructor.eventName("mouseenter"):this.constructor.eventName("focusin"),i=e===ls?this.constructor.eventName("mouseleave"):this.constructor.eventName("focusout");P.on(this._element,t,this._config.selector,t=>{const e=this._initializeOnDelegatedTarget(t);e._activeTrigger["focusin"===t.type?cs:ls]=!0,e._enter()}),P.on(this._element,i,this._config.selector,t=>{const e=this._initializeOnDelegatedTarget(t);e._activeTrigger["focusout"===t.type?cs:ls]=e._element.contains(t.relatedTarget),e._leave()})}this._hideModalHandler=()=>{this._element&&this.hide()},P.on(this._element.closest(rs),as,this._hideModalHandler)}_fixTitle(){const t=this._element.getAttribute("title");t&&(this._element.getAttribute("aria-label")||this._element.textContent.trim()||this._element.setAttribute("aria-label",t),this._element.setAttribute("data-bs-original-title",t),this._element.removeAttribute("title"))}_enter(){this._isShown()||this._isHovered?this._isHovered=!0:(this._isHovered=!0,this._setTimeout(()=>{this._isHovered&&this.show()},this._config.delay.show))}_leave(){this._isWithActiveTrigger()||(this._isHovered=!1,this._setTimeout(()=>{this._isHovered||this.hide()},this._config.delay.hide))}_setTimeout(t,e){clearTimeout(this._timeout),this._timeout=setTimeout(t,e)}_isWithActiveTrigger(){return Object.values(this._activeTrigger).includes(!0)}_getConfig(t){const e=H.getDataAttributes(this._element);for(const t of Object.keys(e))is.has(t)&&delete e[t];return t={...e,..."object"==typeof t&&t?t:{}},t=this._mergeConfigObj(t),t=this._configAfterMerge(t),this._typeCheckConfig(t),t}_configAfterMerge(t){return t.container=!1===t.container?document.body:a(t.container),"number"==typeof t.delay&&(t.delay={show:t.delay,hide:t.delay}),"number"==typeof t.title&&(t.title=t.title.toString()),"number"==typeof t.content&&(t.content=t.content.toString()),t}_getDelegateConfig(){const t={};for(const[e,i]of Object.entries(this._config))this.constructor.Default[e]!==i&&(t[e]=i);return t.selector=!1,t.trigger="manual",t}_disposePopper(){this._popper&&(this._popper.destroy(),this._popper=null),this.tip&&(this.tip.remove(),this.tip=null)}static jQueryInterface(t){return this.each(function(){const e=ps.getOrCreateInstance(this,t);if("string"==typeof t){if(void 0===e[t])throw new TypeError(`No method named "${t}"`);e[t]()}})}}g(ps);const ms=".popover-header",gs=".popover-body",_s={...ps.Default,content:"",offset:[0,8],placement:"right",template:'<div class="popover" role="tooltip"><div class="popover-arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>',trigger:"click"},bs={...ps.DefaultType,content:"(null|string|element|function)"};class vs extends ps{static get Default(){return _s}static get DefaultType(){return bs}static get NAME(){return"popover"}_isWithContent(){return this._getTitle()||this._getContent()}_getContentForTemplate(){return{[ms]:this._getTitle(),[gs]:this._getContent()}}_getContent(){return this._resolvePossibleFunction(this._config.content)}static jQueryInterface(t){return this.each(function(){const e=vs.getOrCreateInstance(this,t);if("string"==typeof t){if(void 0===e[t])throw new TypeError(`No method named "${t}"`);e[t]()}})}}g(vs);const ys=".bs.scrollspy",ws=`activate${ys}`,As=`click${ys}`,Es=`load${ys}.data-api`,Ts="active",Cs="[href]",Os=".nav-link",xs=`${Os}, .nav-item > ${Os}, .list-group-item`,ks={offset:null,rootMargin:"0px 0px -25%",smoothScroll:!1,target:null,threshold:[.1,.5,1]},Ls={offset:"(number|null)",rootMargin:"string",smoothScroll:"boolean",target:"element",threshold:"array"};class Ss extends B{constructor(t,e){super(t,e),this._targetLinks=new Map,this._observableSections=new Map,this._rootElement="visible"===getComputedStyle(this._element).overflowY?null:this._element,this._activeTarget=null,this._observer=null,this._previousScrollData={visibleEntryTop:0,parentScrollTop:0},this.refresh()}static get Default(){return ks}static get DefaultType(){return Ls}static get NAME(){return"scrollspy"}refresh(){this._initializeTargetsAndObservables(),this._maybeEnableSmoothScroll(),this._observer?this._observer.disconnect():this._observer=this._getNewObserver();for(const t of this._observableSections.values())this._observer.observe(t)}dispose(){this._observer.disconnect(),super.dispose()}_configAfterMerge(t){return t.target=a(t.target)||document.body,t.rootMargin=t.offset?`${t.offset}px 0px -30%`:t.rootMargin,"string"==typeof t.threshold&&(t.threshold=t.threshold.split(",").map(t=>Number.parseFloat(t))),t}_maybeEnableSmoothScroll(){this._config.smoothScroll&&(P.off(this._config.target,As),P.on(this._config.target,As,Cs,t=>{const e=this._observableSections.get(t.target.hash);if(e){t.preventDefault();const i=this._rootElement||window,n=e.offsetTop-this._element.offsetTop;if(i.scrollTo)return void i.scrollTo({top:n,behavior:"smooth"});i.scrollTop=n}}))}_getNewObserver(){const t={root:this._rootElement,threshold:this._config.threshold,rootMargin:this._config.rootMargin};return new IntersectionObserver(t=>this._observerCallback(t),t)}_observerCallback(t){const e=t=>this._targetLinks.get(`#${t.target.id}`),i=t=>{this._previousScrollData.visibleEntryTop=t.target.offsetTop,this._process(e(t))},n=(this._rootElement||document.documentElement).scrollTop,s=n>=this._previousScrollData.parentScrollTop;this._previousScrollData.parentScrollTop=n;for(const o of t){if(!o.isIntersecting){this._activeTarget=null,this._clearActiveClass(e(o));continue}const t=o.target.offsetTop>=this._previousScrollData.visibleEntryTop;if(s&&t){if(i(o),!n)return}else s||t||i(o)}}_initializeTargetsAndObservables(){this._targetLinks=new Map,this._observableSections=new Map;const t=R.find(Cs,this._config.target);for(const e of t){if(!e.hash||c(e))continue;const t=R.findOne(decodeURI(e.hash),this._element);l(t)&&(this._targetLinks.set(decodeURI(e.hash),e),this._observableSections.set(e.hash,t))}}_process(t){this._activeTarget!==t&&(this._clearActiveClass(this._config.target),this._activeTarget=t,t.classList.add(Ts),this._activateParents(t),P.trigger(this._element,ws,{relatedTarget:t}))}_activateParents(t){if(t.classList.contains("dropdown-item"))R.findOne(".dropdown-toggle",t.closest(".dropdown")).classList.add(Ts);else for(const e of R.parents(t,".nav, .list-group"))for(const t of R.prev(e,xs))t.classList.add(Ts)}_clearActiveClass(t){t.classList.remove(Ts);const e=R.find(`${Cs}.${Ts}`,t);for(const t of e)t.classList.remove(Ts)}static jQueryInterface(t){return this.each(function(){const e=Ss.getOrCreateInstance(this,t);if("string"==typeof t){if(void 0===e[t]||t.startsWith("_")||"constructor"===t)throw new TypeError(`No method named "${t}"`);e[t]()}})}}P.on(window,Es,()=>{for(const t of R.find('[data-bs-spy="scroll"]'))Ss.getOrCreateInstance(t)}),g(Ss);const Ds=".bs.tab",$s=`hide${Ds}`,Is=`hidden${Ds}`,Ns=`show${Ds}`,Ps=`shown${Ds}`,js=`click${Ds}`,Ms=`keydown${Ds}`,Fs=`load${Ds}`,Hs="ArrowLeft",Ws="ArrowRight",Bs="ArrowUp",zs="ArrowDown",Rs="Home",qs="End",Vs="active",Ks="fade",Qs="show",Xs=".dropdown-toggle",Ys=`:not(${Xs})`,Us='[data-bs-toggle="tab"], [data-bs-toggle="pill"], [data-bs-toggle="list"]',Gs=`.nav-link${Ys}, .list-group-item${Ys}, [role="tab"]${Ys}, ${Us}`,Js=`.${Vs}[data-bs-toggle="tab"], .${Vs}[data-bs-toggle="pill"], .${Vs}[data-bs-toggle="list"]`;class Zs extends B{constructor(t){super(t),this._parent=this._element.closest('.list-group, .nav, [role="tablist"]'),this._parent&&(this._setInitialAttributes(this._parent,this._getChildren()),P.on(this._element,Ms,t=>this._keydown(t)))}static get NAME(){return"tab"}show(){const t=this._element;if(this._elemIsActive(t))return;const e=this._getActiveElem(),i=e?P.trigger(e,$s,{relatedTarget:t}):null;P.trigger(t,Ns,{relatedTarget:e}).defaultPrevented||i&&i.defaultPrevented||(this._deactivate(e,t),this._activate(t,e))}_activate(t,e){t&&(t.classList.add(Vs),this._activate(R.getElementFromSelector(t)),this._queueCallback(()=>{"tab"===t.getAttribute("role")?(t.removeAttribute("tabindex"),t.setAttribute("aria-selected",!0),this._toggleDropDown(t,!0),P.trigger(t,Ps,{relatedTarget:e})):t.classList.add(Qs)},t,t.classList.contains(Ks)))}_deactivate(t,e){t&&(t.classList.remove(Vs),t.blur(),this._deactivate(R.getElementFromSelector(t)),this._queueCallback(()=>{"tab"===t.getAttribute("role")?(t.setAttribute("aria-selected",!1),t.setAttribute("tabindex","-1"),this._toggleDropDown(t,!1),P.trigger(t,Is,{relatedTarget:e})):t.classList.remove(Qs)},t,t.classList.contains(Ks)))}_keydown(t){if(![Hs,Ws,Bs,zs,Rs,qs].includes(t.key))return;t.stopPropagation(),t.preventDefault();const e=this._getChildren().filter(t=>!c(t));let i;if([Rs,qs].includes(t.key))i=e[t.key===Rs?0:e.length-1];else{const n=[Ws,zs].includes(t.key);i=v(e,t.target,n,!0)}i&&(i.focus({preventScroll:!0}),Zs.getOrCreateInstance(i).show())}_getChildren(){return R.find(Gs,this._parent)}_getActiveElem(){return this._getChildren().find(t=>this._elemIsActive(t))||null}_setInitialAttributes(t,e){this._setAttributeIfNotExists(t,"role","tablist");for(const t of e)this._setInitialAttributesOnChild(t)}_setInitialAttributesOnChild(t){t=this._getInnerElement(t);const e=this._elemIsActive(t),i=this._getOuterElement(t);t.setAttribute("aria-selected",e),i!==t&&this._setAttributeIfNotExists(i,"role","presentation"),e||t.setAttribute("tabindex","-1"),this._setAttributeIfNotExists(t,"role","tab"),this._setInitialAttributesOnTargetPanel(t)}_setInitialAttributesOnTargetPanel(t){const e=R.getElementFromSelector(t);e&&(this._setAttributeIfNotExists(e,"role","tabpanel"),t.id&&this._setAttributeIfNotExists(e,"aria-labelledby",`${t.id}`))}_toggleDropDown(t,e){const i=this._getOuterElement(t);if(!i.classList.contains("dropdown"))return;const n=(t,n)=>{const s=R.findOne(t,i);s&&s.classList.toggle(n,e)};n(Xs,Vs),n(".dropdown-menu",Qs),i.setAttribute("aria-expanded",e)}_setAttributeIfNotExists(t,e,i){t.hasAttribute(e)||t.setAttribute(e,i)}_elemIsActive(t){return t.classList.contains(Vs)}_getInnerElement(t){return t.matches(Gs)?t:R.findOne(Gs,t)}_getOuterElement(t){return t.closest(".nav-item, .list-group-item")||t}static jQueryInterface(t){return this.each(function(){const e=Zs.getOrCreateInstance(this);if("string"==typeof t){if(void 0===e[t]||t.startsWith("_")||"constructor"===t)throw new TypeError(`No method named "${t}"`);e[t]()}})}}P.on(document,js,Us,function(t){["A","AREA"].includes(this.tagName)&&t.preventDefault(),c(this)||Zs.getOrCreateInstance(this).show()}),P.on(window,Fs,()=>{for(const t of R.find(Js))Zs.getOrCreateInstance(t)}),g(Zs);const to=".bs.toast",eo=`mouseover${to}`,io=`mouseout${to}`,no=`focusin${to}`,so=`focusout${to}`,oo=`hide${to}`,ro=`hidden${to}`,ao=`show${to}`,lo=`shown${to}`,co="hide",ho="show",uo="showing",fo={animation:"boolean",autohide:"boolean",delay:"number"},po={animation:!0,autohide:!0,delay:5e3};class mo extends B{constructor(t,e){super(t,e),this._timeout=null,this._hasMouseInteraction=!1,this._hasKeyboardInteraction=!1,this._setListeners()}static get Default(){return po}static get DefaultType(){return fo}static get NAME(){return"toast"}show(){P.trigger(this._element,ao).defaultPrevented||(this._clearTimeout(),this._config.animation&&this._element.classList.add("fade"),this._element.classList.remove(co),u(this._element),this._element.classList.add(ho,uo),this._queueCallback(()=>{this._element.classList.remove(uo),P.trigger(this._element,lo),this._maybeScheduleHide()},this._element,this._config.animation))}hide(){this.isShown()&&(P.trigger(this._element,oo).defaultPrevented||(this._element.classList.add(uo),this._queueCallback(()=>{this._element.classList.add(co),this._element.classList.remove(uo,ho),P.trigger(this._element,ro)},this._element,this._config.animation)))}dispose(){this._clearTimeout(),this.isShown()&&this._element.classList.remove(ho),super.dispose()}isShown(){return this._element.classList.contains(ho)}_maybeScheduleHide(){this._config.autohide&&(this._hasMouseInteraction||this._hasKeyboardInteraction||(this._timeout=setTimeout(()=>{this.hide()},this._config.delay)))}_onInteraction(t,e){switch(t.type){case"mouseover":case"mouseout":this._hasMouseInteraction=e;break;case"focusin":case"focusout":this._hasKeyboardInteraction=e}if(e)return void this._clearTimeout();const i=t.relatedTarget;this._element===i||this._element.contains(i)||this._maybeScheduleHide()}_setListeners(){P.on(this._element,eo,t=>this._onInteraction(t,!0)),P.on(this._element,io,t=>this._onInteraction(t,!1)),P.on(this._element,no,t=>this._onInteraction(t,!0)),P.on(this._element,so,t=>this._onInteraction(t,!1))}_clearTimeout(){clearTimeout(this._timeout),this._timeout=null}static jQueryInterface(t){return this.each(function(){const e=mo.getOrCreateInstance(this,t);if("string"==typeof t){if(void 0===e[t])throw new TypeError(`No method named "${t}"`);e[t](this)}})}}return q(mo),g(mo),{Alert:X,Button:U,Carousel:St,Collapse:qt,Dropdown:Qi,Modal:Ln,Offcanvas:Qn,Popover:vs,ScrollSpy:Ss,Tab:Zs,Toast:mo,Tooltip:ps}});
//# sourceMappingURL=bootstrap.bundle.min.js.map
```

### public/assets/js/jquery.slim.min.js

```javascript
/*! jQuery v4.0.0+slim | (c) OpenJS Foundation and other contributors | jquery.com/license */
!function(e,t){"use strict";"object"==typeof module&&"object"==typeof module.exports?module.exports=t(e,!0):t(e)}("undefined"!=typeof window?window:this,function(e,t){"use strict";if(!e.document)throw Error("jQuery requires a window with a document");var n=[],r=Object.getPrototypeOf,i=n.slice,o=n.flat?function(e){return n.flat.call(e)}:function(e){return n.concat.apply([],e)},a=n.push,s=n.indexOf,u={},l=u.toString,c=u.hasOwnProperty,f=c.toString,p=f.call(Object),d={};function h(e){return null==e?e+"":"object"==typeof e?u[l.call(e)]||"object":typeof e}function g(e){return null!=e&&e===e.window}function v(e){var t=!!e&&e.length,n=h(e);return!("function"==typeof e||g(e))&&("array"===n||0===t||"number"==typeof t&&t>0&&t-1 in e)}var y=e.document,m={type:!0,src:!0,nonce:!0,noModule:!0};function x(e,t,n){var r,i=(n=n||y).createElement("script");for(r in i.text=e,m)t&&t[r]&&(i[r]=t[r]);n.head.appendChild(i).parentNode&&i.parentNode.removeChild(i)}var b="4.0.0+slim",w=/HTML$/i,T=function(e,t){return new T.fn.init(e,t)};function C(e,t){return e.nodeName&&e.nodeName.toLowerCase()===t.toLowerCase()}T.fn=T.prototype={jquery:b,constructor:T,length:0,toArray:function(){return i.call(this)},get:function(e){return null==e?i.call(this):e<0?this[e+this.length]:this[e]},pushStack:function(e){var t=T.merge(this.constructor(),e);return t.prevObject=this,t},each:function(e){return T.each(this,e)},map:function(e){return this.pushStack(T.map(this,function(t,n){return e.call(t,n,t)}))},slice:function(){return this.pushStack(i.apply(this,arguments))},first:function(){return this.eq(0)},last:function(){return this.eq(-1)},even:function(){return this.pushStack(T.grep(this,function(e,t){return(t+1)%2}))},odd:function(){return this.pushStack(T.grep(this,function(e,t){return t%2}))},eq:function(e){var t=this.length,n=+e+(e<0?t:0);return this.pushStack(n>=0&&n<t?[this[n]]:[])},end:function(){return this.prevObject||this.constructor()}},T.extend=T.fn.extend=function(){var e,t,n,r,i,o,a=arguments[0]||{},s=1,u=arguments.length,l=!1;for("boolean"==typeof a&&(l=a,a=arguments[s]||{},s++),"object"!=typeof a&&"function"!=typeof a&&(a={}),s===u&&(a=this,s--);s<u;s++)if(null!=(e=arguments[s]))for(t in e)r=e[t],"__proto__"!==t&&a!==r&&(l&&r&&(T.isPlainObject(r)||(i=Array.isArray(r)))?(n=a[t],o=i&&!Array.isArray(n)?[]:i||T.isPlainObject(n)?n:{},i=!1,a[t]=T.extend(l,o,r)):void 0!==r&&(a[t]=r));return a},T.extend({expando:"jQuery"+(b+Math.random()).replace(/\D/g,""),isReady:!0,error:function(e){throw Error(e)},noop:function(){},isPlainObject:function(e){var t,n;return!!e&&"[object Object]"===l.call(e)&&(!(t=r(e))||"function"==typeof(n=c.call(t,"constructor")&&t.constructor)&&f.call(n)===p)},isEmptyObject:function(e){var t;for(t in e)return!1;return!0},globalEval:function(e,t,n){x(e,{nonce:t&&t.nonce},n)},each:function(e,t){var n,r=0;if(v(e))for(n=e.length;r<n&&!1!==t.call(e[r],r,e[r]);r++);else for(r in e)if(!1===t.call(e[r],r,e[r]))break;return e},text:function(e){var t,n="",r=0,i=e.nodeType;if(!i)while(t=e[r++])n+=T.text(t);return 1===i||11===i?e.textContent:9===i?e.documentElement.textContent:3===i||4===i?e.nodeValue:n},makeArray:function(e,t){var n=t||[];return null!=e&&(v(Object(e))?T.merge(n,"string"==typeof e?[e]:e):a.call(n,e)),n},inArray:function(e,t,n){return null==t?-1:s.call(t,e,n)},isXMLDoc:function(e){var t=e&&e.namespaceURI,n=e&&(e.ownerDocument||e).documentElement;return!w.test(t||n&&n.nodeName||"HTML")},contains:function(e,t){var n=t&&t.parentNode;return e===n||!!(n&&1===n.nodeType&&(e.contains?e.contains(n):e.compareDocumentPosition&&16&e.compareDocumentPosition(n)))},merge:function(e,t){for(var n=+t.length,r=0,i=e.length;r<n;r++)e[i++]=t[r];return e.length=i,e},grep:function(e,t,n){for(var r=[],i=0,o=e.length,a=!n;i<o;i++)!t(e[i],i)!==a&&r.push(e[i]);return r},map:function(e,t,n){var r,i,a=0,s=[];if(v(e))for(r=e.length;a<r;a++)null!=(i=t(e[a],a,n))&&s.push(i);else for(a in e)null!=(i=t(e[a],a,n))&&s.push(i);return o(s)},guid:1,support:d}),"function"==typeof Symbol&&(T.fn[Symbol.iterator]=n[Symbol.iterator]),T.each("Boolean Number String Function Array Date RegExp Object Error Symbol".split(" "),function(e,t){u["[object "+t+"]"]=t.toLowerCase()});var E=n.pop,S="[\\x20\\t\\r\\n\\f]",D=y.documentMode,A=D&&RegExp(":enabled|:disabled|\\["+S+"*name"+S+"*="+S+"*(?:''|\"\")"),N=RegExp("^"+S+"+|((?:^|[^\\\\])(?:\\\\.)*)"+S+"+$","g"),k="(?:\\\\[\\da-fA-F]{1,6}"+S+"?|\\\\[^\\r\\n\\f]|[\\w-]|[^\0-\\x7f])+",O=RegExp("^"+S+"*([>+~]|"+S+")"+S+"*"),j=RegExp(S+"|>"),L=/[+~]/,P=y.documentElement,R=P.matches||P.msMatchesSelector;function I(){var e=[];function t(n,r){return e.push(n+" ")>T.expr.cacheLength&&delete t[e.shift()],t[n+" "]=r}return t}function H(e){return e&&void 0!==e.getElementsByTagName&&e}var $="\\["+S+"*("+k+")(?:"+S+"*([*^$|!~]?=)"+S+"*(?:'((?:\\\\.|[^\\\\'])*)'|\"((?:\\\\.|[^\\\\\"])*)\"|("+k+"))|)"+S+"*\\]",M=":("+k+")(?:\\((('((?:\\\\.|[^\\\\'])*)'|\"((?:\\\\.|[^\\\\\"])*)\")|((?:\\\\.|[^\\\\()[\\]]|"+$+")*)|.*)\\)|)",B={ID:RegExp("^#("+k+")"),CLASS:RegExp("^\\.("+k+")"),TAG:RegExp("^("+k+"|[*])"),ATTR:RegExp("^"+$),PSEUDO:RegExp("^"+M),CHILD:RegExp("^:(only|first|last|nth|nth-last)-(child|of-type)(?:\\("+S+"*(even|odd|(([+-]|)(\\d*)n|)"+S+"*(?:([+-]|)"+S+"*(\\d+)|))"+S+"*\\)|)","i")},F=new RegExp(M),q=RegExp("\\\\[\\da-fA-F]{1,6}"+S+"?|\\\\([^\\r\\n\\f])","g"),W=function(e,t){var n="0x"+e.slice(1)-65536;return t||(n<0?String.fromCharCode(n+65536):String.fromCharCode(n>>10|55296,1023&n|56320))};function U(e){return e.replace(q,W)}function z(e){T.error("Syntax error, unrecognized expression: "+e)}var X=RegExp("^"+S+"*,"+S+"*"),_=I();function V(e,t){var n,r,i,o,a,s,u,l=_[e+" "];if(l)return t?0:l.slice(0);a=e,s=[],u=T.expr.preFilter;while(a){for(o in(!n||(r=X.exec(a)))&&(r&&(a=a.slice(r[0].length)||a),s.push(i=[])),n=!1,(r=O.exec(a))&&(n=r.shift(),i.push({value:n,type:r[0].replace(N," ")}),a=a.slice(n.length)),B)(r=T.expr.match[o].exec(a))&&(!u[o]||(r=u[o](r)))&&(n=r.shift(),i.push({value:n,type:o,matches:r}),a=a.slice(n.length));if(!n)break}return t?a.length:a?z(e):_(e,s).slice(0)}function Y(e){for(var t=0,n=e.length,r="";t<n;t++)r+=e[t].value;return r}function Q(e,t,n,r,i,o,a){var s=0,u=e.length,l=null==n;if("object"===h(n))for(s in i=!0,n)Q(e,t,s,n[s],!0,o,a);else if(void 0!==r&&(i=!0,"function"!=typeof r&&(a=!0),l&&(a?(t.call(e,r),t=null):(l=t,t=function(e,t,n){return l.call(T(e),n)})),t))for(;s<u;s++)t(e[s],n,a?r:r.call(e[s],s,t(e[s],n)));return i?e:l?t.call(e):u?t(e[0],n):o}var G=/[^\x20\t\r\n\f]+/g;T.fn.extend({attr:function(e,t){return Q(this,T.attr,e,t,arguments.length>1)},removeAttr:function(e){return this.each(function(){T.removeAttr(this,e)})}}),T.extend({attr:function(e,t,n){var r,i,o=e.nodeType;if(3!==o&&8!==o&&2!==o)return void 0===e.getAttribute?T.prop(e,t,n):(1===o&&T.isXMLDoc(e)||(i=T.attrHooks[t.toLowerCase()]),void 0!==n)?null===n||!1===n&&0!==t.toLowerCase().indexOf("aria-")?void T.removeAttr(e,t):i&&"set"in i&&void 0!==(r=i.set(e,n,t))?r:(e.setAttribute(t,n),n):i&&"get"in i&&null!==(r=i.get(e,t))?r:null==(r=e.getAttribute(t))?void 0:r},attrHooks:{},removeAttr:function(e,t){var n,r=0,i=t&&t.match(G);if(i&&1===e.nodeType)while(n=i[r++])e.removeAttribute(n)}}),D&&(T.attrHooks.type={set:function(e,t){if("radio"===t&&C(e,"input")){var n=e.value;return e.setAttribute("type",t),n&&(e.value=n),t}}});var K=/([\0-\x1f\x7f]|^-?\d)|^-$|[^\x80-\uFFFF\w-]/g;function J(e,t){return t?"\0"===e?"\uFFFD":e.slice(0,-1)+"\\"+e.charCodeAt(e.length-1).toString(16)+" ":"\\"+e}T.escapeSelector=function(e){return(e+"").replace(K,J)};var Z=n.sort,ee=n.splice;function et(e,t){if(e===t)return en=!0,0;var n=!e.compareDocumentPosition-!t.compareDocumentPosition;return n?n:1&(n=(e.ownerDocument||e)==(t.ownerDocument||t)?e.compareDocumentPosition(t):1)?e==y||e.ownerDocument==y&&T.contains(y,e)?-1:t==y||t.ownerDocument==y&&T.contains(y,t)?1:0:4&n?-1:1}T.uniqueSort=function(e){var t,n=[],r=0,i=0;if(en=!1,Z.call(e,et),en){while(t=e[i++])t===e[i]&&(r=n.push(i));while(r--)ee.call(e,n[r],1)}return e},T.fn.uniqueSort=function(){return this.pushStack(T.uniqueSort(i.apply(this)))};var en,er,ei,eo,ea,es,eu=0,el=0,ec=I(),ef=I(),ep=I(),ed=RegExp(S+"+","g"),eh=RegExp("^"+k+"$"),eg=T.extend({needsContext:RegExp("^"+S+"*[>+~]|:(even|odd|eq|gt|lt|nth|first|last)(?:\\("+S+"*((?:-\\d)?\\d*)"+S+"*\\)|)(?=[^-]|$)","i")},B),ev=/^(?:input|select|textarea|button)$/i,ey=/^h\d$/i,em=/^(?:#([\w-]+)|(\w+)|\.([\w-]+))$/,ex=function(){eS()},eb=eA(function(e){return!0===e.disabled&&C(e,"fieldset")},{dir:"parentNode",next:"legend"});function ew(e,t,n,r){var i,o,s,u,l,c,f,p=t&&t.ownerDocument,d=t?t.nodeType:9;if(n=n||[],"string"!=typeof e||!e||1!==d&&9!==d&&11!==d)return n;if(!r&&(eS(t),t=t||eo,es)){if(11!==d&&(l=em.exec(e))){if(i=l[1]){if(9===d)return(s=t.getElementById(i))&&a.call(n,s),n;else if(p&&(s=p.getElementById(i))&&T.contains(t,s))return a.call(n,s),n}else if(l[2])return a.apply(n,t.getElementsByTagName(e)),n;else if((i=l[3])&&t.getElementsByClassName)return a.apply(n,t.getElementsByClassName(i)),n}if(!ep[e+" "]&&(!A||!A.test(e))){if(f=e,p=t,1===d&&(j.test(e)||O.test(e))){((p=L.test(e)&&H(t.parentNode)||t)!=t||D)&&((u=t.getAttribute("id"))?u=T.escapeSelector(u):t.setAttribute("id",u=T.expando)),o=(c=V(e)).length;while(o--)c[o]=(u?"#"+u:":scope")+" "+Y(c[o]);f=c.join(",")}try{return a.apply(n,p.querySelectorAll(f)),n}catch(t){ep(e,!0)}finally{u===T.expando&&t.removeAttribute("id")}}}return ej(e.replace(N,"$1"),t,n,r)}function eT(e){return e[T.expando]=!0,e}function eC(e){return function(t){if("form"in t){if(t.parentNode&&!1===t.disabled){if("label"in t)if("label"in t.parentNode)return t.parentNode.disabled===e;else return t.disabled===e;return t.isDisabled===e||!e!==t.isDisabled&&eb(t)===e}return t.disabled===e}return"label"in t&&t.disabled===e}}function eE(e){return eT(function(t){return t*=1,eT(function(n,r){var i,o=e([],n.length,t),a=o.length;while(a--)n[i=o[a]]&&(n[i]=!(r[i]=n[i]))})})}function eS(e){var t,n=e?e.ownerDocument||e:y;n!=eo&&9===n.nodeType&&(ea=(eo=n).documentElement,es=!T.isXMLDoc(eo),D&&y!=eo&&(t=eo.defaultView)&&t.top!==t&&t.addEventListener("unload",ex))}for(er in ew.matches=function(e,t){return ew(e,null,null,t)},ew.matchesSelector=function(e,t){if(eS(e),es&&!ep[t+" "]&&(!A||!A.test(t)))try{return R.call(e,t)}catch(e){ep(t,!0)}return ew(t,eo,null,[e]).length>0},T.expr={cacheLength:50,createPseudo:eT,match:eg,find:{ID:function(e,t){if(void 0!==t.getElementById&&es){var n=t.getElementById(e);return n?[n]:[]}},TAG:function(e,t){return void 0!==t.getElementsByTagName?t.getElementsByTagName(e):t.querySelectorAll(e)},CLASS:function(e,t){if(void 0!==t.getElementsByClassName&&es)return t.getElementsByClassName(e)}},relative:{">":{dir:"parentNode",first:!0}," ":{dir:"parentNode"},"+":{dir:"previousSibling",first:!0},"~":{dir:"previousSibling"}},preFilter:{ATTR:function(e){return e[1]=U(e[1]),e[3]=U(e[3]||e[4]||e[5]||""),"~="===e[2]&&(e[3]=" "+e[3]+" "),e.slice(0,4)},CHILD:function(e){return e[1]=e[1].toLowerCase(),"nth"===e[1].slice(0,3)?(e[3]||z(e[0]),e[4]=+(e[4]?e[5]+(e[6]||1):2*("even"===e[3]||"odd"===e[3])),e[5]=+(e[7]+e[8]||"odd"===e[3])):e[3]&&z(e[0]),e},PSEUDO:function(e){var t,n=!e[6]&&e[2];return B.CHILD.test(e[0])?null:(e[3]?e[2]=e[4]||e[5]||"":n&&F.test(n)&&(t=V(n,!0))&&(t=n.indexOf(")",n.length-t)-n.length)&&(e[0]=e[0].slice(0,t),e[2]=n.slice(0,t)),e.slice(0,3))}},filter:{ID:function(e){var t=U(e);return function(e){return e.getAttribute("id")===t}},TAG:function(e){var t=U(e).toLowerCase();return"*"===e?function(){return!0}:function(e){return C(e,t)}},CLASS:function(e){var t=ec[e+" "];return t||(t=RegExp("(^|"+S+")"+e+"("+S+"|$)"))&&ec(e,function(e){return t.test("string"==typeof e.className&&e.className||void 0!==e.getAttribute&&e.getAttribute("class")||"")})},ATTR:function(e,t,n){return function(r){var i=T.attr(r,e);return null==i?"!="===t:!t||((i+="","="===t)?i===n:"!="===t?i!==n:"^="===t?n&&0===i.indexOf(n):"*="===t?n&&i.indexOf(n)>-1:"$="===t?n&&i.slice(-n.length)===n:"~="===t?(" "+i.replace(ed," ")+" ").indexOf(n)>-1:"|="===t&&(i===n||i.slice(0,n.length+1)===n+"-"))}},CHILD:function(e,t,n,r,i){var o="nth"!==e.slice(0,3),a="last"!==e.slice(-4),s="of-type"===t;return 1===r&&0===i?function(e){return!!e.parentNode}:function(t,n,u){var l,c,f,p,d,h=o!==a?"nextSibling":"previousSibling",g=t.parentNode,v=s&&t.nodeName.toLowerCase(),y=!u&&!s,m=!1;if(g){if(o){while(h){f=t;while(f=f[h])if(s?C(f,v):1===f.nodeType)return!1;d=h="only"===e&&!d&&"nextSibling"}return!0}if(d=[a?g.firstChild:g.lastChild],a&&y){m=(p=(l=(c=g[T.expando]||(g[T.expando]={}))[e]||[])[0]===eu&&l[1])&&l[2],f=p&&g.childNodes[p];while(f=++p&&f&&f[h]||(m=p=0)||d.pop())if(1===f.nodeType&&++m&&f===t){c[e]=[eu,p,m];break}}else if(y&&(m=p=(l=(c=t[T.expando]||(t[T.expando]={}))[e]||[])[0]===eu&&l[1]),!1===m){while(f=++p&&f&&f[h]||(m=p=0)||d.pop())if((s?C(f,v):1===f.nodeType)&&++m&&(y&&((c=f[T.expando]||(f[T.expando]={}))[e]=[eu,m]),f===t))break}return(m-=i)===r||m%r==0&&m/r>=0}}},PSEUDO:function(e,t){var n=T.expr.pseudos[e]||T.expr.setFilters[e.toLowerCase()]||z("unsupported pseudo: "+e);return n[T.expando]?n(t):n}},pseudos:{not:eT(function(e){var t=[],n=[],r=eO(e.replace(N,"$1"));return r[T.expando]?eT(function(e,t,n,i){var o,a=r(e,null,i,[]),s=e.length;while(s--)(o=a[s])&&(e[s]=!(t[s]=o))}):function(e,i,o){return t[0]=e,r(t,null,o,n),t[0]=null,!n.pop()}}),has:eT(function(e){return function(t){return ew(e,t).length>0}}),contains:eT(function(e){return e=U(e),function(t){return(t.textContent||T.text(t)).indexOf(e)>-1}}),lang:eT(function(e){return eh.test(e||"")||z("unsupported lang: "+e),e=U(e).toLowerCase(),function(t){var n;do if(n=es?t.lang:t.getAttribute("xml:lang")||t.getAttribute("lang"))return(n=n.toLowerCase())===e||0===n.indexOf(e+"-");while((t=t.parentNode)&&1===t.nodeType);return!1}}),target:function(t){var n=e.location&&e.location.hash;return n&&n.slice(1)===t.id},root:function(e){return e===ea},focus:function(e){return e===eo.activeElement&&eo.hasFocus()&&!!(e.type||e.href||~e.tabIndex)},enabled:eC(!1),disabled:eC(!0),checked:function(e){return C(e,"input")&&!!e.checked||C(e,"option")&&!!e.selected},selected:function(e){return D&&e.parentNode&&e.parentNode.selectedIndex,!0===e.selected},empty:function(e){for(e=e.firstChild;e;e=e.nextSibling)if(e.nodeType<6)return!1;return!0},parent:function(e){return!T.expr.pseudos.empty(e)},header:function(e){return ey.test(e.nodeName)},input:function(e){return ev.test(e.nodeName)},button:function(e){return C(e,"input")&&"button"===e.type||C(e,"button")},text:function(e){return C(e,"input")&&"text"===e.type},first:eE(function(){return[0]}),last:eE(function(e,t){return[t-1]}),eq:eE(function(e,t,n){return[n<0?n+t:n]}),even:eE(function(e,t){for(var n=0;n<t;n+=2)e.push(n);return e}),odd:eE(function(e,t){for(var n=1;n<t;n+=2)e.push(n);return e}),lt:eE(function(e,t,n){var r;for(r=n<0?n+t:n>t?t:n;--r>=0;)e.push(r);return e}),gt:eE(function(e,t,n){for(var r=n<0?n+t:n;++r<t;)e.push(r);return e})}},T.expr.pseudos.nth=T.expr.pseudos.eq,{radio:!0,checkbox:!0,file:!0,password:!0,image:!0})T.expr.pseudos[er]=function(e){return function(t){return C(t,"input")&&t.type===e}}(er);for(er in{submit:!0,reset:!0})T.expr.pseudos[er]=function(e){return function(t){return(C(t,"input")||C(t,"button"))&&t.type===e}}(er);function eD(){}function eA(e,t,n){var r=t.dir,i=t.next,o=i||r,a=n&&"parentNode"===o,s=el++;return t.first?function(t,n,i){while(t=t[r])if(1===t.nodeType||a)return e(t,n,i);return!1}:function(t,n,u){var l,c,f=[eu,s];if(u){while(t=t[r])if((1===t.nodeType||a)&&e(t,n,u))return!0}else while(t=t[r])if(1===t.nodeType||a)if(c=t[T.expando]||(t[T.expando]={}),i&&C(t,i))t=t[r]||t;else{if((l=c[o])&&l[0]===eu&&l[1]===s)return f[2]=l[2];if(c[o]=f,f[2]=e(t,n,u))return!0}return!1}}function eN(e){return e.length>1?function(t,n,r){var i=e.length;while(i--)if(!e[i](t,n,r))return!1;return!0}:e[0]}function ek(e,t,n,r,i){for(var o,a=[],s=0,u=e.length,l=null!=t;s<u;s++)(o=e[s])&&(!n||n(o,r,i))&&(a.push(o),l&&t.push(s));return a}function eO(e,t){var n,r,i,o,u=[],l=[],c=ef[e+" "];if(!c){t||(t=V(e)),o=t.length;while(o--)(c=function e(t){for(var n,r,i,o=t.length,u=T.expr.relative[t[0].type],l=u||T.expr.relative[" "],c=+!!u,f=eA(function(e){return e===n},l,!0),p=eA(function(e){return s.call(n,e)>-1},l,!0),d=[function(e,t,r){var i=!u&&(r||t!=ei)||((n=t).nodeType?f(e,t,r):p(e,t,r));return n=null,i}];c<o;c++)if(r=T.expr.relative[t[c].type])d=[eA(eN(d),r)];else{if((r=T.expr.filter[t[c].type].apply(null,t[c].matches))[T.expando]){for(i=++c;i<o&&!T.expr.relative[t[i].type];i++);return function e(t,n,r,i,o,u){return i&&!i[T.expando]&&(i=e(i)),o&&!o[T.expando]&&(o=e(o,u)),eT(function(e,u,l,c){var f,p,d,h,g=[],v=[],y=u.length,m=e||function(e,t,n){for(var r=0,i=t.length;r<i;r++)ew(e,t[r],n);return n}(n||"*",l.nodeType?[l]:l,[]),x=t&&(e||!n)?ek(m,g,t,l,c):m;if(r?r(x,h=o||(e?t:y||i)?[]:u,l,c):h=x,i){f=ek(h,v),i(f,[],l,c),p=f.length;while(p--)(d=f[p])&&(h[v[p]]=!(x[v[p]]=d))}if(e){if(o||t){if(o){f=[],p=h.length;while(p--)(d=h[p])&&f.push(x[p]=d);o(null,h=[],f,c)}p=h.length;while(p--)(d=h[p])&&(f=o?s.call(e,d):g[p])>-1&&(e[f]=!(u[f]=d))}}else h=ek(h===u?h.splice(y,h.length):h),o?o(null,u,h,c):a.apply(u,h)})}(c>1&&eN(d),c>1&&Y(t.slice(0,c-1).concat({value:" "===t[c-2].type?"*":""})).replace(N,"$1"),r,c<i&&e(t.slice(c,i)),i<o&&e(t=t.slice(i)),i<o&&Y(t))}d.push(r)}return eN(d)}(t[o]))[T.expando]?u.push(c):l.push(c);(c=ef(e,(n=u.length>0,r=l.length>0,i=function(e,t,i,o,s){var c,f,p,d=0,h="0",g=e&&[],v=[],y=ei,m=e||r&&T.expr.find.TAG("*",s),x=eu+=null==y?1:Math.random()||.1;for(s&&(ei=t==eo||t||s);null!=(c=m[h]);h++){if(r&&c){f=0,t||c.ownerDocument==eo||(eS(c),i=!es);while(p=l[f++])if(p(c,t||eo,i)){a.call(o,c);break}s&&(eu=x)}n&&((c=!p&&c)&&d--,e&&g.push(c))}if(d+=h,n&&h!==d){f=0;while(p=u[f++])p(g,v,t,i);if(e){if(d>0)while(h--)g[h]||v[h]||(v[h]=E.call(o));v=ek(v)}a.apply(o,v),s&&!e&&v.length>0&&d+u.length>1&&T.uniqueSort(o)}return s&&(eu=x,ei=y),g},n?eT(i):i))).selector=e}return c}function ej(e,t,n,r){var i,o,s,u,l,c="function"==typeof e&&e,f=!r&&V(e=c.selector||e);if(n=n||[],1===f.length){if((o=f[0]=f[0].slice(0)).length>2&&"ID"===(s=o[0]).type&&9===t.nodeType&&es&&T.expr.relative[o[1].type]){if(!(t=(T.expr.find.ID(U(s.matches[0]),t)||[])[0]))return n;c&&(t=t.parentNode),e=e.slice(o.shift().value.length)}i=eg.needsContext.test(e)?0:o.length;while(i--){if(s=o[i],T.expr.relative[u=s.type])break;if((l=T.expr.find[u])&&(r=l(U(s.matches[0]),L.test(o[0].type)&&H(t.parentNode)||t))){if(o.splice(i,1),!(e=r.length&&Y(o)))return a.apply(n,r),n;break}}}return(c||eO(e,f))(r,t,!es,n,!t||L.test(e)&&H(t.parentNode)||t),n}function eL(e,t,n){var r=[],i=void 0!==n;while((e=e[t])&&9!==e.nodeType)if(1===e.nodeType){if(i&&T(e).is(n))break;r.push(e)}return r}function eP(e,t){for(var n=[];e;e=e.nextSibling)1===e.nodeType&&e!==t&&n.push(e);return n}eD.prototype=T.expr.pseudos,T.expr.setFilters=new eD,eS(),T.find=ew,ew.compile=eO,ew.select=ej,ew.setDocument=eS,ew.tokenize=V;var eR=T.expr.match.needsContext,eI=/^<([a-z][^\/\0>:\x20\t\r\n\f]*)[\x20\t\r\n\f]*\/?>(?:<\/\1>|)$/i;function eH(e){return"<"===e[0]&&">"===e[e.length-1]&&e.length>=3}function e$(e,t,n){return"function"==typeof t?T.grep(e,function(e,r){return!!t.call(e,r,e)!==n}):t.nodeType?T.grep(e,function(e){return e===t!==n}):"string"!=typeof t?T.grep(e,function(e){return s.call(t,e)>-1!==n}):T.filter(t,e,n)}T.filter=function(e,t,n){var r=t[0];return(n&&(e=":not("+e+")"),1===t.length&&1===r.nodeType)?T.find.matchesSelector(r,e)?[r]:[]:T.find.matches(e,T.grep(t,function(e){return 1===e.nodeType}))},T.fn.extend({find:function(e){var t,n,r=this.length,i=this;if("string"!=typeof e)return this.pushStack(T(e).filter(function(){for(t=0;t<r;t++)if(T.contains(i[t],this))return!0}));for(t=0,n=this.pushStack([]);t<r;t++)T.find(e,i[t],n);return r>1?T.uniqueSort(n):n},filter:function(e){return this.pushStack(e$(this,e||[],!1))},not:function(e){return this.pushStack(e$(this,e||[],!0))},is:function(e){return!!e$(this,"string"==typeof e&&eR.test(e)?T(e):e||[],!1).length}});var eM,eB=/^(?:\s*(<[\w\W]+>)[^>]*|#([\w-]+))$/;(T.fn.init=function(e,t){var n,r;if(!e)return this;if(e.nodeType)return this[0]=e,this.length=1,this;if("function"==typeof e)return void 0!==eM.ready?eM.ready(e):e(T);if(eH(n=e+""))n=[null,e,null];else{if("string"!=typeof e)return T.makeArray(e,this);n=eB.exec(e)}if(n&&(n[1]||!t))if(!n[1])return(r=y.getElementById(n[2]))&&(this[0]=r,this.length=1),this;else{if(t=t instanceof T?t[0]:t,T.merge(this,T.parseHTML(n[1],t&&t.nodeType?t.ownerDocument||t:y,!0)),eI.test(n[1])&&T.isPlainObject(t))for(n in t)"function"==typeof this[n]?this[n](t[n]):this.attr(n,t[n]);return this}return!t||t.jquery?(t||eM).find(e):this.constructor(t).find(e)}).prototype=T.fn,eM=T(y);var eF=/^(?:parents|prev(?:Until|All))/,eq={children:!0,contents:!0,next:!0,prev:!0};function eW(e,t){while((e=e[t])&&1!==e.nodeType);return e}T.fn.extend({has:function(e){var t=T(e,this),n=t.length;return this.filter(function(){for(var e=0;e<n;e++)if(T.contains(this,t[e]))return!0})},closest:function(e,t){var n,r=0,i=this.length,o=[],a="string"!=typeof e&&T(e);if(!eR.test(e)){for(;r<i;r++)for(n=this[r];n&&n!==t;n=n.parentNode)if(n.nodeType<11&&(a?a.index(n)>-1:1===n.nodeType&&T.find.matchesSelector(n,e))){o.push(n);break}}return this.pushStack(o.length>1?T.uniqueSort(o):o)},index:function(e){return e?"string"==typeof e?s.call(T(e),this[0]):s.call(this,e.jquery?e[0]:e):this[0]&&this[0].parentNode?this.first().prevAll().length:-1},add:function(e,t){return this.pushStack(T.uniqueSort(T.merge(this.get(),T(e,t))))},addBack:function(e){return this.add(null==e?this.prevObject:this.prevObject.filter(e))}}),T.each({parent:function(e){var t=e.parentNode;return t&&11!==t.nodeType?t:null},parents:function(e){return eL(e,"parentNode")},parentsUntil:function(e,t,n){return eL(e,"parentNode",n)},next:function(e){return eW(e,"nextSibling")},prev:function(e){return eW(e,"previousSibling")},nextAll:function(e){return eL(e,"nextSibling")},prevAll:function(e){return eL(e,"previousSibling")},nextUntil:function(e,t,n){return eL(e,"nextSibling",n)},prevUntil:function(e,t,n){return eL(e,"previousSibling",n)},siblings:function(e){return eP((e.parentNode||{}).firstChild,e)},children:function(e){return eP(e.firstChild)},contents:function(e){return null!=e.contentDocument&&r(e.contentDocument)?e.contentDocument:(C(e,"template")&&(e=e.content||e),T.merge([],e.childNodes))}},function(e,t){T.fn[e]=function(n,r){var i=T.map(this,t,n);return"Until"!==e.slice(-5)&&(r=n),r&&"string"==typeof r&&(i=T.filter(r,i)),this.length>1&&(eq[e]||T.uniqueSort(i),eF.test(e)&&i.reverse()),this.pushStack(i)}});var eU=/-([a-z])/g;function ez(e,t){return t.toUpperCase()}function eX(e){return e.replace(eU,ez)}function e_(e){return 1===e.nodeType||9===e.nodeType||!+e.nodeType}function eV(){this.expando=T.expando+eV.uid++}eV.uid=1,eV.prototype={cache:function(e){var t=e[this.expando];return!t&&(t=Object.create(null),e_(e)&&(e.nodeType?e[this.expando]=t:Object.defineProperty(e,this.expando,{value:t,configurable:!0}))),t},set:function(e,t,n){var r,i=this.cache(e);if("string"==typeof t)i[eX(t)]=n;else for(r in t)i[eX(r)]=t[r];return n},get:function(e,t){return void 0===t?this.cache(e):e[this.expando]&&e[this.expando][eX(t)]},access:function(e,t,n){return void 0===t||t&&"string"==typeof t&&void 0===n?this.get(e,t):(this.set(e,t,n),void 0!==n?n:t)},remove:function(e,t){var n,r=e[this.expando];if(void 0!==r){if(void 0!==t){n=(t=Array.isArray(t)?t.map(eX):(t=eX(t))in r?[t]:t.match(G)||[]).length;while(n--)delete r[t[n]]}(void 0===t||T.isEmptyObject(r))&&(e.nodeType?e[this.expando]=void 0:delete e[this.expando])}},hasData:function(e){var t=e[this.expando];return void 0!==t&&!T.isEmptyObject(t)}};var eY=new eV,eQ=new eV,eG=/^(?:\{[\w\W]*\}|\[[\w\W]*\])$/,eK=/[A-Z]/g;function eJ(e,t,n){var r,i;if(void 0===n&&1===e.nodeType)if(r="data-"+t.replace(eK,"-$&").toLowerCase(),"string"==typeof(n=e.getAttribute(r))){try{i=n,n="true"===i||"false"!==i&&("null"===i?null:i===+i+""?+i:eG.test(i)?JSON.parse(i):i)}catch(e){}eQ.set(e,t,n)}else n=void 0;return n}T.extend({hasData:function(e){return eQ.hasData(e)||eY.hasData(e)},data:function(e,t,n){return eQ.access(e,t,n)},removeData:function(e,t){eQ.remove(e,t)},_data:function(e,t,n){return eY.access(e,t,n)},_removeData:function(e,t){eY.remove(e,t)}}),T.fn.extend({data:function(e,t){var n,r,i,o=this[0],a=o&&o.attributes;if(void 0===e){if(this.length&&(i=eQ.get(o),1===o.nodeType&&!eY.get(o,"hasDataAttrs"))){n=a.length;while(n--)a[n]&&0===(r=a[n].name).indexOf("data-")&&eJ(o,r=eX(r.slice(5)),i[r]);eY.set(o,"hasDataAttrs",!0)}return i}return"object"==typeof e?this.each(function(){eQ.set(this,e)}):Q(this,function(t){var n;if(o&&void 0===t)return void 0!==(n=eQ.get(o,e))||void 0!==(n=eJ(o,e))?n:void 0;this.each(function(){eQ.set(this,e,t)})},null,t,arguments.length>1,null,!0)},removeData:function(e){return this.each(function(){eQ.remove(this,e)})}});var eZ=/^(?:input|select|textarea|button)$/i,e0=/^(?:a|area)$/i;function e1(e){return(e.match(G)||[]).join(" ")}function e2(e){return e.getAttribute&&e.getAttribute("class")||""}function e3(e){return Array.isArray(e)?e:"string"==typeof e&&e.match(G)||[]}T.fn.extend({prop:function(e,t){return Q(this,T.prop,e,t,arguments.length>1)},removeProp:function(e){return this.each(function(){delete this[T.propFix[e]||e]})}}),T.extend({prop:function(e,t,n){var r,i,o=e.nodeType;if(3!==o&&8!==o&&2!==o)return(1===o&&T.isXMLDoc(e)||(t=T.propFix[t]||t,i=T.propHooks[t]),void 0!==n)?i&&"set"in i&&void 0!==(r=i.set(e,n,t))?r:e[t]=n:i&&"get"in i&&null!==(r=i.get(e,t))?r:e[t]},propHooks:{tabIndex:{get:function(e){var t=e.getAttribute("tabindex");return t?parseInt(t,10):eZ.test(e.nodeName)||e0.test(e.nodeName)&&e.href?0:-1}}},propFix:{for:"htmlFor",class:"className"}}),D&&(T.propHooks.selected={get:function(e){var t=e.parentNode;return t&&t.parentNode&&t.parentNode.selectedIndex,null},set:function(e){var t=e.parentNode;t&&(t.selectedIndex,t.parentNode&&t.parentNode.selectedIndex)}}),T.each(["tabIndex","readOnly","maxLength","cellSpacing","cellPadding","rowSpan","colSpan","useMap","frameBorder","contentEditable"],function(){T.propFix[this.toLowerCase()]=this}),T.fn.extend({addClass:function(e){var t,n,r,i,o,a;return"function"==typeof e?this.each(function(t){T(this).addClass(e.call(this,t,e2(this)))}):(t=e3(e)).length?this.each(function(){if(r=e2(this),n=1===this.nodeType&&" "+e1(r)+" "){for(o=0;o<t.length;o++)i=t[o],0>n.indexOf(" "+i+" ")&&(n+=i+" ");r!==(a=e1(n))&&this.setAttribute("class",a)}}):this},removeClass:function(e){var t,n,r,i,o,a;return"function"==typeof e?this.each(function(t){T(this).removeClass(e.call(this,t,e2(this)))}):arguments.length?(t=e3(e)).length?this.each(function(){if(r=e2(this),n=1===this.nodeType&&" "+e1(r)+" "){for(o=0;o<t.length;o++){i=t[o];while(n.indexOf(" "+i+" ")>-1)n=n.replace(" "+i+" "," ")}r!==(a=e1(n))&&this.setAttribute("class",a)}}):this:this.attr("class","")},toggleClass:function(e,t){var n,r,i,o;return"function"==typeof e?this.each(function(n){T(this).toggleClass(e.call(this,n,e2(this),t),t)}):"boolean"==typeof t?t?this.addClass(e):this.removeClass(e):(n=e3(e)).length?this.each(function(){for(i=0,o=T(this);i<n.length;i++)r=n[i],o.hasClass(r)?o.removeClass(r):o.addClass(r)}):this},hasClass:function(e){var t,n,r=0;t=" "+e+" ";while(n=this[r++])if(1===n.nodeType&&(" "+e1(e2(n))+" ").indexOf(t)>-1)return!0;return!1}}),T.fn.extend({val:function(e){var t,n,r,i=this[0];if(!arguments.length)return i?(t=T.valHooks[i.type]||T.valHooks[i.nodeName.toLowerCase()])&&"get"in t&&void 0!==(n=t.get(i,"value"))?n:null==(n=i.value)?"":n:void 0;return r="function"==typeof e,this.each(function(n){var i;1===this.nodeType&&(null==(i=r?e.call(this,n,T(this).val()):e)?i="":"number"==typeof i?i+="":Array.isArray(i)&&(i=T.map(i,function(e){return null==e?"":e+""})),(t=T.valHooks[this.type]||T.valHooks[this.nodeName.toLowerCase()])&&"set"in t&&void 0!==t.set(this,i,"value")||(this.value=i))})}}),T.extend({valHooks:{select:{get:function(e){var t,n,r,i=e.options,o=e.selectedIndex,a="select-one"===e.type,s=a?null:[],u=a?o+1:i.length;for(r=o<0?u:a?o:0;r<u;r++)if((n=i[r]).selected&&!n.disabled&&(!n.parentNode.disabled||!C(n.parentNode,"optgroup"))){if(t=T(n).val(),a)return t;s.push(t)}return s},set:function(e,t){var n,r,i=e.options,o=T.makeArray(t),a=i.length;while(a--)((r=i[a]).selected=T.inArray(T(r).val(),o)>-1)&&(n=!0);return n||(e.selectedIndex=-1),o}}}}),D&&(T.valHooks.option={get:function(e){var t=e.getAttribute("value");return null!=t?t:e1(T.text(e))}}),T.each(["radio","checkbox"],function(){T.valHooks[this]={set:function(e,t){if(Array.isArray(t))return e.checked=T.inArray(T(e).val(),t)>-1}}});var e5=/^(?:checkbox|radio)$/i,e9=/^([^.]*)(?:\.(.+)|)/;function e6(){return!0}function e4(){return!1}function e8(e,t,n,r,i,o){var a,s;if("object"==typeof t){for(s in"string"!=typeof n&&(r=r||n,n=void 0),t)e8(e,s,n,r,t[s],o);return e}if(null==r&&null==i?(i=n,r=n=void 0):null==i&&("string"==typeof n?(i=r,r=void 0):(i=r,r=n,n=void 0)),!1===i)i=e4;else if(!i)return e;return 1===o&&(a=i,(i=function(e){return T().off(e),a.apply(this,arguments)}).guid=a.guid||(a.guid=T.guid++)),e.each(function(){T.event.add(this,t,i,r,n)})}function e7(e,t,n){if(!n){void 0===eY.get(e,t)&&T.event.add(e,t,e6);return}eY.set(e,t,!1),T.event.add(e,t,{namespace:!1,handler:function(e){var n,r=eY.get(this,t);if(1&e.isTrigger&&this[t]){if(r.length)(T.event.special[t]||{}).delegateType&&e.stopPropagation();else if(r=i.call(arguments),eY.set(this,t,r),this[t](),n=eY.get(this,t),eY.set(this,t,!1),r!==n)return e.stopImmediatePropagation(),e.preventDefault(),n&&n.value}else r.length&&(eY.set(this,t,{value:T.event.trigger(r[0],r.slice(1),this)}),e.stopPropagation(),e.isImmediatePropagationStopped=e6)}})}T.event={add:function(e,t,n,r,i){var o,a,s,u,l,c,f,p,d,h,g,v=eY.get(e);if(e_(e)){n.handler&&(n=(o=n).handler,i=o.selector),i&&T.find.matchesSelector(P,i),n.guid||(n.guid=T.guid++),(u=v.events)||(u=v.events=Object.create(null)),(a=v.handle)||(a=v.handle=function(t){return T.event.triggered!==t.type?T.event.dispatch.apply(e,arguments):void 0}),l=(t=(t||"").match(G)||[""]).length;while(l--){if(d=g=(s=e9.exec(t[l])||[])[1],h=(s[2]||"").split(".").sort(),!d)continue;f=T.event.special[d]||{},d=(i?f.delegateType:f.bindType)||d,f=T.event.special[d]||{},c=T.extend({type:d,origType:g,data:r,handler:n,guid:n.guid,selector:i,needsContext:i&&T.expr.match.needsContext.test(i),namespace:h.join(".")},o),(p=u[d])||((p=u[d]=[]).delegateCount=0,(!f.setup||!1===f.setup.call(e,r,h,a))&&e.addEventListener&&e.addEventListener(d,a)),f.add&&(f.add.call(e,c),c.handler.guid||(c.handler.guid=n.guid)),i?p.splice(p.delegateCount++,0,c):p.push(c)}}},remove:function(e,t,n,r,i){var o,a,s,u,l,c,f,p,d,h,g,v=eY.hasData(e)&&eY.get(e);if(v&&(u=v.events)){l=(t=(t||"").match(G)||[""]).length;while(l--){if(d=g=(s=e9.exec(t[l])||[])[1],h=(s[2]||"").split(".").sort(),!d){for(d in u)T.event.remove(e,d+t[l],n,r,!0);continue}f=T.event.special[d]||{},p=u[d=(r?f.delegateType:f.bindType)||d]||[],s=s[2]&&RegExp("(^|\\.)"+h.join("\\.(?:.*\\.|)")+"(\\.|$)"),a=o=p.length;while(o--)c=p[o],(i||g===c.origType)&&(!n||n.guid===c.guid)&&(!s||s.test(c.namespace))&&(!r||r===c.selector||"**"===r&&c.selector)&&(p.splice(o,1),c.selector&&p.delegateCount--,f.remove&&f.remove.call(e,c));a&&!p.length&&(f.teardown&&!1!==f.teardown.call(e,h,v.handle)||T.removeEvent(e,d,v.handle),delete u[d])}T.isEmptyObject(u)&&eY.remove(e,"handle events")}},dispatch:function(e){var t,n,r,i,o,a,s=Array(arguments.length),u=T.event.fix(e),l=(eY.get(this,"events")||Object.create(null))[u.type]||[],c=T.event.special[u.type]||{};for(t=1,s[0]=u;t<arguments.length;t++)s[t]=arguments[t];if(u.delegateTarget=this,!c.preDispatch||!1!==c.preDispatch.call(this,u)){a=T.event.handlers.call(this,u,l),t=0;while((i=a[t++])&&!u.isPropagationStopped()){u.currentTarget=i.elem,n=0;while((o=i.handlers[n++])&&!u.isImmediatePropagationStopped())(!u.rnamespace||!1===o.namespace||u.rnamespace.test(o.namespace))&&(u.handleObj=o,u.data=o.data,void 0!==(r=((T.event.special[o.origType]||{}).handle||o.handler).apply(i.elem,s))&&!1===(u.result=r)&&(u.preventDefault(),u.stopPropagation()))}return c.postDispatch&&c.postDispatch.call(this,u),u.result}},handlers:function(e,t){var n,r,i,o,a,s=[],u=t.delegateCount,l=e.target;if(u&&!("click"===e.type&&e.button>=1)){for(;l!==this;l=l.parentNode||this)if(1===l.nodeType&&("click"!==e.type||!0!==l.disabled)){for(n=0,o=[],a={};n<u;n++)void 0===a[i=(r=t[n]).selector+" "]&&(a[i]=r.needsContext?T(i,this).index(l)>-1:T.find(i,this,null,[l]).length),a[i]&&o.push(r);o.length&&s.push({elem:l,handlers:o})}}return l=this,u<t.length&&s.push({elem:l,handlers:t.slice(u)}),s},addProp:function(e,t){Object.defineProperty(T.Event.prototype,e,{enumerable:!0,configurable:!0,get:"function"==typeof t?function(){if(this.originalEvent)return t(this.originalEvent)}:function(){if(this.originalEvent)return this.originalEvent[e]},set:function(t){Object.defineProperty(this,e,{enumerable:!0,configurable:!0,writable:!0,value:t})}})},fix:function(e){return e[T.expando]?e:new T.Event(e)},special:T.extend(Object.create(null),{load:{noBubble:!0},click:{setup:function(e){var t=this||e;return e5.test(t.type)&&t.click&&C(t,"input")&&e7(t,"click",!0),!1},trigger:function(e){var t=this||e;return e5.test(t.type)&&t.click&&C(t,"input")&&e7(t,"click"),!0},_default:function(e){var t=e.target;return e5.test(t.type)&&t.click&&C(t,"input")&&eY.get(t,"click")||C(t,"a")}},beforeunload:{postDispatch:function(e){void 0!==e.result&&e.preventDefault()}}})},T.removeEvent=function(e,t,n){e.removeEventListener&&e.removeEventListener(t,n)},T.Event=function(e,t){if(!(this instanceof T.Event))return new T.Event(e,t);e&&e.type?(this.originalEvent=e,this.type=e.type,this.isDefaultPrevented=e.defaultPrevented?e6:e4,this.target=e.target,this.currentTarget=e.currentTarget,this.relatedTarget=e.relatedTarget):this.type=e,t&&T.extend(this,t),this.timeStamp=e&&e.timeStamp||Date.now(),this[T.expando]=!0},T.Event.prototype={constructor:T.Event,isDefaultPrevented:e4,isPropagationStopped:e4,isImmediatePropagationStopped:e4,isSimulated:!1,preventDefault:function(){var e=this.originalEvent;this.isDefaultPrevented=e6,e&&!this.isSimulated&&e.preventDefault()},stopPropagation:function(){var e=this.originalEvent;this.isPropagationStopped=e6,e&&!this.isSimulated&&e.stopPropagation()},stopImmediatePropagation:function(){var e=this.originalEvent;this.isImmediatePropagationStopped=e6,e&&!this.isSimulated&&e.stopImmediatePropagation(),this.stopPropagation()}},T.each({altKey:!0,bubbles:!0,cancelable:!0,changedTouches:!0,ctrlKey:!0,detail:!0,eventPhase:!0,metaKey:!0,pageX:!0,pageY:!0,shiftKey:!0,view:!0,char:!0,code:!0,charCode:!0,key:!0,keyCode:!0,button:!0,buttons:!0,clientX:!0,clientY:!0,offsetX:!0,offsetY:!0,pointerId:!0,pointerType:!0,screenX:!0,screenY:!0,targetTouches:!0,toElement:!0,touches:!0,which:!0},T.event.addProp),T.each({focus:"focusin",blur:"focusout"},function(e,t){function n(e){var t=T.event.fix(e);t.type="focusin"===e.type?"focus":"blur",t.isSimulated=!0,t.target===t.currentTarget&&eY.get(this,"handle")(t)}T.event.special[e]={setup:function(){if(e7(this,e,!0),!D)return!1;this.addEventListener(t,n)},trigger:function(){return e7(this,e),!0},teardown:function(){if(!D)return!1;this.removeEventListener(t,n)},_default:function(t){return eY.get(t.target,e)},delegateType:t}}),T.each({mouseenter:"mouseover",mouseleave:"mouseout",pointerenter:"pointerover",pointerleave:"pointerout"},function(e,t){T.event.special[e]={delegateType:t,bindType:t,handle:function(e){var n,r=e.relatedTarget,i=e.handleObj;return r&&(r===this||T.contains(this,r))||(e.type=i.origType,n=i.handler.apply(this,arguments),e.type=t),n}}}),T.fn.extend({on:function(e,t,n,r){return e8(this,e,t,n,r)},one:function(e,t,n,r){return e8(this,e,t,n,r,1)},off:function(e,t,n){var r,i;if(e&&e.preventDefault&&e.handleObj)return r=e.handleObj,T(e.delegateTarget).off(r.namespace?r.origType+"."+r.namespace:r.origType,r.selector,r.handler),this;if("object"==typeof e){for(i in e)this.off(i,t,e[i]);return this}return(!1===t||"function"==typeof t)&&(n=t,t=void 0),!1===n&&(n=e4),this.each(function(){T.event.remove(this,e,n,t)})}});var te=/^(?:focusinfocus|focusoutblur)$/,tt=function(e){e.stopPropagation()};T.extend(T.event,{trigger:function(t,n,r,i){var o,a,s,u,l,f,p,d,h=[r||y],v=c.call(t,"type")?t.type:t,m=c.call(t,"namespace")?t.namespace.split("."):[];if((a=d=s=r=r||y,!(3===r.nodeType||8===r.nodeType||te.test(v+T.event.triggered)))&&(v.indexOf(".")>-1&&(v=(m=v.split(".")).shift(),m.sort()),l=0>v.indexOf(":")&&"on"+v,(t=t[T.expando]?t:new T.Event(v,"object"==typeof t&&t)).isTrigger=i?2:3,t.namespace=m.join("."),t.rnamespace=t.namespace?RegExp("(^|\\.)"+m.join("\\.(?:.*\\.|)")+"(\\.|$)"):null,t.result=void 0,t.target||(t.target=r),n=null==n?[t]:T.makeArray(n,[t]),p=T.event.special[v]||{},i||!p.trigger||!1!==p.trigger.apply(r,n))){if(!i&&!p.noBubble&&!g(r)){for(u=p.delegateType||v,!te.test(u+v)&&(a=a.parentNode);a;a=a.parentNode)h.push(a),s=a;s===(r.ownerDocument||y)&&h.push(s.defaultView||s.parentWindow||e)}o=0;while((a=h[o++])&&!t.isPropagationStopped())d=a,t.type=o>1?u:p.bindType||v,(f=(eY.get(a,"events")||Object.create(null))[t.type]&&eY.get(a,"handle"))&&f.apply(a,n),(f=l&&a[l])&&f.apply&&e_(a)&&(t.result=f.apply(a,n),!1===t.result&&t.preventDefault());return t.type=v,!i&&!t.isDefaultPrevented()&&(!p._default||!1===p._default.apply(h.pop(),n))&&e_(r)&&l&&"function"==typeof r[v]&&!g(r)&&((s=r[l])&&(r[l]=null),T.event.triggered=v,t.isPropagationStopped()&&d.addEventListener(v,tt),r[v](),t.isPropagationStopped()&&d.removeEventListener(v,tt),T.event.triggered=void 0,s&&(r[l]=s)),t.result}},simulate:function(e,t,n){var r=T.extend(new T.Event,n,{type:e,isSimulated:!0});T.event.trigger(r,null,t)}}),T.fn.extend({trigger:function(e,t){return this.each(function(){T.event.trigger(e,t,this)})},triggerHandler:function(e,t){var n=this[0];if(n)return T.event.trigger(e,t,n,!0)}});var tn=function(e){return T.contains(e.ownerDocument,e)||e.getRootNode(tr)===e.ownerDocument},tr={composed:!0};P.getRootNode||(tn=function(e){return T.contains(e.ownerDocument,e)});var ti=/<([a-z][^\/\0>\x20\t\r\n\f]*)/i,to={thead:["table"],col:["colgroup","table"],tr:["tbody","table"],td:["tr","tbody","table"]};function ta(e,t){var r;return(r=void 0!==e.getElementsByTagName?n.slice.call(e.getElementsByTagName(t||"*")):void 0!==e.querySelectorAll?e.querySelectorAll(t||"*"):[],void 0===t||t&&C(e,t))?T.merge([e],r):r}to.tbody=to.tfoot=to.colgroup=to.caption=to.thead,to.th=to.td;var ts=/^$|^module$|\/(?:java|ecma)script/i;function tu(e,t){for(var n=0,r=e.length;n<r;n++)eY.set(e[n],"globalEval",!t||eY.get(t[n],"globalEval"))}var tl=/<|&#?\w+;/;function tc(e,t,r,i,o){for(var a,s,u,l,c,f=t.createDocumentFragment(),p=[],d=0,g=e.length;d<g;d++)if((a=e[d])||0===a)if("object"===h(a)&&(a.nodeType||v(a)))T.merge(p,a.nodeType?[a]:a);else if(tl.test(a)){s=s||f.appendChild(t.createElement("div")),c=(u=to[(ti.exec(a)||["",""])[1].toLowerCase()]||n).length;while(--c>-1)s=s.appendChild(t.createElement(u[c]));s.innerHTML=T.htmlPrefilter(a),T.merge(p,s.childNodes),(s=f.firstChild).textContent=""}else p.push(t.createTextNode(a));f.textContent="",d=0;while(a=p[d++]){if(i&&T.inArray(a,i)>-1){o&&o.push(a);continue}if(l=tn(a),s=ta(f.appendChild(a),"script"),l&&tu(s),r){c=0;while(a=s[c++])ts.test(a.type||"")&&r.push(a)}}return f}function tf(e){return e.type=(null!==e.getAttribute("type"))+"/"+e.type,e}function tp(e){return"true/"===(e.type||"").slice(0,5)?e.type=e.type.slice(5):e.removeAttribute("type"),e}function td(e,t,n,r){t=o(t);var i,a,s,u,l,c,f=0,p=e.length,d=p-1,h=t[0];if("function"==typeof h)return e.each(function(i){var o=e.eq(i);t[0]=h.call(this,i,o.html()),td(o,t,n,r)});if(p&&(a=(i=tc(t,e[0].ownerDocument,!1,e,r)).firstChild,1===i.childNodes.length&&(i=a),a||r)){for(u=(s=T.map(ta(i,"script"),tf)).length;f<p;f++)l=i,f!==d&&(l=T.clone(l,!0,!0),u&&T.merge(s,ta(l,"script"))),n.call(e[f],l,f);if(u)for(c=s[s.length-1].ownerDocument,T.map(s,tp),f=0;f<u;f++)l=s[f],ts.test(l.type||"")&&!eY.get(l,"globalEval")&&T.contains(c,l)&&(l.src&&"module"!==(l.type||"").toLowerCase()?T._evalUrl&&!l.noModule&&T._evalUrl(l.src,{nonce:l.nonce,crossOrigin:l.crossOrigin},c):x(l.textContent,l,c))}return e}var th=/<script|<style|<link/i;function tg(e,t){return C(e,"table")&&C(11!==t.nodeType?t:t.firstChild,"tr")&&T(e).children("tbody")[0]||e}function tv(e,t){var n,r,i,o=eY.get(e,"events");if(1===t.nodeType){if(o)for(n in eY.remove(t,"handle events"),o)for(r=0,i=o[n].length;r<i;r++)T.event.add(t,n,o[n][r]);eQ.hasData(e)&&eQ.set(t,T.extend({},eQ.get(e)))}}function ty(e,t,n){for(var r,i=t?T.filter(t,e):e,o=0;null!=(r=i[o]);o++)n||1!==r.nodeType||T.cleanData(ta(r)),r.parentNode&&(n&&tn(r)&&tu(ta(r,"script")),r.parentNode.removeChild(r));return e}T.extend({htmlPrefilter:function(e){return e},clone:function(e,t,n){var r,i,o,a,s=e.cloneNode(!0),u=tn(e);if(D&&(1===e.nodeType||11===e.nodeType)&&!T.isXMLDoc(e))for(r=0,a=ta(s),i=(o=ta(e)).length;r<i;r++)C(a[r],"textarea")&&(a[r].defaultValue=o[r].defaultValue);if(t)if(n)for(r=0,o=o||ta(e),a=a||ta(s),i=o.length;r<i;r++)tv(o[r],a[r]);else tv(e,s);return(a=ta(s,"script")).length>0&&tu(a,!u&&ta(e,"script")),s},cleanData:function(e){for(var t,n,r,i=T.event.special,o=0;void 0!==(n=e[o]);o++)if(e_(n)){if(t=n[eY.expando]){if(t.events)for(r in t.events)i[r]?T.event.remove(n,r):T.removeEvent(n,r,t.handle);n[eY.expando]=void 0}n[eQ.expando]&&(n[eQ.expando]=void 0)}}}),T.fn.extend({detach:function(e){return ty(this,e,!0)},remove:function(e){return ty(this,e)},text:function(e){return Q(this,function(e){return void 0===e?T.text(this):this.empty().each(function(){(1===this.nodeType||11===this.nodeType||9===this.nodeType)&&(this.textContent=e)})},null,e,arguments.length)},append:function(){return td(this,arguments,function(e){(1===this.nodeType||11===this.nodeType||9===this.nodeType)&&tg(this,e).appendChild(e)})},prepend:function(){return td(this,arguments,function(e){if(1===this.nodeType||11===this.nodeType||9===this.nodeType){var t=tg(this,e);t.insertBefore(e,t.firstChild)}})},before:function(){return td(this,arguments,function(e){this.parentNode&&this.parentNode.insertBefore(e,this)})},after:function(){return td(this,arguments,function(e){this.parentNode&&this.parentNode.insertBefore(e,this.nextSibling)})},empty:function(){for(var e,t=0;null!=(e=this[t]);t++)1===e.nodeType&&(T.cleanData(ta(e,!1)),e.textContent="");return this},clone:function(e,t){return e=null!=e&&e,t=null==t?e:t,this.map(function(){return T.clone(this,e,t)})},html:function(e){return Q(this,function(e){var t=this[0]||{},n=0,r=this.length;if(void 0===e&&1===t.nodeType)return t.innerHTML;if("string"==typeof e&&!th.test(e)&&!to[(ti.exec(e)||["",""])[1].toLowerCase()]){e=T.htmlPrefilter(e);try{for(;n<r;n++)t=this[n]||{},1===t.nodeType&&(T.cleanData(ta(t,!1)),t.innerHTML=e);t=0}catch(e){}}t&&this.empty().append(e)},null,e,arguments.length)},replaceWith:function(){var e=[];return td(this,arguments,function(t){var n=this.parentNode;0>T.inArray(this,e)&&(T.cleanData(ta(this)),n&&n.replaceChild(t,this))},e)}}),T.each({appendTo:"append",prependTo:"prepend",insertBefore:"before",insertAfter:"after",replaceAll:"replaceWith"},function(e,t){T.fn[e]=function(e){for(var n,r=[],i=T(e),o=i.length-1,s=0;s<=o;s++)n=s===o?this:this.clone(!0),T(i[s])[t](n),a.apply(r,n);return this.pushStack(r)}}),T.fn.extend({wrapAll:function(e){var t;return this[0]&&("function"==typeof e&&(e=e.call(this[0])),t=T(e,this[0].ownerDocument).eq(0).clone(!0),this[0].parentNode&&t.insertBefore(this[0]),t.map(function(){var e=this;while(e.firstElementChild)e=e.firstElementChild;return e}).append(this)),this},wrapInner:function(e){return"function"==typeof e?this.each(function(t){T(this).wrapInner(e.call(this,t))}):this.each(function(){var t=T(this),n=t.contents();n.length?n.wrapAll(e):t.append(e)})},wrap:function(e){var t="function"==typeof e;return this.each(function(n){T(this).wrapAll(t?e.call(this,n):e)})},unwrap:function(e){return this.parent(e).not("body").each(function(){T(this).replaceWith(this.childNodes)}),this}});var tm=/[+-]?(?:\d*\.|)\d+(?:[eE][+-]?\d+|)/.source,tx=RegExp("^(?:([+-])=|)("+tm+")([a-z%]*)$","i"),tb=RegExp("^("+tm+")(?!px)[a-z%]+$","i"),tw=/^--/,tT=["Top","Right","Bottom","Left"],tC=/^[a-z]/,tE=/^(?:Border(?:Top|Right|Bottom|Left)?(?:Width|)|(?:Margin|Padding)?(?:Top|Right|Bottom|Left)?|(?:Min|Max)?(?:Width|Height))$/;function tS(e){return tC.test(e)&&tE.test(e[0].toUpperCase()+e.slice(1))}var tD=/^-ms-/;function tA(e){return eX(e.replace(tD,"ms-"))}function tN(t){var n=t.ownerDocument.defaultView;return n||(n=e),n.getComputedStyle(t)}function tk(e,t,n){var r,i=tw.test(t);return(n=n||tN(e))&&(r=n.getPropertyValue(t)||n[t],i&&r&&(r=r.replace(N,"$1")||void 0),""!==r||tn(e)||(r=T.style(e,t))),void 0!==r?r+"":r}var tO=["Webkit","Moz","ms"],tj=y.createElement("div").style;function tL(e){return e in tj?e:function(e){var t=e[0].toUpperCase()+e.slice(1),n=tO.length;while(n--)if((e=tO[n]+t)in tj)return e}(e)||e}var tP,tR,tI=y.createElement("table");function tH(){if(tI&&tI.style){var t,n=y.createElement("col"),r=y.createElement("tr"),i=y.createElement("td");if(tI.style.cssText="position:absolute;left:-11111px;border-collapse:separate;border-spacing:0",r.style.cssText="box-sizing:content-box;border:1px solid;height:1px",i.style.cssText="height:9px;width:9px;padding:0",n.span=2,P.appendChild(tI).appendChild(n).parentNode.appendChild(r).appendChild(i).parentNode.appendChild(i.cloneNode(!0)),0===tI.offsetWidth)return void P.removeChild(tI);t=e.getComputedStyle(r),tR=D||18===Math.round(parseFloat(e.getComputedStyle(n).width)),tP=Math.round(parseFloat(t.height)+parseFloat(t.borderTopWidth)+parseFloat(t.borderBottomWidth))===r.offsetHeight,P.removeChild(tI),tI=null}}T.extend(d,{reliableTrDimensions:function(){return tH(),tP},reliableColDimensions:function(){return tH(),tR}});var t$={position:"absolute",visibility:"hidden",display:"block"},tM={letterSpacing:"0",fontWeight:"400"};function tB(e,t,n){var r=tx.exec(t);return r?Math.max(0,r[2]-(n||0))+(r[3]||"px"):t}function tF(e,t,n,r,i,o){var a=+("width"===t),s=0,u=0,l=0;if(n===(r?"border":"content"))return 0;for(;a<4;a+=2)"margin"===n&&(l+=T.css(e,n+tT[a],!0,i)),r?("content"===n&&(u-=T.css(e,"padding"+tT[a],!0,i)),"margin"!==n&&(u-=T.css(e,"border"+tT[a]+"Width",!0,i))):(u+=T.css(e,"padding"+tT[a],!0,i),"padding"!==n?u+=T.css(e,"border"+tT[a]+"Width",!0,i):s+=T.css(e,"border"+tT[a]+"Width",!0,i));return!r&&o>=0&&(u+=Math.max(0,Math.ceil(e["offset"+t[0].toUpperCase()+t.slice(1)]-o-u-s-.5))||0),u+l}function tq(e,t,n){var r=tN(e),i=(D||n)&&"border-box"===T.css(e,"boxSizing",!1,r),o=i,a=tk(e,t,r),s="offset"+t[0].toUpperCase()+t.slice(1);if(tb.test(a)){if(!n)return a;a="auto"}return("auto"===a||D&&i||!d.reliableColDimensions()&&C(e,"col")||!d.reliableTrDimensions()&&C(e,"tr"))&&e.getClientRects().length&&(i="border-box"===T.css(e,"boxSizing",!1,r),(o=s in e)&&(a=e[s])),(a=parseFloat(a)||0)+tF(e,t,n||(i?"border":"content"),o,r,a)+"px"}function tW(e,t){return"none"===e.style.display||""===e.style.display&&"none"===T.css(e,"display")}T.extend({cssHooks:{},style:function(e,t,n,r){if(e&&3!==e.nodeType&&8!==e.nodeType&&e.style){var i,o,a,s=tA(t),u=tw.test(t),l=e.style;if(u||(t=tL(s)),a=T.cssHooks[t]||T.cssHooks[s],void 0===n)return a&&"get"in a&&void 0!==(i=a.get(e,!1,r))?i:l[t];if("string"==(o=typeof n)&&(i=tx.exec(n))&&i[1]&&(n=function(e,t,n,r){var i,o,a=20,s=function(){return T.css(e,t,"")},u=s(),l=n&&n[3]||(tS(t)?"px":""),c=e.nodeType&&(!tS(t)||"px"!==l&&+u)&&tx.exec(T.css(e,t));if(c&&c[3]!==l){u/=2,l=l||c[3],c=+u||1;while(a--)T.style(e,t,c+l),(1-o)*(1-(o=s()/u||.5))<=0&&(a=0),c/=o;c*=2,T.style(e,t,c+l),n=n||[]}return n&&(c=+c||+u||0,i=n[1]?c+(n[1]+1)*n[2]:+n[2]),i}(e,t,i),o="number"),null!=n&&n==n)"number"===o&&(n+=i&&i[3]||(tS(s)?"px":"")),D&&""===n&&0===t.indexOf("background")&&(l[t]="inherit"),a&&"set"in a&&void 0===(n=a.set(e,n,r))||(u?l.setProperty(t,n):l[t]=n)}},css:function(e,t,n,r){var i,o,a,s=tA(t);return(tw.test(t)||(t=tL(s)),(a=T.cssHooks[t]||T.cssHooks[s])&&"get"in a&&(i=a.get(e,!0,n)),void 0===i&&(i=tk(e,t,r)),"normal"===i&&t in tM&&(i=tM[t]),""===n||n)?(o=parseFloat(i),!0===n||isFinite(o)?o||0:i):i}}),T.each(["height","width"],function(e,t){T.cssHooks[t]={get:function(e,n,r){if(n)return"none"===T.css(e,"display")?function(e,t,n){var r,i,o={};for(i in t)o[i]=e.style[i],e.style[i]=t[i];for(i in r=n.call(e),t)e.style[i]=o[i];return r}(e,t$,function(){return tq(e,t,r)}):tq(e,t,r)},set:function(e,n,r){var i,o=tN(e),a=r&&"border-box"===T.css(e,"boxSizing",!1,o),s=r?tF(e,t,r,a,o):0;return s&&(i=tx.exec(n))&&"px"!==(i[3]||"px")&&(e.style[t]=n,n=T.css(e,t)),tB(e,n,s)}}}),T.each({margin:"",padding:"",border:"Width"},function(e,t){T.cssHooks[e+t]={expand:function(n){for(var r=0,i={},o="string"==typeof n?n.split(" "):[n];r<4;r++)i[e+tT[r]+t]=o[r]||o[r-2]||o[0];return i}},"margin"!==e&&(T.cssHooks[e+t].set=tB)}),T.fn.extend({css:function(e,t){return Q(this,function(e,t,n){var r,i,o={},a=0;if(Array.isArray(t)){for(r=tN(e),i=t.length;a<i;a++)o[t[a]]=T.css(e,t[a],!1,r);return o}return void 0!==n?T.style(e,t,n):T.css(e,t)},e,t,arguments.length>1)}}),T.expr.pseudos.hidden=function(e){return!T.expr.pseudos.visible(e)},T.expr.pseudos.visible=function(e){return!!(e.offsetWidth||e.offsetHeight||e.getClientRects().length)};var tU={};function tz(e,t){for(var n,r,i=[],o=0,a=e.length;o<a;o++)(r=e[o]).style&&(n=r.style.display,t?("none"===n&&(i[o]=eY.get(r,"display")||null,i[o]||(r.style.display="")),""===r.style.display&&tW(r)&&(i[o]=function(e){var t,n=e.ownerDocument,r=e.nodeName,i=tU[r];return i||(t=n.body.appendChild(n.createElement(r)),i=T.css(t,"display"),t.parentNode.removeChild(t),"none"===i&&(i="block"),tU[r]=i),i}(r))):"none"!==n&&(i[o]="none",eY.set(r,"display",n)));for(o=0;o<a;o++)null!=i[o]&&(e[o].style.display=i[o]);return e}T.fn.extend({show:function(){return tz(this,!0)},hide:function(){return tz(this)},toggle:function(e){return"boolean"==typeof e?e?this.show():this.hide():this.each(function(){tW(this)?T(this).show():T(this).hide()})}});var tX=/\[\]$/,t_=/\r?\n/g,tV=/^(?:submit|button|image|reset|file)$/i,tY=/^(?:input|select|textarea|keygen)/i;T.param=function(e,t){var n,r=[],i=function(e,t){var n="function"==typeof t?t():t;r[r.length]=encodeURIComponent(e)+"="+encodeURIComponent(null==n?"":n)};if(null==e)return"";if(Array.isArray(e)||e.jquery&&!T.isPlainObject(e))T.each(e,function(){i(this.name,this.value)});else for(n in e)!function e(t,n,r,i){var o;if(Array.isArray(n))T.each(n,function(n,o){r||tX.test(t)?i(t,o):e(t+"["+("object"==typeof o&&null!=o?n:"")+"]",o,r,i)});else if(r||"object"!==h(n))i(t,n);else for(o in n)e(t+"["+o+"]",n[o],r,i)}(n,e[n],t,i);return r.join("&")},T.fn.extend({serialize:function(){return T.param(this.serializeArray())},serializeArray:function(){return this.map(function(){var e=T.prop(this,"elements");return e?T.makeArray(e):this}).filter(function(){var e=this.type;return this.name&&!T(this).is(":disabled")&&tY.test(this.nodeName)&&!tV.test(e)&&(this.checked||!e5.test(e))}).map(function(e,t){var n=T(this).val();return null==n?null:Array.isArray(n)?T.map(n,function(e){return{name:t.name,value:e.replace(t_,"\r\n")}}):{name:t.name,value:n.replace(t_,"\r\n")}}).get()}}),T.parseXML=function(t){var n,r;if(!t||"string"!=typeof t)return null;try{n=new e.DOMParser().parseFromString(t,"text/xml")}catch(e){}return r=n&&n.getElementsByTagName("parsererror")[0],(!n||r)&&T.error("Invalid XML: "+(r?T.map(r.childNodes,function(e){return e.textContent}).join("\n"):t)),n},T.parseHTML=function(t,n,r){var i,o;return"string"==typeof t||eH(t+"")?("boolean"==typeof n&&(r=n,n=!1),n||(n=new e.DOMParser().parseFromString("","text/html")),i=eI.exec(t),o=!r&&[],i)?[n.createElement(i[1])]:(i=tc([t],n,o),o&&o.length&&T(o).remove(),T.merge([],i.childNodes)):[]},T.offset={setOffset:function(e,t,n){var r,i,o,a,s,u,l=T.css(e,"position"),c=T(e),f={};"static"===l&&(e.style.position="relative"),s=c.offset(),o=T.css(e,"top"),u=T.css(e,"left"),("absolute"===l||"fixed"===l)&&(o+u).indexOf("auto")>-1?(a=(r=c.position()).top,i=r.left):(a=parseFloat(o)||0,i=parseFloat(u)||0),"function"==typeof t&&(t=t.call(e,n,T.extend({},s))),null!=t.top&&(f.top=t.top-s.top+a),null!=t.left&&(f.left=t.left-s.left+i),"using"in t?t.using.call(e,f):c.css(f)}},T.fn.extend({offset:function(e){if(arguments.length)return void 0===e?this:this.each(function(t){T.offset.setOffset(this,e,t)});var t,n,r=this[0];if(r)return r.getClientRects().length?(t=r.getBoundingClientRect(),n=r.ownerDocument.defaultView,{top:t.top+n.pageYOffset,left:t.left+n.pageXOffset}):{top:0,left:0}},position:function(){if(this[0]){var e,t,n,r=this[0],i={top:0,left:0};if("fixed"===T.css(r,"position"))t=r.getBoundingClientRect();else{t=this.offset(),n=r.ownerDocument,e=r.offsetParent||n.documentElement;while(e&&e!==n.documentElement&&"static"===T.css(e,"position"))e=e.offsetParent||n.documentElement;e&&e!==r&&1===e.nodeType&&"static"!==T.css(e,"position")&&(i=T(e).offset(),i.top+=T.css(e,"borderTopWidth",!0),i.left+=T.css(e,"borderLeftWidth",!0))}return{top:t.top-i.top-T.css(r,"marginTop",!0),left:t.left-i.left-T.css(r,"marginLeft",!0)}}},offsetParent:function(){return this.map(function(){var e=this.offsetParent;while(e&&"static"===T.css(e,"position"))e=e.offsetParent;return e||P})}}),T.each({scrollLeft:"pageXOffset",scrollTop:"pageYOffset"},function(e,t){var n="pageYOffset"===t;T.fn[e]=function(r){return Q(this,function(e,r,i){var o;if(g(e)?o=e:9===e.nodeType&&(o=e.defaultView),void 0===i)return o?o[t]:e[r];o?o.scrollTo(n?o.pageXOffset:i,n?i:o.pageYOffset):e[r]=i},e,r,arguments.length)}}),T.each({Height:"height",Width:"width"},function(e,t){T.each({padding:"inner"+e,content:t,"":"outer"+e},function(n,r){T.fn[r]=function(i,o){var a=arguments.length&&(n||"boolean"!=typeof i),s=n||(!0===i||!0===o?"margin":"border");return Q(this,function(t,n,i){var o;return g(t)?0===r.indexOf("outer")?t["inner"+e]:t.document.documentElement["client"+e]:9===t.nodeType?(o=t.documentElement,Math.max(t.body["scroll"+e],o["scroll"+e],t.body["offset"+e],o["offset"+e],o["client"+e])):void 0===i?T.css(t,n,s):T.style(t,n,i,s)},t,a?i:void 0,a)}})}),T.fn.extend({bind:function(e,t,n){return this.on(e,null,t,n)},unbind:function(e,t){return this.off(e,null,t)},delegate:function(e,t,n,r){return this.on(t,e,n,r)},undelegate:function(e,t,n){return 1==arguments.length?this.off(e,"**"):this.off(t,e||"**",n)},hover:function(e,t){return this.on("mouseenter",e).on("mouseleave",t||e)}}),T.each("blur focus focusin focusout resize scroll click dblclick mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave change select submit keydown keypress keyup contextmenu".split(" "),function(e,t){T.fn[t]=function(e,n){return arguments.length>0?this.on(t,null,e,n):this.trigger(t)}}),T.proxy=function(e,t){var n,r,o;if("string"==typeof t&&(n=e[t],t=e,e=n),"function"==typeof e)return r=i.call(arguments,2),(o=function(){return e.apply(t||this,r.concat(i.call(arguments)))}).guid=e.guid=e.guid||T.guid++,o},T.holdReady=function(e){e?T.readyWait++:T.ready(!0)},T.expr[":"]=T.expr.filters=T.expr.pseudos,"function"==typeof define&&define.amd&&define("jquery",[],function(){return T});var tQ=e.jQuery,tG=e.$;T.noConflict=function(t){return e.$===T&&(e.$=tG),t&&e.jQuery===T&&(e.jQuery=tQ),T},void 0===t&&(e.jQuery=e.$=T);var tK=[],tJ=function(e){tK.push(e)},tZ=function(t){e.setTimeout(function(){t.call(y,T)})};function t0(){y.removeEventListener("DOMContentLoaded",t0),e.removeEventListener("load",t0),T.ready()}return T.fn.ready=function(e){return tJ(e),this},T.extend({isReady:!1,readyWait:1,ready:function(e){(!0===e?--T.readyWait:T.isReady)||(T.isReady=!0,!0!==e&&--T.readyWait>0||(tJ=function(e){tK.push(e);while(tK.length)"function"==typeof(e=tK.shift())&&tZ(e)})())}}),T.ready.then=T.fn.ready,"loading"!==y.readyState?e.setTimeout(T.ready):(y.addEventListener("DOMContentLoaded",t0),e.addEventListener("load",t0)),T});
```

### public/assets/js/summernote-bs5.min.js

```javascript
/*! Summernote v0.9.0 | (c) 2013~ Hackerwins and contributors | MIT license */
!function(t,e){if("object"==typeof exports&&"object"==typeof module)module.exports=e(require("jquery"));else if("function"==typeof define&&define.amd)define(["jquery"],e);else{var o="object"==typeof exports?e(require("jquery")):e(t.jQuery);for(var n in o)("object"==typeof exports?exports:t)[n]=o[n]}}(self,(t=>(()=>{"use strict";var e={7e3:(t,e,o)=>{var n=o(8938),i=o.n(n);i().summernote=i().summernote||{lang:{}},i().extend(!0,i().summernote.lang,{"en-US":{font:{bold:"Bold",italic:"Italic",underline:"Underline",clear:"Remove Font Style",height:"Line Height",name:"Font Family",strikethrough:"Strikethrough",subscript:"Subscript",superscript:"Superscript",size:"Font Size",sizeunit:"Font Size Unit"},image:{image:"Picture",insert:"Insert Image",resizeFull:"Resize full",resizeHalf:"Resize half",resizeQuarter:"Resize quarter",resizeNone:"Original size",floatLeft:"Float Left",floatRight:"Float Right",floatNone:"Remove float",shapeRounded:"Shape: Rounded",shapeCircle:"Shape: Circle",shapeThumbnail:"Shape: Thumbnail",shapeNone:"Shape: None",dragImageHere:"Drag image or text here",dropImage:"Drop image or Text",selectFromFiles:"Select from files",maximumFileSize:"Maximum file size",maximumFileSizeError:"Maximum file size exceeded.",url:"Image URL",remove:"Remove Image",original:"Original"},video:{video:"Video",videoLink:"Video Link",insert:"Insert Video",url:"Video URL",providers:"(YouTube, Google Drive, Vimeo, Vine, Instagram, DailyMotion, Youku, Peertube)"},link:{link:"Link",insert:"Insert Link",unlink:"Unlink",edit:"Edit",textToDisplay:"Text to display",url:"To what URL should this link go?",openInNewWindow:"Open in new window"},table:{table:"Table",addRowAbove:"Add row above",addRowBelow:"Add row below",addColLeft:"Add column left",addColRight:"Add column right",delRow:"Delete row",delCol:"Delete column",delTable:"Delete table"},hr:{insert:"Insert Horizontal Rule"},style:{style:"Style",p:"Normal",blockquote:"Quote",pre:"Code",h1:"Header 1",h2:"Header 2",h3:"Header 3",h4:"Header 4",h5:"Header 5",h6:"Header 6"},lists:{unordered:"Unordered list",ordered:"Ordered list"},options:{help:"Help",fullscreen:"Full Screen",codeview:"Code View"},paragraph:{paragraph:"Paragraph",outdent:"Outdent",indent:"Indent",left:"Align left",center:"Align center",right:"Align right",justify:"Justify full"},color:{recent:"Recent Color",more:"More Color",background:"Background Color",foreground:"Text Color",transparent:"Transparent",setTransparent:"Set transparent",reset:"Reset",resetToDefault:"Reset to default",cpSelect:"Select"},shortcut:{shortcuts:"Keyboard shortcuts",close:"Close",textFormatting:"Text formatting",action:"Action",paragraphFormatting:"Paragraph formatting",documentStyle:"Document Style",extraKeys:"Extra keys"},help:{escape:"Escape",insertParagraph:"Insert Paragraph",undo:"Undo the last command",redo:"Redo the last command",tab:"Tab",untab:"Untab",bold:"Set a bold style",italic:"Set a italic style",underline:"Set a underline style",strikethrough:"Set a strikethrough style",removeFormat:"Clean a style",justifyLeft:"Set left align",justifyCenter:"Set center align",justifyRight:"Set right align",justifyFull:"Set full align",insertUnorderedList:"Toggle unordered list",insertOrderedList:"Toggle ordered list",outdent:"Outdent on current paragraph",indent:"Indent on current paragraph",formatPara:"Change current block's format as a paragraph(P tag)",formatH1:"Change current block's format as H1",formatH2:"Change current block's format as H2",formatH3:"Change current block's format as H3",formatH4:"Change current block's format as H4",formatH5:"Change current block's format as H5",formatH6:"Change current block's format as H6",insertHorizontalRule:"Insert horizontal rule","linkDialog.show":"Show Link Dialog"},history:{undo:"Undo",redo:"Redo"},specialChar:{specialChar:"SPECIAL CHARACTERS",select:"Select Special characters"},output:{noSelection:"No Selection Made!"}}})},8938:e=>{e.exports=t}},o={};function n(t){var i=o[t];if(void 0!==i)return i.exports;var r=o[t]={exports:{}};return e[t](r,r.exports,n),r.exports}n.n=t=>{var e=t&&t.__esModule?()=>t.default:()=>t;return n.d(e,{a:e}),e},n.d=(t,e)=>{for(var o in e)n.o(e,o)&&!n.o(t,o)&&Object.defineProperty(t,o,{enumerable:!0,get:e[o]})},n.o=(t,e)=>Object.prototype.hasOwnProperty.call(t,e);var i=n(8938),r=n.n(i),a=(n(7e3),["sans-serif","serif","monospace","cursive","fantasy"]);function s(t){return-1===r().inArray(t.toLowerCase(),a)?"'".concat(t,"'"):t}var l,c=navigator.userAgent,u=/MSIE|Trident/i.test(c);if(u){var d=/MSIE (\d+[.]\d+)/.exec(c);d&&(l=parseFloat(d[1])),(d=/Trident\/.*rv:([0-9]{1,}[.0-9]{0,})/.exec(c))&&(l=parseFloat(d[1]))}var f=/Edge\/\d+/.test(c),h="ontouchstart"in window||navigator.MaxTouchPoints>0||navigator.msMaxTouchPoints>0,p=u?"DOMCharacterDataModified DOMSubtreeModified DOMNodeInserted":"input";const m={isMac:navigator.appVersion.indexOf("Mac")>-1,isMSIE:u,isEdge:f,isFF:!f&&/firefox/i.test(c),isPhantom:/PhantomJS/i.test(c),isWebkit:!f&&/webkit/i.test(c),isChrome:!f&&/chrome/i.test(c),isSafari:!f&&/safari/i.test(c)&&!/chrome/i.test(c),browserVersion:l,isSupportTouch:h,isFontInstalled:function(){var t=document.createElement("canvas"),e=t.getContext("2d",{willReadFrequently:!0});function o(t,o){return e.clearRect(0,0,40,20),e.font="20px "+s(t)+', "'+o+'"',e.fillText("mw",20,10),e.getImageData(0,0,40,20).data.join("")}return t.width=40,t.height=20,e.textAlign="center",e.fillStyle="black",e.textBaseline="middle",function(t){var e="Comic Sans MS"===t?"Courier New":"Comic Sans MS";return o(e,e)!==o(t,e)}}(),isW3CRangeSupport:!!document.createRange,inputEventName:p,genericFontFamilies:a,validFontName:s};var v=0;const g={eq:function(t){return function(e){return t===e}},eq2:function(t,e){return t===e},peq2:function(t){return function(e,o){return e[t]===o[t]}},ok:function(){return!0},fail:function(){return!1},self:function(t){return t},not:function(t){return function(){return!t.apply(t,arguments)}},and:function(t,e){return function(o){return t(o)&&e(o)}},invoke:function(t,e){return function(){return t[e].apply(t,arguments)}},resetUniqueId:function(){v=0},uniqueId:function(t){var e=++v+"";return t?t+e:e},rect2bnd:function(t){var e=r()(document);return{top:t.top+e.scrollTop(),left:t.left+e.scrollLeft(),width:t.right-t.left,height:t.bottom-t.top}},invertObject:function(t){var e={};for(var o in t)Object.prototype.hasOwnProperty.call(t,o)&&(e[t[o]]=o);return e},namespaceToCamel:function(t,e){return(e=e||"")+t.split(".").map((function(t){return t.substring(0,1).toUpperCase()+t.substring(1)})).join("")},debounce:function(t,e,o){var n;return function(){var i=this,r=arguments,a=o&&!n;clearTimeout(n),n=setTimeout((function(){n=null,o||t.apply(i,r)}),e),a&&t.apply(i,r)}},isValidUrl:function(t){return/[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)/gi.test(t)}};function b(t){return t[0]}function y(t){return t[t.length-1]}function k(t){return t.slice(1)}function w(t,e){if(t&&t.length&&e){if(t.indexOf)return-1!==t.indexOf(e);if(t.contains)return t.contains(e)}return!1}const C={head:b,last:y,initial:function(t){return t.slice(0,t.length-1)},tail:k,prev:function(t,e){if(t&&t.length&&e){var o=t.indexOf(e);return-1===o?null:t[o-1]}return null},next:function(t,e){if(t&&t.length&&e){var o=t.indexOf(e);return-1===o?null:t[o+1]}return null},find:function(t,e){for(var o=0,n=t.length;o<n;o++){var i=t[o];if(e(i))return i}},contains:w,all:function(t,e){for(var o=0,n=t.length;o<n;o++)if(!e(t[o]))return!1;return!0},sum:function(t,e){return e=e||g.self,t.reduce((function(t,o){return t+e(o)}),0)},from:function(t){for(var e=[],o=t.length,n=-1;++n<o;)e[n]=t[n];return e},isEmpty:function(t){return!t||!t.length},clusterBy:function(t,e){return t.length?k(t).reduce((function(t,o){var n=y(t);return e(y(n),o)?n[n.length]=o:t[t.length]=[o],t}),[[b(t)]]):[]},compact:function(t){for(var e=[],o=0,n=t.length;o<n;o++)t[o]&&e.push(t[o]);return e},unique:function(t){for(var e=[],o=0,n=t.length;o<n;o++)w(e,t[o])||e.push(t[o]);return e}};var S=String.fromCharCode(160);function x(t){return t&&r()(t).hasClass("note-editable")}function T(t){return t=t.toUpperCase(),function(e){return e&&e.nodeName.toUpperCase()===t}}function E(t){return t&&3===t.nodeType}function P(t){return t&&/^BR|^IMG|^HR|^IFRAME|^BUTTON|^INPUT|^AUDIO|^VIDEO|^EMBED/.test(t.nodeName.toUpperCase())}function N(t){return!x(t)&&(t&&/^DIV|^P|^LI|^H[1-7]/.test(t.nodeName.toUpperCase()))}var I=T("PRE"),$=T("LI");var R=T("TABLE"),A=T("DATA");function L(t){return!(z(t)||F(t)||D(t)||N(t)||R(t)||j(t)||A(t))}function F(t){return t&&/^UL|^OL/.test(t.nodeName.toUpperCase())}var D=T("HR");function H(t){return t&&/^TD|^TH/.test(t.nodeName.toUpperCase())}var j=T("BLOCKQUOTE");function z(t){return H(t)||j(t)||x(t)}var B=T("A");var O=T("BODY");var M=m.isMSIE&&m.browserVersion<11?"&nbsp;":"<br>";function U(t){return E(t)?t.nodeValue.length:t?t.childNodes.length:0}function W(t){var e=U(t);return 0===e||(!E(t)&&1===e&&t.innerHTML===M||!(!C.all(t.childNodes,E)||""!==t.innerHTML))}function K(t){P(t)||U(t)||(t.innerHTML=M)}function q(t,e){for(;t;){if(e(t))return t;if(x(t))break;t=t.parentNode}return null}function V(t,e){e=e||g.fail;var o=[];return q(t,(function(t){return x(t)||o.push(t),e(t)})),o}function _(t,e){e=e||g.fail;for(var o=[];t&&!e(t);)o.push(t),t=t.nextSibling;return o}function G(t,e){var o=e.nextSibling,n=e.parentNode;return o?n.insertBefore(t,o):n.appendChild(t),t}function Z(t,e,o){return r().each(e,(function(e,n){!o&&$(t)&&null===t.firstChild&&F(n)&&t.appendChild(ut("br")),t.appendChild(n)})),t}function Y(t){return 0===t.offset}function X(t){return t.offset===U(t.node)}function Q(t){return Y(t)||X(t)}function J(t,e){for(;t&&t!==e;){if(0!==et(t))return!1;t=t.parentNode}return!0}function tt(t,e){if(!e)return!1;for(;t&&t!==e;){if(et(t)!==U(t.parentNode)-1)return!1;t=t.parentNode}return!0}function et(t){for(var e=0;t=t.previousSibling;)e+=1;return e}function ot(t){return!!(t&&t.childNodes&&t.childNodes.length)}function nt(t,e){var o,n;if(0===t.offset){if(x(t.node))return null;o=t.node.parentNode,n=et(t.node)}else ot(t.node)?n=U(o=t.node.childNodes[t.offset-1]):(o=t.node,n=e?0:t.offset-1);return{node:o,offset:n}}function it(t,e){var o,n;if(U(t.node)===t.offset){if(x(t.node))return null;var i=at(t.node);i?(o=i,n=0):(o=t.node.parentNode,n=et(t.node)+1)}else ot(t.node)?(o=t.node.childNodes[t.offset],n=0):(o=t.node,n=e?U(t.node):t.offset+1);return{node:o,offset:n}}function rt(t,e){var o,n=0;if(U(t.node)===t.offset){if(x(t.node))return null;o=t.node.parentNode,n=et(t.node)+1,x(o)&&(o=t.node.nextSibling,n=0)}else ot(t.node)?(o=t.node.childNodes[t.offset],n=0):(o=t.node,n=e?U(t.node):t.offset+1);return{node:o,offset:n}}function at(t){if(t.nextSibling&&t.parent===t.nextSibling.parent)return E(t.nextSibling)?t.nextSibling:at(t.nextSibling)}function st(t,e){return t.node===e.node&&t.offset===e.offset}function lt(t,e){var o=e&&e.isSkipPaddingBlankHTML,n=e&&e.isNotSplitEdgePoint,i=e&&e.isDiscardEmptySplits;if(i&&(o=!0),Q(t)&&(E(t.node)||n)){if(Y(t))return t.node;if(X(t))return t.node.nextSibling}if(E(t.node))return t.node.splitText(t.offset);var r=_(t.node.childNodes[t.offset]),a=G(t.node.cloneNode(!1),t.node);return Z(a,r),o||(K(t.node),K(a)),i&&(W(t.node)&&dt(t.node),W(a))?(dt(a),t.node.nextSibling):a}function ct(t,e,o){var n=V(e.node,g.eq(t));if(!n.length)return null;if(1===n.length)return lt(e,o);if(n.length>2){var i=n.slice(0,n.length-1).find((function(t){return t.nextSibling}));if(i&&0!=e.offset&&X(e)){var r,a=i.nextSibling;1==a.nodeType?(n=V(r=a.childNodes[0],g.eq(t)),e={node:r,offset:0}):3!=a.nodeType||a.data.match(/[\n\r]/g)||(n=V(r=a,g.eq(t)),e={node:r,offset:0})}}return n.reduce((function(t,n){return t===e.node&&(t=lt(e,o)),lt({node:n,offset:t?et(t):U(n)},o)}))}function ut(t){return document.createElement(t)}function dt(t,e){if(t&&t.parentNode){if(t.removeNode)return t.removeNode(e);var o=t.parentNode;if(!e){for(var n=[],i=0,r=t.childNodes.length;i<r;i++)n.push(t.childNodes[i]);for(var a=0,s=n.length;a<s;a++)o.insertBefore(n[a],t)}o.removeChild(t)}}var ft=T("TEXTAREA");function ht(t,e){var o=ft(t[0])?t.val():t.html();return e?o.replace(/[\n\r]/g,""):o}const pt={NBSP_CHAR:S,ZERO_WIDTH_NBSP_CHAR:"\ufeff",blank:M,emptyPara:"<p>".concat(M,"</p>"),makePredByNodeName:T,isEditable:x,isControlSizing:function(t){return t&&r()(t).hasClass("note-control-sizing")},isText:E,isElement:function(t){return t&&1===t.nodeType},isVoid:P,isPara:N,isPurePara:function(t){return N(t)&&!$(t)},isHeading:function(t){return t&&/^H[1-7]/.test(t.nodeName.toUpperCase())},isInline:L,isBlock:g.not(L),isBodyInline:function(t){return L(t)&&!q(t,N)},isBody:O,isParaInline:function(t){return L(t)&&!!q(t,N)},isPre:I,isList:F,isTable:R,isData:A,isCell:H,isBlockquote:j,isBodyContainer:z,isAnchor:B,isDiv:T("DIV"),isLi:$,isBR:T("BR"),isSpan:T("SPAN"),isB:T("B"),isU:T("U"),isS:T("S"),isI:T("I"),isImg:T("IMG"),isTextarea:ft,deepestChildIsEmpty:function(t){do{if(null===t.firstElementChild||""===t.firstElementChild.innerHTML)break}while(t=t.firstElementChild);return W(t)},isEmpty:W,isEmptyAnchor:g.and(B,W),isClosestSibling:function(t,e){return t.nextSibling===e||t.previousSibling===e},withClosestSiblings:function(t,e){e=e||g.ok;var o=[];return t.previousSibling&&e(t.previousSibling)&&o.push(t.previousSibling),o.push(t),t.nextSibling&&e(t.nextSibling)&&o.push(t.nextSibling),o},nodeLength:U,isLeftEdgePoint:Y,isRightEdgePoint:X,isEdgePoint:Q,isLeftEdgeOf:J,isRightEdgeOf:tt,isLeftEdgePointOf:function(t,e){return Y(t)&&J(t.node,e)},isRightEdgePointOf:function(t,e){return X(t)&&tt(t.node,e)},prevPoint:nt,nextPoint:it,nextPointWithEmptyNode:rt,isSamePoint:st,isVisiblePoint:function(t){if(E(t.node)||!ot(t.node)||W(t.node))return!0;var e=t.node.childNodes[t.offset-1],o=t.node.childNodes[t.offset];return!((e&&!P(e)||o&&!P(o))&&!R(o))},prevPointUntil:function(t,e){for(;t;){if(e(t))return t;t=nt(t)}return null},nextPointUntil:function(t,e){for(;t;){if(e(t))return t;t=it(t)}return null},isCharPoint:function(t){if(!E(t.node))return!1;var e=t.node.nodeValue.charAt(t.offset-1);return e&&" "!==e&&e!==S},isSpacePoint:function(t){if(!E(t.node))return!1;var e=t.node.nodeValue.charAt(t.offset-1);return" "===e||e===S},walkPoint:function(t,e,o,n){for(var i=t;i&&i.node&&(o(i),!st(i,e));){i=rt(i,n&&t.node!==i.node&&e.node!==i.node)}},ancestor:q,singleChildAncestor:function(t,e){for(t=t.parentNode;t&&1===U(t);){if(e(t))return t;if(x(t))break;t=t.parentNode}return null},listAncestor:V,lastAncestor:function(t,e){var o=V(t);return C.last(o.filter(e))},listNext:_,listPrev:function(t,e){e=e||g.fail;for(var o=[];t&&!e(t);)o.push(t),t=t.previousSibling;return o},listDescendant:function(t,e){var o=[];return e=e||g.ok,function n(i){t!==i&&e(i)&&o.push(i);for(var r=0,a=i.childNodes.length;r<a;r++)n(i.childNodes[r])}(t),o},commonAncestor:function(t,e){for(var o=V(t),n=e;n;n=n.parentNode)if(o.indexOf(n)>-1)return n;return null},wrap:function(t,e){var o=t.parentNode,n=r()("<"+e+">")[0];return o.insertBefore(n,t),n.appendChild(t),n},insertAfter:G,appendChildNodes:Z,position:et,hasChildren:ot,makeOffsetPath:function(t,e){return V(e,g.eq(t)).map(et).reverse()},fromOffsetPath:function(t,e){for(var o=t,n=0,i=e.length;n<i;n++)o=o.childNodes.length<=e[n]?o.childNodes[o.childNodes.length-1]:o.childNodes[e[n]];return o},splitTree:ct,splitPoint:function(t,e){var o,n,i=e?N:z,r=V(t.node,i),a=C.last(r)||t.node;i(a)?(o=r[r.length-2],n=a):n=(o=a).parentNode;var s=o&&ct(o,t,{isSkipPaddingBlankHTML:e,isNotSplitEdgePoint:e});return s||n!==t.node||(s=t.node.childNodes[t.offset]),{rightNode:s,container:n}},create:ut,createText:function(t){return document.createTextNode(t)},remove:dt,removeWhile:function(t,e){for(;t&&!x(t)&&e(t);){var o=t.parentNode;dt(t),t=o}},replace:function(t,e){if(t.nodeName.toUpperCase()===e.toUpperCase())return t;var o=ut(e);return t.style.cssText&&(o.style.cssText=t.style.cssText),Z(o,C.from(t.childNodes)),G(o,t),dt(t),o},html:function(t,e){var o=ht(t);if(e){o=(o=o.replace(/<(\/?)(\b(?!!)[^>\s]*)(.*?)(\s*\/?>)/g,(function(t,e,o){o=o.toUpperCase();var n=/^DIV|^TD|^TH|^P|^LI|^H[1-7]/.test(o)&&!!e,i=/^BLOCKQUOTE|^TABLE|^TBODY|^TR|^HR|^UL|^OL/.test(o);return t+(n||i?"\n":"")}))).trim()}return o},value:ht,posFromPlaceholder:function(t){var e=r()(t),o=e.offset(),n=e.outerHeight(!0);return{left:o.left,top:o.top+n}},attachEvents:function(t,e){Object.keys(e).forEach((function(o){t.on(o,e[o])}))},detachEvents:function(t,e){Object.keys(e).forEach((function(o){t.off(o,e[o])}))},isCustomStyleTag:function(t){return t&&!E(t)&&C.contains(t.classList,"note-styletag")}};function mt(t){return mt="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},mt(t)}function vt(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,gt(n.key),n)}}function gt(t){var e=function(t,e){if("object"!=mt(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=mt(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==mt(e)?e:e+""}var bt=function(){return t=function t(e,o){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.$note=e,this.memos={},this.modules={},this.layoutInfo={},this.options=r().extend(!0,{},o),r().summernote.ui=r().summernote.ui_template(this.options),this.ui=r().summernote.ui,this.initialize()},e=[{key:"initialize",value:function(){return this.layoutInfo=this.ui.createLayout(this.$note),this._initialize(),this.$note.hide(),this}},{key:"destroy",value:function(){this._destroy(),this.$note.removeData("summernote"),this.ui.removeLayout(this.$note,this.layoutInfo)}},{key:"reset",value:function(){var t=this.isDisabled();this.code(pt.emptyPara),this._destroy(),this._initialize(),t&&this.disable()}},{key:"_initialize",value:function(){var t=this;this.options.id=g.uniqueId(r().now()),this.options.container=this.options.container||this.layoutInfo.editor;var e=r().extend({},this.options.buttons);Object.keys(e).forEach((function(o){t.memo("button."+o,e[o])}));var o=r().extend({},this.options.modules,r().summernote.plugins||{});Object.keys(o).forEach((function(e){t.module(e,o[e],!0)})),Object.keys(this.modules).forEach((function(e){t.initializeModule(e)}))}},{key:"_destroy",value:function(){var t=this;Object.keys(this.modules).reverse().forEach((function(e){t.removeModule(e)})),Object.keys(this.memos).forEach((function(e){t.removeMemo(e)})),this.triggerEvent("destroy",this)}},{key:"code",value:function(t){var e=this.invoke("codeview.isActivated");if(void 0===t)return this.invoke("codeview.sync"),e?this.layoutInfo.codable.val():this.layoutInfo.editable.html();e?this.invoke("codeview.sync",t):this.layoutInfo.editable.html(t),this.$note.val(t),this.triggerEvent("change",t,this.layoutInfo.editable)}},{key:"isDisabled",value:function(){return"false"===this.layoutInfo.editable.attr("contenteditable")}},{key:"enable",value:function(){this.layoutInfo.editable.attr("contenteditable",!0),this.invoke("toolbar.activate",!0),this.triggerEvent("disable",!1),this.options.editing=!0}},{key:"disable",value:function(){this.invoke("codeview.isActivated")&&this.invoke("codeview.deactivate"),this.layoutInfo.editable.attr("contenteditable",!1),this.options.editing=!1,this.invoke("toolbar.deactivate",!0),this.triggerEvent("disable",!0)}},{key:"triggerEvent",value:function(){var t=C.head(arguments),e=C.tail(C.from(arguments)),o=this.options.callbacks[g.namespaceToCamel(t,"on")];o&&o.apply(this.$note[0],e),this.$note.trigger("summernote."+t,e)}},{key:"initializeModule",value:function(t){var e=this.modules[t];e.shouldInitialize=e.shouldInitialize||g.ok,e.shouldInitialize()&&(e.initialize&&e.initialize(),e.events&&pt.attachEvents(this.$note,e.events))}},{key:"module",value:function(t,e,o){if(1===arguments.length)return this.modules[t];this.modules[t]=new e(this),o||this.initializeModule(t)}},{key:"removeModule",value:function(t){var e=this.modules[t];e.shouldInitialize()&&(e.events&&pt.detachEvents(this.$note,e.events),e.destroy&&e.destroy()),delete this.modules[t]}},{key:"memo",value:function(t,e){if(1===arguments.length)return this.memos[t];this.memos[t]=e}},{key:"removeMemo",value:function(t){this.memos[t]&&this.memos[t].destroy&&this.memos[t].destroy(),delete this.memos[t]}},{key:"createInvokeHandlerAndUpdateState",value:function(t,e){var o=this;return function(n){o.createInvokeHandler(t,e)(n),o.invoke("buttons.updateCurrentStyle")}}},{key:"createInvokeHandler",value:function(t,e){var o=this;return function(n){n.preventDefault();var i=r()(n.target);o.invoke(t,e||i.closest("[data-value]").data("value"),i)}}},{key:"invoke",value:function(){var t=C.head(arguments),e=C.tail(C.from(arguments)),o=t.split("."),n=o.length>1,i=n&&C.head(o),r=n?C.last(o):C.head(o),a=this.modules[i||"editor"];return!i&&this[r]?this[r].apply(this,e):a&&a[r]&&a.shouldInitialize()?a[r].apply(a,e):void 0}}],e&&vt(t.prototype,e),o&&vt(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function yt(t){return yt="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},yt(t)}function kt(t){return kt="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},kt(t)}function wt(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,Ct(n.key),n)}}function Ct(t){var e=function(t,e){if("object"!=kt(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=kt(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==kt(e)?e:e+""}function St(t,e){var o,n,i=t.parentElement(),r=document.body.createTextRange(),a=C.from(i.childNodes);for(o=0;o<a.length;o++)if(!pt.isText(a[o])){if(r.moveToElementText(a[o]),r.compareEndPoints("StartToStart",t)>=0)break;n=a[o]}if(0!==o&&pt.isText(a[o-1])){var s=document.body.createTextRange(),l=null;s.moveToElementText(n||i),s.collapse(!n),l=n?n.nextSibling:i.firstChild;var c=t.duplicate();c.setEndPoint("StartToStart",s);for(var u=c.text.replace(/[\r\n]/g,"").length;u>l.nodeValue.length&&l.nextSibling;)u-=l.nodeValue.length,l=l.nextSibling;l.nodeValue;e&&l.nextSibling&&pt.isText(l.nextSibling)&&u===l.nodeValue.length&&(u-=l.nodeValue.length,l=l.nextSibling),i=l,o=u}return{cont:i,offset:o}}function xt(t){var e=document.body.createTextRange(),o=function t(e,o){var n,i;if(pt.isText(e)){var r=pt.listPrev(e,g.not(pt.isText)),a=C.last(r).previousSibling;n=a||e.parentNode,o+=C.sum(C.tail(r),pt.nodeLength),i=!a}else{if(n=e.childNodes[o]||e,pt.isText(n))return t(n,0);o=0,i=!1}return{node:n,collapseToStart:i,offset:o}}(t.node,t.offset);return e.moveToElementText(o.node),e.collapse(o.collapseToStart),e.moveStart("character",o.offset),e}r().fn.extend({summernote:function(){var t=yt(C.head(arguments)),e="string"===t,o="object"===t,n=r().extend({},r().summernote.options,o?C.head(arguments):{});n.langInfo=r().extend(!0,{},r().summernote.lang["en-US"],r().summernote.lang[n.lang]),n.icons=r().extend(!0,{},r().summernote.options.icons,n.icons),n.tooltip="auto"===n.tooltip?!m.isSupportTouch:n.tooltip,this.each((function(t,e){var o=r()(e);if(!o.data("summernote")){var i=new bt(o,n);o.data("summernote",i),o.data("summernote").triggerEvent("init",i.layoutInfo)}}));var i=this.first();if(i.length){var a=i.data("summernote");if(e)return a.invoke.apply(a,C.from(arguments));n.focus&&a.invoke("editor.focus")}return this}});var Tt=function(){function t(e,o,n,i){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.sc=e,this.so=o,this.ec=n,this.eo=i,this.isOnEditable=this.makeIsOn(pt.isEditable),this.isOnList=this.makeIsOn(pt.isList),this.isOnAnchor=this.makeIsOn(pt.isAnchor),this.isOnCell=this.makeIsOn(pt.isCell),this.isOnData=this.makeIsOn(pt.isData)}return e=t,o=[{key:"nativeRange",value:function(){if(m.isW3CRangeSupport){var t=document.createRange();return t.setStart(this.sc,this.so),t.setEnd(this.ec,this.eo),t}var e=xt({node:this.sc,offset:this.so});return e.setEndPoint("EndToEnd",xt({node:this.ec,offset:this.eo})),e}},{key:"getPoints",value:function(){return{sc:this.sc,so:this.so,ec:this.ec,eo:this.eo}}},{key:"getStartPoint",value:function(){return{node:this.sc,offset:this.so}}},{key:"getEndPoint",value:function(){return{node:this.ec,offset:this.eo}}},{key:"select",value:function(){var t=this.nativeRange();if(m.isW3CRangeSupport){var e=document.getSelection();e.rangeCount>0&&e.removeAllRanges(),e.addRange(t)}else t.select();return this}},{key:"scrollIntoView",value:function(t){var e=r()(t).height();return t.scrollTop+e<this.sc.offsetTop&&(t.scrollTop+=Math.abs(t.scrollTop+e-this.sc.offsetTop)),this}},{key:"normalize",value:function(){var e=function(t,e){if(!t)return t;if(pt.isVisiblePoint(t)&&(!pt.isEdgePoint(t)||pt.isRightEdgePoint(t)&&!e||pt.isLeftEdgePoint(t)&&e||pt.isRightEdgePoint(t)&&e&&pt.isVoid(t.node.nextSibling)||pt.isLeftEdgePoint(t)&&!e&&pt.isVoid(t.node.previousSibling)||pt.isBlock(t.node)&&pt.isEmpty(t.node)))return t;var o=pt.ancestor(t.node,pt.isBlock),n=!1;if(!n){var i=pt.prevPoint(t)||{node:null};n=(pt.isLeftEdgePointOf(t,o)||pt.isVoid(i.node))&&!e}var r=!1;if(!r){var a=pt.nextPoint(t)||{node:null};r=(pt.isRightEdgePointOf(t,o)||pt.isVoid(a.node))&&e}if(n||r){if(pt.isVisiblePoint(t))return t;e=!e}return(e?pt.nextPointUntil(pt.nextPoint(t),pt.isVisiblePoint):pt.prevPointUntil(pt.prevPoint(t),pt.isVisiblePoint))||t},o=e(this.getEndPoint(),!1),n=this.isCollapsed()?o:e(this.getStartPoint(),!0);return new t(n.node,n.offset,o.node,o.offset)}},{key:"nodes",value:function(t,e){t=t||g.ok;var o=e&&e.includeAncestor,n=e&&e.fullyContains,i=this.getStartPoint(),r=this.getEndPoint(),a=[],s=[];return pt.walkPoint(i,r,(function(e){var i;pt.isEditable(e.node)||(n?(pt.isLeftEdgePoint(e)&&s.push(e.node),pt.isRightEdgePoint(e)&&C.contains(s,e.node)&&(i=e.node)):i=o?pt.ancestor(e.node,t):e.node,i&&t(i)&&a.push(i))}),!0),C.unique(a)}},{key:"commonAncestor",value:function(){return pt.commonAncestor(this.sc,this.ec)}},{key:"expand",value:function(e){var o=pt.ancestor(this.sc,e),n=pt.ancestor(this.ec,e);if(!o&&!n)return new t(this.sc,this.so,this.ec,this.eo);var i=this.getPoints();return o&&(i.sc=o,i.so=0),n&&(i.ec=n,i.eo=pt.nodeLength(n)),new t(i.sc,i.so,i.ec,i.eo)}},{key:"collapse",value:function(e){return e?new t(this.sc,this.so,this.sc,this.so):new t(this.ec,this.eo,this.ec,this.eo)}},{key:"splitText",value:function(){var e=this.sc===this.ec,o=this.getPoints();return pt.isText(this.ec)&&!pt.isEdgePoint(this.getEndPoint())&&this.ec.splitText(this.eo),pt.isText(this.sc)&&!pt.isEdgePoint(this.getStartPoint())&&(o.sc=this.sc.splitText(this.so),o.so=0,e&&(o.ec=o.sc,o.eo=this.eo-this.so)),new t(o.sc,o.so,o.ec,o.eo)}},{key:"deleteContents",value:function(){if(this.isCollapsed())return this;var e=this.splitText(),o=e.nodes(null,{fullyContains:!0}),n=pt.prevPointUntil(e.getStartPoint(),(function(t){return!C.contains(o,t.node)})),i=[];return r().each(o,(function(t,e){var o=e.parentNode;n.node!==o&&1===pt.nodeLength(o)&&i.push(o),pt.remove(e,!1)})),r().each(i,(function(t,e){pt.remove(e,!1)})),new t(n.node,n.offset,n.node,n.offset).normalize()}},{key:"makeIsOn",value:function(t){return function(){var e=pt.ancestor(this.sc,t);return!!e&&e===pt.ancestor(this.ec,t)}}},{key:"isLeftEdgeOf",value:function(t){if(!pt.isLeftEdgePoint(this.getStartPoint()))return!1;var e=pt.ancestor(this.sc,t);return e&&pt.isLeftEdgeOf(this.sc,e)}},{key:"isCollapsed",value:function(){return this.sc===this.ec&&this.so===this.eo}},{key:"wrapBodyInlineWithPara",value:function(){if(pt.isBodyContainer(this.sc)&&pt.isEmpty(this.sc))return this.sc.innerHTML=pt.emptyPara,new t(this.sc.firstChild,0,this.sc.firstChild,0);var e,o=this.normalize();if(pt.isParaInline(this.sc)||pt.isPara(this.sc))return o;if(pt.isInline(o.sc)){var n=pt.listAncestor(o.sc,g.not(pt.isInline));e=C.last(n),pt.isInline(e)||(e=n[n.length-2]||o.sc.childNodes[o.so])}else e=o.sc.childNodes[o.so>0?o.so-1:0];if(e){var i=pt.listPrev(e,pt.isParaInline).reverse();if((i=i.concat(pt.listNext(e.nextSibling,pt.isParaInline))).length){var r=pt.wrap(C.head(i),"p");pt.appendChildNodes(r,C.tail(i))}}return this.normalize()}},{key:"insertNode",value:function(t){var e=arguments.length>1&&void 0!==arguments[1]&&arguments[1],o=this;(pt.isText(t)||pt.isInline(t))&&(o=this.wrapBodyInlineWithPara().deleteContents());var n=pt.splitPoint(o.getStartPoint(),pt.isInline(t));return n.rightNode?(n.rightNode.parentNode.insertBefore(t,n.rightNode),pt.isEmpty(n.rightNode)&&(e||pt.isPara(t))&&n.rightNode.parentNode.removeChild(n.rightNode)):n.container.appendChild(t),t}},{key:"pasteHTML",value:function(t){t=((t||"")+"").trim(t);var e=r()("<div></div>").html(t)[0],o=C.from(e.childNodes),n=this,i=!1;return n.so>=0&&(o=o.reverse(),i=!0),o=o.map((function(t){return n.insertNode(t,!pt.isInline(t))})),i&&(o=o.reverse()),o}},{key:"toString",value:function(){var t=this.nativeRange();return m.isW3CRangeSupport?t.toString():t.text}},{key:"getWordRange",value:function(e){var o=this.getEndPoint();if(!pt.isCharPoint(o))return this;var n=pt.prevPointUntil(o,(function(t){return!pt.isCharPoint(t)}));return e&&(o=pt.nextPointUntil(o,(function(t){return!pt.isCharPoint(t)}))),new t(n.node,n.offset,o.node,o.offset)}},{key:"getWordsRange",value:function(e){var o=this.getEndPoint(),n=function(t){return!pt.isCharPoint(t)&&!pt.isSpacePoint(t)};if(n(o))return this;var i=pt.prevPointUntil(o,n);return e&&(o=pt.nextPointUntil(o,n)),new t(i.node,i.offset,o.node,o.offset)}},{key:"getWordsMatchRange",value:function(e){var o=this.getEndPoint(),n=pt.prevPointUntil(o,(function(n){if(!pt.isCharPoint(n)&&!pt.isSpacePoint(n))return!0;var i=new t(n.node,n.offset,o.node,o.offset),r=e.exec(i.toString());return r&&0===r.index})),i=new t(n.node,n.offset,o.node,o.offset),r=i.toString(),a=e.exec(r);return a&&a[0].length===r.length?i:null}},{key:"bookmark",value:function(t){return{s:{path:pt.makeOffsetPath(t,this.sc),offset:this.so},e:{path:pt.makeOffsetPath(t,this.ec),offset:this.eo}}}},{key:"paraBookmark",value:function(t){return{s:{path:C.tail(pt.makeOffsetPath(C.head(t),this.sc)),offset:this.so},e:{path:C.tail(pt.makeOffsetPath(C.last(t),this.ec)),offset:this.eo}}}},{key:"getClientRects",value:function(){return this.nativeRange().getClientRects()}}],o&&wt(e.prototype,o),n&&wt(e,n),Object.defineProperty(e,"prototype",{writable:!1}),e;var e,o,n}();const Et={create:function(t,e,o,n){if(4===arguments.length)return new Tt(t,e,o,n);if(2===arguments.length)return new Tt(t,e,o=t,n=e);var i=this.createFromSelection();if(!i&&1===arguments.length){var r=arguments[0];return pt.isEditable(r)&&(r=r.lastChild),this.createFromBodyElement(r,pt.emptyPara===arguments[0].innerHTML)}return i},createFromBodyElement:function(t){var e=arguments.length>1&&void 0!==arguments[1]&&arguments[1];return this.createFromNode(t).collapse(e)},createFromSelection:function(){var t,e,o,n;if(m.isW3CRangeSupport){var i=document.getSelection();if(!i||0===i.rangeCount)return null;if(pt.isBody(i.anchorNode))return null;var r=i.getRangeAt(0);t=r.startContainer,e=r.startOffset,o=r.endContainer,n=r.endOffset}else{var a=document.selection.createRange(),s=a.duplicate();s.collapse(!1);var l=a;l.collapse(!0);var c=St(l,!0),u=St(s,!1);pt.isText(c.node)&&pt.isLeftEdgePoint(c)&&pt.isTextNode(u.node)&&pt.isRightEdgePoint(u)&&u.node.nextSibling===c.node&&(c=u),t=c.cont,e=c.offset,o=u.cont,n=u.offset}return new Tt(t,e,o,n)},createFromNode:function(t){var e=t,o=0,n=t,i=pt.nodeLength(n);return pt.isVoid(e)&&(o=pt.listPrev(e).length-1,e=e.parentNode),pt.isBR(n)?(i=pt.listPrev(n).length-1,n=n.parentNode):pt.isVoid(n)&&(i=pt.listPrev(n).length,n=n.parentNode),this.create(e,o,n,i)},createFromNodeBefore:function(t){return this.createFromNode(t).collapse(!0)},createFromNodeAfter:function(t){return this.createFromNode(t).collapse()},createFromBookmark:function(t,e){var o=pt.fromOffsetPath(t,e.s.path),n=e.s.offset,i=pt.fromOffsetPath(t,e.e.path),r=e.e.offset;return new Tt(o,n,i,r)},createFromParaBookmark:function(t,e){var o=t.s.offset,n=t.e.offset,i=pt.fromOffsetPath(C.head(e),t.s.path),r=pt.fromOffsetPath(C.last(e),t.e.path);return new Tt(i,o,r,n)}};var Pt={BACKSPACE:8,TAB:9,ENTER:13,ESCAPE:27,SPACE:32,DELETE:46,LEFT:37,UP:38,RIGHT:39,DOWN:40,NUM0:48,NUM1:49,NUM2:50,NUM3:51,NUM4:52,NUM5:53,NUM6:54,NUM7:55,NUM8:56,B:66,E:69,I:73,J:74,K:75,L:76,R:82,S:83,U:85,V:86,Y:89,Z:90,SLASH:191,LEFTBRACKET:219,BACKSLASH:220,RIGHTBRACKET:221,HOME:36,END:35,PAGEUP:33,PAGEDOWN:34};const Nt={isEdit:function(t){return C.contains([Pt.BACKSPACE,Pt.TAB,Pt.ENTER,Pt.SPACE,Pt.DELETE],t)},isRemove:function(t){return C.contains([Pt.BACKSPACE,Pt.DELETE],t)},isMove:function(t){return C.contains([Pt.LEFT,Pt.UP,Pt.RIGHT,Pt.DOWN],t)},isNavigation:function(t){return C.contains([Pt.HOME,Pt.END,Pt.PAGEUP,Pt.PAGEDOWN],t)},nameFromCode:g.invertObject(Pt),code:Pt};function It(t){return It="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},It(t)}function $t(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,Rt(n.key),n)}}function Rt(t){var e=function(t,e){if("object"!=It(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=It(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==It(e)?e:e+""}var At=function(){return t=function t(e){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.stack=[],this.stackOffset=-1,this.context=e,this.$editable=e.layoutInfo.editable,this.editable=this.$editable[0]},(e=[{key:"makeSnapshot",value:function(){var t=Et.create(this.editable);return{contents:this.$editable.html(),bookmark:t&&t.isOnEditable()?t.bookmark(this.editable):{s:{path:[],offset:0},e:{path:[],offset:0}}}}},{key:"applySnapshot",value:function(t){null!==t.contents&&this.$editable.html(t.contents),null!==t.bookmark&&Et.createFromBookmark(this.editable,t.bookmark).select()}},{key:"rewind",value:function(){this.$editable.html()!==this.stack[this.stackOffset].contents&&this.recordUndo(),this.stackOffset=0,this.applySnapshot(this.stack[this.stackOffset])}},{key:"commit",value:function(){this.stack=[],this.stackOffset=-1,this.recordUndo()}},{key:"reset",value:function(){this.stack=[],this.stackOffset=-1,this.$editable.html(""),this.recordUndo()}},{key:"undo",value:function(){this.$editable.html()!==this.stack[this.stackOffset].contents&&this.recordUndo(),this.stackOffset>0&&(this.stackOffset--,this.applySnapshot(this.stack[this.stackOffset]))}},{key:"redo",value:function(){this.stack.length-1>this.stackOffset&&(this.stackOffset++,this.applySnapshot(this.stack[this.stackOffset]))}},{key:"recordUndo",value:function(){this.stackOffset++,this.stack.length>this.stackOffset&&(this.stack=this.stack.slice(0,this.stackOffset)),this.stack.push(this.makeSnapshot()),this.stack.length>this.context.options.historyLimit&&(this.stack.shift(),this.stackOffset-=1)}}])&&$t(t.prototype,e),o&&$t(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function Lt(t){return Lt="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},Lt(t)}function Ft(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,Dt(n.key),n)}}function Dt(t){var e=function(t,e){if("object"!=Lt(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=Lt(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==Lt(e)?e:e+""}var Ht=function(){return t=function t(){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t)},e=[{key:"jQueryCSS",value:function(t,e){var o={};return r().each(e,(function(e,n){o[n]=t.css(n)})),o}},{key:"fromNode",value:function(t){var e=this.jQueryCSS(t,["font-family","font-size","text-align","list-style-type","line-height"])||{},o=t[0].style.fontSize||e["font-size"];return e["font-size"]=parseInt(o,10),e["font-size-unit"]=o.match(/[a-z%]+$/),e}},{key:"stylePara",value:function(t,e){r().each(t.nodes(pt.isPara,{includeAncestor:!0}),(function(t,o){r()(o).css(e)}))}},{key:"styleNodes",value:function(t,e){t=t.splitText();var o=e&&e.nodeName||"SPAN",n=!(!e||!e.expandClosestSibling),i=!(!e||!e.onlyPartialContains);if(t.isCollapsed())return[t.insertNode(pt.create(o))];var a=pt.makePredByNodeName(o),s=t.nodes(pt.isText,{fullyContains:!0}).map((function(t){return pt.singleChildAncestor(t,a)||pt.wrap(t,o)}));if(n){if(i){var l=t.nodes();a=g.and(a,(function(t){return C.contains(l,t)}))}return s.map((function(t){var e=pt.withClosestSiblings(t,a),o=C.head(e),n=C.tail(e);return r().each(n,(function(t,e){pt.appendChildNodes(o,e.childNodes),pt.remove(e)})),C.head(e)}))}return s}},{key:"current",value:function(t){var e=r()(pt.isElement(t.sc)?t.sc:t.sc.parentNode),o=this.fromNode(e);try{o=r().extend(o,{"font-bold":document.queryCommandState("bold")?"bold":"normal","font-italic":document.queryCommandState("italic")?"italic":"normal","font-underline":document.queryCommandState("underline")?"underline":"normal","font-subscript":document.queryCommandState("subscript")?"subscript":"normal","font-superscript":document.queryCommandState("superscript")?"superscript":"normal","font-strikethrough":document.queryCommandState("strikethrough")?"strikethrough":"normal","font-family":document.queryCommandValue("fontname")||o["font-family"]})}catch(t){}if(t.isOnList()){var n=["circle","disc","disc-leading-zero","square"].indexOf(o["list-style-type"])>-1;o["list-style"]=n?"unordered":"ordered"}else o["list-style"]="none";var i=pt.ancestor(t.sc,pt.isPara);if(i&&i.style["line-height"])o["line-height"]=i.style.lineHeight;else{var a=parseInt(o["line-height"],10)/parseInt(o["font-size"],10);o["line-height"]=a.toFixed(1)}return o.anchor=t.isOnAnchor()&&pt.ancestor(t.sc,pt.isAnchor),o.ancestors=pt.listAncestor(t.sc,pt.isEditable),o.range=t,o}}],e&&Ft(t.prototype,e),o&&Ft(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function jt(t){return jt="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},jt(t)}function zt(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,Bt(n.key),n)}}function Bt(t){var e=function(t,e){if("object"!=jt(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=jt(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==jt(e)?e:e+""}var Ot=function(){return t=function t(){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t)},e=[{key:"insertOrderedList",value:function(t){this.toggleList("OL",t)}},{key:"insertUnorderedList",value:function(t){this.toggleList("UL",t)}},{key:"indent",value:function(t){var e=this,o=Et.create(t).wrapBodyInlineWithPara(),n=o.nodes(pt.isPara,{includeAncestor:!0}),i=C.clusterBy(n,g.peq2("parentNode"));r().each(i,(function(t,o){var n=C.head(o);if(pt.isLi(n)){var i=e.findList(n.previousSibling);i?o.map((function(t){return i.appendChild(t)})):(e.wrapList(o,n.parentNode.nodeName),o.map((function(t){return t.parentNode})).map((function(t){return e.appendToPrevious(t)})))}else r().each(o,(function(t,e){r()(e).css("marginLeft",(function(t,e){return(parseInt(e,10)||0)+25}))}))})),o.select()}},{key:"outdent",value:function(t){var e=this,o=Et.create(t).wrapBodyInlineWithPara(),n=o.nodes(pt.isPara,{includeAncestor:!0}),i=C.clusterBy(n,g.peq2("parentNode"));r().each(i,(function(t,o){var n=C.head(o);pt.isLi(n)?e.releaseList([o]):r().each(o,(function(t,e){r()(e).css("marginLeft",(function(t,e){return(e=parseInt(e,10)||0)>25?e-25:""}))}))})),o.select()}},{key:"toggleList",value:function(t,e){var o=this,n=Et.create(e).wrapBodyInlineWithPara(),i=n.nodes(pt.isPara,{includeAncestor:!0}),a=n.paraBookmark(i),s=C.clusterBy(i,g.peq2("parentNode"));if(C.find(i,pt.isPurePara)){var l=[];r().each(s,(function(e,n){l=l.concat(o.wrapList(n,t))})),i=l}else{var c=n.nodes(pt.isList,{includeAncestor:!0}).filter((function(e){return!r().nodeName(e,t)}));c.length?r().each(c,(function(e,o){pt.replace(o,t)})):i=this.releaseList(s,!0)}Et.createFromParaBookmark(a,i).select()}},{key:"wrapList",value:function(t,e){var o=C.head(t),n=C.last(t),i=pt.isList(o.previousSibling)&&o.previousSibling,r=pt.isList(n.nextSibling)&&n.nextSibling,a=i||pt.insertAfter(pt.create(e||"UL"),n);return t=t.map((function(t){return pt.isPurePara(t)?pt.replace(t,"LI"):t})),pt.appendChildNodes(a,t,!0),r&&(pt.appendChildNodes(a,C.from(r.childNodes),!0),pt.remove(r)),t}},{key:"releaseList",value:function(t,e){var o=this,n=[];return r().each(t,(function(t,i){var a=C.head(i),s=C.last(i),l=e?pt.lastAncestor(a,pt.isList):a.parentNode,c=l.parentNode;if("LI"===l.parentNode.nodeName)i.map((function(t){var e=o.findNextSiblings(t);c.nextSibling?c.parentNode.insertBefore(t,c.nextSibling):c.parentNode.appendChild(t),e.length&&(o.wrapList(e,l.nodeName),t.appendChild(e[0].parentNode))})),0===l.children.length&&c.removeChild(l),0===c.childNodes.length&&c.parentNode.removeChild(c);else{var u=l.childNodes.length>1?pt.splitTree(l,{node:s.parentNode,offset:pt.position(s)+1},{isSkipPaddingBlankHTML:!0}):null,d=pt.splitTree(l,{node:a.parentNode,offset:pt.position(a)},{isSkipPaddingBlankHTML:!0});i=e?pt.listDescendant(d,pt.isLi):C.from(d.childNodes).filter(pt.isLi),!e&&pt.isList(l.parentNode)||(i=i.map((function(t){return pt.replace(t,"P")}))),r().each(C.from(i).reverse(),(function(t,e){pt.insertAfter(e,l)}));var f=C.compact([l,d,u]);r().each(f,(function(t,e){var o=[e].concat(pt.listDescendant(e,pt.isList));r().each(o.reverse(),(function(t,e){pt.nodeLength(e)||pt.remove(e,!0)}))}))}n=n.concat(i)})),n}},{key:"appendToPrevious",value:function(t){return t.previousSibling?pt.appendChildNodes(t.previousSibling,[t]):this.wrapList([t],"LI")}},{key:"findList",value:function(t){return t?C.find(t.children,(function(t){return["OL","UL"].indexOf(t.nodeName)>-1})):null}},{key:"findNextSiblings",value:function(t){for(var e=[];t.nextSibling;)e.push(t.nextSibling),t=t.nextSibling;return e}}],e&&zt(t.prototype,e),o&&zt(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function Mt(t){return Mt="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},Mt(t)}function Ut(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,Wt(n.key),n)}}function Wt(t){var e=function(t,e){if("object"!=Mt(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=Mt(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==Mt(e)?e:e+""}var Kt=function(){return t=function t(e){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.bullet=new Ot,this.options=e.options},e=[{key:"insertTab",value:function(t,e){var o=pt.createText(new Array(e+1).join(pt.NBSP_CHAR));(t=t.deleteContents()).insertNode(o,!0),(t=Et.create(o,e)).select()}},{key:"insertParagraph",value:function(t,e){e=(e=(e=e||Et.create(t)).deleteContents()).wrapBodyInlineWithPara();var o,n=pt.ancestor(e.sc,pt.isPara);if(n){if(pt.isLi(n)&&(pt.isEmpty(n)||pt.deepestChildIsEmpty(n)))return void this.bullet.toggleList(n.parentNode.nodeName);var i=null;if(1===this.options.blockquoteBreakingLevel?i=pt.ancestor(n,pt.isBlockquote):2===this.options.blockquoteBreakingLevel&&(i=pt.lastAncestor(n,pt.isBlockquote)),i){o=r()(pt.emptyPara)[0],pt.isRightEdgePoint(e.getStartPoint())&&pt.isBR(e.sc.nextSibling)&&r()(e.sc.nextSibling).remove();var a=pt.splitTree(i,e.getStartPoint(),{isDiscardEmptySplits:!0});a?a.parentNode.insertBefore(o,a):pt.insertAfter(o,i)}else{o=pt.splitTree(n,e.getStartPoint());var s=pt.listDescendant(n,pt.isEmptyAnchor);s=s.concat(pt.listDescendant(o,pt.isEmptyAnchor)),r().each(s,(function(t,e){pt.remove(e)})),(pt.isHeading(o)||pt.isPre(o)||pt.isCustomStyleTag(o))&&pt.isEmpty(o)&&(o=pt.replace(o,"p"))}}else{var l=e.sc.childNodes[e.so];o=r()(pt.emptyPara)[0],l?e.sc.insertBefore(o,l):e.sc.appendChild(o)}Et.create(o,0).normalize().select().scrollIntoView(t)}}],e&&Ut(t.prototype,e),o&&Ut(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function qt(t){return qt="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},qt(t)}function Vt(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,_t(n.key),n)}}function _t(t){var e=function(t,e){if("object"!=qt(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=qt(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==qt(e)?e:e+""}var Gt=function t(e,o,n,i){var r={colPos:0,rowPos:0},a=[],s=[];function l(t,e,o,n,i,r,s){var l={baseRow:o,baseCell:n,isRowSpan:i,isColSpan:r,isVirtual:s};a[t]||(a[t]=[]),a[t][e]=l}function c(t,e,o,n){return{baseCell:t.baseCell,action:e,virtualTable:{rowIndex:o,cellIndex:n}}}function u(t,e){if(!a[t])return e;if(!a[t][e])return e;for(var o=e;a[t][o];)if(o++,!a[t][o])return o}function d(t,e){var o=u(t.rowIndex,e.cellIndex),n=e.colSpan>1,i=e.rowSpan>1,a=t.rowIndex===r.rowPos&&e.cellIndex===r.colPos;l(t.rowIndex,o,t,e,i,n,!1);var s=e.attributes.rowSpan?parseInt(e.attributes.rowSpan.value,10):0;if(s>1)for(var c=1;c<s;c++){var d=t.rowIndex+c;f(d,o,e,a),l(d,o,t,e,!0,n,!0)}var h=e.attributes.colSpan?parseInt(e.attributes.colSpan.value,10):0;if(h>1)for(var p=1;p<h;p++){var m=u(t.rowIndex,o+p);f(t.rowIndex,m,e,a),l(t.rowIndex,m,t,e,i,!0,!0)}}function f(t,e,o,n){t===r.rowPos&&r.colPos>=o.cellIndex&&o.cellIndex<=e&&!n&&r.colPos++}function h(e){switch(o){case t.where.Column:if(e.isColSpan)return t.resultAction.SubtractSpanCount;break;case t.where.Row:if(!e.isVirtual&&e.isRowSpan)return t.resultAction.AddCell;if(e.isRowSpan)return t.resultAction.SubtractSpanCount}return t.resultAction.RemoveCell}function p(e){switch(o){case t.where.Column:if(e.isColSpan)return t.resultAction.SumSpanCount;if(e.isRowSpan&&e.isVirtual)return t.resultAction.Ignore;break;case t.where.Row:if(e.isRowSpan)return t.resultAction.SumSpanCount;if(e.isColSpan&&e.isVirtual)return t.resultAction.Ignore}return t.resultAction.AddCell}this.getActionList=function(){for(var e=o===t.where.Row?r.rowPos:-1,i=o===t.where.Column?r.colPos:-1,l=0,u=!0;u;){var d=e>=0?e:l,f=i>=0?i:l,m=a[d];if(!m)return u=!1,s;var v=m[f];if(!v)return u=!1,s;var g=t.resultAction.Ignore;switch(n){case t.requestAction.Add:g=p(v);break;case t.requestAction.Delete:g=h(v)}s.push(c(v,g,d,f)),l++}return s},e&&e.tagName&&("td"===e.tagName.toLowerCase()||"th"===e.tagName.toLowerCase())&&(r.colPos=e.cellIndex,e.parentElement&&e.parentElement.tagName&&"tr"===e.parentElement.tagName.toLowerCase()&&(r.rowPos=e.parentElement.rowIndex)),function(){for(var t=i.rows,e=0;e<t.length;e++)for(var o=t[e].cells,n=0;n<o.length;n++)d(t[e],o[n])}()};Gt.where={Row:0,Column:1},Gt.requestAction={Add:0,Delete:1},Gt.resultAction={Ignore:0,SubtractSpanCount:1,RemoveCell:2,AddCell:3,SumSpanCount:4};var Zt=function(){return t=function t(){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t)},e=[{key:"tab",value:function(t,e){var o=pt.ancestor(t.commonAncestor(),pt.isCell),n=pt.ancestor(o,pt.isTable),i=pt.listDescendant(n,pt.isCell),r=C[e?"prev":"next"](i,o);r&&Et.create(r,0).select()}},{key:"addRow",value:function(t,e){for(var o=pt.ancestor(t.commonAncestor(),pt.isCell),n=r()(o).closest("tr"),i=this.recoverAttributes(n),a=r()("<tr"+i+"></tr>"),s=new Gt(o,Gt.where.Row,Gt.requestAction.Add,r()(n).closest("table")[0]).getActionList(),l=0;l<s.length;l++){var c=s[l],u=this.recoverAttributes(c.baseCell);switch(c.action){case Gt.resultAction.AddCell:a.append("<td"+u+">"+pt.blank+"</td>");break;case Gt.resultAction.SumSpanCount:if("top"===e&&(c.baseCell.parent?c.baseCell.closest("tr").rowIndex:0)<=n[0].rowIndex){var d=r()("<div></div>").append(r()("<td"+u+">"+pt.blank+"</td>").removeAttr("rowspan")).html();a.append(d);break}var f=parseInt(c.baseCell.rowSpan,10);f++,c.baseCell.setAttribute("rowSpan",f)}}if("top"===e)n.before(a);else{if(o.rowSpan>1){var h=n[0].rowIndex+(o.rowSpan-2);return void r()(r()(n).parent().find("tr")[h]).after(r()(a))}n.after(a)}}},{key:"addCol",value:function(t,e){var o=pt.ancestor(t.commonAncestor(),pt.isCell),n=r()(o).closest("tr");r()(n).siblings().push(n);for(var i=new Gt(o,Gt.where.Column,Gt.requestAction.Add,r()(n).closest("table")[0]).getActionList(),a=0;a<i.length;a++){var s=i[a],l=this.recoverAttributes(s.baseCell);switch(s.action){case Gt.resultAction.AddCell:"right"===e?r()(s.baseCell).after("<td"+l+">"+pt.blank+"</td>"):r()(s.baseCell).before("<td"+l+">"+pt.blank+"</td>");break;case Gt.resultAction.SumSpanCount:if("right"===e){var c=parseInt(s.baseCell.colSpan,10);c++,s.baseCell.setAttribute("colSpan",c)}else r()(s.baseCell).before("<td"+l+">"+pt.blank+"</td>")}}}},{key:"recoverAttributes",value:function(t){var e="";if(!t)return e;for(var o=t.attributes||[],n=0;n<o.length;n++)"id"!==o[n].name.toLowerCase()&&o[n].specified&&(e+=" "+o[n].name+"='"+o[n].value+"'");return e}},{key:"deleteRow",value:function(t){for(var e=pt.ancestor(t.commonAncestor(),pt.isCell),o=r()(e).closest("tr"),n=o.children("td, th").index(r()(e)),i=o[0].rowIndex,a=new Gt(e,Gt.where.Row,Gt.requestAction.Delete,r()(o).closest("table")[0]).getActionList(),s=0;s<a.length;s++)if(a[s]){var l=a[s].baseCell,c=a[s].virtualTable,u=l.rowSpan&&l.rowSpan>1,d=u?parseInt(l.rowSpan,10):0;switch(a[s].action){case Gt.resultAction.Ignore:continue;case Gt.resultAction.AddCell:var f=o.next("tr")[0];if(!f)continue;var h=o[0].cells[n];u&&(d>2?(d--,f.insertBefore(h,f.cells[n]),f.cells[n].setAttribute("rowSpan",d),f.cells[n].innerHTML=""):2===d&&(f.insertBefore(h,f.cells[n]),f.cells[n].removeAttribute("rowSpan"),f.cells[n].innerHTML=""));continue;case Gt.resultAction.SubtractSpanCount:u&&(d>2?(d--,l.setAttribute("rowSpan",d),c.rowIndex!==i&&l.cellIndex===n&&(l.innerHTML="")):2===d&&(l.removeAttribute("rowSpan"),c.rowIndex!==i&&l.cellIndex===n&&(l.innerHTML="")));continue;case Gt.resultAction.RemoveCell:continue}}o.remove()}},{key:"deleteCol",value:function(t){for(var e=pt.ancestor(t.commonAncestor(),pt.isCell),o=r()(e).closest("tr"),n=o.children("td, th").index(r()(e)),i=new Gt(e,Gt.where.Column,Gt.requestAction.Delete,r()(o).closest("table")[0]).getActionList(),a=0;a<i.length;a++)if(i[a])switch(i[a].action){case Gt.resultAction.Ignore:continue;case Gt.resultAction.SubtractSpanCount:var s=i[a].baseCell;if(s.colSpan&&s.colSpan>1){var l=s.colSpan?parseInt(s.colSpan,10):0;l>2?(l--,s.setAttribute("colSpan",l),s.cellIndex===n&&(s.innerHTML="")):2===l&&(s.removeAttribute("colSpan"),s.cellIndex===n&&(s.innerHTML=""))}continue;case Gt.resultAction.RemoveCell:pt.remove(i[a].baseCell,!0);continue}}},{key:"createTable",value:function(t,e,o){for(var n,i=[],a=0;a<t;a++)i.push("<td>"+pt.blank+"</td>");n=i.join("");for(var s,l=[],c=0;c<e;c++)l.push("<tr>"+n+"</tr>");s=l.join("");var u=r()("<table>"+s+"</table>");return o&&o.tableClassName&&u.addClass(o.tableClassName),u[0]}},{key:"deleteTable",value:function(t){var e=pt.ancestor(t.commonAncestor(),pt.isCell);r()(e).closest("table").remove()}}],e&&Vt(t.prototype,e),o&&Vt(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function Yt(t){return Yt="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},Yt(t)}function Xt(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,Qt(n.key),n)}}function Qt(t){var e=function(t,e){if("object"!=Yt(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=Yt(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==Yt(e)?e:e+""}var Jt=/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,te=/^(\+?\d{1,3}[\s-]?)?(\d{1,4})[\s-]?(\d{1,4})[\s-]?(\d{1,4})$/,ee=/^([A-Za-z][A-Za-z0-9+-.]*\:|#|\/)/,oe=function(){return t=function t(e){var o=this;!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.context=e,this.$note=e.layoutInfo.note,this.$editor=e.layoutInfo.editor,this.$editable=e.layoutInfo.editable,this.options=e.options,this.lang=this.options.langInfo,this.editable=this.$editable[0],this.lastRange=null,this.snapshot=null,this.style=new Ht,this.table=new Zt,this.typing=new Kt(e),this.bullet=new Ot,this.history=new At(e),this.context.memo("help.escape",this.lang.help.escape),this.context.memo("help.undo",this.lang.help.undo),this.context.memo("help.redo",this.lang.help.redo),this.context.memo("help.tab",this.lang.help.tab),this.context.memo("help.untab",this.lang.help.untab),this.context.memo("help.insertParagraph",this.lang.help.insertParagraph),this.context.memo("help.insertOrderedList",this.lang.help.insertOrderedList),this.context.memo("help.insertUnorderedList",this.lang.help.insertUnorderedList),this.context.memo("help.indent",this.lang.help.indent),this.context.memo("help.outdent",this.lang.help.outdent),this.context.memo("help.formatPara",this.lang.help.formatPara),this.context.memo("help.insertHorizontalRule",this.lang.help.insertHorizontalRule),this.context.memo("help.fontName",this.lang.help.fontName);for(var n=["bold","italic","underline","strikethrough","superscript","subscript","justifyLeft","justifyCenter","justifyRight","justifyFull","formatBlock","removeFormat","backColor"],i=0,a=n.length;i<a;i++)this[n[i]]=function(t){return function(e){o.beforeCommand(),document.execCommand(t,!1,e),o.afterCommand(!0)}}(n[i]),this.context.memo("help."+n[i],this.lang.help[n[i]]);this.fontName=this.wrapCommand((function(t){return o.fontStyling("font-family",m.validFontName(t))})),this.fontSize=this.wrapCommand((function(t){var e=o.currentStyle()["font-size-unit"];return o.fontStyling("font-size",t+e)})),this.fontSizeUnit=this.wrapCommand((function(t){var e=o.currentStyle()["font-size"];return o.fontStyling("font-size",e+t)}));for(var s=1;s<=6;s++)this["formatH"+s]=function(t){return function(){o.formatBlock("H"+t)}}(s),this.context.memo("help.formatH"+s,this.lang.help["formatH"+s]);this.insertParagraph=this.wrapCommand((function(){o.typing.insertParagraph(o.editable)})),this.insertOrderedList=this.wrapCommand((function(){o.bullet.insertOrderedList(o.editable)})),this.insertUnorderedList=this.wrapCommand((function(){o.bullet.insertUnorderedList(o.editable)})),this.indent=this.wrapCommand((function(){o.bullet.indent(o.editable)})),this.outdent=this.wrapCommand((function(){o.bullet.outdent(o.editable)})),this.insertNode=this.wrapCommand((function(t){o.isLimited(r()(t).text().length)||(o.getLastRange().insertNode(t),o.setLastRange(Et.createFromNodeAfter(t).select()))})),this.insertText=this.wrapCommand((function(t){if(!o.isLimited(t.length)){var e=o.getLastRange().insertNode(pt.createText(t));o.setLastRange(Et.create(e,pt.nodeLength(e)).select())}})),this.pasteHTML=this.wrapCommand((function(t){if(!o.isLimited(t.length)){t=o.context.invoke("codeview.purify",t);var e=o.getLastRange().pasteHTML(t);o.setLastRange(Et.createFromNodeAfter(C.last(e)).select())}})),this.formatBlock=this.wrapCommand((function(t,e){var n=o.options.callbacks.onApplyCustomStyle;n?n.call(o,e,o.context,o.onFormatBlock):o.onFormatBlock(t,e)})),this.insertHorizontalRule=this.wrapCommand((function(){var t=o.getLastRange().insertNode(pt.create("HR"));t.nextSibling&&o.setLastRange(Et.create(t.nextSibling,0).normalize().select())})),this.lineHeight=this.wrapCommand((function(t){o.style.stylePara(o.getLastRange(),{lineHeight:t})})),this.createLink=this.wrapCommand((function(t){var e=[],n=t.url,i=t.text,a=t.isNewWindow,s=o.options.linkAddNoReferrer,l=o.options.linkAddNoOpener,c=t.range||o.getLastRange(),u=i.length-c.toString().length;if(!(u>0&&o.isLimited(u))){var d=c.toString()!==i;"string"==typeof n&&(n=n.trim()),n=o.options.onCreateLink?o.options.onCreateLink(n):o.checkLinkUrl(n);var f=[];if(d){var h=(c=c.deleteContents()).insertNode(r()("<A></A>").text(i)[0]);f.push(h)}else f=o.style.styleNodes(c,{nodeName:"A",expandClosestSibling:!0,onlyPartialContains:!0});r().each(f,(function(t,o){r()(o).attr("href",n),a?(r()(o).attr("target","_blank"),s&&e.push("noreferrer"),l&&e.push("noopener"),e.length&&r()(o).attr("rel",e.join(" "))):r()(o).removeAttr("target")})),o.setLastRange(o.createRangeFromList(f).select())}})),this.color=this.wrapCommand((function(t){var e=t.foreColor,o=t.backColor;e&&document.execCommand("foreColor",!1,e),o&&document.execCommand("backColor",!1,o)})),this.foreColor=this.wrapCommand((function(t){document.execCommand("foreColor",!1,t)})),this.insertTable=this.wrapCommand((function(t){var e=t.split("x");o.getLastRange().deleteContents().insertNode(o.table.createTable(e[0],e[1],o.options))})),this.removeMedia=this.wrapCommand((function(){var t=r()(o.restoreTarget()).parent();t.closest("figure").length?t.closest("figure").remove():t=r()(o.restoreTarget()).detach(),o.setLastRange(Et.createFromSelection(t).select()),o.context.triggerEvent("media.delete",t,o.$editable)})),this.floatMe=this.wrapCommand((function(t){var e=r()(o.restoreTarget());e.toggleClass("note-float-left","left"===t),e.toggleClass("note-float-right","right"===t),e.css("float","none"===t?"":t)})),this.resize=this.wrapCommand((function(t){var e=r()(o.restoreTarget());0===(t=parseFloat(t))?e.css("width",""):e.css({width:100*t+"%",height:""})}))},e=[{key:"initialize",value:function(){var t=this;this.$editable.on("keydown",(function(e){if(e.keyCode===Nt.code.ENTER&&t.context.triggerEvent("enter",e),t.context.triggerEvent("keydown",e),t.snapshot=t.history.makeSnapshot(),t.hasKeyShortCut=!1,e.isDefaultPrevented()||(t.options.shortcuts?t.hasKeyShortCut=t.handleKeyMap(e):t.preventDefaultEditableShortCuts(e)),t.isLimited(1,e)){var o=t.getLastRange();if(o.eo-o.so==0)return!1}t.setLastRange(),t.options.recordEveryKeystroke&&!1===t.hasKeyShortCut&&t.history.recordUndo()})).on("keyup",(function(e){t.setLastRange(),t.context.triggerEvent("keyup",e)})).on("focus",(function(e){t.setLastRange(),t.context.triggerEvent("focus",e)})).on("blur",(function(e){t.context.triggerEvent("blur",e)})).on("mousedown",(function(e){t.context.triggerEvent("mousedown",e)})).on("mouseup",(function(e){t.setLastRange(),t.history.recordUndo(),t.context.triggerEvent("mouseup",e)})).on("scroll",(function(e){t.context.triggerEvent("scroll",e)})).on("paste",(function(e){t.setLastRange(),t.context.triggerEvent("paste",e)})).on("copy",(function(e){t.context.triggerEvent("copy",e)})).on("input",(function(){t.isLimited(0)&&t.snapshot&&t.history.applySnapshot(t.snapshot)})),this.$editable.attr("spellcheck",this.options.spellCheck),this.$editable.attr("autocorrect",this.options.spellCheck),this.options.disableGrammar&&this.$editable.attr("data-gramm",!1),this.$editable.html(pt.html(this.$note)||pt.emptyPara),this.$editable.on(m.inputEventName,g.debounce((function(){t.context.triggerEvent("change",t.$editable.html(),t.$editable)}),10)),this.$editable.on("focusin",(function(e){t.context.triggerEvent("focusin",e)})).on("focusout",(function(e){t.context.triggerEvent("focusout",e)})),this.options.airMode?this.options.overrideContextMenu&&this.$editor.on("contextmenu",(function(e){return t.context.triggerEvent("contextmenu",e),!1})):(this.options.width&&this.$editor.outerWidth(this.options.width),this.options.height&&this.$editable.outerHeight(this.options.height),this.options.maxHeight&&this.$editable.css("max-height",this.options.maxHeight),this.options.minHeight&&this.$editable.css("min-height",this.options.minHeight)),this.history.recordUndo(),this.setLastRange()}},{key:"destroy",value:function(){this.$editable.off()}},{key:"handleKeyMap",value:function(t){var e=this.options.keyMap[m.isMac?"mac":"pc"],o=[];t.metaKey&&o.push("CMD"),t.ctrlKey&&!t.altKey&&o.push("CTRL"),t.shiftKey&&o.push("SHIFT");var n=Nt.nameFromCode[t.keyCode];n&&o.push(n);var i=e[o.join("+")];if("TAB"!==n||this.options.tabDisable)if(i){if(!1!==this.context.invoke(i))return t.preventDefault(),!0}else Nt.isEdit(t.keyCode)&&(Nt.isRemove(t.keyCode)&&this.context.invoke("removed"),this.afterCommand());else this.afterCommand();return!1}},{key:"preventDefaultEditableShortCuts",value:function(t){(t.ctrlKey||t.metaKey)&&C.contains([66,73,85],t.keyCode)&&t.preventDefault()}},{key:"isLimited",value:function(t,e){return t=t||0,(void 0===e||!(Nt.isMove(e.keyCode)||Nt.isNavigation(e.keyCode)||e.ctrlKey||e.metaKey||C.contains([Nt.code.BACKSPACE,Nt.code.DELETE],e.keyCode)))&&this.options.maxTextLength>0&&this.$editable.text().length+t>this.options.maxTextLength}},{key:"checkLinkUrl",value:function(t){return Jt.test(t)?"mailto://"+t:te.test(t)?"tel://"+t:ee.test(t)?t:"http://"+t}},{key:"createRange",value:function(){return this.focus(),this.setLastRange(),this.getLastRange()}},{key:"createRangeFromList",value:function(t){var e=Et.createFromNodeBefore(C.head(t)).getStartPoint(),o=Et.createFromNodeAfter(C.last(t)).getEndPoint();return Et.create(e.node,e.offset,o.node,o.offset)}},{key:"setLastRange",value:function(t){t?this.lastRange=t:(this.lastRange=Et.create(this.editable),0===r()(this.lastRange.sc).closest(".note-editable").length&&(this.lastRange=Et.createFromBodyElement(this.editable)))}},{key:"getLastRange",value:function(){return this.lastRange||this.setLastRange(),this.lastRange}},{key:"saveRange",value:function(t){t&&this.getLastRange().collapse().select()}},{key:"restoreRange",value:function(){this.lastRange&&(this.lastRange.select(),this.focus())}},{key:"saveTarget",value:function(t){this.$editable.data("target",t)}},{key:"clearTarget",value:function(){this.$editable.removeData("target")}},{key:"restoreTarget",value:function(){return this.$editable.data("target")}},{key:"currentStyle",value:function(){var t=Et.create();return t&&(t=t.normalize()),t?this.style.current(t):this.style.fromNode(this.$editable)}},{key:"styleFromNode",value:function(t){return this.style.fromNode(t)}},{key:"undo",value:function(){this.context.triggerEvent("before.command",this.$editable.html()),this.history.undo(),this.context.triggerEvent("change",this.$editable.html(),this.$editable)}},{key:"commit",value:function(){this.context.triggerEvent("before.command",this.$editable.html()),this.history.commit(),this.context.triggerEvent("change",this.$editable.html(),this.$editable)}},{key:"redo",value:function(){this.context.triggerEvent("before.command",this.$editable.html()),this.history.redo(),this.context.triggerEvent("change",this.$editable.html(),this.$editable)}},{key:"beforeCommand",value:function(){this.context.triggerEvent("before.command",this.$editable.html()),document.execCommand("styleWithCSS",!1,this.options.styleWithCSS),this.focus()}},{key:"afterCommand",value:function(t){this.normalizeContent(),this.history.recordUndo(),t||this.context.triggerEvent("change",this.$editable.html(),this.$editable)}},{key:"tab",value:function(){var t=this.getLastRange();if(t.isCollapsed()&&t.isOnCell())this.table.tab(t);else{if(0===this.options.tabSize)return!1;this.isLimited(this.options.tabSize)||(this.beforeCommand(),this.typing.insertTab(t,this.options.tabSize),this.afterCommand())}}},{key:"untab",value:function(){var t=this.getLastRange();if(t.isCollapsed()&&t.isOnCell())this.table.tab(t,!0);else if(0===this.options.tabSize)return!1}},{key:"wrapCommand",value:function(t){return function(){this.beforeCommand(),t.apply(this,arguments),this.afterCommand()}}},{key:"removed",value:function(t,e,o){(t=Et.create()).isCollapsed()&&t.isOnCell()&&(o=(e=t.ec).tagName)&&1===e.childElementCount&&"BR"===e.childNodes[0].tagName&&("P"===o?e.remove():["TH","TD"].indexOf(o)>=0&&e.firstChild.remove())}},{key:"insertImage",value:function(t,e){var o,n=this;return(o=t,r().Deferred((function(t){var e=r()("<img>");e.one("load",(function(){e.off("error abort"),t.resolve(e)})).one("error abort",(function(){e.off("load").detach(),t.reject(e)})).css({display:"none"}).appendTo(document.body).attr("src",o)})).promise()).then((function(t){n.beforeCommand(),"function"==typeof e?e(t):("string"==typeof e&&t.attr("data-filename",e),t.css("width",Math.min(n.$editable.width(),t.width()))),t.show(),n.getLastRange().insertNode(t[0]),n.setLastRange(Et.createFromNodeAfter(t[0]).select()),n.afterCommand()})).fail((function(t){n.context.triggerEvent("image.upload.error",t)}))}},{key:"insertImagesAsDataURL",value:function(t){var e=this;r().each(t,(function(t,o){var n=o.name;e.options.maximumImageFileSize&&e.options.maximumImageFileSize<o.size?e.context.triggerEvent("image.upload.error",e.lang.image.maximumFileSizeError):function(t){return r().Deferred((function(e){r().extend(new FileReader,{onload:function(t){var o=t.target.result;e.resolve(o)},onerror:function(t){e.reject(t)}}).readAsDataURL(t)})).promise()}(o).then((function(t){return e.insertImage(t,n)})).fail((function(){e.context.triggerEvent("image.upload.error")}))}))}},{key:"insertImagesOrCallback",value:function(t){this.options.callbacks.onImageUpload?this.context.triggerEvent("image.upload",t):this.insertImagesAsDataURL(t)}},{key:"getSelectedText",value:function(){var t=this.getLastRange();return t.isOnAnchor()&&(t=Et.createFromNode(pt.ancestor(t.sc,pt.isAnchor))),t.toString()}},{key:"onFormatBlock",value:function(t,e){if(document.execCommand("FormatBlock",!1,m.isMSIE?"<"+t+">":t),e&&e.length&&(e[0].tagName.toUpperCase()!==t.toUpperCase()&&(e=e.find(t)),e&&e.length)){var o=this.createRange(),n=r()([o.sc,o.ec]).closest(t);n.removeClass();var i=e[0].className||"";i&&n.addClass(i)}}},{key:"formatPara",value:function(){this.formatBlock("P")}},{key:"fontStyling",value:function(t,e){var o=this.getLastRange();if(""!==o){var n=this.style.styleNodes(o);if(this.$editor.find(".note-status-output").html(""),r()(n).css(t,e),o.isCollapsed()){var i=C.head(n);i&&!pt.nodeLength(i)&&(i.innerHTML=pt.ZERO_WIDTH_NBSP_CHAR,Et.createFromNode(i.firstChild).select(),this.setLastRange(),this.$editable.data("bogus",i))}else o.select()}else{var a=r().now();this.$editor.find(".note-status-output").html('<div id="note-status-output-'+a+'" class="alert alert-info">'+this.lang.output.noSelection+"</div>"),setTimeout((function(){r()("#note-status-output-"+a).remove()}),5e3)}}},{key:"unlink",value:function(){var t=this.getLastRange();if(t.isOnAnchor()){var e=pt.ancestor(t.sc,pt.isAnchor);(t=Et.createFromNode(e)).select(),this.setLastRange(),this.beforeCommand(),document.execCommand("unlink"),this.afterCommand()}}},{key:"getLinkInfo",value:function(){this.hasFocus()||this.focus();var t=this.getLastRange().expand(pt.isAnchor),e=r()(C.head(t.nodes(pt.isAnchor))),o={range:t,text:t.toString(),url:e.length?e.attr("href"):""};return e.length&&(o.isNewWindow="_blank"===e.attr("target")),o}},{key:"addRow",value:function(t){var e=this.getLastRange(this.$editable);e.isCollapsed()&&e.isOnCell()&&(this.beforeCommand(),this.table.addRow(e,t),this.afterCommand())}},{key:"addCol",value:function(t){var e=this.getLastRange(this.$editable);e.isCollapsed()&&e.isOnCell()&&(this.beforeCommand(),this.table.addCol(e,t),this.afterCommand())}},{key:"deleteRow",value:function(){var t=this.getLastRange(this.$editable);t.isCollapsed()&&t.isOnCell()&&(this.beforeCommand(),this.table.deleteRow(t),this.afterCommand())}},{key:"deleteCol",value:function(){var t=this.getLastRange(this.$editable);t.isCollapsed()&&t.isOnCell()&&(this.beforeCommand(),this.table.deleteCol(t),this.afterCommand())}},{key:"deleteTable",value:function(){var t=this.getLastRange(this.$editable);t.isCollapsed()&&t.isOnCell()&&(this.beforeCommand(),this.table.deleteTable(t),this.afterCommand())}},{key:"resizeTo",value:function(t,e,o){var n;if(o){var i=t.y/t.x,r=e.data("ratio");n={width:r>i?t.x:t.y/r,height:r>i?t.x*r:t.y}}else n={width:t.x,height:t.y};e.css(n)}},{key:"hasFocus",value:function(){return this.$editable.is(":focus")}},{key:"focus",value:function(){this.hasFocus()||this.$editable.trigger("focus")}},{key:"isEmpty",value:function(){return pt.isEmpty(this.$editable[0])||pt.emptyPara===this.$editable.html()}},{key:"empty",value:function(){this.context.invoke("code",pt.emptyPara)}},{key:"normalizeContent",value:function(){this.$editable[0].normalize()}}],e&&Xt(t.prototype,e),o&&Xt(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function ne(t){return ne="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},ne(t)}function ie(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,re(n.key),n)}}function re(t){var e=function(t,e){if("object"!=ne(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=ne(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==ne(e)?e:e+""}var ae=function(){return t=function t(e){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.context=e,this.options=e.options,this.$editable=e.layoutInfo.editable},(e=[{key:"initialize",value:function(){this.$editable.on("paste",this.pasteByEvent.bind(this))}},{key:"pasteByEvent",value:function(t){var e=this;if(!this.context.isDisabled()){var o=t.originalEvent.clipboardData;if(o&&o.items&&o.items.length){var n=o.files,i=o.getData("Text");n.length>0&&this.options.allowClipboardImagePasting&&(this.context.invoke("editor.insertImagesOrCallback",n),t.preventDefault()),i.length>0&&this.context.invoke("editor.isLimited",i.length)&&t.preventDefault()}else if(window.clipboardData){var r=window.clipboardData.getData("text");this.context.invoke("editor.isLimited",r.length)&&t.preventDefault()}setTimeout((function(){e.context.invoke("editor.afterCommand")}),10)}}}])&&ie(t.prototype,e),o&&ie(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function se(t){return se="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},se(t)}function le(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,ce(n.key),n)}}function ce(t){var e=function(t,e){if("object"!=se(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=se(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==se(e)?e:e+""}var ue=function(){return t=function t(e){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.context=e,this.$eventListener=r()(document),this.$editor=e.layoutInfo.editor,this.$editable=e.layoutInfo.editable,this.options=e.options,this.lang=this.options.langInfo,this.documentEventHandlers={},this.$dropzone=r()(['<div class="note-dropzone">','<div class="note-dropzone-message"></div>',"</div>"].join("")).prependTo(this.$editor)},e=[{key:"initialize",value:function(){this.options.disableDragAndDrop?(this.documentEventHandlers.onDrop=function(t){t.preventDefault()},this.$eventListener=this.$dropzone,this.$eventListener.on("drop",this.documentEventHandlers.onDrop)):this.attachDragAndDropEvent()}},{key:"attachDragAndDropEvent",value:function(){var t=this,e=r()(),o=this.$dropzone.find(".note-dropzone-message");this.documentEventHandlers.onDragenter=function(n){var i=t.context.invoke("codeview.isActivated"),r=t.$editor.width()>0&&t.$editor.height()>0;i||e.length||!r||(t.$editor.addClass("dragover"),t.$dropzone.width(t.$editor.width()),t.$dropzone.height(t.$editor.height()),o.text(t.lang.image.dragImageHere)),e=e.add(n.target)},this.documentEventHandlers.onDragleave=function(o){(e=e.not(o.target)).length&&"BODY"!==o.target.nodeName||(e=r()(),t.$editor.removeClass("dragover"))},this.documentEventHandlers.onDrop=function(){e=r()(),t.$editor.removeClass("dragover")},this.$eventListener.on("dragenter",this.documentEventHandlers.onDragenter).on("dragleave",this.documentEventHandlers.onDragleave).on("drop",this.documentEventHandlers.onDrop),this.$dropzone.on("dragenter",(function(){t.$dropzone.addClass("hover"),o.text(t.lang.image.dropImage)})).on("dragleave",(function(){t.$dropzone.removeClass("hover"),o.text(t.lang.image.dragImageHere)})),this.$dropzone.on("drop",(function(e){var o=e.originalEvent.dataTransfer;e.preventDefault(),o&&o.files&&o.files.length?(t.$editable.trigger("focus"),t.context.invoke("editor.insertImagesOrCallback",o.files)):r().each(o.types,(function(e,n){if(!(n.toLowerCase().indexOf("_moz_")>-1)){var i=o.getData(n);n.toLowerCase().indexOf("text")>-1?t.context.invoke("editor.pasteHTML",i):r()(i).each((function(e,o){t.context.invoke("editor.insertNode",o)}))}}))})).on("dragover",!1)}},{key:"destroy",value:function(){var t=this;Object.keys(this.documentEventHandlers).forEach((function(e){t.$eventListener.off(e.slice(2).toLowerCase(),t.documentEventHandlers[e])})),this.documentEventHandlers={}}}],e&&le(t.prototype,e),o&&le(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function de(t){return de="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},de(t)}function fe(t,e){var o="undefined"!=typeof Symbol&&t[Symbol.iterator]||t["@@iterator"];if(!o){if(Array.isArray(t)||(o=function(t,e){if(t){if("string"==typeof t)return he(t,e);var o={}.toString.call(t).slice(8,-1);return"Object"===o&&t.constructor&&(o=t.constructor.name),"Map"===o||"Set"===o?Array.from(t):"Arguments"===o||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(o)?he(t,e):void 0}}(t))||e&&t&&"number"==typeof t.length){o&&(t=o);var n=0,i=function(){};return{s:i,n:function(){return n>=t.length?{done:!0}:{done:!1,value:t[n++]}},e:function(t){throw t},f:i}}throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}var r,a=!0,s=!1;return{s:function(){o=o.call(t)},n:function(){var t=o.next();return a=t.done,t},e:function(t){s=!0,r=t},f:function(){try{a||null==o.return||o.return()}finally{if(s)throw r}}}}function he(t,e){(null==e||e>t.length)&&(e=t.length);for(var o=0,n=Array(e);o<e;o++)n[o]=t[o];return n}function pe(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,me(n.key),n)}}function me(t){var e=function(t,e){if("object"!=de(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=de(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==de(e)?e:e+""}var ve=function(){return t=function t(e){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.context=e,this.$editor=e.layoutInfo.editor,this.$editable=e.layoutInfo.editable,this.$codable=e.layoutInfo.codable,this.options=e.options,this.CodeMirrorConstructor=window.CodeMirror,this.options.codemirror.CodeMirrorConstructor&&(this.CodeMirrorConstructor=this.options.codemirror.CodeMirrorConstructor)},e=[{key:"sync",value:function(t){var e=this.isActivated(),o=this.CodeMirrorConstructor;e&&(t?o?this.$codable.data("cmEditor").getDoc().setValue(t):this.$codable.val(t):o&&this.$codable.data("cmEditor").save())}},{key:"initialize",value:function(){var t=this;this.$codable.on("keyup",(function(e){e.keyCode===Nt.code.ESCAPE&&t.deactivate()}))}},{key:"isActivated",value:function(){return this.$editor.hasClass("codeview")}},{key:"toggle",value:function(){this.isActivated()?this.deactivate():this.activate(),this.context.triggerEvent("codeview.toggled")}},{key:"purify",value:function(t){if(this.options.codeviewFilter&&(t=t.replace(this.options.codeviewFilterRegex,""),this.options.codeviewIframeFilter)){var e=this.options.codeviewIframeWhitelistSrc.concat(this.options.codeviewIframeWhitelistSrcBase);t=t.replace(/(<iframe.*?>.*?(?:<\/iframe>)?)/gi,(function(t){if(/<.+src(?==?('|"|\s)?)[\s\S]+src(?=('|"|\s)?)[^>]*?>/i.test(t))return"";var o,n=fe(e);try{for(n.s();!(o=n.n()).done;){var i=o.value;if(new RegExp('src="(https?:)?//'+i.replace(/[-\/\\^$*+?.()|[\]{}]/g,"\\$&")+'/(.+)"').test(t))return t}}catch(t){n.e(t)}finally{n.f()}return""}))}return t}},{key:"activate",value:function(){var t=this,e=this.CodeMirrorConstructor;if(this.$codable.val(pt.html(this.$editable,this.options.prettifyHtml)),this.$codable.height(this.$editable.height()),this.context.invoke("toolbar.updateCodeview",!0),this.context.invoke("airPopover.updateCodeview",!0),this.$editor.addClass("codeview"),this.$codable.trigger("focus"),e){var o=e.fromTextArea(this.$codable[0],this.options.codemirror);if(this.options.codemirror.tern){var n=new e.TernServer(this.options.codemirror.tern);o.ternServer=n,o.on("cursorActivity",(function(t){n.updateArgHints(t)}))}o.on("blur",(function(e){t.context.triggerEvent("blur.codeview",o.getValue(),e)})),o.on("change",(function(){t.context.triggerEvent("change.codeview",o.getValue(),o)})),o.setSize(null,this.$editable.outerHeight()),this.$codable.data("cmEditor",o)}else this.$codable.on("blur",(function(e){t.context.triggerEvent("blur.codeview",t.$codable.val(),e)})),this.$codable.on("input",(function(){t.context.triggerEvent("change.codeview",t.$codable.val(),t.$codable)}))}},{key:"deactivate",value:function(){if(this.CodeMirrorConstructor){var t=this.$codable.data("cmEditor");this.$codable.val(t.getValue()),t.toTextArea()}var e=this.purify(pt.value(this.$codable,this.options.prettifyHtml)||pt.emptyPara),o=this.$editable.html()!==e;this.$editable.html(e),this.$editable.height(this.options.height?this.$codable.height():"auto"),this.$editor.removeClass("codeview"),o&&this.context.triggerEvent("change",this.$editable.html(),this.$editable),this.$editable.trigger("focus"),this.context.invoke("toolbar.updateCodeview",!1),this.context.invoke("airPopover.updateCodeview",!1)}},{key:"destroy",value:function(){this.isActivated()&&this.deactivate()}}],e&&pe(t.prototype,e),o&&pe(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function ge(t){return ge="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},ge(t)}function be(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,ye(n.key),n)}}function ye(t){var e=function(t,e){if("object"!=ge(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=ge(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==ge(e)?e:e+""}var ke=function(){return t=function t(e){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.$document=r()(document),this.$statusbar=e.layoutInfo.statusbar,this.$editable=e.layoutInfo.editable,this.$codable=e.layoutInfo.codable,this.options=e.options},(e=[{key:"initialize",value:function(){var t=this;this.options.airMode||this.options.disableResizeEditor?this.destroy():this.$statusbar.on("mousedown touchstart",(function(e){e.preventDefault(),e.stopPropagation();var o=t.$editable.offset().top-t.$document.scrollTop(),n=t.$codable.offset().top-t.$document.scrollTop(),i=function(e){var i="mousemove"==e.type?e:e.originalEvent.touches[0],r=i.clientY-(o+24),a=i.clientY-(n+24);r=t.options.minheight>0?Math.max(r,t.options.minheight):r,r=t.options.maxHeight>0?Math.min(r,t.options.maxHeight):r,a=t.options.minheight>0?Math.max(a,t.options.minheight):a,a=t.options.maxHeight>0?Math.min(a,t.options.maxHeight):a,t.$editable.height(r),t.$codable.height(a)};t.$document.on("mousemove touchmove",i).one("mouseup touchend",(function(){t.$document.off("mousemove touchmove",i)}))}))}},{key:"destroy",value:function(){this.$statusbar.off(),this.$statusbar.addClass("locked")}}])&&be(t.prototype,e),o&&be(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function we(t){return we="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},we(t)}function Ce(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,Se(n.key),n)}}function Se(t){var e=function(t,e){if("object"!=we(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=we(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==we(e)?e:e+""}var xe=function(){return t=function t(e){var o=this;!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.context=e,this.$editor=e.layoutInfo.editor,this.$toolbar=e.layoutInfo.toolbar,this.$editable=e.layoutInfo.editable,this.$codable=e.layoutInfo.codable,this.$window=r()(window),this.$scrollbar=r()("html, body"),this.scrollbarClassName="note-fullscreen-body",this.onResize=function(){o.resizeTo({h:o.$window.height()-o.$toolbar.outerHeight()})}},(e=[{key:"resizeTo",value:function(t){this.$editable.css("height",t.h),this.$codable.css("height",t.h),this.$codable.data("cmeditor")&&this.$codable.data("cmeditor").setsize(null,t.h)}},{key:"toggle",value:function(){this.$editor.toggleClass("fullscreen");var t=this.isFullscreen();this.$scrollbar.toggleClass(this.scrollbarClassName,t),t?(this.$editable.data("orgHeight",this.$editable.css("height")),this.$editable.data("orgMaxHeight",this.$editable.css("maxHeight")),this.$editable.css("maxHeight",""),this.$window.on("resize",this.onResize).trigger("resize")):(this.$window.off("resize",this.onResize),this.resizeTo({h:this.$editable.data("orgHeight")}),this.$editable.css("maxHeight",this.$editable.css("orgMaxHeight"))),this.context.invoke("toolbar.updateFullscreen",t)}},{key:"isFullscreen",value:function(){return this.$editor.hasClass("fullscreen")}},{key:"destroy",value:function(){this.$scrollbar.removeClass(this.scrollbarClassName)}}])&&Ce(t.prototype,e),o&&Ce(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function Te(t){return Te="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},Te(t)}function Ee(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,Pe(n.key),n)}}function Pe(t){var e=function(t,e){if("object"!=Te(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=Te(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==Te(e)?e:e+""}var Ne=function(){return t=function t(e){var o=this;!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.context=e,this.$document=r()(document),this.$editingArea=e.layoutInfo.editingArea,this.options=e.options,this.lang=this.options.langInfo,this.events={"summernote.mousedown":function(t,e){o.update(e.target,e)&&e.preventDefault()},"summernote.keyup summernote.scroll summernote.change summernote.dialog.shown":function(){o.update()},"summernote.disable summernote.blur":function(){o.hide()},"summernote.codeview.toggled":function(){o.update()}}},e=[{key:"initialize",value:function(){var t=this;this.$handle=r()(['<div class="note-handle">','<div class="note-control-selection">','<div class="note-control-selection-bg"></div>','<div class="note-control-holder note-control-nw"></div>','<div class="note-control-holder note-control-ne"></div>','<div class="note-control-holder note-control-sw"></div>','<div class="',this.options.disableResizeImage?"note-control-holder":"note-control-sizing",' note-control-se"></div>',this.options.disableResizeImage?"":'<div class="note-control-selection-info"></div>',"</div>","</div>"].join("")).prependTo(this.$editingArea),this.$handle.on("mousedown",(function(e){if(pt.isControlSizing(e.target)){e.preventDefault(),e.stopPropagation();var o=t.$handle.find(".note-control-selection").data("target"),n=o.offset(),i=t.$document.scrollTop(),r=function(e){t.context.invoke("editor.resizeTo",{x:e.clientX-n.left,y:e.clientY-(n.top-i)},o,!e.shiftKey),t.update(o[0],e)};t.$document.on("mousemove",r).one("mouseup",(function(e){e.preventDefault(),t.$document.off("mousemove",r),t.context.invoke("editor.afterCommand")})),o.data("ratio")||o.data("ratio",o.height()/o.width())}})),this.$handle.on("wheel",(function(e){e.preventDefault(),t.update()}))}},{key:"destroy",value:function(){this.$handle.remove()}},{key:"update",value:function(t,e){if(this.context.isDisabled())return!1;var o=pt.isImg(t),n=this.$handle.find(".note-control-selection");if(this.context.invoke("imagePopover.update",t,e),o){var i=r()(t),a=this.$editingArea[0].getBoundingClientRect(),s=t.getBoundingClientRect();n.css({display:"block",left:s.left-a.left,top:s.top-a.top,width:s.width,height:s.height}).data("target",i);var l=new Image;l.src=i.attr("src");var c=s.width+"x"+s.height+" ("+this.lang.image.original+": "+l.width+"x"+l.height+")";n.find(".note-control-selection-info").text(c),this.context.invoke("editor.saveTarget",t)}else this.hide();return o}},{key:"hide",value:function(){this.context.invoke("editor.clearTarget"),this.$handle.children().hide()}}],e&&Ee(t.prototype,e),o&&Ee(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function Ie(t){return Ie="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},Ie(t)}function $e(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,Re(n.key),n)}}function Re(t){var e=function(t,e){if("object"!=Ie(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=Ie(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==Ie(e)?e:e+""}var Ae=/^([A-Za-z][A-Za-z0-9+-.]*\:[\/]{2}|tel:|mailto:[A-Z0-9._%+-]+@|xmpp:[A-Z0-9._%+-]+@)?(www\.)?(.+)$/i,Le=function(){return t=function t(e){var o=this;!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.context=e,this.options=e.options,this.$editable=e.layoutInfo.editable,this.events={"summernote.keyup":function(t,e){e.isDefaultPrevented()||o.handleKeyup(e)},"summernote.keydown":function(t,e){o.handleKeydown(e)}}},(e=[{key:"initialize",value:function(){this.lastWordRange=null}},{key:"destroy",value:function(){this.lastWordRange=null}},{key:"replace",value:function(){if(this.lastWordRange){var t=this.lastWordRange.toString(),e=t.match(Ae);if(e&&(e[1]||e[2])){var o=e[1]?t:"http://"+t,n=this.options.showDomainOnlyForAutolink?t.replace(/^(?:https?:\/\/)?(?:tel?:?)?(?:mailto?:?)?(?:xmpp?:?)?(?:www\.)?/i,"").split("/")[0]:t,i=r()("<a></a>").html(n).attr("href",o)[0];this.context.options.linkTargetBlank&&r()(i).attr("target","_blank"),this.lastWordRange.insertNode(i),this.lastWordRange=null,this.context.invoke("editor.focus"),this.context.triggerEvent("change",this.$editable.html(),this.$editable)}}}},{key:"handleKeydown",value:function(t){if(C.contains([Nt.code.ENTER,Nt.code.SPACE],t.keyCode)){var e=this.context.invoke("editor.createRange").getWordRange();this.lastWordRange=e}}},{key:"handleKeyup",value:function(t){(Nt.code.SPACE===t.keyCode||Nt.code.ENTER===t.keyCode&&!t.shiftKey)&&this.replace()}}])&&$e(t.prototype,e),o&&$e(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function Fe(t){return Fe="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},Fe(t)}function De(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,He(n.key),n)}}function He(t){var e=function(t,e){if("object"!=Fe(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=Fe(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==Fe(e)?e:e+""}var je=function(){return t=function t(e){var o=this;!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.$note=e.layoutInfo.note,this.events={"summernote.change":function(){o.$note.val(e.invoke("code"))}}},(e=[{key:"shouldInitialize",value:function(){return pt.isTextarea(this.$note[0])}}])&&De(t.prototype,e),o&&De(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function ze(t){return ze="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},ze(t)}function Be(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,Oe(n.key),n)}}function Oe(t){var e=function(t,e){if("object"!=ze(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=ze(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==ze(e)?e:e+""}var Me=function(){return t=function t(e){var o=this;!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.context=e,this.options=e.options.replace||{},this.keys=[Nt.code.ENTER,Nt.code.SPACE,Nt.code.PERIOD,Nt.code.COMMA,Nt.code.SEMICOLON,Nt.code.SLASH],this.previousKeydownCode=null,this.events={"summernote.keyup":function(t,e){e.isDefaultPrevented()||o.handleKeyup(e)},"summernote.keydown":function(t,e){o.handleKeydown(e)}}},(e=[{key:"shouldInitialize",value:function(){return!!this.options.match}},{key:"initialize",value:function(){this.lastWord=null}},{key:"destroy",value:function(){this.lastWord=null}},{key:"replace",value:function(){if(this.lastWord){var t=this,e=this.lastWord.toString();this.options.match(e,(function(e){if(e){var o="";if("string"==typeof e?o=pt.createText(e):e instanceof jQuery?o=e[0]:e instanceof Node&&(o=e),!o)return;t.lastWord.insertNode(o),t.lastWord=null,t.context.invoke("editor.focus")}}))}}},{key:"handleKeydown",value:function(t){if(this.previousKeydownCode&&C.contains(this.keys,this.previousKeydownCode))this.previousKeydownCode=t.keyCode;else{if(C.contains(this.keys,t.keyCode)){var e=this.context.invoke("editor.createRange").getWordRange();this.lastWord=e}this.previousKeydownCode=t.keyCode}}},{key:"handleKeyup",value:function(t){C.contains(this.keys,t.keyCode)&&this.replace()}}])&&Be(t.prototype,e),o&&Be(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function Ue(t){return Ue="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},Ue(t)}function We(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,Ke(n.key),n)}}function Ke(t){var e=function(t,e){if("object"!=Ue(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=Ue(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==Ue(e)?e:e+""}var qe=function(){return t=function t(e){var o=this;!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.context=e,this.$editingArea=e.layoutInfo.editingArea,this.options=e.options,!0===this.options.inheritPlaceholder&&(this.options.placeholder=this.context.$note.attr("placeholder")||this.options.placeholder),this.events={"summernote.init summernote.change":function(){o.update()},"summernote.codeview.toggled":function(){o.update()}}},(e=[{key:"shouldInitialize",value:function(){return!!this.options.placeholder}},{key:"initialize",value:function(){var t=this;this.$placeholder=r()('<div class="note-placeholder"></div>'),this.$placeholder.on("click",(function(){t.context.invoke("focus")})).html(this.options.placeholder).prependTo(this.$editingArea),this.update()}},{key:"destroy",value:function(){this.$placeholder.remove()}},{key:"update",value:function(){var t=!this.context.invoke("codeview.isActivated")&&this.context.invoke("editor.isEmpty");this.$placeholder.toggle(t)}}])&&We(t.prototype,e),o&&We(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function Ve(t){return Ve="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},Ve(t)}function _e(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,Ge(n.key),n)}}function Ge(t){var e=function(t,e){if("object"!=Ve(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=Ve(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==Ve(e)?e:e+""}var Ze=function(){return t=function t(e){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.ui=r().summernote.ui,this.context=e,this.$toolbar=e.layoutInfo.toolbar,this.options=e.options,this.lang=this.options.langInfo,this.invertedKeyMap=g.invertObject(this.options.keyMap[m.isMac?"mac":"pc"])},e=[{key:"representShortcut",value:function(t){var e=this.invertedKeyMap[t];return this.options.shortcuts&&e?(m.isMac&&(e=e.replace("CMD","⌘").replace("SHIFT","⇧"))," ("+(e=e.replace("BACKSLASH","\\").replace("SLASH","/").replace("LEFTBRACKET","[").replace("RIGHTBRACKET","]"))+")"):""}},{key:"button",value:function(t){return!this.options.tooltip&&t.tooltip&&delete t.tooltip,t.container=this.options.container,this.ui.button(t)}},{key:"initialize",value:function(){this.addToolbarButtons(),this.addImagePopoverButtons(),this.addLinkPopoverButtons(),this.addTablePopoverButtons(),this.fontInstalledMap={}}},{key:"destroy",value:function(){delete this.fontInstalledMap}},{key:"isFontInstalled",value:function(t){return Object.prototype.hasOwnProperty.call(this.fontInstalledMap,t)||(this.fontInstalledMap[t]=m.isFontInstalled(t)||C.contains(this.options.fontNamesIgnoreCheck,t)),this.fontInstalledMap[t]}},{key:"isFontDeservedToAdd",value:function(t){return""!==(t=t.toLowerCase())&&this.isFontInstalled(t)&&-1===m.genericFontFamilies.indexOf(t)}},{key:"colorPalette",value:function(t,e,o,n){var i=this;return this.ui.buttonGroup({className:"note-color "+t,children:[this.button({className:"note-current-color-button",contents:this.ui.icon(this.options.icons.font+" note-recent-color"),tooltip:e,click:function(t){var e=r()(t.currentTarget);o&&n?i.context.invoke("editor.color",{backColor:e.attr("data-backColor"),foreColor:e.attr("data-foreColor")}):o?i.context.invoke("editor.color",{backColor:e.attr("data-backColor")}):n&&i.context.invoke("editor.color",{foreColor:e.attr("data-foreColor")})},callback:function(t){var e=t.find(".note-recent-color");o&&(e.css("background-color",i.options.colorButton.backColor),t.attr("data-backColor",i.options.colorButton.backColor)),n?(e.css("color",i.options.colorButton.foreColor),t.attr("data-foreColor",i.options.colorButton.foreColor)):e.css("color","transparent")}}),this.button({className:"dropdown-toggle",contents:this.ui.dropdownButtonContents("",this.options),tooltip:this.lang.color.more,data:{toggle:"dropdown"}}),this.ui.dropdown({items:(o?['<div class="note-palette">','<div class="note-palette-title">'+this.lang.color.background+"</div>","<div>",'<button type="button" class="note-color-reset btn btn-light btn-default" data-event="backColor" data-value="transparent">',this.lang.color.transparent,"</button>","</div>",'<div class="note-holder" data-event="backColor">\x3c!-- back colors --\x3e</div>',"<div>",'<button type="button" class="note-color-select btn btn-light btn-default" data-event="openPalette" data-value="backColorPicker-'+this.options.id+'">',this.lang.color.cpSelect,"</button>",'<input type="color" id="backColorPicker-'+this.options.id+'" class="note-btn note-color-select-btn" value="'+this.options.colorButton.backColor+'" data-event="backColorPalette-'+this.options.id+'">',"</div>",'<div class="note-holder-custom" id="backColorPalette-'+this.options.id+'" data-event="backColor"></div>',"</div>"].join(""):"")+(n?['<div class="note-palette">','<div class="note-palette-title">'+this.lang.color.foreground+"</div>","<div>",'<button type="button" class="note-color-reset btn btn-light btn-default" data-event="removeFormat" data-value="foreColor">',this.lang.color.resetToDefault,"</button>","</div>",'<div class="note-holder" data-event="foreColor">\x3c!-- fore colors --\x3e</div>',"<div>",'<button type="button" class="note-color-select btn btn-light btn-default" data-event="openPalette" data-value="foreColorPicker-'+this.options.id+'">',this.lang.color.cpSelect,"</button>",'<input type="color" id="foreColorPicker-'+this.options.id+'" class="note-btn note-color-select-btn" value="'+this.options.colorButton.foreColor+'" data-event="foreColorPalette-'+this.options.id+'">',"</div>",'<div class="note-holder-custom" id="foreColorPalette-'+this.options.id+'" data-event="foreColor"></div>',"</div>"].join(""):""),callback:function(t){t.find(".note-holder").each((function(t,e){var o=r()(e);o.append(i.ui.palette({colors:i.options.colors,colorsName:i.options.colorsName,eventName:o.data("event"),container:i.options.container,tooltip:i.options.tooltip}).render())}));var e=[["#FFFFFF","#FFFFFF","#FFFFFF","#FFFFFF","#FFFFFF","#FFFFFF","#FFFFFF","#FFFFFF"]];t.find(".note-holder-custom").each((function(t,o){var n=r()(o);n.append(i.ui.palette({colors:e,colorsName:e,eventName:n.data("event"),container:i.options.container,tooltip:i.options.tooltip}).render())})),t.find("input[type=color]").each((function(e,o){r()(o).on("change",(function(){var e=t.find("#"+r()(this).data("event")).find(".note-color-btn").first(),o=this.value.toUpperCase();e.css("background-color",o).attr("aria-label",o).attr("data-value",o).attr("data-original-title",o),e.trigger("click")}))}))},click:function(e){e.stopPropagation();var o=r()("."+t).find(".note-dropdown-menu"),n=r()(e.target),a=n.data("event"),s=n.attr("data-value");if("openPalette"===a){var l=o.find("#"+s),c=r()(o.find("#"+l.data("event")).find(".note-color-row")[0]),u=c.find(".note-color-btn").last().detach(),d=l.val();u.css("background-color",d).attr("aria-label",d).attr("data-value",d).attr("data-original-title",d),c.prepend(u),l.trigger("click")}else{if(C.contains(["backColor","foreColor"],a)){var f="backColor"===a?"background-color":"color",h=n.closest(".note-color").find(".note-recent-color"),p=n.closest(".note-color").find(".note-current-color-button");h.css(f,s),p.attr("data-"+a,s)}i.context.invoke("editor."+a,s)}}})]}).render()}},{key:"addToolbarButtons",value:function(){var t=this;this.context.memo("button.style",(function(){return t.ui.buttonGroup([t.button({className:"dropdown-toggle",contents:t.ui.dropdownButtonContents(t.ui.icon(t.options.icons.magic),t.options),tooltip:t.lang.style.style,data:{toggle:"dropdown"}}),t.ui.dropdown({className:"dropdown-style",items:t.options.styleTags,title:t.lang.style.style,template:function(e){"string"==typeof e&&(e={tag:e,title:Object.prototype.hasOwnProperty.call(t.lang.style,e)?t.lang.style[e]:e});var o=e.tag,n=e.title;return"<"+o+(e.style?' style="'+e.style+'" ':"")+(e.className?' class="'+e.className+'"':"")+">"+n+"</"+o+">"},click:t.context.createInvokeHandler("editor.formatBlock")})]).render()}));for(var e=function(){var e=t.options.styleTags[o];t.context.memo("button.style."+e,(function(){return t.button({className:"note-btn-style-"+e,contents:'<div data-value="'+e+'">'+e.toUpperCase()+"</div>",tooltip:t.lang.style[e],click:t.context.createInvokeHandler("editor.formatBlock")}).render()}))},o=0,n=this.options.styleTags.length;o<n;o++)e();this.context.memo("button.bold",(function(){return t.button({className:"note-btn-bold",contents:t.ui.icon(t.options.icons.bold),tooltip:t.lang.font.bold+t.representShortcut("bold"),click:t.context.createInvokeHandlerAndUpdateState("editor.bold")}).render()})),this.context.memo("button.italic",(function(){return t.button({className:"note-btn-italic",contents:t.ui.icon(t.options.icons.italic),tooltip:t.lang.font.italic+t.representShortcut("italic"),click:t.context.createInvokeHandlerAndUpdateState("editor.italic")}).render()})),this.context.memo("button.underline",(function(){return t.button({className:"note-btn-underline",contents:t.ui.icon(t.options.icons.underline),tooltip:t.lang.font.underline+t.representShortcut("underline"),click:t.context.createInvokeHandlerAndUpdateState("editor.underline")}).render()})),this.context.memo("button.clear",(function(){return t.button({contents:t.ui.icon(t.options.icons.eraser),tooltip:t.lang.font.clear+t.representShortcut("removeFormat"),click:t.context.createInvokeHandler("editor.removeFormat")}).render()})),this.context.memo("button.strikethrough",(function(){return t.button({className:"note-btn-strikethrough",contents:t.ui.icon(t.options.icons.strikethrough),tooltip:t.lang.font.strikethrough+t.representShortcut("strikethrough"),click:t.context.createInvokeHandlerAndUpdateState("editor.strikethrough")}).render()})),this.context.memo("button.superscript",(function(){return t.button({className:"note-btn-superscript",contents:t.ui.icon(t.options.icons.superscript),tooltip:t.lang.font.superscript,click:t.context.createInvokeHandlerAndUpdateState("editor.superscript")}).render()})),this.context.memo("button.subscript",(function(){return t.button({className:"note-btn-subscript",contents:t.ui.icon(t.options.icons.subscript),tooltip:t.lang.font.subscript,click:t.context.createInvokeHandlerAndUpdateState("editor.subscript")}).render()})),this.context.memo("button.fontname",(function(){var e=t.context.invoke("editor.currentStyle");return t.options.addDefaultFonts&&r().each(e["font-family"].split(","),(function(e,o){o=o.trim().replace(/['"]+/g,""),t.isFontDeservedToAdd(o)&&-1===t.options.fontNames.indexOf(o)&&t.options.fontNames.push(o)})),t.ui.buttonGroup([t.button({className:"dropdown-toggle",contents:t.ui.dropdownButtonContents('<span class="note-current-fontname"></span>',t.options),tooltip:t.lang.font.name,data:{toggle:"dropdown"}}),t.ui.dropdownCheck({className:"dropdown-fontname",checkClassName:t.options.icons.menuCheck,items:t.options.fontNames.filter(t.isFontInstalled.bind(t)),title:t.lang.font.name,template:function(t){return'<span style="font-family: '+m.validFontName(t)+'">'+t+"</span>"},click:t.context.createInvokeHandlerAndUpdateState("editor.fontName")})]).render()})),this.context.memo("button.fontsize",(function(){return t.ui.buttonGroup([t.button({className:"dropdown-toggle",contents:t.ui.dropdownButtonContents('<span class="note-current-fontsize"></span>',t.options),tooltip:t.lang.font.size,data:{toggle:"dropdown"}}),t.ui.dropdownCheck({className:"dropdown-fontsize",checkClassName:t.options.icons.menuCheck,items:t.options.fontSizes,title:t.lang.font.size,click:t.context.createInvokeHandlerAndUpdateState("editor.fontSize")})]).render()})),this.context.memo("button.fontsizeunit",(function(){return t.ui.buttonGroup([t.button({className:"dropdown-toggle",contents:t.ui.dropdownButtonContents('<span class="note-current-fontsizeunit"></span>',t.options),tooltip:t.lang.font.sizeunit,data:{toggle:"dropdown"}}),t.ui.dropdownCheck({className:"dropdown-fontsizeunit",checkClassName:t.options.icons.menuCheck,items:t.options.fontSizeUnits,title:t.lang.font.sizeunit,click:t.context.createInvokeHandlerAndUpdateState("editor.fontSizeUnit")})]).render()})),this.context.memo("button.color",(function(){return t.colorPalette("note-color-all",t.lang.color.recent,!0,!0)})),this.context.memo("button.forecolor",(function(){return t.colorPalette("note-color-fore",t.lang.color.foreground,!1,!0)})),this.context.memo("button.backcolor",(function(){return t.colorPalette("note-color-back",t.lang.color.background,!0,!1)})),this.context.memo("button.ul",(function(){return t.button({contents:t.ui.icon(t.options.icons.unorderedlist),tooltip:t.lang.lists.unordered+t.representShortcut("insertUnorderedList"),click:t.context.createInvokeHandler("editor.insertUnorderedList")}).render()})),this.context.memo("button.ol",(function(){return t.button({contents:t.ui.icon(t.options.icons.orderedlist),tooltip:t.lang.lists.ordered+t.representShortcut("insertOrderedList"),click:t.context.createInvokeHandler("editor.insertOrderedList")}).render()}));var i=this.button({contents:this.ui.icon(this.options.icons.alignLeft),tooltip:this.lang.paragraph.left+this.representShortcut("justifyLeft"),click:this.context.createInvokeHandler("editor.justifyLeft")}),a=this.button({contents:this.ui.icon(this.options.icons.alignCenter),tooltip:this.lang.paragraph.center+this.representShortcut("justifyCenter"),click:this.context.createInvokeHandler("editor.justifyCenter")}),s=this.button({contents:this.ui.icon(this.options.icons.alignRight),tooltip:this.lang.paragraph.right+this.representShortcut("justifyRight"),click:this.context.createInvokeHandler("editor.justifyRight")}),l=this.button({contents:this.ui.icon(this.options.icons.alignJustify),tooltip:this.lang.paragraph.justify+this.representShortcut("justifyFull"),click:this.context.createInvokeHandler("editor.justifyFull")}),c=this.button({contents:this.ui.icon(this.options.icons.outdent),tooltip:this.lang.paragraph.outdent+this.representShortcut("outdent"),click:this.context.createInvokeHandler("editor.outdent")}),u=this.button({contents:this.ui.icon(this.options.icons.indent),tooltip:this.lang.paragraph.indent+this.representShortcut("indent"),click:this.context.createInvokeHandler("editor.indent")});this.context.memo("button.justifyLeft",g.invoke(i,"render")),this.context.memo("button.justifyCenter",g.invoke(a,"render")),this.context.memo("button.justifyRight",g.invoke(s,"render")),this.context.memo("button.justifyFull",g.invoke(l,"render")),this.context.memo("button.outdent",g.invoke(c,"render")),this.context.memo("button.indent",g.invoke(u,"render")),this.context.memo("button.paragraph",(function(){return t.ui.buttonGroup([t.button({className:"dropdown-toggle",contents:t.ui.dropdownButtonContents(t.ui.icon(t.options.icons.alignLeft),t.options),tooltip:t.lang.paragraph.paragraph,data:{toggle:"dropdown"}}),t.ui.dropdown([t.ui.buttonGroup({className:"note-align",children:[i,a,s,l]}),t.ui.buttonGroup({className:"note-list",children:[c,u]})])]).render()})),this.context.memo("button.height",(function(){return t.ui.buttonGroup([t.button({className:"dropdown-toggle",contents:t.ui.dropdownButtonContents(t.ui.icon(t.options.icons.textHeight),t.options),tooltip:t.lang.font.height,data:{toggle:"dropdown"}}),t.ui.dropdownCheck({items:t.options.lineHeights,checkClassName:t.options.icons.menuCheck,className:"dropdown-line-height",title:t.lang.font.height,click:t.context.createInvokeHandler("editor.lineHeight")})]).render()})),this.context.memo("button.table",(function(){return t.ui.buttonGroup([t.button({className:"dropdown-toggle",contents:t.ui.dropdownButtonContents(t.ui.icon(t.options.icons.table),t.options),tooltip:t.lang.table.table,data:{toggle:"dropdown"}}),t.ui.dropdown({title:t.lang.table.table,className:"note-table",items:['<div class="note-dimension-picker">','<div class="note-dimension-picker-mousecatcher" data-event="insertTable" data-value="1x1"></div>','<div class="note-dimension-picker-highlighted"></div>','<div class="note-dimension-picker-unhighlighted"></div>',"</div>",'<div class="note-dimension-display">1 x 1</div>'].join("")})],{callback:function(e){e.find(".note-dimension-picker-mousecatcher").css({width:t.options.insertTableMaxSize.col+"em",height:t.options.insertTableMaxSize.row+"em"}).on("mousedown",t.context.createInvokeHandler("editor.insertTable")).on("mousemove",t.tableMoveHandler.bind(t))}}).render()})),this.context.memo("button.link",(function(){return t.button({contents:t.ui.icon(t.options.icons.link),tooltip:t.lang.link.link+t.representShortcut("linkDialog.show"),click:t.context.createInvokeHandler("linkDialog.show")}).render()})),this.context.memo("button.picture",(function(){return t.button({contents:t.ui.icon(t.options.icons.picture),tooltip:t.lang.image.image,click:t.context.createInvokeHandler("imageDialog.show")}).render()})),this.context.memo("button.video",(function(){return t.button({contents:t.ui.icon(t.options.icons.video),tooltip:t.lang.video.video,click:t.context.createInvokeHandler("videoDialog.show")}).render()})),this.context.memo("button.hr",(function(){return t.button({contents:t.ui.icon(t.options.icons.minus),tooltip:t.lang.hr.insert+t.representShortcut("insertHorizontalRule"),click:t.context.createInvokeHandler("editor.insertHorizontalRule")}).render()})),this.context.memo("button.fullscreen",(function(){return t.button({className:"btn-fullscreen note-codeview-keep",contents:t.ui.icon(t.options.icons.arrowsAlt),tooltip:t.lang.options.fullscreen,click:t.context.createInvokeHandler("fullscreen.toggle")}).render()})),this.context.memo("button.codeview",(function(){return t.button({className:"btn-codeview note-codeview-keep",contents:t.ui.icon(t.options.icons.code),tooltip:t.lang.options.codeview,click:t.context.createInvokeHandler("codeview.toggle")}).render()})),this.context.memo("button.redo",(function(){return t.button({contents:t.ui.icon(t.options.icons.redo),tooltip:t.lang.history.redo+t.representShortcut("redo"),click:t.context.createInvokeHandler("editor.redo")}).render()})),this.context.memo("button.undo",(function(){return t.button({contents:t.ui.icon(t.options.icons.undo),tooltip:t.lang.history.undo+t.representShortcut("undo"),click:t.context.createInvokeHandler("editor.undo")}).render()})),this.context.memo("button.help",(function(){return t.button({contents:t.ui.icon(t.options.icons.question),tooltip:t.lang.options.help,click:t.context.createInvokeHandler("helpDialog.show")}).render()}))}},{key:"addImagePopoverButtons",value:function(){var t=this;this.context.memo("button.resizeFull",(function(){return t.button({contents:'<span class="note-fontsize-10">100%</span>',tooltip:t.lang.image.resizeFull,click:t.context.createInvokeHandler("editor.resize","1")}).render()})),this.context.memo("button.resizeHalf",(function(){return t.button({contents:'<span class="note-fontsize-10">50%</span>',tooltip:t.lang.image.resizeHalf,click:t.context.createInvokeHandler("editor.resize","0.5")}).render()})),this.context.memo("button.resizeQuarter",(function(){return t.button({contents:'<span class="note-fontsize-10">25%</span>',tooltip:t.lang.image.resizeQuarter,click:t.context.createInvokeHandler("editor.resize","0.25")}).render()})),this.context.memo("button.resizeNone",(function(){return t.button({contents:t.ui.icon(t.options.icons.rollback),tooltip:t.lang.image.resizeNone,click:t.context.createInvokeHandler("editor.resize","0")}).render()})),this.context.memo("button.floatLeft",(function(){return t.button({contents:t.ui.icon(t.options.icons.floatLeft),tooltip:t.lang.image.floatLeft,click:t.context.createInvokeHandler("editor.floatMe","left")}).render()})),this.context.memo("button.floatRight",(function(){return t.button({contents:t.ui.icon(t.options.icons.floatRight),tooltip:t.lang.image.floatRight,click:t.context.createInvokeHandler("editor.floatMe","right")}).render()})),this.context.memo("button.floatNone",(function(){return t.button({contents:t.ui.icon(t.options.icons.rollback),tooltip:t.lang.image.floatNone,click:t.context.createInvokeHandler("editor.floatMe","none")}).render()})),this.context.memo("button.removeMedia",(function(){return t.button({contents:t.ui.icon(t.options.icons.trash),tooltip:t.lang.image.remove,click:t.context.createInvokeHandler("editor.removeMedia")}).render()}))}},{key:"addLinkPopoverButtons",value:function(){var t=this;this.context.memo("button.linkDialogShow",(function(){return t.button({contents:t.ui.icon(t.options.icons.link),tooltip:t.lang.link.edit,click:t.context.createInvokeHandler("linkDialog.show")}).render()})),this.context.memo("button.unlink",(function(){return t.button({contents:t.ui.icon(t.options.icons.unlink),tooltip:t.lang.link.unlink,click:t.context.createInvokeHandler("editor.unlink")}).render()}))}},{key:"addTablePopoverButtons",value:function(){var t=this;this.context.memo("button.addRowUp",(function(){return t.button({className:"btn-md",contents:t.ui.icon(t.options.icons.rowAbove),tooltip:t.lang.table.addRowAbove,click:t.context.createInvokeHandler("editor.addRow","top")}).render()})),this.context.memo("button.addRowDown",(function(){return t.button({className:"btn-md",contents:t.ui.icon(t.options.icons.rowBelow),tooltip:t.lang.table.addRowBelow,click:t.context.createInvokeHandler("editor.addRow","bottom")}).render()})),this.context.memo("button.addColLeft",(function(){return t.button({className:"btn-md",contents:t.ui.icon(t.options.icons.colBefore),tooltip:t.lang.table.addColLeft,click:t.context.createInvokeHandler("editor.addCol","left")}).render()})),this.context.memo("button.addColRight",(function(){return t.button({className:"btn-md",contents:t.ui.icon(t.options.icons.colAfter),tooltip:t.lang.table.addColRight,click:t.context.createInvokeHandler("editor.addCol","right")}).render()})),this.context.memo("button.deleteRow",(function(){return t.button({className:"btn-md",contents:t.ui.icon(t.options.icons.rowRemove),tooltip:t.lang.table.delRow,click:t.context.createInvokeHandler("editor.deleteRow")}).render()})),this.context.memo("button.deleteCol",(function(){return t.button({className:"btn-md",contents:t.ui.icon(t.options.icons.colRemove),tooltip:t.lang.table.delCol,click:t.context.createInvokeHandler("editor.deleteCol")}).render()})),this.context.memo("button.deleteTable",(function(){return t.button({className:"btn-md",contents:t.ui.icon(t.options.icons.trash),tooltip:t.lang.table.delTable,click:t.context.createInvokeHandler("editor.deleteTable")}).render()}))}},{key:"build",value:function(t,e){for(var o=0,n=e.length;o<n;o++){for(var i=e[o],r=Array.isArray(i)?i[0]:i,a=Array.isArray(i)?1===i.length?[i[0]]:i[1]:[i],s=this.ui.buttonGroup({className:"note-"+r}).render(),l=0,c=a.length;l<c;l++){var u=this.context.memo("button."+a[l]);u&&s.append("function"==typeof u?u(this.context):u)}s.appendTo(t)}}},{key:"updateCurrentStyle",value:function(t){var e=t||this.$toolbar,o=this.context.invoke("editor.currentStyle");if(this.updateBtnStates(e,{".note-btn-bold":function(){return"bold"===o["font-bold"]},".note-btn-italic":function(){return"italic"===o["font-italic"]},".note-btn-underline":function(){return"underline"===o["font-underline"]},".note-btn-subscript":function(){return"subscript"===o["font-subscript"]},".note-btn-superscript":function(){return"superscript"===o["font-superscript"]},".note-btn-strikethrough":function(){return"strikethrough"===o["font-strikethrough"]}}),o["font-family"]){var n=o["font-family"].split(",").map((function(t){return t.replace(/[\'\"]/g,"").replace(/\s+$/,"").replace(/^\s+/,"")})),i=C.find(n,this.isFontInstalled.bind(this));e.find(".dropdown-fontname a").each((function(t,e){var o=r()(e),n=o.data("value")+""==i+"";o.toggleClass("checked",n)})),e.find(".note-current-fontname").text(i).css("font-family",i)}if(o["font-size"]){var a=o["font-size"];e.find(".dropdown-fontsize a").each((function(t,e){var o=r()(e),n=o.data("value")+""==a+"";o.toggleClass("checked",n)})),e.find(".note-current-fontsize").text(a);var s=o["font-size-unit"];e.find(".dropdown-fontsizeunit a").each((function(t,e){var o=r()(e),n=o.data("value")+""==s+"";o.toggleClass("checked",n)})),e.find(".note-current-fontsizeunit").text(s)}if(o["line-height"]){var l=o["line-height"];e.find(".dropdown-line-height a").each((function(t,e){var o=r()(e),n=r()(e).data("value")+""==l+"";o.toggleClass("checked",n)})),e.find(".note-current-line-height").text(l)}}},{key:"updateBtnStates",value:function(t,e){var o=this;r().each(e,(function(e,n){o.ui.toggleBtnActive(t.find(e),n())}))}},{key:"tableMoveHandler",value:function(t){var e,o=r()(t.target.parentNode),n=o.next(),i=o.find(".note-dimension-picker-mousecatcher"),a=o.find(".note-dimension-picker-highlighted"),s=o.find(".note-dimension-picker-unhighlighted");if(void 0===t.offsetX){var l=r()(t.target).offset();e={x:t.pageX-l.left,y:t.pageY-l.top}}else e={x:t.offsetX,y:t.offsetY};var c=Math.ceil(e.x/18)||1,u=Math.ceil(e.y/18)||1;a.css({width:c+"em",height:u+"em"}),i.data("value",c+"x"+u),c>3&&c<this.options.insertTableMaxSize.col&&s.css({width:c+1+"em"}),u>3&&u<this.options.insertTableMaxSize.row&&s.css({height:u+1+"em"}),n.html(c+" x "+u)}}],e&&_e(t.prototype,e),o&&_e(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function Ye(t){return Ye="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},Ye(t)}function Xe(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,Qe(n.key),n)}}function Qe(t){var e=function(t,e){if("object"!=Ye(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=Ye(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==Ye(e)?e:e+""}var Je=function(){return t=function t(e){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.context=e,this.$window=r()(window),this.$document=r()(document),this.ui=r().summernote.ui,this.$note=e.layoutInfo.note,this.$editor=e.layoutInfo.editor,this.$toolbar=e.layoutInfo.toolbar,this.$editable=e.layoutInfo.editable,this.$statusbar=e.layoutInfo.statusbar,this.options=e.options,this.isFollowing=!1,this.followScroll=this.followScroll.bind(this)},(e=[{key:"shouldInitialize",value:function(){return!this.options.airMode}},{key:"initialize",value:function(){var t=this;this.options.toolbar=this.options.toolbar||[],this.options.toolbar.length?this.context.invoke("buttons.build",this.$toolbar,this.options.toolbar):this.$toolbar.hide(),this.options.toolbarContainer&&this.$toolbar.appendTo(this.options.toolbarContainer),this.changeContainer(!1),this.$note.on("summernote.keyup summernote.mouseup summernote.change",(function(){t.context.invoke("buttons.updateCurrentStyle")})),this.context.invoke("buttons.updateCurrentStyle"),this.options.followingToolbar&&this.$window.on("scroll resize",this.followScroll)}},{key:"destroy",value:function(){this.$toolbar.children().remove(),this.options.followingToolbar&&this.$window.off("scroll resize",this.followScroll)}},{key:"followScroll",value:function(){if(this.$editor.hasClass("fullscreen"))return!1;var t=this.$editor.outerHeight(),e=this.$editor.width(),o=this.$toolbar.height(),n=this.$statusbar.height(),i=0;this.options.otherStaticBar&&(i=r()(this.options.otherStaticBar).outerHeight());var a=this.$document.scrollTop(),s=this.$editor.offset().top,l=s-i,c=s+t-i-o-n;!this.isFollowing&&a>l&&a<c-o?(this.isFollowing=!0,this.$editable.css({marginTop:this.$toolbar.outerHeight()}),this.$toolbar.css({position:"fixed",top:i,width:e,zIndex:1e3})):this.isFollowing&&(a<l||a>c)&&(this.isFollowing=!1,this.$toolbar.css({position:"relative",top:0,width:"100%",zIndex:"auto"}),this.$editable.css({marginTop:""}))}},{key:"changeContainer",value:function(t){t?this.$toolbar.prependTo(this.$editor):this.options.toolbarContainer&&this.$toolbar.appendTo(this.options.toolbarContainer),this.options.followingToolbar&&this.followScroll()}},{key:"updateFullscreen",value:function(t){this.ui.toggleBtnActive(this.$toolbar.find(".btn-fullscreen"),t),this.changeContainer(t)}},{key:"updateCodeview",value:function(t){this.ui.toggleBtnActive(this.$toolbar.find(".btn-codeview"),t),t?this.deactivate():this.activate()}},{key:"activate",value:function(t){var e=this.$toolbar.find("button");t||(e=e.not(".note-codeview-keep")),this.ui.toggleBtn(e,!0)}},{key:"deactivate",value:function(t){var e=this.$toolbar.find("button");t||(e=e.not(".note-codeview-keep")),this.ui.toggleBtn(e,!1)}}])&&Xe(t.prototype,e),o&&Xe(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function to(t){return to="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},to(t)}function eo(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,oo(n.key),n)}}function oo(t){var e=function(t,e){if("object"!=to(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=to(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==to(e)?e:e+""}var no=/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,io=/^(\+?\d{1,3}[\s-]?)?(\d{1,4})[\s-]?(\d{1,4})[\s-]?(\d{1,4})$/,ro=/^([A-Za-z][A-Za-z0-9+-.]*\:|#|\/)/,ao=function(){return t=function t(e){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.context=e,this.ui=r().summernote.ui,this.$body=r()(document.body),this.$editor=e.layoutInfo.editor,this.options=e.options,this.lang=this.options.langInfo,e.memo("help.linkDialog.show",this.options.langInfo.help["linkDialog.show"])},(e=[{key:"initialize",value:function(){var t=this.options.dialogsInBody?this.$body:this.options.container,e=['<div class="form-group note-form-group">','<label for="note-dialog-link-txt-'.concat(this.options.id,'" class="note-form-label">').concat(this.lang.link.textToDisplay,"</label>"),'<input id="note-dialog-link-txt-'.concat(this.options.id,'" class="note-link-text form-control note-form-control note-input" type="text"/>'),"</div>",'<div class="form-group note-form-group">','<label for="note-dialog-link-url-'.concat(this.options.id,'" class="note-form-label">').concat(this.lang.link.url,"</label>"),'<input id="note-dialog-link-url-'.concat(this.options.id,'" class="note-link-url form-control note-form-control note-input" type="text" value="http://"/>'),"</div>",this.options.disableLinkTarget?"":r()("<div></div>").append(this.ui.checkbox({className:"sn-checkbox-open-in-new-window",text:this.lang.link.openInNewWindow,checked:!0}).render()).html()].join(""),o='<input type="button" href="#" class="'.concat("btn btn-primary note-btn note-btn-primary note-link-btn",'" value="').concat(this.lang.link.insert,'" disabled>');this.$dialog=this.ui.dialog({className:"link-dialog",title:this.lang.link.insert,fade:this.options.dialogsFade,body:e,footer:o}).render().appendTo(t)}},{key:"destroy",value:function(){this.ui.hideDialog(this.$dialog),this.$dialog.remove()}},{key:"bindEnterKey",value:function(t,e){t.on("keypress",(function(t){t.keyCode===Nt.code.ENTER&&(t.preventDefault(),e.trigger("click"))}))}},{key:"checkLinkUrl",value:function(t){return no.test(t)?"mailto://"+t:io.test(t)?"tel://"+t:ro.test(t)?t:"http://"+t}},{key:"onCheckLinkUrl",value:function(t){var e=this;t.on("blur",(function(t){t.target.value=""==t.target.value?"":e.checkLinkUrl(t.target.value)}))}},{key:"toggleLinkBtn",value:function(t,e,o){this.ui.toggleBtn(t,e.val()&&o.val())}},{key:"showLinkDialog",value:function(t){var e=this;return r().Deferred((function(o){var n=e.$dialog.find(".note-link-text"),i=e.$dialog.find(".note-link-url"),r=e.$dialog.find(".note-link-btn"),a=e.$dialog.find(".sn-checkbox-open-in-new-window input[type=checkbox]");e.ui.onDialogShown(e.$dialog,(function(){e.context.triggerEvent("dialog.shown"),!t.url&&g.isValidUrl(t.text)&&(t.url=e.checkLinkUrl(t.text)),n.on("input paste propertychange",(function(){var o=n.val(),a=document.createElement("div");a.innerText=o,o=a.innerHTML,t.text=o,e.toggleLinkBtn(r,n,i)})).val(t.text),i.on("input paste propertychange",(function(){t.text||n.val(i.val()),e.toggleLinkBtn(r,n,i)})).val(t.url),m.isSupportTouch||i.trigger("focus"),e.toggleLinkBtn(r,n,i),e.bindEnterKey(i,r),e.bindEnterKey(n,r),e.onCheckLinkUrl(i);var s=void 0!==t.isNewWindow?t.isNewWindow:e.context.options.linkTargetBlank;a.prop("checked",s),r.one("click",(function(r){r.preventDefault(),o.resolve({range:t.range,url:i.val(),text:n.val(),isNewWindow:a.is(":checked")}),e.ui.hideDialog(e.$dialog)}))})),e.ui.onDialogHidden(e.$dialog,(function(){n.off(),i.off(),r.off(),"pending"===o.state()&&o.reject()})),e.ui.showDialog(e.$dialog)})).promise()}},{key:"show",value:function(){var t=this,e=this.context.invoke("editor.getLinkInfo");this.context.invoke("editor.saveRange"),this.showLinkDialog(e).then((function(e){t.context.invoke("editor.restoreRange"),t.context.invoke("editor.createLink",e)})).fail((function(){t.context.invoke("editor.restoreRange")}))}}])&&eo(t.prototype,e),o&&eo(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function so(t){return so="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},so(t)}function lo(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,co(n.key),n)}}function co(t){var e=function(t,e){if("object"!=so(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=so(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==so(e)?e:e+""}var uo=function(){return t=function t(e){var o=this;!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.context=e,this.ui=r().summernote.ui,this.options=e.options,this.events={"summernote.keyup summernote.mouseup summernote.change summernote.scroll":function(){o.update()},"summernote.disable summernote.dialog.shown":function(){o.hide()},"summernote.blur":function(t,e){e.originalEvent&&e.originalEvent.relatedTarget&&o.$popover[0].contains(e.originalEvent.relatedTarget)||o.hide()}}},(e=[{key:"shouldInitialize",value:function(){return!C.isEmpty(this.options.popover.link)}},{key:"initialize",value:function(){this.$popover=this.ui.popover({className:"note-link-popover",callback:function(t){t.find(".popover-content,.note-popover-content").prepend('<span><a target="_blank"></a>&nbsp;</span>')}}).render().appendTo(this.options.container);var t=this.$popover.find(".popover-content,.note-popover-content");this.context.invoke("buttons.build",t,this.options.popover.link),this.$popover.on("mousedown",(function(t){t.preventDefault()}))}},{key:"destroy",value:function(){this.$popover.remove()}},{key:"update",value:function(){if(this.context.invoke("editor.hasFocus")){var t=this.context.invoke("editor.getLastRange");if(t.isCollapsed()&&t.isOnAnchor()){var e=pt.ancestor(t.sc,pt.isAnchor),o=r()(e).attr("href");this.$popover.find("a").attr("href",o).text(o);var n=pt.posFromPlaceholder(e),i=r()(this.options.container).offset();n.top-=i.top,n.left-=i.left,this.$popover.css({display:"block",left:n.left,top:n.top})}else this.hide()}else this.hide()}},{key:"hide",value:function(){this.$popover.hide()}}])&&lo(t.prototype,e),o&&lo(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function fo(t){return fo="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},fo(t)}function ho(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,po(n.key),n)}}function po(t){var e=function(t,e){if("object"!=fo(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=fo(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==fo(e)?e:e+""}var mo=function(){return t=function t(e){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.context=e,this.ui=r().summernote.ui,this.$body=r()(document.body),this.$editor=e.layoutInfo.editor,this.options=e.options,this.lang=this.options.langInfo},(e=[{key:"initialize",value:function(){var t="";if(this.options.maximumImageFileSize){var e=Math.floor(Math.log(this.options.maximumImageFileSize)/Math.log(1024)),o=1*(this.options.maximumImageFileSize/Math.pow(1024,e)).toFixed(2)+" "+" KMGTP"[e]+"B";t="<small>".concat(this.lang.image.maximumFileSize+" : "+o,"</small>")}var n=this.options.dialogsInBody?this.$body:this.options.container,i=['<div class="form-group note-form-group note-group-select-from-files">','<label for="note-dialog-image-file-'+this.options.id+'" class="note-form-label">'+this.lang.image.selectFromFiles+"</label>",'<input id="note-dialog-image-file-'+this.options.id+'" class="note-image-input form-control-file note-form-control note-input" ',' type="file" name="files" accept="'+this.options.acceptImageFileTypes+'" multiple="multiple"/>',t,"</div>",'<div class="form-group note-group-image-url">','<label for="note-dialog-image-url-'+this.options.id+'" class="note-form-label">'+this.lang.image.url+"</label>",'<input id="note-dialog-image-url-'+this.options.id+'" class="note-image-url form-control note-form-control note-input" type="text"/>',"</div>"].join(""),r='<input type="button" href="#" class="'.concat("btn btn-primary note-btn note-btn-primary note-image-btn",'" value="').concat(this.lang.image.insert,'" disabled>');this.$dialog=this.ui.dialog({title:this.lang.image.insert,fade:this.options.dialogsFade,body:i,footer:r}).render().appendTo(n)}},{key:"destroy",value:function(){this.ui.hideDialog(this.$dialog),this.$dialog.remove()}},{key:"bindEnterKey",value:function(t,e){t.on("keypress",(function(t){t.keyCode===Nt.code.ENTER&&(t.preventDefault(),e.trigger("click"))}))}},{key:"show",value:function(){var t=this;this.context.invoke("editor.saveRange"),this.showImageDialog().then((function(e){t.ui.hideDialog(t.$dialog),t.context.invoke("editor.restoreRange"),"string"==typeof e?t.options.callbacks.onImageLinkInsert?t.context.triggerEvent("image.link.insert",e):t.context.invoke("editor.insertImage",e):t.context.invoke("editor.insertImagesOrCallback",e)})).fail((function(){t.context.invoke("editor.restoreRange")}))}},{key:"showImageDialog",value:function(){var t=this;return r().Deferred((function(e){var o=t.$dialog.find(".note-image-input"),n=t.$dialog.find(".note-image-url"),i=t.$dialog.find(".note-image-btn");t.ui.onDialogShown(t.$dialog,(function(){t.context.triggerEvent("dialog.shown"),o.replaceWith(o.clone().on("change",(function(t){e.resolve(t.target.files||t.target.value)})).val("")),n.on("input paste propertychange",(function(){t.ui.toggleBtn(i,n.val())})).val(""),m.isSupportTouch||n.trigger("focus"),i.on("click",(function(t){t.preventDefault(),e.resolve(n.val())})),t.bindEnterKey(n,i)})),t.ui.onDialogHidden(t.$dialog,(function(){o.off(),n.off(),i.off(),"pending"===e.state()&&e.reject()})),t.ui.showDialog(t.$dialog)}))}}])&&ho(t.prototype,e),o&&ho(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function vo(t){return vo="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},vo(t)}function go(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,bo(n.key),n)}}function bo(t){var e=function(t,e){if("object"!=vo(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=vo(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==vo(e)?e:e+""}var yo=function(){return t=function t(e){var o=this;!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.context=e,this.ui=r().summernote.ui,this.editable=e.layoutInfo.editable[0],this.options=e.options,this.events={"summernote.disable summernote.dialog.shown":function(){o.hide()},"summernote.blur":function(t,e){e.originalEvent&&e.originalEvent.relatedTarget&&o.$popover[0].contains(e.originalEvent.relatedTarget)||o.hide()}}},e=[{key:"shouldInitialize",value:function(){return!C.isEmpty(this.options.popover.image)}},{key:"initialize",value:function(){this.$popover=this.ui.popover({className:"note-image-popover"}).render().appendTo(this.options.container);var t=this.$popover.find(".popover-content,.note-popover-content");this.context.invoke("buttons.build",t,this.options.popover.image),this.$popover.on("mousedown",(function(t){t.preventDefault()}))}},{key:"destroy",value:function(){this.$popover.remove()}},{key:"update",value:function(t,e){if(pt.isImg(t)){var o=r()(t).offset(),n=r()(this.options.container).offset(),i={};this.options.popatmouse?(i.left=e.pageX-20,i.top=e.pageY):i=o,i.top-=n.top,i.left-=n.left,this.$popover.css({display:"block",left:i.left,top:i.top})}else this.hide()}},{key:"hide",value:function(){this.$popover.hide()}}],e&&go(t.prototype,e),o&&go(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function ko(t){return ko="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},ko(t)}function wo(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,Co(n.key),n)}}function Co(t){var e=function(t,e){if("object"!=ko(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=ko(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==ko(e)?e:e+""}var So=function(){return t=function t(e){var o=this;!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.context=e,this.ui=r().summernote.ui,this.options=e.options,this.events={"summernote.mousedown":function(t,e){o.update(e.target)},"summernote.keyup summernote.scroll summernote.change":function(){o.update()},"summernote.disable summernote.dialog.shown":function(){o.hide()},"summernote.blur":function(t,e){e.originalEvent&&e.originalEvent.relatedTarget&&o.$popover[0].contains(e.originalEvent.relatedTarget)||o.hide()}}},e=[{key:"shouldInitialize",value:function(){return!C.isEmpty(this.options.popover.table)}},{key:"initialize",value:function(){this.$popover=this.ui.popover({className:"note-table-popover"}).render().appendTo(this.options.container);var t=this.$popover.find(".popover-content,.note-popover-content");this.context.invoke("buttons.build",t,this.options.popover.table),m.isFF&&document.execCommand("enableInlineTableEditing",!1,!1),this.$popover.on("mousedown",(function(t){t.preventDefault()}))}},{key:"destroy",value:function(){this.$popover.remove()}},{key:"update",value:function(t){if(this.context.isDisabled())return!1;var e=pt.isCell(t)||pt.isCell(null==t?void 0:t.parentElement);if(e){var o=pt.posFromPlaceholder(t),n=r()(this.options.container).offset();o.top-=n.top,o.left-=n.left,this.$popover.css({display:"block",left:o.left,top:o.top})}else this.hide();return e}},{key:"hide",value:function(){this.$popover.hide()}}],e&&wo(t.prototype,e),o&&wo(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function xo(t){return xo="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},xo(t)}function To(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,Eo(n.key),n)}}function Eo(t){var e=function(t,e){if("object"!=xo(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=xo(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==xo(e)?e:e+""}var Po=function(){return t=function t(e){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.context=e,this.ui=r().summernote.ui,this.$body=r()(document.body),this.$editor=e.layoutInfo.editor,this.options=e.options,this.lang=this.options.langInfo},(e=[{key:"initialize",value:function(){var t=this.options.dialogsInBody?this.$body:this.options.container,e=['<div class="form-group note-form-group row-fluid">','<label for="note-dialog-video-url-'.concat(this.options.id,'" class="note-form-label">').concat(this.lang.video.url,' <small class="text-muted">').concat(this.lang.video.providers,"</small></label>"),'<input id="note-dialog-video-url-'.concat(this.options.id,'" class="note-video-url form-control note-form-control note-input" type="text"/>'),"</div>"].join(""),o='<input type="button" href="#" class="'.concat("btn btn-primary note-btn note-btn-primary note-video-btn",'" value="').concat(this.lang.video.insert,'" disabled>');this.$dialog=this.ui.dialog({title:this.lang.video.insert,fade:this.options.dialogsFade,body:e,footer:o}).render().appendTo(t)}},{key:"destroy",value:function(){this.ui.hideDialog(this.$dialog),this.$dialog.remove()}},{key:"bindEnterKey",value:function(t,e){t.on("keypress",(function(t){t.keyCode===Nt.code.ENTER&&(t.preventDefault(),e.trigger("click"))}))}},{key:"createVideoNode",value:function(t){var e,o=t.match(/(?:youtu\.be\/|youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=|shorts\/|live\/))([^&\n?]+)(?:.*[?&]t=([^&\n]+))?.*/),n=t.match(/(?:\.|\/\/)drive\.google\.com\/file\/d\/(.[a-zA-Z0-9_-]*)\/view/),i=t.match(/(?:www\.|\/\/)instagram\.com\/(reel|p)\/(.[a-zA-Z0-9_-]*)/),a=t.match(/\/\/vine\.co\/v\/([a-zA-Z0-9]+)/),s=t.match(/\/\/(player\.)?vimeo\.com\/([a-z]*\/)*(\d+)[?]?.*/),l=t.match(/.+dailymotion.com\/(video|hub)\/([^_]+)[^#]*(#video=([^_&]+))?/),c=t.match(/\/\/v\.youku\.com\/v_show\/id_(\w+)=*\.html/),u=t.match(/\/\/(.*)\/videos\/watch\/([^?]*)(?:\?(?:start=(\w*))?(?:&stop=(\w*))?(?:&loop=([10]))?(?:&autoplay=([10]))?(?:&muted=([10]))?)?/),d=t.match(/\/\/v\.qq\.com.*?vid=(.+)/),f=t.match(/\/\/v\.qq\.com\/x?\/?(page|cover).*?\/([^\/]+)\.html\??.*/),h=t.match(/^.+.(mp4|m4v)$/),p=t.match(/^.+.(ogg|ogv)$/),m=t.match(/^.+.(webm)$/),v=t.match(/(?:www\.|\/\/)facebook\.com\/([^\/]+)\/videos\/([0-9]+)/);if(o&&11===o[1].length){var g=o[1],b=0;if(void 0!==o[2]){var y=o[2].match(/^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$/);if(y)for(var k=[3600,60,1],w=0,C=k.length;w<C;w++)b+=void 0!==y[w+1]?k[w]*parseInt(y[w+1],10):0;else b=parseInt(o[2],10)}e=r()("<iframe>").attr("frameborder",0).attr("src","//www.youtube.com/embed/"+g+(b>0?"?start="+b:"")).attr("width","640").attr("height","360")}else if(n&&n[0].length)e=r()("<iframe>").attr("frameborder",0).attr("src","https://drive.google.com/file/d/"+n[1]+"/preview").attr("width","640").attr("height","480");else if(i&&i[0].length)e=r()("<iframe>").attr("frameborder",0).attr("src","https://instagram.com/p/"+i[2]+"/embed/").attr("width","612").attr("height","710").attr("scrolling","no").attr("allowtransparency","true");else if(a&&a[0].length)e=r()("<iframe>").attr("frameborder",0).attr("src",a[0]+"/embed/simple").attr("width","600").attr("height","600").attr("class","vine-embed");else if(s&&s[3].length)e=r()("<iframe webkitallowfullscreen mozallowfullscreen allowfullscreen>").attr("frameborder",0).attr("src","//player.vimeo.com/video/"+s[3]).attr("width","640").attr("height","360");else if(l&&l[2].length)e=r()("<iframe>").attr("frameborder",0).attr("src","//www.dailymotion.com/embed/video/"+l[2]).attr("width","640").attr("height","360");else if(c&&c[1].length)e=r()("<iframe webkitallowfullscreen mozallowfullscreen allowfullscreen>").attr("frameborder",0).attr("height","498").attr("width","510").attr("src","//player.youku.com/embed/"+c[1]);else if(u&&u[0].length){var S=0;"undefined"!==u[2]&&(S=u[2]);var x=0;"undefined"!==u[3]&&(x=u[3]);var T=0;"undefined"!==u[4]&&(T=u[4]);var E=0;"undefined"!==u[5]&&(E=u[5]);var P=0;"undefined"!==u[6]&&(P=u[6]),e=r()('<iframe allowfullscreen sandbox="allow-same-origin allow-scripts allow-popups">').attr("frameborder",0).attr("src","//"+u[1]+"/videos/embed/"+u[2]+"?loop="+T+"&autoplay="+E+"&muted="+P+(S>0?"&start="+S:"")+(x>0?"&end="+b:"")).attr("width","560").attr("height","315")}else if(d&&d[1].length||f&&f[2].length){var N=d&&d[1].length?d[1]:f[2];e=r()("<iframe webkitallowfullscreen mozallowfullscreen allowfullscreen>").attr("frameborder",0).attr("height","310").attr("width","500").attr("src","https://v.qq.com/txp/iframe/player.html?vid="+N+"&amp;auto=0")}else if(h||p||m)e=r()("<video controls>").attr("src",t).attr("width","640").attr("height","360");else{if(!v||!v[0].length)return!1;e=r()("<iframe>").attr("frameborder",0).attr("src","https://www.facebook.com/plugins/video.php?href="+encodeURIComponent(v[0])+"&show_text=0&width=560").attr("width","560").attr("height","301").attr("scrolling","no").attr("allowtransparency","true")}return e.addClass("note-video-clip"),e[0]}},{key:"show",value:function(){var t=this,e=this.context.invoke("editor.getSelectedText");this.context.invoke("editor.saveRange"),this.showVideoDialog(e).then((function(e){t.ui.hideDialog(t.$dialog),t.context.invoke("editor.restoreRange");var o=t.createVideoNode(e);o&&t.context.invoke("editor.insertNode",o)})).fail((function(){t.context.invoke("editor.restoreRange")}))}},{key:"showVideoDialog",value:function(){var t=this;return r().Deferred((function(e){var o=t.$dialog.find(".note-video-url"),n=t.$dialog.find(".note-video-btn");t.ui.onDialogShown(t.$dialog,(function(){t.context.triggerEvent("dialog.shown"),o.on("input paste propertychange",(function(){t.ui.toggleBtn(n,o.val())})),m.isSupportTouch||o.trigger("focus"),n.on("click",(function(t){t.preventDefault(),e.resolve(o.val())})),t.bindEnterKey(o,n)})),t.ui.onDialogHidden(t.$dialog,(function(){o.off(),n.off(),"pending"===e.state()&&e.reject()})),t.ui.showDialog(t.$dialog)}))}}])&&To(t.prototype,e),o&&To(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function No(t){return No="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},No(t)}function Io(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,$o(n.key),n)}}function $o(t){var e=function(t,e){if("object"!=No(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=No(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==No(e)?e:e+""}var Ro=function(){return t=function t(e){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.context=e,this.ui=r().summernote.ui,this.$body=r()(document.body),this.$editor=e.layoutInfo.editor,this.options=e.options,this.lang=this.options.langInfo},e=[{key:"initialize",value:function(){var t=this.options.dialogsInBody?this.$body:this.options.container,e=['<p class="text-center">','<a href="http://summernote.org/" target="_blank" rel="noopener noreferrer">Summernote 0.9.0</a> · ','<a href="https://github.com/summernote/summernote" target="_blank" rel="noopener noreferrer">Project</a> · ','<a href="https://github.com/summernote/summernote/issues" target="_blank" rel="noopener noreferrer">Issues</a>',"</p>"].join("");this.$dialog=this.ui.dialog({title:this.lang.options.help,fade:this.options.dialogsFade,body:this.createShortcutList(),footer:e,callback:function(t){t.find(".modal-body,.note-modal-body").css({"max-height":300,overflow:"scroll"})}}).render().appendTo(t)}},{key:"destroy",value:function(){this.ui.hideDialog(this.$dialog),this.$dialog.remove()}},{key:"createShortcutList",value:function(){var t=this,e=this.options.keyMap[m.isMac?"mac":"pc"];return Object.keys(e).map((function(o){var n=e[o],i=r()('<div><div class="help-list-item"></div></div>');return i.append(r()("<label><kbd>"+o+"</kdb></label>").css({width:180,"margin-right":10})).append(r()("<span></span>").html(t.context.memo("help."+n)||n)),i.html()})).join("")}},{key:"showHelpDialog",value:function(){var t=this;return r().Deferred((function(e){t.ui.onDialogShown(t.$dialog,(function(){t.context.triggerEvent("dialog.shown"),e.resolve()})),t.ui.showDialog(t.$dialog)})).promise()}},{key:"show",value:function(){var t=this;this.context.invoke("editor.saveRange"),this.showHelpDialog().then((function(){t.context.invoke("editor.restoreRange")}))}}],e&&Io(t.prototype,e),o&&Io(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function Ao(t){return Ao="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},Ao(t)}function Lo(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,Fo(n.key),n)}}function Fo(t){var e=function(t,e){if("object"!=Ao(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=Ao(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==Ao(e)?e:e+""}var Do=function(){return t=function t(e){var o=this;!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.context=e,this.ui=r().summernote.ui,this.options=e.options,this.hidable=!0,this.onContextmenu=!1,this.pageX=null,this.pageY=null,this.events={"summernote.contextmenu":function(t){o.options.editing&&(t.preventDefault(),t.stopPropagation(),o.onContextmenu=!0,o.update(!0))},"summernote.mousedown":function(t,e){o.pageX=e.pageX,o.pageY=e.pageY},"summernote.keyup summernote.mouseup summernote.scroll":function(t,e){if(o.options.editing&&!o.onContextmenu){if("keyup"==e.type){var n=o.context.invoke("editor.getLastRange").getWordRange(),i=g.rect2bnd(C.last(n.getClientRects()));o.pageX=i.left,o.pageY=i.top}else o.pageX=e.pageX,o.pageY=e.pageY;o.update()}o.onContextmenu=!1},"summernote.disable summernote.change summernote.dialog.shown summernote.blur":function(){o.hide()},"summernote.focusout":function(){o.$popover.is(":active,:focus")||o.hide()}}},(e=[{key:"shouldInitialize",value:function(){return this.options.airMode&&!C.isEmpty(this.options.popover.air)}},{key:"initialize",value:function(){var t=this;this.$popover=this.ui.popover({className:"note-air-popover"}).render().appendTo(this.options.container);var e=this.$popover.find(".popover-content");this.context.invoke("buttons.build",e,this.options.popover.air),this.$popover.on("mousedown",(function(){t.hidable=!1})),this.$popover.on("mouseup",(function(){t.hidable=!0}))}},{key:"destroy",value:function(){this.$popover.remove()}},{key:"update",value:function(t){var e=this.context.invoke("editor.currentStyle");if(!e.range||e.range.isCollapsed()&&!t)this.hide();else{var o={left:this.pageX,top:this.pageY},n=r()(this.options.container).offset();o.top-=n.top,o.left-=n.left,this.$popover.css({display:"block",left:Math.max(o.left,0)+-5,top:o.top+5}),this.context.invoke("buttons.updateCurrentStyle",this.$popover)}}},{key:"updateCodeview",value:function(t){this.ui.toggleBtnActive(this.$popover.find(".btn-codeview"),t),t&&this.hide()}},{key:"hide",value:function(){this.hidable&&this.$popover.hide()}}])&&Lo(t.prototype,e),o&&Lo(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function Ho(t){return Ho="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},Ho(t)}function jo(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,zo(n.key),n)}}function zo(t){var e=function(t,e){if("object"!=Ho(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=Ho(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==Ho(e)?e:e+""}var Bo=function(){return t=function t(e){var o=this;!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.context=e,this.ui=r().summernote.ui,this.$editable=e.layoutInfo.editable,this.options=e.options,this.hint=this.options.hint||[],this.direction=this.options.hintDirection||"bottom",this.hints=Array.isArray(this.hint)?this.hint:[this.hint],this.events={"summernote.keyup":function(t,e){e.isDefaultPrevented()||o.handleKeyup(e)},"summernote.keydown":function(t,e){o.handleKeydown(e)},"summernote.disable summernote.dialog.shown summernote.blur":function(){o.hide()}}},e=[{key:"shouldInitialize",value:function(){return this.hints.length>0}},{key:"initialize",value:function(){var t=this;this.lastWordRange=null,this.matchingWord=null,this.$popover=this.ui.popover({className:"note-hint-popover",hideArrow:!0,direction:""}).render().appendTo(this.options.container),this.$popover.hide(),this.$content=this.$popover.find(".popover-content,.note-popover-content"),this.$content.on("click",".note-hint-item",(function(e){t.$content.find(".active").removeClass("active"),r()(e.currentTarget).addClass("active"),t.replace()})),this.$popover.on("mousedown",(function(t){t.preventDefault()}))}},{key:"destroy",value:function(){this.$popover.remove()}},{key:"selectItem",value:function(t){this.$content.find(".active").removeClass("active"),t.addClass("active"),this.$content[0].scrollTop=t[0].offsetTop-this.$content.innerHeight()/2}},{key:"moveDown",value:function(){var t=this.$content.find(".note-hint-item.active"),e=t.next();if(e.length)this.selectItem(e);else{var o=t.parent().next();o.length||(o=this.$content.find(".note-hint-group").first()),this.selectItem(o.find(".note-hint-item").first())}}},{key:"moveUp",value:function(){var t=this.$content.find(".note-hint-item.active"),e=t.prev();if(e.length)this.selectItem(e);else{var o=t.parent().prev();o.length||(o=this.$content.find(".note-hint-group").last()),this.selectItem(o.find(".note-hint-item").last())}}},{key:"replace",value:function(){var t=this.$content.find(".note-hint-item.active");if(t.length){var e=this.nodeFromItem(t);if(null!==this.matchingWord&&0===this.matchingWord.length)this.lastWordRange.so=this.lastWordRange.eo;else if(null!==this.matchingWord&&this.matchingWord.length>0&&!this.lastWordRange.isCollapsed()){var o=this.lastWordRange.eo-this.lastWordRange.so-this.matchingWord.length;o>0&&(this.lastWordRange.so+=o)}if(this.lastWordRange.insertNode(e),"next"===this.options.hintSelect){var n=document.createTextNode("");r()(e).after(n),Et.createFromNodeBefore(n).select()}else Et.createFromNodeAfter(e).select();this.lastWordRange=null,this.hide(),this.context.invoke("editor.focus"),this.context.triggerEvent("change",this.$editable.html(),this.$editable)}}},{key:"nodeFromItem",value:function(t){var e=this.hints[t.data("index")],o=t.data("item"),n=e.content?e.content(o):o;return"string"==typeof n&&(n=pt.createText(n)),n}},{key:"createItemTemplates",value:function(t,e){var o=this.hints[t];return e.map((function(e,n){var i=r()('<div class="note-hint-item"></div>');return i.append(o.template?o.template(e):e+""),i.data({index:t,item:e}),0===t&&0===n&&i.addClass("active"),i}))}},{key:"handleKeydown",value:function(t){this.$popover.is(":visible")&&(t.keyCode===Nt.code.ENTER?(t.preventDefault(),this.replace()):t.keyCode===Nt.code.UP?(t.preventDefault(),this.moveUp()):t.keyCode===Nt.code.DOWN&&(t.preventDefault(),this.moveDown()))}},{key:"searchKeyword",value:function(t,e,o){var n=this.hints[t];if(n&&n.match.test(e)&&n.search){var i=n.match.exec(e);this.matchingWord=i[0],n.search(i[1],o)}else o()}},{key:"createGroup",value:function(t,e){var o=this,n=r()('<div class="note-hint-group note-hint-group-'+t+'"></div>');return this.searchKeyword(t,e,(function(e){(e=e||[]).length&&(n.html(o.createItemTemplates(t,e)),o.show())})),n}},{key:"handleKeyup",value:function(t){var e=this;if(!C.contains([Nt.code.ENTER,Nt.code.UP,Nt.code.DOWN],t.keyCode)){var o,n,i=this.context.invoke("editor.getLastRange");if("words"===this.options.hintMode){if(o=i.getWordsRange(i),n=o.toString(),this.hints.forEach((function(t){if(t.match.test(n))return o=i.getWordsMatchRange(t.match),!1})),!o)return void this.hide();n=o.toString()}else o=i.getWordRange(),n=o.toString();if(this.hints.length&&n){this.$content.empty();var a=g.rect2bnd(C.last(o.getClientRects())),s=r()(this.options.container).offset();a&&(a.top-=s.top,a.left-=s.left,this.$popover.hide(),this.lastWordRange=o,this.hints.forEach((function(t,o){t.match.test(n)&&e.createGroup(o,n).appendTo(e.$content)})),this.$content.find(".note-hint-item").first().addClass("active"),"top"===this.direction?this.$popover.css({left:a.left,top:a.top-this.$popover.outerHeight()-5}):this.$popover.css({left:a.left,top:a.top+a.height+5}))}else this.hide()}}},{key:"show",value:function(){this.$popover.show()}},{key:"hide",value:function(){this.$popover.hide()}}],e&&jo(t.prototype,e),o&&jo(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();function Oo(t){return Oo="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},Oo(t)}function Mo(t,e){for(var o=0;o<e.length;o++){var n=e[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,Uo(n.key),n)}}function Uo(t){var e=function(t,e){if("object"!=Oo(t)||!t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var n=o.call(t,e||"default");if("object"!=Oo(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(t,"string");return"symbol"==Oo(e)?e:e+""}r().summernote=r().extend(r().summernote,{version:"0.9.0",plugins:{},dom:pt,range:Et,lists:C,options:{langInfo:r().summernote.lang["en-US"],editing:!0,modules:{editor:oe,clipboard:ae,dropzone:ue,codeview:ve,statusbar:ke,fullscreen:xe,handle:Ne,hintPopover:Bo,autoLink:Le,autoSync:je,autoReplace:Me,placeholder:qe,buttons:Ze,toolbar:Je,linkDialog:ao,linkPopover:uo,imageDialog:mo,imagePopover:yo,tablePopover:So,videoDialog:Po,helpDialog:Ro,airPopover:Do},buttons:{},lang:"en-US",followingToolbar:!1,toolbarPosition:"top",otherStaticBar:"",codeviewKeepButton:!1,toolbar:[["style",["style"]],["font",["bold","underline","clear"]],["fontname",["fontname"]],["color",["color"]],["para",["ul","ol","paragraph"]],["table",["table"]],["insert",["link","picture","video"]],["view",["fullscreen","codeview","help"]]],popatmouse:!0,popover:{image:[["resize",["resizeFull","resizeHalf","resizeQuarter","resizeNone"]],["float",["floatLeft","floatRight","floatNone"]],["remove",["removeMedia"]]],link:[["link",["linkDialogShow","unlink"]]],table:[["add",["addRowDown","addRowUp","addColLeft","addColRight"]],["delete",["deleteRow","deleteCol","deleteTable"]]],air:[["color",["color"]],["font",["bold","underline","clear"]],["para",["ul","paragraph"]],["table",["table"]],["insert",["link","picture"]],["view",["fullscreen","codeview"]]]},linkAddNoReferrer:!1,addLinkNoOpener:!1,airMode:!1,overrideContextMenu:!1,width:null,height:null,linkTargetBlank:!0,focus:!1,tabDisable:!1,tabSize:4,styleWithCSS:!1,shortcuts:!0,textareaAutoSync:!0,tooltip:"auto",container:null,maxTextLength:0,blockquoteBreakingLevel:2,spellCheck:!0,disableGrammar:!1,placeholder:null,inheritPlaceholder:!1,recordEveryKeystroke:!1,historyLimit:200,showDomainOnlyForAutolink:!1,hintMode:"word",hintSelect:"after",hintDirection:"bottom",styleTags:["p","blockquote","pre","h1","h2","h3","h4","h5","h6"],fontNames:["Arial","Arial Black","Comic Sans MS","Courier New","Helvetica Neue","Helvetica","Impact","Lucida Grande","Tahoma","Times New Roman","Verdana"],fontNamesIgnoreCheck:[],addDefaultFonts:!0,fontSizes:["8","9","10","11","12","14","18","24","36"],fontSizeUnits:["px","pt"],colors:[["#000000","#424242","#636363","#9C9C94","#CEC6CE","#EFEFEF","#F7F7F7","#FFFFFF"],["#FF0000","#FF9C00","#FFFF00","#00FF00","#00FFFF","#0000FF","#9C00FF","#FF00FF"],["#F7C6CE","#FFE7CE","#FFEFC6","#D6EFD6","#CEDEE7","#CEE7F7","#D6D6E7","#E7D6DE"],["#E79C9C","#FFC69C","#FFE79C","#B5D6A5","#A5C6CE","#9CC6EF","#B5A5D6","#D6A5BD"],["#E76363","#F7AD6B","#FFD663","#94BD7B","#73A5AD","#6BADDE","#8C7BC6","#C67BA5"],["#CE0000","#E79439","#EFC631","#6BA54A","#4A7B8C","#3984C6","#634AA5","#A54A7B"],["#9C0000","#B56308","#BD9400","#397B21","#104A5A","#085294","#311873","#731842"],["#630000","#7B3900","#846300","#295218","#083139","#003163","#21104A","#4A1031"]],colorsName:[["Black","Tundora","Dove Gray","Star Dust","Pale Slate","Gallery","Alabaster","White"],["Red","Orange Peel","Yellow","Green","Cyan","Blue","Electric Violet","Magenta"],["Azalea","Karry","Egg White","Zanah","Botticelli","Tropical Blue","Mischka","Twilight"],["Tonys Pink","Peach Orange","Cream Brulee","Sprout","Casper","Perano","Cold Purple","Careys Pink"],["Mandy","Rajah","Dandelion","Olivine","Gulf Stream","Viking","Blue Marguerite","Puce"],["Guardsman Red","Fire Bush","Golden Dream","Chelsea Cucumber","Smalt Blue","Boston Blue","Butterfly Bush","Cadillac"],["Sangria","Mai Tai","Buddha Gold","Forest Green","Eden","Venice Blue","Meteorite","Claret"],["Rosewood","Cinnamon","Olive","Parsley","Tiber","Midnight Blue","Valentino","Loulou"]],colorButton:{foreColor:"#000000",backColor:"#FFFF00"},lineHeights:["1.0","1.2","1.4","1.5","1.6","1.8","2.0","3.0"],tableClassName:"table table-bordered",insertTableMaxSize:{col:10,row:10},dialogsInBody:!1,dialogsFade:!1,maximumImageFileSize:null,acceptImageFileTypes:"image/*",allowClipboardImagePasting:!0,callbacks:{onBeforeCommand:null,onBlur:null,onBlurCodeview:null,onChange:null,onChangeCodeview:null,onDialogShown:null,onEnter:null,onFocus:null,onImageLinkInsert:null,onImageUpload:null,onImageUploadError:null,onInit:null,onKeydown:null,onKeyup:null,onMousedown:null,onMouseup:null,onPaste:null,onScroll:null},codemirror:{mode:"text/html",htmlMode:!0,lineNumbers:!0},codeviewFilter:!0,codeviewFilterRegex:/<\/*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|ilayer|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|t(?:itle|extarea)|xml)[^>]*?>/gi,codeviewIframeFilter:!0,codeviewIframeWhitelistSrc:[],codeviewIframeWhitelistSrcBase:["www.youtube.com","www.youtube-nocookie.com","www.facebook.com","vine.co","instagram.com","player.vimeo.com","www.dailymotion.com","player.youku.com","jumpingbean.tv","v.qq.com"],keyMap:{pc:{ESC:"escape",ENTER:"insertParagraph","CTRL+Z":"undo","CTRL+Y":"redo",TAB:"tab","SHIFT+TAB":"untab","CTRL+B":"bold","CTRL+I":"italic","CTRL+U":"underline","CTRL+SHIFT+S":"strikethrough","CTRL+BACKSLASH":"removeFormat","CTRL+SHIFT+L":"justifyLeft","CTRL+SHIFT+E":"justifyCenter","CTRL+SHIFT+R":"justifyRight","CTRL+SHIFT+J":"justifyFull","CTRL+SHIFT+NUM7":"insertUnorderedList","CTRL+SHIFT+NUM8":"insertOrderedList","CTRL+LEFTBRACKET":"outdent","CTRL+RIGHTBRACKET":"indent","CTRL+NUM0":"formatPara","CTRL+NUM1":"formatH1","CTRL+NUM2":"formatH2","CTRL+NUM3":"formatH3","CTRL+NUM4":"formatH4","CTRL+NUM5":"formatH5","CTRL+NUM6":"formatH6","CTRL+ENTER":"insertHorizontalRule","CTRL+K":"linkDialog.show"},mac:{ESC:"escape",ENTER:"insertParagraph","CMD+Z":"undo","CMD+SHIFT+Z":"redo",TAB:"tab","SHIFT+TAB":"untab","CMD+B":"bold","CMD+I":"italic","CMD+U":"underline","CMD+SHIFT+S":"strikethrough","CMD+BACKSLASH":"removeFormat","CMD+SHIFT+L":"justifyLeft","CMD+SHIFT+E":"justifyCenter","CMD+SHIFT+R":"justifyRight","CMD+SHIFT+J":"justifyFull","CMD+SHIFT+NUM7":"insertUnorderedList","CMD+SHIFT+NUM8":"insertOrderedList","CMD+LEFTBRACKET":"outdent","CMD+RIGHTBRACKET":"indent","CMD+NUM0":"formatPara","CMD+NUM1":"formatH1","CMD+NUM2":"formatH2","CMD+NUM3":"formatH3","CMD+NUM4":"formatH4","CMD+NUM5":"formatH5","CMD+NUM6":"formatH6","CMD+ENTER":"insertHorizontalRule","CMD+K":"linkDialog.show"}},icons:{align:"note-icon-align",alignCenter:"note-icon-align-center",alignJustify:"note-icon-align-justify",alignLeft:"note-icon-align-left",alignRight:"note-icon-align-right",rowBelow:"note-icon-row-below",colBefore:"note-icon-col-before",colAfter:"note-icon-col-after",rowAbove:"note-icon-row-above",rowRemove:"note-icon-row-remove",colRemove:"note-icon-col-remove",indent:"note-icon-align-indent",outdent:"note-icon-align-outdent",arrowsAlt:"note-icon-arrows-alt",bold:"note-icon-bold",caret:"note-icon-caret",circle:"note-icon-circle",close:"note-icon-close",code:"note-icon-code",eraser:"note-icon-eraser",floatLeft:"note-icon-float-left",floatRight:"note-icon-float-right",font:"note-icon-font",frame:"note-icon-frame",italic:"note-icon-italic",link:"note-icon-link",unlink:"note-icon-chain-broken",magic:"note-icon-magic",menuCheck:"note-icon-menu-check",minus:"note-icon-minus",orderedlist:"note-icon-orderedlist",pencil:"note-icon-pencil",picture:"note-icon-picture",question:"note-icon-question",redo:"note-icon-redo",rollback:"note-icon-rollback",square:"note-icon-square",strikethrough:"note-icon-strikethrough",subscript:"note-icon-subscript",superscript:"note-icon-superscript",table:"note-icon-table",textHeight:"note-icon-text-height",trash:"note-icon-trash",underline:"note-icon-underline",undo:"note-icon-undo",unorderedlist:"note-icon-unorderedlist",video:"note-icon-video"}}});var Wo=function(){return t=function t(e,o,n,i){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.markup=e,this.children=o,this.options=n,this.callback=i},(e=[{key:"render",value:function(t){var e=r()(this.markup);if(this.options&&this.options.contents&&e.html(this.options.contents),this.options&&this.options.className&&e.addClass(this.options.className),this.options&&this.options.data&&r().each(this.options.data,(function(t,o){e.attr("data-"+t,o)})),this.options&&this.options.click&&e.on("click",this.options.click),this.children){var o=e.find(".note-children-container");this.children.forEach((function(t){t.render(o.length?o:e)}))}return this.callback&&this.callback(e,this.options),this.options&&this.options.callback&&this.options.callback(e),t&&t.append(e),e}}])&&Mo(t.prototype,e),o&&Mo(t,o),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e,o}();const Ko=function(t,e){return function(){var o="object"===Oo(arguments[1])?arguments[1]:arguments[0],n=Array.isArray(arguments[0])?arguments[0]:[];return o&&o.children&&(n=o.children),new Wo(t,n,o,e)}};function qo(t){return qo="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},qo(t)}var Vo=Ko('<div class="note-editor note-frame card"></div>'),_o=Ko('<div class="note-toolbar card-header" role="toolbar"></div>'),Go=Ko('<div class="note-editing-area"></div>'),Zo=Ko('<textarea class="note-codable" aria-multiline="true"></textarea>'),Yo=Ko('<div class="note-editable card-block" contentEditable="true" role="textbox" aria-multiline="true"/>'),Xo=Ko(['<output class="note-status-output" role="status" aria-live="polite"></output>','<div class="note-statusbar" role="status">','<div class="note-resizebar" aria-label="Resize">','<div class="note-icon-bar"></div>','<div class="note-icon-bar"></div>','<div class="note-icon-bar"></div>',"</div>","</div>"].join("")),Qo=Ko('<div class="note-editor note-airframe"></div>'),Jo=Ko(['<div class="note-editable" contentEditable="true" role="textbox" aria-multiline="true"></div>','<output class="note-status-output" role="status" aria-live="polite"></output>'].join("")),tn=Ko('<div class="note-btn-group btn-group">'),en=Ko('<div class="note-dropdown-menu dropdown-menu" role="list">',(function(t,e){var o=Array.isArray(e.items)?e.items.map((function(t){var o="string"==typeof t?t:t.value||"",n=e.template?e.template(t):t,i="object"===qo(t)?t.option:void 0;return'<a class="dropdown-item" href="#" '+('data-value="'+o+'"'+(void 0!==i?' data-option="'+i+'"':""))+' role="listitem" aria-label="'+o+'">'+n+"</a>"})).join(""):e.items;t.html(o).attr({"aria-label":e.title}),e&&e.codeviewKeepButton&&t.addClass("note-codeview-keep")})),on=function(t){return t},nn=Ko('<div class="note-dropdown-menu dropdown-menu note-check" role="list">',(function(t,e){var o=Array.isArray(e.items)?e.items.map((function(t){var o="string"==typeof t?t:t.value||"",n=e.template?e.template(t):t;return'<a class="dropdown-item" href="#" data-value="'+o+'" role="listitem" aria-label="'+t+'">'+ln(e.checkClassName)+" "+n+"</a>"})).join(""):e.items;t.html(o).attr({"aria-label":e.title}),e&&e.codeviewKeepButton&&t.addClass("note-codeview-keep")})),rn=Ko('<div class="modal note-modal" aria-hidden="false" tabindex="-1" role="dialog"></div>',(function(t,e){e.fade&&t.addClass("fade"),t.attr({"aria-label":e.title}),t.html(['<div class="modal-dialog">','<div class="modal-content">',e.title?'<div class="modal-header"><h4 class="modal-title">'+e.title+'</h4><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" aria-hidden="true"></button></div>':"",'<div class="modal-body">'+e.body+"</div>",e.footer?'<div class="modal-footer">'+e.footer+"</div>":"","</div>","</div>"].join(""))})),an=Ko(['<div class="note-popover popover bs-popover-auto show">','<div class="popover-arrow"></div>','<div class="popover-body note-popover-content note-children-container"></div>',"</div>"].join(""),(function(t,e){var o=void 0!==e.direction?e.direction:"bottom";t.attr("data-popper-placement",o),e.hideArrow&&t.find(".popover-arrow").hide()})),sn=Ko('<div class="form-check"></div>',(function(t,e){t.html(['<label class="form-check-label"'+(e.id?' for="note-'+e.id+'"':"")+">",'<input type="checkbox" class="form-check-input"'+(e.id?' id="note-'+e.id+'"':""),e.checked?" checked":"",' aria-label="'+(e.text?e.text:"")+'"',' aria-checked="'+(e.checked?"true":"false")+'"/>'," "+(e.text?e.text:"")+"</label>"].join(""))})),ln=function(t,e){return t.match(/^</)?t:"<"+(e=e||"i")+' class="'+t+'"></'+e+">"};return r().summernote=r().extend(r().summernote,{ui_template:function(t){return{editor:Vo,toolbar:_o,editingArea:Go,codable:Zo,editable:Yo,statusbar:Xo,airEditor:Qo,airEditable:Jo,buttonGroup:tn,dropdown:en,dropdownButtonContents:on,dropdownCheck:nn,dialog:rn,popover:an,icon:ln,checkbox:sn,options:t,palette:function(e,o){return Ko('<div class="note-color-palette"></div>',(function(e,o){for(var n=[],i=0,r=o.colors.length;i<r;i++){for(var a=o.eventName,s=o.colors[i],l=o.colorsName[i],c=[],u=0,d=s.length;u<d;u++){var f=s[u],h=l[u];c.push(['<button type="button" class="note-color-btn"','style="background-color:',f,'" ','data-event="',a,'" ','data-value="',f,'" ','title="',h,'" ','aria-label="',h,'" ','data-toggle="button" tabindex="-1"></button>'].join(""))}n.push('<div class="note-color-row">'+c.join("")+"</div>")}e.html(n.join("")),o.tooltip&&e.find(".note-color-btn").tooltip({container:o.container||t.container,trigger:"hover",placement:"bottom"})}))(e,o)},button:function(e,o){return Ko('<button type="button" class="note-btn btn btn-outline-secondary btn-sm" tabindex="-1">',(function(e,o){o&&o.data&&"dropdown"===o.data.toggle?(e.removeAttr("data-toggle"),e.attr("data-bs-toggle","dropdown"),o&&o.tooltip&&e.attr({title:o.tooltip,"aria-label":o.tooltip})):o&&o.tooltip&&e.attr({title:o.tooltip,"aria-label":o.tooltip}).tooltip({container:o.container||t.container,trigger:"hover",placement:"bottom"}).on("click",(function(t){r()(t.currentTarget).tooltip("hide")})),o&&o.codeviewButton&&e.addClass("note-codeview-keep")}))(e,o)},toggleBtn:function(t,e){t.toggleClass("disabled",!e),t.attr("disabled",!e)},toggleBtnActive:function(t,e){t.toggleClass("active",e)},onDialogShown:function(t,e){t.one("shown.bs.modal",e)},onDialogHidden:function(t,e){t.one("hidden.bs.modal",e)},showDialog:function(t){t.modal("show")},hideDialog:function(t){t.modal("hide")},createLayout:function(e){var o=(t.airMode?Qo([Go([Zo(),Jo()])]):"bottom"===t.toolbarPosition?Vo([Go([Zo(),Yo()]),_o(),Xo()]):Vo([_o(),Go([Zo(),Yo()]),Xo()])).render();return o.insertAfter(e),{note:e,editor:o,toolbar:o.find(".note-toolbar"),editingArea:o.find(".note-editing-area"),editable:o.find(".note-editable"),codable:o.find(".note-codable"),statusbar:o.find(".note-statusbar")}},removeLayout:function(t,e){t.html(e.editable.html()),e.editor.remove(),t.show()}}},interface:"bs5"}),r().summernote.options.styleTags=["p",{title:"Blockquote",tag:"blockquote",className:"blockquote",value:"blockquote"},"pre","h1","h2","h3","h4","h5","h6"],{}})()));
```

### public/favicon/site.webmanifest

```webmanifest
{"name":"","short_name":"","icons":[{"src":"/android-chrome-192x192.png","sizes":"192x192","type":"image/png"},{"src":"/android-chrome-512x512.png","sizes":"512x512","type":"image/png"}],"theme_color":"#ffffff","background_color":"#ffffff","display":"standalone"}
```

### public/uploads/avatars/.htaccess

```text
# Security: Prevent direct access to uploaded files
# Allow only static files
<FilesMatch "\.(php|php[0-9]|phtml|pl|py|rb|sh|cgi|exe|dll|so)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Disable PHP execution
<IfModule mod_php.c>
    php_flag engine off
</IfModule>

# Prevent directory listing
Options -Indexes
```

### public/uploads/documents/.htaccess

```text
# Security: Prevent direct access to uploaded files
# Allow only static files
<FilesMatch "\.(php|php[0-9]|phtml|pl|py|rb|sh|cgi|exe|dll|so)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Disable PHP execution
<IfModule mod_php.c>
    php_flag engine off
</IfModule>

# Prevent directory listing
Options -Indexes
```

### public/uploads/images/.htaccess

```text
# Security: Prevent direct access to uploaded files
# Allow only static files
<FilesMatch "\.(php|php[0-9]|phtml|pl|py|rb|sh|cgi|exe|dll|so)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Disable PHP execution
<IfModule mod_php.c>
    php_flag engine off
</IfModule>

# Prevent directory listing
Options -Indexes
```

### public/uploads/videos/.htaccess

```text
# Security: Prevent direct access to uploaded files
# Allow only static files
<FilesMatch "\.(php|php[0-9]|phtml|pl|py|rb|sh|cgi|exe|dll|so)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Disable PHP execution
<IfModule mod_php.c>
    php_flag engine off
</IfModule>

# Prevent directory listing
Options -Indexes
```

### public/.htaccess

```text
Options -Indexes

<IfModule mod_headers.c>
    # Remove server identification
    Header unset X-Powered-By
    
    # Default no-cache for dynamic content
    Header set Cache-Control "no-store, no-cache, must-revalidate, max-age=0"
    Header set Pragma "no-cache"
    Header set Expires 0

    # Caching for static assets (1 day)
    <FilesMatch "\.(css|js|jpg|jpeg|png|gif|webp|svg|ico|woff|woff2|ttf|eot)$">
        Header set Cache-Control "public, max-age=86400"
    </FilesMatch>

    # Security headers are handled by PHP FrameworkHeader.php
</IfModule>

# Simple compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE text/html
</IfModule>

<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Deny access to sensitive folders outside public/
    RewriteRule ^(app|backup|config|process|vendor|views)(/.*)?$ - [F,L]

    # If someone visits /public/... redirect to root
    RewriteCond %{THE_REQUEST} \s/public/([^\s?]*) [NC]
    RewriteRule ^public/(.*)$ /$1 [L,R=301]

    # Allow direct access to existing files and folders (CSS, JS, fonts, images, etc.)
    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)/$ /$1 [L,R=301]

    # Handle Front Controller (all other requests go to index.php)
    RewriteRule ^ index.php [L,QSA]

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
</IfModule>

# Prevent access to sensitive files
<FilesMatch "\.(htaccess|htpasswd|ini|log|sh|inc|bak|sql)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
```

### public/index.php

```php
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
```

### public/robots.txt

```text
# =========================
# General rules (ALL bots)
# =========================
User-agent: *
Disallow: /admin/
Disallow: /login
Disallow: /logout
Disallow: /register

Sitemap: https://crm.verola.co.test/sitemap.xml
Security: https://crm.verola.co.test/security.txt


# =========================
# OpenAI (ChatGPT)
# =========================
User-agent: GPTBot
Disallow: /admin/
Disallow: /login
Disallow: /logout
Disallow: /register


# =========================
# Google AI / Extended usage
# =========================
User-agent: Google-Extended
Disallow: /admin/
Disallow: /login
Disallow: /logout
Disallow: /register

User-agent: CloudVertexBot
Disallow: /admin/
Disallow: /login
Disallow: /logout
Disallow: /register


# =========================
# Anthropic (Claude)
# =========================
User-agent: ClaudeBot
Disallow: /admin/
Disallow: /login
Disallow: /logout
Disallow: /register


# =========================
# Perplexity AI
# =========================
User-agent: PerplexityBot
Disallow: /admin/
Disallow: /login
Disallow: /logout
Disallow: /register


# =========================
# Apple Intelligence
# =========================
User-agent: Applebot-Extended
Disallow: /admin/
Disallow: /login
Disallow: /logout
Disallow: /register


# =========================
# Common Crawl (used by many LLMs)
# =========================
User-agent: CCBot
Disallow: /admin/
Disallow: /login
Disallow: /logout
Disallow: /register
```

### tutorial/Claude - Build a Blog with Onebuild (CRUD Tutorial).md

```markdown
# Build a Blog with Onebuild

## A step-by-step CRUD tutorial for non-programmers

*Rewritten for your real Onebuild project. The code in this tutorial was tested end to end (login, create, edit, delete, image upload) before printing.*

## Read this first

**What you will build.** A small blog inside your Onebuild site:

- a **Categories** manager (add, list, edit, delete)
- an **Articles** manager (the same four actions, plus a picture upload)
- two **public pages** where visitors read the articles

Programmers call the four actions **CRUD**: **C**reate, **R**ead, **U**pdate, **D**elete. Almost every website is CRUD on different things (products, customers, invoices). Learn it once and you can reuse it forever.

**How long it takes.** About 3 to 4 hours, in several sessions. The site works at every CHECKPOINT, so you can stop after any Part and continue another day.

**How to follow this paper**

1. Every file starts with a label such as `FILE: app/Models/CategoryModel.php` and says **NEW FILE** or **EDIT FILE**.
2. Type the code exactly as printed. PHP is strict about these characters: `;` `{ }` `( )` `[ ]` `' '` `$` `->` `=>`. One missing character gives an error page.
3. Indentation (the spaces at the start of a line) is only for reading. Keep it anyway, it helps you spot mistakes.
4. Lines that start with `//` or `*`, or that sit between `/**` and `*/`, are comments written for humans. You may skip typing them.
5. Names are case-sensitive. The file `AdminCategoryController.php` must contain `class AdminCategoryController`. Same letters, same capitals.
6. Use a code editor such as VS Code (free). Do not use Word.
7. **Typo detector.** After you save a PHP file, open a terminal in your project folder (in Laragon: Menu, then Terminal) and run `php -l app/Helpers/BlogHelper.php`, using the path of the file you just made. It answers `No syntax errors detected`, or tells you which line has the problem. It works for view files too.

## Before you start (checklist)

Tick each box.

- [ ] Your Onebuild home page opens in the browser.
- [ ] You can sign in at `/login` and reach `/dashboard`.
- [ ] Your account has the role **root** or **admin**. The admin pages you will build refuse everybody else. (A root account is created by running `php bin/add_root.php "+628123456789" "YourPassword"` once in the project folder.)
- [ ] While learning on your own computer, open the file `.env` and set `APP_ENV=development`. Error messages then show on screen instead of a blank page. Never leave this on a real website: set it back to `production` when you publish.
- [ ] You have a database tool (phpMyAdmin or HeidiSQL) and you know which database Onebuild uses. It is the `DB_NAME` line in `.env`.

## The big picture (read once, 5 minutes)

Think of a restaurant.

- **Route** = the menu board at the door. It says "this address is handled by that waiter". Onebuild keeps its routes in `public/index.php`.
- **Controller** = the waiter. It receives the request, checks you are allowed, fetches what is needed and brings it to the table. Controllers live in `app/Controllers/`.
- **Model** = the pantry keeper. The only one who touches the database. Models live in `app/Models/`.
- **View** = the plate. The HTML page the visitor sees. Views live in `views/`.

What happens when you open the page `/dashboard/categories`:

```text
Browser asks for  /dashboard/categories
        |
        v
public/index.php    route list says: AdminCategoryController -> index()
        |
        v
AdminCategoryController::index()
        1. Is this person an admin?             (requireAdmin)
        2. Ask the model for all categories     (CategoryModel -> database)
        3. Hand the list to the view
        |
        v
views/admin/categories/index.php   builds the HTML page and sends it back
```

**One rule you will see everywhere.** Anything with a form needs **two** controller methods. One **shows** the form (the browser *asks* for a page: this is called GET). The other **receives** the filled form (the browser *sends* data: this is called POST).

- Create = `create()` shows the empty form, `store()` saves it
- Read = `index()` shows the list
- Update = `edit()` shows the filled form, `update()` saves it
- Delete = `delete()` removes the item (a small form with a Delete button triggers it)

**Words you will meet**

- **Slug**: the web-friendly version of a name. "My First Post!" becomes `my-first-post` and is used in the address `/blog/my-first-post`.
- **Session**: the server's memory of who is logged in.
- **Flash message**: a green or red message shown once (for example "Category created.") and then forgotten. Onebuild's top menu already displays them. You only write `$_SESSION['success'] = '...'` or `$_SESSION['error'] = '...'`.
- **CSRF token**: a secret code hidden inside every form. It proves the form came from your own site and not from a trick page. Every form you build contains one, and every action that receives a form checks it.
- **Layout**: a file holding the parts that every page shares (top menu, sidebar, footer).

**What every Onebuild model can already do** (free, you inherit it):

- `findAll('name ASC')`: all rows, sorted
- `findById(5)`: one row, or `null` when it does not exist
- `insert([...])`: add a row (the dates are filled in for you)
- `update(5, [...])`: change a row
- `delete(5)`: remove a row
- `query('SELECT ...', [values])`: your own SQL, returns many rows
- `queryOne('SELECT ...', [values])`: your own SQL, returns one row or `null`

**Files you will make (NEW) or change (EDIT)**

```text
database (phpMyAdmin)                           2 new tables
app/Helpers/BlogHelper.php                      NEW
app/Models/CategoryModel.php                    NEW
app/Models/ArticleModel.php                     NEW
app/Controllers/AdminCategoryController.php     NEW
app/Controllers/AdminArticleController.php      NEW
app/Controllers/BlogController.php              NEW
views/layouts/admin_top.php                     NEW
views/layouts/admin_bottom.php                  NEW
views/admin/categories/  _form, create, edit, index    NEW (4 files)
views/admin/articles/    _form, create, edit, index    NEW (4 files)
views/blog/              index, show                   NEW (2 files)
views/dashboard/_sidebar.php                    EDIT
views/layouts/navbar.php                        EDIT
public/index.php                                EDIT
```

## Part 1: The database tables

A table is like an Excel sheet: columns across the top, one row for each item.

**Step 1.1** Open phpMyAdmin (or HeidiSQL) and click your Onebuild database.

**Step 1.2** Click the **SQL** tab (HeidiSQL: the Query tab). Type this, then press **Go** (HeidiSQL: Execute).

```sql
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `articles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `content` TEXT NOT NULL,
    `image` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

The lines that matter:

- `id ... AUTO_INCREMENT`: the database numbers every row 1, 2, 3... for you.
- `slug ... UNIQUE`: two rows can never have the same slug.
- `category_id`: the link that says which category an article belongs to.
- `ON DELETE RESTRICT`: the database refuses to delete a category that still has articles. This protects you from wiping out articles by accident.
- `created_at`, `updated_at`: dates that fill themselves in.

**CHECKPOINT 1.** In the list of tables you now see `categories` and `articles`, both empty.

## Part 2: The helper file

A **helper** is a toolbox of small functions that belong to no single page. Ours has five tools:

- `makeSlug()` turns "My First Post!" into `my-first-post`.
- `imageFolder()` says where pictures are stored.
- `saveImageAsWebp()` checks an uploaded picture, converts it to WebP (a small, fast format) and saves it under a random name.
- `deleteImage()` removes a picture file.
- `excerpt()` cuts a long text short for the list page.

**Step 2.1** Create a new folder named `Helpers` inside the `app` folder.

**Step 2.2** Create the file below.

**FILE: `app/Helpers/BlogHelper.php`  (NEW FILE)**

```php
<?php

namespace App\Helpers;

use Exception;

class BlogHelper
{
    /**
     * Turns any text into a clean web-address word.
     * Example: "My First Post!" becomes "my-first-post"
     */
    public static function makeSlug(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9 -]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        $text = trim($text, '-');

        if ($text === '') {
            return 'n-a';
        }

        return $text;
    }

    /**
     * The folder on the server where article images are stored.
     */
    public static function imageFolder(): string
    {
        return dirname(__DIR__, 2) . '/public/uploads/images';
    }

    /**
     * Takes an uploaded image (JPG, PNG or WebP), converts it to WebP,
     * saves it in the folder, and returns the new file name.
     * If something is wrong it throws an Exception with a friendly message.
     */
    public static function saveImageAsWebp(array $file, string $folder): string
    {
        $maxSize = 2 * 1024 * 1024; // 2 MB

        if ($file['size'] > $maxSize) {
            throw new Exception('The image is too big. Maximum size is 2 MB.');
        }

        $info = getimagesize($file['tmp_name']);
        if ($info === false) {
            throw new Exception('The uploaded file is not a valid image.');
        }

        $mime = $info['mime'];
        $image = false;

        if ($mime === 'image/jpeg') {
            $image = imagecreatefromjpeg($file['tmp_name']);
        } elseif ($mime === 'image/png') {
            $image = imagecreatefrompng($file['tmp_name']);
            if ($image !== false) {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
        } elseif ($mime === 'image/webp') {
            $image = imagecreatefromwebp($file['tmp_name']);
        } else {
            throw new Exception('Only JPG, PNG and WebP images are allowed.');
        }

        if ($image === false) {
            throw new Exception('The image could not be read. Please try another file.');
        }

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        $filename = 'img_' . bin2hex(random_bytes(8)) . '.webp';
        $saved = imagewebp($image, $folder . '/' . $filename, 80);

        if ($saved === false) {
            throw new Exception('The image could not be saved.');
        }

        return $filename;
    }

    /**
     * Deletes an image file from the folder (does nothing if it is not there).
     */
    public static function deleteImage(string $folder, string $filename): void
    {
        if ($filename === '') {
            return;
        }

        $path = $folder . '/' . basename($filename);
        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * Returns the first part of a long text, for the blog list page.
     */
    public static function excerpt(string $text, int $length = 120): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length) . '...';
    }
}
```

What to notice:

- `namespace App\Helpers;` tells Onebuild where the file lives. The namespace `App\Helpers` means the folder `app/Helpers`. Because of this, you never have to write `require` for your own classes: Onebuild finds them by name.
- `static` means you can use the tool directly, without making an object first: `BlogHelper::makeSlug('Hello')`.
- `throw new Exception('...')` rings an alarm that carries a message. The controller will catch it and show the message to the user.
- Images are saved in `public/uploads/images`. That folder already exists in Onebuild and already blocks the running of any program placed inside it, which keeps uploads safe.

Run the typo detector: `php -l app/Helpers/BlogHelper.php`

## Part 3: The models

A model is the only place that talks to the database. Both of ours inherit all the free methods (`findAll`, `findById`, `insert`, `update`, `delete`) and add a few of their own.

**FILE: `app/Models/CategoryModel.php`  (NEW FILE)**

```php
<?php

namespace App\Models;

use App\Core\Model;

class CategoryModel extends Model
{
    protected string $table = 'categories';

    /**
     * Is this slug already used by another category?
     */
    public function slugExists(string $slug, int $ignoreId = 0): bool
    {
        $row = $this->queryOne(
            "SELECT id FROM categories WHERE slug = ? AND id != ? LIMIT 1",
            [$slug, $ignoreId]
        );

        return $row !== null;
    }

    /**
     * How many articles belong to this category?
     */
    public function countArticles(int $categoryId): int
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS total FROM articles WHERE category_id = ?",
            [$categoryId]
        );

        return (int) $row['total'];
    }
}
```

**FILE: `app/Models/ArticleModel.php`  (NEW FILE)**

```php
<?php

namespace App\Models;

use App\Core\Model;

class ArticleModel extends Model
{
    protected string $table = 'articles';

    /**
     * Is this slug already used by another article?
     */
    public function slugExists(string $slug, int $ignoreId = 0): bool
    {
        $row = $this->queryOne(
            "SELECT id FROM articles WHERE slug = ? AND id != ? LIMIT 1",
            [$slug, $ignoreId]
        );

        return $row !== null;
    }

    /**
     * All articles, newest first, together with their category name.
     */
    public function getAllWithCategory(): array
    {
        $sql = "SELECT articles.*, categories.name AS category_name
                FROM articles
                LEFT JOIN categories ON articles.category_id = categories.id
                ORDER BY articles.id DESC";

        return $this->query($sql);
    }

    /**
     * One article (found by its slug) together with its category name.
     */
    public function findWithCategoryBySlug(string $slug): ?array
    {
        $sql = "SELECT articles.*, categories.name AS category_name
                FROM articles
                LEFT JOIN categories ON articles.category_id = categories.id
                WHERE articles.slug = ?
                LIMIT 1";

        return $this->queryOne($sql, [$slug]);
    }
}
```

What to notice:

- `extends Model` means "start with everything a normal Onebuild model can do".
- `protected string $table = 'articles';` tells the model which table it manages.
- Each `?` in the SQL is a blank that is filled safely with the matching value from the list `[...]`. **Never paste user text directly into SQL.** The `?` blanks are what protect your site from SQL injection (a classic hacking trick).
- `LEFT JOIN` borrows the category name from the other table, so the list page can show a name instead of a number.
- `?array` means "returns an array, or `null` when nothing was found".

Run the typo detector on both files.

## Part 4: Shared layout pieces

Every admin page needs the same frame: header, top menu, sidebar, and a footer. Instead of repeating 15 lines in eight files, we make two small files: one **opens** the frame, one **closes** it. Each page puts its own content in between, like a picture in a picture frame.

**FILE: `views/layouts/admin_top.php`  (NEW FILE)**

```php
<?php
// Opens every admin page: header + top menu + sidebar + start of the main area.
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/navbar.php';
?>
<link rel="stylesheet" href="<?= $base ?>/assets/css/dashboard.css" nonce="<?= $nonce ?>">

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../dashboard/_sidebar.php'; ?>

        <section class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
            </div>
```

**FILE: `views/layouts/admin_bottom.php`  (NEW FILE)**

```php
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
```

What to notice:

- `require_once` means "paste that file here".
- `<?= $base ?>` prints your website's base address. Put it in front of every link and every file path, so the site works no matter which folder or domain it runs on.
- `<?= htmlspecialchars(...) ?>` prints text safely. **Always** wrap text that comes from the database or from a visitor in `htmlspecialchars()`. Without it a visitor could inject harmful code into your page.
- `$nonce` is a one-time pass that Onebuild's security rules require on every inline `<script>` and `<link>`. Remember this: **an inline `<script>` without `nonce="<?= $nonce ?>"` is blocked by the browser.**

**Step 4.3** Add two links to the sidebar.

**FILE: `views/dashboard/_sidebar.php`  (EDIT FILE)**

Open the file and find this existing block (the "Overview" menu item):

```php
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>" href="<?= $base ?>/dashboard">
                    <i class="bi bi-speedometer2"></i> Overview
                </a>
            </li>
```

Directly **under** that block (after its closing `</li>`) add:

```php
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'categories' ? 'active' : '' ?>" href="<?= $base ?>/dashboard/categories">
                    <i class="bi bi-tags"></i> Categories
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'articles' ? 'active' : '' ?>" href="<?= $base ?>/dashboard/articles">
                    <i class="bi bi-journal-text"></i> Articles
                </a>
            </li>
```

(The `? 'active' : ''` part copies the style already used in that file: it adds the word `active` to highlight the menu item of the page you are on.)

**CHECKPOINT 4.** Sign in and open `/dashboard`. The sidebar now shows **Categories** and **Articles**. Clicking them gives a "404" page for now. That is correct: you have not made those pages yet.

## Part 5: Category CRUD

We build the complete cycle for categories first because it is the smallest one. Articles (Part 6) use exactly the same pattern, with a few extras.

**Step 5.1** The controller. One class with six actions you can see in the browser (`index`, `create`, `store`, `edit`, `update`, `delete`) plus two helper methods that tidy up and check the form data.

**FILE: `app/Controllers/AdminCategoryController.php`  (NEW FILE)**

```php
<?php

namespace App\Controllers;

use PDO;
use App\Core\Controller;
use App\Core\CsrfProtection;
use App\Core\Validator;
use App\Helpers\BlogHelper;
use App\Models\CategoryModel;

class AdminCategoryController extends Controller
{
    private CategoryModel $categoryModel;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->categoryModel = new CategoryModel($pdo);
    }

    /**
     * Page: list of all categories
     */
    public function index(): void
    {
        $this->requireAdmin();

        $categories = $this->categoryModel->findAll('name ASC');

        $this->view('admin/categories/index', [
            'pageTitle'   => 'Categories',
            'currentPage' => 'categories',
            'categories'  => $categories,
        ]);
    }

    /**
     * Page: empty form to add a category
     */
    public function create(): void
    {
        $this->requireAdmin();

        $this->view('admin/categories/create', [
            'pageTitle'   => 'New Category',
            'currentPage' => 'categories',
            'category'    => ['name' => '', 'slug' => ''],
        ]);
    }

    /**
     * Action: save the new category (the form sends data here)
     */
    public function store(): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/dashboard/categories');
            return;
        }
        CsrfProtection::requireValidToken();

        $category = $this->readForm();
        $error = $this->validateCategory($category, 0);

        if ($error !== '') {
            $_SESSION['error'] = $error;
            $this->view('admin/categories/create', [
                'pageTitle'   => 'New Category',
                'currentPage' => 'categories',
                'category'    => $category,
            ]);
            return;
        }

        $this->categoryModel->insert([
            'name' => $category['name'],
            'slug' => $category['slug'],
        ]);

        $_SESSION['success'] = 'Category created.';
        $this->redirect('/dashboard/categories');
    }

    /**
     * Page: form filled with the current values of one category
     */
    public function edit(int $id): void
    {
        $this->requireAdmin();

        $category = $this->categoryModel->findById($id);
        if ($category === null) {
            $_SESSION['error'] = 'Category not found.';
            $this->redirect('/dashboard/categories');
            return;
        }

        $this->view('admin/categories/edit', [
            'pageTitle'   => 'Edit Category',
            'currentPage' => 'categories',
            'category'    => $category,
        ]);
    }

    /**
     * Action: save the changes (the edit form sends data here)
     */
    public function update(int $id): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/dashboard/categories');
            return;
        }
        CsrfProtection::requireValidToken();

        $existing = $this->categoryModel->findById($id);
        if ($existing === null) {
            $_SESSION['error'] = 'Category not found.';
            $this->redirect('/dashboard/categories');
            return;
        }

        $category = $this->readForm();
        $category['id'] = $id;
        $error = $this->validateCategory($category, $id);

        if ($error !== '') {
            $_SESSION['error'] = $error;
            $this->view('admin/categories/edit', [
                'pageTitle'   => 'Edit Category',
                'currentPage' => 'categories',
                'category'    => $category,
            ]);
            return;
        }

        $this->categoryModel->update($id, [
            'name' => $category['name'],
            'slug' => $category['slug'],
        ]);

        $_SESSION['success'] = 'Category updated.';
        $this->redirect('/dashboard/categories');
    }

    /**
     * Action: delete one category
     */
    public function delete(int $id): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/dashboard/categories');
            return;
        }
        CsrfProtection::requireValidToken();

        $category = $this->categoryModel->findById($id);
        if ($category === null) {
            $_SESSION['error'] = 'Category not found.';
            $this->redirect('/dashboard/categories');
            return;
        }

        $total = $this->categoryModel->countArticles($id);
        if ($total > 0) {
            $_SESSION['error'] = 'This category still has ' . $total . ' article(s). Move or delete them first.';
            $this->redirect('/dashboard/categories');
            return;
        }

        $this->categoryModel->delete($id);

        $_SESSION['success'] = 'Category deleted.';
        $this->redirect('/dashboard/categories');
    }

    /**
     * Reads the form fields and cleans them up.
     */
    private function readForm(): array
    {
        $name = Validator::sanitize($_POST['name'] ?? '', 100);
        $slug = Validator::sanitize($_POST['slug'] ?? '', 100);

        if ($slug === '') {
            $slug = $name;
        }
        $slug = BlogHelper::makeSlug($slug);

        return ['name' => $name, 'slug' => $slug];
    }

    /**
     * Checks the data. Returns an error message, or '' when everything is fine.
     */
    private function validateCategory(array $category, int $ignoreId): string
    {
        if ($category['name'] === '') {
            return 'Category name is required.';
        }

        if ($this->categoryModel->slugExists($category['slug'], $ignoreId)) {
            return 'This slug is already used by another category. Please type a different slug.';
        }

        return '';
    }
}
```

How to read it:

1. `__construct` runs when Onebuild creates the controller. `parent::__construct($pdo)` hands the database connection to the Onebuild base controller. Then we create our model.
2. The first line of every action is `$this->requireAdmin();`. Anybody who is not logged in as admin or root is sent to the login page. **Forget this line and that page is open to the whole world.**
3. `store`, `update` and `delete` receive a form, so they check two things first: the request really is a POST, and the secret CSRF code is correct (`CsrfProtection::requireValidToken()`). A wrong or missing code stops everything with error 419.
4. `$_SESSION['success'] = '...'` stores a flash message. `$this->redirect(...)` then sends the browser back to the list, where the message appears once. Redirecting after saving also means that pressing the Refresh key will not save twice.
5. When the data is wrong we do **not** redirect. We show the form again with what the user already typed (`'category' => $category`), so nothing has to be retyped.
6. `readForm()` collects and cleans the typed values. `Validator::sanitize()` is a Onebuild tool that trims spaces, removes HTML tags and cuts the text to a maximum length. `$_POST['name'] ?? ''` means "the field `name`, or an empty text if it is missing". If the slug field is empty, the name is used to make one.
7. `delete()` refuses to remove a category that still contains articles.
8. The number in `edit(int $id)` comes from the address (`/dashboard/categories/edit/3`). Step 5.3 shows how the route passes it in.

Run the typo detector.

**Step 5.2** The four views. Create the folders `views/admin` and `views/admin/categories` first.

The form is needed twice (create and edit), so we write it once in `_form.php`. A file name that starts with an underscore is a *piece* that other pages paste in. It is not a page by itself.

**FILE: `views/admin/categories/_form.php`  (NEW FILE)**

```php
<form method="POST" action="<?= $formAction ?>">
    <?= \App\Core\CsrfProtection::getHiddenInput() ?>

    <div class="mb-3">
        <label for="name" class="form-label">Category Name</label>
        <input type="text" class="form-control" id="name" name="name"
               value="<?= htmlspecialchars($category['name']) ?>" maxlength="100" required>
    </div>

    <div class="mb-3">
        <label for="slug" class="form-label">Slug (optional)</label>
        <input type="text" class="form-control" id="slug" name="slug"
               value="<?= htmlspecialchars($category['slug']) ?>" maxlength="100">
        <div class="form-text">The word used in the web address. Leave it empty and it is created from the name.</div>
    </div>

    <button type="submit" class="btn btn-primary"><?= $submitLabel ?></button>
    <a href="<?= $base ?>/dashboard/categories" class="btn btn-secondary">Cancel</a>
</form>
```

**FILE: `views/admin/categories/create.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../../layouts/admin_top.php'; ?>

<?php
$formAction  = $base . '/dashboard/categories/store';
$submitLabel = 'Save Category';
?>

<div class="row">
    <div class="col-lg-6">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/admin_bottom.php'; ?>
```

**FILE: `views/admin/categories/edit.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../../layouts/admin_top.php'; ?>

<?php
$formAction  = $base . '/dashboard/categories/update/' . (int) $category['id'];
$submitLabel = 'Update Category';
?>

<div class="row">
    <div class="col-lg-6">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/admin_bottom.php'; ?>
```

**FILE: `views/admin/categories/index.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../../layouts/admin_top.php'; ?>

<div class="mb-3">
    <a href="<?= $base ?>/dashboard/categories/create" class="btn btn-primary">Add New Category</a>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="4" class="text-center text-muted">No categories yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?= (int) $cat['id'] ?></td>
                        <td><?= htmlspecialchars($cat['name']) ?></td>
                        <td><?= htmlspecialchars($cat['slug']) ?></td>
                        <td>
                            <a href="<?= $base ?>/dashboard/categories/edit/<?= (int) $cat['id'] ?>" class="btn btn-sm btn-warning">Edit</a>

                            <form method="POST" action="<?= $base ?>/dashboard/categories/delete/<?= (int) $cat['id'] ?>"
                                  class="d-inline js-confirm" data-message="Delete this category?">
                                <?= \App\Core\CsrfProtection::getHiddenInput() ?>
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script nonce="<?= $nonce ?>">
// Ask "Are you sure?" before any Delete button sends its form.
document.querySelectorAll('.js-confirm').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        if (!confirm(form.dataset.message)) {
            event.preventDefault();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/admin_bottom.php'; ?>
```

What to notice in the views:

- `<?php if (...): ?> ... <?php else: ?> ... <?php endif; ?>` and `<?php foreach (...): ?> ... <?php endforeach; ?>` are PHP's way of writing "if" and "repeat for each row" in the middle of HTML. Every `if` and `foreach` must be closed by its `endif` and `endforeach`.
- `<?= \App\Core\CsrfProtection::getHiddenInput() ?>` prints the hidden secret code. **Every form needs it**, or the controller answers with error 419.
- **Delete is a tiny form with a button, not a plain link.** A plain link can be triggered by a trick page on another website. A form with the secret code cannot.
- The little `<script>` at the bottom shows the "Are you sure?" popup. It is plain JavaScript (no jQuery needed) and it carries `nonce="<?= $nonce ?>"`, which Onebuild's security rules demand.
- There is no `style="..."` anywhere. Onebuild's security rules block inline styles, so we use Bootstrap's ready-made classes (`mb-3`, `col-lg-6`, `d-inline` and so on) instead.

**Step 5.3** Tell Onebuild which address belongs to which method. This is the route list.

**FILE: `public/index.php`  (EDIT FILE)**

Scroll down to the end of the file and find these lines:

```php
// Protected route example
$router->addRoute('dashboard', 'DashboardController', 'index');
$router->addRoute('dashboard/profile', 'DashboardController', 'profile');

// Dispatch request - FIXED FOR SUBDIRECTORY
```

Add the following block **between** the `dashboard/profile` line and the `// Dispatch request` comment:

```php
// Admin: categories
$router->addRoute('dashboard/categories', 'AdminCategoryController', 'index');
$router->addRoute('dashboard/categories/create', 'AdminCategoryController', 'create');
$router->addRoute('dashboard/categories/store', 'AdminCategoryController', 'store');
$router->addRoute('dashboard/categories/edit/([0-9]+)', 'AdminCategoryController', 'edit');
$router->addRoute('dashboard/categories/update/([0-9]+)', 'AdminCategoryController', 'update');
$router->addRoute('dashboard/categories/delete/([0-9]+)', 'AdminCategoryController', 'delete');

```

How a route reads: `addRoute('address', 'ControllerName', 'methodName')`. The address has no slash at the start. `([0-9]+)` means "one or more digits", and whatever digits are found are handed to the method: the address `dashboard/categories/edit/3` calls `edit(3)`.

**CHECKPOINT 5. Try everything.**

1. Open a private/incognito browser window and visit `/dashboard/categories`. You must be sent to the login page. (This proves the admin lock works.)
2. In your normal window, click **Categories** in the sidebar. You see the table with "No categories yet."
3. Click **Add New Category**. Type `Technology News`, leave Slug empty, click **Save Category**. You return to the list with a green message, and the slug shows `technology-news`.
4. Add `Technology News` again. This time you stay on the form, see a red message that the slug is already used, and your typed text is still there.
5. Click **Edit** on the row. Change the name to `Tech` and the slug to `tech`. Click **Update Category**.
6. Add a category named `Temporary`. Click its **Delete** button. A popup asks "Delete this category?". Click Cancel: nothing happens. Click Delete again and then OK: it disappears.

Keep the `Tech` category for the next Part. If the popup does not appear when you click Delete, the `nonce="<?= $nonce ?>"` part of the script line is probably mistyped.

## Part 6: Article CRUD

Articles follow the very same pattern as categories. Three things are new:

- a **drop-down list** to pick the category,
- a **picture upload** (converted to WebP and saved in `public/uploads/images`),
- on edit, the old picture is **kept** unless you choose a new one (and the old file is removed when replaced or when the article is deleted).

**Step 6.1** The controller.

**FILE: `app/Controllers/AdminArticleController.php`  (NEW FILE)**

```php
<?php

namespace App\Controllers;

use PDO;
use Exception;
use App\Core\Controller;
use App\Core\CsrfProtection;
use App\Core\Validator;
use App\Helpers\BlogHelper;
use App\Models\ArticleModel;
use App\Models\CategoryModel;

class AdminArticleController extends Controller
{
    private ArticleModel $articleModel;
    private CategoryModel $categoryModel;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->articleModel  = new ArticleModel($pdo);
        $this->categoryModel = new CategoryModel($pdo);
    }

    /**
     * Page: list of all articles
     */
    public function index(): void
    {
        $this->requireAdmin();

        $articles = $this->articleModel->getAllWithCategory();

        $this->view('admin/articles/index', [
            'pageTitle'   => 'Articles',
            'currentPage' => 'articles',
            'articles'    => $articles,
        ]);
    }

    /**
     * Page: empty form to write a new article
     */
    public function create(): void
    {
        $this->requireAdmin();

        $categories = $this->categoryModel->findAll('name ASC');
        if (empty($categories)) {
            $_SESSION['error'] = 'Please create a category first.';
            $this->redirect('/dashboard/categories');
            return;
        }

        $this->view('admin/articles/create', [
            'pageTitle'   => 'New Article',
            'currentPage' => 'articles',
            'categories'  => $categories,
            'article'     => [
                'title'       => '',
                'slug'        => '',
                'category_id' => 0,
                'content'     => '',
                'image'       => '',
            ],
        ]);
    }

    /**
     * Action: save the new article
     */
    public function store(): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/dashboard/articles');
            return;
        }
        CsrfProtection::requireValidToken();

        $article = $this->readForm();
        $article['image'] = '';
        $error = $this->validateArticle($article, 0);

        $newImage = '';
        if ($error === '') {
            try {
                $newImage = $this->handleUpload();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        if ($error !== '') {
            $_SESSION['error'] = $error;
            $this->view('admin/articles/create', [
                'pageTitle'   => 'New Article',
                'currentPage' => 'articles',
                'categories'  => $this->categoryModel->findAll('name ASC'),
                'article'     => $article,
            ]);
            return;
        }

        $this->articleModel->insert([
            'category_id' => $article['category_id'],
            'title'       => $article['title'],
            'slug'        => $article['slug'],
            'content'     => $article['content'],
            'image'       => $newImage,
        ]);

        $_SESSION['success'] = 'Article published.';
        $this->redirect('/dashboard/articles');
    }

    /**
     * Page: form filled with the current values of one article
     */
    public function edit(int $id): void
    {
        $this->requireAdmin();

        $article = $this->articleModel->findById($id);
        if ($article === null) {
            $_SESSION['error'] = 'Article not found.';
            $this->redirect('/dashboard/articles');
            return;
        }

        $this->view('admin/articles/edit', [
            'pageTitle'   => 'Edit Article',
            'currentPage' => 'articles',
            'categories'  => $this->categoryModel->findAll('name ASC'),
            'article'     => $article,
        ]);
    }

    /**
     * Action: save the changes
     */
    public function update(int $id): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/dashboard/articles');
            return;
        }
        CsrfProtection::requireValidToken();

        $existing = $this->articleModel->findById($id);
        if ($existing === null) {
            $_SESSION['error'] = 'Article not found.';
            $this->redirect('/dashboard/articles');
            return;
        }

        $article = $this->readForm();
        $article['id'] = $id;
        $article['image'] = $existing['image'];
        $error = $this->validateArticle($article, $id);

        $newImage = '';
        if ($error === '') {
            try {
                $newImage = $this->handleUpload();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        if ($error !== '') {
            $_SESSION['error'] = $error;
            $this->view('admin/articles/edit', [
                'pageTitle'   => 'Edit Article',
                'currentPage' => 'articles',
                'categories'  => $this->categoryModel->findAll('name ASC'),
                'article'     => $article,
            ]);
            return;
        }

        $imageName = $existing['image'];
        if ($newImage !== '') {
            BlogHelper::deleteImage(BlogHelper::imageFolder(), (string) $existing['image']);
            $imageName = $newImage;
        }

        $this->articleModel->update($id, [
            'category_id' => $article['category_id'],
            'title'       => $article['title'],
            'slug'        => $article['slug'],
            'content'     => $article['content'],
            'image'       => $imageName,
        ]);

        $_SESSION['success'] = 'Article updated.';
        $this->redirect('/dashboard/articles');
    }

    /**
     * Action: delete one article (and its image file)
     */
    public function delete(int $id): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/dashboard/articles');
            return;
        }
        CsrfProtection::requireValidToken();

        $article = $this->articleModel->findById($id);
        if ($article === null) {
            $_SESSION['error'] = 'Article not found.';
            $this->redirect('/dashboard/articles');
            return;
        }

        BlogHelper::deleteImage(BlogHelper::imageFolder(), (string) $article['image']);
        $this->articleModel->delete($id);

        $_SESSION['success'] = 'Article deleted.';
        $this->redirect('/dashboard/articles');
    }

    /**
     * Reads the form fields and cleans them up.
     */
    private function readForm(): array
    {
        $title      = Validator::sanitize($_POST['title'] ?? '', 255);
        $slug       = Validator::sanitize($_POST['slug'] ?? '', 255);
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $content    = trim($_POST['content'] ?? '');

        if ($slug === '') {
            $slug = $title;
        }
        $slug = BlogHelper::makeSlug($slug);

        return [
            'title'       => $title,
            'slug'        => $slug,
            'category_id' => $categoryId,
            'content'     => $content,
        ];
    }

    /**
     * Checks the data. Returns an error message, or '' when everything is fine.
     */
    private function validateArticle(array $article, int $ignoreId): string
    {
        if ($article['title'] === '') {
            return 'Title is required.';
        }

        if ($this->categoryModel->findById($article['category_id']) === null) {
            return 'Please choose a category.';
        }

        if ($article['content'] === '') {
            return 'Content is required.';
        }

        if (strlen($article['content']) > 60000) {
            return 'Content is too long.';
        }

        if ($this->articleModel->slugExists($article['slug'], $ignoreId)) {
            return 'This slug is already used by another article. Please type a different slug.';
        }

        return '';
    }

    /**
     * Saves the uploaded image (if any) and returns its file name.
     * Returns '' when no file was chosen. Throws an Exception on problems.
     */
    private function handleUpload(): string
    {
        if (!isset($_FILES['image'])) {
            return '';
        }

        $code = $_FILES['image']['error'];

        if ($code === UPLOAD_ERR_NO_FILE) {
            return '';
        }

        if ($code !== UPLOAD_ERR_OK) {
            throw new Exception('The image could not be uploaded (error code ' . $code . ').');
        }

        return BlogHelper::saveImageAsWebp($_FILES['image'], BlogHelper::imageFolder());
    }
}
```

What is new compared to the category controller:

- `try { ... } catch (Exception $e) { ... }` means "try this; if the helper rings its alarm (`throw`), do not crash, run the catch part instead". Here the catch part stores the alarm's message (`$e->getMessage()`) so it can be shown in red.
- `handleUpload()` looks at `$_FILES['image']`, PHP's box holding the uploaded picture. It returns the new file name, or an empty text when no picture was chosen.
- In `update()`: no new picture means the old one stays. A new picture replaces the old one, and the old file is deleted so your folder does not fill up with unused pictures.
- `create()` sends you to the Categories page when there is no category yet, because an article needs one.
- `(int) $_POST['category_id']` forces the value to be a whole number. Never trust what arrives from a browser.

Run the typo detector.

**Step 6.2** The four views. Create the folder `views/admin/articles` first.

**FILE: `views/admin/articles/_form.php`  (NEW FILE)**

```php
<form method="POST" action="<?= $formAction ?>" enctype="multipart/form-data">
    <?= \App\Core\CsrfProtection::getHiddenInput() ?>

    <div class="mb-3">
        <label for="title" class="form-label">Title</label>
        <input type="text" class="form-control" id="title" name="title"
               value="<?= htmlspecialchars($article['title']) ?>" maxlength="255" required>
    </div>

    <div class="mb-3">
        <label for="slug" class="form-label">Slug (optional)</label>
        <input type="text" class="form-control" id="slug" name="slug"
               value="<?= htmlspecialchars($article['slug']) ?>" maxlength="255">
        <div class="form-text">The word used in the web address. Leave it empty and it is created from the title.</div>
    </div>

    <div class="mb-3">
        <label for="category_id" class="form-label">Category</label>
        <select class="form-select" id="category_id" name="category_id" required>
            <option value="">-- Choose a category --</option>
            <?php foreach ($categories as $cat): ?>
                <?php
                $selected = '';
                if ((int) $cat['id'] === (int) $article['category_id']) {
                    $selected = 'selected';
                }
                ?>
                <option value="<?= (int) $cat['id'] ?>" <?= $selected ?>><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label for="image" class="form-label">Featured Image (JPG, PNG or WebP, max 2 MB)</label>
        <?php if (!empty($article['image'])): ?>
            <div class="mb-2">
                <img src="<?= $base ?>/uploads/images/<?= htmlspecialchars($article['image']) ?>"
                     class="img-thumbnail" width="150" alt="Current image">
                <div class="form-text">Current image. Choose a new file only if you want to replace it.</div>
            </div>
        <?php endif; ?>
        <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/webp">
    </div>

    <div class="mb-3">
        <label for="content" class="form-label">Content</label>
        <textarea class="form-control" id="content" name="content" rows="10" required><?= htmlspecialchars($article['content']) ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary"><?= $submitLabel ?></button>
    <a href="<?= $base ?>/dashboard/articles" class="btn btn-secondary">Cancel</a>
</form>
```

**FILE: `views/admin/articles/create.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../../layouts/admin_top.php'; ?>

<?php
$formAction  = $base . '/dashboard/articles/store';
$submitLabel = 'Publish Article';
?>

<div class="row">
    <div class="col-lg-8">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/admin_bottom.php'; ?>
```

**FILE: `views/admin/articles/edit.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../../layouts/admin_top.php'; ?>

<?php
$formAction  = $base . '/dashboard/articles/update/' . (int) $article['id'];
$submitLabel = 'Update Article';
?>

<div class="row">
    <div class="col-lg-8">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/admin_bottom.php'; ?>
```

**FILE: `views/admin/articles/index.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../../layouts/admin_top.php'; ?>

<div class="mb-3">
    <a href="<?= $base ?>/dashboard/articles/create" class="btn btn-primary">Write New Article</a>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
        <thead>
            <tr>
                <th>Image</th>
                <th>Title</th>
                <th>Category</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($articles)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted">No articles yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($articles as $art): ?>
                    <tr>
                        <td>
                            <?php if (!empty($art['image'])): ?>
                                <img src="<?= $base ?>/uploads/images/<?= htmlspecialchars($art['image']) ?>"
                                     width="60" class="img-thumbnail" alt="Thumbnail">
                            <?php else: ?>
                                <span class="badge bg-secondary">No image</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($art['title']) ?></td>
                        <td><?= htmlspecialchars($art['category_name'] ?? 'Uncategorized') ?></td>
                        <td><?= htmlspecialchars(date('d M Y', strtotime($art['created_at']))) ?></td>
                        <td>
                            <a href="<?= $base ?>/blog/<?= htmlspecialchars($art['slug']) ?>" class="btn btn-sm btn-info" target="_blank">View</a>
                            <a href="<?= $base ?>/dashboard/articles/edit/<?= (int) $art['id'] ?>" class="btn btn-sm btn-warning">Edit</a>

                            <form method="POST" action="<?= $base ?>/dashboard/articles/delete/<?= (int) $art['id'] ?>"
                                  class="d-inline js-confirm" data-message="Delete this article?">
                                <?= \App\Core\CsrfProtection::getHiddenInput() ?>
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script nonce="<?= $nonce ?>">
// Ask "Are you sure?" before any Delete button sends its form.
document.querySelectorAll('.js-confirm').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        if (!confirm(form.dataset.message)) {
            event.preventDefault();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/admin_bottom.php'; ?>
```

What is new in these views:

- A form that uploads a file **must** have `enctype="multipart/form-data"` in its `<form>` tag. Without it the picture never arrives.
- The drop-down: for every category we print an `<option>`. The one whose number matches the article's category gets the word `selected`, so the right choice is pre-picked when you edit.
- The "View" button on the list opens the public page of the article (you build it in Part 7).

**Step 6.3** Add the article routes.

**FILE: `public/index.php`  (EDIT FILE)**

Directly under the categories block you added in Step 5.3 (and still above the `// Dispatch request` comment), add:

```php
// Admin: articles
$router->addRoute('dashboard/articles', 'AdminArticleController', 'index');
$router->addRoute('dashboard/articles/create', 'AdminArticleController', 'create');
$router->addRoute('dashboard/articles/store', 'AdminArticleController', 'store');
$router->addRoute('dashboard/articles/edit/([0-9]+)', 'AdminArticleController', 'edit');
$router->addRoute('dashboard/articles/update/([0-9]+)', 'AdminArticleController', 'update');
$router->addRoute('dashboard/articles/delete/([0-9]+)', 'AdminArticleController', 'delete');

```

**CHECKPOINT 6. Try everything.**

1. Click **Articles** in the sidebar. You see "No articles yet."
2. Click **Write New Article**. Title: `My First Post!`. Category: `Tech`. Choose a small JPG or PNG picture. Content: type two short lines. Click **Publish Article**. You return to the list with a thumbnail and a green message.
3. Look inside the folder `public/uploads/images`. You find one new file whose name ends with `.webp`. That is your converted picture.
4. Write another article with the same title. You get the red "slug already used" message and your text is kept.
5. Try to upload a text file that you renamed to `picture.jpg`. You get "not a valid image".
6. Click **Edit** on the first article. Change the title, choose **no** new picture, click **Update Article**. The thumbnail is still there.
7. Edit it again, this time choose a different picture. Check the folder: there is still exactly one `.webp` file (the old one was removed).
8. Go to **Categories** and try to delete `Tech`. You get a red message: the category still has an article.
9. Back in **Articles**, make sure you end with **two articles** in the `Tech` category (one with a picture, one without). You need them for Part 7. You can test **Delete** on a third, temporary article.

## Part 7: The public blog pages

These are the pages ordinary visitors see. There is **no** `requireAdmin()` here on purpose: everybody may read. Only two actions are needed: a list (`index`) and one full article (`show`).

**Step 7.1** The controller.

**FILE: `app/Controllers/BlogController.php`  (NEW FILE)**

```php
<?php

namespace App\Controllers;

use PDO;
use App\Core\Controller;
use App\Models\ArticleModel;

class BlogController extends Controller
{
    private ArticleModel $articleModel;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->articleModel = new ArticleModel($pdo);
    }

    /**
     * Public page: list of all articles
     */
    public function index(): void
    {
        $articles = $this->articleModel->getAllWithCategory();

        $this->view('blog/index', [
            'pageTitle'       => 'Blog',
            'metaDescription' => 'Latest articles',
            'articles'        => $articles,
        ]);
    }

    /**
     * Public page: one full article
     */
    public function show(string $slug): void
    {
        $article = $this->articleModel->findWithCategoryBySlug($slug);

        if ($article === null) {
            http_response_code(404);
            $this->view('errors/404', ['pageTitle' => 'Article not found']);
            return;
        }

        $this->view('blog/show', [
            'pageTitle'       => $article['title'],
            'metaDescription' => 'Read: ' . $article['title'],
            'article'         => $article,
        ]);
    }
}
```

If no article matches the address, we answer with Onebuild's existing 404 page and the proper "404" code. The `pageTitle` text becomes the title in the browser tab.

**Step 7.2** The two views. Create the folder `views/blog` first. These pages use Onebuild's normal public frame (header, navbar, footer), exactly like `views/home/index.php` does.

**FILE: `views/blog/index.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container">
    <h1 class="mb-4">Latest Articles</h1>

    <?php if (empty($articles)): ?>
        <p class="text-muted">No articles have been published yet.</p>
    <?php else: ?>
        <div class="row">
            <?php foreach ($articles as $art): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <?php if (!empty($art['image'])): ?>
                            <img src="<?= $base ?>/uploads/images/<?= htmlspecialchars($art['image']) ?>"
                                 class="card-img-top" alt="<?= htmlspecialchars($art['title']) ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <span class="badge bg-primary mb-2"><?= htmlspecialchars($art['category_name'] ?? 'General') ?></span>
                            <h5 class="card-title"><?= htmlspecialchars($art['title']) ?></h5>
                            <p class="card-text text-muted"><?= htmlspecialchars(\App\Helpers\BlogHelper::excerpt($art['content'], 120)) ?></p>
                        </div>
                        <div class="card-footer bg-white border-0 pb-3">
                            <a href="<?= $base ?>/blog/<?= htmlspecialchars($art['slug']) ?>" class="btn btn-outline-primary btn-sm">Read more</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
```

**FILE: `views/blog/show.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <a href="<?= $base ?>/blog" class="btn btn-outline-secondary btn-sm mb-3">&larr; Back to articles</a>

            <span class="badge bg-info text-dark mb-2"><?= htmlspecialchars($article['category_name'] ?? 'General') ?></span>
            <h1 class="fw-bold"><?= htmlspecialchars($article['title']) ?></h1>
            <p class="text-muted small">Published on <?= htmlspecialchars(date('d M Y', strtotime($article['created_at']))) ?></p>

            <?php if (!empty($article['image'])): ?>
                <img src="<?= $base ?>/uploads/images/<?= htmlspecialchars($article['image']) ?>"
                     class="img-fluid rounded my-3 w-100" alt="<?= htmlspecialchars($article['title']) ?>">
            <?php endif; ?>

            <div class="mt-4 fs-5">
                <?= nl2br(htmlspecialchars($article['content'])) ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
```

What to notice: `nl2br(htmlspecialchars(...))` first makes the text safe, then turns your line breaks into `<br>` so paragraphs look right. The `??` in `$art['category_name'] ?? 'General'` means "use this value, or `General` if there is none".

**Step 7.3** Add a **Blog** link to the top menu.

**FILE: `views/layouts/navbar.php`  (EDIT FILE)**

Find the Home menu item:

```php
                <li class="nav-item">
                    <a class="nav-link <?= ($requestUri === '' || $requestUri === '/') ? 'active' : '' ?>" href="<?= $base ?>">Home</a>
                </li>
```

Directly **under** it add:

```php
                <li class="nav-item">
                    <a class="nav-link <?= stripos($requestUri, 'blog') === 0 ? 'active' : '' ?>" href="<?= $base ?>/blog">Blog</a>
                </li>
```

**Step 7.4** Add the two public routes.

**FILE: `public/index.php`  (EDIT FILE)**

Under the articles block, still above `// Dispatch request`, add:

```php
// Blog (public pages)
$router->addRoute('blog', 'BlogController', 'index');
$router->addRoute('blog/([a-z0-9-]+)', 'BlogController', 'show');

```

The pattern `([a-z0-9-]+)` means "letters, digits and dashes", which is exactly what a slug is made of. The address `blog/my-first-post` calls `show('my-first-post')`.

**CHECKPOINT 7. The final test.**

1. Open a private/incognito window (you are not logged in) and go to `/blog`. You see a card for each article, with picture, category badge, title and the start of the text.
2. Click **Read more**. You see the full article. The address in the browser ends with the slug, for example `/blog/my-first-post`.
3. Type `<b>hello</b>` inside an article's content and save. On the public page the visible text is literally `<b>hello</b>` and nothing turns bold. That is the `htmlspecialchars` protection working.
4. Visit `/blog/does-not-exist`. You get the 404 page.
5. In the private window visit `/dashboard/articles`. You are sent to the login page.
6. The top menu now has a **Blog** link. Your original home page, login and profile page still work.

**Congratulations. You have built a complete CRUD application.**

## Part 8: What happens when you click "Publish Article"

Read this slowly once. It ties everything together.

1. The browser sends the form (POST) to the address `/dashboard/articles/store`, with your text, the picture and the secret CSRF code.
2. `public/index.php` looks through the route list and finds `dashboard/articles/store`. It creates `AdminArticleController` and calls `store()`.
3. `store()` first runs `requireAdmin()` (are you an admin?) and `requireValidToken()` (is the secret code correct?).
4. `readForm()` collects and cleans the text. `validateArticle()` checks it (title present? real category? slug free?).
5. `handleUpload()` passes the picture to `BlogHelper::saveImageAsWebp()`, which checks that it really is an image, converts it and saves it.
6. `ArticleModel::insert()` (inherited from Onebuild's Model) writes the new row into the `articles` table.
7. The controller stores "Article published." in the session and redirects to `/dashboard/articles`.
8. The list page runs `index()`, asks `ArticleModel` for the rows, hands them to `views/admin/articles/index.php`, and the top menu shows the green message once.

Route, controller, model, view. Every page you will ever build in Onebuild is a variation of this chain.

## When something goes wrong

First: make sure `APP_ENV=development` is set in `.env` (local computer only). Then find your symptom.

**A white page, or "Something went wrong" / Error 500**

PHP found a problem it could not recover from. Run the typo detector (`php -l path/to/file.php`) on the file you edited last. If it says "No syntax errors", the message is hidden in your server's PHP error log (in Laragon: Menu, Apache or Nginx, then the error log). Read the last line.

**"Parse error: syntax error, unexpected ..." with a line number**

A typo. Look at that line **and the line above it**. Usual suspects: a missing `;` at the end of a line, a missing `)` or `}` or `]`, a missing closing quote, `->` typed as `-` or `=>` typed as `=`.

**A page you made shows the 404 page**

Onebuild shows 404 whenever it cannot match a route **or** cannot find the controller class or method. Check, in this order: (1) the route line in `public/index.php` is spelled exactly like the address and has **no** slash at the start; (2) the controller file name equals the class name, capitals included; (3) the method name in the route equals the real method name.

**"Class ... not found" or "View not found"**

The file is in the wrong folder or has the wrong name. `AdminCategoryController` must be in `app/Controllers/`, models in `app/Models/`, the helper in `app/Helpers/`, views under `views/`. Also check the `namespace` line at the top of the file.

**"Table ... doesn't exist" (an SQLSTATE message)**

You ran the SQL of Part 1 in the wrong database. The right one is named in the `DB_NAME` line of `.env`.

**"CSRF token validation failed" (error 419)**

The form is missing the line `<?= \App\Core\CsrfProtection::getHiddenInput() ?>`, or you left the form open for more than an hour (reload the page and try again), or the picture was bigger than your server allows and the browser's whole form was dropped. Try a smaller picture.

**"The image could not be uploaded (error code 1)" or "(error code 2)"**

The picture is larger than the server's limit. Choose a smaller picture, or raise `upload_max_filesize` and `post_max_size` in `php.ini` and restart the server.

**"Call to undefined function imagecreatefromwebp()" or "imagewebp()"**

PHP's picture extension is switched off. In `php.ini` enable `extension=gd` and restart the server (in Laragon: Menu, PHP, Extensions, tick `gd`).

**"Access denied. Admin privileges required."**

You are logged in, but your role is not `root` or `admin`. Open the `users` table in your database tool and look at the `role` column of your user.

**The "Are you sure?" popup does not appear when I click Delete**

The `<script>` line at the bottom of the list view is missing `nonce="<?= $nonce ?>"` or has a typo in it. The browser silently refuses scripts without the nonce.

**Pictures do not show up**

Open the folder `public/uploads/images`: is the `.webp` file there? If the folder is empty on a live server, the folder is probably not writable (give it permission 755).

**I fixed the file but nothing changed**

Save the file (Ctrl+S), then reload the browser page with Ctrl+F5.

## Make your own CRUD: the 10-step recipe

To manage anything else (products, clients, events...), copy the pattern. Example: **Products**.

1. Create the table in the database (`products` with the columns you need).
2. Copy `CategoryModel.php` to `ProductModel.php`. Change the class name, `$table`, and the SQL inside.
3. Copy `AdminCategoryController.php` to `AdminProductController.php`. Change the class name, the model, the view folder names, the address text (`/dashboard/products`) and the messages.
4. Adjust `readForm()` and `validateProduct()` to your fields.
5. Copy the four views of `views/admin/categories/` into `views/admin/products/` and change the fields and the table columns.
6. Add six routes to `public/index.php`.
7. Add a link in `views/dashboard/_sidebar.php` with its own `$currentPage` word.
8. Test: create, edit, delete, empty form, duplicate value, no login.
9. Read your controller once more and ask: is `requireAdmin()` the first line of every action? Does every form carry the CSRF code? Is every printed value wrapped in `htmlspecialchars()`?
10. Before going live, set `APP_ENV=production` in the live `.env`.

## Ideas for practice

- **Draft or published.** Add a column `status` to `articles` (`'draft'` or `'published'`), add a drop-down in the form, and show only published articles on `/blog`. Hint: the base Model already has `findAllPublished()`, which looks for `status = 'published'`.
- **Dashboard numbers.** Show "Total articles" and "Total categories" on the dashboard overview page. Hint: add a `countAll()` method to each model using `queryOne('SELECT COUNT(*) AS total FROM ...')`.
- **Articles per category.** Add the route `blog/category/([a-z0-9-]+)` that lists only the articles of one category.

If you get stuck on any step, tell me the file name and copy the exact error message. That is all I need to help you.
```

### tutorial/Gemini - step_by_step_guide_blog_category_crud_with_onebuild.md

```markdown
# Building Your First Blog with the onebuild PHP Framework

Welcome! This step-by-step guide is designed for non-programmers who want to build a complete Blog using the **onebuild** PHP MVC framework.

In this tutorial, you will create:

1. **Database Tables** for Categories and Articles.

2. **Helper File** for image handling and generating clean URLs (slugs).

3. **Navigation Bar (Menu)** for header navigation across the website and admin backend.

4. **Category & Article CRUD** (Backend) to create, edit, list, and delete items.

5. **Admin Dashboard Landing Page** to manage everything from one simple location.

6. **Public Frontend Pages** to display a list of articles and detailed single-article views.

## Code Rules Used in This Guide

* **No Ternary Operators**: We use explicit `if / else` statements so the logic is easy to read.

* **Technologies Used**: PHP, Bootstrap 5, jQuery 3, WebP image conversion.

---

## Step 1: Create the Database Tables

Open **phpMyAdmin** or your database client, select your database, and run this SQL query:

```sql
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `articles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `content` TEXT NOT NULL,
    `image` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Step 2: Create the Helper File (Image Upload & Slugs)

Create a helper file to handle **slug creation** and **WebP image conversion**.

📁 **File Location:** `app/Helpers/BlogHelper.php`

```php
<?php

namespace App\Helpers;

class BlogHelper
{
    /**
     * Converts any string into an SEO-friendly URL slug.
     */
    public static function makeSlug(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9 -]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        $text = trim($text, '-');
        
        if (empty($text)) {
            return 'n-a';
        }
        
        return $text;
    }

    /**
     * Uploads an image file and converts it to .webp format.
     */
    public static function uploadAndConvertToWebp(array $file, string $targetDirectory): string
    {
        if (empty($file['tmp_name'])) {
            return '';
        }

        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        $sourcePath = $file['tmp_name'];
        $fileInfo = getimagesize($sourcePath);

        if ($fileInfo === false) {
            return '';
        }

        $mimeType = $fileInfo['mime'];
        $imageResource = null;

        if ($mimeType === 'image/jpeg') {
            $imageResource = imagecreatefromjpeg($sourcePath);
        } else if ($mimeType === 'image/png') {
            $imageResource = imagecreatefrompng($sourcePath);
        } else if ($mimeType === 'image/webp') {
            $imageResource = imagecreatefromwebp($sourcePath);
        }

        if ($imageResource === null) {
            return '';
        }

        $filename = uniqid('img_', true) . '.webp';
        $destinationPath = rtrim($targetDirectory, '/') . '/' . $filename;

        imagewebp($imageResource, $destinationPath, 80);
        imagedestroy($imageResource);

        return $filename;
    }
}
```

---

## Step 3: Create the Navigation Bar (Menu Layout)

Create a reusable Bootstrap menu bar layout that sits on top of your site so you can easily navigate between public pages and the admin backend.

📁 **File Location:** `app/Views/layouts/navbar.php`

```php
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/">My Fast Blog</a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="mainNavbar">
      <!-- Left Menu Items -->
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="/">Home</a>
        </li>
      </ul>

      <!-- Right Menu Items (Admin Panel) -->
      <ul class="navbar-nav ms-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle btn btn-outline-light text-white px-3 border-0" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
            ⚙️ Admin Panel
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="/admin">Dashboard Overview</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="/admin/articles">Manage Articles</a></li>
            <li><a class="dropdown-item" href="/admin/categories">Manage Categories</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
```

---

## Step 4: Create the Models

Models communicate directly with database tables.

### 4.1 Category Model

📁 **File Location:** `app/Models/CategoryModel.php`

```php
<?php

namespace App\Models;

use App\Core\Model;

class CategoryModel extends Model
{
    protected string $table = 'categories';
    protected array $fillable = ['name', 'slug'];

    /**
     * Get total count for dashboard statistics.
     */
    public function countAll(): int
    {
        $sql = "SELECT COUNT(*) as total FROM categories";
        $result = $this->db->fetch($sql);
        return (int) ($result['total'] ?? 0);
    }
}
```

### 4.2 Article Model

📁 **File Location:** `app/Models/ArticleModel.php`

```php
<?php

namespace App\Models;

use App\Core\Model;

class ArticleModel extends Model
{
    protected string $table = 'articles';
    protected array $fillable = ['category_id', 'title', 'slug', 'content', 'image'];

    /**
     * Get total count for dashboard statistics.
     */
    public function countAll(): int
    {
        $sql = "SELECT COUNT(*) as total FROM articles";
        $result = $this->db->fetch($sql);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Custom query to get all articles along with their category name.
     */
    public function getAllWithCategory(): array
    {
        $sql = "SELECT articles.*, categories.name AS category_name 
                FROM articles 
                LEFT JOIN categories ON articles.category_id = categories.id 
                ORDER BY articles.id DESC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Get recent articles for dashboard overview.
     */
    public function getRecent(int $limit = 5): array
    {
        $sql = "SELECT articles.*, categories.name AS category_name 
                FROM articles 
                LEFT JOIN categories ON articles.category_id = categories.id 
                ORDER BY articles.id DESC LIMIT " . (int)$limit;
        return $this->db->fetchAll($sql);
    }

    /**
     * Find a single article by its slug along with its category name.
     */
    public function findBySlug(string $slug): ?array
    {
        $sql = "SELECT articles.*, categories.name AS category_name 
                FROM articles 
                LEFT JOIN categories ON articles.category_id = categories.id 
                WHERE articles.slug = :slug LIMIT 1";
        $result = $this->db->fetch($sql, ['slug' => $slug]);
        
        if ($result === false) {
            return null;
        }

        return $result;
    }
}
```

---

## Step 5: Create the Controllers

### 5.1 Admin Dashboard Controller

📁 **File Location:** `app/Controllers/AdminDashboardController.php`

```php
<?php

namespace App\Controllers;

use App\Core\Controller;

class AdminDashboardController extends Controller
{
    private $articleModel;
    private $categoryModel;

    public function __construct()
    {
        $this->articleModel = $this->model('ArticleModel');
        $this->categoryModel = $this->model('CategoryModel');
    }

    public function index(): void
    {
        $totalArticles = $this->articleModel->countAll();
        $totalCategories = $this->categoryModel->countAll();
        $recentArticles = $this->articleModel->getRecent(5);

        $this->view('admin/dashboard', [
            'totalArticles'   => $totalArticles,
            'totalCategories' => $totalCategories,
            'recentArticles'  => $recentArticles
        ]);
    }
}
```

### 5.2 Admin Category Controller

📁 **File Location:** `app/Controllers/AdminCategoryController.php`

```php
<?php

namespace App\Controllers;

use App\Core\Controller;

class AdminCategoryController extends Controller
{
    private $categoryModel;

    public function __construct()
    {
        $this->categoryModel = $this->model('CategoryModel');
    }

    public function index(): void
    {
        $categories = $this->categoryModel->all();
        $this->view('admin/categories/index', ['categories' => $categories]);
    }

    public function create(): void
    {
        $this->view('admin/categories/create');
    }

    public function store(): void
    {
        $name = $_POST['name'] ?? '';
        $slug = $_POST['slug'] ?? '';

        if (empty($slug)) {
            $slug = \App\Helpers\BlogHelper::makeSlug($name);
        } else {
            $slug = \App\Helpers\BlogHelper::makeSlug($slug);
        }

        $this->categoryModel->create([
            'name' => $name,
            'slug' => $slug
        ]);

        header('Location: /admin/categories');
        exit;
    }

    public function edit(int $id): void
    {
        $category = $this->categoryModel->find($id);
        $this->view('admin/categories/edit', ['category' => $category]);
    }

    public function update(int $id): void
    {
        $name = $_POST['name'] ?? '';
        $slug = $_POST['slug'] ?? '';

        if (empty($slug)) {
            $slug = \App\Helpers\BlogHelper::makeSlug($name);
        } else {
            $slug = \App\Helpers\BlogHelper::makeSlug($slug);
        }

        $this->categoryModel->update($id, [
            'name' => $name,
            'slug' => $slug
        ]);

        header('Location: /admin/categories');
        exit;
    }

    public function delete(int $id): void
    {
        $this->categoryModel->delete($id);
        header('Location: /admin/categories');
        exit;
    }
}
```

### 5.3 Admin Article Controller

📁 **File Location:** `app/Controllers/AdminArticleController.php`

```php
<?php

namespace App\Controllers;

use App\Core\Controller;

class AdminArticleController extends Controller
{
    private $articleModel;
    private $categoryModel;

    public function __construct()
    {
        $this->articleModel = $this->model('ArticleModel');
        $this->categoryModel = $this->model('CategoryModel');
    }

    public function index(): void
    {
        $articles = $this->articleModel->getAllWithCategory();
        $this->view('admin/articles/index', ['articles' => $articles]);
    }

    public function create(): void
    {
        $categories = $this->categoryModel->all();
        $this->view('admin/articles/create', ['categories' => $categories]);
    }

    public function store(): void
    {
        $title = $_POST['title'] ?? '';
        $slug = $_POST['slug'] ?? '';
        $categoryId = $_POST['category_id'] ?? 0;
        $content = $_POST['content'] ?? '';

        if (empty($slug)) {
            $slug = \App\Helpers\BlogHelper::makeSlug($title);
        } else {
            $slug = \App\Helpers\BlogHelper::makeSlug($slug);
        }

        $imageName = '';
        if (isset($_FILES['image'])) {
            if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/';
                $imageName = \App\Helpers\BlogHelper::uploadAndConvertToWebp($_FILES['image'], $uploadDir);
            }
        }

        $this->articleModel->create([
            'category_id' => $categoryId,
            'title'       => $title,
            'slug'        => $slug,
            'content'     => $content,
            'image'       => $imageName
        ]);

        header('Location: /admin/articles');
        exit;
    }

    public function edit(int $id): void
    {
        $article = $this->articleModel->find($id);
        $categories = $this->categoryModel->all();

        $this->view('admin/articles/edit', [
            'article'    => $article,
            'categories' => $categories
        ]);
    }

    public function update(int $id): void
    {
        $article = $this->articleModel->find($id);
        $title = $_POST['title'] ?? '';
        $slug = $_POST['slug'] ?? '';
        $categoryId = $_POST['category_id'] ?? 0;
        $content = $_POST['content'] ?? '';

        if (empty($slug)) {
            $slug = \App\Helpers\BlogHelper::makeSlug($title);
        } else {
            $slug = \App\Helpers\BlogHelper::makeSlug($slug);
        }

        $imageName = $article['image'];
        if (isset($_FILES['image'])) {
            if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/';
                $newImage = \App\Helpers\BlogHelper::uploadAndConvertToWebp($_FILES['image'], $uploadDir);
                if (!empty($newImage)) {
                    $imageName = $newImage;
                }
            }
        }

        $this->articleModel->update($id, [
            'category_id' => $categoryId,
            'title'       => $title,
            'slug'        => $slug,
            'content'     => $content,
            'image'       => $imageName
        ]);

        header('Location: /admin/articles');
        exit;
    }

    public function delete(int $id): void
    {
        $this->articleModel->delete($id);
        header('Location: /admin/articles');
        exit;
    }
}
```

### 5.4 Public Blog Controller

📁 **File Location:** `app/Controllers/BlogController.php`

```php
<?php

namespace App\Controllers;

use App\Core\Controller;

class BlogController extends Controller
{
    private $articleModel;

    public function __construct()
    {
        $this->articleModel = $this->model('ArticleModel');
    }

    public function index(): void
    {
        $articles = $this->articleModel->getAllWithCategory();
        $this->view('blog/index', ['articles' => $articles]);
    }

    public function show(string $slug): void
    {
        $article = $this->articleModel->findBySlug($slug);

        if ($article === null) {
            http_response_code(404);
            echo "Article not found.";
            return;
        }

        $this->view('blog/show', ['article' => $article]);
    }
}
```

---

## Step 6: Create the View Files

### 6.1 Admin Dashboard Central Hub View

📁 **File Location:** `app/Views/admin/dashboard.php`

```php
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Admin Dashboard</h2>
        <a href="/" target="_blank" class="btn btn-outline-secondary">View Website &rarr;</a>
    </div>

    <!-- Quick Navigation Bar -->
    <div class="card mb-4 bg-light">
        <div class="card-body d-flex justify-content-start gap-2">
            <a href="/admin" class="btn btn-dark active">Dashboard Overview</a>
            <a href="/admin/articles" class="btn btn-outline-primary">Manage Articles</a>
            <a href="/admin/categories" class="btn btn-outline-primary">Manage Categories</a>
        </div>
    </div>

    <!-- Summary Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Articles</h5>
                    <p class="card-text display-4 fw-bold"><?php echo $totalArticles; ?></p>
                    <a href="/admin/articles/create" class="btn btn-light btn-sm">+ Add Article</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Categories</h5>
                    <p class="card-text display-4 fw-bold"><?php echo $totalCategories; ?></p>
                    <a href="/admin/categories/create" class="btn btn-light btn-sm">+ Add Category</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Articles Table -->
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recently Added Articles</h5>
            <a href="/admin/articles" class="btn btn-sm btn-link">View All</a>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentArticles)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-3 text-muted">No articles found yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentArticles as $art): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($art['title']); ?></td>
                            <td><span class="badge bg-info"><?php echo htmlspecialchars($art['category_name'] ?? 'Uncategorized'); ?></span></td>
                            <td><?php echo $art['created_at']; ?></td>
                            <td>
                                <a href="/admin/articles/edit/<?php echo $art['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
```

### 6.2 Admin Views (Categories)

📁 **Category List View:** `app/Views/admin/categories/index.php`

```php
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Category Management</h2>
        <div>
            <a href="/admin" class="btn btn-outline-secondary me-2">&larr; Dashboard</a>
            <a href="/admin/categories/create" class="btn btn-primary">Add New Category</a>
        </div>
    </div>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th>Slug</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td><?php echo $cat['id']; ?></td>
                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                <td><?php echo htmlspecialchars($cat['slug']); ?></td>
                <td>
                    <a href="/admin/categories/edit/<?php echo $cat['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="/admin/categories/delete/<?php echo $cat['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this category?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
```

📁 **Category Create View:** `app/Views/admin/categories/create.php`

```php
<div class="container my-5" style="max-width: 600px;">
    <h2>Create New Category</h2>
    <form action="/admin/categories/store" method="POST">
        <div class="mb-3">
            <label class="form-label">Category Name</label>
            <input type="text" id="cat-name" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Slug (Optional)</label>
            <input type="text" id="cat-slug" name="slug" class="form-control">
        </div>
        <button type="submit" class="btn btn-success">Save Category</button>
        <a href="/admin/categories" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script>
$('#cat-name').on('keyup', function() {
    let val = $(this).val().toLowerCase().replace(/[^a-z0-9 -]/g, '').replace(/\s+/g, '-');
    $('#cat-slug').val(val);
});
</script>
```

📁 **Category Edit View:** `app/Views/admin/categories/edit.php`

```php
<div class="container my-5" style="max-width: 600px;">
    <h2>Edit Category</h2>
    <form action="/admin/categories/update/<?php echo $category['id']; ?>" method="POST">
        <div class="mb-3">
            <label class="form-label">Category Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($category['name']); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Slug</label>
            <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($category['slug']); ?>">
        </div>
        <button type="submit" class="btn btn-primary">Update Category</button>
        <a href="/admin/categories" class="btn btn-secondary">Cancel</a>
    </form>
</div>
```

### 6.3 Admin Views (Articles)

📁 **Article List View:** `app/Views/admin/articles/index.php`

```php
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Article Management</h2>
        <div>
            <a href="/admin" class="btn btn-outline-secondary me-2">&larr; Dashboard</a>
            <a href="/admin/articles/create" class="btn btn-primary">Create Article</a>
        </div>
    </div>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Image</th>
                <th>Title</th>
                <th>Category</th>
                <th>Slug</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($articles as $art): ?>
            <tr>
                <td>
                    <?php if (!empty($art['image'])): ?>
                        <img src="/uploads/<?php echo $art['image']; ?>" width="60" class="img-thumbnail" alt="thumbnail">
                    <?php else: ?>
                        <span class="badge bg-secondary">No Image</span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($art['title']); ?></td>
                <td><span class="badge bg-info"><?php echo htmlspecialchars($art['category_name'] ?? 'Uncategorized'); ?></span></td>
                <td><?php echo htmlspecialchars($art['slug']); ?></td>
                <td>
                    <a href="/admin/articles/edit/<?php echo $art['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="/admin/articles/delete/<?php echo $art['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete article?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
```

📁 **Article Create View:** `app/Views/admin/articles/create.php`

```php
<div class="container my-5" style="max-width: 800px;">
    <h2>Create New Article</h2>
    <form action="/admin/articles/store" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Article Title</label>
            <input type="text" id="article-title" name="title" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Slug</label>
            <input type="text" id="article-slug" name="slug" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Featured Image (Will autoconvert to WebP)</label>
            <input type="file" name="image" class="form-control" accept="image/*">
        </div>

        <div class="mb-3">
            <label class="form-label">Article Content</label>
            <textarea name="content" class="form-control" rows="8" required></textarea>
        </div>

        <button type="submit" class="btn btn-success">Publish Article</button>
        <a href="/admin/articles" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script>
$('#article-title').on('keyup', function() {
    let val = $(this).val().toLowerCase().replace(/[^a-z0-9 -]/g, '').replace(/\s+/g, '-');
    $('#article-slug').val(val);
});
</script>
```

📁 **Article Edit View:** `app/Views/admin/articles/edit.php`

```php
<div class="container my-5" style="max-width: 800px;">
    <h2>Edit Article</h2>
    <form action="/admin/articles/update/<?php echo $article['id']; ?>" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Article Title</label>
            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($article['title']); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Slug</label>
            <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($article['slug']); ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php if ($cat['id'] == $article['category_id']) { echo 'selected'; } ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Current Image</label><br>
            <?php if (!empty($article['image'])): ?>
                <img src="/uploads/<?php echo $article['image']; ?>" class="img-thumbnail mb-2" width="150" alt="Current Image">
            <?php else: ?>
                <p class="text-muted">No image uploaded.</p>
            <?php endif; ?>
            <input type="file" name="image" class="form-control" accept="image/*">
        </div>

        <div class="mb-3">
            <label class="form-label">Content</label>
            <textarea name="content" class="form-control" rows="8" required><?php echo htmlspecialchars($article['content']); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Update Article</button>
        <a href="/admin/articles" class="btn btn-secondary">Cancel</a>
    </form>
</div>
```

### 6.4 Public Frontend Views

📁 **Public Article List Page:** `app/Views/blog/index.php`

```php
<div class="container my-5">
    <h1 class="mb-4">Latest Blog Posts</h1>
    <div class="row">
        <?php foreach ($articles as $art): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <?php if (!empty($art['image'])): ?>
                        <img src="/uploads/<?php echo $art['image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($art['title']); ?>" style="height: 200px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <span class="badge bg-primary mb-2"><?php echo htmlspecialchars($art['category_name'] ?? 'General'); ?></span>
                        <h5 class="card-title"><?php echo htmlspecialchars($art['title']); ?></h5>
                        <p class="card-text text-muted">
                            <?php echo htmlspecialchars(substr($art['content'], 0, 100)); ?>...
                        </p>
                    </div>
                    <div class="card-footer bg-white border-0 pb-3">
                        <a href="/article/<?php echo $art['slug']; ?>" class="btn btn-outline-primary btn-sm">Read Full Article</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
```

📁 **Single Article Detail View:** `app/Views/blog/show.php`

```php
<div class="container my-5" style="max-width: 800px;">
    <a href="/" class="btn btn-outline-secondary mb-3">&larr; Back to Articles</a>
    
    <span class="badge bg-info text-dark d-inline-block mb-2"><?php echo htmlspecialchars($article['category_name']); ?></span>
    <h1 class="fw-bold"><?php echo htmlspecialchars($article['title']); ?></h1>
    <p class="text-muted small">Published on: <?php echo $article['created_at']; ?></p>

    <?php if (!empty($article['image'])): ?>
        <img src="/uploads/<?php echo $article['image']; ?>" class="img-fluid rounded my-3 w-100" alt="<?php echo htmlspecialchars($article['title']); ?>">
    <?php endif; ?>

    <div class="mt-4 fs-5 leading-relaxed">
        <?php echo nl2br(htmlspecialchars($article['content'])); ?>
    </div>
</div>
```

---

## Step 7: Register Routes

Open your framework routing file (`routes/web.php`) and add the main `/admin` route along with all action endpoints:

📁 **File Location:** `routes/web.php`

```php
use App\Core\Router;

// Public Routes
Router::get('/', 'BlogController@index');
Router::get('/article/{slug}', 'BlogController@show');

// Admin Dashboard Overview Route
Router::get('/admin', 'AdminDashboardController@index');

// Admin Category Routes
Router::get('/admin/categories', 'AdminCategoryController@index');
Router::get('/admin/categories/create', 'AdminCategoryController@create');
Router::post('/admin/categories/store', 'AdminCategoryController@store');
Router::get('/admin/categories/edit/{id}', 'AdminCategoryController@edit');
Router::post('/admin/categories/update/{id}', 'AdminCategoryController@update');
Router::get('/admin/categories/delete/{id}', 'AdminCategoryController@delete');

// Admin Article Routes
Router::get('/admin/articles', 'AdminArticleController@index');
Router::get('/admin/articles/create', 'AdminArticleController@create');
Router::post('/admin/articles/store', 'AdminArticleController@store');
Router::get('/admin/articles/edit/{id}', 'AdminArticleController@edit');
Router::post('/admin/articles/update/{id}', 'AdminArticleController@update');
Router::get('/admin/articles/delete/{id}', 'AdminArticleController@delete');
```

---

## Testing Your Complete Dashboard Flow

1. **Include Navbar**: Simply `require` or include `app/Views/layouts/navbar.php` inside your primary layout header or at the top of your page views.
2. **Dashboard Home**: Go to **`http://localhost/admin`** to see your main dashboard summary cards and total counts.
3. **Category Management**: Click **Manage Categories** or go to `http://localhost/admin/categories` to add, edit, or remove categories.
4. **Article Management**: Click **Manage Articles** or go to `http://localhost/admin/articles` to write posts, auto-generate WebP images, edit dynamic slugs, or delete old articles.
5. **View Website**: Click **View Website** at the top right of your dashboard to jump to the public homepage and check how your articles look to real visitors.
```

### views/auth/login.php

```php
<?php
$base = '';
if (isset($GLOBALS['base'])) {
    $base = $GLOBALS['base'];
}
$nonce = '';
if (isset($GLOBALS['csp_nonce'])) {
    $nonce = $GLOBALS['csp_nonce'];
}
// Set a default page title only if the controller did not provide one
if (!isset($pageTitle)) {
    $pageTitle = 'OneBuild PHP MVC Framework';
}

// Set a default meta description only if the controller did not provide one
if (!isset($metaDescription)) {
     $metaDescription = 'OneBuild Description';
}
?>
<?php $bodyClass = 'bg-light'; ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">Sign In</h2>
                        <form method="POST" action="<?= $base ?>/login/submit" novalidate>
                            <?= \App\Core\CsrfProtection::getHiddenInput() ?>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= htmlspecialchars(isset($_POST['phone']) ? $_POST['phone'] : '') ?>" 
                                       placeholder="+628123456789" required autocomplete="tel">
                                <div class="form-text">Include country code (e.g., +628123456789)</div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       required autocomplete="current-password">
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Sign In</button>
                            </div>
                        </form>
                        <?php /*
                        <p class="text-center mt-3">
                            Don't have an account? <a href="<?= $base ?>/register">Register</a>
                        </p>
                        */ ?>
                        <p class="text-center mt-3 text-muted small">
                            Accounts are created by an administrator.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
```

### views/dashboard/_sidebar.php

```php
<?php
$base = $base ?? ($GLOBALS['base'] ?? '');
$currentPage = $currentPage ?? 'index';
?>
<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar" id="sidebarMenu">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>" href="<?= $base ?>/dashboard">
                    <i class="bi bi-speedometer2"></i> Overview
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>" href="<?= $base ?>/dashboard/profile">
                    <i class="bi bi-person"></i> Profile
                </a>
            </li>
            <li class="nav-item">
                <form method="POST" action="<?= $base ?>/logout" style="margin:0;">
                    <?= \App\Core\CsrfProtection::getHiddenInput() ?>
                    <button type="submit" class="nav-link btn btn-link" style="text-align:left; width:100%;">
                        <i class="bi bi-box-arrow-right"></i> Sign Out
                    </button>
                </form>
            </li>
        </ul>
    </div>
</nav>
```

### views/dashboard/index.php

```php
<?php
$base = '';
if (isset($GLOBALS['base'])) {
    $base = $GLOBALS['base'];
}
$nonce = '';
if (isset($GLOBALS['csp_nonce'])) {
    $nonce = $GLOBALS['csp_nonce'];
}
// Set a default page title only if the controller did not provide one
if (!isset($pageTitle)) {
    $pageTitle = 'OneBuild PHP MVC Framework';
}

// Set a default meta description only if the controller did not provide one
if (!isset($metaDescription)) {
     $metaDescription = 'OneBuild Description';
}
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>
<link rel="stylesheet" href="<?= $base ?>/assets/css/dashboard.css" nonce="<?= $nonce ?>">

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php require_once __DIR__ . '/_sidebar.php'; ?>

        <!-- Main content -->
        <section class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Welcome to your Dashboard</h5>
                            <p class="card-text">Add your dashboard content here.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
```

### views/dashboard/profile.php

```php
<?php
$base = '';
if (isset($GLOBALS['base'])) {
    $base = $GLOBALS['base'];
}
$nonce = '';
if (isset($GLOBALS['csp_nonce'])) {
    $nonce = $GLOBALS['csp_nonce'];
}
// Set a default page title only if the controller did not provide one
if (!isset($pageTitle)) {
    $pageTitle = 'OneBuild PHP MVC Framework';
}

// Set a default meta description only if the controller did not provide one
if (!isset($metaDescription)) {
     $metaDescription = 'OneBuild Description';
}
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>
<link rel="stylesheet" href="<?= $base ?>/assets/css/dashboard.css" nonce="<?= $nonce ?>">

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php require_once __DIR__ . '/_sidebar.php'; ?>

        <!-- Main content -->
        <section class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Profile</h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <!-- Avatar Card -->
                    <div class="card mb-4">
                        <div class="card-body text-center">
                            <?php if (!empty($user['avatar'])): ?>
                                <img src="<?= $base . htmlspecialchars($user['avatar']) ?>" alt="Avatar" class="rounded-circle mb-3 avatar-lg">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center mb-3 avatar-lg">
                                    <i class="bi bi-person-fill text-white fs-1"></i>
                                </div>
                            <?php endif; ?>
                            <h5><?= htmlspecialchars($user['username'] ?? $user['phone']) ?></h5>
                            <p class="text-muted small"><?= htmlspecialchars($user['phone']) ?></p>
                            <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'editor' ? 'warning' : 'primary') ?>">
                                <?= ucfirst($user['role']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Change Avatar Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Change Avatar</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?= $base ?>/dashboard/profile" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <input type="hidden" name="action" value="update_avatar">
                                <?= \App\Core\CsrfProtection::getHiddenInput() ?>
                                <div class="mb-3">
                                    <input type="file" class="form-control" name="avatar" id="avatar" accept="image/jpeg,image/png,image/gif,image/webp" required>
                                    <div class="form-text">Max 2MB. Allowed: JPG, PNG, GIF, WebP</div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Upload Avatar</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <!-- Username Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Username</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?= $base ?>/dashboard/profile" class="needs-validation" novalidate>
                                <input type="hidden" name="action" value="update_username">
                                <?= \App\Core\CsrfProtection::getHiddenInput() ?>
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text">@</span>
                                        <input type="text" class="form-control" name="username" id="username" 
                                               value="<?= htmlspecialchars($user['username'] ?? '') ?>" 
                                               placeholder="Enter username" 
                                               pattern="[a-zA-Z0-9_]{3,50}"
                                               maxlength="50">
                                    </div>
                                    <div class="form-text">3-50 characters. Letters, numbers, and underscores only.</div>
                                    <div class="invalid-feedback">Please enter a valid username (3-50 characters, letters/numbers/underscores only).</div>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Username</button>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form id="changePasswordForm" method="POST" action="<?= $base ?>/dashboard/profile" class="needs-validation" novalidate>
                                <input type="hidden" name="action" value="change_password">
                                <?= \App\Core\CsrfProtection::getHiddenInput() ?>
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" name="current_password" id="current_password" required>
                                    <div class="invalid-feedback">Please enter your current password.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="new_password" id="new_password" required minlength="8">
                                    <div class="form-text">Must be at least 8 characters.</div>
                                    <div class="invalid-feedback">Password must be at least 8 characters.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                                    <div class="invalid-feedback">Please confirm your new password.</div>
                                </div>
                                <button type="submit" class="btn btn-primary">Change Password</button>
                            </form>
                        </div>
                    </div>

                    <!-- Account Info Card -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Account Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <th scope="row" class="col-label">User ID</th>
                                        <td><?= $user['id'] ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row" class="col-label">Phone</th>
                                        <td><?= htmlspecialchars($user['phone']) ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row" class="col-label">Role</th>
                                        <td>
                                            <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'editor' ? 'warning' : 'primary') ?>">
                                                <?= ucfirst($user['role']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row" class="col-label">Member Since</th>
                                        <td><?= date('F j, Y', strtotime($user['created_at'])) ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row" class="col-label">Last Updated</th>
                                        <td><?= date('F j, Y H:i', strtotime($user['updated_at'])) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<style nonce="<?= $nonce ?>">
.avatar-lg {
    width: 120px;
    height: 120px;
}
.avatar-lg img {
    object-fit: cover;
}
.fs-1 {
    font-size: 3rem !important;
}
.col-label {
    width: 150px;
}
</style>

<script nonce="<?= $nonce ?>">
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Password match validation
    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        const newPassword = changePasswordForm.querySelector('#new_password');
        const confirmPassword = changePasswordForm.querySelector('#confirm_password');
        
        function checkPasswordMatch() {
            if (newPassword.value && confirmPassword.value && newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
        
        newPassword.addEventListener('input', checkPasswordMatch);
        confirmPassword.addEventListener('input', checkPasswordMatch);
    }

    // Avatar preview
    const avatarInput = document.getElementById('avatar');
    if (avatarInput) {
        avatarInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.querySelector('.card-body.text-center img');
                    const placeholder = document.querySelector('.card-body.text-center .rounded-circle.bg-secondary');
                    if (img) {
                        img.src = e.target.result;
                    } else if (placeholder) {
                        placeholder.innerHTML = `<img src="${e.target.result}" alt="Avatar Preview" class="rounded-circle avatar-lg">`;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>
```

### views/errors/404.php

```php
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container">
    <h1>Error 404</h1>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
```

### views/errors/500.php

```php
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container">
    <h1>Error 500</h1>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
```

### views/home/index.php

```php
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container">
    <h1>Hi. Welcome to OneBuild PHP MVC Framework</h1>

    <h2>A very basic-simple PHP Framework with Bootstrap and JQuery installed into it. This framework is aimed for user that needs a simple PHP webapps that just "works".</h2>
    
    <p>Lorem ipsum dolor sit amet, cillum laborum ad exercitation id. Nulla do occaecat elit laboris qui labore excepteur officia sunt elit reprehenderit laboris. Et adipiscing aute mollit voluptate adipiscing nostrud proident magna ex enim. Ullamco mollit proident in in enim et esse.</p>

    <p>Ullamco qui veniam nisi consequat laborum consectetur dolore adipiscing laborum in. Consequat magna voluptate sunt consequat voluptate esse fugiat. Deserunt ea ut do ea duis occaecat nulla. Qui adipiscing incididunt ea enim laboris cupidatat cupidatat esse. Laborum aliqua commodo culpa dolore deserunt nostrud aute do.</p>

    <p>Consequat aliqua ex nisi dolore eiusmod aliqua ex ut non. Do in minim culpa in dolor cupidatat ex. Dolor sint esse ex enim non esse esse. Nisi in aliqua incididunt ex dolore labore non et in nulla elit. Tempor quis mollit nostrud sed voluptate consectetur elit ad.</p>

    <p>Culpa cillum cupidatat fugiat culpa cupidatat ut dolor exercitation. Mollit aute in ea nulla non id do consectetur deserunt quis. Aliquip dolore sunt consectetur proident cupidatat duis dolor. In officia aliqua mollit irure laboris quis officia ad.</p>

    <p>Non minim qui irure ea esse non non aliquip reprehenderit do ut. Ut officia ut consequat officia ut consectetur eu sed fugiat exercitation irure incididunt. Tempor sunt enim voluptate sed cupidatat dolor labore reprehenderit mollit dolore elit. Elit laborum officia id commodo minim dolore fugiat proident adipiscing. Magna laborum quis aliqua dolore adipiscing dolor sed.</p>

    <p>In anim dolore proident est culpa velit veniam eiusmod esse occaecat. Ut ut nostrud labore non occaecat ut culpa ullamco ut. In nostrud quis ea tempor in nostrud anim id laboris deserunt officia. Cillum proident minim ex culpa anim reprehenderit ut est ea.</p>

    <p>Incididunt veniam commodo qui anim culpa mollit in in. Fugiat labore velit incididunt voluptate pariatur dolore sed excepteur veniam commodo cillum in. Et sed ut dolore anim minim do enim. Do deserunt magna labore aliquip dolor magna incididunt qui ut sunt ullamco quis.</p>

    <p>Aliquip cupidatat est tempor dolore cillum anim excepteur ut cillum. Ut irure ut cupidatat in non aliquip et magna anim pariatur esse. Qui reprehenderit in est dolore aliqua ex minim ut non culpa est. Non enim ut labore reprehenderit ut commodo reprehenderit elit aliqua. Sint pariatur cillum magna minim eu sunt quis sunt quis in occaecat. Elit mollit aliqua non est reprehenderit ut dolore do irure ut elit in.</p>

    <p>In eu id dolore nisi dolore fugiat do quis sint cillum aliquip est. Dolore ullamco sunt quis nisi duis qui consequat nisi ad ut. Est dolore sunt officia dolor minim ad nulla irure. Est eiusmod non aliquip excepteur ea dolore voluptate esse do reprehenderit elit ad. Esse elit consequat dolor aliquip elit laboris consequat ad.</p>

    <p>Veniam irure nulla pariatur culpa pariatur nisi velit aute reprehenderit. Occaecat ut dolore aliquip et ut consequat voluptate laboris proident ut eu. In nostrud aliqua ut dolore aute in eu esse fugiat officia. In laborum eu pariatur eiusmod esse ea tempor ut culpa.</p>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
```

### views/layouts/footer.php

```php
    </main>
    
    <style nonce="<?= $nonce ?>">
    .page-bottom-unhide{
        height:70px;
    }
    .site-footer {
        position: relative;
        left: 0;
        bottom: 0;
        width: 100%;
        height: auto;
        background-color: rgba(255,255,255,1);
        color: black;
        padding-top: 5px;
        padding-bottom: 5px;
    }
    </style>

    <div class="page-bottom-unhide">&nbsp;</div>    

    <footer class="site-footer">
        <hr>
        <div class="container">
            <p>&copy; <?= date('Y') ?> - Ready to build an amazing website !!!</p>
        </div>
    </footer>
    <script src="<?= $base ?>/assets/js/jquery.slim.min.js" nonce="<?= $nonce ?>"></script>
    <script src="<?= $base ?>/assets/js/bootstrap.bundle.min.js" nonce="<?= $nonce ?>"></script>
    <script src="<?= $base ?>/assets/js/summernote-bs5.min.js" nonce="<?= $nonce ?>"></script>
</body>
</html>
```

### views/layouts/header.php

```php
<?php
$base = '';
if (isset($GLOBALS['base'])) {
    $base = $GLOBALS['base'];
}
$nonce = '';
if (isset($GLOBALS['csp_nonce'])) {
    $nonce = $GLOBALS['csp_nonce'];
}
// Set a default page title only if the controller did not provide one
if (!isset($pageTitle)) {
    $pageTitle = 'OneBuild PHP MVC Framework';
}

// Set a default meta description only if the controller did not provide one
if (!isset($metaDescription)) {
     $metaDescription = 'OneBuild Description';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <link rel="icon" type="image/x-icon" href="<?= $base ?>/favicon.ico"> 

    <link rel="stylesheet" href="<?= $base ?>/assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/summernote-bs5.min.css" nonce="<?= $nonce ?>">
    
    <!-- CSP Nonce for inline scripts/styles -->
    <meta name="csp-nonce" content="<?= $nonce ?>">
    <meta name="csrf-token" content="<?= \App\Core\CsrfProtection::getToken() ?>">

</head>
<?php
if (!isset($bodyClass)) {
    $bodyClass = '';
}
?>
<body class="<?= htmlspecialchars($bodyClass) ?>">
```

### views/layouts/navbar.php

```php
<?php
$requestUri = $GLOBALS['requestUri'] ?? '';
// $requestUri is trimmed (no leading/trailing slashes): 'login', 'register', 'dashboard', 'dashboard/profile', '' (home)
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?= $base ?>">OneBuild MVC PHP</a>

        <div class="d-flex align-items-center">
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== 0): ?>
            <button class="navbar-toggler me-2" type="button" id="sidebarToggle" aria-label="Toggle sidebar">
                <span>&#x25E7;</span>
            </button>
            <?php endif; ?>

            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#navbarNav"
                    aria-controls="navbarNav"
                    aria-expanded="false"
                    aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?= ($requestUri === '' || $requestUri === '/') ? 'active' : '' ?>" href="<?= $base ?>">Home</a>
                </li>
                <?php /*
                <li class="nav-item">
                    <a class="nav-link" href="#">Products</a>
                </li>
                */ ?>
                <?php /* Commented out because registration can only be done by root/admin
                <?php if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $requestUri === 'login' ? 'active' : '' ?>" href="<?= $base ?>/login">Sign In</a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $requestUri === 'register' ? 'active' : '' ?>" href="<?= $base ?>/register">Register</a>
                    </li>
                
                <?php else: ?>
                */ ?>
                <?php if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $requestUri === 'login' ? 'active' : '' ?>" href="<?= $base ?>/login">Sign In</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= stripos($requestUri, 'dashboard') === 0 ? 'active' : '' ?>" href="<?= $base ?>/dashboard">Dashboard</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php if (!empty($_SESSION['avatar'])): ?>
                                <img src="<?= $base . htmlspecialchars($_SESSION['avatar']) ?>" alt="Avatar" class="rounded-circle me-2 avatar-sm">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-2 avatar-sm">
                                    <i class="bi bi-person-fill text-white fs-6"></i>
                                </div>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></h6></li>
                            <li><small class="dropdown-item-text text-muted"><?= htmlspecialchars($_SESSION['phone'] ?? '') ?></small></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= $base ?>/dashboard/profile"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="<?= $base ?>/logout" style="margin:0;">
                                    <?= \App\Core\CsrfProtection::getHiddenInput() ?>
                                    <button type="submit" class="dropdown-item" style="background:none; border:none; width:100%; text-align:left;">
                                        <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="mt-4"></div>

<style nonce="<?= $nonce ?>">
.avatar-sm {
    width: 32px;
    height: 32px;
}
.avatar-sm img {
    object-fit: cover;
}
.fs-6 {
    font-size: 0.875rem !important;
}
</style>

<script nonce="<?= $nonce ?>">
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebarMenu');
    const overlay = document.getElementById('sidebarOverlay');
    const base = <?= json_encode($base) ?>;
    
    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('show');
        if (overlay) overlay.classList.remove('show');
    }
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            if (sidebar) {
                sidebar.classList.toggle('show');
                if (overlay) overlay.classList.toggle('show');
            } else {
                // Redirect to dashboard if sidebar doesn't exist on this page
                window.location.href = base + '/dashboard';
            }
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth < 768) {
            if (sidebar && !sidebar.contains(e.target) && sidebarToggle && !sidebarToggle.contains(e.target)) {
                closeSidebar();
            }
        }
    });
    
    // Close sidebar on window resize to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            closeSidebar();
        }
    });
});
</script>

<?php // Open the main content area now that the navbar is done ?>
<main class="main-content">

    <?php // Render flash messages from session, then clear them ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="container mt-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
```

### .env.example

```example
# One Framework Environment Configuration
# IMPORTANT: Keep this file secure and never commit to version control
# Copy to .env and configure for your environment

# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database (configure for your shared hosting)
# Usually: DB_HOST=localhost (or provided hostname)
# DB_NAME=your_database_name
# DB_USER=your_database_user
# DB_PASS=your_database_password
DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password
DB_CHARSET=utf8mb4

# Session
SESSION_TIMEOUT=1800

# Security
CSRF_TOKEN_LIFETIME=3600
RATE_LIMIT_ENABLED=true

# File Upload
MAX_FILE_SIZE=5242880
ALLOWED_FILE_TYPES=jpg,jpeg,png,gif,webp,pdf

# Shared Hosting Specific
# Set session save path to a writable directory outside public_html
# SESSION_SAVE_PATH=/home/youruser/storage/sessions

# Email (optional - configure if using mail features)
# MAIL_HOST=smtp.yourprovider.com
# MAIL_PORT=587
# MAIL_USERNAME=your_email@domain.com
# MAIL_PASSWORD=your_email_password
# MAIL_ENCRYPTION=tls
# MAIL_FROM_ADDRESS=noreply@yourdomain.com
# MAIL_FROM_NAME="Your Site Name"
```

### .gitignore

```text
# Environment files
.env
.env.local
.env.production

# Allow the database folder and SQL files
!database/
!database/initial_database.sql

# Database backups
*.dump

# Logs
logs/*.log
*.log

# Cache files
cache/
tmp/
bin/*.md

# IDE files
.vscode/
.idea/
*.swp
*.swo

# OS files
.DS_Store
Thumbs.db

# Vendor dependencies
vendor/

# User uploads (optional - you might want to track some)
public/uploads/media/*
!public/uploads/media/.htaccess
!public/uploads/media/noimage*.webp

# Backup files
*.bak
*.backup

# Security audit files (remove after review)
public/security-audit-report.php
public/test-*.html
public/test-*.php
```

### .htaccess

```text
# Root .htaccess - Protect non-public directories
# Deny access to sensitive folders
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Block access to sensitive directories
    RewriteRule ^(app|config|storage|logs|vendor|backup)(/.*)?$ - [F,L]
    
    # Block access to hidden files and dotfiles
    RewriteRule (^\.|/\.) - [F,L]
    
    # Block access to .env, .git, composer files
    RewriteRule ^(\.env|\.git|composer\.(json|lock)) - [F,L]
</IfModule>

# Fallback for non-mod_rewrite environments
<IfModule !mod_rewrite.c>
    <FilesMatch "^\.">
        Order Allow,Deny
        Deny from all
    </FilesMatch>
    
    <FilesMatch "^(composer\.|\.env)">
        Order Allow,Deny
        Deny from all
    </FilesMatch>
</IfModule>

# Prevent directory listing
Options -Indexes
```
