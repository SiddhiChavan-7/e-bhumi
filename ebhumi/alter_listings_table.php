<?php
require_once 'config.php';
$sql = "ALTER TABLE property_listings 
        ADD COLUMN image_path VARCHAR(255) AFTER layout_pdf_path,
        ADD COLUMN latitude DECIMAL(10, 8) AFTER image_path,
        ADD COLUMN longitude DECIMAL(11, 8) AFTER latitude;";
try {
    $pdo->exec($sql);
    echo "Columns added to property_listings.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
