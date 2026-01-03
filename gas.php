<?php

/** 
 * 1. ✅ Load Firebase
 */
require_once 'firebase_config.php';
$database = getDatabase();

/**
 * 2. 🔥 Fetch latest gas value from Firebase
 */
$firebaseGas = $database->getReference("sensors/gas/latest/value")->getValue();
$firebaseTimestamp = $database->getReference("sensors/gas/latest/timestamp")->getValue();
$gas = $firebaseGas ?? 0;
$lastUpdate = $firebaseTimestamp ?? time();

/**
 * 3. 📊 Fetch gas history from Firebase
 */
$historyRef = $database->getReference("sensors/gas/history");
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
 * 4. 💨 Gas Status Classification
 */
if ($gas >= 800) {
    $gasStatus = 'Critical (Mixer Activated)';
    $statusClass = 'crit';
    $statusDesc = 'Gas level extremely high. Automatic mixer activation.';
} elseif ($gas >= 600) {
    $gasStatus = 'Warning (High)';
    $statusClass = 'warn';
    $statusDesc = 'Gas level rising. Monitor closely.';
} elseif ($gas >= 200) {
    $gasStatus = 'Optimal';
    $statusClass = 'ok';
    $statusDesc = 'Normal aerobic decomposition. Gas levels healthy.';
} else {
    $gasStatus = 'Below Range';
    $statusClass = 'info';
    $statusDesc = 'Gas production low. May indicate slow decomposition.';
}

