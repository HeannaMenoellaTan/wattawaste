<?php
include("db.php");

$data = json_decode(file_get_contents('php://input'), true);

if(!$data) {
    echo "No JSON received";
    exit();
}

// Insert each value into its respective table
$now = date("Y-m-d H:i:s");

// Temperature
if(isset($data['temperature'])){
    mysqli_query($conn, "INSERT INTO temperatures (Temp_Ave, Temp_Sensor, created_at) VALUES ('{$data['temperature']}', 'Sensor 1', '$now')");
}

// Humidity
if(isset($data['humidity'])){
    mysqli_query($conn, "INSERT INTO humid (Humid_Lvl, Humid_Status, created_at) VALUES ('{$data['humidity']}', '{$data['status']['humidity']}', '$now')");
}

// PH
if(isset($data['ph'])){
    mysqli_query($conn, "INSERT INTO ph (pH_Value, created_at) VALUES ('{$data['ph']}', '$now')");
}

// Gas
if(isset($data['gas_level'])){
    mysqli_query($conn, "INSERT INTO gas (Gas_Lvl, Gas_Status, created_at) VALUES ('{$data['gas_level']}', '{$data['status']['gas']}', '$now')");
}

// Weight
if(isset($data['weight'])){
    mysqli_query($conn, "INSERT INTO weights (Weight_Lvl, created_at) VALUES ('{$data['weight']}', '$now')");
}

echo "Saved Successfully";
?>
