<?php
declare(strict_types=1);

session_start();

require_once 'includes/security.php';
require_once 'includes/password_policy.php';
require_once 'db_connect.php';

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$error_message = '';
$success_message = '';
$tokenValid = false;
$tokenRow = null;

if ($token !== '') {
    try {
        $tokenHash = hash('sha256', $token);
        $stmt = $pdo->prepare('
            SELECT token_id, user_id, expires_at, used_at
            FROM password_reset_tokens
            WHERE token_hash = :token_hash
            LIMIT 1
        ');
        $stmt->execute([':token_hash' => $tokenHash]);
        $tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($tokenRow && $tokenRow['used_at'] === null && strtotime((string) $tokenRow['expires_at']) > time()) {
            $tokenValid = true;
        }
    } catch (PDOException $e) {
        error_log('ResetTokenLookupError: ' . $e->getMessage());
        $error_message = 'Unable to validate this reset token.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error_message = 'Invalid request token. Please try again.';
    } elseif ($token === '' || !$tokenValid || !$tokenRow) {
        $error_message = 'This reset token is invalid or expired.';
    } else {
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($newPassword === '' || $confirmPassword === '') {
            $error_message = 'Both password fields are required.';
        } elseif ($newPassword !== $confirmPassword) {
            $error_message = 'Passwords do not match.';
        } else {
            $policyError = validatePasswordPolicy($newPassword);
            if ($policyError !== null) {
                $error_message = $policyError;
            } else {
                try {
                    $pdo->beginTransaction();

                    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

                    $updateUserStmt = $pdo->prepare('
                        UPDATE USERS
                        SET password_hash = :password_hash,
                            must_change_password = 0
                        WHERE user_id = :user_id
                    ');
                    $updateUserStmt->execute([
                        ':password_hash' => $newHash,
                        ':user_id' => (int) $tokenRow['user_id'],
                    ]);

                    $useTokenStmt = $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE token_id = :token_id');
                    $useTokenStmt->execute([':token_id' => (int) $tokenRow['token_id']]);

                    $expireOthersStmt = $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL');
                    $expireOthersStmt->execute([':user_id' => (int) $tokenRow['user_id']]);

                    $pdo->commit();

                    $success_message = 'Password reset successful. You can now log in with the new password.';
                    $tokenValid = false;
                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log('ResetPasswordError: ' . $e->getMessage());
                    $error_message = 'Unable to reset password right now.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - TCMS</title>
    <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body>
    <div class="container" style="max-width: 520px; padding-top: 3rem; padding-bottom: 3rem;">
        <div class="card" style="padding: 2rem;">
            <h1 style="font-size: 1.5rem; margin-bottom: 0.5rem;">Reset Password</h1>
            <p style="color: var(--ink-soft); margin-bottom: 1.5rem;">Create a new password for your account.</p>

            <?php if ($error_message !== ''): ?><div class="alert alert-danger"><?php echo h($error_message); ?></div><?php endif; ?>
            <?php if ($success_message !== ''): ?><div class="alert alert-success"><?php echo h($success_message); ?></div><?php endif; ?>

            <?php if ($tokenValid): ?>
                <form method="POST" data-confirm="Confirm password reset?">
                    <?php echo csrfInput(); ?>
                    <input type="hidden" name="token" value="<?php echo h($token); ?>">
                    <div class="mb-3">
                        <label class="form-label" for="new_password">New Password</label>
                        <input class="form-control" id="new_password" type="password" name="new_password" required minlength="8">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="confirm_password">Confirm New Password</label>
                        <input class="form-control" id="confirm_password" type="password" name="confirm_password" required minlength="8">
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-secondary" href="login.php">Back to Login</a>
                        <button class="btn btn-primary" type="submit">Reset Password</button>
                    </div>
                </form>
            <?php else: ?>
                <?php if ($success_message !== ''): ?>
                    <a class="btn btn-primary" href="login.php">Go to Login</a>
                <?php else: ?>
                    <div class="alert alert-warning">Reset token is missing, invalid, or expired.</div>
                    <a class="btn btn-secondary" href="forgot_password.php">Request New Link</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/js/confirm_actions.js"></script>
</body>
</html>
