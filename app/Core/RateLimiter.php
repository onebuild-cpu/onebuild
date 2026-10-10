<?php
/**
 * Rate Limiter
 * Provides rate limiting for sensitive endpoints
 */
namespace App\Core;

class RateLimiter {
    private static array $limits = [
        'login' => ['max' => 5, 'window' => 300],      // 5 attempts per 5 minutes
        'register' => ['max' => 3, 'window' => 3600],  // 3 attempts per hour
        'password_reset' => ['max' => 3, 'window' => 3600],
        'api' => ['max' => 60, 'window' => 60],        // 60 requests per minute
    ];

    /**
     * Check if request is within rate limit
     * @return array ['allowed' => bool, 'remaining' => int, 'retry_after' => int|null]
     */
    public static function check(string $key, string $action = 'login'): array {
        $config = self::$limits[$action] ?? ['max' => 10, 'window' => 60];
        $maxAttempts = $config['max'];
        $window = $config['window'];
        
        $identifier = self::getIdentifier($key);
        $storageKey = "ratelimit_{$action}_{$identifier}";
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $now = time();
        $attempts = $_SESSION[$storageKey] ?? [];
        
        // Clean old attempts outside window
        $attempts = array_filter($attempts, fn($ts) => $now - $ts < $window);
        
        $remaining = max(0, $maxAttempts - count($attempts));
        $allowed = count($attempts) < $maxAttempts;
        
        $retryAfter = null;
        if (!$allowed && !empty($attempts)) {
            $oldest = min($attempts);
            $retryAfter = $window - ($now - $oldest);
        }
        
        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'retry_after' => $retryAfter,
        ];
    }

    /**
     * Record an attempt (call after check)
     */
    public static function record(string $key, string $action = 'login'): void {
        $identifier = self::getIdentifier($key);
        $storageKey = "ratelimit_{$action}_{$identifier}";
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $attempts = $_SESSION[$storageKey] ?? [];
        $attempts[] = time();
        $_SESSION[$storageKey] = $attempts;
    }

    /**
     * Check and record in one call
     * @throws Exception if rate limited
     */
    public static function consume(string $key, string $action = 'login'): void {
        $result = self::check($key, $action);
        
        if (!$result['allowed']) {
            $retryAfter = $result['retry_after'] ?? 60;
            
            // Log rate limit event
            if (class_exists(SecurityLogger::class)) {
                $logger = new SecurityLogger();
                $logger->logSuspicious('rate_limit_exceeded', "Rate limit exceeded for {$action}", [
                    'action' => $action,
                    'identifier' => self::getIdentifier($key),
                    'retry_after' => $retryAfter,
                ]);
            }
            
            http_response_code(429);
            header('Retry-After: ' . $retryAfter);
            header('Content-Type: application/json');
            die(json_encode([
                'error' => 'Too many requests',
                'message' => "Rate limit exceeded. Please try again in {$retryAfter} seconds.",
                'retry_after' => $retryAfter,
            ]));
        }
        
        self::record($key, $action);
    }

    /**
     * Clear rate limit for a key (e.g., after successful login)
     */
    public static function clear(string $key, string $action = 'login'): void {
        $identifier = self::getIdentifier($key);
        $storageKey = "ratelimit_{$action}_{$identifier}";
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION[$storageKey]);
    }

    /**
     * Get unique identifier for rate limiting (IP + User Agent hash)
     */
    private static function getIdentifier(string $key): string {
        $ip = self::getClientIp();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return hash('sha256', $ip . '|' . $ua . '|' . $key);
    }

    private static function getClientIp(): string {
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