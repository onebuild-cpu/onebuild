<?php
/**
 * Input Validator & Sanitizer
 * Provides validation and sanitization for user inputs
 */
namespace App\Core;

class Validator {
    /**
     * Sanitize string input
     */
    public static function sanitize(string $input, int $maxLength = 255, bool $allowHtml = false): string {
        $input = trim($input);
        
        if (!$allowHtml) {
            $input = strip_tags($input);
        }
        
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Limit length
        if (strlen($input) > $maxLength) {
            $input = substr($input, 0, $maxLength);
        }
        
        return $input;
    }

    /**
     * Sanitize for HTML output (prevents XSS)
     */
    public static function escape(string $input): string {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitize for SQL (use prepared statements instead, but useful for debugging)
     */
    public static function escapeSql(string $input): string {
        // This is for logging/debugging only - use prepared statements for queries
        return addslashes($input);
    }

    /**
     * Validate email
     */
    public static function email(string $email): bool {
        $email = trim($email);
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate phone number (E.164 format)
     */
    public static function phone(string $phone): bool {
        $phone = trim($phone);
        // E.164 format: + followed by 8-15 digits
        return preg_match('/^\+\d{8,15}$/', $phone) === 1;
    }

    /**
     * Validate URL
     */
    public static function url(string $url): bool {
        $url = trim($url);
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate integer
     */
    public static function integer(mixed $value, int $min = null, int $max = null): bool {
        if (!filter_var($value, FILTER_VALIDATE_INT) && !is_int($value)) {
            return false;
        }
        $int = (int)$value;
        if ($min !== null && $int < $min) return false;
        if ($max !== null && $int > $max) return false;
        return true;
    }

    /**
     * Validate string length
     */
    public static function length(string $value, int $min = 0, int $max = 255): bool {
        $len = strlen($value);
        return $len >= $min && $len <= $max;
    }

    /**
     * Validate required field
     */
    public static function required(mixed $value): bool {
        if (is_string($value)) {
            return trim($value) !== '';
        }
        return $value !== null && $value !== '';
    }

    /**
     * Validate against allowed values
     */
    public static function inArray(mixed $value, array $allowed): bool {
        return in_array($value, $allowed, true);
    }

    /**
     * Validate alphanumeric (with optional spaces, dashes, underscores)
     */
    public static function alphanumeric(string $value, bool $allowSpaces = true, bool $allowDash = true, bool $allowUnderscore = true): bool {
        $pattern = '/^[a-zA-Z0-9';
        if ($allowSpaces) $pattern .= '\s';
        if ($allowDash) $pattern .= '-';
        if ($allowUnderscore) $pattern .= '_';
        $pattern .= ']+$/';
        return preg_match($pattern, $value) === 1;
    }

    /**
     * Validate password strength
     */
    public static function passwordStrength(string $password): array {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        if (strlen($password) > 128) {
            $errors[] = 'Password must not exceed 128 characters';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        if (!preg_match('/\d/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'score' => self::calculatePasswordScore($password),
        ];
    }

    /**
     * Calculate password entropy score (0-4)
     */
    private static function calculatePasswordScore(string $password): int {
        $score = 0;
        $length = strlen($password);
        
        // Length scoring
        if ($length >= 12) $score++;
        if ($length >= 16) $score++;
        
        // Character variety
        $variety = 0;
        if (preg_match('/[a-z]/', $password)) $variety++;
        if (preg_match('/[A-Z]/', $password)) $variety++;
        if (preg_match('/\d/', $password)) $variety++;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $variety++;
        
        $score += min($variety, 2);
        
        return min($score, 4);
    }

    /**
     * Sanitize file name
     */
    public static function sanitizeFileName(string $filename): string {
        // Remove path components
        $filename = basename($filename);
        
        // Remove special characters except alphanumeric, dot, dash, underscore
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        // Prevent multiple dots
        $filename = preg_replace('/\.+/', '.', $filename);
        
        // Limit length
        if (strlen($filename) > 255) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 255 - strlen($ext) - 1) . '.' . $ext;
        }
        
        return $filename;
    }

    /**
     * Validate file upload
     */
    public static function validateFileUpload(array $file, array $allowedMimes = [], int $maxSize = 5242880): array {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error: ' . $file['error'];
            return ['valid' => false, 'errors' => $errors];
        }
        
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed (' . ($maxSize / 1024 / 1024) . 'MB)';
        }
        
        // Check MIME type
        if (!empty($allowedMimes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime, $allowedMimes, true)) {
                $errors[] = 'File type not allowed';
            }
        }
        
        // Check extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx'];
        if (!in_array($ext, $allowedExts, true)) {
            $errors[] = 'File extension not allowed';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'mime' => $mime ?? null,
            'extension' => $ext,
        ];
    }

    /**
     * Validate array of inputs against rules
     * Rules format: ['field' => ['rule' => value, ...]]
     */
    public static function validate(array $data, array $rules): array {
        $errors = [];
        $validated = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $required = $fieldRules['required'] ?? false;
            
            if ($required && !self::required($value)) {
                $errors[$field] = ucfirst($field) . ' is required';
                continue;
            }
            
            if (!self::required($value)) {
                $validated[$field] = $value;
                continue;
            }
            
            // Type-specific validation
            if (isset($fieldRules['type'])) {
                switch ($fieldRules['type']) {
                    case 'email':
                        if (!self::email($value)) {
                            $errors[$field] = 'Invalid email format';
                        }
                        break;
                    case 'phone':
                        if (!self::phone($value)) {
                            $errors[$field] = 'Invalid phone number format (use E.164: +628123456789)';
                        }
                        break;
                    case 'url':
                        if (!self::url($value)) {
                            $errors[$field] = 'Invalid URL format';
                        }
                        break;
                    case 'int':
                        $min = $fieldRules['min'] ?? null;
                        $max = $fieldRules['max'] ?? null;
                        if (!self::integer($value, $min, $max)) {
                            $errors[$field] = 'Must be a valid integer' . 
                                ($min !== null ? " >= $min" : '') . 
                                ($max !== null ? " <= $max" : '');
                        }
                        break;
                    case 'string':
                        $min = $fieldRules['min'] ?? 0;
                        $max = $fieldRules['max'] ?? 255;
                        if (!self::length($value, $min, $max)) {
                            $errors[$field] = "Must be between $min and $max characters";
                        }
                        break;
                }
            }
            
            // Custom validation callback
            if (isset($fieldRules['callback']) && is_callable($fieldRules['callback'])) {
                $result = $fieldRules['callback']($value);
                if ($result !== true) {
                    $errors[$field] = is_string($result) ? $result : 'Validation failed';
                }
            }
            
            // Allowed values
            if (isset($fieldRules['in'])) {
                if (!self::inArray($value, $fieldRules['in'])) {
                    $errors[$field] = 'Invalid value';
                }
            }
            
            // Sanitize
            $validated[$field] = self::sanitize($value, $fieldRules['max'] ?? 255, $fieldRules['allow_html'] ?? false);
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $validated,
        ];
    }
}