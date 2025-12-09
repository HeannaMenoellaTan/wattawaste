<?php
$conn = new mysqli("localhost", "root", "", "wattawaste_system");
if ($conn->connect_error) die("DB error");

$message = $_POST['message'] ?? '';
if (!empty($message)) {
  $stmt = $conn->prepare("INSERT INTO reports (Summary) VALUES (?)");
  $stmt->bind_param("s", $message);
  $stmt->execute();
  $stmt->close();
}
$conn->close();
?>