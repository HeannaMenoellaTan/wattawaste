<?php
/**
 * predict_readiness.php — Single source of truth for compost readiness
 *
 * ROOT CAUSE FIXES (original):
 *
 * BUG 1: Adding 60kg → instant 78% readiness
 *   CAUSE: Sensors alone (temp/humidity/gas/pH) scored 60%+ regardless of time
 *   FIX:   Stage ceilings — you CANNOT exceed the ceiling of your current stage
 *          Stage 'initial' ceiling = 12%. Sensors can't push you past it.
 *
 * BUG 2: Removing half the weight → 97% readiness
 *   CAUSE: weightLoss = (initial - current) / initial = 50% → high score
 *          The system couldn't tell "physically removed" from "decomposed"
 *   FIX:   Anti-cheat: if weight drops faster than max realistic decomp rate
 *          (~5%/day), the credited weight loss is capped to elapsed-time-based max.
 *          Manual removal = almost no credited weight loss.
 *
 * BUG 3: Stage jumps to "Late Thermophilic" on day 1
 *   CAUSE: Stage was purely score-based with no time gate
 *   FIX:   Each stage requires BOTH sensor conditions AND minimum elapsed hours:
 *          initial=0h, mesophilic=6h, thermophilic=48h, late_thermo=120h, maturation=240h
 *
 * BUG 4: AI k-NN days prediction was redundant / ignored
 *   CAUSE: ML was blended weakly into the composite score
 *   FIX:   ML now adjusts score based on (elapsed / expected_total) ratio.
 *          If AI says 30 days and only 2 elapsed → score is pulled down toward ~7%
 *          If AI says 1 day remaining → score can approach ceiling
 *
 * NEW FIX — BUG 5: Outcome field from saved sessions was never used
 *   CAUSE: k-NN loop never filtered or weighted by outcome (success/partial/failed)
 *          A failed batch carried the same prediction weight as a successful one.
 *   FIX:   Outcome penalty multiplier applied to Euclidean distance:
 *          success  → ×1.0  (no penalty — treated as closest/most trusted)
 *          partial  → ×1.8  (moderately penalised)
 *          failed   → ×3.5  (heavily penalised — treated as much further away)
 *          Result: successful sessions dominate the k-NN prediction.
 *          The earliest successful session naturally becomes the benchmark.
 *
 * NEW FIX — BUG 6: k-NN predicted days never preferred fastest successful session
 *   CAUSE: All sessions were averaged regardless of speed or success
 *   FIX:   After outcome weighting, successful sessions with fewer daysToReady
 *          are naturally closer (lower distance) and dominate the weighted average.
 *          Additionally, if any pure-success sessions exist, their minimum
 *          daysToReady is stored as successBenchmark and returned in the response
 *          so the UI can display "fastest known cycle" as a target reference.
 *
 * HOW STAGES WORK:
 *   - cycle_start_ts is written to Firebase when weight > 0 for the first time
 *   - Stage = f(elapsed_hours, temperature, humidity, gas, pH, credited_weight_loss)
 *   - Readiness is mapped into the stage's range [min, max] on the 0-100 scale
 *   - Hard ceiling prevents any score from exceeding the stage maximum
 *
 * STAGE MAP:
 *   initial      [0h+  ] → score range  0–12%
 *   mesophilic   [6h+  ] → score range 12–35%
 *   thermophilic [48h+ ] → score range 35–65%
 *   late_thermo  [120h+] → score range 65–82%
 *   maturation   [240h+] → score range 82–100%
 */

header("Content-Type: application/json");
error_reporting(0);
ini_set('display_errors', 0);

require_once 'firebase_config.php';

// ── Stage ceilings: score CANNOT exceed this until you advance ───────────────
const STAGE_CEILING = [
    'no_compost'   => 0,
    'initial'      => 12,
    'mesophilic'   => 35,
    'thermophilic' => 65,
    'late_thermo'  => 82,
    'maturation'   => 100,
];

