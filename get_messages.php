<?php
$conn = new mysqli("localhost", "root", "", "wattawaste_system");
if ($conn->connect_error) {
  die(json_encode(["error" => "DB Connection failed."]));
}

$sort = isset($_GET['sort']) && $_GET['sort'] === 'asc' ? 'ASC' : 'DESC';

$query = "SELECT Query, Summary, date_created 
          FROM reports 
          ORDER BY date_created $sort";
$result = $conn->query($query);

$messages = [];
while ($row = $result->fetch_assoc()) {
  $messages[] = [
    "activity" => "Report Update",
    "message_text" => $row['Summary'],   // ✅ FIXED — plain text, no HTML
    "created_at" => $row['date_created']
  ];
}

echo json_encode($messages);
$conn->close();
?>
