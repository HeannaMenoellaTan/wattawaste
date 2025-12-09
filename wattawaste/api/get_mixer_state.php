<?php
header('Content-Type: application/json');
require_once '../db.php';

$mixer = $pdo->query("SELECT desired_state FROM mixer_control WHERE id = 1")->fetchColumn();
echo json_encode(['state' => $mixer]);
?>
