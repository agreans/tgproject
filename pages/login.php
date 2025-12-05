<?php
require_once __DIR__ . '/../src/bootstrap.php';

// If already logged in, redirect
if (isAdminLoggedIn()) {
    redirect('/dashboard');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    
    if (password_verify($password, ADMIN_PASSWORD_HASH)) {
        $_SESSION['admin_logged_in'] = true;
        redirect('/dashboard');
    } else {
        $error = 'Invalid password';
    }
}

$pageTitle = 'Login';
ob_start();
?>
<div class="container">
    <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Telegram Userbot Panel</h2>
                    <h5 class="text-center text-muted mb-4">Admin Login</h5>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

