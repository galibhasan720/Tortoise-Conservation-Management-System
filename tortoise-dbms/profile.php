<?php
declare(strict_types=1);

$requiredPermission = 'user.profile.read';
require_once 'includes/auth_check.php';
require_once 'includes/security.php';
require_once 'db_connect.php';

$canUpdateOwnProfile = hasPermission('user.profile.update.own');

$error_message = '';
$success_message = '';

try {
    $stmt = $pdo->prepare('SELECT user_id, username, full_name, email, phone, role_id FROM USERS WHERE user_id = :user_id LIMIT 1');
    $stmt->execute([':user_id' => (int) $_SESSION['user_id']]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        http_response_code(404);
        exit('User profile not found.');
    }
} catch (PDOException $e) {
    error_log('ProfileLoadError: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to load profile.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    if (!$canUpdateOwnProfile) {
        http_response_code(403);
        exit('403 Forbidden');
    }

    $username = postString('username', 50);
    $fullName = postString('full_name', 120, false);
    $email = postString('email', 190, false);
    $phone = postString('phone', 30, false);

    if ($username === null) {
        $error_message = 'Username is required and must be 50 characters or fewer.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
        $error_message = 'Username contains invalid characters.';
    } elseif ($email !== '' && $email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid email address.';
    } else {
        try {
            $dupeStmt = $pdo->prepare('SELECT user_id FROM USERS WHERE username = :username AND user_id <> :user_id LIMIT 1');
            $dupeStmt->execute([
                ':username' => $username,
                ':user_id' => (int) $_SESSION['user_id'],
            ]);
            if ($dupeStmt->fetch(PDO::FETCH_ASSOC)) {
                $error_message = 'Username already exists. Choose another username.';
            } else {
                $emailNormalized = ($email === '' ? null : $email);
                $phoneNormalized = ($phone === '' ? null : $phone);
                $nameNormalized = ($fullName === '' ? null : $fullName);

                $updateStmt = $pdo->prepare('
                    UPDATE USERS
                    SET username = :username,
                        full_name = :full_name,
                        email = :email,
                        phone = :phone
                    WHERE user_id = :user_id
                ');
                $updateStmt->execute([
                    ':username' => $username,
                    ':full_name' => $nameNormalized,
                    ':email' => $emailNormalized,
                    ':phone' => $phoneNormalized,
                    ':user_id' => (int) $_SESSION['user_id'],
                ]);

                $profile['username'] = $username;
                $profile['full_name'] = $nameNormalized;
                $profile['email'] = $emailNormalized;
                $profile['phone'] = $phoneNormalized;

                $success_message = 'Profile updated successfully.';
            }
        } catch (PDOException $e) {
            error_log('ProfileUpdateError: ' . $e->getMessage());
            $error_message = 'Unable to update your profile right now.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Profile - TCMS</title>
    <?php include 'includes/head_template.php'; ?>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-header" data-aos="fade-up">
        <div class="container">
            <h1>My Profile</h1>
            <p>Manage your account details securely.</p>
        </div>
    </div>

    <div class="container">
        <?php if ($error_message !== ''): ?><div class="alert alert-danger"><?php echo h($error_message); ?></div><?php endif; ?>
        <?php if ($success_message !== ''): ?><div class="alert alert-success"><?php echo h($success_message); ?></div><?php endif; ?>

        <div class="card" style="max-width: 760px; margin: 0 auto;">
            <div class="card-body">
                <form method="POST" data-confirm="Save profile changes?">
                    <?php echo csrfInput(); ?>

                    <div class="mb-3">
                        <label class="form-label" for="username">Username</label>
                        <input class="form-control" id="username" name="username" value="<?php echo h((string) $profile['username']); ?>" required maxlength="50" pattern="[a-zA-Z0-9_.-]+" <?php echo $canUpdateOwnProfile ? '' : 'readonly'; ?>>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="full_name">Full Name</label>
                        <input class="form-control" id="full_name" name="full_name" value="<?php echo h((string) ($profile['full_name'] ?? '')); ?>" maxlength="120" <?php echo $canUpdateOwnProfile ? '' : 'readonly'; ?>>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control" id="email" name="email" type="email" value="<?php echo h((string) ($profile['email'] ?? '')); ?>" maxlength="190" <?php echo $canUpdateOwnProfile ? '' : 'readonly'; ?>>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="phone">Phone</label>
                        <input class="form-control" id="phone" name="phone" value="<?php echo h((string) ($profile['phone'] ?? '')); ?>" maxlength="30" <?php echo $canUpdateOwnProfile ? '' : 'readonly'; ?>>
                    </div>

                    <div class="d-flex gap-2">
                        <a class="btn btn-secondary" href="index.php">Back</a>
                        <?php if ($canUpdateOwnProfile): ?>
                            <button class="btn btn-primary" type="submit">Save Profile</button>
                        <?php endif; ?>
                        <?php if (hasPermission('user.password.change.own')): ?>
                            <a class="btn btn-outline-primary" href="change_password.php">Change Password</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer_script.php'; ?>
    <script src="assets/js/confirm_actions.js"></script>
</body>
</html>
