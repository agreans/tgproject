<?php
require_once __DIR__ . '/../src/bootstrap.php';
requireAdminAuth();

$db = Database::getInstance();
$templates = $db->fetchAll("SELECT * FROM message_templates ORDER BY created_at DESC");

// Check for success messages
$alert = null;
if (isset($_GET['success'])) {
    $alert = ['type' => 'success', 'message' => 'Operation completed successfully'];
}

$pageTitle = 'Message Templates';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Message Templates</h1>
    <a href="/template-add" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Add Template
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
        <?php if (empty($templates)): ?>
            <p class="text-muted">No templates yet. <a href="/template-add">Create your first template</a></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Content Preview</th>
                            <th>Variables</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $template): ?>
                        <tr>
                            <td><?= $template['id'] ?></td>
                            <td><?= e($template['name']) ?></td>
                            <td>
                                <small><?= e(substr($template['content'], 0, 100)) ?><?= strlen($template['content']) > 100 ? '...' : '' ?></small>
                            </td>
                            <td>
                                <small class="text-muted">
                                    {firstname}, {lastname}, {username}, {fullname}
                                </small>
                            </td>
                            <td><?= date('Y-m-d H:i', strtotime($template['created_at'])) ?></td>
                            <td>
                                <a href="/template-edit?id=<?= $template['id'] ?>" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="/template-delete?id=<?= $template['id'] ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure?')" title="Delete">
                                    <i class="bi bi-trash"></i>
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

