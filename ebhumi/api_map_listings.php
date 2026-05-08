<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT id, survey_number, village, total_area, area_type, asking_price, image_path, latitude, longitude 
        FROM property_listings 
        WHERE status = 'Available' AND latitude IS NOT NULL AND longitude IS NOT NULL
    ");
    $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $listings]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
