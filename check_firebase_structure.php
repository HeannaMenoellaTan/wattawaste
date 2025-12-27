<?php
require_once 'firebase_config.php';

try {
    $database = getDatabase();
    
    echo "<h2>Checking Firebase Structure</h2>";
    
    // Check root sensors
    echo "<h3>1. Checking 'sensors' node:</h3>";
    $sensorsRef = $database->getReference('sensors');
    $sensorsSnapshot = $sensorsRef->getSnapshot();
    
    if ($sensorsSnapshot->exists()) {
        echo "<p>✅ 'sensors' exists</p>";
        echo "<pre>";
        print_r($sensorsSnapshot->getValue());
        echo "</pre>";
    } else {
        echo "<p>❌ 'sensors' does NOT exist</p>";
    }
    
    // Check if weight exists
    echo "<h3>2. Checking 'sensors/weight' node:</h3>";
    $weightRef = $database->getReference('sensors/weight');
    $weightSnapshot = $weightRef->getSnapshot();
    
    if ($weightSnapshot->exists()) {
        echo "<p>✅ 'sensors/weight' exists</p>";
        echo "<pre>";
        print_r($weightSnapshot->getValue());
        echo "</pre>";
    } else {
        echo "<p>❌ 'sensors/weight' does NOT exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>ERROR: " . $e->getMessage() . "</p>";
}
?>