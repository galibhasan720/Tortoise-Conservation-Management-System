<?php
declare(strict_types=1);

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/security.php';
require_once 'db_connect.php';

$error_message = '';
$success_message = '';
$dev_reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error_message = 'Invalid request token. Please try again.';
    } else {
        $identity = trim((string) ($_POST['identity'] ?? ''));

        if ($identity === '') {
            $error_message = 'Enter your username or email.';
        } else {
            try {
                $stmt = $pdo->prepare('
                    SELECT user_id, username
                    FROM USERS
                    WHERE (username = :identity OR email = :identity)
                      AND is_active = 1
                    LIMIT 1
                ');
                $stmt->execute([':identity' => $identity]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    $rawToken = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $rawToken);

                    $expireStmt = $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL');
                    $expireStmt->execute([':user_id' => (int) $user['user_id']]);

                    $insertStmt = $pdo->prepare('
                        INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, requested_ip)
                        VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 15 MINUTE), :requested_ip)
                    ');
                    $insertStmt->execute([
                        ':user_id' => (int) $user['user_id'],
                        ':token_hash' => $tokenHash,
                        ':requested_ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                    ]);

                    $resetLink = 'reset_password.php?token=' . urlencode($rawToken);
                    error_log('TCMS password reset link for user ' . (string) $user['username'] . ': ' . $resetLink);

                    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
                    if (str_contains($host, 'localhost') || str_contains($host, '127.0.0.1')) {
                        $dev_reset_link = $resetLink;
                    }
                }

                $success_message = 'If an active account exists for the provided value, a reset link has been generated.';
            } catch (PDOException $e) {
                error_log('ForgotPasswordError: ' . $e->getMessage());
                $error_message = 'Unable to process this request right now.';
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
    <title>Forgot Password - TCMS</title>
    <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body>
    <div class="container" style="max-width: 520px; padding-top: 3rem; padding-bottom: 3rem;">
        <div class="card" style="padding: 2rem;">
            <h1 style="font-size: 1.5rem; margin-bottom: 0.5rem;">Forgot Password</h1>
            <p style="color: var(--ink-soft); margin-bottom: 1.5rem;">Enter your username or email to request a secure reset link.</p>

            <?php if ($error_message !== ''): ?><div class="alert alert-danger"><?php echo h($error_message); ?></div><?php endif; ?>
            <?php if ($success_message !== ''): ?><div class="alert alert-success"><?php echo h($success_message); ?></div><?php endif; ?>

            <?php if ($dev_reset_link !== ''): ?>
                <div class="alert alert-warning">
                    Development reset link: <a href="<?php echo h($dev_reset_link); ?>"><?php echo h($dev_reset_link); ?></a>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <?php echo csrfInput(); ?>
                <div class="mb-3">
                    <label class="form-label" for="identity">Username or Email</label>
                    <input class="form-control" id="identity" name="identity" required maxlength="190">
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-secondary" href="login.php">Back to Login</a>
                    <button class="btn btn-primary" type="submit">Generate Reset Link</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
