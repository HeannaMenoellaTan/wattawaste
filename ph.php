<?php

/** 
 * 1. ✅ Load Firebase
 */
require_once 'firebase_config.php';
$database = getDatabase();

/**
 * 2. 🔥 Fetch latest pH value from Firebase
 */
$firebasePH = $database->getReference("sensors/ph/latest/value")->getValue();
$firebaseTimestamp = $database->getReference("sensors/ph/latest/timestamp")->getValue();
$ph = $firebasePH ?? 7.0;
$lastUpdate = $firebaseTimestamp ?? time();

/**
 * 3. 📊 Fetch pH history from Firebase
 */
$historyRef = $database->getReference("sensors/ph/history");
$historySnapshot = $historyRef->getSnapshot();

$historyData = [];
if ($historySnapshot->exists()) {
    foreach ($historySnapshot->getValue() as $key => $item) {
        $historyData[] = [
            'timestamp' => $item['timestamp'] ?? time(),
            'value' => $item['value'] ?? 7.0
        ];
    }
}

// Sort by timestamp (newest first) and limit to 50
usort($historyData, function($a, $b) {
    return $b['timestamp'] - $a['timestamp'];
});
$historyData = array_slice($historyData, 0, 50);

/**
 * 4. ⚗️ pH Status Classification
 */
if ($ph < 6.0) {
    $phStatus = 'Too Acidic';
    $statusClass = 'acidic';
    $statusDesc = 'Add brown materials or lime to increase pH.';
    $recommendation = 'Add brown/lime materials to neutralize acidity';
} elseif ($ph > 8.0) {
    $phStatus = 'Too Alkaline';
    $statusClass = 'alkaline';
    $statusDesc = 'Add green materials or acidic materials to decrease pH.';
    $recommendation = 'Add green/acidic materials to reduce alkalinity';
} else {
    $phStatus = 'Optimal (Healthy)';
    $statusClass = 'ok';
    $statusDesc = 'Perfect pH range for composting. Keep monitoring.';
    $recommendation = 'Compost is healthy, continue monitoring daily';
}

