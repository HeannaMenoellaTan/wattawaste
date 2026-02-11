<?php
/**
 * Enhanced Compost Readiness with Fertilizer Output Calculation
 * 
 * Weight Tracking Logic:
 * 1. Current Weight = Real-time sensor data from bin
 * 2. Initial Weight = Weight when composting batch started
 * 3. Weight Loss = Initial - Current (material that decomposed/evaporated)
 * 4. Fertilizer Output = Predicted finished compost weight (accounts for further decomposition)
 */

header("Content-Type: application/json");
require_once 'firebase_config.php';

try {
    $database = getDatabase();
    
    // ========== FETCH SENSOR DATA ==========
    $temperature = floatval($database->getReference("sensors/temperature/latest")->getValue() ?? 0);
    $humidity = floatval($database->getReference("sensors/humidity/latest")->getValue() ?? 0);
    $gas = floatval($database->getReference("sensors/gas/latest")->getValue() ?? 0);
    $ph = floatval($database->getReference("sensors/ph/latest")->getValue() ?? 0);
    $currentWeight = floatval($database->getReference("sensors/weight/latest")->getValue() ?? 0);
    
    // ========== WEIGHT TRACKING ==========
    $initialWeight = floatval($database->getReference("sensors/weight/initial")->getValue() ?? null);
    
    // Initialize or reset initial weight when new batch is added
    if ($initialWeight === null || $currentWeight > $initialWeight) {
        $database->getReference("sensors/weight/initial")->set($currentWeight);
        $initialWeight = $currentWeight;
    }
    
    // Calculate weight loss (what has already decomposed)
    $weightLoss = 0;
    $weightLossKg = 0;
    if ($initialWeight > 0) {
        $weightLossKg = $initialWeight - $currentWeight;
        $weightLoss = ($weightLossKg / $initialWeight) * 100;
        $weightLoss = max(0, min(100, $weightLoss));
    }
    
    // ========== SCORING SYSTEM ==========
    $score = 0;
    
    // Temperature (20 points)
    if ($temperature >= 45 && $temperature <= 70) {
        $score += 20;
    } elseif ($temperature >= 20 && $temperature < 45) {
        $score += 12;
    } elseif ($temperature < 40 && $temperature >= 15) {
        $score += 15;
    }
    
    // Humidity (15 points)
    if ($humidity >= 40 && $humidity <= 60) {
        $score += 15;
    } elseif ($humidity >= 30 && $humidity <= 70) {
        $score += 10;
    } elseif ($humidity >= 20 && $humidity <= 80) {
        $score += 5;
    }
    
    // pH Level (15 points)
    if ($ph >= 6.5 && $ph <= 8.0) {
        $score += 15;
    } elseif ($ph >= 6.0 && $ph <= 8.5) {
        $score += 10;
    } elseif ($ph >= 5.5 && $ph < 6.0) {
        $score += 7;
    }
    
    // Gas Level (10 points)
    if ($gas < 100) {
        $score += 10;
    } elseif ($gas < 300) {
        $score += 8;
    } elseif ($gas < 600) {
        $score += 5;
    } elseif ($gas < 1000) {
        $score += 2;
    }
    
    // Weight Loss Progress (40 points)
    if ($weightLoss >= 40) {
        $weightScore = 40;
    } elseif ($weightLoss >= 30) {
        $weightScore = 30;
    } elseif ($weightLoss >= 20) {
        $weightScore = 20;
    } elseif ($weightLoss >= 10) {
        $weightScore = 10;
    } else {
        $weightScore = ($weightLoss / 10) * 10;
    }
    $score += $weightScore;
    
    // ========== STAGE-BASED VALIDATION ==========
    $stage = "Unknown";
    
    if ($temperature >= 45 && $temperature <= 70 && $ph >= 6.5 && $ph <= 8.0 && $humidity >= 40 && $humidity <= 60) {
        $stage = "Thermophilic";
        if ($weightLoss < 15) {
            $score -= 5;
        }
    } elseif ($temperature < 40 && $ph >= 7.0 && $ph <= 8.0 && $gas < 100) {
        $stage = "Maturation";
        if ($weightLoss < 30) {
            $score -= 10;
        } else {
            $score += 5;
        }
    } elseif ($temperature >= 20 && $temperature < 45 && $ph >= 5.5 && $ph < 6.5) {
        $stage = "Mesophilic";
    }
    
    // Final readiness score
    $finalScore = max(0, min(100, $score));
    
    // ========== FERTILIZER OUTPUT CALCULATION ==========
    /**
     * Composting typically results in 40-60% weight loss
     * Final fertilizer = Starting weight × (1 - expected_loss_rate)
     * 
     * We calculate TWO values:
     * 1. Predicted Output = What we expect based on current progress
     * 2. Actual Output = Only shown when compost is 100% ready
     */
    
    // Expected final weight based on typical 50% loss for finished compost
    $expectedFinalWeight = $initialWeight * 0.5;
    
    // Predicted output = Adjust based on current readiness
    // If 50% ready, we predict it will lose half of the expected loss
    $predictedOutput = $initialWeight - ($initialWeight * 0.5 * ($finalScore / 100));
    $predictedOutput = max(0, $predictedOutput);
    
    // Actual output = Only when truly ready (≥90% readiness AND ≥40% weight loss)
    $actualOutput = 0;
    $actualOutputReady = false;
    
    if ($finalScore >= 90 && $weightLoss >= 40) {
        $actualOutput = $currentWeight; // The current weight IS the finished compost
        $actualOutputReady = true;
    }
    
    // Calculate fertilizer output as percentage of capacity
    $capacity = 100; // 100 kg maximum capacity
    $fertilizerPercentage = ($predictedOutput / $capacity) * 100;
    
    // ========== RESPONSE ==========
    echo json_encode([
        "readiness" => round($finalScore, 1),
        "status" => "success",
        "weight_tracking" => [
            "initial_weight" => round($initialWeight, 4),
            "current_weight" => round($currentWeight, 4),
            "weight_loss_kg" => round($weightLossKg, 4),
            "weight_loss_percent" => round($weightLoss, 1)
        ],
        "fertilizer_output" => [
            "predicted_output_kg" => round($predictedOutput, 4),
            "actual_output_kg" => round($actualOutput, 4),
            "is_ready" => $actualOutputReady,
            "fertilizer_percentage" => round($fertilizerPercentage, 1)
        ],
        "analytics" => [
            "stage" => $stage,
            "environment_score" => round($score - $weightScore, 1),
            "weight_score" => round($weightScore, 1),
            "temperature" => $temperature,
            "humidity" => $humidity,
            "ph" => $ph,
            "gas" => $gas
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "readiness" => 0,
        "status" => "error",
        "error" => $e->getMessage(),
        "weight_tracking" => [
            "initial_weight" => 0,
            "current_weight" => 0,
            "weight_loss_kg" => 0,
            "weight_loss_percent" => 0
        ],
        "fertilizer_output" => [
            "predicted_output_kg" => 0,
            "actual_output_kg" => 0,
            "is_ready" => false,
            "fertilizer_percentage" => 0
        ]
    ]);
}
?>