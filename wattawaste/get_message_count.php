<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "wattawaste_system");

if ($conn->connect_error) {
  echo json_encode(['count' => 0]);
  exit;
}

$result = $conn->query("SELECT COUNT(*) AS count FROM reports");
$count = $result->fetch_assoc()['count'] ?? 0;

echo json_encode(['count' => (int)$count]);
$conn->close();
?>
