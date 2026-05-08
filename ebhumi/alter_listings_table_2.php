<?php
require_once 'config.php';
$sql = "ALTER TABLE property_listings 
        ADD COLUMN address_details TEXT AFTER village,
        ADD COLUMN total_area VARCHAR(100) AFTER address_details;";
try {
    $pdo->exec($sql);
    echo "Columns added to property_listings.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
