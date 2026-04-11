<?php
declare(strict_types=1);

$requiredPermission = 'dashboard.read';
require_once 'includes/auth_check.php';
require_once 'includes/security.php';
require_once 'db_connect.php';

$roleCode = strtoupper((string) ($_SESSION['role_code'] ?? 'STAFF'));

function getRoleDashboardCards(string $roleCode): array
{
    return match ($roleCode) {
        'ADMIN' => [
            ['title' => 'User Administration', 'description' => 'Manage users and role assignments.', 'link' => 'admin_create_user.php', 'perm' => 'user.create'],
            ['title' => 'All Tasks', 'description' => 'Review and control all task records.', 'link' => 'tasks.php', 'perm' => 'task.read'],
            ['title' => 'All Alerts', 'description' => 'Manage all active and resolved alerts.', 'link' => 'alerts.php', 'perm' => 'alert.read'],
            ['title' => 'Settings', 'description' => 'Manage users and secure reset flows.', 'link' => 'settings.php', 'perm' => 'settings.read'],
        ],
        'SUPERVISOR' => [
            ['title' => 'Task Assignment', 'description' => 'Assign and monitor tasks for all teams.', 'link' => 'tasks.php', 'perm' => 'task.assign'],
            ['title' => 'Alert Queue', 'description' => 'Track emergencies and escalations.', 'link' => 'alerts.php', 'perm' => 'alert.read'],
            ['title' => 'Operations Read View', 'description' => 'Read-only overview of core records.', 'link' => 'create.php', 'perm' => 'tortoise.read'],
            ['title' => 'Settings', 'description' => 'Manage user profiles and secure reset links.', 'link' => 'settings.php', 'perm' => 'settings.read'],
        ],
        'VET' => [
            ['title' => 'Health Records', 'description' => 'Create and update medical records.', 'link' => 'health_records.php', 'perm' => 'health.read'],
            ['title' => 'Critical Alerts', 'description' => 'Resolve medical-risk alerts quickly.', 'link' => 'alerts.php', 'perm' => 'alert.read'],
            ['title' => 'Assigned Tasks', 'description' => 'Complete medical tasks.', 'link' => 'tasks.php', 'perm' => 'task.read'],
        ],
        'CARETAKER' => [
            ['title' => 'Feeding Logs', 'description' => 'Log and update feeding activity.', 'link' => 'feeding_logs.php', 'perm' => 'feeding.read'],
            ['title' => 'Assigned Tasks', 'description' => 'Track and update daily care tasks.', 'link' => 'tasks.php', 'perm' => 'task.read'],
            ['title' => 'Alerts', 'description' => 'Read active center alerts.', 'link' => 'alerts.php', 'perm' => 'alert.read'],
        ],
        'BREEDING_OFFICER' => [
            ['title' => 'Breeding Records', 'description' => 'Manage mating and hatch records.', 'link' => 'breeding_records.php', 'perm' => 'breeding.read'],
            ['title' => 'Assigned Tasks', 'description' => 'Complete breeding workflow tasks.', 'link' => 'tasks.php', 'perm' => 'task.read'],
            ['title' => 'Alerts', 'description' => 'Read active alerts impacting breeding.', 'link' => 'alerts.php', 'perm' => 'alert.read'],
        ],
        'ENV_TECH' => [
            ['title' => 'Environmental Alerts', 'description' => 'Investigate and resolve environmental alerts.', 'link' => 'alerts.php', 'perm' => 'alert.read'],
            ['title' => 'Assigned Tasks', 'description' => 'Perform environment maintenance tasks.', 'link' => 'tasks.php', 'perm' => 'task.read'],
            ['title' => 'Telemetry Context', 'description' => 'Read environmental context from alerts.', 'link' => 'alerts.php', 'perm' => 'alert.read'],
        ],
        'COLLECTION_OFFICER' => [
            ['title' => 'Tortoise Registration', 'description' => 'Create and update tortoise intake profiles.', 'link' => 'create.php', 'perm' => 'tortoise.create'],
            ['title' => 'Assigned Tasks', 'description' => 'Complete intake and placement tasks.', 'link' => 'tasks.php', 'perm' => 'task.read'],
            ['title' => 'Alerts', 'description' => 'Read alerts affecting intake operations.', 'link' => 'alerts.php', 'perm' => 'alert.read'],
        ],
        default => [
            ['title' => 'My Tasks', 'description' => 'View and update your assigned tasks.', 'link' => 'tasks.php', 'perm' => 'task.read'],
            ['title' => 'Feeding Logs', 'description' => 'Allowed daily feeding functions.', 'link' => 'feeding_logs.php', 'perm' => 'feeding.read'],
            ['title' => 'Alerts', 'description' => 'Read active alerts.', 'link' => 'alerts.php', 'perm' => 'alert.read'],
        ],
    };
}

