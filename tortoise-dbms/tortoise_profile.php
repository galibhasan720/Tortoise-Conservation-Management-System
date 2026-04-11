<?php
declare(strict_types=1);

$requiredAnyPermissions = ['tortoise.profile.read', 'tortoise.read'];
require_once 'includes/auth_check.php';
require_once 'includes/security.php';
require_once 'db_connect.php';

$tortoiseId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$tortoiseId) {
    http_response_code(400);
    exit('Invalid tortoise ID.');
}

$tortoise = null;
$feeding = [];
$health = [];
$breeding = [];

try {
    $stmt = $pdo->prepare('
        SELECT
            tp.tortoise_id,
            tp.gender,
            tp.weight,
            tp.date_of_entry,
            tp.origin_location,
            tp.status,
            sd.species_name,
            sd.conservation_status,
            e.location AS enclosure_location,
            e.habitat_type
        FROM TORTOISE_PROFILE tp
        INNER JOIN SPECIES_DICT sd ON sd.species_id = tp.species_id
        LEFT JOIN ENCLOSURE e ON e.enclosure_id = tp.enclosure_id
        WHERE tp.tortoise_id = :tortoise_id
        LIMIT 1
    ');
    $stmt->execute([':tortoise_id' => $tortoiseId]);
    $tortoise = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tortoise) {
        http_response_code(404);
        exit('Tortoise profile not found.');
    }

    $feedStmt = $pdo->prepare('SELECT log_id, food_type, amount, timestamp FROM FEEDING_LOG WHERE tortoise_id = :tortoise_id ORDER BY timestamp DESC LIMIT 10');
    $feedStmt->execute([':tortoise_id' => $tortoiseId]);
    $feeding = $feedStmt->fetchAll(PDO::FETCH_ASSOC);

    $healthStmt = $pdo->prepare('SELECT record_id, diagnosis, treatment_plan, vaccination_date FROM HEALTH_RECORD WHERE tortoise_id = :tortoise_id ORDER BY record_id DESC LIMIT 10');
    $healthStmt->execute([':tortoise_id' => $tortoiseId]);
    $health = $healthStmt->fetchAll(PDO::FETCH_ASSOC);

    $breedStmt = $pdo->prepare('
        SELECT breeding_id, mating_date, egg_count, hatch_success
        FROM BREEDING_RECORD
        WHERE male_tortoise_id = :tortoise_id OR female_tortoise_id = :tortoise_id
        ORDER BY mating_date DESC
        LIMIT 10
    ');
    $breedStmt->execute([':tortoise_id' => $tortoiseId]);
    $breeding = $breedStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('TortoiseProfileLoadError: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to load tortoise profile.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Tortoise Profile - TCMS</title>
    <?php include 'includes/head_template.php'; ?>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-header" data-aos="fade-up">
        <div class="container">
            <h1>Tortoise Profile #<?php echo (int) $tortoise['tortoise_id']; ?></h1>
            <p>Detailed profile and recent activity.</p>
        </div>
    </div>

    <div class="container">
        <div class="card mb-3" data-aos="fade-up">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><strong>Species:</strong> <?php echo h($tortoise['species_name']); ?></div>
                    <div class="col-md-6"><strong>Conservation Status:</strong> <?php echo h($tortoise['conservation_status']); ?></div>
                    <div class="col-md-6"><strong>Gender:</strong> <?php echo h($tortoise['gender']); ?></div>
                    <div class="col-md-6"><strong>Weight:</strong> <?php echo h((string) $tortoise['weight']); ?> kg</div>
                    <div class="col-md-6"><strong>Date of Entry:</strong> <?php echo h((string) $tortoise['date_of_entry']); ?></div>
                    <div class="col-md-6"><strong>Status:</strong> <?php echo h($tortoise['status']); ?></div>
                    <div class="col-md-6"><strong>Origin:</strong> <?php echo h($tortoise['origin_location']); ?></div>
                    <div class="col-md-6"><strong>Enclosure:</strong> <?php echo h((string) ($tortoise['enclosure_location'] ?? 'Unassigned')); ?> (<?php echo h((string) ($tortoise['habitat_type'] ?? 'N/A')); ?>)</div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <a class="btn btn-secondary" href="index.php">Back</a>
                    <?php if (hasPermission('tortoise.update') || hasPermission('tortoise.profile.update')): ?>
                        <a class="btn btn-primary" href="create.php?id=<?php echo (int) $tortoise['tortoise_id']; ?>">Edit Profile</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5>Recent Feeding Logs</h5>
                        <ul class="mb-0">
                            <?php foreach ($feeding as $row): ?>
                                <li><?php echo h((string) $row['timestamp']); ?> - <?php echo h($row['food_type']); ?> (<?php echo h((string) $row['amount']); ?>)</li>
                            <?php endforeach; ?>
                            <?php if (!$feeding): ?><li>No feeding logs.</li><?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5>Recent Health Records</h5>
                        <ul class="mb-0">
                            <?php foreach ($health as $row): ?>
                                <li>#<?php echo (int) $row['record_id']; ?> - <?php echo h($row['diagnosis']); ?></li>
                            <?php endforeach; ?>
                            <?php if (!$health): ?><li>No health records.</li><?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5>Recent Breeding Records</h5>
                        <ul class="mb-0">
                            <?php foreach ($breeding as $row): ?>
                                <li><?php echo h((string) $row['mating_date']); ?> - Eggs: <?php echo (int) $row['egg_count']; ?>, Hatched: <?php echo (int) $row['hatch_success']; ?></li>
                            <?php endforeach; ?>
                            <?php if (!$breeding): ?><li>No breeding records.</li><?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer_script.php'; ?>
</body>
</html>
