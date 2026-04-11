<?php
declare(strict_types=1);

$requiredAnyPermissions = ['feeding.read', 'feeding.create', 'feeding.update'];
require_once 'includes/auth_check.php';
require_once 'includes/security.php';
require 'db_connect.php';

$canCreate = hasPermission('feeding.create');
$canUpdate = hasPermission('feeding.update');
$canRead = hasPermission('feeding.read');

if (!$canRead && !$canCreate && !$canUpdate) {
    http_response_code(403);
    exit('403 Forbidden');
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $action = postString('action', 20);

    if ($action === 'create' && $canCreate) {
        $tortoiseId = postInt('tortoise_id');
        $foodType = postString('food_type', 100);
        $amount = postFloat('amount');

        if ($tortoiseId === null || $foodType === null || $amount === null || $amount <= 0) {
            $error_message = 'Invalid feeding log input.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO FEEDING_LOG (tortoise_id, user_id, food_type, amount) VALUES (:tortoise_id, :user_id, :food_type, :amount)');
                $stmt->execute([
                    ':tortoise_id' => $tortoiseId,
                    ':user_id' => (int) $_SESSION['user_id'],
                    ':food_type' => $foodType,
                    ':amount' => $amount,
                ]);
                $success_message = 'Feeding log created.';
            } catch (PDOException $e) {
                error_log('FeedingCreateError: ' . $e->getMessage());
                $error_message = 'Failed to create feeding log.';
            }
        }
    }

    if ($action === 'update' && $canUpdate) {
        $logId = postInt('log_id');
        $foodType = postString('food_type', 100);
        $amount = postFloat('amount');

        if ($logId === null || $foodType === null || $amount === null || $amount <= 0) {
            $error_message = 'Invalid feeding update input.';
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE FEEDING_LOG SET food_type = :food_type, amount = :amount WHERE log_id = :log_id');
                $stmt->execute([
                    ':food_type' => $foodType,
                    ':amount' => $amount,
                    ':log_id' => $logId,
                ]);
                $success_message = 'Feeding log updated.';
            } catch (PDOException $e) {
                error_log('FeedingUpdateError: ' . $e->getMessage());
                $error_message = 'Failed to update feeding log.';
            }
        }
    }
}

$feeding_logs = [];
$tortoise_list = [];

try {
    if ($canCreate) {
        $tortoiseStmt = $pdo->query('SELECT tortoise_id FROM TORTOISE_PROFILE ORDER BY tortoise_id ASC');
        $tortoise_list = $tortoiseStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmt = $pdo->prepare('
        SELECT
            fl.log_id,
            fl.tortoise_id,
            fl.user_id,
            fl.food_type,
            fl.amount,
            fl.timestamp,
            sd.species_name,
            u.username AS caretaker_name
        FROM FEEDING_LOG fl
        INNER JOIN TORTOISE_PROFILE tp ON fl.tortoise_id = tp.tortoise_id
        INNER JOIN SPECIES_DICT sd ON tp.species_id = sd.species_id
        LEFT JOIN USERS u ON fl.user_id = u.user_id
        ORDER BY fl.timestamp DESC
        LIMIT 100
    ');
    $stmt->execute();
    $feeding_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('FeedingLoadError: ' . $e->getMessage());
    $error_message = 'Error retrieving feeding logs.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Feeding Logs - TCMS</title>
    <?php include 'includes/head_template.php'; ?>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-header" data-aos="fade-up">
        <div class="container">
            <h1>Feeding Logs</h1>
            <p>Read, create, and update feeding records based on your permissions.</p>
        </div>
    </div>

    <div class="container">
        <?php if ($error_message !== ''): ?><div class="alert alert-danger"><?php echo h($error_message); ?></div><?php endif; ?>
        <?php if ($success_message !== ''): ?><div class="alert alert-success"><?php echo h($success_message); ?></div><?php endif; ?>

        <?php if ($canCreate): ?>
            <div class="card mb-3" data-aos="fade-up">
                <div class="card-body">
                    <h5>Add Feeding Log</h5>
                    <form method="POST" class="row g-2" data-confirm="Create this feeding log entry?">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="action" value="create">
                        <div class="col-md-3">
                            <select class="form-select" name="tortoise_id" required>
                                <option value="">Tortoise</option>
                                <?php foreach ($tortoise_list as $t): ?>
                                    <option value="<?php echo (int) $t['tortoise_id']; ?>">#<?php echo (int) $t['tortoise_id']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5"><input class="form-control" name="food_type" placeholder="Food type" required></div>
                        <div class="col-md-2"><input class="form-control" name="amount" type="number" step="0.01" placeholder="Amount" required></div>
                        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Save</button></div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="card" data-aos="fade-up">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>ID</th><th>Tortoise</th><th>Species</th><th>Food</th><th>Amount</th><th>User</th><th>Time</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($feeding_logs as $log): ?>
                        <tr>
                            <td>#<?php echo (int) $log['log_id']; ?></td>
                            <td><a href="tortoise_profile.php?id=<?php echo (int) $log['tortoise_id']; ?>">#<?php echo (int) $log['tortoise_id']; ?></a></td>
                            <td><?php echo h($log['species_name']); ?></td>
                            <td><?php echo h($log['food_type']); ?></td>
                            <td><?php echo h((string) $log['amount']); ?></td>
                            <td><?php echo h($log['caretaker_name'] ?? 'N/A'); ?></td>
                            <td><?php echo h((string) $log['timestamp']); ?></td>
                            <td>
                                <?php if ($canUpdate): ?>
                                    <form method="POST" class="d-flex gap-1" data-confirm="Update this feeding log?">
                                        <?php echo csrfInput(); ?>
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="log_id" value="<?php echo (int) $log['log_id']; ?>">
                                        <input class="form-control form-control-sm" name="food_type" value="<?php echo h($log['food_type']); ?>" required>
                                        <input class="form-control form-control-sm" type="number" step="0.01" name="amount" value="<?php echo h((string) $log['amount']); ?>" required>
                                        <button class="btn btn-sm btn-outline-primary" type="submit">Update</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include 'includes/footer_script.php'; ?>
    <script src="assets/js/confirm_actions.js"></script>
</body>
</html>