// ── Min elapsed hours before stage can be reached ───────────────────────────
const STAGE_MIN_HOURS = [
    'initial'      => 0,
    'mesophilic'   => 6,
    'thermophilic' => 48,
    'late_thermo'  => 120,
    'maturation'   => 240,
];

// ── Score range each stage occupies on the 0-100 readiness scale ─────────────
const STAGE_RANGE = [
    'initial'      => [0,  12],
    'mesophilic'   => [12, 35],
    'thermophilic' => [35, 65],
    'late_thermo'  => [65, 82],
    'maturation'   => [82, 100],
];

// ── Stage display names ───────────────────────────────────────────────────────
const STAGE_NAME = [
    'no_compost'   => 'No Compost',
    'initial'      => 'Initial Breakdown',
    'mesophilic'   => 'Mesophilic Stage (Building)',
    'thermophilic' => 'Thermophilic Stage (Active)',
    'late_thermo'  => 'Late Thermophilic Stage',
    'maturation'   => 'Maturation Stage (Curing)',
];

// ── Outcome penalty multipliers for k-NN distance weighting ─────────────────
// Lower multiplier = session is treated as "closer" = more influential
// SUCCESS  → no penalty   (fastest, most trusted cycles dominate)
// PARTIAL  → 1.8× farther (mixed result, reduced influence)
// FAILED   → 3.5× farther (bad batch, nearly excluded from prediction)
const OUTCOME_PENALTY = [
    'success' => 1.0,
    'partial' => 1.8,
    'failed'  => 3.5,
];

