<?php
/**
 * chart_data.php — History Graph API Endpoint
 *
 * Tries aggregated/ nodes first (populated by aggregate.php cron).
 * Falls back to raw history/ nodes so the chart works even before
 * aggregate.php has ever run.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=60');

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

// ── Sensor metadata ───────────────────────────────────────────────────────────
$META = [
    'temperature' => ['label' => 'Temperature', 'unit' => '°C',  'color' => '#ef4444', 'ok_min' => 45,  'ok_max' => 70],
    'humidity'    => ['label' => 'Humidity',    'unit' => '%',   'color' => '#38bdf8', 'ok_min' => 40,  'ok_max' => 60],
    'gas'         => ['label' => 'Gas Level',   'unit' => 'ppm', 'color' => '#f59e0b', 'ok_min' => 0,   'ok_max' => 600],
    'ph'          => ['label' => 'pH Level',    'unit' => '',    'color' => '#10b981', 'ok_min' => 6.5, 'ok_max' => 8.0],
    'weight'      => ['label' => 'Weight',      'unit' => 'kg',  'color' => '#8b5cf6', 'ok_min' => 0,   'ok_max' => 1000],
];

try {
    $db        = getDatabase();
    $now       = (int)(microtime(true) * 1000);
    $startTime = $now - $VALID_RANGES[$range];

    $labels  = [];
    $avgData = [];
    $minData = [];
    $maxData = [];
    $source  = 'aggregated';

    // ── 1. Try aggregated/ first ──────────────────────────────────────────────
    $snapshot = $db->getReference("sensors/{$sensor}/aggregated")
        ->orderByChild('timestamp')
        ->startAt($startTime)
        ->endAt($now)
        ->getValue();

    if (!empty($snapshot)) {
        // Sort ascending by timestamp
        uasort($snapshot, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);

        foreach ($snapshot as $entry) {
            if (!isset($entry['timestamp'])) continue;

            $ts      = (int)($entry['timestamp'] / 1000);
            $labels[]  = ($range === '7d' || $range === '30d')
                ? date('M d H:i', $ts)
                : date('H:i', $ts);

            $avgData[] = round($entry['average'] ?? 0, 2);
            $minData[] = round($entry['min']     ?? 0, 2);
            $maxData[] = round($entry['max']     ?? 0, 2);
        }
    }

    // ── 2. Fallback: read raw history/ directly (same as temperature.php) ─────
    if (empty($labels)) {
        $source   = 'raw_history';
        $rawSnap  = $db->getReference("sensors/{$sensor}/history")
            ->orderByChild('timestamp')
            ->startAt($startTime)
            ->endAt($now)
            ->getValue();

        // Also try key-ordered if orderByChild returns nothing
        // (some ESP32 firmwares push with key = timestamp ms)
        if (empty($rawSnap)) {
            $rawSnap = $db->getReference("sensors/{$sensor}/history")
                ->orderByKey()
                ->startAt((string)$startTime)
                ->getValue();
        }

        if (!empty($rawSnap)) {
            // Build entries array — handle both {value, timestamp} objects and plain floats
            $entries = [];
            foreach ($rawSnap as $key => $entry) {
                if (is_array($entry) && isset($entry['value'])) {
                    $val  = (float)$entry['value'];
                    $tsMs = isset($entry['timestamp']) ? (int)$entry['timestamp'] : (int)$key;
                } else {
                    $val  = (float)$entry;
                    $tsMs = (int)$key;
                }

                if (is_numeric($val) && $tsMs >= $startTime) {
                    $entries[] = ['ts' => $tsMs, 'value' => $val];
                }
            }

            // Sort ascending
            usort($entries, fn($a, $b) => $a['ts'] <=> $b['ts']);

            // Downsample to max 200 points so chart stays fast
            $total  = count($entries);
            $step   = max(1, (int)ceil($total / 200));

            // Group into 5-min buckets so avg/min/max make sense
            $buckets = [];
            foreach ($entries as $e) {
                $bucket = (int)(floor($e['ts'] / (5 * 60 * 1000)) * (5 * 60 * 1000));
                $buckets[$bucket][] = $e['value'];
            }
            ksort($buckets);

            foreach ($buckets as $bucketTs => $vals) {
                $ts      = (int)($bucketTs / 1000);
                $labels[]  = ($range === '7d' || $range === '30d')
                    ? date('M d H:i', $ts)
                    : date('H:i', $ts);

                $avgData[] = round(array_sum($vals) / count($vals), 2);
                $minData[] = round(min($vals), 2);
                $maxData[] = round(max($vals), 2);
            }
        }
    }

    echo json_encode([
        'sensor'    => $sensor,
        'range'     => $range,
        'source'    => $source,          // 'aggregated' or 'raw_history' — useful for debugging
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