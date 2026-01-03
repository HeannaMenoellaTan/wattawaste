<?php
header("Content-Type: application/json");

require_once 'firebase_config.php';

try {
    $database = getDatabase();
    
    $temperature = floatval($database->getReference("sensors/temperature/latest/value")->getValue() ?? 0);
    $humidity = floatval($database->getReference("sensors/humidity/latest/value")->getValue() ?? 0);
    $gas = floatval($database->getReference("sensors/gas/latest/value")->getValue() ?? 0);
    $ph = floatval($database->getReference("sensors/ph/latest/value")->getValue() ?? 0);
    
    $score = 0;
    
    // Temperature (25 points)
    if ($temperature >= 45 && $temperature <= 70) {
        $score += 25;
    } elseif ($temperature >= 20 && $temperature < 45) {
        $score += 15;
    }
    
    // Humidity (25 points)
    if ($humidity >= 61 && $humidity <= 69) {
        $score += 25;
    } elseif ($humidity >= 40 && $humidity <= 80) {
        $score += 15;
    }
    
    // pH (25 points)
    if ($ph >= 6.5 && $ph <= 8.0) {
        $score += 25;
    } elseif ($ph >= 6.0 && $ph <= 8.5) {
        $score += 15;
    }
    
    // Gas (25 points)
    if ($gas < 300) {
        $score += 25;
    } elseif ($gas < 600) {
        $score += 15;
    }
    
    echo json_encode([
        "readiness" => min(max($score, 0), 100),
        "status" => "success"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "readiness" => 0,
        "error" => $e->getMessage()
    ]);
}
?>