#!/usr/bin/env perl
# -*- coding: utf-8 -*-

################################################################################
#
#   TORTOISE CONSERVATION MANAGEMENT SYSTEM
#   Authentication & Role-Based Access Control (RBAC) System
#   
#   Complete Implementation Guide
#
#   @author Senior PHP Security Architect
#   @date April 8, 2026
#   @version 1.0
#
################################################################################

================================================================================
OVERVIEW
================================================================================

This authentication system provides:
✓ Secure user login with password hashing (password_hash/password_verify)
✓ SQL Injection prevention (PDO prepared statements with named parameters)
✓ Role-Based Access Control (RBAC) for 6 staff roles
✓ Session management with session regeneration (prevents session fixation)
✓ Admin-only user account creation
✓ Reusable authentication checks for protecting pages

Database Schema:
  USERS table: user_id (PK), role_id (FK), username (UNIQUE), password_hash
  ROLES table: role_id (PK), role_name (ENUM)


================================================================================
FILE STRUCTURE & PURPOSE
================================================================================

1. login.php
   Location: /Tortoise Management/login.php
   Purpose: Public-facing login page
   Features:
     - Displays login form (username + password)
     - Handles POST authentication
     - Verifies credentials via password_verify()
     - Creates session with user_id & role_name
     - Regenerates session ID (security)
     - Redirects authenticated users to index.php
   
2. logout.php
   Location: /Tortoise Management/logout.php
   Purpose: Destroy session and secure logout
   Features:
     - Unsets all session variables
     - Deletes session cookie
     - Destroys session
     - Redirects to login.php
   
3. includes/auth_check.php
   Location: /Tortoise Management/includes/auth_check.php
   Purpose: Reusable authentication middleware
   Features:
     - Checks if user is logged in
     - Optionally enforces role-based access
     - Shows 403 error if user lacks required role
     - Handles already-started sessions gracefully
   
4. admin_create_user.php
   Location: /Tortoise Management/admin_create_user.php
   Purpose: Admin interface for creating new user accounts
   Features:
     - Enforces Admin-only access via auth_check.php
     - Form for username, role selection, password
     - Password validation (min 8 chars, match)
     - Hashes password with password_hash()
     - Prevents duplicate usernames
     - Shows success/error messages


================================================================================
HOW TO USE THE AUTHENTICATION SYSTEM
================================================================================

STEP 1: Protect a Page (Basic Authentication)
──────────────────────────────────────────────────────────────────────────────
Place this at the VERY TOP of ANY page that should require login:

    <?php
    $allowed_roles = null;  // Optional: set to array for RBAC
    require_once 'includes/auth_check.php';
    // ... rest of your page code ...
    ?>

Example (No Role Restriction):
    <?php
    require_once 'includes/auth_check.php';
    ?>

If user is NOT logged in → redirected to login.php
If user IS logged in → page loads normally
Session variables available: $_SESSION['user_id'], $_SESSION['role_name']


STEP 2: Protect a Page with Role-Based Access Control (RBAC)
──────────────────────────────────────────────────────────────────────────────
Set $allowed_roles BEFORE including auth_check.php:

    <?php
    $allowed_roles = ['Admin', 'Vet'];
    require_once 'includes/auth_check.php';
    ?>

Valid roles:
  - 'Admin' (system administrator, creates users)
  - 'Vet' (veterinarian, manages health records)
  - 'Caretaker' (staff member, manages daily care)
  - 'Breeding Officer' (manages breeding records)
  - 'Env Tech' (environmental technician)
  - 'Collection Officer' (collects data)

If user's role NOT in $allowed_roles → shows 403 Access Denied error
If user's role IS in array → page loads normally


STEP 3: Access Session Variables
──────────────────────────────────────────────────────────────────────────────
After auth_check.php is included, use:

    echo "Logged in as: " . $_SESSION['role_name'];
    echo "User ID: " . $_SESSION['user_id'];


STEP 4: Create Login Link for Navigation
──────────────────────────────────────────────────────────────────────────────
Add link to your header/navbar:

    <a href="logout.php" class="btn btn-danger">Logout</a>

Or create a user menu that shows:

    <?php if (isset($_SESSION['user_id'])): ?>
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['role_name']); ?></span>
        <a href="logout.php">Logout</a>
    <?php else: ?>
        <a href="login.php">Login</a>
    <?php endif; ?>


================================================================================
SECURITY FEATURES IMPLEMENTED
================================================================================

