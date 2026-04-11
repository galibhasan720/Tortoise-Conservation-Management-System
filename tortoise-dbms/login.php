<?php
declare(strict_types=1);

session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'db_connect.php';
require_once 'includes/security.php';
require_once 'includes/permission.php';

$username_input = '';
$error_message = '';
$success_message = '';

if (isset($_GET['expired'])) {
    $error_message = 'Your session expired due to inactivity. Please log in again.';
} elseif (isset($_GET['logged_out'])) {
    $success_message = 'You have been logged out successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error_message = 'Invalid request token. Please try again.';
    } else {
        $username_input = trim((string) ($_POST['username'] ?? ''));
        $password_input = (string) ($_POST['password'] ?? '');

        if ($username_input === '' || $password_input === '') {
            $error_message = 'Username and password are required.';
        } else {
            try {
                $stmt = $pdo->prepare('
                    SELECT
                        u.user_id,
                        u.is_active,
                        u.must_change_password,
                        u.password_hash,
                        r.role_id,
                        r.role_name,
                        r.role_code
                    FROM USERS u
                    INNER JOIN ROLES r ON u.role_id = r.role_id
                    WHERE u.username = :username
                    LIMIT 1
                ');
                $stmt->bindParam(':username', $username_input, PDO::PARAM_STR);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password_input, (string) $user['password_hash'])) {
                    if ((int) ($user['is_active'] ?? 1) !== 1) {
                        $error_message = 'Your account is inactive. Contact Administrator.';
                    } else {
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = (int) $user['user_id'];
                        $_SESSION['role_id'] = (int) $user['role_id'];
                        $_SESSION['role_name'] = (string) $user['role_name'];
                        $_SESSION['role_code'] = (string) $user['role_code'];
                        $_SESSION['last_activity'] = time();

                        loadUserPermissions((int) $user['user_id']);

                        if ((int) ($user['must_change_password'] ?? 0) === 1) {
                            header('Location: change_password.php?force=1');
                            exit;
                        }

                        header('Location: index.php');
                        exit;
                    }
                } else {
                    $error_message = 'Invalid username or password. Please try again.';
                }
            } catch (PDOException $e) {
                error_log('Login Query Error: ' . $e->getMessage());
                $error_message = 'An error occurred. Please try again later.';
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
    <title>Login - Tortoise Conservation Management System</title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bg);
            padding: 1rem;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
        }
        .login-card {
            padding: 2.5rem;
        }
        .login-card h1 {
            text-align: center;
            margin-bottom: 0.5rem;
            font-size: 1.75rem;
        }
        .login-card .subtitle {
            text-align: center;
            color: var(--text-muted);
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text);
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
        }
        .login-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        .login-actions button {
            flex: 1;
        }
        .error-alert {
            background-color: #fee;
            border: 2px solid #c93232;
            color: #c93232;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .success-alert {
            background-color: #efe;
            border: 2px solid #009579;
            color: #009579;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card login-card">
            <h1>Tortoise Conservation</h1>
            <p class="subtitle">Management System Login</p>

            <?php if (!empty($error_message)): ?>
                <div class="error-alert" role="alert">
                    <?php echo h($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="success-alert" role="alert">
                    <?php echo h($success_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" autocomplete="off">
                <?php echo csrfInput(); ?>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?php echo h($username_input); ?>"
                        placeholder="Enter your username"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >
                </div>

                <div class="login-actions">
                    <button type="submit" class="btn btn-primary">Login</button>
                    <button type="reset" class="btn btn-outline">Clear</button>
                </div>
            </form>

            <p style="text-align: center; margin-top: 1.5rem; color: var(--text-muted); font-size: 0.9rem;">
                Contact your Administrator if you need account access.
            </p>
            <p style="text-align: center; margin-top: 0.5rem;">
                <a href="forgot_password.php">Forgot password?</a>
            </p>
        </div>
    </div>
</body>
</html>
