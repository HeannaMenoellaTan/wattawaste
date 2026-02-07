<?php
// Minimal PHP - just for auth check and page structure
// All data will be fetched from Firebase via JavaScript
require_once 'firebase_config.php';
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

<!-- Firebase Auth and Database -->
<script type="module">
import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
import { getAuth, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
import { getDatabase, ref, onValue, query, limitToLast } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-database.js';

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
const database = getDatabase(app);

onAuthStateChanged(auth, (user) => {
    if (!user) {
        console.log('❌ No user found, redirecting to login...');
        window.location.href = 'login.php';
    } else {
        console.log('✅ User authenticated:', user.email || user.phoneNumber);
        sessionStorage.setItem('userEmail', user.email || user.phoneNumber || '');
        sessionStorage.setItem('userId', user.uid);
        
        // Initialize pH monitoring after authentication
        window.initializePHMonitoring();
    }
});

window.firebaseAuth = auth;
window.firebaseDatabase = database;
window.firebaseRef = ref;
window.firebaseOnValue = onValue;
window.firebaseQuery = query;
window.firebaseLimitToLast = limitToLast;
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

.ph-hero.ok {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
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
    display: none;
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
            <div class="ph-hero ok" id="phHero">
                <div class="ph-display-large" id="phDisplay">--</div>
                <div class="status-badge-large ok" id="statusBadge">
                    Loading...
                </div>
                <div class="mt-3 small" style="opacity: 0.9; position: relative; z-index: 1;" id="statusDesc">
                    Fetching pH data from sensors...
                </div>
                <div class="mt-2 small" style="opacity: 0.8; position: relative; z-index: 1;" id="lastUpdateText">
                    Last updated: Just now
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card p-4 h-100">
                <h6 class="text-center mb-3">pH Scale (0-14)</h6>
                <div class="ph-scale">
                    <div class="ph-indicator" id="phIndicator" style="left: calc(50% - 10px);"></div>
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

    <div class="alert-custom acidic mb-4" id="warningBanner" style="display: none;">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Action Required:</strong> <span id="warningText"></span>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card">
                <i class="fas fa-vial text-danger mb-2" style="font-size: 32px;"></i>
                <div class="stat-value" id="maxPHStat">--</div>
                <div class="stat-label">Maximum pH</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <i class="fas fa-chart-line text-success mb-2" style="font-size: 32px;"></i>
                <div class="stat-value" id="avgPHStat">--</div>
                <div class="stat-label">Average pH</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <i class="fas fa-flask text-info mb-2" style="font-size: 32px;"></i>
                <div class="stat-value" id="minPHStat">--</div>
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
                    pH Level Trend <span id="readingsCount">(Loading...)</span>
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
                    <p class="text-muted text-center py-4">Loading pH history...</p>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Recommendation Popup -->
<div class="recommendation-popup ok" id="recommendationPopup">
    <button class="close-popup" onclick="closePopup()">×</button>
    <h6><i class="fas fa-lightbulb me-2"></i>Recommendation</h6>
    <p class="mb-0 mt-2" id="recommendationText">Compost is healthy, continue monitoring daily</p>
</div>

</div>

<script>
let phChart = null;
let lastUpdateTime = 0;
const UPDATE_INTERVAL = 1800000; // 30 minutes in milliseconds (30 * 60 * 1000)

// Update pH scale indicator
function updatePHIndicator(ph) {
    const indicator = document.getElementById('phIndicator');
    const percentage = Math.min(Math.max((ph / 14) * 100, 0), 100);
    indicator.style.left = `calc(${percentage}% - 10px)`;
}

// Close popup
function closePopup() {
    const popup = document.getElementById('recommendationPopup');
    if (popup) popup.style.display = 'none';
}

// Get pH status and recommendations
function getPHStatus(ph) {
    if (ph < 6.0) {
        return {
            status: 'Too Acidic',
            statusClass: 'acidic',
            desc: 'Add brown materials or lime to increase pH.',
            recommendation: 'Add brown/lime materials to neutralize acidity'
        };
    } else if (ph > 8.0) {
        return {
            status: 'Too Alkaline',
            statusClass: 'alkaline',
            desc: 'Add green materials or acidic materials to decrease pH.',
            recommendation: 'Add green/acidic materials to reduce alkalinity'
        };
    } else {
        return {
            status: 'Optimal (Healthy)',
            statusClass: 'ok',
            desc: 'Perfect pH range for composting. Keep monitoring.',
            recommendation: 'Compost is healthy, continue monitoring daily'
        };
    }
}

// Update pH display
function updatePHDisplay(ph) {
    const status = getPHStatus(ph);
    
    // Update display
    document.getElementById('phDisplay').textContent = ph.toFixed(1);
    document.getElementById('statusBadge').textContent = status.status;
    document.getElementById('statusDesc').textContent = status.desc;
    document.getElementById('lastUpdateText').textContent = 'Last updated: Just now';
    
    // Update indicator
    updatePHIndicator(ph);
    
    // Update hero background and badge classes
    const hero = document.getElementById('phHero');
    const badge = document.getElementById('statusBadge');
    const popup = document.getElementById('recommendationPopup');
    const warningBanner = document.getElementById('warningBanner');
    
    hero.classList.remove('acidic', 'alkaline', 'ok');
    badge.classList.remove('acidic', 'alkaline', 'ok');
    popup.classList.remove('acidic', 'alkaline', 'ok');
    warningBanner.classList.remove('acidic', 'alkaline');
    
    hero.classList.add(status.statusClass);
    badge.classList.add(status.statusClass);
    popup.classList.add(status.statusClass);
    
    // Update warning banner and popup
    if (ph < 6.0 || ph > 8.0) {
        warningBanner.classList.add(status.statusClass);
        warningBanner.style.display = 'block';
        document.getElementById('warningText').textContent = status.recommendation;
        document.getElementById('recommendationText').textContent = status.recommendation;
        popup.style.display = 'block';
    } else {
        warningBanner.style.display = 'none';
        popup.style.display = 'none';
    }
}

// Update statistics
function updateStatistics(historyData) {
    if (historyData.length === 0) return;
    
    const phs = historyData.map(item => item.value);
    const maxPH = Math.max(...phs);
    const minPH = Math.min(...phs);
    const avgPH = phs.reduce((a, b) => a + b, 0) / phs.length;
    
    document.getElementById('maxPHStat').textContent = maxPH.toFixed(2);
    document.getElementById('avgPHStat').textContent = avgPH.toFixed(2);
    document.getElementById('minPHStat').textContent = minPH.toFixed(2);
}

function getRelativeTimeLabel(index, total) {
    const minutesAgo = (total - index - 1) * 2; // Assuming ~2 minutes between readings
    
    if (minutesAgo === 0) return 'Just now';
    if (minutesAgo < 60) return `${minutesAgo} min ago`;
    
    const hoursAgo = Math.floor(minutesAgo / 60);
    if (hoursAgo < 24) return `${hoursAgo} hour${hoursAgo > 1 ? 's' : ''} ago`;
    
    const daysAgo = Math.floor(hoursAgo / 24);
    return `${daysAgo} day${daysAgo > 1 ? 's' : ''} ago`;
}

// Update history display
function updateHistoryDisplay(historyData) {
    const container = document.getElementById('historyContainer');
    
    if (historyData.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-4">No history data available</p>';
        return;
    }
    
    // Take last 20 readings
    const recent = historyData.slice(0, 20);
    
    container.innerHTML = recent.map((record, index) => {
        const val = record.value;
        let badgeClass = 'success';
        let badgeText = 'Optimal';
        
        if (val < 6.0) {
            badgeClass = 'danger';
            badgeText = 'Too Acidic';
        } else if (val > 8.0) {
            badgeClass = 'primary';
            badgeText = 'Too Alkaline';
        }
        
        const timeLabel = getRelativeTimeLabel(index, recent.length);
        
        return `
            <div class="history-item">
                <div>
                    <div class="history-value">${val.toFixed(2)}</div>
                    <div class="history-time">Reading #${record.sequenceId} - ${timeLabel}</div>
                </div>
                <div>
                    <span class="badge bg-${badgeClass}">${badgeText}</span>
                </div>
            </div>
        `;
    }).join('');
}

// Update chart
function updateChart(historyData) {
    const ctx = document.getElementById('phChart').getContext('2d');
    
    // Prepare data - use sequence for X axis
    const labels = historyData.map((item) => {
        return `Reading ${item.sequenceId}`;
    });
    
    const phs = historyData.map(item => item.value);
    
    // Create gradient
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(234, 179, 8, 0.3)');
    gradient.addColorStop(1, 'rgba(245, 158, 11, 0.05)');
    
    // Destroy existing chart if it exists
    if (phChart) {
        phChart.destroy();
    }
    
    // Create new chart
    phChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.reverse(),
            datasets: [{
                label: 'pH Level',
                data: phs.reverse(),
                borderColor: '#eab308',
                backgroundColor: gradient,
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 7,
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
    
    document.getElementById('readingsCount').textContent = `(Last ${historyData.length} Readings)`;
}

// Initialize pH monitoring with Firebase
window.initializePHMonitoring = function() {
    console.log('⚗️ Initializing pH monitoring...');
    
    const database = window.firebaseDatabase;
    
    // Listen to latest pH (real-time, but throttled to 10 minutes)
    const phRef = window.firebaseRef(database, 'sensors/ph/latest');
    window.firebaseOnValue(phRef, (snapshot) => {
        const now = Date.now();
        
        // Only update display every 30 minutes
        if (now - lastUpdateTime < UPDATE_INTERVAL) {
            console.log('⏱️ Skipping pH update (less than 30 minutes since last update)');
            return;
        }
        
        lastUpdateTime = now;
        
        const ph = snapshot.val() || 7.0;
        console.log('⚗️ pH updated:', ph);
        
        updatePHDisplay(ph);
    });
    
    // Listen to pH history (limited to last 50 readings)
    const historyQuery = window.firebaseQuery(
        window.firebaseRef(database, 'sensors/ph/history'),
        window.firebaseLimitToLast(50)
    );
    
    window.firebaseOnValue(historyQuery, (snapshot) => {
        const historyData = [];
        
        if (snapshot.exists()) {
            snapshot.forEach((childSnapshot) => {
                const sequenceId = parseInt(childSnapshot.key); // This is just a sequence number, not a timestamp
                const value = childSnapshot.val();
                
                historyData.push({
                    sequenceId: sequenceId,
                    value: value
                });
            });
        }
        
        // Sort by sequence ID (newest first)
        historyData.sort((a, b) => b.sequenceId - a.sequenceId);
        
        console.log('📊 pH history loaded:', historyData.length, 'readings');
        
        // Update statistics
        updateStatistics(historyData);
        
        // Update history display
        updateHistoryDisplay(historyData);
        
        // Update chart
        updateChart(historyData);
    });
    
    console.log('✅ pH monitoring initialized');
};
</script>

</body>
</html>