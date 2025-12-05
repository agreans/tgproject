<?php
require_once __DIR__ . '/../src/bootstrap.php';
requireAdminAuth();

$db = Database::getInstance();
$templateId = (int)($_GET['id'] ?? 0);

if ($templateId > 0) {
    $db->execute("DELETE FROM message_templates WHERE id = ?", [$templateId]);
}

redirect('/templates');

