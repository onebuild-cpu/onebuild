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

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function check(string $key, string $action = 'login'): array
    {
        $config = self::$limits[$action] ?? ['max' => 10, 'window' => 60];
        $maxAttempts = $config['max'];
        $window      = $config['window'];

        $bucket = $this->getIdentifier($key);
        $row = $this->fetchRow($bucket, $action);

        if ($row === null) {
            return ['allowed' => true, 'remaining' => $maxAttempts, 'retry_after' => null];
        }

        $now = time();
        $windowStart = (int) $row['window_start'];
        if ($now - $windowStart >= $window) {
            return ['allowed' => true, 'remaining' => $maxAttempts, 'retry_after' => null];
        }

        $used      = (int) $row['attempt_count'];
        $remaining = max(0, $maxAttempts - $used);
        $allowed   = $used < $maxAttempts;

        $retryAfter = null;
        if (!$allowed) {
            $retryAfter = $window - ($now - $windowStart);
            if ($retryAfter < 0) $retryAfter = 0;
        }

        return ['allowed' => $allowed, 'remaining' => $remaining, 'retry_after' => $retryAfter];
    }

    public function record(string $key, string $action = 'login'): void
    {
        $config = self::$limits[$action] ?? ['max' => 10, 'window' => 60];
        $window = $config['window'];
        $bucket = $this->getIdentifier($key);
        $row = $this->fetchRow($bucket, $action);

        if ($row === null) {
            $sql = "INSERT INTO rate_limits (bucket, action, attempt_count, window_start, created_at, updated_at)
                    VALUES (:bucket, :action, 1, :window_start, NOW(), NOW())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['bucket' => $bucket, 'action' => $action, 'window_start' => time()]);
            return;
        }

        $now = time();
        $windowStart = (int) $row['window_start'];
        if ($now - $windowStart >= $window) {
            $sql = "UPDATE rate_limits SET attempt_count = 1, window_start = :window_start, updated_at = NOW()
                    WHERE bucket = :bucket AND action = :action";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['window_start' => $now, 'bucket' => $bucket, 'action' => $action]);
            return;
        }

        $sql = "UPDATE rate_limits SET attempt_count = attempt_count + 1, updated_at = NOW()
                WHERE bucket = :bucket AND action = :action";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['bucket' => $bucket, 'action' => $action]);
    }

    public function consume(string $key, string $action = 'login'): void
    {
        $result = $this->check($key, $action);

        if (!$result['allowed']) {
            $retryAfter = $result['retry_after'] ?? 60;

            if (class_exists(SecurityLogger::class)) {
                $logger = new SecurityLogger();
                $logger->logSuspicious('rate_limit_exceeded', "Rate limit exceeded for {$action}", [
                    'action'      => $action,
                    'identifier'  => $this->getIdentifier($key),
                    'retry_after' => $retryAfter,
                ]);
            }

            throw new RateLimitException($action, (int) $retryAfter);
        }

        $this->record($key, $action);
    }

    public function clear(string $key, string $action = 'login'): void
    {
        $bucket = $this->getIdentifier($key);
        $sql = "DELETE FROM rate_limits WHERE bucket = :bucket AND action = :action";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['bucket' => $bucket, 'action' => $action]);
    }

    public static function formatRetryAfter(int $seconds): string
    {
        if ($seconds < 60) return $seconds . ' seconds';
        if ($seconds < 3600) return ((int) ceil($seconds / 60)) . ' minutes';
        return ((int) ceil($seconds / 3600)) . ' hours';
    }

    private function fetchRow(string $bucket, string $action): ?array
    {
        $sql = "SELECT attempt_count, window_start FROM rate_limits WHERE bucket = :bucket AND action = :action LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['bucket' => $bucket, 'action' => $action]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * SECURE BY DEFAULT: 
     * 1. If a specific target key (like a phone number) is provided, it becomes the primary identifier.
     *    This prevents attackers from bypassing the limit by rotating their IP address.
     * 2. Otherwise, it falls back to the direct REMOTE_ADDR to prevent HTTP header spoofing.
     */
    private function getIdentifier(string $key): string
    {
        $genericActions = ['login', 'register', 'password_reset', 'api', 'contact'];
        
        // If the key is NOT a generic action name, treat it as a specific target (e.g., a phone number).
        // Hash it to keep the database bucket length consistent and obscure the PII.
        if (!in_array($key, $genericActions, true)) {
            return hash('sha256', 'target:' . $key);
        }

        // For generic actions, use IP + User Agent.
        // SECURE DEFAULT: Only trust REMOTE_ADDR to prevent header spoofing.
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        /* 
         * OPTIONAL: If you are strictly behind a trusted reverse proxy (like Cloudflare), 
         * you can uncomment the block below. NEVER enable this if your server is directly 
         * exposed to the internet, as attackers can easily spoof the X-Forwarded-For header.
         * 
         * if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
         *     $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
         *     $ip = trim($ips[0]); 
         * }
         */

        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return hash('sha256', 'ip:' . $ip . '|' . $ua);
    }
}