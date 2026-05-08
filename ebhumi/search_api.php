<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'includes/risk_engine.php';

$survey_number = trim($_GET['survey_number'] ?? '');
$village = trim($_GET['village'] ?? '');
$taluka = trim($_GET['taluka'] ?? '');
$owner_name = trim($_GET['owner_name'] ?? '');
$district = trim($_GET['district'] ?? '');

if (empty($survey_number) && empty($village) && empty($taluka) && empty($owner_name)) {
    echo json_encode(['error' => 'Please provide search criteria.']);
    exit;
}

try {
    $property = null;

    if (!empty($survey_number)) {
        $sql = "SELECT * FROM properties WHERE survey_number = :survey";
        $params = [':survey' => $survey_number];
        if (!empty($district)) {
            $sql .= " AND district = :district";
            $params[':district'] = $district;
        }
        $sql .= " LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $property = $stmt->fetch(PDO::FETCH_ASSOC);
    } 
    elseif (!empty($village) || !empty($taluka)) {
        $sql = "SELECT * FROM properties WHERE 1=1";
        $params = [];
        if (!empty($village)) {
            $sql .= " AND village LIKE :village";
            $params[':village'] = "%$village%";
        }
        if (!empty($taluka)) {
            $sql .= " AND taluka LIKE :taluka";
            $params[':taluka'] = "%$taluka%";
        }
        $sql .= " LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $property = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    elseif (!empty($owner_name)) {
        $sql = "SELECT p.* FROM properties p 
                JOIN seven_twelve_records s ON p.id = s.property_id 
                WHERE s.owner_name LIKE :owner 
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':owner' => "%$owner_name%"]);
        $property = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$property) {
        echo json_encode(['error' => 'Property not found in records.']);
        exit;
    }

    // Calculate the Trust Score dynamically!
    $risk_data = calculateTrustScore($property['id'], $pdo);

    // Prepare JSON response
    echo json_encode([
        'success' => true,
        'property' => $property,
        'risk' => $risk_data
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
