<?php
/**
 * One Build - Security Headers & Configuration
 * Version: 1.0
 * Author: Awan
 *
 * This file sends all important HTTP headers for security & caching,
 * replacing .htaccess rules when using Nginx shared hosting.
 */

// Determine environment early
$appEnv = $_ENV['APP_ENV'] ?? 'production';
$isProduction = ($appEnv === 'production');

// Global error handler - prevent stack traces in production
if ($isProduction) {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../../../storage/logs/php_errors.log');

    set_error_handler(function ($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        error_log("PHP Error [$severity]: $message in $file:$line");
        return true;
    });

    set_exception_handler(function ($exception) {
        error_log("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine());
        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        echo '<!DOCTYPE html><html><head><title>Server Error</title>'
           . '<meta name="viewport" content="width=device-width, initial-scale=1">'
           . '<style>body{font-family:sans-serif;max-width:600px;margin:50px auto;padding:20px;text-align:center}'
           . 'h1{color:#dc3545}</style></head><body>'
           . '<h1>500 - Internal Server Error</h1>'
           . '<p>Something went wrong. Please try again later.</p>'
           . '</body></html>';
        exit();
    });

    register_shutdown_function(function () {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            error_log("Fatal Error: {$error['message']} in {$error['file']}:{$error['line']}");
        }
    });
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Session cookie hardening (before session_start)
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = session_get_cookie_params();
    $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    
    session_set_cookie_params([
        'lifetime' => 3600,
        'path' => $cookieParams['path'] ?? '/',
        'domain' => $cookieParams['domain'] ?? '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $secure ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', '3600');
    ini_set('session.sid_length', '32');
    ini_set('session.sid_bits_per_character', '6');
}

// Generate CSP nonce
if (!isset($GLOBALS['csp_nonce'])) {
    $GLOBALS['csp_nonce'] = base64_encode(random_bytes(16));
}

// Utility: check if a header already exists (case-insensitive)
if (!function_exists('header_exists')) {
    function header_exists($name) {
        $name = trim($name);
        foreach (headers_list() as $header) {
            if (stripos($header, $name . ':') === 0) {
                return true;
            }
        }
        return false;
    }
}

// Add header only if not already set
if (!function_exists('safe_header')) {
    function safe_header($string) {
        [$name] = explode(":", $string, 2);
        if (!header_exists(trim($name))) {
            header($string);
        }
    }
}

// Detect localhost
$httpHost = '';
if (isset($_SERVER['HTTP_HOST'])) {
    $httpHost = $_SERVER['HTTP_HOST'];
}
$isLocalhost = in_array($httpHost, [
    'localhost',
    '127.0.0.1',
    'aitravelr.local',
    'crm.verola.co.test',
    'onebuild.test',
    'www.onebuild.test',
    'localhost:8080',
    '127.0.0.1:8080',
]);

$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $isHttps = true;
}

// Force HTTPS redirect (only in production, not localhost, not already HTTPS)
if ($isProduction && !$isLocalhost && !$isHttps) {
    if (!headers_sent()) {
        $redirectURL = 'https://' . $httpHost . $_SERVER['REQUEST_URI'];
        header("Location: $redirectURL", true, 301);
        exit();
    }
}

// Security headers
safe_header("X-Powered-By: ");
safe_header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
safe_header("Pragma: no-cache");
safe_header("Expires: 0");
safe_header("X-Content-Type-Options: nosniff");
safe_header("X-Frame-Options: DENY");
safe_header("Referrer-Policy: strict-origin-when-cross-origin");
safe_header("X-Permitted-Cross-Domain-Policies: none");
safe_header("Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()");

// COOP only on HTTPS
if ($isHttps) {
    safe_header("Cross-Origin-Opener-Policy: same-origin");
}

// HSTS only on HTTPS
if ($isHttps) {
    safe_header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
}

// Content Security Policy (CSP)
$nonce = $GLOBALS['csp_nonce'];
$csp = "default-src 'self'; "
     . "base-uri 'self'; "
     . "object-src 'none'; "
     . "frame-ancestors 'self'; "

