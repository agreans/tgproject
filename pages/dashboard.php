<?php
require_once __DIR__ . '/../src/bootstrap.php';
requireAdminAuth();

require_once __DIR__ . '/../src/TelegramAccount.php';
require_once __DIR__ . '/../src/BroadcastManager.php';
require_once __DIR__ . '/../src/GroupScraper.php';

$db = Database::getInstance();

// Get statistics
$totalAccounts = $db->fetchOne("SELECT COUNT(*) as count FROM telegram_accounts")['count'] ?? 0;
$activeAccounts = $db->fetchOne("SELECT COUNT(*) as count FROM telegram_accounts WHERE status = 'active'")['count'] ?? 0;
$totalTemplates = $db->fetchOne("SELECT COUNT(*) as count FROM message_templates")['count'] ?? 0;
$totalUsers = $db->fetchOne("SELECT COUNT(*) as count FROM scraped_users")['count'] ?? 0;
$pendingJobs = $db->fetchOne("SELECT COUNT(*) as count FROM broadcast_jobs WHERE status = 'pending'")['count'] ?? 0;
$queueItems = $db->fetchOne("SELECT COUNT(*) as count FROM message_queue WHERE status = 'pending'")['count'] ?? 0;

$pageTitle = 'Dashboard';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Dashboard</h1>
    <span class="text-muted"><?= date('Y-m-d H:i:s') ?></span>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Telegram Accounts</h5>
                <h2><?= $totalAccounts ?></h2>
                <small class="text-muted"><?= $activeAccounts ?> active</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Templates</h5>
                <h2><?= $totalTemplates ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Scraped Users</h5>
                <h2><?= $totalUsers ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Queue</h5>
                <h2><?= $queueItems ?></h2>
                <small class="text-muted"><?= $pendingJobs ?> pending jobs</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Recent Activity</h5>
            </div>
            <div class="card-body">
                <?php
                $activities = $db->fetchAll(
                    "SELECT al.*, ta.label as account_label
                     FROM activity_logs al
                     LEFT JOIN telegram_accounts ta ON al.account_id = ta.id
                     ORDER BY al.created_at DESC
                     LIMIT 10"
                );
                ?>
                <?php if (empty($activities)): ?>
                    <p class="text-muted">No recent activity</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($activities as $activity): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong><?= e($activity['action']) ?></strong>
                                    <?php if ($activity['account_label']): ?>
                                        <span class="text-muted">- <?= e($activity['account_label']) ?></span>
                                    <?php endif; ?>
                                    <br>
                                    <small class="text-muted"><?= e($activity['message']) ?></small>
                                </div>
                                <small class="text-muted"><?= date('H:i', strtotime($activity['created_at'])) ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Recent Broadcast Jobs</h5>
            </div>
            <div class="card-body">
                <?php
                $jobs = $db->fetchAll(
                    "SELECT * FROM broadcast_jobs ORDER BY created_at DESC LIMIT 5"
                );
                ?>
                <?php if (empty($jobs)): ?>
                    <p class="text-muted">No broadcast jobs</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($jobs as $job): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= e($job['name']) ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?= $job['sent_count'] ?>/<?= $job['total_users'] ?> sent
                                    </small>
                                </div>
                                <span class="badge bg-<?= $job['status'] === 'completed' ? 'success' : ($job['status'] === 'running' ? 'warning' : 'secondary') ?>">
                                    <?= e($job['status']) ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

