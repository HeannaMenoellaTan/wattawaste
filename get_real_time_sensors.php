<?php
require_once '../firebase_config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $database = getDatabase();
    
    // Get current sensor readings from Firebase
    $currentSensors = $database->getReference('sensors/current')->getValue();
    
    if ($currentSensors) {
        $response = [
            'success' => true,
            'data' => $currentSensors,
            'timestamp' => time()
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'No sensor data available',
            'data' => []
        ];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Real-time Sensor Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving sensor data',
        'error' => $e->getMessage()
    ]);
}
?>