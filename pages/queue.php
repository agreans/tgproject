<?php
require_once __DIR__ . '/../src/bootstrap.php';
requireAdminAuth();

$db = Database::getInstance();

$status = $_GET['status'] ?? 'all';
$limit = 500;

$where = '';
$params = [];
if ($status !== 'all') {
    $where = "WHERE mq.status = ?";
    $params[] = $status;
}

$queueItems = $db->fetchAll(
    "SELECT mq.*, ta.label as account_label, su.username, su.first_name, su.last_name, bj.name as job_name
     FROM message_queue mq
     LEFT JOIN telegram_accounts ta ON mq.account_id = ta.id
     LEFT JOIN scraped_users su ON mq.user_id = su.user_id
     LEFT JOIN broadcast_jobs bj ON mq.job_id = bj.id
     {$where}
     ORDER BY mq.created_at DESC
     LIMIT ?",
    array_merge($params, [$limit])
);

$stats = [
    'total' => $db->fetchOne("SELECT COUNT(*) as count FROM message_queue")['count'] ?? 0,
    'pending' => $db->fetchOne("SELECT COUNT(*) as count FROM message_queue WHERE status = 'pending'")['count'] ?? 0,
    'sent' => $db->fetchOne("SELECT COUNT(*) as count FROM message_queue WHERE status = 'sent'")['count'] ?? 0,
    'failed' => $db->fetchOne("SELECT COUNT(*) as count FROM message_queue WHERE status = 'failed'")['count'] ?? 0,
];

$pageTitle = 'Message Queue';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Message Queue</h1>
    <div>
        <a href="/queue?status=all" class="btn btn-sm btn-<?= $status === 'all' ? 'primary' : 'outline-primary' ?>">All</a>
        <a href="/queue?status=pending" class="btn btn-sm btn-<?= $status === 'pending' ? 'primary' : 'outline-primary' ?>">Pending</a>
        <a href="/queue?status=sent" class="btn btn-sm btn-<?= $status === 'sent' ? 'primary' : 'outline-primary' ?>">Sent</a>
        <a href="/queue?status=failed" class="btn btn-sm btn-<?= $status === 'failed' ? 'primary' : 'outline-primary' ?>">Failed</a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Total</h6>
                <h3><?= $stats['total'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Pending</h6>
                <h3 class="text-warning"><?= $stats['pending'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Sent</h6>
                <h3 class="text-success"><?= $stats['sent'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Failed</h6>
                <h3 class="text-danger"><?= $stats['failed'] ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Job</th>
                        <th>Account</th>
                        <th>User</th>
                        <th>Status</th>
                        <th>Attempts</th>
                        <th>Error</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($queueItems)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No queue items</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($queueItems as $item): ?>
                        <tr>
                            <td><?= $item['id'] ?></td>
                            <td><?= e($item['job_name'] ?? 'N/A') ?></td>
                            <td><?= e($item['account_label'] ?? 'N/A') ?></td>
                            <td>
                                <?= e(trim(($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? ''))) ?>
                                (@<?= e($item['username'] ?: 'N/A') ?>)
                            </td>
                            <td>
                                <span class="badge bg-<?= $item['status'] === 'sent' ? 'success' : ($item['status'] === 'failed' ? 'danger' : 'warning') ?>">
                                    <?= e($item['status']) ?>
                                </span>
                            </td>
                            <td><?= $item['attempts'] ?></td>
                            <td><small><?= e($item['error_message'] ?: '') ?></small></td>
                            <td><?= date('Y-m-d H:i:s', strtotime($item['created_at'])) ?></td>
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

