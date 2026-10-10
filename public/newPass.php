<?php
/**
 * newPass.php — CLI-only Argon2id password hash generator.
 *
 * Usage:
 *   php newPass.php "YourNewPassword"
 *   php newPass.php               (prompts securely, no echo)
 *
 * Only runs from the command line. Refuses to run via web server.
 */

// ---------------------------------------------------------------
// Guard: refuse to run from a web server (browser/HTTP).
// ---------------------------------------------------------------
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script can only be run from the command line.\n");
}

// ---------------------------------------------------------------
// Get the password from argv or prompt interactively.
// ---------------------------------------------------------------
$password = $argv[1] ?? null;

if ($password === null || $password === '') {
    // Prompt without echoing (works on Linux/macOS/WSL; on Windows it still hides).
    fwrite(STDOUT, "Enter new password: ");
    if (function_exists('shell_exec') && stripos(PHP_OS_FAMILY, 'Windows') === false) {
        shell_exec('stty -echo');
        $password = trim(fgets(STDIN));
        shell_exec('stty echo');
        fwrite(STDOUT, "\n");
    } else {
        // Windows fallback: we can't easily hide input in native cmd,
        // so just read normally.
        $password = trim(fgets(STDIN));
    }
}

if ($password === null || $password === '') {
    fwrite(STDERR, "Error: no password provided.\n");
    exit(1);
}

// ---------------------------------------------------------------
// Basic strength check (matches your Validator::passwordStrength rules).
// ---------------------------------------------------------------
$errors = [];
if (strlen($password) < 8)             $errors[] = 'at least 8 characters';
if (!preg_match('/[A-Z]/', $password)) $errors[] = 'one uppercase letter';
if (!preg_match('/[a-z]/', $password)) $errors[] = 'one lowercase letter';
if (!preg_match('/\d/', $password))    $errors[] = 'one number';
if (!preg_match('/[^A-Za-z0-9]/', $password)) $errors[] = 'one special character';

if ($errors) {
    fwrite(STDERR, "Password too weak. It must contain: " . implode(', ', $errors) . "\n");
    exit(1);
}

// ---------------------------------------------------------------
// Generate Argon2id hash.
// ---------------------------------------------------------------
try {
    $hash = password_hash($password, PASSWORD_ARGON2ID);
} catch (\Exception $e) {
    fwrite(STDERR, "Hashing failed: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Is Argon2id supported? Check `php -i | grep argon`\n");
    exit(1);
}

if (!password_verify($password, $hash)) {
    fwrite(STDERR, "Verification failed — something is wrong.\n");
    exit(1);
}

// ---------------------------------------------------------------
// Output.
// ---------------------------------------------------------------
echo "\n";
echo "Password hash (Argon2id):\n";
echo $hash . "\n";
echo "\n";
echo "SQL to update a user (replace the phone number):\n";
echo "UPDATE `users` SET `password` = '" . addslashes($hash) . "' WHERE `phone` = '+6281234567890';\n";
echo "\n";