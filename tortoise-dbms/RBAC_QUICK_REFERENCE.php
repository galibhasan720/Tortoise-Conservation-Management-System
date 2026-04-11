<?php
/**
 * Quick Reference: RBAC Configuration Examples
 * 
 * Copy these snippets to protect your pages with the authentication system
 * 
 * @author Senior PHP Security Architect
 * @date April 8, 2026
 */

// ===== CONFIGURATION 1: ANY LOGGED-IN USER =====
// Place at TOP of pages accessible to any authenticated user
/*
<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
?>
  
<h1>Dashboard</h1>
<p>Welcome, <?php echo $_SESSION['role_name']; ?></p>

<?php
require_once 'includes/footer.php';
?>
*/

// ===== CONFIGURATION 2: ADMIN ONLY =====
// Place at TOP of pages accessible to Admin role only
/*
<?php
$allowed_roles = ['Admin'];
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
?>
  
<h1>Admin Panel</h1>

<?php
require_once 'includes/footer.php';
?>
*/

// ===== CONFIGURATION 3: VET & ADMIN ONLY =====
// Place at TOP of pages for Vet and Admin roles
/*
<?php
$allowed_roles = ['Admin', 'Vet'];
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
?>
  
<h1>Health Records (Restricted)</h1>

<?php
require_once 'includes/footer.php';
?>
*/

// ===== CONFIGURATION 4: MULTIPLE ROLES (Caretaker, Breeding Officer, Admin) =====
/*
<?php
$allowed_roles = ['Admin', 'Caretaker', 'Breeding Officer'];
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
?>
  
<h1>Staff Management</h1>

<?php
require_once 'includes/footer.php';
?>
*/

// ===== CONFIGURATION 5: VET SPECIFIC PAGE =====
/*
<?php
$allowed_roles = ['Vet'];
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
?>
  
<h1>Clinical Records (Vet Only)</h1>

<?php
require_once 'includes/footer.php';
?>
*/

// ===== CONFIGURATION 6: BREEDING OFFICER PAGE =====
/*
<?php
$allowed_roles = ['Breeding Officer', 'Admin'];
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
?>
  
<h1>Breeding Program Records</h1>

<?php
require_once 'includes/footer.php';
?>
*/

// ===== CONFIGURATION 7: ENVIRONMENTAL TECH PAGE =====
/*
<?php
$allowed_roles = ['Env Tech', 'Admin'];
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
?>
  
<h1>Environmental Monitoring</h1>

<?php
require_once 'includes/footer.php';
?>
*/

// ===== CONFIGURATION 8: COLLECTION OFFICER PAGE =====
/*
<?php
$allowed_roles = ['Collection Officer', 'Admin'];
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
?>
  
<h1>Data Collection Reports</h1>

<?php
require_once 'includes/footer.php';
?>
*/

// ===== STATIC INDEX/DASHBOARD PAGE (OPTIONAL, if you want a dynamic welcome) =====
/*
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    // User not logged in, show public page or redirect
    header('Location: login.php');
    exit;
}

require_once 'includes/header.php';
?>

<h1>Welcome, <?php echo htmlspecialchars($_SESSION['role_name']); ?></h1>

<div class="dashboard-grid">
    <div class="bento-item">
        <h2>Manage Tortoises</h2>
        <a href="tortoise_list.php" class="btn btn-primary">View List</a>
    </div>
    
    <div class="bento-item">
        <h2>Health Records</h2>
        <a href="health_records.php" class="btn btn-primary">View Records</a>
    </div>
    
    <div class="bento-item">
        <h2>Feeding Logs</h2>
        <a href="feeding_logs.php" class="btn btn-primary">View Logs</a>
    </div>
    
    <?php if ($_SESSION['role_name'] === 'Admin'): ?>
        <div class="bento-item">
            <h2>User Management</h2>
            <a href="admin_create_user.php" class="btn btn-primary">Create User</a>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?>
*/

// ===== QUICK ACCESS VARIABLES IN YOUR PAGES =====
/*
$_SESSION['user_id']      // Current user's ID (integer)
$_SESSION['role_name']    // Current user's role ('Admin', 'Vet', etc.)

// Example: Show Admin link only to Admins
<?php if ($_SESSION['role_name'] === 'Admin'): ?>
    <a href="admin_create_user.php">Create User</a>
<?php endif; ?>

// Example: Show different UI based on role
<?php
switch ($_SESSION['role_name']) {
    case 'Admin':
        echo "You have full system access";
        break;
    case 'Vet':
        echo "You can manage health records";
        break;
    case 'Caretaker':
        echo "You can log daily care activities";
        break;
    case 'Breeding Officer':
        echo "You can manage breeding records";
        break;
    case 'Env Tech':
        echo "You can monitor environmental data";
        break;
    case 'Collection Officer':
        echo "You can submit collection reports";
        break;
}
?>
*/

?>
