<?php

/** 
 * 1. ✅ Load Firebase
 */
require_once 'firebase_config.php';
$database = getDatabase();

/**
 * 2. 🔥 Fetch latest temperature value from Firebase
 */
$firebaseTemp = $database->getReference("sensors/temperature/latest/value")->getValue();
$firebaseTimestamp = $database->getReference("sensors/temperature/latest/timestamp")->getValue();
$temp = $firebaseTemp ?? 0;
$lastUpdate = $firebaseTimestamp ?? time();

/**
 * 3. 📊 Fetch temperature history from Firebase
 */
$historyRef = $database->getReference("sensors/temperature/history");
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

/**
 * 4. 🌡 Temperature Status Classification
 */
if ($temp >= 45 && $temp <= 70) {
    $tempStatus = 'Thermophilic (Active)';
    $statusClass = 'ok';
    $statusDesc = 'Optimal composting temperature. High microbial activity.';
} elseif ($temp >= 20 && $temp < 45) {
    $tempStatus = 'Mesophilic (Initial)';
    $statusClass = 'warn';
    $statusDesc = 'Initial composting phase. Microbes breaking down materials.';
} elseif ($temp < 20) {
    $tempStatus = 'Too Cold';
    $statusClass = 'crit';
    $statusDesc = 'Temperature too low for effective composting.';
} else {
    $tempStatus = 'Too Hot';
    $statusClass = 'crit';
    $statusDesc = 'Temperature exceeds safe composting range.';
}

// Calculate statistics
$avgTemp = 0;
$maxTemp = 0;
$minTemp = 100;
if (!empty($historyData)) {
    $temps = array_column($historyData, 'value');
    $avgTemp = array_sum($temps) / count($temps);
    $maxTemp = max($temps);
    $minTemp = min($temps);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Temperature Monitor - WattAWaste</title>
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
    --ok: #22c55e;
    --warn: #f59e0b;
    --crit: #ef4444;
}

