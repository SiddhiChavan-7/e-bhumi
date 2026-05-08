<?php
require_once 'config.php';
$sql = "CREATE TABLE IF NOT EXISTS purchase_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    buyer_id INT NOT NULL,
    status ENUM('Pending', 'Accepted', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES property_listings(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES citizens(id) ON DELETE CASCADE
);";
try {
    $pdo->exec($sql);
    echo "Purchase requests table added.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
