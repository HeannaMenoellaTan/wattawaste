<?php
header("Content-Type: application/json");

require_once 'firebase_config.php';

date_default_timezone_set('Asia/Manila');

// ==== SENSOR STATUS CHECK FUNCTION ====
function getSensorStatus($timestamp) {
    if (!$timestamp || $timestamp <= 0) {
        return ["Faulty", "faulty", "No data"];
    }
    
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff <= 10) {
        return ["Online", "online", $diff . "s ago"];
    } 
    if ($diff <= 30) {
        return ["Delayed", "delayed", $diff . "s ago"];
    } 
    if ($diff <= 120) {
        return ["Offline", "offline", $diff . "s ago"];
    }
    return ["Faulty", "faulty", $diff . "s ago"];
}

// ==== FETCH SENSOR DATA FROM FIREBASE ====
function fetchSensorData($database, $sensorType, $displayName) {
    try {
        $latestRef = $database->getReference("sensors/$sensorType/latest");
        $snapshot = $latestRef->getSnapshot();
        
        if (!$snapshot->exists()) {
            return [
                "name" => $displayName,
                "status" => "Faulty",
                "class" => "faulty",
                "lastUpdate" => "No data"
            ];
        }
        
        $data = $snapshot->getValue();
        $timestamp = $data['timestamp'] ?? null;
        
        list($status, $class, $timeAgo) = getSensorStatus($timestamp);
        
        return [
            "name" => $displayName,
            "status" => $status,
            "class" => $class,
            "lastUpdate" => $timeAgo
        ];
        
    } catch (Exception $e) {
        error_log("Sensor fetch error for $sensorType: " . $e->getMessage());
        return [
            "name" => $displayName,
            "status" => "Faulty",
            "class" => "faulty",
            "lastUpdate" => "Error"
        ];
    }
}

try {
    $database = getDatabase();
    
    // ==== FETCH ALL SENSORS ====
    $output = [
        fetchSensorData($database, 'temperature', 'Temperature'),
        fetchSensorData($database, 'humidity', 'Humidity'),
        fetchSensorData($database, 'gas', 'Gas'),
        fetchSensorData($database, 'ph', 'pH')
    ];
    
    echo json_encode($output);
    
} catch (Exception $e) {
    error_log("Sensor status error: " . $e->getMessage());
    echo json_encode([
        "error" => "Failed to fetch sensor status",
        "message" => $e->getMessage()
    ]);
}
exit;
?>