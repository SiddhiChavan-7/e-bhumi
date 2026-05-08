<?php
require_once 'config.php';

$sql = "
CREATE TABLE IF NOT EXISTS citizens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    mobile_number VARCHAR(15) NOT NULL UNIQUE,
    aadhaar_number VARCHAR(12) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";

try {
    $pdo->exec($sql);
    echo "Database updated successfully. Citizens table created.";
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>
