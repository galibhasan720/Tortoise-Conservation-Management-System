<?php
declare(strict_types=1);

$requiredAnyPermissions = ['health.read', 'health.create', 'health.update', 'health.resolve'];
require_once 'includes/auth_check.php';
require_once 'includes/security.php';
require 'db_connect.php';

$canCreate = hasPermission('health.create');
$canUpdate = hasPermission('health.update');
$canResolve = hasPermission('health.resolve');

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();
    $action = postString('action', 20);

    if ($action === 'create' && $canCreate) {
        $tortoiseId = postInt('tortoise_id');
        $diagnosis = postString('diagnosis', 65535);
        $treatmentPlan = postString('treatment_plan', 65535, false);
        $vaccinationDate = postString('vaccination_date', 20, false);

        if ($tortoiseId === null || $diagnosis === null) {
            $error_message = 'Invalid health record input.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO HEALTH_RECORD (tortoise_id, user_id, diagnosis, treatment_plan, vaccination_date) VALUES (:tortoise_id, :user_id, :diagnosis, :treatment_plan, :vaccination_date)');
                $stmt->execute([
                    ':tortoise_id' => $tortoiseId,
                    ':user_id' => (int) $_SESSION['user_id'],
                    ':diagnosis' => $diagnosis,
                    ':treatment_plan' => $treatmentPlan !== '' ? $treatmentPlan : null,
                    ':vaccination_date' => $vaccinationDate !== '' ? $vaccinationDate : null,
                ]);
                $success_message = 'Health record created.';
            } catch (PDOException $e) {
                error_log('HealthCreateError: ' . $e->getMessage());
                $error_message = 'Failed to create health record.';
            }
        }
    }

    if ($action === 'update' && $canUpdate) {
        $recordId = postInt('record_id');
        $diagnosis = postString('diagnosis', 65535);
        $treatmentPlan = postString('treatment_plan', 65535, false);
        $vaccinationDate = postString('vaccination_date', 20, false);

        if ($recordId === null || $diagnosis === null) {
            $error_message = 'Invalid health update input.';
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE HEALTH_RECORD SET diagnosis = :diagnosis, treatment_plan = :treatment_plan, vaccination_date = :vaccination_date WHERE record_id = :record_id');
                $stmt->execute([
                    ':diagnosis' => $diagnosis,
                    ':treatment_plan' => $treatmentPlan !== '' ? $treatmentPlan : null,
                    ':vaccination_date' => $vaccinationDate !== '' ? $vaccinationDate : null,
                    ':record_id' => $recordId,
                ]);
                $success_message = 'Health record updated.';
            } catch (PDOException $e) {
                error_log('HealthUpdateError: ' . $e->getMessage());
                $error_message = 'Failed to update health record.';
            }
        }
    }

    if ($action === 'resolve' && $canResolve) {
        $tortoiseId = postInt('tortoise_id');
        if ($tortoiseId === null) {
            $error_message = 'Invalid tortoise ID for resolve.';
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE TORTOISE_PROFILE SET status = :status WHERE tortoise_id = :tortoise_id');
                $stmt->execute([':status' => 'Healthy', ':tortoise_id' => $tortoiseId]);
                $success_message = 'Tortoise status resolved to Healthy.';
            } catch (PDOException $e) {
                error_log('HealthResolveError: ' . $e->getMessage());
                $error_message = 'Failed to resolve health case.';
            }
        }
    }
}

$tortoise_list = [];
$health_records = [];

