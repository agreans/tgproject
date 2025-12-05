<?php
// Base paths
$basePath = realpath(__DIR__ . '/..');

// Paths
define('BASE_PATH', $basePath);
define('DATA_PATH', BASE_PATH . '/data');
define('LOGS_PATH', BASE_PATH . '/logs');
define('JOBS_PATH', BASE_PATH . '/jobs');
define('SESSIONS_PATH', BASE_PATH . '/account_sessions');
define('ADMIN_SESSIONS_PATH', BASE_PATH . '/sessions');

// Database configuration
define('DB_TYPE', getenv('DB_TYPE') ?: 'sqlite');
define('DB_PATH', DATA_PATH . '/database.sqlite');
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'telegram_panel');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Admin authentication
// Replace this hash using generate_password.php
define('ADMIN_PASSWORD_HASH', getenv('ADMIN_PASSWORD_HASH') ?: '$2y$10$NQ0b9aV7b.bmRaGI45JYluQWpCIdnP3gcNqDDLiKX1mQ4LOlVUUli');

// Worker settings
define('MAX_EXECUTION_TIME', 25);

// Hostinger-friendly timezone
if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}
