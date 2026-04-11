<?php
declare(strict_types=1);

$requiredPermission = 'user.password.change.own';
require_once 'includes/auth_check.php';
require_once 'includes/security.php';
require_once 'includes/password_policy.php';
require_once 'db_connect.php';

$error_message = '';
$success_message = '';
$forceMode = isset($_GET['force']) && $_GET['force'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (!$forceMode && $currentPassword === '') {
        $error_message = 'Current password is required.';
    } elseif ($newPassword === '' || $confirmPassword === '') {
        $error_message = 'New password and confirmation are required.';
    } elseif ($newPassword !== $confirmPassword) {
        $error_message = 'New password and confirmation do not match.';
    } else {
        $policyError = validatePasswordPolicy($newPassword);
        if ($policyError !== null) {
            $error_message = $policyError;
        } else {
            try {
                $stmt = $pdo->prepare('SELECT password_hash FROM USERS WHERE user_id = :user_id LIMIT 1');
                $stmt->execute([':user_id' => (int) $_SESSION['user_id']]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$row) {
                    $error_message = 'User account not found.';
                } elseif (!$forceMode && !password_verify($currentPassword, (string) $row['password_hash'])) {
                    $error_message = 'Current password is incorrect.';
                } else {
                    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

                    $updateStmt = $pdo->prepare('
                        UPDATE USERS
                        SET password_hash = :password_hash,
                            must_change_password = 0
                        WHERE user_id = :user_id
                    ');
                    $updateStmt->execute([
                        ':password_hash' => $newHash,
                        ':user_id' => (int) $_SESSION['user_id'],
                    ]);

                    $expireStmt = $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL');
                    $expireStmt->execute([':user_id' => (int) $_SESSION['user_id']]);

                    session_regenerate_id(true);
                    $success_message = 'Password changed successfully.';
                    $forceMode = false;
                }
            } catch (PDOException $e) {
                error_log('ChangePasswordError: ' . $e->getMessage());
                $error_message = 'Unable to update password right now.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Change Password - TCMS</title>
    <?php include 'includes/head_template.php'; ?>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-header" data-aos="fade-up">
        <div class="container">
            <h1>Change Password</h1>
            <p><?php echo $forceMode ? 'Password update is required before continuing.' : 'Update your account password securely.'; ?></p>
        </div>
    </div>

    <div class="container">
        <?php if ($error_message !== ''): ?><div class="alert alert-danger"><?php echo h($error_message); ?></div><?php endif; ?>
        <?php if ($success_message !== ''): ?><div class="alert alert-success"><?php echo h($success_message); ?></div><?php endif; ?>

        <div class="card" style="max-width: 700px; margin: 0 auto;">
            <div class="card-body">
                <form method="POST" data-confirm="Confirm password change?">
                    <?php echo csrfInput(); ?>

                    <?php if (!$forceMode): ?>
                        <div class="mb-3">
                            <label class="form-label" for="current_password">Current Password</label>
                            <input class="form-control" id="current_password" type="password" name="current_password" required>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label" for="new_password">New Password</label>
                        <input class="form-control" id="new_password" type="password" name="new_password" required minlength="8">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="confirm_password">Confirm New Password</label>
                        <input class="form-control" id="confirm_password" type="password" name="confirm_password" required minlength="8">
                    </div>

                    <div class="d-flex gap-2">
                        <a class="btn btn-secondary" href="profile.php">Back to Profile</a>
                        <button class="btn btn-primary" type="submit">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer_script.php'; ?>
    <script src="assets/js/confirm_actions.js"></script>
</body>
</html>