try {
    if ($canCreate) {
        $tortoiseStmt = $pdo->query('SELECT tortoise_id FROM TORTOISE_PROFILE ORDER BY tortoise_id ASC');
        $tortoise_list = $tortoiseStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmt = $pdo->prepare('
        SELECT
            hr.record_id,
            hr.tortoise_id,
            hr.user_id,
            hr.diagnosis,
            hr.treatment_plan,
            hr.vaccination_date,
            tp.status,
            sd.species_name,
            u.username AS vet_name
        FROM HEALTH_RECORD hr
        INNER JOIN TORTOISE_PROFILE tp ON tp.tortoise_id = hr.tortoise_id
        INNER JOIN SPECIES_DICT sd ON sd.species_id = tp.species_id
        LEFT JOIN USERS u ON u.user_id = hr.user_id
        ORDER BY hr.record_id DESC
        LIMIT 100
    ');
    $stmt->execute();
    $health_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('HealthLoadError: ' . $e->getMessage());
    $error_message = 'Error retrieving health records.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Health Records - TCMS</title>
    <?php include 'includes/head_template.php'; ?>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-header" data-aos="fade-up">
        <div class="container">
            <h1>Health Records</h1>
            <p>Permission-scoped medical record management.</p>
        </div>
    </div>

    <div class="container">
        <?php if ($error_message !== ''): ?><div class="alert alert-danger"><?php echo h($error_message); ?></div><?php endif; ?>
        <?php if ($success_message !== ''): ?><div class="alert alert-success"><?php echo h($success_message); ?></div><?php endif; ?>

        <?php if ($canCreate): ?>
            <div class="card mb-3"><div class="card-body">
                <h5>Add Health Record</h5>
                <form method="POST" class="row g-2" data-confirm="Create this health record?">
                    <?php echo csrfInput(); ?>
                    <input type="hidden" name="action" value="create">
                    <div class="col-md-2">
                        <select name="tortoise_id" class="form-select" required>
                            <option value="">Tortoise</option>
                            <?php foreach ($tortoise_list as $t): ?>
                                <option value="<?php echo (int) $t['tortoise_id']; ?>">#<?php echo (int) $t['tortoise_id']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3"><input class="form-control" name="diagnosis" placeholder="Diagnosis" required></div>
                    <div class="col-md-3"><input class="form-control" name="treatment_plan" placeholder="Treatment plan"></div>
                    <div class="col-md-2"><input class="form-control" name="vaccination_date" type="date"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Save</button></div>
                </form>
            </div></div>
        <?php endif; ?>

        <div class="card"><div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead><tr><th>ID</th><th>Tortoise</th><th>Species</th><th>Status</th><th>Diagnosis</th><th>Treatment</th><th>Vaccination</th><th>Vet</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($health_records as $record): ?>
                    <tr>
                        <td>#<?php echo (int) $record['record_id']; ?></td>
                        <td><a href="tortoise_profile.php?id=<?php echo (int) $record['tortoise_id']; ?>">#<?php echo (int) $record['tortoise_id']; ?></a></td>
                        <td><?php echo h($record['species_name']); ?></td>
                        <td><?php echo h($record['status']); ?></td>
                        <td><?php echo h($record['diagnosis']); ?></td>
                        <td><?php echo h($record['treatment_plan'] ?? ''); ?></td>
                        <td><?php echo h((string) ($record['vaccination_date'] ?? '')); ?></td>
                        <td><?php echo h($record['vet_name'] ?? 'N/A'); ?></td>
                        <td>
                            <?php if ($canUpdate): ?>
                                <form method="POST" class="d-flex gap-1 mb-1" data-confirm="Update this health record?">
                                    <?php echo csrfInput(); ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="record_id" value="<?php echo (int) $record['record_id']; ?>">
                                    <input class="form-control form-control-sm" name="diagnosis" value="<?php echo h($record['diagnosis']); ?>" required>
                                    <button class="btn btn-sm btn-outline-primary" type="submit">Update</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($canResolve): ?>
                                <form method="POST" data-confirm="Resolve this health case and set tortoise status to Healthy?">
                                    <?php echo csrfInput(); ?>
                                    <input type="hidden" name="action" value="resolve">
                                    <input type="hidden" name="tortoise_id" value="<?php echo (int) $record['tortoise_id']; ?>">
                                    <button class="btn btn-sm btn-success" type="submit">Resolve Case</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div></div>
    </div>

    <?php include 'includes/footer_script.php'; ?>
    <script src="assets/js/confirm_actions.js"></script>
</body>
</html>