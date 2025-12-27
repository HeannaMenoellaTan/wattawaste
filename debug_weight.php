<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'firebase_config.php';

echo "<h2>Debug Weight Data</h2>";

try {
    $database = getDatabase();
    
    // Test 1: Check if Weight node exists (capital W)
    echo "<h3>Test 1: Check Weight Node</h3>";
    $weightRef = $database->getReference('sensors/Weight');
    $weightSnapshot = $weightRef->getSnapshot();
    
    echo "Weight node exists: " . ($weightSnapshot->exists() ? "YES" : "NO") . "<br>";
    
    if ($weightSnapshot->exists()) {
        echo "<pre>";
        print_r($weightSnapshot->getValue());
        echo "</pre>";
    }
    
    // Test 2: Get Latest
    echo "<h3>Test 2: Get Latest Data</h3>";
    $latestRef = $database->getReference('sensors/Weight/latest');
    $latestSnapshot = $latestRef->getSnapshot();
    
    echo "Latest exists: " . ($latestSnapshot->exists() ? "YES" : "NO") . "<br>";
    
    if ($latestSnapshot->exists()) {
        $latestData = $latestSnapshot->getValue();
        echo "<pre>";
        print_r($latestData);
        echo "</pre>";
        
        $weight = $latestData['value'] ?? 'NOT FOUND';
        $capacity = $latestData['capacity'] ?? 'NOT FOUND';
        
        echo "Weight value: $weight<br>";
        echo "Capacity value: $capacity<br>";
    }
    
    // Test 3: Get History
    echo "<h3>Test 3: Get History Data</h3>";
    $historyRef = $database->getReference('sensors/Weight/history');
    $historySnapshot = $historyRef->getSnapshot();
    
    echo "History exists: " . ($historySnapshot->exists() ? "YES" : "NO") . "<br>";
    
    if ($historySnapshot->exists()) {
        $historyData = $historySnapshot->getValue();
        echo "Number of history entries: " . count($historyData) . "<br>";
        echo "<pre>";
        print_r($historyData);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>