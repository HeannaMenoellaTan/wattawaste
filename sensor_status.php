<?php
header("Content-Type: application/json");

// ==== DATABASE CONNECTION ====
$conn = new mysqli("localhost", "root", "", "wattawaste_system");
if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

date_default_timezone_set('Asia/Manila');

// ==== SENSOR STATUS CHECK FUNCTION ====
function getSensorStatus($created_at) {
    if (!$created_at || $created_at == "0000-00-00 00:00:00") {
        return ["Faulty", "faulty", "No data"];
    }

    $last = strtotime($created_at);
    $now = time();
    $diff = $now - $last;

    if ($diff <= 10) {
        return ["Online", "online", $diff . "s ago"];
    } 
    if ($diff <= 30) {
        return ["Delayed", "delayed", $diff . "s ago"];
    } 
    if ($diff <= 120) {
        return ["Offline", "offline", $diff . "s ago"];
    }
    return ["Faulty", "faulty", $diff . "s ago"];
}

// ==== SENSOR QUERIES ====
$sensors = [
    "Temperature" => "SELECT Created_At AS created_at FROM temperatures ORDER BY Temp_Id DESC LIMIT 1",
    "Humidity"    => "SELECT Created_At AS created_at FROM humidity ORDER BY Humid_Id DESC LIMIT 1",
    "Gas"         => "SELECT Created_At AS created_at FROM gas ORDER BY Gas_Id DESC LIMIT 1",
    "pH"          => "SELECT Created_At AS created_at FROM ph ORDER BY pH_Id DESC LIMIT 1"
];

$output = [];

foreach ($sensors as $name => $query) {
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        list($status, $class, $timeAgo) = getSensorStatus($row["created_at"]);
    } else {
        $status = "Faulty";
        $class = "faulty";
        $timeAgo = "No data";
    }

    $output[] = [
        "name" => $name,
        "status" => $status,
        "class" => $class,
        "lastUpdate" => $timeAgo
    ];
}

echo json_encode($output);
exit;
?>
