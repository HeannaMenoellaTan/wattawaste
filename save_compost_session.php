<?php
/**
 * save_compost_session.php
 *
 * When called:
 * 1. Saves the ML training record to compost_sessions/{key}
 * 2. Archives ALL sensor history to compost_history/{key}/sensors/
 * 3. Resets sensors/weight/initial to the current live weight
 *    → This makes index.php, weight.php, predict_readiness.php etc.
 *      treat the next reading as a brand-new composting cycle
 * 4. Deletes anonymous Firebase Auth users (ESP32 cleanup)
 */

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

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
        error_log('[save_compost_session] Anonymous user cleanup error: ' . $e->getMessage());
    }
}

try {
    require_once __DIR__ . '/firebase_config.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
        exit;
    }

    // Validate required fields
    foreach (['temp','humidity','gas','ph','weightLoss','daysToReady','outcome'] as $f) {
        if (!isset($data[$f])) {
            echo json_encode(['success' => false, 'error' => "Missing field: $f"]);
            exit;
        }
    }

    $db  = getDatabase();
    $key = (string)(int)(microtime(true) * 1000); // ms timestamp as unique key

    // ── 1. Save ML training record ────────────────────────────────────────────
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
        'batchKey'    => $key,
    ];
    $db->getReference("compost_sessions/{$key}")->set($session);

    // ── 2. Archive sensor histories ───────────────────────────────────────────
    $sensors     = ['temperature', 'humidity', 'gas', 'ph', 'weight'];
    $archiveBase = "compost_history/{$key}";

    // Save session metadata in archive
    $db->getReference("{$archiveBase}/meta")->set([
        'savedAt'     => $data['savedAt'] ?? date('c'),
        'daysToReady' => max(1, (int)$data['daysToReady']),
        'outcome'     => $session['outcome'],
        'readiness'   => $session['readiness'],
        'finalTemp'   => $session['temp'],
        'finalHum'    => $session['humidity'],
        'finalGas'    => $session['gas'],
        'finalPH'     => $session['ph'],
        'weightLoss'  => $session['weightLoss'],
    ]);

    // Copy each sensor's history into the archive
    foreach ($sensors as $sensor) {
        $history = $db->getReference("sensors/{$sensor}/history")->getValue();
        if ($history) {
            $db->getReference("{$archiveBase}/sensors/{$sensor}")->set($history);
        }

        // Also archive the latest snapshot
        $latest = $db->getReference("sensors/{$sensor}/latest")->getValue();
        if ($latest !== null) {
            $db->getReference("{$archiveBase}/latest/{$sensor}")->set($latest);
        }
    }

    // Archive initial weight used for this batch
    $initialWeight = $db->getReference("sensors/weight/initial")->getValue();
    if ($initialWeight !== null) {
        $db->getReference("{$archiveBase}/meta/initialWeight")->set($initialWeight);
    }

    // ── 3. Reset initial weight → current weight (new cycle starts here) ──────
    $currentWeight = $db->getReference("sensors/weight/latest")->getValue() ?? 0;
    $db->getReference("sensors/weight/initial")->set((float)$currentWeight);

    // ── 4. Delete anonymous Firebase Auth users created by ESP32 ─────────────
    deleteAnonymousFirebaseUsers();

    // ── 5. Respond ────────────────────────────────────────────────────────────
    echo json_encode([
        'success'          => true,
        'key'              => $key,
        'archived'         => true,
        'newInitialWeight' => (float)$currentWeight,
        'message'          => 'Session saved. History archived. Anonymous users cleaned up. New cycle started.',
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}