<?php
require_once 'firebase_config.php';

try {
    $database = getDatabase();
    
    echo "<h2>All Sensors Nodes:</h2>";
    
    $sensorsRef = $database->getReference('sensors');
    $sensorsSnapshot = $sensorsRef->getSnapshot();
    
    if ($sensorsSnapshot->exists()) {
        $allSensors = $sensorsSnapshot->getValue();
        
        echo "<h3>Available sensor nodes:</h3>";
        echo "<ul>";
        foreach (array_keys($allSensors) as $sensorName) {
            echo "<li><strong>$sensorName</strong></li>";
        }
        echo "</ul>";
        
        echo "<h3>Full sensors structure:</h3>";
        echo "<pre>";
        print_r($allSensors);
        echo "</pre>";
    } else {
        echo "<p>No sensors node found!</p>";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>