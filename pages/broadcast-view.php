<?php
require_once __DIR__ . '/../src/bootstrap.php';
requireAdminAuth();

require_once __DIR__ . '/../src/BroadcastManager.php';

$broadcastManager = new BroadcastManager();
$jobId = (int)($_GET['id'] ?? 0);
$job = $broadcastManager->getJob($jobId);

if (!$job) {
    redirect('/broadcasts');
}

$stats = $broadcastManager->getJobStats($jobId);
$db = Database::getInstance();
$queueItems = $db->fetchAll(
    "SELECT mq.*, su.username, su.first_name, su.last_name
     FROM message_queue mq
     LEFT JOIN scraped_users su ON mq.user_id = su.user_id
     WHERE mq.job_id = ?
     ORDER BY mq.created_at DESC
     LIMIT 100",
    [$jobId]
);

$pageTitle = 'View Broadcast';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Broadcast Job: <?= e($job['name']) ?></h1>
    <a href="/broadcasts" class="btn btn-secondary">Back to Broadcasts</a>
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
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Pending</h6>
                <h3 class="text-warning"><?= $stats['pending'] ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5>Job Details</h5>
    </div>
    <div class="card-body">
        <p><strong>Template:</strong> <?= e($job['template_name'] ?? 'N/A') ?></p>
        <p><strong>Status:</strong> <span class="badge bg-<?= $job['status'] === 'completed' ? 'success' : ($job['status'] === 'running' ? 'warning' : 'secondary') ?>"><?= e($job['status']) ?></span></p>
        <p><strong>Created:</strong> <?= date('Y-m-d H:i:s', strtotime($job['created_at'])) ?></p>
        <?php if ($job['started_at']): ?>
            <p><strong>Started:</strong> <?= date('Y-m-d H:i:s', strtotime($job['started_at'])) ?></p>
        <?php endif; ?>
        <?php if ($job['completed_at']): ?>
            <p><strong>Completed:</strong> <?= date('Y-m-d H:i:s', strtotime($job['completed_at'])) ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5>Queue Items (Last 100)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Status</th>
                        <th>Attempts</th>
                        <th>Error</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($queueItems as $item): ?>
                    <tr>
                        <td><?= $item['id'] ?></td>
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
                        <td><?= date('H:i:s', strtotime($item['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

