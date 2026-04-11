<?php
declare(strict_types=1);

$requiredPermission = 'tortoise.delete';
require_once 'includes/auth_check.php';
require_once 'includes/security.php';
require 'db_connect.php';

$tortoiseId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$tortoiseId) {
    header('Location: index.php');
    exit;
}

$errorMessage = '';
$tortoise = null;

try {
    $stmt = $pdo->prepare('
        SELECT tp.tortoise_id, tp.gender, tp.weight, tp.status, sd.species_name, e.location
        FROM TORTOISE_PROFILE tp
        INNER JOIN SPECIES_DICT sd ON sd.species_id = tp.species_id
        LEFT JOIN ENCLOSURE e ON e.enclosure_id = tp.enclosure_id
        WHERE tp.tortoise_id = :tortoise_id
        LIMIT 1
    ');
    $stmt->execute([':tortoise_id' => $tortoiseId]);
    $tortoise = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tortoise) {
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('DeleteLoadError: ' . $e->getMessage());
    $errorMessage = 'Could not load tortoise details.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    if (!hasPermission('tortoise.delete')) {
        http_response_code(403);
        exit('403 Forbidden');
    }

    try {
        $deleteStmt = $pdo->prepare('DELETE FROM TORTOISE_PROFILE WHERE tortoise_id = :tortoise_id');
        $deleteStmt->execute([':tortoise_id' => $tortoiseId]);
        header('Location: index.php?deleted=1');
        exit;
    } catch (PDOException $e) {
        error_log('DeleteExecError: ' . $e->getMessage());
        $errorMessage = 'Unable to delete record.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Delete Tortoise - TCMS</title>
    <?php include 'includes/head_template.php'; ?>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-header" data-aos="fade-up">
        <div class="container">
            <h1>Delete Tortoise</h1>
            <p>This action permanently removes related records through foreign key cascade.</p>
        </div>
    </div>

    <div class="container">
        <div class="card" data-aos="fade-up" style="max-width: 640px; margin: 0 auto;">
            <div class="card-body">
                <?php if ($errorMessage !== ''): ?>
                    <div class="alert alert-danger"><?php echo h($errorMessage); ?></div>
                <?php endif; ?>

                <?php if ($tortoise): ?>
                    <p><strong>ID:</strong> #<?php echo h((string) $tortoise['tortoise_id']); ?></p>
                    <p><strong>Species:</strong> <?php echo h($tortoise['species_name']); ?></p>
                    <p><strong>Status:</strong> <?php echo h($tortoise['status']); ?></p>
                    <p><strong>Enclosure:</strong> <?php echo h($tortoise['location'] ?? 'Unassigned'); ?></p>

                    <form method="POST" class="mt-4" data-confirm="Delete this tortoise profile permanently?">
                        <?php echo csrfInput(); ?>
                        <a class="btn btn-outline-primary" href="tortoise_profile.php?id=<?php echo (int) $tortoise['tortoise_id']; ?>">View Profile</a>
                        <a class="btn btn-secondary" href="index.php">Cancel</a>
                        <button type="submit" class="btn btn-danger">Confirm Delete</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer_script.php'; ?>
    <script src="assets/js/confirm_actions.js"></script>
</body>
</html>
