<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'firebase_config.php';

echo "<h2>Testing Firebase Connection</h2>";

try {
    $database = getDatabase();
    echo "<p>✅ Firebase database connection successful!</p>";
    
    // Test latest
    echo "<h3>Testing Latest Data:</h3>";
    $latestRef = $database->getReference('sensors/weight/latest');
    echo "<p>Reference path: " . $latestRef->getPath() . "</p>";
    
    $latestSnapshot = $latestRef->getSnapshot();
    echo "<p>Snapshot exists: " . ($latestSnapshot->exists() ? 'YES' : 'NO') . "</p>";
    
    if ($latestSnapshot->exists()) {
        echo "<pre>";
        print_r($latestSnapshot->getValue());
        echo "</pre>";
    } else {
        echo "<p style='color:red;'>❌ No data found at sensors/weight/latest</p>";
    }
    
    // Test history
    echo "<h3>Testing History Data:</h3>";
    $historyRef = $database->getReference('sensors/weight/history');
    echo "<p>Reference path: " . $historyRef->getPath() . "</p>";
    
    $historySnapshot = $historyRef->getSnapshot();
    echo "<p>Snapshot exists: " . ($historySnapshot->exists() ? 'YES' : 'NO') . "</p>";
    
    if ($historySnapshot->exists()) {
        $historyData = $historySnapshot->getValue();
        echo "<p>Number of history entries: " . count($historyData) . "</p>";
        echo "<pre>";
        print_r($historyData);
        echo "</pre>";
    } else {
        echo "<p style='color:red;'>❌ No data found at sensors/weight/history</p>";
    }
    
    // Test database URL
    echo "<h3>Configuration Check:</h3>";
    echo "<p>Database URL: " . FIREBASE_DATABASE_URL . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>";
    echo $e->getTraceAsString();
    echo "</pre>";
}
?>