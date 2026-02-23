<?php
/**
 * predict_readiness.php — Fixed: readiness only valid when weight > 0
 * 
 * KEY FIXES:
 * 1. If current weight = 0 AND no fertilizer session saved → readiness = 0 (not ready)
 * 2. Weight must show meaningful presence before composting can be "ready"
 * 3. Fertilizer output only shows when weight has been properly tracked
 * 4. Stage detection accounts for zero-weight edge case
 */

header("Content-Type: application/json");
error_reporting(0);
ini_set('display_errors', 0);

require_once 'firebase_config.php';

try {
    $database = getDatabase();

    // ── Fetch live sensors ────────────────────────────────────────────────────
    $temperature   = floatval($database->getReference("sensors/temperature/latest")->getValue() ?? 0);
    $humidity      = floatval($database->getReference("sensors/humidity/latest")->getValue() ?? 0);
    $gas           = floatval($database->getReference("sensors/gas/latest")->getValue() ?? 0);
    $ph            = floatval($database->getReference("sensors/ph/latest")->getValue() ?? 0);
    $currentWeight = floatval($database->getReference("sensors/weight/latest")->getValue() ?? 0);
    $initialWeight = floatval($database->getReference("sensors/weight/initial")->getValue() ?? 0);

    // ── Check if user has already saved/harvested a session ──────────────────
    // If weight is 0 but a session was saved, it means user harvested
    $lastSession = $database->getReference("sensors/weight/last_session")->getValue();
    $sessionHarvested = ($lastSession !== null && isset($lastSession['harvested']) && $lastSession['harvested'] === true);

    // ── CRITICAL FIX: If current weight is 0 and no harvest session saved ────
    // This means bin is empty / not started → readiness = 0
    $weightIsZero = ($currentWeight <= 0);
    $hasNoCompost = $weightIsZero && !$sessionHarvested;

    if ($hasNoCompost) {
        // Return 0% ready — no compost in bin
        echo json_encode([
            "readiness" => 0,
            "status"    => "no_compost",
            "message"   => "No compost detected. Add organic waste to begin composting.",

            "ml" => [
                "sessions_used"    => 0,
                "ml_influence_pct" => 0,
                "rule_score"       => 0,
                "ml_score"         => null,
                "predicted_days"   => null,
                "final_score"      => 0,
            ],

            "weight_tracking" => [
                "initial_weight"      => 0,
                "current_weight"      => 0,
                "weight_loss_kg"      => 0,
                "weight_loss_percent" => 0,
            ],

            "fertilizer_output" => [
                "predicted_output_kg"   => 0,
                "actual_output_kg"      => 0,
                "is_ready"              => false,
                "fertilizer_percentage" => 0,
            ],

            "analytics" => [
                "stage"             => "No Compost",
                "environment_score" => 0,
                "weight_score"      => 0,
                "temperature"       => $temperature,
                "humidity"          => $humidity,
                "ph"                => $ph,
                "gas"               => $gas,
            ],
        ]);
        exit;
    }

    // ── Auto-init initial weight (only when weight > 0) ──────────────────────
    if ($initialWeight <= 0 && $currentWeight > 0) {
        $database->getReference("sensors/weight/initial")->set($currentWeight);
        $initialWeight = $currentWeight;
    }

    // If current weight somehow exceeds initial, update initial
    if ($currentWeight > $initialWeight && $initialWeight > 0) {
        $database->getReference("sensors/weight/initial")->set($currentWeight);
        $initialWeight = $currentWeight;
    }

    // Weight loss
    $weightLossKg = max(0, $initialWeight - $currentWeight);
    $weightLoss   = $initialWeight > 0
        ? max(0, min(100, ($weightLossKg / $initialWeight) * 100))
        : 0;

    // ── MINIMUM WEIGHT THRESHOLD ──────────────────────────────────────────────
    // Compost needs at least 1kg to start meaningful decomposition scoring
    $MINIMUM_WEIGHT_KG = 1.0;
    if ($currentWeight < $MINIMUM_WEIGHT_KG && !$sessionHarvested) {
        $weightThresholdPenalty = true;
    } else {
        $weightThresholdPenalty = false;
    }

    // ── Rule-based score ──────────────────────────────────────────────────────
    $score = 0;

    // Temperature (20 pts)
    if ($temperature >= 45 && $temperature <= 70)     $score += 20;
    elseif ($temperature >= 20 && $temperature < 45)  $score += 12;
    elseif ($temperature < 40 && $temperature >= 15)  $score += 15;

    // Humidity (15 pts)
    if ($humidity >= 40 && $humidity <= 60)            $score += 15;
    elseif ($humidity >= 30 && $humidity <= 70)        $score += 10;
    elseif ($humidity >= 20 && $humidity <= 80)        $score += 5;

    // pH (15 pts)
    if ($ph >= 6.5 && $ph <= 8.0)                     $score += 15;
    elseif ($ph >= 6.0 && $ph <= 8.5)                 $score += 10;
    elseif ($ph >= 5.5 && $ph < 6.0)                  $score += 7;

    // Gas (10 pts)
    if ($gas < 100)                                    $score += 10;
    elseif ($gas < 300)                                $score += 8;
    elseif ($gas < 600)                                $score += 5;
    elseif ($gas < 1000)                               $score += 2;

    // Weight loss (40 pts) — KEY: if weight is 0 and not harvested, this stays 0
    if ($weightLoss >= 40)      $weightScore = 40;
    elseif ($weightLoss >= 30)  $weightScore = 30;
    elseif ($weightLoss >= 20)  $weightScore = 20;
    elseif ($weightLoss >= 10)  $weightScore = 10;
    else                        $weightScore = ($weightLoss / 10) * 10;
    $score += $weightScore;

    // ── CRITICAL FIX: Cap score if weight hasn't meaningfully decreased ──────
    // If weight loss is 100% but current weight is 0 and no harvest → cap at 5%
    // This prevents showing "ready" when bin is empty without a saved session
    if ($weightIsZero && !$sessionHarvested) {
        $score = min($score, 5);
        $weightScore = 0;
    }

    // Apply threshold penalty for very low weight
    if ($weightThresholdPenalty) {
        $score = min($score, 15); // Max 15% when weight is below threshold
    }

    // Stage detection
    $stage = "Unknown";
    if ($weightIsZero && !$sessionHarvested) {
        $stage = "No Compost";
    } elseif ($temperature >= 45 && $temperature <= 70 && $ph >= 6.5 && $ph <= 8.0 && $humidity >= 40 && $humidity <= 60) {
        $stage = "Thermophilic";
        if ($weightLoss < 15) $score -= 5;
    } elseif ($temperature < 40 && $ph >= 7.0 && $ph <= 8.0 && $gas < 100) {
        $stage = "Maturation";
        $score += ($weightLoss < 30) ? -10 : 5;
    } elseif ($temperature >= 20 && $temperature < 45 && $ph >= 5.5 && $ph < 6.5) {
        $stage = "Mesophilic";
    }

    $ruleScore = max(0, min(100, $score));

    // ── Load past sessions from Firebase ─────────────────────────────────────
    $rawSessions  = $database->getReference("compost_sessions")->getValue() ?? [];
    $sessions     = [];
    foreach ($rawSessions as $s) {
        if (isset($s['temp'], $s['humidity'], $s['gas'], $s['ph'], $s['weightLoss'], $s['daysToReady'])) {
            $sessions[] = $s;
        }
    }
    $sessionCount = count($sessions);

    // ── k-NN: predict days to ready from past sessions ────────────────────────
    $mlDays      = null;
    $mlScore     = null;
    $mlInfluence = 0;

    if ($sessionCount > 0 && !$weightIsZero) {
        $k = min(3, $sessionCount);

        $distances = [];
        foreach ($sessions as $i => $s) {
            $distances[$i] = sqrt(
                pow(($temperature - $s['temp'])      / 80,   2) +
                pow(($humidity    - $s['humidity'])   / 100,  2) +
                pow(($gas         - $s['gas'])        / 1000, 2) +
                pow(($ph          - $s['ph'])         / 14,   2) +
                pow(($weightLoss  - $s['weightLoss']) / 100,  2)
            );
        }
        asort($distances);
        $topK = array_slice(array_keys($distances), 0, $k, true);

        $ws = 0; $wt = 0;
        foreach ($topK as $i) {
            $w   = 1 / max($distances[$i], 0.0001);
            $ws += $w * $sessions[$i]['daysToReady'];
            $wt += $w;
        }
        $mlDays = $wt > 0 ? round($ws / $wt, 1) : null;

        if ($mlDays !== null) {
            $maxDays     = 60;
            $mlScore     = max(0, min(100, (1 - ($mlDays / $maxDays)) * 100));
            $mlInfluence = min(0.40, $sessionCount * 0.02);
        }
    }

    // ── Blend rule-based + ML scores ─────────────────────────────────────────
    if ($mlScore !== null && $mlInfluence > 0 && !$weightIsZero) {
        $finalScore = ($ruleScore * (1 - $mlInfluence)) + ($mlScore * $mlInfluence);
    } else {
        $finalScore = $ruleScore;
    }
    $finalScore = round(max(0, min(100, $finalScore)), 1);

    // ── CRITICAL: Final safety check — no weight = no readiness ─────────────
    // Unless a session was harvested (weight intentionally set to 0 after harvest)
    if ($weightIsZero && !$sessionHarvested) {
        $finalScore = 0;
    }

    // ── Fertilizer output ─────────────────────────────────────────────────────
    // Only compute if there's actual weight to base it on
    if ($initialWeight > 0) {
        $predictedOutput = max(0, $initialWeight - ($initialWeight * 0.5 * ($finalScore / 100)));
    } else {
        $predictedOutput = 0;
    }

    $actualOutput      = 0;
    $actualOutputReady = false;
    if ($finalScore >= 90 && $weightLoss >= 40 && $currentWeight > 0) {
        $actualOutput      = $currentWeight;
        $actualOutputReady = true;
    }

    $capacity      = 100;
    $fertilizerPct = $capacity > 0 ? ($predictedOutput / $capacity) * 100 : 0;

    // ── Response ──────────────────────────────────────────────────────────────
    echo json_encode([
        "readiness" => $finalScore,
        "status"    => "success",

        "ml" => [
            "sessions_used"    => $sessionCount,
            "ml_influence_pct" => round($mlInfluence * 100, 1),
            "rule_score"       => $ruleScore,
            "ml_score"         => $mlScore,
            "predicted_days"   => $mlDays,
            "final_score"      => $finalScore,
        ],

        "weight_tracking" => [
            "initial_weight"      => round($initialWeight, 4),
            "current_weight"      => round($currentWeight, 4),
            "weight_loss_kg"      => round($weightLossKg, 4),
            "weight_loss_percent" => round($weightLoss, 1),
        ],

        "fertilizer_output" => [
            "predicted_output_kg"   => round($predictedOutput, 4),
            "actual_output_kg"      => round($actualOutput, 4),
            "is_ready"              => $actualOutputReady,
            "fertilizer_percentage" => round($fertilizerPct, 1),
        ],

        "analytics" => [
            "stage"             => $stage,
            "environment_score" => round($score - $weightScore, 1),
            "weight_score"      => round($weightScore, 1),
            "temperature"       => $temperature,
            "humidity"          => $humidity,
            "ph"                => $ph,
            "gas"               => $gas,
        ],
    ]);

} catch (Exception $e) {
    echo json_encode([
        "readiness" => 0,
        "status"    => "error",
        "error"     => $e->getMessage(),
        "ml"        => ["sessions_used" => 0, "ml_influence_pct" => 0],
        "weight_tracking"   => ["initial_weight"=>0,"current_weight"=>0,"weight_loss_kg"=>0,"weight_loss_percent"=>0],
        "fertilizer_output" => ["predicted_output_kg"=>0,"actual_output_kg"=>0,"is_ready"=>false,"fertilizer_percentage"=>0],
    ]);
}