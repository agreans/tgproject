<?php
require_once __DIR__ . '/../src/bootstrap.php';
requireAdminAuth();

require_once __DIR__ . '/../src/TelegramAccount.php';
require_once __DIR__ . '/../src/GroupScraper.php';

$accountManager = new TelegramAccount();
$scraper = new GroupScraper();

$accounts = $accountManager->getAllAccounts();
$activeAccounts = array_filter($accounts, fn($a) => $a['status'] === 'active');

$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'join') {
        $accountId = (int)($_POST['account_id'] ?? 0);
        $username = $_POST['username'] ?? '';
        
        if (empty($username) || $accountId <= 0) {
            $alert = ['type' => 'danger', 'message' => 'Account and username are required'];
        } else {
            $result = $scraper->joinGroup($accountId, $username);
            $alert = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message']];
        }
    } elseif ($action === 'scrape') {
        $accountId = (int)($_POST['account_id'] ?? 0);
        $username = $_POST['username'] ?? '';
        $sourceGroup = $_POST['source_group'] ?? '';
        
        if (empty($username) || $accountId <= 0) {
            $alert = ['type' => 'danger', 'message' => 'Account and username are required'];
        } else {
            $result = $scraper->scrapeMembers($accountId, $username, $sourceGroup);
            $alert = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] . ($result['success'] ? ' - ' . $result['count'] . ' users scraped' : '')];
        }
    }
}

$userCount = $scraper->getUserCount();
$recentUsers = $scraper->getAllUsers(10);

$pageTitle = 'Group Scraper';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Group Scraper</h1>
    <div>
        <span class="badge bg-info">Total Users: <?= $userCount ?></span>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Join Group</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="join">
                    <div class="mb-3">
                        <label for="account_id_join" class="form-label">Account</label>
                        <select class="form-select" id="account_id_join" name="account_id" required>
                            <option value="">Select Account</option>
                            <?php foreach ($activeAccounts as $account): ?>
                                <option value="<?= $account['id'] ?>"><?= e($account['label']) ?> (<?= e($account['phone']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="username_join" class="form-label">Group Username</label>
                        <input type="text" class="form-control" id="username_join" name="username" required 
                               placeholder="@groupname">
                    </div>
                    <button type="submit" class="btn btn-primary">Join Group</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Scrape Members</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="scrape">
                    <div class="mb-3">
                        <label for="account_id_scrape" class="form-label">Account</label>
                        <select class="form-select" id="account_id_scrape" name="account_id" required>
                            <option value="">Select Account</option>
                            <?php foreach ($activeAccounts as $account): ?>
                                <option value="<?= $account['id'] ?>"><?= e($account['label']) ?> (<?= e($account['phone']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="username_scrape" class="form-label">Group Username</label>
                        <input type="text" class="form-control" id="username_scrape" name="username" required 
                               placeholder="@groupname">
                    </div>
                    <div class="mb-3">
                        <label for="source_group" class="form-label">Source Label (optional)</label>
                        <input type="text" class="form-control" id="source_group" name="source_group" 
                               placeholder="Label for tracking">
                    </div>
                    <button type="submit" class="btn btn-primary">Scrape Members</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5>Recently Scraped Users</h5>
    </div>
    <div class="card-body">
        <?php if (empty($recentUsers)): ?>
            <p class="text-muted">No users scraped yet</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Source</th>
                            <th>Scraped</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $user): ?>
                        <tr>
                            <td><?= $user['user_id'] ?></td>
                            <td>@<?= e($user['username'] ?: 'N/A') ?></td>
                            <td><?= e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?></td>
                            <td><?= e($user['source_group'] ?: 'N/A') ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($user['scraped_at'])) ?></td>
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

