<?php
/**
 * Simple Dotenv Loader
 * Lightweight .env file parser - no dependencies required
 */
namespace App\Core;
use RuntimeException;

class Dotenv
{
    /**
     * Load environment variables from .env file
     */
    public static function load($path = null) {
        // 1. If no path is provided, calculate it relative to THIS file (Dotenv.php)
        // __DIR__ is 'domain_folder/app/core'
        // dirname(__DIR__, 1) goes up to 'domain_folder'
        if ($path === null) {
            $path = dirname(__DIR__, 2) . '/.env';
        }

        // 2. If the file doesn't exist, check a fallback (just in case)
        if (!file_exists($path)) {
            $docRoot = '';
            if (isset($_SERVER['DOCUMENT_ROOT'])) {
                $docRoot = $_SERVER['DOCUMENT_ROOT'];
            }
            $path = $docRoot . '/../.env';
        }

        // 3. If it still doesn't exist, exit early
        if (!file_exists($path)) {
            return;
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments and empty lines
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }
            
            // Parse key=value
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove surrounding quotes if present
                if (preg_match('/^["\'](.*)["\']$/', $value, $matches)) {
                    $value = $matches[1];
                }
                
                // Only set if not already set in environment
                if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
    }
    
    /**
     * Get environment variable with fallback
     */
    public static function get(string $key, $default = null)
    {
        $value = null;
        if (isset($_ENV[$key])) {
            $value = $_ENV[$key];
        } elseif (isset($_SERVER[$key])) {
            $value = $_SERVER[$key];
        } elseif (getenv($key) !== false) {
            $value = getenv($key);
        } else {
            $value = $default;
        }
        return $value;
    }
    
    /**
     * Get required environment variable (throws if not set)
     */
    public static function required(string $key)
    {
        $value = self::get($key);
        if ($value === null) {
            throw new RuntimeException("Required environment variable '$key' is not set");
        }
        return $value;
    }
}