body {
    background: var(--bg);
    font-family: Poppins, system-ui, Segoe UI, Arial;
    color: var(--ink);
    min-height: 100vh;
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

.temp-hero {
    background: linear-gradient(135deg, #ff7b00 0%, #ff0000 100%);
    border-radius: 20px;
    padding: 40px;
    color: white;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.temp-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse 3s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

.temp-display-large {
    font-size: 96px;
    font-weight: 900;
    line-height: 1;
    text-shadow: 0 4px 12px rgba(0,0,0,0.2);
    position: relative;
    z-index: 1;
}

.status-badge-large {
    display: inline-block;
    padding: 12px 30px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 18px;
    background: rgba(255,255,255,0.9);
    margin-top: 16px;
    position: relative;
    z-index: 1;
}

.status-badge-large.ok { color: var(--ok); }
.status-badge-large.warn { color: var(--warn); }
.status-badge-large.crit { color: var(--crit); }

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

.thermo-visual {
    width: 80px;
    height: 300px;
    background: linear-gradient(to top, #0088ff 0%, #00ff00 33%, #ffae00 66%, #ff0000 100%);
    border-radius: 40px;
    position: relative;
    margin: 0 auto;
    box-shadow: inset 0 0 20px rgba(0,0,0,0.1);
}

.thermo-indicator {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.4);
    backdrop-filter: blur(10px);
    border-radius: 40px;
    transition: height 1s cubic-bezier(0.4, 0, 0.2, 1);
    border: 3px solid white;
}

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
    border-color: #f59e0b;
}
</style>
</head>
<body>

<?php include 'sideabr.php'; ?>
<div class="main">
<?php include 'topnav.php'; ?>

<div class="container-fluid px-4 py-4">
    
    <h1 class="page-title">🌡️ Temperature Monitoring</h1>

    <!-- Hero Temperature Display -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="temp-hero">
                <div class="temp-display-large" id="tempDisplay"><?php echo number_format($temp, 1); ?>°C</div>
                <div class="status-badge-large <?php echo $statusClass; ?>" id="statusBadge">
                    <?php echo $tempStatus; ?>
                </div>
                <div class="mt-3 small" style="opacity: 0.9; position: relative; z-index: 1;">
                    <?php echo $statusDesc; ?>
                </div>
                <div class="mt-2 small" style="opacity: 0.8; position: relative; z-index: 1;">
                    Last updated: <?php echo date('g:i A - M j, Y', $lastUpdate); ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card p-4 h-100 d-flex align-items-center justify-content-center">
                <h6 class="text-center mb-3">Visual Indicator</h6>
                <div class="thermo-visual">
                    <div class="thermo-indicator" id="thermoIndicator"></div>
                </div>
                <div class="mt-3 text-center small-muted">
                    <div><span style="color: #ff0000;">●</span> 70°C+ Too Hot</div>
                    <div><span style="color: #ff7b00;">●</span> 45-70°C Active</div>
                    <div><span style="color: #ffae00;">●</span> 20-45°C Initial</div>
                    <div><span style="color: #0088ff;">●</span> &lt;20°C Too Cold</div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($temp < 15 || $temp > 75): ?>
    <div class="alert-custom warning mb-4">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Warning:</strong> Temperature is outside the optimal composting range (20-70°C). Monitor closely.
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card">
                <i class="fas fa-temperature-high text-danger mb-2" style="font-size: 32px;"></i>
                <div class="stat-value"><?php echo number_format($maxTemp, 1); ?>°C</div>
                <div class="stat-label">Maximum Temperature</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <i class="fas fa-chart-line text-success mb-2" style="font-size: 32px;"></i>
                <div class="stat-value"><?php echo number_format($avgTemp, 1); ?>°C</div>
                <div class="stat-label">Average Temperature</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <i class="fas fa-temperature-low text-info mb-2" style="font-size: 32px;"></i>
                <div class="stat-value"><?php echo number_format($minTemp, 1); ?>°C</div>
                <div class="stat-label">Minimum Temperature</div>
            </div>
        </div>
    </div>

    <!-- Temperature Trend Chart -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card p-4">
                <h5 class="mb-3">
                    <i class="fas fa-chart-area text-primary me-2"></i>
                    Temperature Trend (Last <?php echo count($historyData); ?> Readings)
                </h5>
                <canvas id="tempChart" style="max-height: 350px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Readings -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card p-4">
                <h5 class="mb-3">
                    <i class="fas fa-history text-info me-2"></i>
                    Recent Temperature Readings
                </h5>
                <div id="historyContainer" style="max-height: 500px; overflow-y: auto;">
                    <?php if (empty($historyData)): ?>
                        <p class="text-muted text-center py-4">No history data available</p>
                    <?php else: ?>
                        <?php foreach (array_slice($historyData, 0, 20) as $record): ?>
                        <div class="history-item">
                            <div>
                                <div class="history-value"><?php echo number_format($record['value'], 1); ?>°C</div>
                                <div class="history-time"><?php echo date('g:i A - M j, Y', $record['timestamp']); ?></div>
                            </div>
                            <div>
                                <?php
                                $val = $record['value'];
                                if ($val >= 45 && $val <= 70) {
                                    echo '<span class="badge bg-success">Active</span>';
                                } elseif ($val >= 20 && $val < 45) {
                                    echo '<span class="badge bg-warning">Initial</span>';
                                } else {
                                    echo '<span class="badge bg-danger">Alert</span>';
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
// Update thermometer visual indicator
function updateThermoIndicator(temp) {
    const indicator = document.getElementById('thermoIndicator');
    const percentage = Math.min(Math.max((temp / 100) * 100, 0), 100);
    indicator.style.height = percentage + '%';
}

// Initialize with PHP value
updateThermoIndicator(<?php echo $temp; ?>);

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
const temperatures = historyData.map(item => item.value);

// Create temperature chart
const ctx = document.getElementById('tempChart').getContext('2d');
const gradient = ctx.createLinearGradient(0, 0, 0, 400);
gradient.addColorStop(0, 'rgba(255, 123, 0, 0.3)');
gradient.addColorStop(1, 'rgba(255, 0, 0, 0.05)');

const tempChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Temperature (°C)',
            data: temperatures,
            borderColor: '#ff7b00',
            backgroundColor: gradient,
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointRadius: 3,
            pointHoverRadius: 6,
            pointBackgroundColor: '#ff7b00',
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
                        return 'Temperature: ' + context.parsed.y.toFixed(1) + '°C';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                min: 0,
                max: 100,
                ticks: {
                    callback: function(value) {
                        return value + '°C';
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

// Real-time updates
async function fetchLatestTemperature() {
    try {
        const res = await fetch('api/get_latest.php', { cache: 'no-store' });
        const { latest } = await res.json();
        
        if (latest && latest.temperature !== undefined) {
            const temp = parseFloat(latest.temperature);
            document.getElementById('tempDisplay').textContent = temp.toFixed(1) + '°C';
            updateThermoIndicator(temp);
            
            // Update status badge
            const badge = document.getElementById('statusBadge');
            badge.classList.remove('ok', 'warn', 'crit');
            
            if (temp >= 45 && temp <= 70) {
                badge.classList.add('ok');
                badge.textContent = 'Thermophilic (Active)';
            } else if (temp >= 20 && temp < 45) {
                badge.classList.add('warn');
                badge.textContent = 'Mesophilic (Initial)';
            } else {
                badge.classList.add('crit');
                badge.textContent = temp < 20 ? 'Too Cold' : 'Too Hot';
            }
        }
    } catch (e) {
        console.error('Error fetching temperature:', e);
    }
}

// Update every 3 seconds
setInterval(fetchLatestTemperature, 3000);
</script>

</body>
</html>