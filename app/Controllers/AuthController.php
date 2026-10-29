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

class AuthController extends Controller
{
    private UserModel $model;
    private RateLimiter $rateLimiter;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->model       = new UserModel($pdo);
        $this->rateLimiter = new RateLimiter($pdo);
    }

    public function showLogin(): void
    {
        $this->view('auth/login', ['pageTitle' => 'Sign In']);
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login');
            return;
        }

        // 1. Sanitize and validate input FIRST to save resources
        $phone = Validator::sanitize($_POST['phone'] ?? '', 50);
        $password = $_POST['password'] ?? '';

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

        // 2. Rate limiting: 5 attempts per 5 minutes PER PHONE NUMBER.
        // By passing $phone instead of 'login', we prevent attackers from 
        // bypassing the limit by simply rotating their IP address.
        try {
            $this->rateLimiter->consume($phone, 'login');
        } catch (RateLimitException $e) {
            $wait = RateLimiter::formatRetryAfter($e->getRetryAfter());
            $_SESSION['error'] = "Too many login attempts for this account. Please try again in {$wait}.";
            $this->redirect('/login');
            return;
        }

        // 3. CSRF protection
        CsrfProtection::requireValidToken();

        // 4. Attempt login
        $user = $this->model->login($phone, $password);
        
        if ($user) {
            // Clear rate limit on successful login so legitimate typos don't haunt the user
            $this->rateLimiter->clear($phone, 'login');

            if (class_exists(SecurityLogger::class)) {
                $logger = new SecurityLogger();
                $logger->logAuth('login', true, ['user_id' => $user['id'], 'phone' => $phone]);
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'] ?? null;
            $_SESSION['avatar'] = $user['avatar'] ?? null;
            $_SESSION['phone'] = $user['phone'] ?? null;
            
            // Regenerate session ID for security (prevents session fixation)
            session_regenerate_id(true); 
            
            // Regenerate CSRF token after privilege elevation
            CsrfProtection::invalidateToken();
            
            $this->redirect('/dashboard');
        } else {
            if (class_exists(SecurityLogger::class)) {
                $logger = new SecurityLogger();
                $logger->logAuth('login', false, ['phone' => $phone, 'reason' => 'invalid_credentials']);
            }
            
            $_SESSION['error'] = 'Invalid phone number or password.';
            $this->redirect('/login');
        }
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/dashboard');
            return;
        }
        
        \App\Core\CsrfProtection::requireValidToken();

        // 1. Unset all session variables immediately
        $_SESSION = [];
        
        // 2. Delete the session cookie securely
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"],
                $params["samesite"] ?? 'Lax' // Added for modern browser compliance
            );
        }
        
        // 3. Destroy the session data on the server
        session_destroy();
        
        // 4. Start a fresh session solely to hold the success flash message
        session_start();
        session_regenerate_id(true); // Ensure the new session gets a pristine, unpredictable ID
        
        \App\Core\CsrfProtection::invalidateToken();
        $_SESSION['success'] = 'You have been signed out.';
        
        $this->redirect('/login');
    }
}