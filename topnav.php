<?php
require_once 'firebase_config.php';

function getSensorStatus($database, $sensorType) {
    try {
        $latestRef = $database->getReference("sensors/$sensorType/latest");
        $snapshot = $latestRef->getSnapshot();
        if (!$snapshot->exists()) return ['status' => 'faulty', 'time' => 'No data'];
        $data = $snapshot->getValue();
        $val = $data['value'] ?? null;
        $timestamp = $data['timestamp'] ?? null;
        if ($val === null || $val < 0) return ['status' => 'faulty', 'time' => 'Invalid'];
        if ($timestamp === null) return ['status' => 'faulty', 'time' => 'No timestamp'];
        $diff = time() - $timestamp;
        if ($diff <= 10) return ['status' => 'online', 'time' => $diff];
        elseif ($diff <= 30) return ['status' => 'delayed', 'time' => $diff];
        else return ['status' => 'offline', 'time' => $diff];
    } catch (Exception $e) {
        return ['status' => 'faulty', 'time' => 'Error'];
    }
}

$database = getDatabase();
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

$faultyCount = 0;
foreach($sensors as $sensor) {
    if($sensor['status'] === 'faulty') $faultyCount++;
}
?>

<!-- ===== TOP NAV ===== -->
<div class="top-nav d-flex justify-content-between align-items-center p-2 px-3 shadow-sm bg-white rounded-3" id="topNav">
    <h1 class="page-title m-0">Leafcycle</h1>

    <div class="top-right d-flex align-items-center gap-2 gap-md-3">

        <!-- DATE & TIME (hidden on small mobile) -->
        <div id="dateTime" class="datetime fw-semibold d-none d-md-block"></div>

        <!-- SENSOR STATUS DROPDOWN -->
        <div class="dropdown">
            <button class="btn btn-light btn-sm dropdown-toggle px-2 px-md-3" type="button" id="sensorDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="d-none d-sm-inline">⚡ Sensor Status</span>
                <span class="d-inline d-sm-none">⚡</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sensorDropdown">
                <?php foreach($sensors as $name => $sensor): 
                    $statusClass = $sensor['status']; 
                    $timeAgo = is_numeric($sensor['time']) ? $sensor['time'].'s ago' : $sensor['time'];
                    $icon = match($name){
                        'Temperature'=>'🌡️', 'Humidity'=>'💧', 'Gas'=>'💨', 'pH'=>'⚗️', default=>'📡'
                    };
                ?>
                <li>
                    <div class="dropdown-item d-flex justify-content-between align-items-center gap-2">
                        <span><?php echo "$icon $name"; ?></span>
                        <span class="dot <?php echo $statusClass; ?>"></span>
                        <small class="text-muted ms-auto"><?php echo $timeAgo; ?></small>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- FAULTY COUNT (icon only on mobile) -->
        <div class="faulty" id="faultyText">
            <span class="d-none d-sm-inline">⚠️ <?php echo $faultyCount; ?> Faulty Sensor<?php echo $faultyCount !== 1 ? 's' : ''; ?></span>
            <span class="d-inline d-sm-none" title="Faulty sensors">⚠️ <?php echo $faultyCount; ?></span>
        </div>

        <!-- PROFILE DROPDOWN -->
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle p-0 border-0" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="profile.jpg" alt="Profile" class="rounded-circle" width="36" height="36" style="object-fit:cover;">
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                <li><a class="dropdown-item" href="profile.php">
                    <i class="fas fa-user me-2"></i>Profile
                </a></li>
                <!-- Date/time shown in dropdown on mobile -->
                <li class="d-md-none">
                    <div class="dropdown-item text-muted small" id="dateTimeMobile"></div>
                </li>
            </ul>
        </div>

    </div>
</div>

<!-- ===== STYLES ===== -->
<style>
.top-nav {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: rgba(255,255,255,0.97);
    backdrop-filter: blur(12px);
    border-radius: 12px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}
