<?php
header("Content-Type: application/json");
$conn = new mysqli("127.0.0.1", "root", "", "wattawaste_system");

if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}

// Get latest temperature, humidity, gas, ph
$temp = $conn->query("SELECT Temp_Ave FROM temperatures ORDER BY Temp_Id DESC LIMIT 1")->fetch_assoc()['Temp_Ave'] ?? 0;
$humidity = $conn->query("SELECT Humid_Lvl FROM humidity ORDER BY Humid_Id DESC LIMIT 1")->fetch_assoc()['Humid_Lvl'] ?? 0;
$gas = $conn->query("SELECT Gas_Lvl FROM gas ORDER BY Gas_Id DESC LIMIT 1")->fetch_assoc()['Gas_Lvl'] ?? 0;
$ph = $conn->query("SELECT pH_Value FROM ph ORDER BY pH_Id DESC LIMIT 1")->fetch_assoc()['pH_Value'] ?? 0;

echo json_encode([
    "latest" => [
        "temperature" => $temp,
        "humidity"    => $humidity,
        "gas"         => $gas,
        "ph"          => $ph
    ],
    "mixer" => 0
]);

$conn->close();
?>
