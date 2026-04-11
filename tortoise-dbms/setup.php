<?php
/**
 * Tortoise Conservation Management System - Initial Setup
 * 
 * One-time setup script to initialize the authentication system
 * Creates the initial Admin account and ROLES data
 * 
 * INSTRUCTIONS:
 * 1. Ensure database 'tortoise_conservation_db' exists and is connected
 * 2. Place this file in the root directory temporarily
 * 3. Visit http://localhost/Tortoise Management/setup.php in your browser
 * 4. Follow the form to create the first Admin account
 * 5. DELETE this file when setup is complete (for security)
 * 
 * @author Senior PHP Security Architect
 * @version 1.0
 * @date April 8, 2026
 */

// Include database connection
require_once 'db_connect.php';

// Check if setup has already been completed
$setup_completed = false;
$admin_exists = false;

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM USERS WHERE role_id = (SELECT role_id FROM ROLES WHERE role_name = "Admin")');
    $stmt->execute();
    $result = $stmt->fetch();
    $admin_exists = $result['count'] > 0;
    
    if ($admin_exists) {
        $setup_completed = true;
    }
} catch (PDOException $e) {
    // Table might not exist yet
}

// Initialize variables
$error_messages = [];
$success_message = '';
$form_submitted = false;

// Process POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_submitted = true;
    
    $admin_username = trim($_POST['admin_username'] ?? '');
    $admin_password = $_POST['admin_password'] ?? '';
    $admin_password_confirm = $_POST['admin_password_confirm'] ?? '';
    
    // Validation
    if (empty($admin_username)) {
        $error_messages[] = 'Admin username is required.';
    } elseif (strlen($admin_username) < 3) {
        $error_messages[] = 'Admin username must be at least 3 characters.';
    }
    
    if (empty($admin_password)) {
        $error_messages[] = 'Admin password is required.';
    } elseif (strlen($admin_password) < 8) {
        $error_messages[] = 'Admin password must be at least 8 characters.';
    }
    
    if ($admin_password !== $admin_password_confirm) {
        $error_messages[] = 'Passwords do not match.';
    }
    
    // Attempt to create initial setup
    if (empty($error_messages)) {
        try {
            // Step 1: Insert ROLES if they don't exist
            $roles_to_insert = ['Admin', 'Vet', 'Caretaker', 'Breeding Officer', 'Env Tech', 'Collection Officer'];
            
            foreach ($roles_to_insert as $role_name) {
                try {
                    $stmt = $pdo->prepare('INSERT INTO ROLES (role_name) VALUES (:role_name)');
                    $stmt->bindParam(':role_name', $role_name, PDO::PARAM_STR);
                    $stmt->execute();
                } catch (PDOException $e) {
                    // Role might already exist, that's ok
                    if (strpos($e->getMessage(), 'Duplicate') === false) {
                        throw $e;
                    }
                }
            }
            
            // Step 2: Get the Admin role ID
            $stmt = $pdo->prepare('SELECT role_id FROM ROLES WHERE role_name = "Admin" LIMIT 1');
            $stmt->execute();
            $admin_role = $stmt->fetch();
            
            if (!$admin_role) {
                throw new Exception('Admin role could not be found or created.');
            }
            
            $admin_role_id = $admin_role['role_id'];
            
            // Step 3: Create the Admin user
            $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare('INSERT INTO USERS (role_id, username, password_hash) VALUES (:role_id, :username, :password_hash)');
            $stmt->bindParam(':role_id', $admin_role_id, PDO::PARAM_INT);
            $stmt->bindParam(':username', $admin_username, PDO::PARAM_STR);
            $stmt->bindParam(':password_hash', $password_hash, PDO::PARAM_STR);
            $stmt->execute();
            
            $success_message = 'System setup completed successfully! Admin account created.';
            $setup_completed = true;
            $admin_exists = true;
            
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false || 
                strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error_messages[] = 'Admin username already exists. Setup may have already been completed.';
            } else {
                error_log('Setup Error: ' . $e->getMessage());
                $error_messages[] = 'An error occurred during setup: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
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
    <title>System Setup - Tortoise Conservation Management System</title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 1rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .setup-container {
            width: 100%;
            max-width: 500px;
        }
        .setup-card {
            background-color: var(--surface);
            border: 2px solid var(--line);
            border-radius: 14px;
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
        }
        .setup-card h1 {
            text-align: center;
            margin-bottom: 0.5rem;
            font-size: 1.75rem;
            color: var(--text);
        }
        .setup-card .subtitle {
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
            border: 2px solid var(--line);
            border-radius: 8px;
            background-color: var(--surface);
            color: var(--text);
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: var(--shadow-sm);
        }
        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        .button-group button {
            flex: 1;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            border: 2px solid var(--primary);
            background-color: var(--primary);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .button-group button:hover {
            transform: translateY(-2px) translateX(-2px);
            box-shadow: var(--shadow-md);
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 2px solid;
        }
        .alert-error {
            background-color: #fee;
            border-color: #c93232;
            color: #c93232;
        }
        .alert-error ul {
            margin: 0.5rem 0 0 0;
            padding-left: 1.5rem;
        }
        .alert-success {
            background-color: #efe;
            border-color: #009579;
            color: #009579;
        }
        .info-box {
            background-color: var(--surface-soft);
            border: 2px solid var(--line);
            border-radius: 8px;
            padding: 1rem;
            margin: 1.5rem 0;
            font-size: 0.95rem;
            color: var(--text);
        }
        .info-box strong {
            display: block;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-card">
            <h1>System Setup</h1>
            <p class="subtitle">Initialize the Authentication System</p>
            
            <?php if ($setup_completed && $admin_exists): ?>
                <div class="alert alert-success">
                    <strong>✓ System is Ready!</strong>
                    <p>The authentication system has been successfully initialized.</p>
                    <p><strong>Next Steps:</strong></p>
                    <ol style="margin: 0.5rem 0; padding-left: 1.5rem;">
                        <li>Delete this <code>setup.php</code> file immediately (for security)</li>
                        <li>Visit <a href="login.php" style="color: inherit; text-decoration: underline;">login.php</a> to log in</li>
                        <li>Go to <a href="admin_create_user.php" style="color: inherit; text-decoration: underline;">admin_create_user.php</a> to create additional accounts</li>
                    </ol>
                </div>
                
                <div class="info-box">
                    <strong>⚠ IMPORTANT:</strong>
                    Delete the <code>setup.php</code> file from your server immediately for security reasons.
                </div>
                
            <?php elseif (!$admin_exists): ?>
                
                <?php if (!empty($error_messages)): ?>
                    <div class="alert alert-error">
                        <strong>Errors:</strong>
                        <ul>
                            <?php foreach ($error_messages as $error): ?>
                                <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message) && $form_submitted): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
                
                <div class="info-box">
                    <strong>Welcome!</strong>
                    Create the first Admin account for your Tortoise Conservation Management System.
                </div>
                
                <form method="POST" action="setup.php">
                    <div class="form-group">
                        <label for="admin_username">Admin Username</label>
                        <input 
                            type="text" 
                            id="admin_username" 
                            name="admin_username" 
                            placeholder="e.g., administrator"
                            required
                            minlength="3"
                            autofocus
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_password">Admin Password</label>
                        <input 
                            type="password" 
                            id="admin_password" 
                            name="admin_password" 
                            placeholder="Minimum 8 characters"
                            required
                            minlength="8"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_password_confirm">Confirm Password</label>
                        <input 
                            type="password" 
                            id="admin_password_confirm" 
                            name="admin_password_confirm" 
                            placeholder="Re-enter password"
                            required
                            minlength="8"
                        >
                    </div>
                    
                    <div class="button-group">
                        <button type="submit">Create Admin Account</button>
                    </div>
                </form>
                
            <?php else: ?>
                <div class="alert alert-success">
                    ✓ Admin account already exists. No further setup needed.
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <p>Proceed to <a href="login.php" style="color: var(--primary); text-decoration: underline;">Login</a></p>
                </div>
                
            <?php endif; ?>
            
        </div>
    </div>
</body>
</html>
