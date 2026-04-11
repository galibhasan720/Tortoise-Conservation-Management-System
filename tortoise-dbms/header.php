<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/permission.php';
require_once __DIR__ . '/includes/security.php';

$currentFile = basename($_SERVER['PHP_SELF'] ?? '');

$menuItems = [
    ['label' => 'Dashboard', 'href' => 'index.php', 'perm' => 'dashboard.read'],
    ['label' => 'My Profile', 'href' => 'profile.php', 'perm' => 'user.profile.read'],
    ['label' => 'Notifications', 'href' => 'notifications.php', 'perm' => 'notification.read'],
    ['label' => 'Register Tortoise', 'href' => 'create.php', 'perm' => 'tortoise.create'],
    ['label' => 'Feeding Logs', 'href' => 'feeding_logs.php', 'perm' => 'feeding.read'],
    ['label' => 'Health Records', 'href' => 'health_records.php', 'perm' => 'health.read'],
    ['label' => 'Breeding Records', 'href' => 'breeding_records.php', 'perm' => 'breeding.read'],
    ['label' => 'Tasks', 'href' => 'tasks.php', 'perm' => 'task.read'],
    ['label' => 'Alerts', 'href' => 'alerts.php', 'perm' => 'alert.read'],
    ['label' => 'Settings', 'href' => 'settings.php', 'perm' => 'settings.read'],
    ['label' => 'Create User', 'href' => 'admin_create_user.php', 'perm' => 'user.create'],
];
?>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">TCMS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <?php foreach ($menuItems as $item): ?>
                    <?php if (hasPermission($item['perm'])): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentFile === $item['href'] ? 'active' : '' ?>" href="<?= h($item['href']) ?>">
                                <?= h($item['label']) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
                <li class="nav-item ms-lg-2">
                    <form method="POST" action="logout.php" class="d-inline">
                        <?= csrfInput(); ?>
                        <button type="submit" class="btn btn-sm btn-outline-light">Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
