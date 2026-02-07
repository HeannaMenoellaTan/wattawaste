<?php
// Minimal PHP - just for auth check and page structure
// All data will be fetched from Firebase via JavaScript
require_once 'firebase_config.php';
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
        
        // Initialize weight monitoring after authentication
        window.initializeWeightMonitoring();
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

/* History Items */
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
<div class="warning-popup-overlay" id="warningOverlay" onclick="closeWarning()"></div>
<div class="warning-popup" id="warningPopup">
    <i class="fas fa-exclamation-triangle"></i>
    <h3>⚠️ Warning!</h3>
    <p id="warningText">The bin is almost full!</p>
    <button onclick="closeWarning()">Got it</button>
</div>

<h1 class="page-title">⚖️ Weight Monitoring</h1>

<!-- Two-Card Design -->
<div class="cards-row">
    <!-- Current Weight Card -->
    <div class="weight-card">
        <h3><i class="fas fa-weight me-2"></i>Current Weight</h3>
        <div class="weight-value" id="currentWeightDisplay">-- kg</div>
        <div class="weight-capacity">Capacity: <span id="capacityDisplay">1</span> kg</div>
        <div class="progress-bar-container">
            <div class="progress-bar-fill" id="weightProgress" style="width: 0%;"></div>
        </div>
        <div class="mt-2 text-muted" style="font-size: 13px;" id="weightPercentText">
            0.0% Full
        </div>
    </div>
    
    <!-- Total Compost Fertilizer Card -->
    <div class="weight-card">
        <h3><i class="fas fa-seedling me-2"></i>Total Compost Fertilizer</h3>
        <div class="weight-value" id="fertilizerDisplay">-- kg</div>
        <div class="weight-capacity">Current yield (50% conversion)</div>
        <div class="progress-bar-container">
            <div class="progress-bar-fill" id="fertilizerProgress" style="width: 0%;"></div>
        </div>
        <div class="mt-2 text-muted" style="font-size: 13px;" id="fertilizerMaxText">
            Maximum: 0.5 kg
        </div>
    </div>
</div>

<div class="alert-custom warning mb-4" id="warningBanner" style="display: none;">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Warning:</strong> <span id="warningBannerText"></span>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <i class="fas fa-weight-hanging text-success mb-2" style="font-size: 32px;"></i>
            <div class="stat-value" id="maxWeightStat">-- kg</div>
            <div class="stat-label">Maximum Weight</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <i class="fas fa-chart-line text-primary mb-2" style="font-size: 32px;"></i>
            <div class="stat-value" id="avgWeightStat">-- kg</div>
            <div class="stat-label">Average Weight</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <i class="fas fa-arrow-down text-info mb-2" style="font-size: 32px;"></i>
            <div class="stat-value" id="minWeightStat">-- kg</div>
            <div class="stat-label">Minimum Weight</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <i class="fas fa-seedling text-warning mb-2" style="font-size: 32px;"></i>
            <div class="stat-value" id="fertilizerStat">-- kg</div>
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
                Weight Trend <span id="readingsCount">(Loading...)</span>
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
                <p class="text-muted text-center py-4">Loading weight history...</p>
            </div>
        </div>
    </div>
</div>

</div>
</div>

<script>
let weightChart = null;
const CAPACITY = 1; // 1 kg capacity
let lastUpdateTime = 0;
const UPDATE_INTERVAL = 1800000; // 30 minutes in milliseconds (30 * 60 * 1000)

function closeWarning() {
    const popup = document.getElementById('warningPopup');
    const overlay = document.getElementById('warningOverlay');
    if (popup) popup.classList.remove('show');
    if (overlay) overlay.classList.remove('show');
}

function showWarning(percentage) {
    const popup = document.getElementById('warningPopup');
    const overlay = document.getElementById('warningOverlay');
    const text = document.getElementById('warningText');
    
    text.textContent = `The bin is almost full (${percentage.toFixed(1)}%)!`;
    popup.classList.add('show');
    overlay.classList.add('show');
}

function updateWeightDisplay(weight, capacity) {
    const percentage = (weight / capacity) * 100;
    const fertilizer = weight * 0.5;
    
    // Update displays
    document.getElementById('currentWeightDisplay').textContent = weight.toFixed(4) + ' kg';
    document.getElementById('fertilizerDisplay').textContent = fertilizer.toFixed(3) + ' kg';
    document.getElementById('capacityDisplay').textContent = capacity.toFixed(1);
    document.getElementById('weightPercentText').textContent = percentage.toFixed(1) + '% Full';
    document.getElementById('fertilizerMaxText').textContent = 'Maximum: ' + (capacity * 0.5).toFixed(1) + ' kg';
    document.getElementById('fertilizerStat').textContent = fertilizer.toFixed(3) + ' kg';
    
    // Update progress bars
    const weightProgress = document.getElementById('weightProgress');
    weightProgress.style.width = Math.min(100, percentage) + '%';
    
    // Update colors based on percentage
    if (percentage >= 80) {
        weightProgress.style.background = 'linear-gradient(90deg, #ff6b6b, #ee5a6f)';
        document.getElementById('warningBanner').style.display = 'block';
        document.getElementById('warningBannerText').textContent = `The bin is reaching capacity (${percentage.toFixed(1)}%). Consider emptying soon.`;
    } else if (percentage >= 60) {
        weightProgress.style.background = 'linear-gradient(90deg, #ffd93d, #f6c23e)';
        document.getElementById('warningBanner').style.display = 'none';
    } else {
        weightProgress.style.background = 'linear-gradient(90deg, #6fcf97, #27ae60)';
        document.getElementById('warningBanner').style.display = 'none';
    }
    
    // Update fertilizer progress
    const fertilizerProgress = document.getElementById('fertilizerProgress');
    fertilizerProgress.style.width = Math.min(100, (fertilizer / (capacity * 0.5)) * 100) + '%';
    fertilizerProgress.style.background = 'linear-gradient(90deg, #ffd93d, #f6c23e)';
}