1. PASSWORD SECURITY
   ✓ Passwords hashed with password_hash() (bcrypt by default)
   ✓ password_verify() never reveals timing info about passwords
   ✓ Passwords NEVER stored in plain text
   ✓ No password reset links sent via email (admin only changes)

2. SQL INJECTION PREVENTION
   ✓ PDO prepared statements with NAMED PARAMETERS (:username, :password_hash)
   ✓ PDO::ATTR_EMULATE_PREPARES = false (uses native prepared statements)
   ✓ All user input bound via bindParam()

3. SESSION SECURITY
   ✓ session_regenerate_id(true) after successful login (prevents fixation)
   ✓ Session destroyed on logout (unsetting $_SESSION alone is insufficient)
   ✓ Session cookies set with httpOnly flag (prevents JS access)

4. AUTHENTICATION/AUTHORIZATION
   ✓ Field validation (username format, password length)
   ✓ No information leaks (invalid username/password show same error)
   ✓ Role-based access control (RBAC) enforced at page level
   ✓ Role verification via database query (not just stored in session)

5. LOGICAL SECURITY
   ✓ Admin role exclusively creates users (no public registration)
   ✓ Passwords changed only by Admins (secure password policy)
   ✓ Username uniqueness enforced (UNIQUE constraint in DB)
   ✓ Error messages sanitized with htmlspecialchars()


================================================================================
COMMON USAGE EXAMPLES
================================================================================

EXAMPLE 1: Admin-Only Dashboard
────────────────────────────────────────────────────────────────────────────
File: admin_dashboard.php

    <?php
    $allowed_roles = ['Admin'];
    require_once 'includes/auth_check.php';
    require_once 'includes/header.php';
    ?>
    
    <h1>Admin Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['role_name']); ?></p>
    <!-- Admin content here -->
    
    <?php require_once 'includes/footer.php'; ?>


EXAMPLE 2: Vet-Accessible Page
────────────────────────────────────────────────────────────────────────────
File: health_management.php

    <?php
    $allowed_roles = ['Admin', 'Vet'];
    require_once 'includes/auth_check.php';
    require_once 'includes/header.php';
    ?>
    
    <h1>Health Records Management</h1>
    <!-- Only Admins and Vets see this -->
    
    <?php require_once 'includes/footer.php'; ?>


EXAMPLE 3: Any Logged-In User Page
────────────────────────────────────────────────────────────────────────────
File: breeding_records.php

    <?php
    require_once 'includes/auth_check.php';
    require_once 'includes/header.php';
    ?>
    
    <h1>Breeding Records</h1>
    <p>User logged in: <?php echo $_SESSION['user_id']; ?></p>
    <!-- All authenticated users see this -->
    
    <?php require_once 'includes/footer.php'; ?>


================================================================================
INITIAL SETUP CHECKLIST
================================================================================

1. ✓ VERIFY DATABASE
   [ ] MySQL database 'tortoise_conservation_db' created
   [ ] USERS table exists with: user_id, role_id, username, password_hash
   [ ] ROLES table exists with: role_id, role_name (ENUM)
   [ ] Insert initial ROLES data:
       INSERT INTO ROLES (role_name) VALUES ('Admin'), ('Vet'), ('Caretaker'), 
                                             ('Breeding Officer'), ('Env Tech'), 
                                             ('Collection Officer');

2. ✓ CREATE FIRST ADMIN ACCOUNT (manual SQL)
   [ ] Insert via PHP admin_create_user.php (after creating an account)
   [ ] OR insert manually with:
       INSERT INTO USERS (role_id, username, password_hash) 
       VALUES (1, 'admin', '$2y$10$...');  -- password_hash('password', PASSWORD_DEFAULT)

3. ✓ DEPLOY FILES
   [ ] login.php → /Tortoise Management/login.php
   [ ] logout.php → /Tortoise Management/logout.php
   [ ] includes/auth_check.php → /Tortoise Management/includes/auth_check.php
   [ ] admin_create_user.php → /Tortoise Management/admin_create_user.php

4. ✓ VERIFY CONNECTION
   [ ] Ensure db_connect.php is in the root directory
   [ ] Test: Visit login.php and verify form displays

5. ✓ PROTECT EXISTING PAGES
   [ ] Add to top of sensitive pages:
       $allowed_roles = ['Admin'];  // or ['Admin', 'Vet'], etc
       require_once 'includes/auth_check.php';

