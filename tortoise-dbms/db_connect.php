<?php
/**
 * Database Connection Script - Tortoise Conservation Management System
 * 
 * This script establishes a secure PDO connection to the MySQL database.
 * Uses prepared statements, exception handling, and best-practice PDO attributes.
 * 
 * @author Senior Backend Developer
 * @version 1.0
 * @date April 6, 2026
 */

// Database Configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'tortoise_conservation_db';
$db_charset = 'utf8mb4';

// Construct DSN (Data Source Name)
$dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";

try {
    // Create PDO connection instance
    $pdo = new PDO($dsn, $db_user, $db_pass);
    
    // Configure PDO Attributes for security and consistency
    
    // 1. Set error mode to throw exceptions for proper error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 2. Set default fetch mode to associative arrays
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // 3. Disable emulated prepared statements for strict type checking
    //    This ensures real prepared statements are used (prevents SQL injection)
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch (PDOException $e) {
    // Catch any connection errors and display safe error message
    // Never expose sensitive credentials like host, user, or password
    die('Database Connection Error: Unable to connect to the database. 
         Please check the database server and try again later.');
}

// Uncomment the line below to test the database connection:
// echo "Connected successfully";
