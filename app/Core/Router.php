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
        echo '<!DOCTYPE html>
            <html>
            <head><title>404 Not Found</title></head>
            <body>
                <h1>404 - Page Not Found</h1>
                <p>The requested URL was not found on this server.</p>
            </body>
            </html>';
        exit();
    }
}