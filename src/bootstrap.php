<?php
/**
 * Bootstrap File
 * Initializes the application
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
$configPath = __DIR__ . '/../config/config.php';
$configExample = __DIR__ . '/../config/config.example.php';

if (file_exists($configPath)) {
    require_once $configPath;
} elseif (file_exists($configExample)) {
    require_once $configExample;
} else {
    die('Configuration file missing.');
}

// Create necessary directories
$dirs = [
    SESSIONS_PATH,
    ADMIN_SESSIONS_PATH,
    JOBS_PATH,
    LOGS_PATH,
    DATA_PATH,
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Load Composer autoloader
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die("Error: Composer dependencies not installed. Run: composer install");
}
require_once $autoloadPath;

// Load helper functions
require_once __DIR__ . '/helpers.php';

// Load database
require_once __DIR__ . '/Database.php';

// Initialize database
try {
    Database::getInstance();
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

