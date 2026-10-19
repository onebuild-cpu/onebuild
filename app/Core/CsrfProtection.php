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