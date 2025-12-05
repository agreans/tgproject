<?php
require_once __DIR__ . '/../src/bootstrap.php';
requireAdminAuth();

require_once __DIR__ . '/../src/BroadcastManager.php';

$broadcastManager = new BroadcastManager();
$jobs = $broadcastManager->getAllJobs();

$pageTitle = 'Broadcasts';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Broadcast Jobs</h1>
    <a href="/broadcast-create" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Create Broadcast
    </a>
</div>

<?php if ($alert): ?>
<div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show" role="alert">
    <?= e($alert['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <?php if (empty($jobs)): ?>
            <p class="text-muted">No broadcast jobs yet. <a href="/broadcast-create">Create your first broadcast</a></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Template</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td><?= $job['id'] ?></td>
                            <td><?= e($job['name']) ?></td>
                            <td><?= e($job['template_name'] ?? 'N/A') ?></td>
                            <td>
                                <span class="badge bg-<?= $job['status'] === 'completed' ? 'success' : ($job['status'] === 'running' ? 'warning' : 'secondary') ?>">
                                    <?= e($job['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?= $job['sent_count'] ?>/<?= $job['total_users'] ?>
                                <?php if ($job['total_users'] > 0): ?>
                                    (<?= round(($job['sent_count'] / $job['total_users']) * 100, 1) ?>%)
                                <?php endif; ?>
                            </td>
                            <td><?= date('Y-m-d H:i', strtotime($job['created_at'])) ?></td>
                            <td>
                                <a href="/broadcast-view?id=<?= $job['id'] ?>" class="btn btn-sm btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

