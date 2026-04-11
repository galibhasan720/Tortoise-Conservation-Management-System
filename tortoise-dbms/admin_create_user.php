<?php
declare(strict_types=1);

$requiredPermission = 'user.create';
require_once 'includes/auth_check.php';
require_once 'includes/security.php';

// Include database connection
require_once 'db_connect.php';

// Include header
require_once 'includes/header.php';

// Initialize variables
$form_data = [
    'username' => '',
    'role_id' => '',
    'password' => '',
];

$error_messages = [];
$success_message = '';

// Process POST request (Handle Form Submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();
    
    // Retrieve and sanitize input
    $form_data['username'] = trim($_POST['username'] ?? '');
    $form_data['role_id'] = trim($_POST['role_id'] ?? '');
    $password_input = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // === VALIDATION =================================================================
    
    // Check username is not empty
    if (empty($form_data['username'])) {
        $error_messages[] = 'Username is required.';
    } elseif (strlen($form_data['username']) < 3) {
        $error_messages[] = 'Username must be at least 3 characters long.';
    } elseif (strlen($form_data['username']) > 50) {
        $error_messages[] = 'Username cannot exceed 50 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $form_data['username'])) {
        $error_messages[] = 'Username can only contain letters, numbers, underscores, dots, and hyphens.';
    }
    
    // Check role_id is selected
    if (empty($form_data['role_id'])) {
        $error_messages[] = 'Role is required.';
    }
    
    // Check password is not empty
    if (empty($password_input)) {
        $error_messages[] = 'Password is required.';
    } elseif (strlen($password_input) < 8) {
        $error_messages[] = 'Password must be at least 8 characters long.';
    }
    
    // Check passwords match
    if ($password_input !== $password_confirm) {
        $error_messages[] = 'Passwords do not match.';
    }
    
    // === ATTEMPT USER CREATION =====================================================
    
    if (empty($error_messages)) {
        try {
            // Hash the password using PHP's default algorithm (currently bcrypt)
            $password_hash = password_hash($password_input, PASSWORD_DEFAULT);
            
            // Prepared statement: Insert new user
            $stmt = $pdo->prepare('
                INSERT INTO USERS (role_id, username, password_hash)
                VALUES (:role_id, :username, :password_hash)
            ');
            
            // Bind parameters using named placeholders
            $stmt->bindParam(':role_id', $form_data['role_id'], PDO::PARAM_INT);
            $stmt->bindParam(':username', $form_data['username'], PDO::PARAM_STR);
            $stmt->bindParam(':password_hash', $password_hash, PDO::PARAM_STR);
            
            // Execute the insert
            $stmt->execute();
            
            // Success!
            $success_message = 'User account created successfully for: ' . htmlspecialchars($form_data['username'], ENT_QUOTES, 'UTF-8');
            
            // Clear form data
            $form_data = [
                'username' => '',
                'role_id' => '',
                'password' => '',
            ];
            
        } catch (PDOException $e) {
            // Check if error is due to duplicate username
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false || 
                strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error_messages[] = 'Username already exists. Please choose a different username.';
            } else {
                // Log the actual error securely
                error_log('User Creation Error: ' . $e->getMessage());
                $error_messages[] = 'An error occurred while creating the user. Please try again later.';
            }
        }
    }
}

// Fetch available roles from database
$roles = [];
try {
    $stmt = $pdo->prepare('SELECT role_id, role_name FROM ROLES ORDER BY role_name');
    $stmt->execute();
    $roles = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Roles Fetch Error: ' . $e->getMessage());
}

?>
<div class="container">
    <div style="display: grid; grid-template-columns: 1fr; gap: 2rem; max-width: 600px;">
        
        <div class="card bento-item">
            <h1 style="margin-bottom: 0.5rem;">Create New User</h1>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Add a new staff member to the system</p>
            
            <?php if (!empty($error_messages)): ?>
                <div style="background-color: #fee; border: 2px solid #c93232; color: #c93232; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <strong>Errors:</strong>
                    <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem;">
                        <?php foreach ($error_messages as $error): ?>
                            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div style="background-color: #efe; border: 2px solid #009579; color: #009579; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="admin_create_user.php" data-confirm="Create this new user account?">
                <?php echo csrfInput(); ?>
                
                <div style="margin-bottom: 1.5rem;">
                    <label for="username"><strong>Username</strong></label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?php echo htmlspecialchars($form_data['username'], ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="e.g., john.smith"
                        required
                        pattern="[a-zA-Z0-9_.-]+"
                        minlength="3"
                        maxlength="50"
                    >
                    <small style="color: var(--text-muted); display: block; margin-top: 0.25rem;">
                        Letters, numbers, underscores, dots, and hyphens allowed. 3-50 characters.
                    </small>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label for="role_id"><strong>Role</strong></label>
                    <select id="role_id" name="role_id" required>
                        <option value="">-- Select a role --</option>
                        <?php foreach ($roles as $role): ?>
                            <option 
                                value="<?php echo htmlspecialchars($role['role_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo ($form_data['role_id'] == $role['role_id']) ? 'selected' : ''; ?>
                            >
                                <?php echo htmlspecialchars($role['role_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label for="password"><strong>Password</strong></label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter a secure password"
                        required
                        minlength="8"
                    >
                    <small style="color: var(--text-muted); display: block; margin-top: 0.25rem;">
                        Minimum 8 characters. Use a mix of uppercase, lowercase, numbers, and symbols.
                    </small>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label for="password_confirm"><strong>Confirm Password</strong></label>
                    <input 
                        type="password" 
                        id="password_confirm" 
                        name="password_confirm" 
                        placeholder="Re-enter the password"
                        required
                        minlength="8"
                    >
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Create User</button>
                    <button type="reset" class="btn btn-outline" style="flex: 1;">Clear Form</button>
                </div>
                
            </form>
        </div>
        
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
