<?php
require_once 'firebase_config.php';

// ==== Get Latest Weight Data from Firebase ====
try {
    $database = getDatabase();
    
    // Get latest weight
    $latestRef = $database->getReference('sensors/weight/latest');
    $latestSnapshot = $latestRef->getSnapshot();
    
    if ($latestSnapshot->exists()) {
        $latestData = $latestSnapshot->getValue();
        $currentWeight = $latestData['value'] ?? 0;
        $weightCapacity = $latestData['capacity'] ?? 50;
        $lastUpdate = $latestData['timestamp'] ?? time();
    } else {
        $currentWeight = 0;
        $weightCapacity = 50;
        $lastUpdate = time();
    }
    
    // Calculate fertilizer output (50% of current weight)
    $fertilizerOutput = round($currentWeight * 0.5, 2);
    
    // Determine if bin is almost full (>80%)
    $weightPercentage = ($currentWeight / $weightCapacity) * 100;
    $showWarning = $weightPercentage >= 80;
    
    // ==== Fetch Weight History from Firebase ====
    $historyRef = $database->getReference('sensors/weight/history');
    $historySnapshot = $historyRef->getSnapshot();
    
    $historyData = [];
    if ($historySnapshot->exists()) {
        foreach ($historySnapshot->getValue() as $key => $item) {
            $historyData[] = [
                'timestamp' => $item['timestamp'] ?? time(),
                'value' => $item['value'] ?? 0
            ];
        }
    }
    
    // Sort by timestamp (newest first) and limit to 50
    usort($historyData, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    $historyData = array_slice($historyData, 0, 50);
    
    // Calculate statistics
    $avgWeight = 0;
    $maxWeight = 0;
    $minWeight = $weightCapacity;
    if (!empty($historyData)) {
        $weights = array_column($historyData, 'value');
        $avgWeight = array_sum($weights) / count($weights);
        $maxWeight = max($weights);
        $minWeight = min($weights);
    }
    
} catch (Exception $e) {
    error_log("Weight page error: " . $e->getMessage());
    $currentWeight = 0;
    $weightCapacity = 50;
    $fertilizerOutput = 0;
    $showWarning = false;
    $historyData = [];
    $lastUpdate = time();
    $avgWeight = 0;
    $maxWeight = 0;
    $minWeight = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Weight Level | WattAWaste</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://kit.fontawesome.com/a2e0e6ad65.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Firebase Auth -->
<script type="module">
import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
import { getAuth, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';

const firebaseConfig = {
    apiKey: "AIzaSyAu9hOwjiuAl9PCh50HefMGZU9XDosu68I",
    authDomain: "wattawaste-d3503.firebaseapp.com",
    databaseURL: "https://wattawaste-d3503-default-rtdb.asia-southeast1.firebasedatabase.app",
    projectId: "wattawaste-d3503",
    storageBucket: "wattawaste-d3503.firebasestorage.app",
    messagingSenderId: "842761118644",
    appId: "1:842761118644:web:ddef65fd892486f67f88e1",
    measurementId: "G-33Z8K3NBY1"
};

const app = initializeApp(firebaseConfig);
const auth = getAuth(app);

onAuthStateChanged(auth, (user) => {
    if (!user) {
        console.log('❌ No user found, redirecting to login...');
        window.location.href = 'login.php';
    } else {
        console.log('✅ User authenticated:', user.email || user.phoneNumber);
        sessionStorage.setItem('userEmail', user.email || user.phoneNumber || '');
        sessionStorage.setItem('userId', user.uid);
    }
});

window.firebaseAuth = auth;
</script>

<?php include_once 'notif_bell.php'; ?>

<style>
:root {
    --brand: #4CAF50;
    --brand-dark: #2E7D32;
    --ink: #333;
    --panel: #fff;
    --muted: #555;
    --bg: #F9FAFB;
    --warning: #f59e0b;
    --danger: #ef4444;
}

body { 
    font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: var(--bg);
    margin: 0;
    padding: 0;
    color: var(--ink);
    min-height: 100vh;
}

.main { 
    padding: 30px;
    max-width: 1400px;
    margin: 0 auto;
}

.card {
    border: none;
    border-radius: 16px;
    background: var(--panel);
    box-shadow: 0 6px 16px rgba(2, 6, 23, .06);
    transition: transform .2s, box-shadow .2s;
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 26px rgba(2, 6, 23, .12);
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    color: var(--ink);
    margin-bottom: 24px;
}

/* Warning Popup */
.warning-popup {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border: 3px solid #ff6b6b;
    border-radius: 15px;
    padding: 30px 40px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    z-index: 10000;
    text-align: center;
    min-width: 400px;
    animation: slideDown 0.4s ease;
}

.warning-popup.show {
    display: block;
}

.warning-popup-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
}

.warning-popup-overlay.show {
    display: block;
}

.warning-popup i {
    color: #ff6b6b;
    font-size: 48px;
    margin-bottom: 15px;
    animation: pulse 2s infinite;
}

.warning-popup h3 {
    color: #d63031;
    font-size: 20px;
    margin: 10px 0;
}

.warning-popup p {
    color: #636e72;
    margin-bottom: 20px;
}

.warning-popup button {
    background: #ff6b6b;
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.3s ease;
}

.warning-popup button:hover {
    background: #ee5a6f;
}

@keyframes slideDown {
    from {
        transform: translate(-50%, -60%);
        opacity: 0;
    }
    to {
        transform: translate(-50%, -50%);
        opacity: 1;
    }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Cards Container */
.cards-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-bottom: 30px;
}

.weight-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 6px 16px rgba(2, 6, 23, .06);
    transition: transform .2s, box-shadow .2s;
}

.weight-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 26px rgba(2, 6, 23, .12);
}