function updateStatistics(historyData) {
    if (historyData.length === 0) return;
    
    const weights = historyData.map(item => item.value);
    const maxWeight = Math.max(...weights);
    const minWeight = Math.min(...weights);
    const avgWeight = weights.reduce((a, b) => a + b, 0) / weights.length;
    
    document.getElementById('maxWeightStat').textContent = maxWeight.toFixed(4) + ' kg';
    document.getElementById('avgWeightStat').textContent = avgWeight.toFixed(4) + ' kg';
    document.getElementById('minWeightStat').textContent = minWeight.toFixed(4) + ' kg';
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

function updateHistoryDisplay(historyData) {
    const container = document.getElementById('historyContainer');
    
    if (historyData.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-4">No history data available</p>';
        return;
    }
    
    // Take last 20 readings
    const recent = historyData.slice(0, 20);
    
    container.innerHTML = recent.map((record, index) => {
        const percentage = (record.value / CAPACITY) * 100;
        let badgeClass = 'success';
        let badgeText = 'Normal';
        
        if (percentage >= 80) {
            badgeClass = 'danger';
            badgeText = 'Almost Full';
        } else if (percentage >= 60) {
            badgeClass = 'warning text-dark';
            badgeText = 'Filling';
        }
        
        const timeLabel = getRelativeTimeLabel(index, recent.length);
        
        return `
            <div class="history-item">
                <div>
                    <div class="history-value">${record.value.toFixed(4)} kg</div>
                    <div class="history-time">Reading #${record.sequenceId} - ${timeLabel}</div>
                </div>
                <div>
                    <span class="badge bg-${badgeClass}">${badgeText}</span>
                </div>
            </div>
        `;
    }).join('');
}

function updateChart(historyData) {
    const ctx = document.getElementById('weightChart').getContext('2d');
    
    // Prepare data - use sequence for X axis
    const labels = historyData.map((item, index) => {
        return `Reading ${item.sequenceId}`;
    });
    
    const weights = historyData.map(item => item.value);
    
    // Create gradient
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(76, 175, 80, 0.3)');
    gradient.addColorStop(1, 'rgba(76, 175, 80, 0.05)');
    
    // Destroy existing chart if it exists
    if (weightChart) {
        weightChart.destroy();
    }
    
    // Create new chart
    weightChart = new Chart(ctx, {
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
                            return 'Weight: ' + context.parsed.y.toFixed(4) + ' kg';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    min: 0,
                    max: CAPACITY,
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(2) + ' kg';
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

// Initialize weight monitoring with Firebase
window.initializeWeightMonitoring = function() {
    console.log('⚖️ Initializing weight monitoring...');
    
    const database = window.firebaseDatabase;
    
    // Listen to latest weight (real-time, but we'll throttle updates)
    const weightRef = window.firebaseRef(database, 'sensors/weight/latest');
    window.firebaseOnValue(weightRef, (snapshot) => {
        const now = Date.now();
        
        // Only update display every 30 minutes
        if (now - lastUpdateTime < UPDATE_INTERVAL) {
            console.log('⏱️ Skipping update (less than 30 minutes since last update)');
            return;
        }
        
        lastUpdateTime = now;
        
        const weight = snapshot.val() || 0;
        console.log('⚖️ Weight updated:', weight);
        
        updateWeightDisplay(weight, CAPACITY);
        
        // Show warning if needed
        const percentage = (weight / CAPACITY) * 100;
        if (percentage >= 80) {
            showWarning(percentage);
        }
    });
    
    // Listen to weight history (limited to last 50 readings)
    const historyQuery = window.firebaseQuery(
        window.firebaseRef(database, 'sensors/weight/history'),
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
        
        console.log('📊 Weight history loaded:', historyData.length, 'readings');
        
        // Update statistics
        updateStatistics(historyData);
        
        // Update history display
        updateHistoryDisplay(historyData);
        
        // Update chart
        updateChart(historyData);
    });
    
    console.log('✅ Weight monitoring initialized');
};

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
</script>

</body>
</html>