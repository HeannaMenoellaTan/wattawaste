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
<title>Temperature Monitor - WattAWaste</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://kit.fontawesome.com/a2e0e6ad65.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Firebase Auth and Database -->
<script type="module">
import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
import { getAuth, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
import { getDatabase, ref, onValue, query, limitToLast, remove } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-database.js';

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
        
        // Initialize temperature monitoring after authentication
        window.initializeTemperatureMonitoring();
    }
});

window.firebaseAuth = auth;
window.firebaseDatabase = database;
window.firebaseRef = ref;
window.firebaseOnValue = onValue;
window.firebaseQuery = query;
window.firebaseLimitToLast = limitToLast;
window.firebaseRemove = remove;
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
                <div class="temp-display-large" id="tempDisplay">--°C</div>
                <div class="status-badge-large ok" id="statusBadge">
                    Loading...
                </div>
                <div class="mt-3 small" style="opacity: 0.9; position: relative; z-index: 1;" id="statusDesc">
                    Fetching temperature data from sensors...
                </div>
                <div class="mt-2 small" style="opacity: 0.8; position: relative; z-index: 1;" id="lastUpdateText">
                    Last updated: --
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card p-4 h-100 d-flex align-items-center justify-content-center">
                <h6 class="text-center mb-3">Visual Indicator</h6>
                <div class="thermo-visual">
                    <div class="thermo-indicator" id="thermoIndicator" style="height: 0%;"></div>
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

    <div class="alert-custom warning mb-4" id="warningBanner" style="display: none;">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Warning:</strong> <span id="warningText">Temperature is outside the optimal composting range (20-70°C). Monitor closely.</span>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card">
                <i class="fas fa-temperature-high text-danger mb-2" style="font-size: 32px;"></i>
                <div class="stat-value" id="maxTempStat">--°C</div>
                <div class="stat-label">Maximum Temperature</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <i class="fas fa-chart-line text-success mb-2" style="font-size: 32px;"></i>
                <div class="stat-value" id="avgTempStat">--°C</div>
                <div class="stat-label">Average Temperature</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <i class="fas fa-temperature-low text-info mb-2" style="font-size: 32px;"></i>
                <div class="stat-value" id="minTempStat">--°C</div>
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
                    Temperature Trend <span id="readingsCount">(Loading...)</span>
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
                    <p class="text-muted text-center py-4">Loading temperature history...</p>
                </div>
            </div>
        </div>
    </div>

</div>
</div>

<script>
let tempChart = null;
let lastUpdateTime = 0;
const UPDATE_INTERVAL = 1800000; // 30 minutes in milliseconds (30 * 60 * 1000)

// Update thermometer visual indicator
function updateThermoIndicator(temp) {
    const indicator = document.getElementById('thermoIndicator');
    const percentage = Math.min(Math.max((temp / 100) * 100, 0), 100);
    indicator.style.height = percentage + '%';
}

// Get temperature status
function getTemperatureStatus(temp) {
    if (temp >= 45 && temp <= 70) {
        return {
            status: 'Thermophilic (Active)',
            statusClass: 'ok',
            desc: 'Optimal composting temperature. High microbial activity.'
        };
    } else if (temp >= 20 && temp < 45) {
        return {
            status: 'Mesophilic (Initial)',
            statusClass: 'warn',
            desc: 'Initial composting phase. Microbes breaking down materials.'
        };
    } else if (temp < 20) {
        return {
            status: 'Too Cold',
            statusClass: 'crit',
            desc: 'Temperature too low for effective composting.'
        };
    } else {
        return {
            status: 'Too Hot',
            statusClass: 'crit',
            desc: 'Temperature exceeds safe composting range.'
        };
    }
}

// Update temperature display
function updateTemperatureDisplay(temp) {
    const status = getTemperatureStatus(temp);
    
    // Update display
    document.getElementById('tempDisplay').textContent = temp.toFixed(1) + '°C';
    document.getElementById('statusBadge').textContent = status.status;
    document.getElementById('statusDesc').textContent = status.desc;
    document.getElementById('lastUpdateText').textContent = 'Last updated: ' + new Date().toLocaleString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
    
    // Update thermometer indicator
    updateThermoIndicator(temp);
    
    // Update badge classes
    const badge = document.getElementById('statusBadge');
    badge.classList.remove('ok', 'warn', 'crit');
    badge.classList.add(status.statusClass);
    
    // Update warning banner
    const warningBanner = document.getElementById('warningBanner');
    if (temp < 15 || temp > 75) {
        warningBanner.style.display = 'block';
    } else {
        warningBanner.style.display = 'none';
    }
}

