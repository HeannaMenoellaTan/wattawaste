<?php
require_once 'firebase_config.php';

echo "Testing Firebase Connection...\n\n";

try {
    $database = getDatabase();
    
    // Test 1: Write data
    echo "1. Writing test data...\n";
    $database->getReference('test/connection')->set([
        'status' => 'connected',
        'message' => 'WATTAWASTE Firebase is working!',
        'timestamp' => time(),
        'date' => date('Y-m-d H:i:s')
    ]);
    echo "   ✓ Data written successfully\n\n";
    
    // Test 2: Read data
    echo "2. Reading test data...\n";
    $data = $database->getReference('test/connection')->getValue();
    echo "   ✓ Data retrieved:\n";
    echo "   - Status: " . $data['status'] . "\n";
    echo "   - Message: " . $data['message'] . "\n";
    echo "   - Date: " . $data['date'] . "\n\n";
    
    echo "========================================\n";
    echo "✓ Firebase is connected and working!\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
```

## Step 3: Your File Structure Should Look Like:
```
wattawaste/
├── vendor/                    (created by Composer)
├── firebase-credentials.json  (download this from Firebase)
├── firebase_config.php        (create this - code above)
├── test_firebase.php          (create this - code above)
├── composer.json
├── api/
├── account.php
└── ... (your other files)
