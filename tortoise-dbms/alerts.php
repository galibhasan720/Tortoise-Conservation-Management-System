<?php
declare(strict_types=1);

$requiredAnyPermissions = ['alert.read', 'alert.update', 'alert.assign', 'alert.resolve'];
require_once 'includes/auth_check.php';
require_once 'includes/security.php';
require 'db_connect.php';

$canRead = hasPermission('alert.read');
$canUpdate = hasPermission('alert.update');
$canAssign = hasPermission('alert.assign');
$canResolve = hasPermission('alert.resolve');
$roleCode = strtoupper((string) ($_SESSION['role_code'] ?? ''));

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $action = postString('action', 30);
    $alertId = postInt('alert_id');

    if ($alertId === null) {
        $error_message = 'Invalid alert ID.';
    } else {
        try {
            if ($action === 'assign' && $canAssign) {
                $assignedUserId = postInt('assigned_to_user_id');
                if ($assignedUserId === null) {
                    $error_message = 'Assignee is required.';
                } else {
                    $stmt = $pdo->prepare('UPDATE ALERTS SET assigned_to_user_id = :assigned_to_user_id, status = :status WHERE alert_id = :alert_id');
                    $stmt->execute([
                        ':assigned_to_user_id' => $assignedUserId,
                        ':status' => 'In Progress',
                        ':alert_id' => $alertId,
                    ]);
                    $success_message = 'Alert assigned.';
                }
            }

            if ($action === 'resolve' && $canResolve) {
                $stmt = $pdo->prepare('UPDATE ALERTS SET status = :status, resolved_by_user_id = :resolved_by_user_id WHERE alert_id = :alert_id');
                $stmt->execute([
                    ':status' => 'Resolved',
                    ':resolved_by_user_id' => (int) $_SESSION['user_id'],
                    ':alert_id' => $alertId,
                ]);
                $success_message = 'Alert resolved.';
            }

            if ($action === 'update' && $canUpdate) {
                $severity = postString('severity', 20);
                $allowedSeverities = ['Info', 'Warning', 'High', 'Critical'];
                if ($severity === null || !in_array($severity, $allowedSeverities, true)) {
                    $error_message = 'Invalid severity.';
                } else {
                    $stmt = $pdo->prepare('UPDATE ALERTS SET severity = :severity WHERE alert_id = :alert_id');
                    $stmt->execute([
                        ':severity' => $severity,
                        ':alert_id' => $alertId,
                    ]);
                    $success_message = 'Alert severity updated.';
                }
            }
        } catch (PDOException $e) {
            error_log('AlertActionError: ' . $e->getMessage());
            $error_message = 'Alert action failed.';
        }
    }
}

$alerts = [];
$assignees = [];

try {
    if ($canAssign) {
        $userStmt = $pdo->query('SELECT user_id, username FROM USERS ORDER BY username ASC');
        $assignees = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $routingFilter = '';
    if ($roleCode === 'VET') {
        $routingFilter = " AND (a.trigger_reason LIKE '%sick%' OR a.trigger_reason LIKE '%injury%' OR a.trigger_reason LIKE '%health%') ";
    } elseif ($roleCode === 'ENV_TECH') {
        $routingFilter = " AND (a.trigger_reason LIKE '%oxygen%' OR a.trigger_reason LIKE '%temperature%' OR a.trigger_reason LIKE '%humidity%' OR a.trigger_reason LIKE '%weather%' OR a.trigger_reason LIKE '%environment%') ";
    }

    $stmt = $pdo->prepare('
        SELECT
            a.alert_id,
            a.enclosure_id,
            a.trigger_reason,
            a.severity,
            a.timestamp,
            a.status,
            a.resolved_by_user_id,
            a.assigned_to_user_id,
            e.location,
            e.habitat_type,
            rb.username AS resolved_by_username,
            ab.username AS assigned_to_username
        FROM ALERTS a
        INNER JOIN ENCLOSURE e ON e.enclosure_id = a.enclosure_id
        LEFT JOIN USERS rb ON rb.user_id = a.resolved_by_user_id
        LEFT JOIN USERS ab ON ab.user_id = a.assigned_to_user_id
        WHERE 1=1 ' . $routingFilter . '
        ORDER BY FIELD(a.severity, "Critical", "High", "Warning", "Info"), a.timestamp DESC
        LIMIT 200
    ');
    $stmt->execute();
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('AlertLoadError: ' . $e->getMessage());
    $error_message = 'Error loading alerts.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Alerts - TCMS</title>
    <?php include 'includes/head_template.php'; ?>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-header" data-aos="fade-up"><div class="container"><h1>Alerts</h1><p>Severity-based alert routing and response.</p></div></div>

    <div class="container">
        <?php if ($error_message !== ''): ?><div class="alert alert-danger"><?php echo h($error_message); ?></div><?php endif; ?>
        <?php if ($success_message !== ''): ?><div class="alert alert-success"><?php echo h($success_message); ?></div><?php endif; ?>

        <?php if ($canRead): ?>
            <div class="card"><div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>ID</th><th>Severity</th><th>Reason</th><th>Location</th><th>Status</th><th>Assigned</th><th>Resolved By</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($alerts as $alert): ?>
                        <tr>
                            <td>#<?php echo (int) $alert['alert_id']; ?></td>
                            <td><?php echo h((string) ($alert['severity'] ?? 'Warning')); ?></td>
                            <td><?php echo h($alert['trigger_reason']); ?></td>
                            <td><?php echo h($alert['location']); ?> (<?php echo h($alert['habitat_type']); ?>)</td>
                            <td><?php echo h($alert['status']); ?></td>
                            <td><?php echo h($alert['assigned_to_username'] ?? 'N/A'); ?></td>
                            <td><?php echo h($alert['resolved_by_username'] ?? 'N/A'); ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <?php if ($canAssign): ?>
                                        <form method="POST" class="d-flex gap-1" data-confirm="Assign this alert?">
                                            <?php echo csrfInput(); ?>
                                            <input type="hidden" name="action" value="assign">
                                            <input type="hidden" name="alert_id" value="<?php echo (int) $alert['alert_id']; ?>">
                                            <select class="form-select form-select-sm" name="assigned_to_user_id" required>
                                                <option value="">Assign</option>
                                                <?php foreach ($assignees as $u): ?>
                                                    <option value="<?php echo (int) $u['user_id']; ?>"><?php echo h($u['username']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-sm btn-outline-primary" type="submit">Set</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($canUpdate): ?>
                                        <form method="POST" class="d-flex gap-1" data-confirm="Save alert severity update?">
                                            <?php echo csrfInput(); ?>
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="alert_id" value="<?php echo (int) $alert['alert_id']; ?>">
                                            <select class="form-select form-select-sm" name="severity">
                                                <?php foreach (['Info', 'Warning', 'High', 'Critical'] as $sev): ?>
                                                    <option <?php echo (($alert['severity'] ?? 'Warning') === $sev) ? 'selected' : ''; ?>><?php echo h($sev); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-sm btn-outline-secondary" type="submit">Save</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($canResolve && $alert['status'] !== 'Resolved'): ?>
                                        <form method="POST" data-confirm="Resolve this alert?">
                                            <?php echo csrfInput(); ?>
                                            <input type="hidden" name="action" value="resolve">
                                            <input type="hidden" name="alert_id" value="<?php echo (int) $alert['alert_id']; ?>">
                                            <button class="btn btn-sm btn-success" type="submit">Resolve</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$alerts): ?><tr><td colspan="8">No alerts found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div></div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer_script.php'; ?>
    <script src="assets/js/confirm_actions.js"></script>
</body>
</html>
