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