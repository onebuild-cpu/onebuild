<?php
/**
 * PSR-4 Compliant Autoloader
 * No Composer required - pure PHP implementation
 */
namespace App\Core;

class Autoloader
{
    private static array $prefixes = [];

    public static function register(): void
    {
        spl_autoload_register([self::class, 'loadClass']);
    }

    public static function addNamespace(string $prefix, string $baseDir): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        self::$prefixes[$prefix][] = $baseDir;
    }

    public static function loadClass(string $class): void
    {
        foreach (self::$prefixes as $prefix => $dirs) {
            if (str_starts_with($class, $prefix)) {
                $relativeClass = substr($class, strlen($prefix));
                $file = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

                foreach ($dirs as $dir) {
                    $fullPath = $dir . $file;
                    if (file_exists($fullPath)) {
                        require_once $fullPath;
                        return;
                    }
                }
            }
        }
    }
}