6. ✓ ADD LOGOUT LINK
   [ ] Add to header.php or navigation:
       <a href="logout.php">Logout</a>


================================================================================
TROUBLESHOOTING
================================================================================

PROBLEM: "Cannot redeclare session_start()"
SOLUTION: Only call session_start() once at the top of a page.
          auth_check.php checks if session already started.

PROBLEM: Login always shows "Invalid username or password"
SOLUTION: 
  - Verify ROLES table has data
  - Check username exists in USERS table
  - Verify password_hash() matches stored hash (test manually)

PROBLEM: User can access admin_create_user.php even though they're not Admin
SOLUTION: 
  - Check $allowed_roles is set to ['Admin'] BEFORE auth_check.php
  - Verify user's role_name in database matches 'Admin'

PROBLEM: Session doesn't persist after login
SOLUTION:
  - Ensure session.save_path is writable (usually /tmp on Linux, %TEMP% on Windows)
  - Check for output before session_start() call

PROBLEM: Getting "Access Denied" but I should have access
SOLUTION:
  - Verify your role_name in USERS table matches exactly (case-sensitive)
  - Check $allowed_roles array spelling matches database (e.g., 'Admin' not 'ADMIN')

PROBLEM: Getting 403 error on every protected page
SOLUTION:
  - Verify login succeeded (check SESSION variables exist)
  - Use var_dump($_SESSION) in auth_check.php to debug


================================================================================
PASSWORD POLICY & USER MANAGEMENT
================================================================================

PASSWORD REQUIREMENTS:
  - Minimum 8 characters (enforced in admin_create_user.php form)
  - No complexity requirements in code (enforce via policy/training)
  - Hashed with password_hash() (currently bcrypt)

CREATING A NEW USER:
  1. Only Admin can access admin_create_user.php
  2. Form asks for: username, role, password (twice)
  3. System validates and hashes password
  4. User cannot register themselves

CHANGING USER PASSWORD:
  - Currently: Admin must reset password in database manually
  - To implement: Create admin_reset_password.php with auth_check.php
  - Always use password_hash() and password_verify()

DELETING A USER:
  - Admin deletes via direct database query (USERS table)
  - ON DELETE RESTRICT on ROLES prevents deleting used roles


================================================================================
EXTENDING THE SYSTEM
================================================================================

ADD A NEW ROLE:
  1. Insert into ROLES table:
     INSERT INTO ROLES (role_name) VALUES ('New Role');
  2. Use in $allowed_roles:
     $allowed_roles = ['Admin', 'New Role'];

ADD PASSWORD RESET FEATURE:
  1. Create password_reset.php (public)
  2. User enters email/username (check against USERS)
  3. Generate random token, store in database
  4. Send link to user email with token
  5. User clicks link → password_reset_form.php
  6. User enters new password
  7. Verify token, update password_hash, delete token

ADD LOGIN AUDIT LOG:
  1. Create LOGIN_LOG table with: log_id, user_id, login_time, ip_address, success
  2. Add to login.php after successful auth:
     INSERT INTO LOGIN_LOG (user_id, login_time, ip_address, success)
     VALUES (:user_id, NOW(), :ip, 1)
  3. Also log failed attempts (username, time, ip, success=0)

ADD ACCOUNT LOCKED FEATURE (after failed attempts):
  1. Add 'account_locked' column to USERS table
  2. Increment failed_login_attempts counter
  3. After 5 failed attempts, lock account
  4. Admin must unlock via admin dashboard


================================================================================
SECURITY CHECKLIST FOR PRODUCTION
================================================================================

Before deploying to production:
  [ ] Change db_connect.php credentials (not 'root' with blank password)
  [ ] Enable HTTPS/SSL certificate
  [ ] Set session.secure and session.httponly in php.ini
  [ ] Set session.samesite = 'Strict' or 'Lax'
  [ ] Enable database query logging for security
  [ ] Implement rate limiting on login.php (prevent brute force)
  [ ] Add email notifications for failed login attempts
  [ ] Implement IP whitelisting (optional, for internal systems)
  [ ] Enable 2FA (two-factor authentication) for Admin role
  [ ] Encrypt sensitive database columns (passwords already hashed)
  [ ] Regular security audits of SQL queries
  [ ] Monitor for suspicious login patterns

================================================================================
CONTACT & SUPPORT
================================================================================

Created by: Senior PHP Security Architect
Date: April 8, 2026
Version: 1.0

For security questions, contact your system administrator.

================================================================================
