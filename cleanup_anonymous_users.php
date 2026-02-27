<?php
/**
 * cleanup_anonymous_users.php
 *
 * One-time or scheduled script that deletes ALL anonymous Firebase Auth
 * users (the backlog created by the ESP32 signing in anonymously).
 *
 * Usage:
 *   Manual one-time run:
 *     php cleanup_anonymous_users.php
 *
 *   Or add to cron (runs every hour):
 *     0 * * * * php /path/to/cleanup_anonymous_users.php >> /var/log/firebase_cleanup.log 2>&1
 *
 * Requirements:
 *   - service-account.json in the same directory
 *   - kreait/firebase-php installed (composer require kreait/firebase-php)
 */

require_once __DIR__ . '/firebase_config.php';

$factory = (new \Kreait\Firebase\Factory)
    ->withServiceAccount(__DIR__ . '/service-account.json')
    ->withDatabaseUri('https://wattawaste-d3503-default-rtdb.asia-southeast1.firebasedatabase.app/');

$auth = $factory->createAuth();

$deleted  = 0;
$skipped  = 0;
$failed   = 0;

echo "🔍 Scanning Firebase Auth users...\n";

try {
    // ── Fetch user list — works across kreait v4 / v5 / v6 / v7+ ────────────
    // v7+  → listUsers() returns an iterable object (foreach directly)
    // v5-v6 → returns a Generator (foreach directly)
    // Both work with a plain foreach — no iterateAll() needed.
    $rawResult = $auth->listUsers();

    foreach ($rawResult as $user) {
        $providers = $user->providerData ?? [];

        $isAnonymous = empty($user->email)
                    && empty($user->phoneNumber)
                    && empty($providers);

        if (!$isAnonymous) {
            $skipped++;
            continue;
        }

        try {
            $auth->deleteUser($user->uid);
            $deleted++;
            echo "🗑️  Deleted anonymous user: {$user->uid}\n";
        } catch (Throwable $e) {
            $failed++;
            echo "❌  Failed to delete {$user->uid}: " . $e->getMessage() . "\n";
        }
    }

    echo "\n✅ Cleanup complete.\n";
    echo "   Deleted : {$deleted}\n";
    echo "   Skipped : {$skipped} (non-anonymous — kept)\n";
    echo "   Failed  : {$failed}\n";

} catch (Throwable $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "\n💡 Tip: Check that service-account.json is valid and has the correct permissions.\n";
    exit(1);
}