.weight-card h3 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 20px;
    color: var(--ink);
}

.weight-value {
    font-size: 42px;
    font-weight: bold;
    color: var(--brand-dark);
    margin-bottom: 5px;
}

.weight-capacity {
    font-size: 14px;
    color: var(--muted);
    margin-bottom: 15px;
}

/* Progress Bar */
.progress-bar-container {
    width: 100%;
    height: 12px;
    background: #e8e8e8;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 10px;
}

.progress-bar-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 0.5s ease, background 0.3s ease;
}

.stat-card {
    text-align: center;
    padding: 20px;
}

.stat-value {
    font-size: 36px;
    font-weight: 800;
    color: var(--brand-dark);
    line-height: 1;
}

.stat-label {
    font-size: 14px;
    color: var(--muted);
    margin-top: 8px;
}

/* History Items - Temperature Page Style */
.history-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 16px;
    margin-bottom: 8px;
    background: #f8fafc;
    border-radius: 12px;
    border-left: 4px solid var(--brand);
    transition: all 0.2s;
}

.history-item:hover {
    background: #f1f5f9;
    transform: translateX(4px);
}

.history-value {
    font-size: 22px;
    font-weight: 700;
    color: var(--brand-dark);
}

.history-time {
    font-size: 13px;
    color: var(--muted);
}

.small-muted {
    color: var(--muted);
    font-size: 14px;
}

.alert-custom {
    padding: 16px 20px;
    border-radius: 12px;
    border-left: 4px solid;
    font-weight: 500;
}

.alert-custom.warning {
    background: #FFF8E1;
    color: #7A5A00;
    border-color: var(--warning);
}

@media (max-width: 768px) {
    .cards-row {
        grid-template-columns: 1fr;
    }
    
    .warning-popup {
        min-width: 300px;
        padding: 20px 30px;
    }
    
    .history-value {
        font-size: 18px;
    }
    
    .history-time {
        font-size: 11px;
    }
}
</style>
</head>
<body>

<?php include 'sideabr.php'; ?>

<div class="main">
<?php include 'topnav.php'; ?>

<div class="container-fluid px-4 py-4">

<!-- Warning Popup Overlay -->
<?php if ($showWarning): ?>
<div class="warning-popup-overlay show" id="warningOverlay" onclick="closeWarning()"></div>
<div class="warning-popup show" id="warningPopup">
    <i class="fas fa-exclamation-triangle"></i>
    <h3>⚠️ Warning!</h3>
    <p>The bin is almost full (<?= number_format($weightPercentage, 1) ?>%)!</p>
    <button onclick="closeWarning()">Got it</button>
