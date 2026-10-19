<?php
/**
 * Security Configuration
 * 
 * This file contains security settings that can be adjusted based on environment.
 * For production, ensure all security features are enabled.
 */

// Detect environment
$httpHost = '';
if (isset($_SERVER['HTTP_HOST'])) {
    $httpHost = $_SERVER['HTTP_HOST'];
}
$isLocalhost = in_array($httpHost, [
    'localhost',
    '127.0.0.1',
    'onebuild.test',
    'www.onebuild.test',
    'gudangpc.test',
    'www.gudangpc.test',
    'localhost:8080',
    '127.0.0.1:8080',
]);

$isHTTPS = false;
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    $isHTTPS = true;
}
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $isHTTPS = true;
}

$config = [
    'cookie_secure' => !$isLocalhost && $isHTTPS,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'session_timeout' => 3600,
    'session_regenerate_interval' => 1800,
    'use_strict_mode' => true,
    'force_https' => !$isLocalhost,
    'hsts_enabled' => !$isLocalhost && $isHTTPS,
    'csp_enabled' => true,
    'csp_report_only' => $isLocalhost,
    'is_localhost' => $isLocalhost,
    'is_https' => $isHTTPS,
    'environment' => $isLocalhost ? 'development' : 'production'
];
return $config;