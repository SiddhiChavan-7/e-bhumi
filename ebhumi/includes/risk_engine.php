<?php
/**
 * e-BhumiAbhilekhan - Trust Score Engine
 */

function calculateTrustScore($property_id, $pdo) {
    $score = 10; // Start with a perfect score out of 10
    $reasons = []; // Explain why the score dropped

    // 1. Check if we have 7/12 data
    $stmt1 = $pdo->prepare("SELECT * FROM seven_twelve_records WHERE property_id = :id");
    $stmt1->execute([':id' => $property_id]);
    $seven_twelve = $stmt1->fetch(PDO::FETCH_ASSOC);

    if (!$seven_twelve) {
        $score -= 3;
        $reasons[] = "Missing official 7/12 record data (-3 pts).";
    }

    // 2. Check for active encumbrances
    $stmt2 = $pdo->prepare("SELECT * FROM encumbrances WHERE property_id = :id AND status = 'Active'");
    $stmt2->execute([':id' => $property_id]);
    $encumbrances = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $high_risk_found = false;

    foreach ($encumbrances as $enc) {
        if ($enc['severity_level'] === 'High') {
            $score -= 4;
            $reasons[] = "High Risk Active Encumbrance: " . $enc['encumbrance_type'] . " (-4 pts).";
            $high_risk_found = true;
        } elseif ($enc['severity_level'] === 'Medium') {
            $score -= 2;
            $reasons[] = "Medium Risk Active Encumbrance: " . $enc['encumbrance_type'] . " (-2 pts).";
        } elseif ($enc['severity_level'] === 'Low') {
            $score -= 1;
            $reasons[] = "Low Risk Active Encumbrance: " . $enc['encumbrance_type'] . " (-1 pt).";
        }
    }

    // 3. Prevent score from going below 0
    if ($score < 0) {
        $score = 0;
    }

    // Determine Status Badge
    $status = "Safe to Buy";
    $css_class = "high"; // green
    if ($score <= 4 || $high_risk_found) {
        $status = "High Risk / Dispute";
        $css_class = "low"; // red
    } elseif ($score <= 7) {
        $status = "Proceed with Caution";
        $css_class = "mid"; // yellow
    }

    return [
        'score' => $score,
        'max_score' => 10,
        'status' => $status,
        'css_class' => $css_class,
        'reasons' => $reasons,
        'seven_twelve' => $seven_twelve,
        'encumbrances' => $encumbrances
    ];
}
?>