</div>
<?php endif; ?>

<h1 class="page-title">⚖️ Weight Monitoring</h1>

<!-- Original Two-Card Design -->
<div class="cards-row">
    <!-- Current Weight Card -->
    <div class="weight-card">
        <h3><i class="fas fa-weight me-2"></i>Current Weight</h3>
        <div class="weight-value" id="currentWeightDisplay"><?= number_format($currentWeight, 2) ?> kg</div>
        <div class="weight-capacity">Capacity: <?= $weightCapacity ?> kg</div>
        <div class="progress-bar-container">
            <div class="progress-bar-fill" id="weightProgress" style="
                width: <?= min(100, $weightPercentage) ?>%; 
                background: <?= $weightPercentage >= 80 ? 'linear-gradient(90deg, #ff6b6b, #ee5a6f)' : 
                              ($weightPercentage >= 60 ? 'linear-gradient(90deg, #ffd93d, #f6c23e)' : 
                              'linear-gradient(90deg, #6fcf97, #27ae60)') ?>;
            "></div>
        </div>
        <div class="mt-2 text-muted" style="font-size: 13px;">
            <?= number_format($weightPercentage, 1) ?>% Full
        </div>
    </div>
    
    <!-- Total Compost Fertilizer Card -->
    <div class="weight-card">
        <h3><i class="fas fa-seedling me-2"></i>Total Compost Fertilizer</h3>
        <div class="weight-value" id="fertilizerDisplay"><?= number_format($fertilizerOutput, 1) ?> kg</div>
        <div class="weight-capacity">Current yield (50% conversion)</div>
        <div class="progress-bar-container">
            <div class="progress-bar-fill" id="fertilizerProgress" style="
                width: <?= min(100, ($fertilizerOutput / ($weightCapacity * 0.5)) * 100) ?>%; 
                background: linear-gradient(90deg, #ffd93d, #f6c23e);
            "></div>
        </div>
        <div class="mt-2 text-muted" style="font-size: 13px;">
            Maximum: <?= number_format($weightCapacity * 0.5, 1) ?> kg
        </div>
    </div>
</div>

<?php if ($weightPercentage >= 80): ?>
<div class="alert-custom warning mb-4">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Warning:</strong> The bin is reaching capacity (<?php echo number_format($weightPercentage, 1); ?>%). Consider emptying soon.
</div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <i class="fas fa-weight-hanging text-success mb-2" style="font-size: 32px;"></i>
            <div class="stat-value"><?php echo number_format($maxWeight, 2); ?> kg</div>
            <div class="stat-label">Maximum Weight</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <i class="fas fa-chart-line text-primary mb-2" style="font-size: 32px;"></i>
            <div class="stat-value"><?php echo number_format($avgWeight, 2); ?> kg</div>
            <div class="stat-label">Average Weight</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <i class="fas fa-arrow-down text-info mb-2" style="font-size: 32px;"></i>
            <div class="stat-value"><?php echo number_format($minWeight, 2); ?> kg</div>
            <div class="stat-label">Minimum Weight</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <i class="fas fa-seedling text-warning mb-2" style="font-size: 32px;"></i>
            <div class="stat-value"><?php echo number_format($fertilizerOutput, 1); ?> kg</div>
            <div class="stat-label">Fertilizer Output</div>
        </div>
    </div>
</div>

<!-- Weight Trend Chart -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card p-4">
            <h5 class="mb-3">
                <i class="fas fa-chart-area text-primary me-2"></i>
                Weight Trend (Last <?php echo count($historyData); ?> Readings)
            </h5>
            <canvas id="weightChart" style="max-height: 350px;"></canvas>
        </div>
    </div>
</div>

<!-- Recent Weight Readings -->
<div class="row g-4">
    <div class="col-12">
        <div class="card p-4">
            <h5 class="mb-3">
                <i class="fas fa-history text-success me-2"></i>
                Recent Weight Readings
            </h5>
            <div id="historyContainer" style="max-height: 500px; overflow-y: auto;">
                <?php if (empty($historyData)): ?>
                    <p class="text-muted text-center py-4">No history data available</p>
                <?php else: ?>
                    <?php foreach (array_slice($historyData, 0, 20) as $record): ?>
                    <div class="history-item">
                        <div>
                            <div class="history-value"><?php echo number_format($record['value'], 2); ?> kg</div>
                            <div class="history-time"><?php echo date('g:i A - M j, Y', $record['timestamp']); ?></div>
                        </div>
                        <div>
                            <?php
                            $percentage = ($record['value'] / $weightCapacity) * 100;
                            if ($percentage >= 80) {
                                echo '<span class="badge bg-danger">Almost Full</span>';
                            } elseif ($percentage >= 60) {
                                echo '<span class="badge bg-warning text-dark">Filling</span>';
                            } else {
                                echo '<span class="badge bg-success">Normal</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</div>
</div>

<script>
function closeWarning() {
    const popup = document.getElementById('warningPopup');
    const overlay = document.getElementById('warningOverlay');
    if (popup) popup.classList.remove('show');
    if (overlay) overlay.classList.remove('show');
}

// Smooth progress bar animation on load
window.addEventListener('load', () => {
    document.querySelectorAll('.progress-bar-fill').forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
        }, 100);
    });
});

