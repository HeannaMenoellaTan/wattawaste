<?php
/**
 * delete_compost_session.php
 *
 * Wipes all current-cycle sensor data from Firebase WITHOUT saving
 * to the ML compost_sessions training set.
 *
 * What it deletes:
 *   - sensors/{sensor}/history      (all raw readings)
 *   - sensors/{sensor}/aggregated   (all 5-min aggregates)
 *   - alerts/{sensor}               (all alerts)
 *   - sensors/weight/cycle_start_ts (cycle timer — so new cycle starts fresh)
 *
 * What it resets:
 *   - sensors/weight/initial        → set to current weight (new baseline)
 *   - meta/aggregator/lastRunMs     → removed (cron starts fresh)
 *
 * What it does NOT touch:
 *   - compost_sessions              (ML training data — untouched)
 *   - sensors/{sensor}/latest       (live readings — untouched)
 *   - controls/motor                (motor state — untouched)
 *
 * Also:
 *   - Deletes all anonymous Firebase Auth users (ESP32 cleanup)
 */

// ── CRITICAL: header + error suppression FIRST, before any other code ────────
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// ── Only accept POST ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// ── Validate confirmation flag ────────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);
if (empty($input['confirm'])) {
    echo json_encode(['success' => false, 'error' => 'Confirmation missing']);
    exit;
}

// ── Load Firebase AFTER validation ───────────────────────────────────────────
require_once 'firebase_config.php';

// ── Helper: delete all anonymous Firebase Auth users ─────────────────────────
function deleteAnonymousFirebaseUsers(): void {
    try {
        $factory = (new \Kreait\Firebase\Factory)
->withServiceAccount('C:/xampp/secure/service-account.json')
            ->withDatabaseUri('https://wattawaste-d3503-default-rtdb.asia-southeast1.firebasedatabase.app/');

        $auth   = $factory->createAuth();
        foreach ($auth->listUsers() as $user) {
            $isAnonymous = empty($user->email)
                        && empty($user->phoneNumber)
                        && empty($user->providerData ?? []);

            if ($isAnonymous) {
                $auth->deleteUser($user->uid);
            }
        }
    } catch (Throwable $e) {
        // Non-fatal — swallow silently so the main response is never broken
        error_log('[delete_compost_session] Anonymous user cleanup error: ' . $e->getMessage());
    }
}

$sensors = ['temperature', 'humidity', 'gas', 'ph', 'weight'];

try {
    $db = getDatabase();

    // ── Delete sensor history, aggregates, and alerts ─────────────────────────
    foreach ($sensors as $sensor) {
        $db->getReference("sensors/{$sensor}/history")->remove();
        $db->getReference("sensors/{$sensor}/aggregated")->remove();
        $db->getReference("alerts/{$sensor}")->remove();
    }

    // ── Reset weight baseline to current live weight ──────────────────────────
    $currentWeight = floatval($db->getReference('sensors/weight/latest')->getValue() ?? 0);
    $db->getReference('sensors/weight/initial')->set($currentWeight);

    // ── Clear cycle timer so predict_readiness.php starts a new clock ─────────
    $db->getReference('sensors/weight/cycle_start_ts')->remove();

    // ── Clear last_session flag ───────────────────────────────────────────────
    $db->getReference('sensors/weight/last_session')->remove();

    // ── Clear aggregator window so cron picks up fresh ────────────────────────
    $db->getReference('meta/aggregator/lastRunMs')->remove();

    // ── Delete anonymous Firebase Auth users created by ESP32 ────────────────
    deleteAnonymousFirebaseUsers();

    echo json_encode([
        'success'           => true,
        'message'           => 'Session deleted. Anonymous users cleaned up. New cycle started.',
        'newWeightBaseline' => $currentWeight,
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
    ]);
}