<?php
/**
 * e-BhumiAbhilekhan - Database Configuration File
 * Make sure you have created the 'ebhumi' database and imported schema.sql
 */

// Database credentials
define('DB_SERVER', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); // Default XAMPP/WAMP password is empty
define('DB_NAME', 'ebhumi_db');

// Attempt to connect to MySQL database using PDO
try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Optional: Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("ERROR: Could not connect to the database. " . $e->getMessage());
}
?>
