<?php
/**
 * Admin Dashboard - Complete Firebase Implementation with Real-Time Data
 * STRICTLY FOR ADMIN USE ONLY
 */

// ===== STEP 1: VERIFY ADMIN ACCESS =====
require_once 'firebase_admin_check.php';
requireAdmin(); // Automatically redirects non-admins

// ===== STEP 2: LOAD FIREBASE =====
require_once 'firebase_config.php';

try {
    $database = getDatabase();
    
    // ===== FETCH USER COUNT FROM FIREBASE =====
    $usersRef = $database->getReference('users');
    $usersSnapshot = $usersRef->getSnapshot();
    $totalUsers = $usersSnapshot->exists() ? count($usersSnapshot->getValue()) : 0;
    
    // ===== FETCH LATEST SENSOR VALUES (Initial Load Only) =====
    $currentTemp = $database->getReference("sensors/temperature/latest")->getValue() ?? 0;
    $currentHumidity = $database->getReference("sensors/humidity/latest")->getValue() ?? 0;
    $currentGas = $database->getReference("sensors/gas/latest")->getValue() ?? 0;
    $currentPH = $database->getReference("sensors/ph/latest")->getValue() ?? 0;
    $weight_lvl = $database->getReference("sensors/weight/latest")->getValue() ?? 0;
    $weight_capacity = 1; // Default capacity
    
    // Calculate percentages
    $w_percent = $weight_capacity > 0 ? round(($weight_lvl / $weight_capacity) * 100, 1) : 0;
    $dailyWaste = $weight_lvl;
    $fertilizerConsumed = 400; // Sample value
    $fertilizerRemainingPercent = $w_percent;
    
} catch (Exception $e) {
    error_log("Admin Dashboard Error: " . $e->getMessage());
    $totalUsers = 0;
    $currentTemp = 0;
    $currentHumidity = 0;
    $currentGas = 0;
    $currentPH = 0;
    $dailyWaste = 0;
    $fertilizerConsumed = 0;
    $fertilizerRemainingPercent = 0;
}

// Include admin navigation
require_once 'admin_nav.php';
?>

<!-- Admin Dashboard Content -->
<div class="container-fluid px-4 py-4">

    <!-- Admin Info Badge -->
    <div class="admin-info-badge">
        <i class="fas fa-info-circle"></i>
        <div>
            <strong>Logged in as Admin:</strong> 
            <?php echo htmlspecialchars($_SESSION['username'] ?? 'Administrator'); ?> 
            (<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>)
        </div>
    </div>

    <h1 class="page-title">
        <i class="fas fa-shield-alt text-danger me-2"></i>
        Admin Dashboard
        <span class="admin-badge">
            <i class="fas fa-user-shield me-1"></i>ADMIN ONLY
        </span>
    </h1>

    <!-- Top Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value" id="totalUsers"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="stat-icon" style="background: #FFF3CD;">
                    <i class="fas fa-trash" style="color: #856404;"></i>
                </div>
                <div class="stat-value" id="dailyWasteValue"><?php echo number_format($dailyWaste, 4); ?></div>
                <div class="stat-label">Daily Waste Weight (kg)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="stat-icon" style="background: #D1ECF1;">
                    <i class="fas fa-seedling" style="color: #0c5460;"></i>
                </div>
                <div class="stat-value" id="fertilizerConsumedValue"><?php echo $fertilizerConsumed; ?></div>
                <div class="stat-label">Fertilizer Consumed (kg)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="stat-icon" style="background: #F8D7DA;">
                    <i class="fas fa-percentage" style="color: #721c24;"></i>
                </div>
                <div class="stat-value" id="fertilizerRemainingValue"><?php echo $fertilizerRemainingPercent; ?>%</div>
                <div class="stat-label">Fertilizer Remaining</div>
            </div>
        </div>
    </div>

    <!-- Sensor Trend Graphs -->
    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card p-3">
                <h5 class="mb-3"><i class="fas fa-thermometer-half text-danger me-2"></i>Temperature Trend</h5>
                <div class="graph-card">
                    <canvas id="tempChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card p-3">
                <h5 class="mb-3"><i class="fas fa-tint text-info me-2"></i>Humidity Trend</h5>
                <div class="graph-card">
                    <canvas id="humChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card p-3">
                <h5 class="mb-3"><i class="fas fa-wind text-warning me-2"></i>Gas Level Trend</h5>
                <div class="graph-card">
                    <canvas id="gasChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Sensor Values -->
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card current-value-card">
                <div><i class="fas fa-thermometer-half text-danger" style="font-size: 32px;"></i></div>
                <h5 class="mt-2">Current Temperature</h5>
                <div class="current-value temp-value" id="currentTemp"><?php echo number_format($currentTemp, 1); ?>°C</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card current-value-card">
                <div><i class="fas fa-wind text-primary" style="font-size: 32px;"></i></div>
                <h5 class="mt-2">Current Gas Level</h5>
                <div class="current-value gas-value" id="currentGas"><?php echo number_format($currentGas, 0); ?> ppm</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card current-value-card">
                <div><i class="fas fa-vial text-warning" style="font-size: 32px;"></i></div>
                <h5 class="mt-2">Current pH Level</h5>
                <div class="current-value ph-value" id="currentPH"><?php echo number_format($currentPH, 1); ?></div>
            </div>
        </div>
    </div>

