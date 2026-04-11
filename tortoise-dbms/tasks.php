<?php
declare(strict_types=1);

$requiredAnyPermissions = ['task.read', 'task.create', 'task.update', 'task.assign', 'task.resolve'];
require_once 'includes/auth_check.php';
require_once 'includes/security.php';
require 'db_connect.php';

$canRead = hasPermission('task.read');
$canCreate = hasPermission('task.create');
$canUpdate = hasPermission('task.update');
$canAssign = hasPermission('task.assign');
$canResolve = hasPermission('task.resolve');

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $action = postString('action', 30);

    if ($action === 'assign_task' && ($canAssign || $canCreate)) {
        $assignedUserId = postInt('assigned_user_id');
        $description = postString('description', 65535);
        $dueDate = postString('due_date', 20);

        if ($assignedUserId === null || $description === null || $dueDate === null || strtotime($dueDate) === false) {
            $error_message = 'Invalid assignment input.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO TASK_SCHEDULE (user_id, assigned_by_user_id, description, due_date, status) VALUES (:user_id, :assigned_by_user_id, :description, :due_date, :status)');
                $stmt->execute([
                    ':user_id' => $assignedUserId,
                    ':assigned_by_user_id' => (int) $_SESSION['user_id'],
                    ':description' => $description,
                    ':due_date' => $dueDate,
                    ':status' => 'Assigned',
                ]);

                $taskId = (int) $pdo->lastInsertId();
                $notifyStmt = $pdo->prepare('
                    INSERT INTO user_notifications (user_id, type, title, message, ref_table, ref_id)
                    VALUES (:user_id, :type, :title, :message, :ref_table, :ref_id)
                ');
                $notifyStmt->execute([
                    ':user_id' => $assignedUserId,
                    ':type' => 'TaskAssigned',
                    ':title' => 'New task assigned',
                    ':message' => 'A new task has been assigned to you with due date ' . $dueDate . '.',
                    ':ref_table' => 'TASK_SCHEDULE',
                    ':ref_id' => $taskId,
                ]);

                $success_message = 'Task assigned successfully.';
            } catch (PDOException $e) {
                error_log('TaskAssignError: ' . $e->getMessage());
                $error_message = 'Failed to assign task.';
            }
        }
    }

    if ($action === 'update_status' && ($canUpdate || $canResolve)) {
        $taskId = postInt('task_id');
        $newStatus = postString('status', 20);
        $allowedStatuses = ['Pending', 'Assigned', 'In Progress', 'Completed', 'Verified', 'Cancelled'];

        if ($taskId === null || $newStatus === null || !in_array($newStatus, $allowedStatuses, true)) {
            $error_message = 'Invalid task update input.';
        } else {
            try {
                $taskStmt = $pdo->prepare('SELECT task_id, user_id, status FROM TASK_SCHEDULE WHERE task_id = :task_id LIMIT 1');
                $taskStmt->execute([':task_id' => $taskId]);
                $task = $taskStmt->fetch(PDO::FETCH_ASSOC);

                if (!$task) {
                    $error_message = 'Task not found.';
                } else {
                    $isOwner = ((int) $task['user_id'] === (int) $_SESSION['user_id']);
                    if (!$canAssign && !$isOwner) {
                        $error_message = 'You can only update your assigned tasks.';
                    } else {
                        $updateStmt = $pdo->prepare('UPDATE TASK_SCHEDULE SET status = :status WHERE task_id = :task_id');
                        $updateStmt->execute([
                            ':status' => $newStatus,
                            ':task_id' => $taskId,
                        ]);
                        $success_message = 'Task status updated.';
                    }
                }
            } catch (PDOException $e) {
                error_log('TaskUpdateError: ' . $e->getMessage());
                $error_message = 'Failed to update task status.';
            }
        }
    }
}

