<?php
$conn = new mysqli("localhost", "root", "", "wattawaste_system");
if ($conn->connect_error) { die(json_encode(["error" => "DB connection failed"])); }

$result = $conn->query("SELECT * FROM temperatures ORDER BY Temp_Id DESC LIMIT 1");
echo json_encode($result->fetch_assoc());
$conn->close();
?>
