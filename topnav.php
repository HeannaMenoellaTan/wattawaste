<?php
require_once 'firebase_config.php';

// ==== SENSOR STATUS FUNCTION FOR FIREBASE ====
function getSensorStatus($database, $sensorType) {
    try {
        // Get latest sensor data from Firebase
        $latestRef = $database->getReference("sensors/$sensorType/latest");
        $snapshot = $latestRef->getSnapshot();
        
        if (!$snapshot->exists()) {
            return ['status' => 'faulty', 'time' => 'No data'];
        }
        
        $data = $snapshot->getValue();
        $val = $data['value'] ?? null;
        $timestamp = $data['timestamp'] ?? null;
        
        if ($val === null || $val < 0 || $timestamp === null) {
            return ['status' => 'faulty', 'time' => 'Invalid data'];
        }
        
        $diff = time() - $timestamp;
        
        if ($diff <= 10) {
            return ['status' => 'online', 'time' => $diff];
        } elseif ($diff <= 30) {
            return ['status' => 'delayed', 'time' => $diff];
        } else {
            return ['status' => 'offline', 'time' => $diff];
        }
        
    } catch (Exception $e) {
        error_log("Sensor status error for $sensorType: " . $e->getMessage());
        return ['status' => 'faulty', 'time' => 'Error'];
    }
}

// Get database instance
$database = getDatabase();

// Fetch statuses for all sensors
$tempStatus = getSensorStatus($database, 'temperature');
$humStatus  = getSensorStatus($database, 'humidity');
$gasStatus  = getSensorStatus($database, 'gas');
$phStatus   = getSensorStatus($database, 'ph');

$sensors = [
    'Temperature' => $tempStatus,
    'Humidity'    => $humStatus,
    'Gas'         => $gasStatus,
    'pH'          => $phStatus
];
?>

<!-- ===== TOP NAV ===== -->
<div class="top-nav d-flex justify-content-between align-items-center p-2 px-3 shadow-sm bg-white rounded-3">
    <h1 class="page-title m-0">WattAWaste</h1>

    <div class="top-right d-flex align-items-center gap-3">

        <!-- DATE & TIME -->
        <div id="dateTime" class="datetime fw-semibold"></div>

        <!-- SENSOR STATUS DROPDOWN -->
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle" type="button" id="sensorDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                ⚡ Sensor Status
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sensorDropdown">
                <?php foreach($sensors as $name => $sensor): 
                    $statusClass = $sensor['status']; 
                    $timeAgo = is_numeric($sensor['time']) ? $sensor['time'].'s ago' : $sensor['time'];
                    $icon = match($name){
                        'Temperature'=>'🌡️',
                        'Humidity'=>'💧',
                        'Gas'=>'💨',
                        'pH'=>'⚗️',
                        default=>'📡'
                    };
                ?>
                <li>
                    <div class="dropdown-item d-flex justify-content-between align-items-center">
                        <span><?php echo "$icon $name"; ?></span>
                        <span class="dot <?php echo $statusClass; ?>"></span>
                        <small class="text-muted"><?php echo $timeAgo; ?></small>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- FAULTY SENSOR COUNT -->
        <div class="faulty" id="faultyText">
            ⚠️ <?php 
                $faultyCount = count(array_filter($sensors, fn($s) => $s['status'] === 'faulty'));
                echo $faultyCount . ' Faulty Sensor' . ($faultyCount !== 1 ? 's' : '');
            ?>
        </div>

        <!-- PROFILE DROPDOWN -->
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle p-0 border-0" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="profile.jpg" alt="Profile" class="rounded-circle" width="40" height="40">
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                <li><a class="dropdown-item" href="#">Profile</a></li>
                <li><a class="dropdown-item" href="#">Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
            </ul>
        </div>

    </div>
</div>

<!-- ===== STYLES ===== -->
<style>
body {
    margin: 0;
    padding: 0;
}
.top-nav {
    position: sticky;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(12px);
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}
.top-nav.hidden {
    transform: translateY(-120%);
}
.page-title { color:#2E7D32; font-weight:700; font-size:1.25rem; }
.top-right .datetime { color:#2E7D32; opacity:.85; font-weight:600; }
.dropdown-menu { min-width:250px; z-index: 4000;}
.dropdown-item { display:flex; justify-content:space-between; align-items:center; }
.card, .dashboard-container {
    position: relative;
    z-index: 1;
}
.dot { width:10px; height:10px; border-radius:50%; margin-left:8px; }
.dot.online  { background:#2ecc71; box-shadow: 0 0 8px rgba(46,204,113,0.5); }
.dot.delayed { background:#f1c40f; box-shadow: 0 0 8px rgba(241,196,15,0.5); }
.dot.offline { background:#e74c3c; }
.dot.faulty  { background:#7f8c8d; }
.faulty { font-size:14px; font-weight:600; color:#E65100; }
.main {
    padding-top: 0;
}
</style>

<!-- ===== SCRIPTS ===== -->
<script>
function updateDateTime(){
    const now = new Date();
    const options = { month:'short', day:'numeric', year:'numeric', hour:'2-digit', minute:'2-digit' };
    document.getElementById('dateTime').textContent = now.toLocaleString('en-US', options);
}
setInterval(updateDateTime, 1000);
updateDateTime();

// Update faulty sensor count dynamically
function updateFaultyCount() {
    const dots = document.querySelectorAll('.dropdown-item .dot.faulty');
    const count = dots.length;
    document.getElementById('faultyText').textContent = `⚠️ ${count} Faulty Sensor${count!==1?'s':''}`;
}
updateFaultyCount();

// Live refresh of sensor dropdown from Firebase
async function refreshSensors(){
    try{
        const res = await fetch('sensor_status.php');
        const data = await res.json();
        const dropdown = document.querySelector('#sensorDropdown + .dropdown-menu');
        dropdown.innerHTML = '';
        data.forEach(sensor => {
            const iconMap = {'Temperature':'🌡️','Humidity':'💧','Gas':'💨','pH':'⚗️'};
            const icon = iconMap[sensor.name]||'📡';
            const timeAgo = isNaN(sensor.lastUpdate)?sensor.lastUpdate:sensor.lastUpdate+'s ago';
            dropdown.innerHTML += `
                <li>
                    <div class="dropdown-item d-flex justify-content-between align-items-center">
                        <span>${icon} ${sensor.name}</span>
                        <span class="dot ${sensor.class}"></span>
                        <small class="text-muted">${timeAgo}</small>
                    </div>
                </li>
            `;
        });
        updateFaultyCount();
    } catch(e){ console.error('Sensor fetch error', e); }
}
setInterval(refreshSensors, 3000);

// Auto-hide navigation on scroll down
let lastScroll = 0;
const topNav = document.querySelector('.top-nav');

window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
    if (currentScroll > lastScroll && currentScroll > 80) {
        topNav.classList.add('hidden');
    } else {
        topNav.classList.remove('hidden');
    }
    lastScroll = currentScroll <= 0 ? 0 : currentScroll;
);
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>