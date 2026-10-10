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
use App\Core\Validator;
use App\Core\SecurityLogger;

/**
 * AuthController manages authentication flows
 */
class AuthController extends Controller
{
    /** @var UserModel User model instance */
    private UserModel $model;

    /**
     * Constructor
     * 
     * @param PDO $pdo Database connection
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->model = new UserModel($pdo);
    }

    /**
     * Display registration form
     * 
     * @return void
     */
    public function showRegister(): void
    {
        $this->view('auth/register', ['pageTitle' => 'Register']);
    }

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
     * 
     * @return void
     */
    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/register');
            return;
        }

        // Rate limiting: 3 attempts per hour
        RateLimiter::consume('register', 'register');

        // CSRF protection
        CsrfProtection::requireValidToken();

        $phone = Validator::sanitize($_POST['phone'] ?? '', 50);
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';

        // Validate phone
        if (empty($phone)) {
            $_SESSION['error'] = 'Phone number is required.';
            $this->redirect('/register');
            return;
        }

        if (!Validator::phone($phone)) {
            $_SESSION['error'] = 'Invalid phone number format. Use E.164 format (e.g., +628123456789).';
            $this->redirect('/register');
            return;
        }

        // Validate password strength
        $pwCheck = Validator::passwordStrength($password);
        if (!$pwCheck['valid']) {
            $_SESSION['error'] = implode('; ', $pwCheck['errors']);
            $this->redirect('/register');
            return;
        }

        // Password match
        if ($password !== $confirm) {
            $_SESSION['error'] = 'Passwords do not match.';
            $this->redirect('/register');
            return;
        }

        try {
            $userId = $this->model->register($phone, $password);
            
            // Log successful registration
            if (class_exists(SecurityLogger::class)) {
                $logger = new SecurityLogger();
                $logger->logAuth('register', true, ['user_id' => $userId, 'phone' => $phone]);
            }
            
            $_SESSION['success'] = 'Registered! Please sign in.';
            CsrfProtection::invalidateToken();
            $this->redirect('/login');
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/register');
        }
    }

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

        // Rate limiting: 5 attempts per 5 minutes
        RateLimiter::consume('login', 'login');

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
            RateLimiter::clear('login', 'login');

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

    /**
     * Clear rate limit for current session
     * Used for testing or after lockout
     * 
     * @return void
     */
    public function clearRateLimit(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        foreach ($_SESSION as $key => $value) {
            if (str_starts_with($key, 'ratelimit_')) {
                unset($_SESSION[$key]);
            }
        }
        
        $_SESSION['success'] = 'Rate limit cleared. You can now register.';
        $this->redirect('/register');
    }
}