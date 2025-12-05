<?php
require_once __DIR__ . '/../src/bootstrap.php';
requireAdminAuth();

require_once __DIR__ . '/../src/TelegramAccount.php';

$accountManager = new TelegramAccount();
$accounts = $accountManager->getAllAccounts();
$csrfToken = generateCSRFToken();

// Check for success messages
$alert = null;
if (isset($_GET['success'])) {
    $alert = ['type' => 'success', 'message' => 'Operation completed successfully'];
}

$pageTitle = 'Telegram Accounts';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Telegram Accounts</h1>
    <a href="/account-add" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Add Account
    </a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($accounts)): ?>
            <p class="text-muted">No accounts added yet. <a href="/account-add">Add your first account</a></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Label</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Limits</th>
                            <th>Sent Today</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accounts as $account): ?>
                        <tr>
                            <td><?= $account['id'] ?></td>
                            <td><?= e($account['label']) ?></td>
                            <td><?= e($account['phone']) ?></td>
                            <td>
                                <span class="status-badge status-<?= $account['status'] ?>">
                                    <?= e($account['status']) ?>
                                </span>
                            </td>
                            <td>
                                <small>
                                    Daily: <?= $account['daily_limit'] ?><br>
                                    Per Run: <?= $account['per_run_limit'] ?><br>
                                    Delay: <?= $account['delay_min'] ?>-<?= $account['delay_max'] ?>s
                                </small>
                            </td>
                            <td><?= $account['messages_sent_today'] ?>/<?= $account['daily_limit'] ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($account['status'] === 'pending'): ?>
                                        <a href="/account-auth?id=<?= $account['id'] ?>" class="btn btn-primary" title="Authenticate">
                                            <i class="bi bi-key"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="/account-status?id=<?= $account['id'] ?>" class="btn btn-info" title="Check Status">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </a>
                                    <a href="/account-edit?id=<?= $account['id'] ?>" class="btn btn-warning" title="Edit Limits">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="/account-delete" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="id" value="<?= (int)$account['id'] ?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this account?')" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
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

