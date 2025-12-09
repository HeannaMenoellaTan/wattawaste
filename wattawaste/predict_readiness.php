<?php
header("Content-Type: application/json");

$python = 'C:\\Users\\marlyn\\AppData\\Local\\Programs\\Python\\Python313\\python.exe';
$base = __DIR__;
$mlDir = $base . DIRECTORY_SEPARATOR . 'ml_model';
$script = $mlDir . DIRECTORY_SEPARATOR . 'predict_readiness.py';

if (!file_exists($script)) {
  echo json_encode(["readiness" => null, "error" => "predict_readiness.py not found"]);
  exit;
}

chdir($mlDir);
$cmd = "\"$python\" \"$script\" 2>&1";
$output = shell_exec($cmd);
$json = json_decode($output, true);

if (json_last_error() === JSON_ERROR_NONE && isset($json["readiness"])) {
  echo json_encode($json);
} else {
  echo json_encode([
    "readiness" => null,
    "error" => "Python error/output",
    "details" => $output
  ]);
}
?>
