<?php
// notif_fetch.php
session_start();
include('db.php'); // use your existing $conn

$query = "SELECT Rep_Id AS id, Query AS message_text, date_created AS created_at 
          FROM reports 
          ORDER BY date_created DESC 
          LIMIT 20";
$result = $conn->query($query);

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

header('Content-Type: application/json');
echo json_encode($messages);
exit;
