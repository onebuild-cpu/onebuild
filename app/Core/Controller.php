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