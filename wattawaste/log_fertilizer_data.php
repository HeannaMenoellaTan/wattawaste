<?php
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) exit;

$file = 'fertilizer_log.csv';
$fp = fopen($file, 'a');
if (filesize($file) == 0) {
  fputcsv($fp, ['timestamp', 'readiness', 'predicted', 'actual']);
}
fputcsv($fp, [$data['timestamp'], $data['readiness'], $data['predicted'], $data['actual']]);
fclose($fp);
?>
