<?php
require_once __DIR__ . '/../src/bootstrap.php';
requireAdminAuth();

$db = Database::getInstance();

$logs = $db->fetchAll(
    "SELECT al.*, ta.label as account_label
     FROM activity_logs al
     LEFT JOIN telegram_accounts ta ON al.account_id = ta.id
     ORDER BY al.created_at DESC
     LIMIT 500"
);

$pageTitle = 'Activity Logs';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Activity Logs</h1>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Time</th>
                        <th>Account</th>
                        <th>Action</th>
                        <th>Message</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No logs</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= $log['id'] ?></td>
                            <td><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
                            <td><?= e($log['account_label'] ?? 'System') ?></td>
                            <td><?= e($log['action']) ?></td>
                            <td><?= e($log['message'] ?? '') ?></td>
                            <td>
                                <span class="badge bg-<?= $log['status'] === 'success' ? 'success' : ($log['status'] === 'error' ? 'danger' : 'secondary') ?>">
                                    <?= e($log['status'] ?? 'N/A') ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

