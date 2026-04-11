<?php
declare(strict_types=1);

$requiredPermission = 'settings.read';
require_once 'includes/auth_check.php';
require_once 'includes/security.php';
require_once 'db_connect.php';

$canManageUsers = hasPermission('user.update');
$canResetPasswords = hasPermission('user.password.reset.manage');

$error_message = '';
$success_message = '';
$dev_reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $action = postString('action', 30);

    try {
        if ($action === 'update_user' && $canManageUsers) {
            $userId = postInt('user_id');
            $username = postString('username', 50);
            $fullName = postString('full_name', 120, false);
            $email = postString('email', 190, false);
            $phone = postString('phone', 30, false);
            $isActive = postString('is_active', 1) === '1' ? 1 : 0;
            $roleId = postInt('role_id');

            if ($userId === null || $username === null || $roleId === null) {
                $error_message = 'Invalid user update input.';
            } elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
                $error_message = 'Username contains invalid characters.';
            } elseif ($email !== '' && $email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = 'Invalid email address.';
            } else {
                $dupeStmt = $pdo->prepare('SELECT user_id FROM USERS WHERE username = :username AND user_id <> :user_id LIMIT 1');
                $dupeStmt->execute([
                    ':username' => $username,
                    ':user_id' => $userId,
                ]);

                if ($dupeStmt->fetch(PDO::FETCH_ASSOC)) {
                    $error_message = 'Username already exists.';
                } else {
                    $stmt = $pdo->prepare('
                        UPDATE USERS
                        SET username = :username,
                            full_name = :full_name,
                            email = :email,
                            phone = :phone,
                            role_id = :role_id,
                            is_active = :is_active
                        WHERE user_id = :user_id
                    ');
                    $stmt->execute([
                        ':username' => $username,
                        ':full_name' => ($fullName === '' ? null : $fullName),
                        ':email' => ($email === '' ? null : $email),
                        ':phone' => ($phone === '' ? null : $phone),
                        ':role_id' => $roleId,
                        ':is_active' => $isActive,
                        ':user_id' => $userId,
                    ]);
                    $success_message = 'User details updated.';
                }
            }
        }

        if ($action === 'generate_reset_link' && $canResetPasswords) {
            $userId = postInt('user_id');
            if ($userId === null) {
                $error_message = 'Invalid user for reset link generation.';
            } else {
                $rawToken = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $rawToken);

                $expireStmt = $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL');
                $expireStmt->execute([':user_id' => $userId]);

                $insertStmt = $pdo->prepare('
                    INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, requested_ip)
                    VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 15 MINUTE), :requested_ip)
                ');
                $insertStmt->execute([
                    ':user_id' => $userId,
                    ':token_hash' => $tokenHash,
                    ':requested_ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                ]);

                $mustChangeStmt = $pdo->prepare('UPDATE USERS SET must_change_password = 1 WHERE user_id = :user_id');
                $mustChangeStmt->execute([':user_id' => $userId]);

                $resetLink = 'reset_password.php?token=' . urlencode($rawToken);
                $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
                if (str_contains($host, 'localhost') || str_contains($host, '127.0.0.1')) {
                    $dev_reset_link = $resetLink;
                }

                $success_message = 'Reset link generated. Password is never displayed.';
                error_log('TCMS admin generated reset link for user_id ' . $userId . ': ' . $resetLink);
            }
        }
    } catch (PDOException $e) {
        error_log('SettingsActionError: ' . $e->getMessage());
        $error_message = 'Unable to complete this action.';
    }
}

$users = [];
$roles = [];

try {
    $roleStmt = $pdo->query('SELECT role_id, role_name FROM ROLES ORDER BY role_name ASC');
    $roles = $roleStmt->fetchAll(PDO::FETCH_ASSOC);

    $userStmt = $pdo->query('
        SELECT u.user_id, u.username, u.full_name, u.email, u.phone, u.is_active, u.must_change_password, u.role_id, r.role_name
        FROM USERS u
        INNER JOIN ROLES r ON r.role_id = u.role_id
        ORDER BY r.role_name, u.username
        LIMIT 300
    ');
    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('SettingsLoadError: ' . $e->getMessage());
    $error_message = 'Unable to load settings data.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Settings - TCMS</title>
    <?php include 'includes/head_template.php'; ?>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-header" data-aos="fade-up">
        <div class="container">
            <h1>Settings</h1>
            <p>Manage users and account controls without revealing passwords.</p>
        </div>
    </div>

    <div class="container">
        <?php if ($error_message !== ''): ?><div class="alert alert-danger"><?php echo h($error_message); ?></div><?php endif; ?>
        <?php if ($success_message !== ''): ?><div class="alert alert-success"><?php echo h($success_message); ?></div><?php endif; ?>

        <?php if ($dev_reset_link !== ''): ?>
            <div class="alert alert-warning">Development reset link: <a href="<?php echo h($dev_reset_link); ?>"><?php echo h($dev_reset_link); ?></a></div>
        <?php endif; ?>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Active</th>
                            <th>Must Change Password</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <form method="POST" class="d-flex gap-1 align-items-center" data-confirm="Save user changes?">
                                        <?php echo csrfInput(); ?>
                                        <input type="hidden" name="action" value="update_user">
                                        <input type="hidden" name="user_id" value="<?php echo (int) $user['user_id']; ?>">
                                        <input class="form-control form-control-sm" name="username" value="<?php echo h($user['username']); ?>" maxlength="50" pattern="[a-zA-Z0-9_.-]+" <?php echo $canManageUsers ? '' : 'readonly'; ?>>
                                </td>
                                <td>
                                        <select class="form-select form-select-sm" name="role_id" <?php echo $canManageUsers ? '' : 'disabled'; ?>>
                                            <?php foreach ($roles as $role): ?>
                                                <option value="<?php echo (int) $role['role_id']; ?>" <?php echo ((int) $user['role_id'] === (int) $role['role_id']) ? 'selected' : ''; ?>><?php echo h($role['role_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                </td>
                                <td><input class="form-control form-control-sm" name="email" value="<?php echo h((string) ($user['email'] ?? '')); ?>" <?php echo $canManageUsers ? '' : 'readonly'; ?>></td>
                                <td><input class="form-control form-control-sm" name="phone" value="<?php echo h((string) ($user['phone'] ?? '')); ?>" <?php echo $canManageUsers ? '' : 'readonly'; ?>></td>
                                <td>
                                    <select class="form-select form-select-sm" name="is_active" <?php echo $canManageUsers ? '' : 'disabled'; ?>>
                                        <option value="1" <?php echo ((int) $user['is_active'] === 1) ? 'selected' : ''; ?>>Yes</option>
                                        <option value="0" <?php echo ((int) $user['is_active'] === 0) ? 'selected' : ''; ?>>No</option>
                                    </select>
                                </td>
                                <td><?php echo ((int) $user['must_change_password'] === 1) ? 'Yes' : 'No'; ?></td>
                                <td class="d-flex gap-1">
                                    <?php if ($canManageUsers): ?>
                                        <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                                    <?php endif; ?>
                                    </form>

                                    <?php if ($canResetPasswords): ?>
                                        <form method="POST" data-confirm="Generate a secure password reset link for this user?">
                                            <?php echo csrfInput(); ?>
                                            <input type="hidden" name="action" value="generate_reset_link">
                                            <input type="hidden" name="user_id" value="<?php echo (int) $user['user_id']; ?>">
                                            <button class="btn btn-sm btn-outline-secondary" type="submit">Reset Link</button>
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
