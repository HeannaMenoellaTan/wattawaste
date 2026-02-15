<?php
/**
 * aggregate.php — Sensor Data Aggregator (Cron Script)
 * 
 * PURPOSE: Reads raw sensor history from Firebase, computes 5-minute averages,
 *          stores aggregated data, triggers alerts on abnormal readings, and
 *          cleans up old raw history to reduce Firebase read costs.
 * 
 * SETUP (InfinityFree cPanel Cron):
 *   Command : php /path/to/your/site/aggregate.php
 *   Schedule: Every 5 minutes — */5 * * * *
 * 
 * REQUIRES: firebase_config.php (your existing file)
 */

// ── Load your existing Firebase config ──────────────────────────────────────
require_once __DIR__ . '/firebase_config.php';

$db = getDatabase(); // Uses your existing getDatabase() function

// ── Config ───────────────────────────────────────────────────────────────────
define('WINDOW_MS',   5 * 60 * 1000);   // 5-minute aggregation window
define('HISTORY_TTL', 24 * 60 * 60 * 1000); // Keep raw history for 24h
define('AGG_TTL',     30 * 24 * 60 * 60 * 1000); // Keep aggregates for 30 days
define('LOG_FILE',    __DIR__ . '/logs/aggregate.log');

// Alert thresholds — adjust to match your compost sensor ranges
$THRESHOLDS = [
    'temperature' => ['min' => 15,  'max' => 75,   'unit' => '°C'],
    'humidity'    => ['min' => 30,  'max' => 90,   'unit' => '%'],
    'gas'         => ['min' => 0,   'max' => 800,  'unit' => 'ppm'],
    'ph'          => ['min' => 5.5, 'max' => 8.5,  'unit' => 'pH'],
    'weight'      => ['min' => 0,   'max' => 1000, 'unit' => 'kg'],
];

$SENSORS = array_keys($THRESHOLDS);

// ── Logger ────────────────────────────────────────────────────────────────────
function logMsg(string $msg): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    echo $line;
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @file_put_contents(LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}

// ── Main runner ───────────────────────────────────────────────────────────────
function run(object $db, array $sensors, array $thresholds): void {
    $now          = (int) (microtime(true) * 1000);
    $windowStart  = $now - WINDOW_MS;

    logMsg("=== Aggregation started ===");

    foreach ($sensors as $sensor) {
        try {
            aggregateSensor($db, $sensor, $now, $windowStart, $thresholds[$sensor]);
        } catch (Throwable $e) {
            logMsg("ERROR [$sensor]: " . $e->getMessage());
        }
    }

    // Cleanup passes (runs every invocation — cheap since Firebase handles indexing)
    try {
        cleanupOldHistory($db, $sensors, $now);
    } catch (Throwable $e) {
        logMsg("ERROR [cleanup-history]: " . $e->getMessage());
    }

    try {
        cleanupOldAggregates($db, $sensors, $now);
    } catch (Throwable $e) {
        logMsg("ERROR [cleanup-aggregates]: " . $e->getMessage());
    }

    logMsg("=== Aggregation complete ===\n");
}

// ── Per-sensor aggregation ────────────────────────────────────────────────────
function aggregateSensor(object $db, string $sensor, int $now, int $windowStart, array $threshold): void {
    // Fetch raw history entries in the last 5 minutes
    $historyRef = $db->getReference("sensors/{$sensor}/history");
    $snapshot   = $historyRef
        ->orderByChild('timestamp')
        ->startAt($windowStart)
        ->endAt($now)
        ->getValue();

    if (empty($snapshot)) {
        logMsg("○ [{$sensor}] No history data in window — skipping");
        return;
    }

    // Collect values
    $values = [];
    foreach ($snapshot as $entry) {
        if (isset($entry['value']) && is_numeric($entry['value'])) {
            $values[] = (float) $entry['value'];
        }
    }

    if (empty($values)) {
        logMsg("○ [{$sensor}] No valid numeric values — skipping");
        return;
    }

    $count = count($values);
    $avg   = array_sum($values) / $count;
    $min   = min($values);
    $max   = max($values);
    $sum   = array_sum($values);

    // Store 5-minute aggregate
    $db->getReference("sensors/{$sensor}/aggregated/{$now}")->set([
        'timestamp'   => $now,
        'average'     => round($avg, 2),
        'min'         => round($min, 2),
        'max'         => round($max, 2),
        'sum'         => round($sum, 2),
        'count'       => $count,
        'period'      => '5min',
        'startTime'   => $windowStart,
        'endTime'     => $now,
    ]);

    // Update the /latest node so your dashboard reads the aggregated average
    // (reduces live reads from history — saves Firebase costs)
    $db->getReference("sensors/{$sensor}/latest")->set(round($avg, 2));

    logMsg("✓ [{$sensor}] {$count} readings | avg=" . round($avg, 2) . " min=" . round($min, 2) . " max=" . round($max, 2));

    // Check alert thresholds
    checkAlert($db, $sensor, $avg, $threshold, $now);
}

// ── Alert checker ─────────────────────────────────────────────────────────────
function checkAlert(object $db, string $sensor, float $value, array $threshold, int $now): void {
    if ($value < $threshold['min'] || $value > $threshold['max']) {
        $unit    = $threshold['unit'];
        $message = ucfirst($sensor) . " reading ({$value} {$unit}) is outside normal range "
                 . "[{$threshold['min']}–{$threshold['max']} {$unit}]";

        $db->getReference("alerts/{$sensor}/{$now}")->set([
            'timestamp' => $now,
            'sensor'    => $sensor,
            'value'     => $value,
            'threshold' => $threshold,
            'message'   => $message,
            'resolved'  => false,
        ]);

        logMsg("⚠ ALERT [{$sensor}]: {$message}");
    }
}

// ── History cleanup (raw data older than 24h) ─────────────────────────────────
function cleanupOldHistory(object $db, array $sensors, int $now): void {
    $cutoff = $now - HISTORY_TTL;

    foreach ($sensors as $sensor) {
        $snapshot = $db->getReference("sensors/{$sensor}/history")
            ->orderByChild('timestamp')
            ->endAt($cutoff)
            ->getValue();

        if (empty($snapshot)) {
            continue;
        }

        $deleted = 0;
        foreach ($snapshot as $key => $_) {
            $db->getReference("sensors/{$sensor}/history/{$key}")->remove();
            $deleted++;
        }

        if ($deleted > 0) {
            logMsg("🗑  [{$sensor}] Deleted {$deleted} old raw history entries");
        }
    }
}

// ── Aggregate cleanup (aggregates older than 30 days) ─────────────────────────
function cleanupOldAggregates(object $db, array $sensors, int $now): void {
    $cutoff = $now - AGG_TTL;

    foreach ($sensors as $sensor) {
        $snapshot = $db->getReference("sensors/{$sensor}/aggregated")
            ->orderByChild('timestamp')
            ->endAt($cutoff)
            ->getValue();

        if (empty($snapshot)) {
            continue;
        }

        $deleted = 0;
        foreach ($snapshot as $key => $_) {
            $db->getReference("sensors/{$sensor}/aggregated/{$key}")->remove();
            $deleted++;
        }

        if ($deleted > 0) {
            logMsg("🗑  [{$sensor}] Deleted {$deleted} old aggregates");
        }
    }
}

// ── Execute ────────────────────────────────────────────────────────────────────
run($db, $SENSORS, $THRESHOLDS);