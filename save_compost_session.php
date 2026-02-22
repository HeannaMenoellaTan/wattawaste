<?php
// Suppress ALL PHP warnings/notices so nothing prints before our JSON
error_reporting(0);
ini_set('display_errors', 0);

// Must be first output
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/firebase_config.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON body: ' . $body]);
        exit;
    }

    // Validate
    $required = ['temp', 'humidity', 'gas', 'ph', 'weightLoss', 'daysToReady', 'outcome'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            echo json_encode(['success' => false, 'error' => "Missing field: $field"]);
            exit;
        }
    }

    $session = [
        'temp'        => round((float)$data['temp'],       2),
        'humidity'    => round((float)$data['humidity'],   2),
        'gas'         => round((float)$data['gas'],        2),
        'ph'          => round((float)$data['ph'],         2),
        'weightLoss'  => round((float)$data['weightLoss'], 2),
        'daysToReady' => max(1, (int)$data['daysToReady']),
        'outcome'     => in_array($data['outcome'], ['success','partial','failed'])
                            ? $data['outcome'] : 'success',
        'readiness'   => round((float)($data['readiness'] ?? 0), 1),
        'savedAt'     => $data['savedAt'] ?? date('c'),
        'wasteType'   => 'garden',
    ];

    $db  = getDatabase();
    $key = (string)(int)(microtime(true) * 1000);
    $db->getReference("compost_sessions/{$key}")->set($session);

    echo json_encode(['success' => true, 'key' => $key]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}