// Update statistics
function updateStatistics(historyData) {
    if (historyData.length === 0) return;
    
    const temperatures = historyData.map(item => item.value);
    const maxTemp = Math.max(...temperatures);
    const minTemp = Math.min(...temperatures);
    const avgTemp = temperatures.reduce((a, b) => a + b, 0) / temperatures.length;
    
    document.getElementById('maxTempStat').textContent = maxTemp.toFixed(1) + '°C';
    document.getElementById('avgTempStat').textContent = avgTemp.toFixed(1) + '°C';
    document.getElementById('minTempStat').textContent = minTemp.toFixed(1) + '°C';
}

// Update history display
function updateHistoryDisplay(historyData) {
    const container = document.getElementById('historyContainer');
    
    if (historyData.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-4">No history data available</p>';
        return;
    }
    
    // Take last 10 readings
    const recent = historyData.slice(0, 10);
    
    container.innerHTML = recent.map(record => {
        const val = record.value;
        let badgeClass = 'success';
        let badgeText = 'Active';
        
        if (val >= 45 && val <= 70) {
            badgeClass = 'success';
            badgeText = 'Active';
        } else if (val >= 20 && val < 45) {
            badgeClass = 'warning';
            badgeText = 'Initial';
        } else {
            badgeClass = 'danger';
            badgeText = 'Alert';
        }
        
        const date = new Date(record.timestamp);
        const timeString = date.toLocaleString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
        
        return `
            <div class="history-item">
                <div>
                    <div class="history-value">${val.toFixed(1)}°C</div>
                    <div class="history-time">${timeString}</div>
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
    const ctx = document.getElementById('tempChart').getContext('2d');
    
    // Prepare data
    const labels = historyData.map(item => {
        const date = new Date(item.timestamp);
        return date.toLocaleString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    });
    
    const temperatures = historyData.map(item => item.value);
    
    // Create gradient
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(255, 123, 0, 0.3)');
    gradient.addColorStop(1, 'rgba(255, 0, 0, 0.05)');
    
    // Destroy existing chart if it exists
    if (tempChart) {
        tempChart.destroy();
    }
    
    // Create new chart
    tempChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.reverse(),
            datasets: [{
                label: 'Temperature (°C)',
                data: temperatures.reverse(),
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
    
    document.getElementById('readingsCount').textContent = `(Last ${historyData.length} Readings)`;
}

// Initialize temperature monitoring with Firebase
window.initializeTemperatureMonitoring = function() {
    console.log('🌡️ Initializing temperature monitoring...');
    
    const database = window.firebaseDatabase;
    
    // Listen to latest temperature (real-time, but throttled to 30 minutes)
    const tempRef = window.firebaseRef(database, 'sensors/temperature/latest');
    window.firebaseOnValue(tempRef, (snapshot) => {
        const now = Date.now();
        
        // Only update display every 30 minutes
        if (now - lastUpdateTime < UPDATE_INTERVAL) {
            console.log('⏱️ Skipping temperature update (less than 30 minutes since last update)');
            return;
        }
        
        lastUpdateTime = now;
        
        const temp = snapshot.val() || 0;
        console.log('🌡️ Temperature updated:', temp);
        
        updateTemperatureDisplay(temp);
    });
    
    // Listen to temperature history (limited to last 10 readings)
    const historyQuery = window.firebaseQuery(
        window.firebaseRef(database, 'sensors/temperature/history'),
        window.firebaseLimitToLast(10)
    );
    
    window.firebaseOnValue(historyQuery, async (snapshot) => {
        const historyData = [];
        const allKeys = [];
        
        if (snapshot.exists()) {
            snapshot.forEach((childSnapshot) => {
                const timestamp = parseInt(childSnapshot.key);
                const value = childSnapshot.val();
                
                allKeys.push(timestamp);
                
                historyData.push({
                    timestamp: timestamp,
                    value: value
                });
            });
        }
        
        // Sort by timestamp (newest first)
        historyData.sort((a, b) => b.timestamp - a.timestamp);
        
        // Keep only the 10 most recent readings and delete older ones
        if (allKeys.length > 10) {
            const sortedKeys = allKeys.sort((a, b) => b - a);
            const keysToDelete = sortedKeys.slice(10); // Get keys beyond the 10 most recent
            
            // Delete old records from Firebase
            for (const key of keysToDelete) {
                const recordRef = window.firebaseRef(database, `sensors/temperature/history/${key}`);
                try {
                    await window.firebaseRemove(recordRef);
                    console.log(`🗑️ Deleted old temperature record: ${key}`);
                } catch (err) {
                    console.error(`❌ Failed to delete record ${key}:`, err);
                }
            }
        }
        
        console.log('📊 Temperature history loaded:', historyData.length, 'readings');
        
        // Update statistics
        updateStatistics(historyData);
        
        // Update history display
        updateHistoryDisplay(historyData);
        
        // Update chart
        updateChart(historyData);
    });
    
    console.log('✅ Temperature monitoring initialized');
};
</script>

</body>
</html>