<?php
/**
 * aggregate.php — Sensor Data Aggregator (Cron Script)
 *
 * SETUP (InfinityFree cPanel Cron):
 *   Command : php /path/to/your/site/aggregate.php
 *   Schedule: Every 5 minutes — * /5 * * * *
 *
 * REQUIRES: firebase_config.php (your existing file)
 */

require_once __DIR__ . '/firebase_config.php';
$db = getDatabase();

// ── Config ────────────────────────────────────────────────────────────────────
define('WINDOW_MS',    5 * 60 * 1000);               // 5-min aggregation window
define('HISTORY_TTL',  7 * 24 * 60 * 60 * 1000);    // Keep raw history for 7 DAYS
define('AGG_TTL',      7 * 24 * 60 * 60 * 1000);    // Keep aggregates for 7 DAYS
define('LOCK_FILE',    __DIR__ . '/logs/aggregate.lock');
define('LOG_FILE',     __DIR__ . '/logs/aggregate.log');
define('LAST_RUN_KEY', 'meta/aggregator/lastRunMs');

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
    $line   = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    echo $line;
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    @file_put_contents(LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}

// ── Process lock (prevents overlapping cron runs) ─────────────────────────────
function acquireLock(): bool {
    $logDir = dirname(LOCK_FILE);
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);

    $fp = fopen(LOCK_FILE, 'c');
    if (!$fp) return false;

    if (!flock($fp, LOCK_EX | LOCK_NB)) {
        fclose($fp);
        return false;
    }

    $mtime = @filemtime(LOCK_FILE);
    if ($mtime && (time() - $mtime) > 600) {
        logMsg("WARNING: Stale lock detected (>10 min old) — force releasing.");
    }

    ftruncate($fp, 0);
    fwrite($fp, (string) getmypid());
    fflush($fp);
    $GLOBALS['_lockFp'] = $fp;
    return true;
}

function releaseLock(): void {
    if (!empty($GLOBALS['_lockFp'])) {
        flock($GLOBALS['_lockFp'], LOCK_UN);
        fclose($GLOBALS['_lockFp']);
        @unlink(LOCK_FILE);
        $GLOBALS['_lockFp'] = null;
    }
}

// ── Main runner ───────────────────────────────────────────────────────────────
function run(object $db, array $sensors, array $thresholds): void {
    if (!acquireLock()) {
        logMsg("SKIP: Another aggregation is already running. Exiting.");
        return;
    }

    register_shutdown_function('releaseLock');

    $now = (int)(microtime(true) * 1000);

    // Determine window start — resume from last run timestamp stored in Firebase
    $lastRunMs = $db->getReference(LAST_RUN_KEY)->getValue();

    if (!$lastRunMs || !is_numeric($lastRunMs)) {
        $windowStart = $now - WINDOW_MS;
        logMsg("First run detected — defaulting window to last 5 minutes.");
    } else {
        $windowStart = (int)$lastRunMs;
        $gapMinutes  = round(($now - $windowStart) / 60000, 1);
        logMsg("Resuming from last run. Gap covered: {$gapMinutes} minutes.");
    }

    // Safety cap: never process more than 2 hours at once to prevent timeout
    if (($now - $windowStart) > 2 * 60 * 60 * 1000) {
        logMsg("WARNING: Gap > 2 hours. Capping window to prevent timeout.");
        $windowStart = $now - (2 * 60 * 60 * 1000);
    }

    logMsg("=== Aggregation started | window: "
        . date('Y-m-d H:i:s', $windowStart / 1000)
        . " → " . date('Y-m-d H:i:s', $now / 1000) . " ===");

    foreach ($sensors as $sensor) {
        try {
            aggregateSensor($db, $sensor, $now, $windowStart, $thresholds[$sensor]);
        } catch (Throwable $e) {
            logMsg("ERROR [$sensor]: " . $e->getMessage());
        }
    }

    // Save current timestamp as next window start
    $db->getReference(LAST_RUN_KEY)->set($now);

    // Cleanup passes
    try { cleanupOldHistory($db, $sensors, $now); }
    catch (Throwable $e) { logMsg("ERROR [cleanup-history]: " . $e->getMessage()); }

    try { cleanupOldAggregates($db, $sensors, $now); }
    catch (Throwable $e) { logMsg("ERROR [cleanup-aggregates]: " . $e->getMessage()); }

    releaseLock();
    logMsg("=== Aggregation complete ===\n");
}

