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
$step = $_GET['step'] ?? 'send';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $alert = ['type' => 'danger', 'message' => 'Invalid CSRF token'];
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'send_code') {
            $result = $accountManager->sendCode($accountId);
            if ($result['success']) {
                if (isset($result['already_logged_in']) && $result['already_logged_in']) {
                    $alert = ['type' => 'success', 'message' => $result['message']];
                    header('Location: /accounts?success=1');
                    exit;
                } else {
                    $alert = ['type' => 'success', 'message' => $result['message']];
                    $step = 'verify';
                }
            } else {
                $alert = ['type' => 'danger', 'message' => $result['message']];
            }
        } elseif ($action === 'verify_code') {
            $code = $_POST['code'] ?? '';
            if (empty($code)) {
                $alert = ['type' => 'danger', 'message' => 'Code is required'];
            } else {
                $result = $accountManager->completeLogin($accountId, $code);
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
}

$pageTitle = 'Authenticate Account';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Authenticate Account</h1>
    <a href="/accounts" class="btn btn-secondary">Back to Accounts</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <strong>Label:</strong> <?= e($account['label']) ?><br>
            <strong>Phone:</strong> <?= e($account['phone']) ?>
        </div>
        
        <?php if ($step === 'send'): ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="send_code">
                <p>Click the button below to send a verification code to your Telegram account.</p>
                <button type="submit" class="btn btn-primary">Send Verification Code</button>
                <a href="/accounts" class="btn btn-secondary">Cancel</a>
            </form>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="verify_code">
                <div class="mb-3">
                    <label for="code" class="form-label">Verification Code</label>
                    <input type="text" class="form-control" id="code" name="code" required 
                           placeholder="Enter code from Telegram" autofocus>
                    <small class="form-text text-muted">Check your Telegram app for the verification code</small>
                </div>
                <button type="submit" class="btn btn-primary">Verify Code</button>
                <a href="/account-auth?id=<?= $accountId ?>&step=send" class="btn btn-secondary">Resend Code</a>
                <a href="/accounts" class="btn btn-secondary">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