// Calculate statistics
$avgGas = 0;
$maxGas = 0;
$minGas = 1000;
if (!empty($historyData)) {
    $gases = array_column($historyData, 'value');
    $avgGas = array_sum($gases) / count($gases);
    $maxGas = max($gases);
    $minGas = min($gases);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Gas Monitor - WattAWaste</title>
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
    --info: #38bdf8;
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

.gas-hero {
    background: linear-gradient(135deg, #ffba08 0%, #f48c06 100%);
    border-radius: 20px;
    padding: 40px;
    color: white;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.gas-hero::before {
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

.gas-display-large {
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
.status-badge-large.info { color: var(--info); }

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

.gas-visual {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #ffba08, #f48c06);
    border-radius: 50%;
    position: relative;
    margin: 0 auto;
    box-shadow: 0 8px 24px rgba(244,140,6,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    animation: gasGlow 2s ease-in-out infinite;
}

@keyframes gasGlow {
    0%, 100% { box-shadow: 0 8px 24px rgba(244,140,6,0.4); }
    50% { box-shadow: 0 8px 32px rgba(244,140,6,0.7); }
}

.gas-visual::before {
    content: '';
    position: absolute;
    width: 80%;
    height: 80%;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    animation: gasPulse 2s ease-in-out infinite;
}

@keyframes gasPulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.2); opacity: 0.8; }
}

.gas-icon {
    font-size: 48px;
    color: white;
    position: relative;
    z-index: 1;
}

.history-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 16px;
    margin-bottom: 8px;
    background: #f8fafc;
    border-radius: 12px;
    border-left: 4px solid #ffba08;
    transition: all 0.2s;
}

.history-item:hover {
    background: #f1f5f9;
    transform: translateX(4px);
}

.history-value {
    font-size: 22px;
    font-weight: 700;
    color: #d97706;
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

.alert-custom.critical {
    background: #FFE5E5;
    color: #7F1D1D;
    border-color: #ef4444;
}
</style>
</head>
<body>

<?php include 'sideabr.php'; ?>
<div class="main">
<?php include 'topnav.php'; ?>

<div class="container-fluid px-4 py-4">
    
    <h1 class="page-title">💨 Gas Level Monitoring</h1>

    <!-- Hero Gas Display -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="gas-hero">
                <div class="gas-display-large" id="gasDisplay"><?php echo number_format($gas, 0); ?> ppm</div>
                <div class="status-badge-large <?php echo $statusClass; ?>" id="statusBadge">
                    <?php echo $gasStatus; ?>
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
                <h6 class="text-center mb-3">Gas Emission</h6>
                <div class="gas-visual">
                    <i class="fas fa-wind gas-icon"></i>
                </div>
                <div class="mt-3 text-center small-muted">
                    <div><span style="color: #ef4444;">●</span> ≥800 ppm Critical</div>
                    <div><span style="color: #f59e0b;">●</span> 600-799 ppm Warning</div>
                    <div><span style="color: #10b981;">●</span> 200-599 ppm Optimal</div>
                    <div><span style="color: #38bdf8;">●</span> &lt;200 ppm Below Range</div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($gas >= 800): ?>
    <div class="alert-custom critical mb-4">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Critical Alert:</strong> Gas levels dangerously high! Mixer should be activated immediately to improve aeration.
    </div>
    <?php elseif ($gas >= 600): ?>
    <div class="alert-custom warning mb-4">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Warning:</strong> Gas levels are rising. Ensure proper ventilation and consider activating the mixer.
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card">
                <i class="fas fa-wind text-danger mb-2" style="font-size: 32px;"></i>
                <div class="stat-value"><?php echo number_format($maxGas, 0); ?> ppm</div>
                <div class="stat-label">Maximum Gas Level</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <i class="fas fa-chart-line text-success mb-2" style="font-size: 32px;"></i>
                <div class="stat-value"><?php echo number_format($avgGas, 0); ?> ppm</div>
                <div class="stat-label">Average Gas Level</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <i class="fas fa-smog text-info mb-2" style="font-size: 32px;"></i>
                <div class="stat-value"><?php echo number_format($minGas, 0); ?> ppm</div>
                <div class="stat-label">Minimum Gas Level</div>
            </div>
        </div>
    </div>

    <!-- Gas Trend Chart -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card p-4">
                <h5 class="mb-3">
                    <i class="fas fa-chart-area text-primary me-2"></i>
                    Gas Level Trend (Last <?php echo count($historyData); ?> Readings)
                </h5>
                <canvas id="gasChart" style="max-height: 350px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Readings -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card p-4">
                <h5 class="mb-3">
                    <i class="fas fa-history text-info me-2"></i>
                    Recent Gas Level Readings
                </h5>
                <div id="historyContainer" style="max-height: 500px; overflow-y: auto;">
                    <?php if (empty($historyData)): ?>
                        <p class="text-muted text-center py-4">No history data available</p>
                    <?php else: ?>
                        <?php foreach (array_slice($historyData, 0, 20) as $record): ?>
                        <div class="history-item">
                            <div>
                                <div class="history-value"><?php echo number_format($record['value'], 0); ?> ppm</div>
                                <div class="history-time"><?php echo date('g:i A - M j, Y', $record['timestamp']); ?></div>
                            </div>
                            <div>
                                <?php
                                $val = $record['value'];
                                if ($val >= 800) {
                                    echo '<span class="badge bg-danger">Critical</span>';
                                } elseif ($val >= 600) {
                                    echo '<span class="badge bg-warning">Warning</span>';
                                } elseif ($val >= 200) {
                                    echo '<span class="badge bg-success">Optimal</span>';
                                } else {
                                    echo '<span class="badge bg-info">Below Range</span>';
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
const gases = historyData.map(item => item.value);

// Create gas chart
const ctx = document.getElementById('gasChart').getContext('2d');
const gradient = ctx.createLinearGradient(0, 0, 0, 400);
gradient.addColorStop(0, 'rgba(255, 186, 8, 0.3)');
gradient.addColorStop(1, 'rgba(244, 140, 6, 0.05)');

const gasChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Gas Level (ppm)',
            data: gases,
            borderColor: '#ffba08',
            backgroundColor: gradient,
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointRadius: 3,
            pointHoverRadius: 6,
            pointBackgroundColor: '#ffba08',
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
                        return 'Gas Level: ' + context.parsed.y.toFixed(0) + ' ppm';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                min: 0,
                max: 1000,
                ticks: {
                    callback: function(value) {
                        return value + ' ppm';
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
async function fetchLatestGas() {
    try {
        const res = await fetch('api/get_latest.php', { cache: 'no-store' });
        const { latest } = await res.json();
        
        if (latest && latest.gas !== undefined) {
            const gas = parseFloat(latest.gas);
            document.getElementById('gasDisplay').textContent = gas.toFixed(0) + ' ppm';
            
            // Update status badge
            const badge = document.getElementById('statusBadge');
            badge.classList.remove('ok', 'warn', 'crit', 'info');
            
            if (gas >= 800) {
                badge.classList.add('crit');
                badge.textContent = 'Critical (Mixer Activated)';
            } else if (gas >= 600) {
                badge.classList.add('warn');
                badge.textContent = 'Warning (High)';
            } else if (gas >= 200) {
                badge.classList.add('ok');
                badge.textContent = 'Optimal';
            } else {
                badge.classList.add('info');
                badge.textContent = 'Below Range';
            }
        }
    } catch (e) {
        console.error('Error fetching gas:', e);
    }
}

// Update every 3 seconds
setInterval(fetchLatestGas, 3000);
</script>

</body>
</html>