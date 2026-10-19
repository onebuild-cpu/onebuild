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