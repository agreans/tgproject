<?php
require_once __DIR__ . '/../src/bootstrap.php';
requireAdminAuth();

require_once __DIR__ . '/../src/TelegramAccount.php';

$accountManager = new TelegramAccount();
$accountId = (int)($_GET['id'] ?? 0);
$account = $accountManager->getAccount($accountId);

if (!$account) {
    redirect('/accounts');
}

$result = $accountManager->checkStatus($accountId);
$alert = ['type' => $result['success'] ? 'success' : 'info', 'message' => $result['message'] ?? 'Status checked'];

header('Location: /accounts?status=' . urlencode($result['status'] ?? 'unknown'));
exit;