$cards = getRoleDashboardCards($roleCode);

$dynamicCards = [
    ['title' => 'My Profile', 'description' => 'View and update your own profile and username.', 'link' => 'profile.php', 'perm' => 'user.profile.read'],
    ['title' => 'Notifications', 'description' => 'Read assigned task notifications and updates.', 'link' => 'notifications.php', 'perm' => 'notification.read'],
    ['title' => 'Change Password', 'description' => 'Update your password securely.', 'link' => 'change_password.php', 'perm' => 'user.password.change.own'],
];

$cards = array_merge($cards, $dynamicCards);

$visibleCards = array_values(array_filter($cards, static function (array $card): bool {
    return !isset($card['perm']) || hasPermission((string) $card['perm']);
}));

$pendingTaskCount = 0;
$unreadNotificationCount = 0;

try {
    $taskStmt = $pdo->prepare("SELECT COUNT(*) FROM TASK_SCHEDULE WHERE user_id = :user_id AND status IN ('Pending', 'Assigned', 'In Progress')");
    $taskStmt->execute([':user_id' => (int) $_SESSION['user_id']]);
    $pendingTaskCount = (int) $taskStmt->fetchColumn();

    if (hasPermission('notification.read')) {
        $notifStmt = $pdo->prepare('SELECT COUNT(*) FROM user_notifications WHERE user_id = :user_id AND is_read = 0');
        $notifStmt->execute([':user_id' => (int) $_SESSION['user_id']]);
        $unreadNotificationCount = (int) $notifStmt->fetchColumn();
    }
} catch (PDOException $e) {
    error_log('DashboardWidgetError: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TCMS</title>
    <?php include 'includes/head_template.php'; ?>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-header" data-aos="fade-up">
        <div class="container">
            <h1><?php echo h((string) ($_SESSION['role_name'] ?? 'User')); ?> Dashboard</h1>
            <p>Role-scoped workspace for the Tortoise Conservation Center.</p>
        </div>
    </div>

    <div class="container">
        <div class="row mb-3">
            <div class="col-md-6" data-aos="fade-up">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Pending Tasks</h5>
                        <?php if ($pendingTaskCount > 0): ?>
                            <p class="card-text">You have <?php echo (int) $pendingTaskCount; ?> pending tasks.</p>
                        <?php else: ?>
                            <p class="card-text">No new tasks assigned.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6" data-aos="fade-up">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Unread Notifications</h5>
                        <p class="card-text"><?php echo (int) $unreadNotificationCount; ?> unread notification(s).</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <?php foreach ($visibleCards as $card): ?>
                <div class="col-md-6 col-lg-4 mb-3" data-aos="fade-up">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo h($card['title']); ?></h5>
                            <p class="card-text text-muted"><?php echo h($card['description']); ?></p>
                            <a href="<?php echo h($card['link']); ?>" class="btn btn-primary btn-sm">Open</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include 'includes/footer_script.php'; ?>
</body>
</html>