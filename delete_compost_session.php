<?php
/**
 * delete_compost_session.php
 *
 * Wipes all current-cycle sensor data from Firebase WITHOUT saving
 * to the ML compost_sessions training set.
 *
 * Called via POST from ai_advisor.php → doDelete()
 *
 * What it deletes:
 *   - sensors/{sensor}/history      (all raw readings)
 *   - sensors/{sensor}/aggregated   (all 5-min aggregates)
 *   - alerts/{sensor}               (all alerts)
 * 
 * What it resets:
 *   - sensors/weight/initial        → set to current weight (new baseline)
 *   - meta/aggregator/lastRunMs     → removed (cron starts fresh)
 *
 * What it does NOT touch:
 *   - compost_sessions              (ML training data — untouched)
 *   - sensors/{sensor}/latest       (live readings — untouched)
 *   - controls/motor                (motor state — untouched)
 */

require_once 'firebase_config.php';
header('Content-Type: application/json');

$database->getReference("sensors/weight/cycle_start_ts")->remove();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (empty($input['confirm'])) {
    echo json_encode(['success' => false, 'error' => 'Confirmation missing']);
    exit;
}

$db      = getDatabase();
$sensors = ['temperature', 'humidity', 'gas', 'ph', 'weight'];

try {
    foreach ($sensors as $sensor) {
        // Wipe all raw history entries
        $db->getReference("sensors/{$sensor}/history")->remove();

        // Wipe all 5-min aggregates
        $db->getReference("sensors/{$sensor}/aggregated")->remove();

        // Wipe all alerts for this sensor
        $db->getReference("alerts/{$sensor}")->remove();
    }

    // Reset weight baseline: new cycle starts from current weight
    $currentWeight = floatval($db->getReference('sensors/weight/latest')->getValue() ?? 0);
    $db->getReference('sensors/weight/initial')->set($currentWeight);

    // Clear aggregator window so cron picks up from now
    $db->getReference('meta/aggregator/lastRunMs')->remove();

    echo json_encode([
        'success'          => true,
        'message'          => 'Session deleted. New cycle started.',
        'newWeightBaseline' => $currentWeight,
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
    ]);
}