<?php
/**
 * Main Router
 * Handles all page routing
 */

require_once __DIR__ . '/src/bootstrap.php';

$route = getRoute();

// Route mapping
$routes = [
    '' => 'dashboard.php',
    'login' => 'login.php',
    'logout' => 'logout.php',
    'dashboard' => 'dashboard.php',
    'accounts' => 'accounts.php',
    'account-add' => 'account-add.php',
    'account-auth' => 'account-auth.php',
    'account-status' => 'account-status.php',
    'account-edit' => 'account-edit.php',
    'account-delete' => 'account-delete.php',
    'templates' => 'templates.php',
    'template-add' => 'template-add.php',
    'template-edit' => 'template-edit.php',
    'template-delete' => 'template-delete.php',
    'scraper' => 'scraper.php',
    'broadcasts' => 'broadcasts.php',
    'broadcast-create' => 'broadcast-create.php',
    'broadcast-view' => 'broadcast-view.php',
    'queue' => 'queue.php',
    'logs' => 'logs.php',
];

// Default to login if not authenticated (except login page)
if ($route !== 'login' && !isAdminLoggedIn()) {
    $route = 'login';
}

// Get page file
$pageFile = $routes[$route] ?? null;

if (!$pageFile) {
    http_response_code(404);
    die("Page not found: /{$route}");
}

$pagePath = __DIR__ . '/pages/' . $pageFile;

if (!file_exists($pagePath)) {
    http_response_code(404);
    die("Page file not found: {$pageFile}");
}

// Include page
require $pagePath;

