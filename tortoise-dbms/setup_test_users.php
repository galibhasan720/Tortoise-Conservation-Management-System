<?php
/**
 * Setup Test Users - Creates test accounts for all roles
 * This script creates user accounts with simple passwords for testing
 * Run this once, then delete or disable it for production
 */

require_once 'db_connect.php';

// Define test users with simple passwords
$test_users = [
    ['username' => 'admin', 'password' => 'Admin123', 'role_name' => 'Admin'],
    ['username' => 'vet', 'password' => 'Vet123', 'role_name' => 'Vet'],
    ['username' => 'caretaker', 'password' => 'Caretaker123', 'role_name' => 'Caretaker'],
    ['username' => 'breeding', 'password' => 'Breeding123', 'role_name' => 'Breeding Officer'],
    ['username' => 'envtech', 'password' => 'EnvTech123', 'role_name' => 'Env Tech'],
    ['username' => 'collector', 'password' => 'Collector123', 'role_name' => 'Collection Officer'],
];

try {
    // Get role IDs
    $role_stmt = $pdo->query('SELECT role_id, role_name FROM ROLES');
    $roles = [];
    foreach ($role_stmt->fetchAll(PDO::FETCH_ASSOC) as $role) {
        $roles[$role['role_name']] = $role['role_id'];
    }

    $created_count = 0;
    $skipped_count = 0;

    foreach ($test_users as $user) {
        // Check if user already exists
        $check_stmt = $pdo->prepare('SELECT user_id FROM USERS WHERE username = :username');
        $check_stmt->bindParam(':username', $user['username']);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            echo "✓ User '{$user['username']}' already exists - skipped\n";
            $skipped_count++;
            continue;
        }

        // Hash the password
        $password_hash = password_hash($user['password'], PASSWORD_DEFAULT);

        // Insert the user
        $insert_stmt = $pdo->prepare('
            INSERT INTO USERS (role_id, username, password_hash)
            VALUES (:role_id, :username, :password_hash)
        ');
        
        $insert_stmt->bindParam(':role_id', $roles[$user['role_name']], PDO::PARAM_INT);
        $insert_stmt->bindParam(':username', $user['username'], PDO::PARAM_STR);
        $insert_stmt->bindParam(':password_hash', $password_hash, PDO::PARAM_STR);
        
        $insert_stmt->execute();
        echo "✓ Created user '{$user['username']}' ({$user['role_name']})\n";
        $created_count++;
    }

    echo "\n========================================\n";
    echo "Setup Complete!\n";
    echo "========================================\n";
    echo "Created: $created_count users\n";
    echo "Skipped: $skipped_count users (already existed)\n";
    echo "\nCreated Accounts:\n";
    echo "========================================\n";
    
    foreach ($test_users as $user) {
        printf("%-20s | %-18s\n", $user['username'], $user['role_name']);
    }
    echo "========================================\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