.top-nav.hidden { transform: translateY(-120%); }

.page-title { color:#2E7D32; font-weight:700; font-size:clamp(1rem, 3vw, 1.25rem); }
.top-right .datetime { color:#2E7D32; opacity:.85; font-weight:600; font-size: clamp(.75rem, 2vw, .95rem); }

.dropdown-menu { min-width: 230px; z-index: 4000; }
.dropdown-item { display:flex; justify-content:space-between; align-items:center; }

.dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.dot.online  { background:#2ecc71; }
.dot.delayed { background:#f1c40f; }
.dot.offline { background:#e74c3c; }
.dot.faulty  { background:#7f8c8d; }

.faulty { font-size: clamp(12px, 2.5vw, 14px); font-weight:600; color:#E65100; white-space:nowrap; }

#profileDropdown + .dropdown-menu .dropdown-item { padding: 10px 16px; transition: all 0.2s ease; }
#profileDropdown + .dropdown-menu .dropdown-item:hover { background: rgba(76,175,80,0.1); color: #2E7D32; }
#profileDropdown + .dropdown-menu .dropdown-item i { color: #4CAF50; }

/* Mobile: top-nav shifts right for hamburger button */
@media (max-width: 768px) {
    .top-nav {
        border-radius: 10px;
        padding-left: 60px !important; /* space for hamburger */
    }
}
</style>

<!-- ===== SCRIPTS ===== -->
<script>
function updateDateTime(){
    const now = new Date();
    const options = { month:'short', day:'numeric', year:'numeric', hour:'2-digit', minute:'2-digit' };
    const str = now.toLocaleString('en-US', options);
    const el = document.getElementById('dateTime');
    const elM = document.getElementById('dateTimeMobile');
    if (el) el.textContent = str;
    if (elM) elM.textContent = str;
}
setInterval(updateDateTime, 1000);
updateDateTime();

function updateFaultyCount() {
    const faultyDots = document.querySelectorAll('#sensorDropdown + .dropdown-menu .dot.faulty');
    const count = faultyDots.length;
    const el = document.getElementById('faultyText');
    if (!el) return;
    el.innerHTML = `
        <span class="d-none d-sm-inline">⚠️ ${count} Faulty Sensor${count!==1?'s':''}</span>
        <span class="d-inline d-sm-none" title="Faulty sensors">⚠️ ${count}</span>
    `;
}

async function refreshSensors(){
    try{
        const res = await fetch('sensor_status.php');
        const data = await res.json();
        const dropdown = document.querySelector('#sensorDropdown + .dropdown-menu');
        if (!dropdown) return;
        dropdown.innerHTML = '';
        data.forEach(sensor => {
            const iconMap = {'Temperature':'🌡️','Humidity':'💧','Gas':'💨','pH':'⚗️'};
            const icon = iconMap[sensor.name]||'📡';
            const timeAgo = isNaN(sensor.lastUpdate)?sensor.lastUpdate:sensor.lastUpdate+'s ago';
            dropdown.innerHTML += `
                <li>
                    <div class="dropdown-item d-flex justify-content-between align-items-center gap-2">
                        <span>${icon} ${sensor.name}</span>
                        <span class="dot ${sensor.class}"></span>
                        <small class="text-muted ms-auto">${timeAgo}</small>
                    </div>
                </li>
            `;
        });
        updateFaultyCount();
    } catch(e){ console.error('Sensor fetch error', e); }
}
setInterval(refreshSensors, 3000);

// Hide on scroll down, show on scroll up
let lastScroll = 0;
const topNav = document.getElementById('topNav');
window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
    if (currentScroll > lastScroll && currentScroll > 60) {
        topNav.classList.add('hidden');
    } else {
        topNav.classList.remove('hidden');
    }
    lastScroll = currentScroll <= 0 ? 0 : currentScroll;
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>