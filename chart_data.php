<?php
/**
 * chart_data.php — History Graph API Endpoint
 *
 * Serves aggregated sensor history to your dashboard charts.
 * Reads from Firebase aggregated/ nodes (cheap — NOT raw history).
 *
 * USAGE (fetch from your index.php JS):
 *   fetch('chart_data.php?sensor=temperature&range=24h')
 *   fetch('chart_data.php?sensor=ph&range=7d')
 *
 * PARAMS:
 *   sensor  — temperature | humidity | gas | ph | weight
 *   range   — 1h | 6h | 24h (default) | 7d | 30d
 *   format  — json (default)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // tighten in production if needed
header('Cache-Control: public, max-age=60'); // cache for 1 minute

require_once __DIR__ . '/firebase_config.php';

// ── Input validation ──────────────────────────────────────────────────────────
$VALID_SENSORS = ['temperature', 'humidity', 'gas', 'ph', 'weight'];
$VALID_RANGES  = [
    '1h'  =>  1 * 60 * 60 * 1000,
    '6h'  =>  6 * 60 * 60 * 1000,
    '24h' => 24 * 60 * 60 * 1000,
    '7d'  =>  7 * 24 * 60 * 60 * 1000,
    '30d' => 30 * 24 * 60 * 60 * 1000,
];

$sensor = $_GET['sensor'] ?? 'temperature';
$range  = $_GET['range']  ?? '24h';

if (!in_array($sensor, $VALID_SENSORS, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid sensor. Choose: ' . implode(', ', $VALID_SENSORS)]);
    exit;
}

if (!isset($VALID_RANGES[$range])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid range. Choose: ' . implode(', ', array_keys($VALID_RANGES))]);
    exit;
}

// ── Fetch from Firebase ───────────────────────────────────────────────────────
try {
    $db = getDatabase();

    $now       = (int) (microtime(true) * 1000);
    $startTime = $now - $VALID_RANGES[$range];

    $snapshot = $db->getReference("sensors/{$sensor}/aggregated")
        ->orderByChild('timestamp')
        ->startAt($startTime)
        ->endAt($now)
        ->getValue();

    // Build chart-friendly arrays
    $labels  = []; // Human-readable timestamps
    $avgData = [];
    $minData = [];
    $maxData = [];

    if (!empty($snapshot)) {
        // Sort by timestamp ascending
        uasort($snapshot, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);

        foreach ($snapshot as $entry) {
            if (!isset($entry['timestamp'])) continue;

            // Format label based on range
            $ts = (int) ($entry['timestamp'] / 1000);
            if ($range === '1h' || $range === '6h') {
                $label = date('H:i', $ts);
            } elseif ($range === '24h') {
                $label = date('H:i', $ts);
            } else {
                $label = date('M d H:i', $ts);
            }

            $labels[]  = $label;
            $avgData[] = round($entry['average'] ?? 0, 2);
            $minData[] = round($entry['min']     ?? 0, 2);
            $maxData[] = round($entry['max']     ?? 0, 2);
        }
    }

    // Sensor display metadata
    $META = [
        'temperature' => ['label' => 'Temperature', 'unit' => '°C',  'color' => '#ef4444', 'ok_min' => 45, 'ok_max' => 70],
        'humidity'    => ['label' => 'Humidity',    'unit' => '%',   'color' => '#38bdf8', 'ok_min' => 40, 'ok_max' => 60],
        'gas'         => ['label' => 'Gas Level',   'unit' => 'ppm', 'color' => '#f59e0b', 'ok_min' => 0,  'ok_max' => 600],
        'ph'          => ['label' => 'pH Level',    'unit' => '',    'color' => '#10b981', 'ok_min' => 6.5,'ok_max' => 8.0],
        'weight'      => ['label' => 'Weight',      'unit' => 'kg',  'color' => '#8b5cf6', 'ok_min' => 0,  'ok_max' => 1000],
    ];

    echo json_encode([
        'sensor'    => $sensor,
        'range'     => $range,
        'meta'      => $META[$sensor],
        'count'     => count($labels),
        'labels'    => $labels,
        'datasets'  => [
            'average' => $avgData,
            'min'     => $minData,
            'max'     => $maxData,
        ],
        'generated' => date('c'),
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Firebase error: ' . $e->getMessage()]);
}