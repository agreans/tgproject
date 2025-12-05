<?php
/**
 * Password Hash Generator
 * Run: php generate_password.php
 */

echo "Telegram Userbot - Password Hash Generator\n";
echo "==========================================\n\n";

$password = readline("Enter admin password: ");

if (empty($password)) {
    echo "Error: Password cannot be empty!\n";
    exit(1);
}

$hash = password_hash($password, PASSWORD_BCRYPT);

echo "\n";
echo "Generated Password Hash:\n";
echo "========================\n";
echo $hash . "\n\n";
echo "Copy this hash to config/config.php:\n";
echo "define('ADMIN_PASSWORD_HASH', '" . $hash . "');\n\n";

