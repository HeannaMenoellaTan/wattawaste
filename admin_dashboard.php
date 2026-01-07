<?php
/**
 * Admin Dashboard - Complete Firebase Implementation
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
    
    // ===== FETCH LATEST SENSOR VALUES =====
    $currentTemp = $database->getReference("sensors/temperature/latest/value")->getValue() ?? 0;
    $currentHumidity = $database->getReference("sensors/humidity/latest/value")->getValue() ?? 0;
    $currentGas = $database->getReference("sensors/gas/latest/value")->getValue() ?? 0;
    $currentPH = $database->getReference("sensors/ph/latest/value")->getValue() ?? 0;
    $weight_lvl = $database->getReference("sensors/weight/latest/value")->getValue() ?? 0;
    $weight_capacity = $database->getReference("sensors/weight/latest/capacity")->getValue() ?? 50;
    
    // Calculate percentages
    $w_percent = $weight_capacity > 0 ? round(($weight_lvl / $weight_capacity) * 100, 1) : 0;
    $dailyWaste = $weight_lvl;
    $fertilizerConsumed = 400; // Sample value
    $fertilizerRemainingPercent = $w_percent;
    
    // ===== FETCH HISTORY DATA FOR GRAPHS =====
    function fetchHistoryData($database, $sensor) {
        $historyRef = $database->getReference("sensors/$sensor/history");
        $historySnapshot = $historyRef->getSnapshot();
        
        $data = [];
        if ($historySnapshot->exists()) {
            foreach ($historySnapshot->getValue() as $key => $item) {
                $data[] = [
                    'value' => $item['value'] ?? 0,
                    'timestamp' => $item['timestamp'] ?? time()
                ];
            }
        }
        
        // Sort by timestamp
        usort($data, function($a, $b) {
            return $a['timestamp'] - $b['timestamp'];
        });
        
        // Limit to last 20 readings for performance
        return array_slice($data, -20);
    }
    
    $temperatureData = fetchHistoryData($database, 'temperature');
    $humidityData = fetchHistoryData($database, 'humidity');
    $gasData = fetchHistoryData($database, 'gas');
    
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
    $temperatureData = [];
    $humidityData = [];
    $gasData = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - WattAWaste</title>
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
        console.log('✅ Admin authenticated:', user.email || user.phoneNumber);
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
</head>
<body>

<?php include 'sidebar.php'; ?>
<div class="main">
<?php include 'topnav.php'; ?>

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
                <div class="stat-value"><?php echo number_format($dailyWaste, 1); ?></div>
                <div class="stat-label">Daily Waste Weight (kg)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="stat-icon" style="background: #D1ECF1;">
                    <i class="fas fa-seedling" style="color: #0c5460;"></i>
                </div>
                <div class="stat-value"><?php echo $fertilizerConsumed; ?></div>
                <div class="stat-label">Fertilizer Consumed (kg)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="stat-icon" style="background: #F8D7DA;">
                    <i class="fas fa-percentage" style="color: #721c24;"></i>
                </div>
                <div class="stat-value"><?php echo $fertilizerRemainingPercent; ?>%</div>
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

<script>
// Prepare chart data
const temperatureData = <?php echo json_encode($temperatureData); ?>;
const humidityData = <?php echo json_encode($humidityData); ?>;
const gasData = <?php echo json_encode($gasData); ?>;

// Format timestamps
function formatTime(timestamp) {
    const date = new Date(timestamp * 1000);
    return date.toLocaleString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit' 
    });
}

const tempLabels = temperatureData.map(item => formatTime(item.timestamp));
const tempValues = temperatureData.map(item => item.value);

const humLabels = humidityData.map(item => formatTime(item.timestamp));
const humValues = humidityData.map(item => item.value);

const gasLabels = gasData.map(item => formatTime(item.timestamp));
const gasValues = gasData.map(item => item.value);

// Temperature Chart
new Chart(document.getElementById('tempChart'), {
    type: 'line',
    data: {
        labels: tempLabels,
        datasets: [{
            label: 'Temperature (°C)',
            data: tempValues,
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

// Humidity Chart
new Chart(document.getElementById('humChart'), {
    type: 'line',
    data: {
        labels: humLabels,
        datasets: [{
            label: 'Humidity (%)',
            data: humValues,
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

// Gas Chart
new Chart(document.getElementById('gasChart'), {
    type: 'bar',
    data: {
        labels: gasLabels,
        datasets: [{
            label: 'Gas Level (ppm)',
            data: gasValues,
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

// Real-time updates for current values
async function fetchLatestSensorData() {
    try {
        const res = await fetch('api/get_latest.php', { cache: 'no-store' });
        const { latest } = await res.json();
        
        if (latest) {
            if (latest.temperature !== undefined) {
                document.getElementById('currentTemp').textContent = 
                    parseFloat(latest.temperature).toFixed(1) + '°C';
            }
            if (latest.gas !== undefined) {
                document.getElementById('currentGas').textContent = 
                    parseFloat(latest.gas).toFixed(0) + ' ppm';
            }
            if (latest.ph !== undefined) {
                document.getElementById('currentPH').textContent = 
                    parseFloat(latest.ph).toFixed(1);
            }
        }
    } catch (e) {
        console.error('Error fetching sensor data:', e);
    }
}

// Update every 5 seconds
setInterval(fetchLatestSensorData, 5000);

// Log admin access
console.log('🔒 Admin Dashboard loaded - User: <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>');
</script>

</body>
</html>