// Prepare chart data
const historyData = <?php echo json_encode($historyData); ?>;
const labels = historyData.map(item => {
    const date = new Date(item.timestamp * 1000);
    return date.toLocaleString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit' 
    });
});
const weights = historyData.map(item => item.value);

// Create weight chart
const ctx = document.getElementById('weightChart').getContext('2d');
const gradient = ctx.createLinearGradient(0, 0, 0, 400);
gradient.addColorStop(0, 'rgba(76, 175, 80, 0.3)');
gradient.addColorStop(1, 'rgba(76, 175, 80, 0.05)');

const weightChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels.reverse(),
        datasets: [{
            label: 'Weight (kg)',
            data: weights.reverse(),
            borderColor: '#4CAF50',
            backgroundColor: gradient,
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointHoverRadius: 7,
            pointBackgroundColor: '#4CAF50',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(0,0,0,0.8)',
                padding: 12,
                titleFont: { size: 14 },
                bodyFont: { size: 13 },
                callbacks: {
                    label: function(context) {
                        return 'Weight: ' + context.parsed.y.toFixed(2) + ' kg';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                min: 0,
                max: <?php echo $weightCapacity; ?>,
                ticks: {
                    callback: function(value) {
                        return value + ' kg';
                    }
                },
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Real-time weight updates
async function fetchLatestWeight() {
    try {
        const res = await fetch('api/get_latest.php', { cache: 'no-store' });
        const data = await res.json();
        
        if (data && data.latest && data.latest.weight !== undefined) {
            const weight = parseFloat(data.latest.weight);
            const capacity = <?php echo $weightCapacity; ?>;
            const fertilizer = weight * 0.5;
            const percentage = (weight / capacity) * 100;
            
            // Update displays
            document.getElementById('currentWeightDisplay').textContent = weight.toFixed(2) + ' kg';
            document.getElementById('fertilizerDisplay').textContent = fertilizer.toFixed(1) + ' kg';
            
            // Update progress bars
            const weightProgress = document.getElementById('weightProgress');
            weightProgress.style.width = Math.min(100, percentage) + '%';
            
            // Update colors based on percentage
            if (percentage >= 80) {
                weightProgress.style.background = 'linear-gradient(90deg, #ff6b6b, #ee5a6f)';
            } else if (percentage >= 60) {
                weightProgress.style.background = 'linear-gradient(90deg, #ffd93d, #f6c23e)';
            } else {
                weightProgress.style.background = 'linear-gradient(90deg, #6fcf97, #27ae60)';
            }
            
            // Update fertilizer progress
            const fertilizerProgress = document.getElementById('fertilizerProgress');
            fertilizerProgress.style.width = Math.min(100, (fertilizer / (capacity * 0.5)) * 100) + '%';
        }
    } catch (e) {
        console.error('Error fetching weight:', e);
    }
}

// Update every 5 seconds
setInterval(fetchLatestWeight, 5000);
</script>

</body>
</html>