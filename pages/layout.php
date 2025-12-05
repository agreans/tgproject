<?php
/**
 * Layout Template
 */
$pageTitle = $pageTitle ?? 'Dashboard';
$currentRoute = getRoute();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - Telegram Userbot Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #2c3e50;
        }
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 12px 20px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: #34495e;
            color: #fff;
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .status-pending { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <?php if (isAdminLoggedIn()): ?>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-2 sidebar">
                <div class="p-3">
                    <h4 class="text-white mb-4">Telegram Userbot</h4>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= $currentRoute === '' || $currentRoute === 'dashboard' ? 'active' : '' ?>" href="/dashboard">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($currentRoute, 'account') !== false ? 'active' : '' ?>" href="/accounts">
                                <i class="bi bi-person-badge"></i> Accounts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($currentRoute, 'template') !== false ? 'active' : '' ?>" href="/templates">
                                <i class="bi bi-file-text"></i> Templates
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentRoute === 'scraper' ? 'active' : '' ?>" href="/scraper">
                                <i class="bi bi-people"></i> Scraper
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($currentRoute, 'broadcast') !== false ? 'active' : '' ?>" href="/broadcasts">
                                <i class="bi bi-megaphone"></i> Broadcasts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentRoute === 'queue' ? 'active' : '' ?>" href="/queue">
                                <i class="bi bi-list-ul"></i> Queue
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentRoute === 'logs' ? 'active' : '' ?>" href="/logs">
                                <i class="bi bi-journal-text"></i> Logs
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a class="nav-link" href="/logout">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            <main class="col-md-10 main-content">
                <div class="p-4">
                    <?php if (isset($alert)): ?>
                    <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show" role="alert">
                        <?= e($alert['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    <?= $content ?? '' ?>
                </div>
            </main>
        </div>
    </div>
    <?php else: ?>
        <?= $content ?? '' ?>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>

