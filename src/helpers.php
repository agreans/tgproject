<?php
/**
 * Helper Functions
 */

use danog\MadelineProto\Logger as MadelineLogger;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Settings\Logger;

/**
 * Get session path for Telegram account
 * Must be an absolute path to a single .madeline file
 */
function getSessionPath(string $phone): string {
    $safePhone = preg_replace('/[^0-9]/', '', $phone);
    $base = SESSIONS_PATH;

    if (!is_dir($base)) {
        mkdir($base, 0755, true);
    }

    return rtrim($base, '/') . '/account_' . $safePhone . '.madeline';
}

/**
 * Build MadelineProto settings for a specific account
 */
function buildMadelineSettings(int $apiId, string $apiHash): Settings {
    $settings = new Settings();
    $settings->setAppInfo((new AppInfo())
        ->setApiId($apiId)
        ->setApiHash($apiHash));

    $logger = (new Logger())
        ->setLevel(MadelineLogger::NOTICE)
        ->setMaxSize(1 * 1024 * 1024);

    $settings->setLogger($logger);
    $settings->setRetryPhone(true);
    $settings->setTemplatesPath(LOGS_PATH . '/templates');

    return $settings;
}

/**
 * Check if user is logged in as admin
 */
function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Require admin authentication
 */
function requireAdminAuth(): void {
    if (!isAdminLoggedIn()) {
        header('Location: /login');
        exit;
    }
}

/**
 * Sanitize output
 */
function e(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function generateCSRFToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Log error
 */
function logError(string $message, array $context = []): void {
    $logFile = LOGS_PATH . '/error_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
    $logMessage = "[{$timestamp}] {$message}{$contextStr}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Log info
 */
function logInfo(string $message, array $context = []): void {
    $logFile = LOGS_PATH . '/info_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
    $logMessage = "[{$timestamp}] {$message}{$contextStr}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Get route from request
 */
function getRoute(): string {
    $route = $_GET['route'] ?? '';
    return trim($route, '/');
}

/**
 * Redirect
 */
function redirect(string $url): void {
    header("Location: {$url}");
    exit;
}

/**
 * JSON response
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Format phone number
 */
function formatPhone(string $phone): string {
    return preg_replace('/[^0-9]/', '', $phone);
}