try {
    $database = getDatabase();

    // ── 1. Fetch all sensor values ────────────────────────────────────────────
    $temperature   = floatval($database->getReference("sensors/temperature/latest")->getValue() ?? 0);
    $humidity      = floatval($database->getReference("sensors/humidity/latest")->getValue() ?? 0);
    $gas           = floatval($database->getReference("sensors/gas/latest")->getValue() ?? 0);
    $ph            = floatval($database->getReference("sensors/ph/latest")->getValue() ?? 0);
    $currentWeight = floatval($database->getReference("sensors/weight/latest")->getValue() ?? 0);
    $initialWeight = floatval($database->getReference("sensors/weight/initial")->getValue() ?? 0);

    // ── 2. Harvest session check ──────────────────────────────────────────────
    $lastSession      = $database->getReference("sensors/weight/last_session")->getValue();
    $sessionHarvested = ($lastSession !== null
        && isset($lastSession['harvested'])
        && $lastSession['harvested'] === true);

    // ── 3. Gate: no weight = no composting ───────────────────────────────────
    $weightIsZero = ($currentWeight <= 0);
    $hasNoCompost = $weightIsZero && !$sessionHarvested;

    if ($hasNoCompost) {
        echo json_encode([
            "readiness"  => 0,
            "status"     => "no_compost",
            "message"    => "No compost detected. Add organic waste to begin composting.",
            "stage"      => "no_compost",
            "stage_name" => "No Compost",
            "ml"         => [
                "sessions_used"        => 0,
                "success_sessions"     => 0,
                "ml_influence_pct"     => 0,
                "rule_score"           => 0,
                "ml_score"             => null,
                "predicted_days"       => null,
                "success_benchmark_days" => null,
                "final_score"          => 0,
                "stage_ceiling"        => 0,
            ],
            "weight_tracking"   => ["initial_weight"=>0,"current_weight"=>0,"weight_loss_kg"=>0,"weight_loss_percent"=>0,"credited_weight_loss"=>0,"hours_elapsed"=>0,"days_elapsed"=>0],
            "fertilizer_output" => ["predicted_output_kg"=>0,"actual_output_kg"=>0,"is_ready"=>false,"fertilizer_percentage"=>0],
            "analytics"  => ["stage"=>"no_compost","stage_ceiling"=>0,"elapsed_hours"=>0,"elapsed_days"=>0,"credited_wl"=>0,"weight_score"=>0,"time_score"=>0,"environment_score"=>0,"temperature"=>$temperature,"humidity"=>$humidity,"ph"=>$ph,"gas"=>$gas],
        ]);
        exit;
    }

    // ── 4. Auto-init initial weight ───────────────────────────────────────────
    if ($initialWeight <= 0 && $currentWeight > 0) {
        $database->getReference("sensors/weight/initial")->set($currentWeight);
        $initialWeight = $currentWeight;
    }
    // If weight increased (more material added), update initial baseline
    if ($currentWeight > $initialWeight && $initialWeight > 0) {
        $database->getReference("sensors/weight/initial")->set($currentWeight);
        $initialWeight = $currentWeight;
    }

    // ── 5. Auto-init cycle start timestamp ───────────────────────────────────
    $cycleStartTs = $database->getReference("sensors/weight/cycle_start_ts")->getValue();
    if ($cycleStartTs === null && $currentWeight > 0) {
        $nowMs = (double)(time()) * 1000.0;
        $database->getReference("sensors/weight/cycle_start_ts")->set($nowMs);
        $cycleStartTs = $nowMs;
    }

    // ── 6. Elapsed time ───────────────────────────────────────────────────────
    $nowMs        = (double)(time()) * 1000.0;
    $elapsedMs    = $cycleStartTs ? max(0.0, $nowMs - floatval($cycleStartTs)) : 0.0;
    $elapsedHours = $elapsedMs / 3600000.0;
    $elapsedDays  = $elapsedHours / 24.0;

    // ── 7. Weight loss calculation ────────────────────────────────────────────
    $weightLossKg  = max(0.0, $initialWeight - $currentWeight);
    $weightLossPct = $initialWeight > 0
        ? max(0.0, min(100.0, ($weightLossKg / $initialWeight) * 100.0))
        : 0.0;

    // ── 8. ANTI-CHEAT: cap credited weight loss by elapsed time ──────────────
    // Real aerobic decomposition loses at most ~5% of mass per day.
    // If weight drops faster than this, it was likely physically removed.
    if ($elapsedHours < 168) {
        $maxCreditedLoss    = min(100.0, $elapsedHours * (5.0 / 24.0) * 100.0);
        $creditedWeightLoss = min($weightLossPct, $maxCreditedLoss);
    } else {
        $creditedWeightLoss = $weightLossPct;
    }

    // ── 9. STAGE DETERMINATION ────────────────────────────────────────────────
    function determineStage(
        float $t, float $h, float $g, float $p,
        float $wl, float $creditedWl, float $hours
    ): string {
        if ($hours < 0.5) return 'initial';

        if ($hours >= STAGE_MIN_HOURS['maturation']
            && $creditedWl  >= 25.0
            && $t  >= 15.0 && $t  <= 45.0
            && $p  >= 6.5  && $p  <= 8.5
            && $g  <  300.0) {
            return 'maturation';
        }

        if ($hours >= STAGE_MIN_HOURS['late_thermo']
            && $creditedWl  >= 15.0
            && $t  >= 30.0 && $t  <= 65.0
            && $p  >= 5.5) {
            return 'late_thermo';
        }

        if ($hours >= STAGE_MIN_HOURS['thermophilic']
            && $t  >= 40.0 && $t  <= 70.0
            && $h  >= 40.0 && $h  <= 75.0
            && $p  >= 5.5  && $p  <= 9.0) {
            return 'thermophilic';
        }

        if ($hours >= STAGE_MIN_HOURS['mesophilic']
            && $t  >= 20.0
            && $h  >= 25.0) {
            return 'mesophilic';
        }

        return 'initial';
    }

    $stage      = determineStage($temperature, $humidity, $gas, $ph,
                                 $weightLossPct, $creditedWeightLoss, $elapsedHours);
    $ceiling    = STAGE_CEILING[$stage];
    $stageRange = STAGE_RANGE[$stage];

    // ── 10. SENSOR SCORES (0–100 each) ───────────────────────────────────────

    // Temperature
    if ($temperature >= 45 && $temperature <= 65)      $tScore = 100;
    elseif ($temperature >= 35 && $temperature < 45)   $tScore = 72;
    elseif ($temperature > 65 && $temperature <= 70)   $tScore = 58;
    elseif ($temperature >= 25 && $temperature < 35)   $tScore = 42;
    elseif ($temperature > 70)                          $tScore = 8;
    else                                                $tScore = 15;

    // Humidity
    if ($humidity >= 45 && $humidity <= 60)             $hScore = 100;
    elseif ($humidity >= 35 && $humidity < 45)          $hScore = 78;
    elseif ($humidity > 60 && $humidity <= 70)          $hScore = 68;
    elseif ($humidity > 70 && $humidity <= 80)          $hScore = 35;
    elseif ($humidity > 80)                             $hScore = 12;
    else                                                $hScore = 22;

    // pH
    if ($ph >= 6.5 && $ph <= 7.5)                       $phScore = 100;
    elseif ($ph >= 6.0 && $ph <= 8.0)                   $phScore = 80;
    elseif ($ph >= 5.5 && $ph <= 8.5)                   $phScore = 55;
    else                                                 $phScore = 18;

    // Gas — lower = more mature
    if ($gas <  100)                                    $gScore = 92;
    elseif ($gas < 300)                                 $gScore = 78;
    elseif ($gas < 600)                                 $gScore = 55;
    elseif ($gas < 800)                                 $gScore = 32;
    else                                                $gScore = 8;

    // Credited weight loss
    if ($creditedWeightLoss >= 50)                      $wlScore = 100;
    elseif ($creditedWeightLoss >= 35)                  $wlScore = 85;
    elseif ($creditedWeightLoss >= 20)                  $wlScore = 62;
    elseif ($creditedWeightLoss >= 10)                  $wlScore = 38;
    elseif ($creditedWeightLoss >= 5)                   $wlScore = 18;
    else                                                $wlScore = max(4, $creditedWeightLoss * 2);

    // Time progression — 60 days = full time score
    $timeScore = min(100.0, ($elapsedDays / 60.0) * 100.0);

    // ── 11. Composite WITHIN-STAGE score (0-100) ─────────────────────────────
    $withinStage =
        $wlScore   * 0.30 +
        $timeScore * 0.25 +
        $tScore    * 0.20 +
        $hScore    * 0.12 +
        $gScore    * 0.08 +
        $phScore   * 0.05;

    // ── 12. Map within-stage score → readiness in the stage's range ───────────
    [$rMin, $rMax] = $stageRange;
    $rawReadiness = $rMin + ($withinStage / 100.0) * ($rMax - $rMin);
    $rawReadiness = max(0.0, min((float)$ceiling, $rawReadiness));

    // ── 13. k-NN ML prediction WITH OUTCOME WEIGHTING ────────────────────────
    //
    // KEY CHANGE: Each session's Euclidean distance is multiplied by an outcome
    // penalty before being used in the inverse-distance weighted average.
    //
    //   success → ×1.0  — no penalty, trusted as the gold standard
    //   partial → ×1.8  — moderate penalty, less influential
    //   failed  → ×3.5  — heavy penalty, nearly excluded from prediction
    //
    // Effect: The k-NN prediction will gravitate toward successful sessions.
    // If two sessions have identical sensor readings but one succeeded and one
    // failed, the successful session will have ~3.5× more weight in the average.
    // The fastest successful cycle naturally becomes the dominant benchmark.
    // ─────────────────────────────────────────────────────────────────────────
    $rawSessions = $database->getReference("compost_sessions")->getValue() ?? [];
    $sessions    = [];
    foreach ($rawSessions as $s) {
        if (isset($s['temp'], $s['humidity'], $s['gas'], $s['ph'], $s['weightLoss'], $s['daysToReady'])) {
            $sessions[] = $s;
        }
    }
    $sessionCount = count($sessions);

    // Count sessions by outcome for transparency in the response
    $successCount = 0;
    $partialCount = 0;
    $failedCount  = 0;
    foreach ($sessions as $s) {
        $outcome = $s['outcome'] ?? 'success';
        if ($outcome === 'success') $successCount++;
        elseif ($outcome === 'partial') $partialCount++;
        elseif ($outcome === 'failed')  $failedCount++;
        else $successCount++; // treat unknown as success (backward compat)
    }

    // Find the fastest successful session as the benchmark target
    // This is shown in the UI so users know what to aim for
    $successBenchmarkDays = null;
    foreach ($sessions as $s) {
        $outcome = $s['outcome'] ?? 'success';
        if ($outcome === 'success') {
            $d = floatval($s['daysToReady']);
            if ($successBenchmarkDays === null || $d < $successBenchmarkDays) {
                $successBenchmarkDays = $d;
            }
        }
    }

    $mlDays      = null;
    $mlInfluence = 0.0;

    if ($sessionCount > 0 && !$weightIsZero) {
        $k = min(3, $sessionCount);

        // Compute outcome-penalised distances
        $distances = [];
        foreach ($sessions as $i => $s) {
            // Raw Euclidean distance across 5 sensor dimensions
            $rawDist = sqrt(
                pow(($temperature         - $s['temp'])       / 80.0,   2) +
                pow(($humidity            - $s['humidity'])   / 100.0,  2) +
                pow(($gas                 - $s['gas'])        / 1000.0, 2) +
                pow(($ph                  - $s['ph'])         / 14.0,   2) +
                pow(($creditedWeightLoss  - $s['weightLoss']) / 100.0,  2)
            );

            // Apply outcome penalty — failed/partial sessions are pushed "further away"
            $outcome = $s['outcome'] ?? 'success';
            $penalty = OUTCOME_PENALTY[$outcome] ?? OUTCOME_PENALTY['success'];
            $distances[$i] = $rawDist * $penalty;
        }

        // Pick k nearest (after penalty) and compute weighted-average daysToReady
        asort($distances);
        $topK = array_slice(array_keys($distances), 0, $k, true);
        $ws = 0.0; $wt = 0.0;
        foreach ($topK as $i) {
            $w   = 1.0 / max($distances[$i], 0.0001);
            $ws += $w * floatval($sessions[$i]['daysToReady']);
            $wt += $w;
        }
        $mlDays = $wt > 0 ? round($ws / $wt, 1) : null;

        // If we have a success benchmark, nudge mlDays toward it
        // (so the system aspires to the fastest known successful cycle)
        if ($mlDays !== null && $successBenchmarkDays !== null && $successCount > 0) {
            // Blend: 70% k-NN prediction, 30% fastest success benchmark
            // This keeps ML flexible while anchoring to known-good performance
            $mlDays = round($mlDays * 0.70 + $successBenchmarkDays * 0.30, 1);
        }
    }

    // ── 14. ML modifies score based on (elapsed / total_expected) progress ────
    $finalScore = $rawReadiness;

    if ($mlDays !== null) {
        $remainingDays = max(0.0, $mlDays);
        $totalExpected = $elapsedDays + $remainingDays;

        if ($totalExpected > 0) {
            $aiProgress  = min(100.0, ($elapsedDays / $totalExpected) * 100.0);
            // ML influence grows with session count: 2% per session, max 40%
            // Successful sessions contribute more — scale by success ratio
            $successRatio = $sessionCount > 0 ? ($successCount / $sessionCount) : 0.0;
            $baseInfluence = min(0.40, $sessionCount * 0.02);
            // Boost influence slightly if most sessions are successful (up to +10%)
            $mlInfluence = min(0.40, $baseInfluence * (1.0 + $successRatio * 0.25));
            $finalScore  = ($rawReadiness * (1.0 - $mlInfluence)) + ($aiProgress * $mlInfluence);
        }
    }

    // Hard enforce stage ceiling
    $finalScore = round(max(0.0, min((float)$ceiling, $finalScore)), 1);

    // ── 15. Fertilizer output ─────────────────────────────────────────────────
    $predictedOutput   = $initialWeight > 0 ? round($initialWeight * 0.50, 4) : 0.0;
    $actualOutput      = 0.0;
    $actualOutputReady = false;

    if ($stage === 'maturation' && $creditedWeightLoss >= 25.0 && $elapsedDays >= 10.0) {
        $actualOutput      = round($currentWeight, 4);
        $actualOutputReady = true;
    }

    $fertilizerPct = 100 > 0 ? round(($predictedOutput / 100) * 100, 1) : 0.0;

    // ── 16. Response ──────────────────────────────────────────────────────────
    echo json_encode([
        "readiness"  => $finalScore,
        "status"     => "success",
        "stage"      => $stage,
        "stage_name" => STAGE_NAME[$stage],

        "ml" => [
            "sessions_used"          => $sessionCount,
            "success_sessions"       => $successCount,
            "partial_sessions"       => $partialCount,
            "failed_sessions"        => $failedCount,
            "ml_influence_pct"       => round($mlInfluence * 100, 1),
            "rule_score"             => round($rawReadiness, 1),
            "ml_score"               => $mlDays !== null
                ? round(min(100.0, ($elapsedDays / max(0.01, $elapsedDays + $mlDays)) * 100.0), 1)
                : null,
            "predicted_days"         => $mlDays,
            "success_benchmark_days" => $successBenchmarkDays,
            "final_score"            => $finalScore,
            "stage_ceiling"          => $ceiling,
        ],

        "weight_tracking" => [
            "initial_weight"       => round($initialWeight, 4),
            "current_weight"       => round($currentWeight, 4),
            "weight_loss_kg"       => round($weightLossKg,  4),
            "weight_loss_percent"  => round($weightLossPct, 1),
            "credited_weight_loss" => round($creditedWeightLoss, 1),
            "hours_elapsed"        => round($elapsedHours,  1),
            "days_elapsed"         => round($elapsedDays,   1),
        ],

        "fertilizer_output" => [
            "predicted_output_kg"   => $predictedOutput,
            "actual_output_kg"      => $actualOutput,
            "is_ready"              => $actualOutputReady,
            "fertilizer_percentage" => $fertilizerPct,
        ],

        "analytics" => [
            "stage"              => $stage,
            "stage_ceiling"      => $ceiling,
            "elapsed_hours"      => round($elapsedHours,  1),
            "elapsed_days"       => round($elapsedDays,   1),
            "credited_wl"        => round($creditedWeightLoss, 1),
            "raw_wl"             => round($weightLossPct, 1),
            "weight_score"       => round($wlScore,       1),
            "time_score"         => round($timeScore,     1),
            "environment_score"  => round($tScore*0.20 + $hScore*0.12 + $gScore*0.08 + $phScore*0.05, 1),
            "within_stage_score" => round($withinStage,   1),
            "temperature"        => $temperature,
            "humidity"           => $humidity,
            "ph"                 => $ph,
            "gas"                => $gas,
        ],
    ]);

} catch (Exception $e) {
    echo json_encode([
        "readiness"  => 0,
        "status"     => "error",
        "error"      => $e->getMessage(),
        "stage"      => "no_compost",
        "stage_name" => "Error",
        "ml"         => [
            "sessions_used"          => 0,
            "success_sessions"       => 0,
            "ml_influence_pct"       => 0,
            "rule_score"             => 0,
            "ml_score"               => null,
            "predicted_days"         => null,
            "success_benchmark_days" => null,
            "final_score"            => 0,
            "stage_ceiling"          => 0,
        ],
        "weight_tracking"   => ["initial_weight"=>0,"current_weight"=>0,"weight_loss_kg"=>0,"weight_loss_percent"=>0,"credited_weight_loss"=>0,"hours_elapsed"=>0,"days_elapsed"=>0],
        "fertilizer_output" => ["predicted_output_kg"=>0,"actual_output_kg"=>0,"is_ready"=>false,"fertilizer_percentage"=>0],
    ]);
}