<?php
// Usage: php change_pwd.php "your_new_password"

if ($argc < 2) {
    echo "Error: No password provided.\n";
    echo "Usage: php " . basename(__FILE__) . " \"new_password\"\n";
    exit(1);
}

$newPassword = $argv[1];

// Generate the Argon2id hash
$hash = password_hash($newPassword, PASSWORD_ARGON2ID);

if ($hash === false) {
    echo "Error: Failed to generate password hash.\n";
    exit(1);
}

echo "Generated Argon2id Hash:\n";
echo $hash . "\n\n";
echo "You can use this SQL query in your database:\n";
echo "UPDATE users SET password = '" . $hash . "' WHERE id = 1; // Adjust ID or email as needed\n";