</div>
</div>
</div>

<!-- Bootstrap & Chart.js -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- Firebase Integration -->
<script type="module">
import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
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
const database = getDatabase(app);

// Make Firebase available globally
window.firebaseDatabase = database;
window.firebaseRef = ref;
window.firebaseOnValue = onValue;
window.firebaseQuery = query;
window.firebaseLimitToLast = limitToLast;

console.log('🔥 Firebase initialized for Admin Dashboard');

// Initialize real-time listeners after DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.setupAdminFirebaseListeners();
});
</script>

<style>
:root {
    --brand: #4CAF50;
    --brand-dark: #2E7D32;
    --ink: #333;
    --panel: #fff;
    --muted: #555;
    --bg: #F9FAFB;
}

body {
    font-family: Poppins, system-ui, Arial;
    background: var(--bg);
    margin: 0;
}

.card {
    background: var(--panel);
    border-radius: 16px;
    padding: 20px;
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

.admin-badge {
    display: inline-block;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    margin-left: 12px;
}

.stat-card {
    text-align: center;
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: #E8F5E9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
}

.stat-icon i {
    font-size: 28px;
    color: var(--brand-dark);
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

.graph-card {
    height: 300px;
}

.current-value-card {
    text-align: center;
    padding: 24px;
}

.current-value {
    font-size: 42px;
    font-weight: 800;
    line-height: 1;
    margin-top: 12px;
}

.temp-value { color: #ff5733; }
.gas-value { color: #2e86de; }
.ph-value { color: #f59e0b; }

.admin-info-badge {
    background: #E3F2FD;
    border-left: 4px solid #2196F3;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.admin-info-badge i {
    color: #2196F3;
    font-size: 20px;
}
</style>

<script>
// Initialize charts as null
let tempChart = null;
let humChart = null;
let gasChart = null;

// Setup Firebase Real-Time Listeners for Charts
window.setupAdminFirebaseListeners = function() {
    const database = window.firebaseDatabase;
    console.log('🔥 Setting up Admin Firebase real-time listeners...');
    
    // ========== TEMPERATURE CHART ==========
    const tempHistoryQuery = window.firebaseQuery(
        window.firebaseRef(database, 'sensors/temperature/history'),
        window.firebaseLimitToLast(50)
    );
    
    window.firebaseOnValue(tempHistoryQuery, (snapshot) => {
        const historyData = [];
        
        if (snapshot.exists()) {
            snapshot.forEach((childSnapshot) => {
                const timestamp = parseInt(childSnapshot.key);
                const value = childSnapshot.val();
                
                historyData.push({
                    timestamp: timestamp,
                    value: value
                });
            });
        }
        
        // Sort by timestamp (oldest to newest for chart)
        historyData.sort((a, b) => a.timestamp - b.timestamp);
        
        console.log('📊 Temperature history loaded:', historyData.length, 'readings');
        console.log('📊 Temperature data:', historyData);
        
        // If no data, show placeholder
        if (historyData.length === 0) {
            console.warn('⚠️ No temperature history data available');
            historyData.push({ timestamp: Date.now(), value: 25 }); // Placeholder
        }
        
        // Prepare chart data
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
        
        console.log('📊 Temperature labels:', labels);
        console.log('📊 Temperature values:', temperatures);
        
        // Create gradient
        const ctx = document.getElementById('tempChart').getContext('2d');
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
                labels: labels,
                datasets: [{
                    label: 'Temperature (°C)',
                    data: temperatures,
                    borderColor: '#ff5733',
                    backgroundColor: 'rgba(255, 87, 51, 0.2)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Temperature: ' + context.parsed.y.toFixed(1) + '°C';
                            }
                        }
                    }
                },
                scales: { 
                    x: { display: false },
                    y: { 
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + '°C';
                            }
                        }
                    }
                }
            }
        });
        
        console.log('✅ Temperature chart created');
    });
    
    // ========== HUMIDITY CHART ==========
    const humHistoryQuery = window.firebaseQuery(
        window.firebaseRef(database, 'sensors/humidity/history'),
        window.firebaseLimitToLast(50)
    );
    
    window.firebaseOnValue(humHistoryQuery, (snapshot) => {
        const historyData = [];
        
        if (snapshot.exists()) {
            snapshot.forEach((childSnapshot) => {
                const timestamp = parseInt(childSnapshot.key);
                const value = childSnapshot.val();
                
                historyData.push({
                    timestamp: timestamp,
                    value: value
                });
            });
        }
        
        // Sort by timestamp
        historyData.sort((a, b) => a.timestamp - b.timestamp);
        
        console.log('📊 Humidity history loaded:', historyData.length, 'readings');
        console.log('📊 Humidity data:', historyData);
        
        // If no data, show placeholder
        if (historyData.length === 0) {
            console.warn('⚠️ No humidity history data available');
            historyData.push({ timestamp: Date.now(), value: 50 }); // Placeholder
        }
        
        const labels = historyData.map(item => {
            const date = new Date(item.timestamp);
            return date.toLocaleString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit' 
            });
        });
        
        const humidity = historyData.map(item => item.value);
        
        console.log('📊 Humidity labels:', labels);
        console.log('📊 Humidity values:', humidity);
        
        const ctx = document.getElementById('humChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(0, 180, 216, 0.3)');
        gradient.addColorStop(1, 'rgba(72, 202, 228, 0.05)');
        
        if (humChart) {
            humChart.destroy();
        }
        
        humChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Humidity (%)',
                    data: humidity,
                    borderColor: '#00b4d8',
                    backgroundColor: 'rgba(0, 180, 216, 0.2)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Humidity: ' + context.parsed.y.toFixed(1) + '%';
                            }
                        }
                    }
                },
                scales: { 
                    x: { display: false },
                    y: { 
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
        
        console.log('✅ Humidity chart created');
    });
    
    // ========== GAS CHART ==========
    const gasHistoryQuery = window.firebaseQuery(
        window.firebaseRef(database, 'sensors/gas/history'),
        window.firebaseLimitToLast(50)
    );
    
    window.firebaseOnValue(gasHistoryQuery, (snapshot) => {
        const historyData = [];
        
        if (snapshot.exists()) {
            snapshot.forEach((childSnapshot) => {
                const timestamp = parseInt(childSnapshot.key);
                const value = childSnapshot.val();
                
                historyData.push({
                    timestamp: timestamp,
                    value: value
                });
            });
        }
        
        // Sort by timestamp
        historyData.sort((a, b) => a.timestamp - b.timestamp);
        
        console.log('📊 Gas history loaded:', historyData.length, 'readings');
        console.log('📊 Gas data:', historyData);
        
        // If no data, show placeholder
        if (historyData.length === 0) {
            console.warn('⚠️ No gas history data available');
            historyData.push({ timestamp: Date.now(), value: 100 }); // Placeholder
        }
        
        const labels = historyData.map(item => {
            const date = new Date(item.timestamp);
            return date.toLocaleString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit' 
            });
        });
        
        const gas = historyData.map(item => item.value);
        
        console.log('📊 Gas labels:', labels);
        console.log('📊 Gas values:', gas);
        
        const ctx = document.getElementById('gasChart').getContext('2d');
        
        if (gasChart) {
            gasChart.destroy();
        }
        
        gasChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Gas Level (ppm)',
                    data: gas,
                    backgroundColor: '#2e86de'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Gas: ' + context.parsed.y.toFixed(0) + ' ppm';
                            }
                        }
                    }
                },
                scales: { 
                    x: { display: false },
                    y: { 
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + ' ppm';
                            }
                        }
                    }
                }
            }
        });
        
        console.log('✅ Gas chart created');
    });
    
    // ========== CURRENT VALUES (Latest readings) ==========
    // Temperature listener
    const tempRef = window.firebaseRef(database, 'sensors/temperature/latest');
    window.firebaseOnValue(tempRef, (snapshot) => {
        const temperature = snapshot.val() || 0;
        console.log('🌡️ Temperature updated:', temperature);
        document.getElementById('currentTemp').textContent = temperature.toFixed(1) + '°C';
    });

    // Gas listener
    const gasRef = window.firebaseRef(database, 'sensors/gas/latest');
    window.firebaseOnValue(gasRef, (snapshot) => {
        const gas = snapshot.val() || 0;
        console.log('🔥 Gas level updated:', gas);
        document.getElementById('currentGas').textContent = gas.toFixed(2) + ' ppm';
    });

    // pH listener
    const phRef = window.firebaseRef(database, 'sensors/ph/latest');
    window.firebaseOnValue(phRef, (snapshot) => {
        const ph = snapshot.val() || 0;
        console.log('⚗️ pH level updated:', ph);
        document.getElementById('currentPH').textContent = ph.toFixed(1);
    });

    // Weight listener
    const weightRef = window.firebaseRef(database, 'sensors/weight/latest');
    window.firebaseOnValue(weightRef, (snapshot) => {
        const weight = snapshot.val() || 0;
        console.log('⚖️ Weight updated:', weight);
        document.getElementById('dailyWasteValue').textContent = weight.toFixed(4);
    });

    console.log('✅ All Admin Firebase listeners initialized!');
};

// Log admin access
console.log('🔒 Admin Dashboard loaded - User: <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>');
</script>

</body>
</html>