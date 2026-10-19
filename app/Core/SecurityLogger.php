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