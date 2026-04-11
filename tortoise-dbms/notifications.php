<?php
declare(strict_types=1);

$requiredPermission = 'notification.read';
require_once 'includes/auth_check.php';
require_once 'includes/security.php';
require_once 'db_connect.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    if (!hasPermission('notification.manage') && !hasPermission('notification.read')) {
        http_response_code(403);
        exit('403 Forbidden');
    }

    $action = postString('action', 20);

    try {
        if ($action === 'mark_read') {
            $notificationId = postInt('notification_id');
            if ($notificationId !== null) {
                $stmt = $pdo->prepare('
                    UPDATE user_notifications
                    SET is_read = 1, read_at = NOW()
                    WHERE notification_id = :notification_id
                      AND user_id = :user_id
                ');
                $stmt->execute([
                    ':notification_id' => $notificationId,
                    ':user_id' => (int) $_SESSION['user_id'],
                ]);
                $success_message = 'Notification marked as read.';
            }
        }

        if ($action === 'mark_all_read') {
            $stmt = $pdo->prepare('UPDATE user_notifications SET is_read = 1, read_at = NOW() WHERE user_id = :user_id AND is_read = 0');
            $stmt->execute([':user_id' => (int) $_SESSION['user_id']]);
            $success_message = 'All notifications marked as read.';
        }
    } catch (PDOException $e) {
        error_log('NotificationActionError: ' . $e->getMessage());
        $error_message = 'Unable to update notification state.';
    }
}

$notifications = [];
$pendingCount = 0;

try {
    $pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM TASK_SCHEDULE WHERE user_id = :user_id AND status IN ('Pending','Assigned','In Progress')");
    $pendingStmt->execute([':user_id' => (int) $_SESSION['user_id']]);
    $pendingCount = (int) $pendingStmt->fetchColumn();

    $stmt = $pdo->prepare('
        SELECT notification_id, type, title, message, is_read, created_at
        FROM user_notifications
        WHERE user_id = :user_id
        ORDER BY is_read ASC, created_at DESC
        LIMIT 200
    ');
    $stmt->execute([':user_id' => (int) $_SESSION['user_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('NotificationLoadError: ' . $e->getMessage());
    $error_message = 'Unable to load notifications.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Notifications - TCMS</title>
    <?php include 'includes/head_template.php'; ?>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-header" data-aos="fade-up">
        <div class="container">
            <h1>Notifications</h1>
            <p>Task updates and assignment status for your account.</p>
        </div>
    </div>

    <div class="container">
        <?php if ($error_message !== ''): ?><div class="alert alert-danger"><?php echo h($error_message); ?></div><?php endif; ?>
        <?php if ($success_message !== ''): ?><div class="alert alert-success"><?php echo h($success_message); ?></div><?php endif; ?>

        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <strong>Pending Task Count:</strong> <?php echo (int) $pendingCount; ?>
                    <?php if ($pendingCount === 0): ?>
                        <div class="text-muted">No new tasks assigned.</div>
                    <?php endif; ?>
                </div>
                <form method="POST" data-confirm="Mark all notifications as read?">
                    <?php echo csrfInput(); ?>
                    <input type="hidden" name="action" value="mark_all_read">
                    <button class="btn btn-outline-primary" type="submit">Mark All Read</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr><th>Status</th><th>Type</th><th>Title</th><th>Message</th><th>Created</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $item): ?>
                            <tr>
                                <td><?php echo ((int) $item['is_read'] === 1) ? 'Read' : 'New'; ?></td>
                                <td><?php echo h($item['type']); ?></td>
                                <td><?php echo h($item['title']); ?></td>
                                <td><?php echo h($item['message']); ?></td>
                                <td><?php echo h((string) $item['created_at']); ?></td>
                                <td>
                                    <?php if ((int) $item['is_read'] === 0): ?>
                                        <form method="POST" data-confirm="Mark this notification as read?">
                                            <?php echo csrfInput(); ?>
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="notification_id" value="<?php echo (int) $item['notification_id']; ?>">
                                            <button class="btn btn-sm btn-outline-primary" type="submit">Mark Read</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$notifications): ?>
                            <tr><td colspan="6">No notifications yet. No new tasks assigned.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include 'includes/footer_script.php'; ?>
    <script src="assets/js/confirm_actions.js"></script>
</body>
</html>
