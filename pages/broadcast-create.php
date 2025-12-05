<?php
require_once __DIR__ . '/../src/bootstrap.php';
requireAdminAuth();

require_once __DIR__ . '/../src/TelegramAccount.php';
require_once __DIR__ . '/../src/BroadcastManager.php';
require_once __DIR__ . '/../src/GroupScraper.php';

$accountManager = new TelegramAccount();
$broadcastManager = new BroadcastManager();
$scraper = new GroupScraper();

$accounts = $accountManager->getAllAccounts();
$activeAccounts = array_filter($accounts, fn($a) => $a['status'] === 'active');

$db = Database::getInstance();
$templates = $db->fetchAll("SELECT * FROM message_templates ORDER BY name");
$users = $scraper->getAllUsers(10000); // Get all users

$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $templateId = (int)($_POST['template_id'] ?? 0);
    $accountIds = $_POST['account_ids'] ?? [];
    $userIds = $_POST['user_ids'] ?? [];
    
    if (empty($name) || $templateId <= 0 || empty($accountIds) || empty($userIds)) {
        $alert = ['type' => 'danger', 'message' => 'All fields are required'];
    } else {
        $accountIds = array_map('intval', $accountIds);
        $userIds = array_map('intval', $userIds);
        
        $result = $broadcastManager->createJob($name, $templateId, $accountIds, $userIds);
        if ($result['success']) {
            redirect('/broadcasts?success=1');
        } else {
            $alert = ['type' => 'danger', 'message' => $result['message']];
        }
    }
}

$pageTitle = 'Create Broadcast';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Create Broadcast Job</h1>
    <a href="/broadcasts" class="btn btn-secondary">Back to Broadcasts</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label for="name" class="form-label">Job Name *</label>
                <input type="text" class="form-control" id="name" name="name" required 
                       placeholder="e.g., Welcome Campaign">
            </div>
            
            <div class="mb-3">
                <label for="template_id" class="form-label">Message Template *</label>
                <select class="form-select" id="template_id" name="template_id" required>
                    <option value="">Select Template</option>
                    <?php foreach ($templates as $template): ?>
                        <option value="<?= $template['id'] ?>"><?= e($template['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Select Accounts *</label>
                <div class="border p-3" style="max-height: 200px; overflow-y: auto;">
                    <?php if (empty($activeAccounts)): ?>
                        <p class="text-muted">No active accounts. <a href="/accounts">Add accounts first</a></p>
                    <?php else: ?>
                        <?php foreach ($activeAccounts as $account): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="account_ids[]" 
                                       value="<?= $account['id'] ?>" id="account_<?= $account['id'] ?>">
                                <label class="form-check-label" for="account_<?= $account['id'] ?>">
                                    <?= e($account['label']) ?> (<?= e($account['phone']) ?>)
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Select Users *</label>
                <div class="border p-3" style="max-height: 300px; overflow-y: auto;">
                    <?php if (empty($users)): ?>
                        <p class="text-muted">No users scraped. <a href="/scraper">Scrape users first</a></p>
                    <?php else: ?>
                        <div class="mb-2">
                            <button type="button" class="btn btn-sm btn-secondary" onclick="selectAllUsers()">Select All</button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="deselectAllUsers()">Deselect All</button>
                            <span class="ms-2 text-muted"><?= count($users) ?> users</span>
                        </div>
                        <?php foreach ($users as $user): ?>
                            <div class="form-check">
                                <input class="form-check-input user-checkbox" type="checkbox" name="user_ids[]" 
                                       value="<?= $user['id'] ?>" id="user_<?= $user['id'] ?>">
                                <label class="form-check-label" for="user_<?= $user['id'] ?>">
                                    <?= e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?>
                                    (@<?= e($user['username'] ?: 'N/A') ?>)
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Create Broadcast Job</button>
            <a href="/broadcasts" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<script>
function selectAllUsers() {
    document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = true);
}
function deselectAllUsers() {
    document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = false);
}
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