// ── Per-sensor aggregation ────────────────────────────────────────────────────
function aggregateSensor(object $db, string $sensor, int $now, int $windowStart, array $threshold): void {
    $snapshot = $db->getReference("sensors/{$sensor}/history")
        ->orderByChild('timestamp')
        ->startAt($windowStart)
        ->endAt($now)
        ->getValue();

    $values    = [];
    $malformed = 0;

    if (!empty($snapshot)) {
        foreach ($snapshot as $key => $entry) {
            // Auto-fix malformed entries (plain numbers instead of objects)
            if (is_numeric($entry)) {
                $malformed++;
                $fixedTs = is_numeric($key) ? (int)$key : $now;
                $db->getReference("sensors/{$sensor}/history/{$key}")->set([
                    'value'     => (float)$entry,
                    'timestamp' => $fixedTs,
                ]);
                $values[] = (float)$entry;
            } elseif (isset($entry['value']) && is_numeric($entry['value'])) {
                $values[] = (float)$entry['value'];
            }
        }
    }

    if ($malformed > 0) {
        logMsg("  ⚠ [{$sensor}] Auto-fixed {$malformed} malformed history entries");
    }

    if (empty($values)) {
        logMsg("○ [{$sensor}] No valid readings in window — skipping");
        return;
    }

    $count = count($values);
    $avg   = array_sum($values) / $count;
    $min   = min($values);
    $max   = max($values);

    // Write 5-minute aggregate
    $db->getReference("sensors/{$sensor}/aggregated/{$now}")->set([
        'timestamp'  => $now,
        'average'    => round($avg, 2),
        'min'        => round($min, 2),
        'max'        => round($max, 2),
        'count'      => $count,
        'period'     => '5min',
        'startTime'  => $windowStart,
        'endTime'    => $now,
    ]);

    // NOTE: /latest is owned by the ESP32 hardware — do NOT overwrite it here.
    // Dashboard reads /latest for live display; /aggregated for historical charts.

    logMsg("✓ [{$sensor}] {$count} readings | avg=" . round($avg, 2)
        . "  min=" . round($min, 2) . "  max=" . round($max, 2));

    checkAlert($db, $sensor, $avg, $threshold, $now);
}

// ── Alert checker ─────────────────────────────────────────────────────────────
function checkAlert(object $db, string $sensor, float $value, array $threshold, int $now): void {
    if ($value >= $threshold['min'] && $value <= $threshold['max']) {
        return; // Normal — no alert
    }

    $unit    = $threshold['unit'];
    $rounded = round($value, 2);
    $message = ucfirst($sensor) . " 5-min average ({$rounded} {$unit}) is outside normal range "
             . "[{$threshold['min']}–{$threshold['max']} {$unit}]";

    $db->getReference("alerts/{$sensor}/{$now}")->set([
        'timestamp' => $now,
        'sensor'    => $sensor,
        'value'     => $rounded,
        'threshold' => $threshold,
        'message'   => $message,
        'resolved'  => false,
    ]);

    logMsg("⚠ ALERT [{$sensor}]: {$message}");
}

// ── History cleanup — raw entries older than 7 DAYS ───────────────────────────
function cleanupOldHistory(object $db, array $sensors, int $now): void {
    $cutoff = $now - HISTORY_TTL; // 7 days ago in ms

    foreach ($sensors as $sensor) {
        $snapshot = $db->getReference("sensors/{$sensor}/history")
            ->orderByChild('timestamp')
            ->endAt($cutoff)
            ->getValue();

        if (empty($snapshot)) continue;

        $deleted = 0;
        foreach (array_keys($snapshot) as $key) {
            $db->getReference("sensors/{$sensor}/history/{$key}")->remove();
            $deleted++;
        }

        logMsg("🗑  [{$sensor}] Deleted {$deleted} raw entries older than 7 days");
    }
}

// ── Aggregate cleanup — aggregates older than 7 DAYS ─────────────────────────
function cleanupOldAggregates(object $db, array $sensors, int $now): void {
    $cutoff = $now - AGG_TTL; // 7 days ago in ms

    foreach ($sensors as $sensor) {
        $snapshot = $db->getReference("sensors/{$sensor}/aggregated")
            ->orderByChild('timestamp')
            ->endAt($cutoff)
            ->getValue();

        if (empty($snapshot)) continue;

        $deleted = 0;
        foreach (array_keys($snapshot) as $key) {
            $db->getReference("sensors/{$sensor}/aggregated/{$key}")->remove();
            $deleted++;
        }

        logMsg("🗑  [{$sensor}] Deleted {$deleted} old aggregates older than 7 days");
    }
}

// ── Execute ────────────────────────────────────────────────────────────────────
run($db, $SENSORS, $THRESHOLDS);