<?php
require_once __DIR__ . '/../src/bootstrap.php';
requireAdminAuth();

$db = Database::getInstance();
$templateId = (int)($_GET['id'] ?? 0);
$template = $db->fetchOne("SELECT * FROM message_templates WHERE id = ?", [$templateId]);

if (!$template) {
    redirect('/templates');
}

$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $content = $_POST['content'] ?? '';
    
    if (empty($name) || empty($content)) {
        $alert = ['type' => 'danger', 'message' => 'Name and content are required'];
    } else {
        $success = $db->execute(
            "UPDATE message_templates SET name = ?, content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$name, $content, $templateId]
        );
        if ($success) {
            redirect('/templates?success=1');
        } else {
            $alert = ['type' => 'danger', 'message' => 'Failed to update template'];
        }
    }
    $template = $db->fetchOne("SELECT * FROM message_templates WHERE id = ?", [$templateId]); // Refresh
}

$pageTitle = 'Edit Template';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Edit Message Template</h1>
    <a href="/templates" class="btn btn-secondary">Back to Templates</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="alert alert-info">
            <strong>Available Variables:</strong><br>
            <code>{firstname}</code> - User's first name<br>
            <code>{lastname}</code> - User's last name<br>
            <code>{username}</code> - User's username<br>
            <code>{fullname}</code> - Full name (first + last)
        </div>
        
        <form method="POST">
            <div class="mb-3">
                <label for="name" class="form-label">Template Name *</label>
                <input type="text" class="form-control" id="name" name="name" required 
                       value="<?= e($template['name']) ?>">
            </div>
            
            <div class="mb-3">
                <label for="content" class="form-label">Message Content *</label>
                <textarea class="form-control" id="content" name="content" rows="10" required><?= e($template['content']) ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Update Template</button>
            <a href="/templates" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';

