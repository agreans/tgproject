<?php
require_once __DIR__ . '/../src/bootstrap.php';
requireAdminAuth();

require_once __DIR__ . '/../src/TelegramAccount.php';

$accountManager = new TelegramAccount();
$alert = null;
$csrfToken = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $alert = ['type' => 'danger', 'message' => 'Invalid CSRF token'];
    } else {
        $phone = $_POST['phone'] ?? '';
        $label = $_POST['label'] ?? '';
        $apiId = (int)($_POST['api_id'] ?? 0);
        $apiHash = $_POST['api_hash'] ?? '';

        if (empty($phone) || empty($label) || empty($apiId) || empty($apiHash)) {
            $alert = ['type' => 'danger', 'message' => 'All fields are required'];
        } else {
            $result = $accountManager->addAccount($phone, $label, $apiId, $apiHash);
            if ($result['success']) {
                $alert = ['type' => 'success', 'message' => $result['message']];
                header('Location: /accounts?success=1');
                exit;
            } else {
                $alert = ['type' => 'danger', 'message' => $result['message']];
            }
        }
    }
}

$pageTitle = 'Add Account';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Add Telegram Account</h1>
    <a href="/accounts" class="btn btn-secondary">Back to Accounts</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <div class="mb-3">
                <label for="label" class="form-label">Label *</label>
                <input type="text" class="form-control" id="label" name="label" required
                       placeholder="e.g., My Personal Account">
            </div>
            
            <div class="mb-3">
                <label for="phone" class="form-label">Phone Number *</label>
                <input type="text" class="form-control" id="phone" name="phone" required 
                       placeholder="+1234567890">
                <small class="form-text text-muted">Include country code (e.g., +1 for US)</small>
            </div>
            
            <div class="mb-3">
                <label for="api_id" class="form-label">API ID *</label>
                <input type="number" class="form-control" id="api_id" name="api_id" required>
                <small class="form-text text-muted">Get from <a href="https://my.telegram.org/apps" target="_blank">my.telegram.org/apps</a></small>
            </div>
            
            <div class="mb-3">
                <label for="api_hash" class="form-label">API Hash *</label>
                <input type="text" class="form-control" id="api_hash" name="api_hash" required>
                <small class="form-text text-muted">Get from <a href="https://my.telegram.org/apps" target="_blank">my.telegram.org/apps</a></small>
            </div>
            
            <button type="submit" class="btn btn-primary">Add Account</button>
            <a href="/accounts" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

