<?php
require_once 'config.php';
$sql = "CREATE TABLE IF NOT EXISTS property_listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    survey_number VARCHAR(50),
    village VARCHAR(100),
    area_type ENUM('Highway Touch', 'Roadside', 'Village Interior', 'Agricultural') NOT NULL,
    asking_price DECIMAL(15,2) NOT NULL,
    description TEXT,
    layout_pdf_path VARCHAR(255),
    status ENUM('Available', 'Sold') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES citizens(id) ON DELETE CASCADE
);";
try {
    $pdo->exec($sql);
    echo "Property listings table added.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
