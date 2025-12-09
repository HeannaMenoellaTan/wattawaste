<?php
// Just a fake response for now — simulating IoT device control
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);
if (!isset($input['state'])) {
  echo json_encode(["error" => "No state received"]);
  exit();
}

$state = $input['state'] == 1 ? "ON" : "OFF";

// pretend to control mixer (later this can update a database or send to Raspberry Pi)
echo json_encode(["message" => "Mixer turned $state"]);
?>