// Calculate statistics
$avgPH = 0;
$maxPH = 0;
$minPH = 14;
if (!empty($historyData)) {
    $phs = array_column($historyData, 'value');
    $avgPH = array_sum($phs) / count($phs);
    $maxPH = max($phs);
    $minPH = min($phs);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>pH Monitor - WattAWaste</title>
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
    --acidic: #ef4444;
    --alkaline: #3b82f6;
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

.ph-hero {
    background: linear-gradient(135deg, #f59e0b 0%, #eab308 100%);
    border-radius: 20px;
    padding: 40px;
    color: white;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.ph-hero.acidic {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.ph-hero.alkaline {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.ph-hero::before {
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

.ph-display-large {
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
.status-badge-large.acidic { color: var(--acidic); }
.status-badge-large.alkaline { color: var(--alkaline); }

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

.ph-scale {
    width: 100%;
    height: 40px;
    background: linear-gradient(to right, #ff0000 0%, #ff7f00 14%, #ffff00 28%, #00ff00 42%, #0000ff 57%, #4b0082 71%, #9400d3 85%, #ff00ff 100%);
    border-radius: 20px;
    position: relative;
    margin: 20px 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.ph-indicator {
    position: absolute;
    top: -10px;
    width: 20px;
    height: 60px;
    background: white;
    border: 3px solid #333;
    border-radius: 10px;
    transition: left 1s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}

.ph-scale-labels {
    display: flex;
    justify-content: space-between;
    margin-top: 8px;
    font-size: 12px;
    color: var(--muted);
    font-weight: 600;
}

.history-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 16px;
    margin-bottom: 8px;
    background: #f8fafc;
    border-radius: 12px;
    border-left: 4px solid #eab308;
    transition: all 0.2s;
}

.history-item:hover {
    background: #f1f5f9;
    transform: translateX(4px);
}

.history-value {
    font-size: 22px;
    font-weight: 700;
    color: #ca8a04;
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

.alert-custom.acidic {
    background: #FEE2E2;
    color: #7F1D1D;
    border-color: #ef4444;
}

.alert-custom.alkaline {
    background: #DBEAFE;
    color: #1E3A8A;
    border-color: #3b82f6;
}

.recommendation-popup {
    position: fixed;
    top: 100px;
    right: 30px;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.15);
    z-index: 1000;
    max-width: 350px;
    border-left: 5px solid;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { transform: translateX(400px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.recommendation-popup.acidic { border-color: #ef4444; }
.recommendation-popup.alkaline { border-color: #3b82f6; }
.recommendation-popup.ok { border-color: #22c55e; }

.close-popup {
    position: absolute;
    top: 10px;
    right: 10px;
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: var(--muted);
}
</style>
</head>
<body>

<?php include 'sideabr.php'; ?>
<div class="main">
<?php include 'topnav.php'; ?>

<div class="container-fluid px-4 py-4">
    
    <h1 class="page-title">⚗️ pH Level Monitoring</h1>

    <!-- Hero pH Display -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="ph-hero <?php echo $statusClass; ?>" id="phHero">
                <div class="ph-display-large" id="phDisplay"><?php echo number_format($ph, 1); ?></div>
                <div class="status-badge-large <?php echo $statusClass; ?>" id="statusBadge">
                    <?php echo $phStatus; ?>
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
            <div class="card p-4 h-100">
                <h6 class="text-center mb-3">pH Scale (0-14)</h6>
                <div class="ph-scale">
                    <div class="ph-indicator" id="phIndicator"></div>
                </div>
                <div class="ph-scale-labels">
                    <span>0</span>
                    <span>7</span>
                    <span>14</span>
                </div>
                <div class="mt-3 text-center small-muted">
                    <div><span style="color: #ef4444;">●</span> &lt;6.0 Too Acidic</div>
                    <div><span style="color: #22c55e;">●</span> 6.0-8.0 Optimal</div>
                    <div><span style="color: #3b82f6;">●</span> &gt;8.0 Too Alkaline</div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($ph < 6.0 || $ph > 8.0): ?>
    <div class="alert-custom <?php echo $statusClass; ?> mb-4">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Action Required:</strong> <?php echo $recommendation; ?>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card">
                <i class="fas fa-vial text-danger mb-2" style="font-size: 32px;"></i>
                <div class="stat-value"><?php echo number_format($maxPH, 2); ?></div>
                <div class="stat-label">Maximum pH</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <i class="fas fa-chart-line text-success mb-2" style="font-size: 32px;"></i>
                <div class="stat-value"><?php echo number_format($avgPH, 2); ?></div>
                <div class="stat-label">Average pH</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <i class="fas fa-flask text-info mb-2" style="font-size: 32px;"></i>
                <div class="stat-value"><?php echo number_format($minPH, 2); ?></div>
                <div class="stat-label">Minimum pH</div>
            </div>
        </div>
    </div>

    <!-- pH Trend Chart -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card p-4">
                <h5 class="mb-3">
                    <i class="fas fa-chart-area text-primary me-2"></i>
                    pH Level Trend (Last <?php echo count($historyData); ?> Readings)
                </h5>
                <canvas id="phChart" style="max-height: 350px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Readings -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card p-4">
                <h5 class="mb-3">
                    <i class="fas fa-history text-info me-2"></i>
                    Recent pH Readings
                </h5>
                <div id="historyContainer" style="max-height: 500px; overflow-y: auto;">
                    <?php if (empty($historyData)): ?>
                        <p class="text-muted text-center py-4">No history data available</p>
                    <?php else: ?>
                        <?php foreach (array_slice($historyData, 0, 20) as $record): ?>
                        <div class="history-item">
                            <div>
                                <div class="history-value"><?php echo number_format($record['value'], 2); ?></div>
                                <div class="history-time"><?php echo date('g:i A - M j, Y', $record['timestamp']); ?></div>
                            </div>
                            <div>
                                <?php
                                $val = $record['value'];
                                if ($val < 6.0) {
                                    echo '<span class="badge bg-danger">Too Acidic</span>';
                                } elseif ($val > 8.0) {
                                    echo '<span class="badge bg-primary">Too Alkaline</span>';
                                } else {
                                    echo '<span class="badge bg-success">Optimal</span>';
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

<!-- Recommendation Popup -->
<?php if ($ph < 6.0 || $ph > 8.0): ?>
<div class="recommendation-popup <?php echo $statusClass; ?>" id="recommendationPopup">
    <button class="close-popup" onclick="closePopup()">×</button>
    <h6><i class="fas fa-lightbulb me-2"></i>Recommendation</h6>
    <p class="mb-0 mt-2"><?php echo $recommendation; ?></p>
</div>
<?php endif; ?>

</div>

<script>
// Update pH scale indicator
function updatePHIndicator(ph) {
    const indicator = document.getElementById('phIndicator');
    const percentage = Math.min(Math.max((ph / 14) * 100, 0), 100);
    indicator.style.left = `calc(${percentage}% - 10px)`;
}

// Initialize with PHP value
updatePHIndicator(<?php echo $ph; ?>);

// Close popup
function closePopup() {
    const popup = document.getElementById('recommendationPopup');
    if (popup) popup.style.display = 'none';
}

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
const phs = historyData.map(item => item.value);

// Create pH chart
const ctx = document.getElementById('phChart').getContext('2d');
const gradient = ctx.createLinearGradient(0, 0, 0, 400);
gradient.addColorStop(0, 'rgba(234, 179, 8, 0.3)');
gradient.addColorStop(1, 'rgba(245, 158, 11, 0.05)');

const phChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'pH Level',
            data: phs,
            borderColor: '#eab308',
            backgroundColor: gradient,
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointRadius: 3,
            pointHoverRadius: 6,
            pointBackgroundColor: '#eab308',
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
                        return 'pH: ' + context.parsed.y.toFixed(2);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: false,
                min: 4,
                max: 10,
                ticks: {
                    callback: function(value) {
                        return value.toFixed(1);
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
async function fetchLatestPH() {
    try {
        const res = await fetch('api/get_latest.php', { cache: 'no-store' });
        const { latest } = await res.json();
        
        if (latest && latest.ph !== undefined) {
            const ph = parseFloat(latest.ph);
            document.getElementById('phDisplay').textContent = ph.toFixed(1);
            updatePHIndicator(ph);
            
            // Update hero background and badge
            const hero = document.getElementById('phHero');
            const badge = document.getElementById('statusBadge');
            
            hero.classList.remove('acidic', 'alkaline', 'ok');
            badge.classList.remove('acidic', 'alkaline', 'ok');
            
            if (ph < 6.0) {
                hero.classList.add('acidic');
                badge.classList.add('acidic');
                badge.textContent = 'Too Acidic';
            } else if (ph > 8.0) {
                hero.classList.add('alkaline');
                badge.classList.add('alkaline');
                badge.textContent = 'Too Alkaline';
            } else {
                hero.classList.add('ok');
                badge.classList.add('ok');
                badge.textContent = 'Optimal (Healthy)';
            }
        }
    } catch (e) {
        console.error('Error fetching pH:', e);
    }
}

// Update every 3 seconds
setInterval(fetchLatestPH, 3000);
</script>

</body>
</html>