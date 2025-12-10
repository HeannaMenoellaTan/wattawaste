<?php
include("db.php");
require_once '../firebase_config.php';

$data = json_decode(file_get_contents('php://input'), true);

if(!$data) {
    echo "No JSON received";
    exit();
}

// Current timestamp
$now = date("Y-m-d H:i:s");
$timestamp = time();

// Prepare Firebase data structure
$firebaseData = [
    'timestamp' => $timestamp,
    'date' => $now
];

// Temperature
if(isset($data['temperature'])){
    mysqli_query($conn, "INSERT INTO temperatures (Temp_Ave, Temp_Sensor, created_at) VALUES ('{$data['temperature']}', 'Sensor 1', '$now')");
    $firebaseData['temperature'] = floatval($data['temperature']);
}

// Humidity
if(isset($data['humidity'])){
    mysqli_query($conn, "INSERT INTO humid (Humid_Lvl, Humid_Status, created_at) VALUES ('{$data['humidity']}', '{$data['status']['humidity']}', '$now')");
    $firebaseData['humidity'] = floatval($data['humidity']);
    $firebaseData['humidity_status'] = $data['status']['humidity'];
}

// PH
if(isset($data['ph'])){
    mysqli_query($conn, "INSERT INTO ph (pH_Value, created_at) VALUES ('{$data['ph']}', '$now')");
    $firebaseData['ph'] = floatval($data['ph']);
}

// Gas
if(isset($data['gas_level'])){
    mysqli_query($conn, "INSERT INTO gas (Gas_Lvl, Gas_Status, created_at) VALUES ('{$data['gas_level']}', '{$data['status']['gas']}', '$now')");
    $firebaseData['gas_level'] = floatval($data['gas_level']);
    $firebaseData['gas_status'] = $data['status']['gas'];
}

// Weight
if(isset($data['weight'])){
    mysqli_query($conn, "INSERT INTO weights (Weight_Lvl, created_at) VALUES ('{$data['weight']}', '$now')");
    $firebaseData['weight'] = floatval($data['weight']);
}

// Save to Firebase
try {
    $database = getDatabase();
    
    // Save current sensor reading (overwrites previous)
    $database->getReference('sensors/current/sensor_1')->set($firebaseData);
    
    // Save to historical log (with auto-generated ID)
    $database->getReference('sensors/history/sensor_1')->push($firebaseData);
    
    echo "Saved Successfully (MySQL + Firebase)";
    
} catch (Exception $e) {
    error_log("Firebase Error: " . $e->getMessage());
    // Still return success if MySQL saved, Firebase is optional
    echo "Saved Successfully (MySQL only - Firebase error: " . $e->getMessage() . ")";
}
?>