// Script sources - Nonce-based only
     . "script-src 'self' 'nonce-{$nonce}' "
     . "https://cdn.jsdelivr.net "
     . "https://www.googletagmanager.com "
     . "https://*.googletagmanager.com "
     . "https://googletagmanager.com "
     . "https://tagmanager.google.com "
     . "https://www.google-analytics.com "
     . "https://*.google-analytics.com "
     . "https://analytics.google.com "
     . "https://*.analytics.google.com "
     . "https://connect.facebook.net "
     . "https://www.instagram.com "
     . "https://platform.instagram.com "
     . "https://www.tiktok.com "
     . "https://s0.wp.com https://s1.wp.com https://s2.wp.com; "

// Style sources
     . "style-src 'self' 'nonce-{$nonce}' "
     . "https://cdn.jsdelivr.net "
     . "https://googletagmanager.com "
     . "https://tagmanager.google.com "
     . "https://www.googletagmanager.com "
     . "https://sf16-website-login.neutral.ttwstatic.com; "

// Image sources
     . "img-src 'self' data: blob: https: "
     . "https://www.googletagmanager.com "
     . "https://*.google-analytics.com "
     . "https://*.googletagmanager.com "
     . "https://ssl.gstatic.com "
     . "https://www.gstatic.com "
     . "https://*.g.doubleclick.net "
     . "https://*.google.com "
     . "https://www.google.com "
     . "https://p16-va.tiktokcdn.com "
     . "https://www.facebook.com "
     . "https://scontent.xx.fbcdn.net; "

// Connection sources
     . "connect-src 'self' "
     . "https://ipinfo.io "
     . "https://www.googletagmanager.com "
     . "https://*.googletagmanager.com "
     . "https://www.google-analytics.com "
     . "https://*.google-analytics.com "
     . "https://analytics.google.com "
     . "https://*.analytics.google.com "
     . "https://www.google.com "
     . "https://*.google.com "
     . "https://*.g.doubleclick.net "
     . "https://pagead2.googlesyndication.com "
     . "https://region1.google-analytics.com "
     . "https://region1.analytics.google.com "
     . "https://stats.g.doubleclick.net "
     . "https://www.instagram.com "
     . "https://www.tiktok.com "
     . "https://www.facebook.com; "

// Font sources
     . "font-src 'self' data: blob: https://fonts.gstatic.com https://cdn.jsdelivr.net; "

// Frame sources
     . "frame-src 'self' "
     . "https://www.googletagmanager.com "
     . "https://googletagmanager.com "
     . "https://tagmanager.google.com "
     . "https://www.google.com "
     . "https://www.youtube.com "
     . "https://youtube.com "
     . "https://www.facebook.com "
     . "https://connect.facebook.net "
     . "https://www.instagram.com "
     . "https://platform.instagram.com "
     . "https://www.tiktok.com "
     . "https://www.tiktokcdn.com; "

// Media sources
     . "media-src 'self' data: blob:; "

// CSP violation reporting
     . "report-uri /csp-report; "
     . "report-to csp-endpoint;";

safe_header("Content-Security-Policy: $csp");

// Report-To header for modern CSP reporting
safe_header('Report-To: {"group":"csp-endpoint","max_age":10886400,"endpoints":[{"url":"/csp-report"}]}');

// Load Security Configuration
$securityConfig = require __DIR__ . '/../../config/security.php';

// Define base URL
$protocol = $isHttps ? 'https' : 'http';
$host = $httpHost ?: 'localhost';
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$scriptDir = rtrim($scriptDir, '/\\');
$base = $protocol . '://' . $host . $scriptDir;

$baseImg = $base . '/uploads/media/';
$baseImgThumb = $base . '/uploads/media/thumbs/thumb_';

// Set as globals
$GLOBALS['base'] = $base;
$GLOBALS['baseImg'] = $baseImg;
$GLOBALS['baseImgThumb'] = $baseImgThumb;
$GLOBALS['is_localhost'] = $isLocalhost;
$GLOBALS['is_https'] = $isHttps;
$GLOBALS['is_production'] = $isProduction;
$GLOBALS['app_env'] = $appEnv;