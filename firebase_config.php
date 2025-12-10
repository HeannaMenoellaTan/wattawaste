<?php
require __DIR__.'/vendor/autoload.php';
use Kreait\Firebase\Factory;

// Firebase configuration
define('FIREBASE_CREDENTIALS', __DIR__.'/firebase-credentials.json');
define('FIREBASE_DATABASE_URL', 'https://wattawaste-d3503-default-rtdb.asia-southeast1.firebasedatabase.app/');

/**
 * Get Realtime Database instance
 */
function getDatabase() {
    static $database = null;
    
    if ($database === null) {
        try {
            $factory = (new Factory)
                ->withServiceAccount(FIREBASE_CREDENTIALS)
                ->withDatabaseUri(FIREBASE_DATABASE_URL);
            
            $database = $factory->createDatabase();
        } catch (Exception $e) {
            error_log("Firebase initialization error: " . $e->getMessage());
            throw $e;
        }
    }
    
    return $database;
}
?>