$users = [];
if ($canAssign || $canCreate) {
    try {
        $userStmt = $pdo->query('
            SELECT u.user_id, u.username, r.role_name
            FROM USERS u
            INNER JOIN ROLES r ON r.role_id = u.role_id
            ORDER BY r.role_name, u.username
        ');
        $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('TaskUsersLoadError: ' . $e->getMessage());
    }
}

$tasks = [];
try {
    if ($canAssign) {
        $stmt = $pdo->prepare('
            SELECT
                ts.task_id,
                ts.user_id,
                ts.assigned_by_user_id,
                ts.description,
                ts.due_date,
                ts.status,
                u.username AS assignee_username,
                r.role_name AS assignee_role,
                au.username AS assigner_username
            FROM TASK_SCHEDULE ts
            LEFT JOIN USERS u ON u.user_id = ts.user_id
            LEFT JOIN ROLES r ON r.role_id = u.role_id
            LEFT JOIN USERS au ON au.user_id = ts.assigned_by_user_id
            ORDER BY ts.due_date ASC, ts.task_id DESC
            LIMIT 200
        ');
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare('
            SELECT
                ts.task_id,
                ts.user_id,
                ts.assigned_by_user_id,
                ts.description,
                ts.due_date,
                ts.status,
                u.username AS assignee_username,
                r.role_name AS assignee_role,
                au.username AS assigner_username
            FROM TASK_SCHEDULE ts
            LEFT JOIN USERS u ON u.user_id = ts.user_id
            LEFT JOIN ROLES r ON r.role_id = u.role_id
            LEFT JOIN USERS au ON au.user_id = ts.assigned_by_user_id
            WHERE ts.user_id = :user_id
            ORDER BY ts.due_date ASC, ts.task_id DESC
            LIMIT 200
        ');
        $stmt->execute([':user_id' => (int) $_SESSION['user_id']]);
    }
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('TaskLoadError: ' . $e->getMessage());
    $error_message = 'Error loading tasks.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Tasks - TCMS</title>
    <?php include 'includes/head_template.php'; ?>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-header" data-aos="fade-up"><div class="container"><h1>Tasks</h1><p>Assignment, ownership, and completion workflow.</p></div></div>

    <div class="container">
        <?php if ($error_message !== ''): ?><div class="alert alert-danger"><?php echo h($error_message); ?></div><?php endif; ?>
        <?php if ($success_message !== ''): ?><div class="alert alert-success"><?php echo h($success_message); ?></div><?php endif; ?>

        <?php if ($canAssign || $canCreate): ?>
            <div class="card mb-3"><div class="card-body">
                <h5>Assign Task</h5>
                <form method="POST" class="row g-2" data-confirm="Assign this task now?">
                    <?php echo csrfInput(); ?>
                    <input type="hidden" name="action" value="assign_task">
                    <div class="col-md-5"><input class="form-control" name="description" placeholder="Task description" required></div>
                    <div class="col-md-3"><input class="form-control" name="due_date" type="date" required></div>
                    <div class="col-md-3">
                        <select class="form-select" name="assigned_user_id" required>
                            <option value="">Assignee</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo (int) $user['user_id']; ?>"><?php echo h($user['username']); ?> (<?php echo h($user['role_name']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1"><button class="btn btn-primary w-100" type="submit">Add</button></div>
                </form>
            </div></div>
        <?php endif; ?>

        <?php if ($canRead): ?>
            <div class="card"><div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>ID</th><th>Description</th><th>Due Date</th><th>Status</th><th>Assignee</th><th>Assigner</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td>#<?php echo (int) $task['task_id']; ?></td>
                            <td><?php echo h($task['description']); ?></td>
                            <td><?php echo h((string) $task['due_date']); ?></td>
                            <td><?php echo h($task['status']); ?></td>
                            <td><?php echo h($task['assignee_username'] ?? 'N/A'); ?> (<?php echo h($task['assignee_role'] ?? 'N/A'); ?>)</td>
                            <td><?php echo h($task['assigner_username'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($canUpdate || $canResolve): ?>
                                    <form method="POST" class="d-flex gap-1" data-confirm="Save task status update?">
                                        <?php echo csrfInput(); ?>
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="task_id" value="<?php echo (int) $task['task_id']; ?>">
                                        <select class="form-select form-select-sm" name="status">
                                            <?php foreach (['Pending','Assigned','In Progress','Completed','Verified','Cancelled'] as $status): ?>
                                                <option <?php echo $task['status'] === $status ? 'selected' : ''; ?>><?php echo h($status); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$tasks): ?><tr><td colspan="7">No tasks found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div></div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer_script.php'; ?>
    <script src="assets/js/confirm_actions.js"></script>
</body>
</html>
