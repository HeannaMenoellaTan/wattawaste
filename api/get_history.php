<?php
header('Content-Type: application/json');
require_once '../db.php';

$stmt = $pdo->query("SELECT * FROM sensor_readings ORDER BY timestamp DESC LIMIT 50");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
