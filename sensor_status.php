<?php
header("Content-Type: application/json");
require_once 'firebase_config.php';
date_default_timezone_set('Asia/Manila');

// ==== SENSOR STATUS CHECK FUNCTION ====
function getSensorStatus($timestamp) {
    if (!$timestamp || $timestamp <= 0) {
        return ["Faulty", "faulty", "No data"];
    }

    $now  = time();
    $diff = $now - $timestamp;

    if ($diff <= 10)  return ["Online",  "online",  $diff . "s ago"];
    if ($diff <= 30)  return ["Delayed", "delayed", $diff . "s ago"];
    if ($diff <= 120) return ["Offline", "offline", $diff . "s ago"];

    return ["Faulty", "faulty", $diff . "s ago"];
}

// ==== FETCH SENSOR DATA FROM FIREBASE ====
function fetchSensorData($database, $sensorType, $displayName) {
    try {
        // ── Strategy 1: try sensors/{type}/latest (may be scalar OR object) ──
        $latestRef  = $database->getReference("sensors/$sensorType/latest");
        $snapshot   = $latestRef->getSnapshot();
        $timestamp  = null;

        if ($snapshot->exists()) {
            $data = $snapshot->getValue();

            if (is_array($data)) {
                // Object form: { value: X, timestamp: Y }
                $timestamp = $data['timestamp'] ?? null;
            }
            // If it's a plain scalar the timestamp lives elsewhere – fall through
        }

        // ── Strategy 2: try sensors/{type}/timestamp directly ──
        if (!$timestamp) {
            $tsRef     = $database->getReference("sensors/$sensorType/timestamp");
            $tsSnap    = $tsRef->getSnapshot();
            if ($tsSnap->exists()) {
                $timestamp = $tsSnap->getValue();
            }
        }

        // ── Strategy 3: try sensors/{type}/lastUpdate ──
        if (!$timestamp) {
            $luRef  = $database->getReference("sensors/$sensorType/lastUpdate");
            $luSnap = $luRef->getSnapshot();
            if ($luSnap->exists()) {
                $timestamp = $luSnap->getValue();
            }
        }

        // ── Strategy 4: scan history for the most recent entry ──
        if (!$timestamp) {
            $histRef  = $database->getReference("sensors/$sensorType/history");
            $histSnap = $histRef
                ->orderByChild('timestamp')
                ->limitToLast(1)
                ->getSnapshot();

            if ($histSnap->exists()) {
                $entries = $histSnap->getValue();
                foreach ($entries as $entry) {
                    $ts = null;
                    if (is_array($entry)) {
                        $ts = $entry['timestamp'] ?? null;
                    }
                    if ($ts) $timestamp = $ts;
                }
            }
        }

        // ── Evaluate ──
        if (!$timestamp) {
            // Last resort: sensor node exists but no timestamp found anywhere –
            // treat as online if the value itself exists and is recent-ish
            if ($snapshot->exists() && $snapshot->getValue() !== null) {
                return [
                    "name"       => $displayName,
                    "status"     => "Online",
                    "class"      => "online",
                    "lastUpdate" => "Active"
                ];
            }
            return [
                "name"       => $displayName,
                "status"     => "Faulty",
                "class"      => "faulty",
                "lastUpdate" => "No timestamp"
            ];
        }

        // Firebase may store timestamps in milliseconds — normalise to seconds
        if ($timestamp > 1_000_000_000_000) {
            $timestamp = intval($timestamp / 1000);
        }

        list($status, $class, $timeAgo) = getSensorStatus($timestamp);

        return [
            "name"       => $displayName,
            "status"     => $status,
            "class"      => $class,
            "lastUpdate" => $timeAgo
        ];

    } catch (Exception $e) {
        error_log("Sensor fetch error for $sensorType: " . $e->getMessage());
        return [
            "name"       => $displayName,
            "status"     => "Faulty",
            "class"      => "faulty",
            "lastUpdate" => "Error"
        ];
    }
}

try {
    $database = getDatabase();

    $output = [
        fetchSensorData($database, 'temperature', 'Temperature'),
        fetchSensorData($database, 'humidity',    'Humidity'),
        fetchSensorData($database, 'gas',         'Gas'),
        fetchSensorData($database, 'ph',          'pH'),
    ];

    echo json_encode($output);

} catch (Exception $e) {
    error_log("Sensor status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "error"   => "Failed to fetch sensor status",
        "message" => $e->getMessage()
    ]);
}
exit;
?>