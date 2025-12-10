<?php
require_once '../firebase_config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $database = getDatabase();
    
    // 1. Get CURRENT sensor readings (latest values)
    // This is what you see on your main dashboard
    $current = $database->getReference('sensors/current')->getValue();
    
    // 2. Get HISTORICAL data (last 20 readings for charts/graphs)
    // OrderByChild sorts by timestamp, limitToLast gets the most recent
    $history = $database->getReference('sensors/history/sensor_1')
        ->orderByChild('timestamp')
        ->limitToLast(20)  // Get last 20 readings
        ->getValue();
    
    // 3. Get fertilizer application logs
    $fertilizer = $database->getReference('fertilizer_logs')
        ->orderByChild('timestamp')
        ->limitToLast(10)  // Get last 10 logs
        ->getValue();
    
    // 4. Calculate statistics (optional)
    $stats = [];
    if ($history) {
        $temps = array_column($history, 'temperature');
        $stats = [
            'avg_temperature' => round(array_sum($temps) / count($temps), 2),
            'min_temperature' => min($temps),
            'max_temperature' => max($temps),
            'total_readings' => count($history)
        ];
    }
    
    // 5. Return everything in one response
    $response = [
        'success' => true,
        'data' => [
            'current_sensors' => $current ?: [],      // Latest sensor values
            'sensor_history' => $history ?: [],       // Historical data for charts
            'fertilizer_logs' => $fertilizer ?: [],   // Fertilizer application logs
            'statistics' => $stats,                    // Calculated stats
            'last_updated' => date('Y-m-d H:i:s')     // When data was fetched
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // If there's an error, return error message
    error_log("Dashboard Data Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving dashboard data',
        'error' => $e->getMessage()
    ]);
}
?>