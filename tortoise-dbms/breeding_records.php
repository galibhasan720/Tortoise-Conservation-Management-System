<?php
declare(strict_types=1);

$requiredAnyPermissions = ['breeding.read', 'breeding.create', 'breeding.update'];
require_once 'includes/auth_check.php';
require_once 'includes/security.php';
require 'db_connect.php';

$canCreate = hasPermission('breeding.create');
$canUpdate = hasPermission('breeding.update');

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();
    $action = postString('action', 20);

    if ($action === 'create' && $canCreate) {
        $maleId = postInt('male_tortoise_id', false);
        $femaleId = postInt('female_tortoise_id', false);
        $matingDate = postString('mating_date', 20);
        $eggCount = postInt('egg_count');
        $hatchSuccess = postInt('hatch_success');

        if ($matingDate === null || strtotime($matingDate) === false || $eggCount === null || $hatchSuccess === null) {
            $error_message = 'Invalid breeding input.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO BREEDING_RECORD (male_tortoise_id, female_tortoise_id, user_id, mating_date, egg_count, hatch_success) VALUES (:male_tortoise_id, :female_tortoise_id, :user_id, :mating_date, :egg_count, :hatch_success)');
                $stmt->execute([
                    ':male_tortoise_id' => $maleId,
                    ':female_tortoise_id' => $femaleId,
                    ':user_id' => (int) $_SESSION['user_id'],
                    ':mating_date' => $matingDate,
                    ':egg_count' => $eggCount,
                    ':hatch_success' => $hatchSuccess,
                ]);
                $success_message = 'Breeding record created.';
            } catch (PDOException $e) {
                error_log('BreedingCreateError: ' . $e->getMessage());
                $error_message = 'Failed to create breeding record.';
            }
        }
    }

    if ($action === 'update' && $canUpdate) {
        $breedingId = postInt('breeding_id');
        $eggCount = postInt('egg_count');
        $hatchSuccess = postInt('hatch_success');

        if ($breedingId === null || $eggCount === null || $hatchSuccess === null) {
            $error_message = 'Invalid breeding update input.';
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE BREEDING_RECORD SET egg_count = :egg_count, hatch_success = :hatch_success WHERE breeding_id = :breeding_id');
                $stmt->execute([
                    ':egg_count' => $eggCount,
                    ':hatch_success' => $hatchSuccess,
                    ':breeding_id' => $breedingId,
                ]);
                $success_message = 'Breeding record updated.';
            } catch (PDOException $e) {
                error_log('BreedingUpdateError: ' . $e->getMessage());
                $error_message = 'Failed to update breeding record.';
            }
        }
    }
}

$breeding_records = [];
$tortoise_list = [];

try {
    if ($canCreate) {
        $stmtTortoise = $pdo->query('SELECT tortoise_id FROM TORTOISE_PROFILE ORDER BY tortoise_id ASC');
        $tortoise_list = $stmtTortoise->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmt = $pdo->prepare('
        SELECT
            br.breeding_id,
            br.male_tortoise_id,
            br.female_tortoise_id,
            br.user_id,
            br.mating_date,
            br.egg_count,
            br.hatch_success,
            u.username AS officer_name
        FROM BREEDING_RECORD br
        LEFT JOIN USERS u ON u.user_id = br.user_id
        ORDER BY br.mating_date DESC
        LIMIT 100
    ');
    $stmt->execute();
    $breeding_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('BreedingLoadError: ' . $e->getMessage());
    $error_message = 'Error retrieving breeding records.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Breeding Records - TCMS</title>
    <?php include 'includes/head_template.php'; ?>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-header" data-aos="fade-up"><div class="container"><h1>Breeding Records</h1><p>Permission-scoped breeding data operations.</p></div></div>

    <div class="container">
        <?php if ($error_message !== ''): ?><div class="alert alert-danger"><?php echo h($error_message); ?></div><?php endif; ?>
        <?php if ($success_message !== ''): ?><div class="alert alert-success"><?php echo h($success_message); ?></div><?php endif; ?>

        <?php if ($canCreate): ?>
            <div class="card mb-3"><div class="card-body">
                <h5>Add Breeding Record</h5>
                <form method="POST" class="row g-2" data-confirm="Create this breeding record?">
                    <?php echo csrfInput(); ?>
                    <input type="hidden" name="action" value="create">
                    <div class="col-md-2"><select class="form-select" name="male_tortoise_id"><option value="">Male</option><?php foreach ($tortoise_list as $t): ?><option value="<?php echo (int) $t['tortoise_id']; ?>">#<?php echo (int) $t['tortoise_id']; ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-2"><select class="form-select" name="female_tortoise_id"><option value="">Female</option><?php foreach ($tortoise_list as $t): ?><option value="<?php echo (int) $t['tortoise_id']; ?>">#<?php echo (int) $t['tortoise_id']; ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-2"><input class="form-control" type="date" name="mating_date" required></div>
                    <div class="col-md-2"><input class="form-control" type="number" name="egg_count" min="0" required></div>
                    <div class="col-md-2"><input class="form-control" type="number" name="hatch_success" min="0" required></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Save</button></div>
                </form>
            </div></div>
        <?php endif; ?>

        <div class="card"><div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead><tr><th>ID</th><th>Male</th><th>Female</th><th>Date</th><th>Eggs</th><th>Hatched</th><th>Officer</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($breeding_records as $br): ?>
                    <tr>
                        <td>#<?php echo (int) $br['breeding_id']; ?></td>
                        <td>
                            <?php if ($br['male_tortoise_id'] !== null): ?>
                                <a href="tortoise_profile.php?id=<?php echo (int) $br['male_tortoise_id']; ?>"><?php echo h((string) $br['male_tortoise_id']); ?></a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($br['female_tortoise_id'] !== null): ?>
                                <a href="tortoise_profile.php?id=<?php echo (int) $br['female_tortoise_id']; ?>"><?php echo h((string) $br['female_tortoise_id']); ?></a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td><?php echo h((string) $br['mating_date']); ?></td>
                        <td><?php echo h((string) $br['egg_count']); ?></td>
                        <td><?php echo h((string) $br['hatch_success']); ?></td>
                        <td><?php echo h($br['officer_name'] ?? 'N/A'); ?></td>
                        <td>
                            <?php if ($canUpdate): ?>
                                <form method="POST" class="d-flex gap-1" data-confirm="Update this breeding record?">
                                    <?php echo csrfInput(); ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="breeding_id" value="<?php echo (int) $br['breeding_id']; ?>">
                                    <input class="form-control form-control-sm" type="number" name="egg_count" min="0" value="<?php echo h((string) $br['egg_count']); ?>" required>
                                    <input class="form-control form-control-sm" type="number" name="hatch_success" min="0" value="<?php echo h((string) $br['hatch_success']); ?>" required>
                                    <button class="btn btn-sm btn-outline-primary" type="submit">Update</button>
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
