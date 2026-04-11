<?php
declare(strict_types=1);

require_once 'includes/security.php';
require_once 'includes/permission.php';

$tortoiseId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
$isEditMode = ($tortoiseId !== null && $tortoiseId !== false);

$requiredPermission = $isEditMode ? 'tortoise.update' : 'tortoise.create';
require_once 'includes/auth_check.php';
require 'db_connect.php';

$errorMessage = '';
$speciesList = [];
$enclosureList = [];
$tortoise = [
    'species_id' => '',
    'enclosure_id' => '',
    'gender' => 'Unknown',
    'weight' => '',
    'date_of_entry' => date('Y-m-d'),
    'origin_location' => '',
    'status' => 'Healthy',
];

try {
    $speciesStmt = $pdo->query('SELECT species_id, species_name FROM SPECIES_DICT ORDER BY species_name ASC');
    $speciesList = $speciesStmt->fetchAll(PDO::FETCH_ASSOC);

    $enclosureStmt = $pdo->query('SELECT enclosure_id, location FROM ENCLOSURE ORDER BY location ASC');
    $enclosureList = $enclosureStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($isEditMode) {
        $tortoiseStmt = $pdo->prepare('SELECT * FROM TORTOISE_PROFILE WHERE tortoise_id = :tortoise_id LIMIT 1');
        $tortoiseStmt->execute([':tortoise_id' => $tortoiseId]);
        $row = $tortoiseStmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            exit('Tortoise record not found.');
        }
        $tortoise = $row;
    }
} catch (PDOException $e) {
    error_log('CreateLoadError: ' . $e->getMessage());
    $errorMessage = 'Failed to load form data.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    if ($isEditMode && !hasPermission('tortoise.update')) {
        http_response_code(403);
        exit('403 Forbidden');
    }
    if (!$isEditMode && !hasPermission('tortoise.create')) {
        http_response_code(403);
        exit('403 Forbidden');
    }

    $speciesId = postInt('species_id');
    $enclosureId = postInt('enclosure_id', false);
    $gender = postString('gender', 20);
    $weight = postFloat('weight');
    $dateOfEntry = postString('date_of_entry', 20);
    $originLocation = postString('origin_location', 255);
    $status = postString('status', 30);

    $errors = [];
    if ($speciesId === null) {
        $errors[] = 'Species is required.';
    }
    if (!in_array($gender, ['Male', 'Female', 'Unknown'], true)) {
        $errors[] = 'Gender is invalid.';
    }
    if ($weight === null || $weight <= 0) {
        $errors[] = 'Weight must be positive.';
    }
    if ($dateOfEntry === null || strtotime($dateOfEntry) === false) {
        $errors[] = 'Date of entry is invalid.';
    }
    if ($originLocation === null) {
        $errors[] = 'Origin location is required.';
    }
    if (!in_array($status, ['Healthy', 'Under Treatment', 'Quarantine', 'Deceased', 'Released'], true)) {
        $errors[] = 'Status is invalid.';
    }

    if (empty($errors)) {
        try {
            if ($isEditMode) {
                $stmt = $pdo->prepare('
                    UPDATE TORTOISE_PROFILE
                    SET enclosure_id = :enclosure_id,
                        species_id = :species_id,
                        gender = :gender,
                        weight = :weight,
                        date_of_entry = :date_of_entry,
                        origin_location = :origin_location,
                        status = :status
                    WHERE tortoise_id = :tortoise_id
                ');
                $stmt->execute([
                    ':enclosure_id' => $enclosureId,
                    ':species_id' => $speciesId,
                    ':gender' => $gender,
                    ':weight' => $weight,
                    ':date_of_entry' => $dateOfEntry,
                    ':origin_location' => $originLocation,
                    ':status' => $status,
                    ':tortoise_id' => $tortoiseId,
                ]);
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO TORTOISE_PROFILE
                    (enclosure_id, species_id, gender, weight, date_of_entry, origin_location, status)
                    VALUES
                    (:enclosure_id, :species_id, :gender, :weight, :date_of_entry, :origin_location, :status)
                ');
                $stmt->execute([
                    ':enclosure_id' => $enclosureId,
                    ':species_id' => $speciesId,
                    ':gender' => $gender,
                    ':weight' => $weight,
                    ':date_of_entry' => $dateOfEntry,
                    ':origin_location' => $originLocation,
                    ':status' => $status,
                ]);
            }

            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            error_log('CreateSaveError: ' . $e->getMessage());
            $errorMessage = 'Failed to save tortoise record.';
        }
    } else {
        $errorMessage = implode(' ', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $isEditMode ? 'Edit Tortoise' : 'Register Tortoise'; ?> - TCMS</title>
    <?php include 'includes/head_template.php'; ?>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-header" data-aos="fade-up">
        <div class="container">
            <h1><?php echo $isEditMode ? 'Edit Tortoise Profile' : 'Register New Tortoise'; ?></h1>
            <p>Permission-based tortoise profile management.</p>
        </div>
    </div>

    <div class="container">
        <div class="card" style="max-width: 680px; margin: 0 auto; padding: 2rem;" data-aos="fade-up">
            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger"><?php echo h($errorMessage); ?></div>
            <?php endif; ?>

            <form method="POST" data-confirm="<?php echo $isEditMode ? 'Save tortoise profile updates?' : 'Create this tortoise profile?'; ?>">
                <?php echo csrfInput(); ?>
                <div class="mb-3">
                    <label class="form-label">Species</label>
                    <select class="form-select" name="species_id" required>
                        <option value="">Select species</option>
                        <?php foreach ($speciesList as $species): ?>
                            <option value="<?php echo (int) $species['species_id']; ?>" <?php echo ((int) $tortoise['species_id'] === (int) $species['species_id']) ? 'selected' : ''; ?>>
                                <?php echo h($species['species_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Enclosure</label>
                    <select class="form-select" name="enclosure_id">
                        <option value="">Unassigned</option>
                        <?php foreach ($enclosureList as $enclosure): ?>
                            <option value="<?php echo (int) $enclosure['enclosure_id']; ?>" <?php echo ((string) $tortoise['enclosure_id'] === (string) $enclosure['enclosure_id']) ? 'selected' : ''; ?>>
                                <?php echo h($enclosure['location']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Gender</label>
                    <select class="form-select" name="gender" required>
                        <?php foreach (['Male', 'Female', 'Unknown'] as $genderOption): ?>
                            <option <?php echo ($tortoise['gender'] === $genderOption) ? 'selected' : ''; ?>><?php echo h($genderOption); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Weight (kg)</label>
                    <input type="number" step="0.01" class="form-control" name="weight" value="<?php echo h((string) $tortoise['weight']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Date of Entry</label>
                    <input type="date" class="form-control" name="date_of_entry" value="<?php echo h((string) $tortoise['date_of_entry']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Origin Location</label>
                    <input type="text" class="form-control" name="origin_location" value="<?php echo h((string) $tortoise['origin_location']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status" required>
                        <?php foreach (['Healthy', 'Under Treatment', 'Quarantine', 'Deceased', 'Released'] as $statusOption): ?>
                            <option <?php echo ($tortoise['status'] === $statusOption) ? 'selected' : ''; ?>><?php echo h($statusOption); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <a href="index.php" class="btn btn-secondary flex-fill">Cancel</a>
                    <?php if ($isEditMode): ?>
                        <a href="tortoise_profile.php?id=<?php echo (int) $tortoiseId; ?>" class="btn btn-outline-primary flex-fill">View Profile</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary flex-fill"><?php echo $isEditMode ? 'Update' : 'Create'; ?></button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/footer_script.php'; ?>
    <script src="assets/js/confirm_actions.js"></script>
</body>
</html>
