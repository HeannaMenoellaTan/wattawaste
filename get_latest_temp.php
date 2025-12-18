<?php
header('Content-Type: application/json');

try {
    require_once 'firebase_config.php';
    $database = getDatabase();
    
    // Get the latest temperature reading from Firebase
    $tempRef = $database->getReference('sensors/temperature/latest');
    $snapshot = $tempRef->getSnapshot();
    
    if ($snapshot->exists()) {
        $data = $snapshot->getValue();
        
        // Return the data in a format similar to MySQL response
        echo json_encode($data);
    } else {
        // No data found
        echo json_encode([
            "value" => null,
            "timestamp" => null,
            "message" => "No temperature data available"
        ]);
    }
    
} catch (Exception $e) {
    error_log("Get latest temp error: " . $e->getMessage());
    echo json_encode([
        "error" => "Failed to fetch temperature data",
        "details" => $e->getMessage()
    ]);
}
?>