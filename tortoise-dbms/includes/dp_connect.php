<?php
/**
 * Database Connection Script
 * Environment: XAMPP (Localhost)
 * Database: tortoise_conservation_db
 * Methodology: PDO (PHP Data Objects)
 */

$host = 'localhost';
$dbname = 'tortoise_conservation_db';
$username = 'root';
$password = ''; 
$charset = 'utf8mb4'; // Ensures full Unicode support

// Construct the Data Source Name (DSN)
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// Rigorously configure PDO attributes
$options = [
    // Throw exceptions on errors so they can be caught and handled gracefully
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    
    // Return rows as associative arrays by default (e.g., $row['tortoise_id'])
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    
    // Disable emulation to rely on the native prepared statements of the MySQL engine (Max Security)
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Instantiate the PDO connection
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // TEST MECHANISM: Uncomment the line below to verify your bridge works, 
    // then re-comment it before including this file in your HTML views.
    // echo "Connected successfully";

} catch (PDOException $e) {
    // Catch connection errors and display a sanitized, generic error message.
    // NOTE: In a real production environment, you would log $e->getMessage() to a secure server file.
    
    error_log("Connection Error: " . $e->getMessage()); // Logs to XAMPP's php_error_log
    die("Database connection failed. Please check your system configuration or ensure the MySQL server is running.");
}

// EOF: Intentionally omitting the closing PHP tag to prevent whitespace injection.