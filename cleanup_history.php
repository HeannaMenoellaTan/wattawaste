<?php
/**
 * cleanup_history.php — One-shot / standalone history & aggregate cleanup
 *
 * Run manually:  php cleanup_history.php
 *
 * ALSO: Apply the two patched functions below back into aggregate.php
 *       to fix the cleanup going forward (orderByChild → orderByKey).
 *
 * REQUIRES: firebase_config.php (your existing file)
 */

require_once __DIR__ . '/firebase_config.php';
$db = getDatabase();

define('HISTORY_TTL', 7 * 24 * 60 * 60 * 1000);   // 7 days in ms
define('AGG_TTL',     7 * 24 * 60 * 60 * 1000);   // 7 days in ms
define('LOG_FILE',    __DIR__ . '/logs/cleanup.log');

$SENSORS = ['temperature', 'humidity', 'gas', 'ph', 'weight'];

// ── Logger ────────────────────────────────────────────────────────────────────
function logMsg(string $msg): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    echo $line;
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    @file_put_contents(LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}

// ── FIX: Use orderByKey instead of orderByChild('timestamp') ──────────────────
//
//  WHY THE ORIGINAL FAILED:
//    ->orderByChild('timestamp')->endAt($cutoff)
//    This filters by a CHILD FIELD called "timestamp" inside each entry.
//    If entries are plain numbers (not objects), or the object has no
//    "timestamp" field, Firebase returns nothing → no deletions happen.
//
//  THE FIX:
//    ->orderByKey()->endAt((string)$cutoff)
//    Your history keys ARE Unix-ms timestamps (e.g. 1771771135000),
//    so ordering by key and capping at the cutoff string works correctly.
//    Firebase key ordering is lexicographic, but since all timestamps have
//    the same number of digits this is equivalent to numeric ordering.

function cleanupOldHistory(object $db, array $sensors, int $now): void {
    $cutoff = $now - HISTORY_TTL;

    logMsg("--- History cleanup | cutoff: " . date('Y-m-d H:i:s', $cutoff / 1000) . " ---");

    foreach ($sensors as $sensor) {
        $snapshot = $db->getReference("sensors/{$sensor}/history")
            ->orderByKey()                  // ← KEY FIX: was orderByChild('timestamp')
            ->endAt((string)$cutoff)        // ← KEY FIX: cast to string for key queries
            ->getValue();

        if (empty($snapshot)) {
            logMsg("○ [{$sensor}] No old history entries to remove");
            continue;
        }

        $deleted = 0;
        foreach (array_keys($snapshot) as $key) {
            $db->getReference("sensors/{$sensor}/history/{$key}")->remove();
            $deleted++;
        }

        logMsg("🗑  [{$sensor}] Deleted {$deleted} raw entries older than 7 days");
    }
}

function cleanupOldAggregates(object $db, array $sensors, int $now): void {
    $cutoff = $now - AGG_TTL;

    logMsg("--- Aggregate cleanup | cutoff: " . date('Y-m-d H:i:s', $cutoff / 1000) . " ---");

    foreach ($sensors as $sensor) {
        $snapshot = $db->getReference("sensors/{$sensor}/aggregated")
            ->orderByKey()                  // ← KEY FIX: was orderByChild('timestamp')
            ->endAt((string)$cutoff)        // ← KEY FIX: cast to string for key queries
            ->getValue();

        if (empty($snapshot)) {
            logMsg("○ [{$sensor}] No old aggregates to remove");
            continue;
        }

        $deleted = 0;
        foreach (array_keys($snapshot) as $key) {
            $db->getReference("sensors/{$sensor}/aggregated/{$key}")->remove();
            $deleted++;
        }

        logMsg("🗑  [{$sensor}] Deleted {$deleted} old aggregates older than 7 days");
    }
}

// ── Run ───────────────────────────────────────────────────────────────────────
$now = (int)(microtime(true) * 1000);
logMsg("=== Standalone cleanup started ===");

try { cleanupOldHistory($db, $SENSORS, $now); }
catch (Throwable $e) { logMsg("ERROR [cleanup-history]: " . $e->getMessage()); }

try { cleanupOldAggregates($db, $SENSORS, $now); }
catch (Throwable $e) { logMsg("ERROR [cleanup-aggregates]: " . $e->getMessage()); }

logMsg("=== Cleanup complete ===\n");