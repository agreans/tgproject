<?php
require_once __DIR__ . '/../src/bootstrap.php';
requireAdminAuth();

require_once __DIR__ . '/../src/TelegramAccount.php';

$accountManager = new TelegramAccount();
$accountId = (int)($_GET['id'] ?? 0);
$account = $accountManager->getAccount($accountId);
$csrfToken = generateCSRFToken();

if (!$account) {
    redirect('/accounts');
}

$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $alert = ['type' => 'danger', 'message' => 'Invalid CSRF token'];
    } else {
        $dailyLimit = (int)($_POST['daily_limit'] ?? 100);
        $perRunLimit = (int)($_POST['per_run_limit'] ?? 10);
        $delayMin = (int)($_POST['delay_min'] ?? 2);
        $delayMax = (int)($_POST['delay_max'] ?? 5);

        if ($delayMin > $delayMax) {
            $alert = ['type' => 'danger', 'message' => 'Min delay cannot be greater than max delay'];
        } else {
            $success = $accountManager->updateLimits($accountId, $dailyLimit, $perRunLimit, $delayMin, $delayMax);
            if ($success) {
                $alert = ['type' => 'success', 'message' => 'Limits updated successfully'];
                $account = $accountManager->getAccount($accountId); // Refresh
            } else {
                $alert = ['type' => 'danger', 'message' => 'Failed to update limits'];
            }
        }
    }
}

$pageTitle = 'Edit Account Limits';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Edit Account Limits</h1>
    <a href="/accounts" class="btn btn-secondary">Back to Accounts</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <strong>Label:</strong> <?= e($account['label']) ?><br>
            <strong>Phone:</strong> <?= e($account['phone']) ?>
        </div>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="daily_limit" class="form-label">Daily Limit</label>
                    <input type="number" class="form-control" id="daily_limit" name="daily_limit" 
                           value="<?= $account['daily_limit'] ?>" required min="1">
                    <small class="form-text text-muted">Maximum messages per day</small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="per_run_limit" class="form-label">Per Run Limit</label>
                    <input type="number" class="form-control" id="per_run_limit" name="per_run_limit" 
                           value="<?= $account['per_run_limit'] ?>" required min="1">
                    <small class="form-text text-muted">Maximum messages per worker run</small>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="delay_min" class="form-label">Min Delay (seconds)</label>
                    <input type="number" class="form-control" id="delay_min" name="delay_min" 
                           value="<?= $account['delay_min'] ?>" required min="1">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="delay_max" class="form-label">Max Delay (seconds)</label>
                    <input type="number" class="form-control" id="delay_max" name="delay_max" 
                           value="<?= $account['delay_max'] ?>" required min="1">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Update Limits</button>
            <a href="/accounts" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

