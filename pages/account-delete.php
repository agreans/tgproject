<?php
require_once __DIR__ . '/../src/bootstrap.php';
requireAdminAuth();

require_once __DIR__ . '/../src/TelegramAccount.php';

$accountManager = new TelegramAccount();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(400);
    exit('Invalid CSRF token');
}

$accountId = (int)($_POST['id'] ?? 0);

if ($accountId > 0) {
    $accountManager->deleteAccount($accountId);
}

redirect('/accounts');

