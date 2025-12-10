<?php
require_once 'firebase_config.php';

/**
 * Write sensor data to Firebase
 */
function writeSensorData($sensorType, $value) {
    try {
        $database = getDatabase();
        $timestamp = round(microtime(true) * 1000);
        
        // Update latest value
        $database->getReference("sensors/{$sensorType}/latest")
            ->set([
                'value' => (float)$value,
                'timestamp' => $timestamp
            ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Firebase write error: " . $e->getMessage());
        return false;
    }
}

/**
 * Write weight data to Firebase
 */
function writeWeightData($current, $capacity) {
    try {
        $database = getDatabase();
        $timestamp = round(microtime(true) * 1000);
        
        $database->getReference("sensors/weight/latest")
            ->set([
                'current' => (float)$current,
                'capacity' => (float)$capacity,
                'timestamp' => $timestamp
            ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Firebase weight error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get latest sensor readings from Firebase
 */
function getLatestSensorData() {
    try {
        $database = getDatabase();
        $snapshot = $database->getReference('sensors')->getValue();
        
        if (!$snapshot) {
            return [
                'temperature' => 0,
                'humidity' => 0,
                'gas' => 0,
                'ph' => 0,
                'weight_current' => 0,
                'weight_capacity' => 0
            ];
        }
        
        return [
            'temperature' => $snapshot['temperature']['latest']['value'] ?? 0,
            'humidity' => $snapshot['humidity']['latest']['value'] ?? 0,
            'gas' => $snapshot['gas']['latest']['value'] ?? 0,
            'ph' => $snapshot['ph']['latest']['value'] ?? 0,
            'weight_current' => $snapshot['weight']['latest']['current'] ?? 0,
            'weight_capacity' => $snapshot['weight']['latest']['capacity'] ?? 0
        ];
    } catch (Exception $e) {
        error_log("Firebase read error: " . $e->getMessage());
        return null;
    }
}

/**
 * Update compost stage
 */
function updateCompostStage($stage, $description) {
    try {
        $database = getDatabase();
        $timestamp = round(microtime(true) * 1000);
        
        $database->getReference('compost_stages/current')
            ->set([
                'stage' => $stage,
                'description' => $description,
                'timestamp' => $timestamp
            ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Firebase stage update error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get mixer status from Firebase
 */
function getMixerStatus() {
    try {
        $database = getDatabase();
        $snapshot = $database->getReference('mixer')->getValue();
        
        $today = date('Y-m-d');
        $lastDate = $snapshot['last_date'] ?? '';
        
        // Reset count if it's a new day
        if ($lastDate !== $today) {
            $database->getReference('mixer')->update([
                'today_count' => 0,
                'last_date' => $today,
                'status' => false
            ]);
            return ['status' => false, 'count' => 0];
        }
        
        return [
            'status' => $snapshot['status'] ?? false,
            'count' => $snapshot['today_count'] ?? 0
        ];
    } catch (Exception $e) {
        error_log("Firebase mixer status error: " . $e->getMessage());
        return ['status' => false, 'count' => 0];
    }
}

/**
 * Toggle mixer (with 2x daily limit enforced server-side)
 */
function toggleMixer($turnOn) {
    try {
        $database = getDatabase();
        $status = getMixerStatus();
        
        if ($turnOn) {
            // Check if limit reached
            if ($status['count'] >= 2) {
                return [
                    'success' => false, 
                    'message' => '⚠️ Daily limit reached (2/2)',
                    'count' => $status['count']
                ];
            }
            
            // Turn ON
            $database->getReference('mixer')->update([
                'status' => true,
                'today_count' => $status['count'] + 1,
                'last_date' => date('Y-m-d'),
                'last_updated' => round(microtime(true) * 1000)
            ]);
            
            return [
                'success' => true, 
                'message' => "✅ Mixer turned ON (" . ($status['count'] + 1) . "/2)",
                'count' => $status['count'] + 1,
                'status' => true
            ];
        } else {
            // Turn OFF
            $database->getReference('mixer')->update([
                'status' => false,
                'last_updated' => round(microtime(true) * 1000)
            ]);
            
            return [
                'success' => true, 
                'message' => '🛑 Mixer turned OFF',
                'status' => false,
                'count' => $status['count']
            ];
        }
    } catch (Exception $e) {
        error_log("Firebase mixer toggle error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Update fertilizer predictions
 */
function updateFertilizerData($readiness, $predictedOutput, $actualOutput = 0) {
    try {
        $database = getDatabase();
        
        $database->getReference('fertilizer')
            ->set([
                'readiness' => (float)$readiness,
                'predicted_output' => (float)$predictedOutput,
                'actual_output' => (float)$actualOutput,
                'timestamp' => round(microtime(true) * 1000)
            ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Firebase fertilizer update error: " . $e->getMessage());
        return false;
    }
}
?>