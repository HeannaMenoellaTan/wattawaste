<?php
require_once '../firebase_config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $database = getDatabase();
    
    // Get latest sensor values from Firebase
    $temperature = $database->getReference('sensors/temperature/latest/value')->getValue() ?? 0;
    $humidity = $database->getReference('sensors/humidity/latest/value')->getValue() ?? 0;
    $gas = $database->getReference('sensors/gas/latest/value')->getValue() ?? 0;
    $ph = $database->getReference('sensors/ph/latest/value')->getValue() ?? 0;
    
    $response = [
        'success' => true,
        'latest' => [
            'temperature' => $temperature,
            'humidity' => $humidity,
            'gas' => $gas,
            'ph' => $ph
        ],
        'timestamp' => time()
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Get Latest Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving latest sensor data',
        'error' => $e->getMessage()
    ]);
}
?>