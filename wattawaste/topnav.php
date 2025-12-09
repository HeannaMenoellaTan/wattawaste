<?php
// ==== SENSOR STATUS FUNCTION ====
function getSensorStatus($conn, $table, $valueField, $timeField) {
    $q = $conn->query("SELECT $valueField, $timeField FROM $table ORDER BY $timeField DESC LIMIT 1");
    if (!$q || $q->num_rows == 0) return ['status'=>'faulty','time'=>'No data'];

    $row = $q->fetch_assoc();
    $val = $row[$valueField];
    $time = strtotime($row[$timeField]);
    $diff = time() - $time;

    if ($val === null || $val < 0) return ['status'=>'faulty','time'=>$diff];
    if ($diff <= 10) return ['status'=>'online','time'=>$diff];
    if ($diff <= 30) return ['status'=>'delayed','time'=>$diff];
    return ['status'=>'offline','time'=>$diff];
}

// Fetch statuses
$tempStatus = getSensorStatus($conn,'temperatures','Temp_Ave','Created_At');
$humStatus  = getSensorStatus($conn,'humidity','Humid_Lvl','Created_At');
$gasStatus  = getSensorStatus($conn,'gas','Gas_Lvl','Created_At');
$phStatus   = getSensorStatus($conn,'ph','pH_Value','Created_At');

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
            ⚠️ 0 Faulty Sensors
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
    transition: transform 0.3s ease; /* for smooth hide/show */
}
.top-nav.hidden {
    transform: translateY(-120%); /* moves it up out of view */
}
.page-title { color:#2E7D32; font-weight:700; font-size:1.25rem; }
.top-right .datetime { color:#2E7D32; opacity:.85; font-weight:600; }
.dropdown-menu { min-width:250px;  z-index: 4000;}
.dropdown-item { display:flex; justify-content:space-between; align-items:center; }
/* Cards should not block dropdown */
.card, .dashboard-container {
    position: relative;   /* keep for shadows */
    z-index: 1;           /* low enough so dropdown is on top */
}
.dot { width:10px; height:10px; border-radius:50%; margin-left:8px; }
.dot.online  { background:#2ecc71; }
.dot.delayed { background:#f1c40f; }
.dot.offline { background:#e74c3c; }
.dot.faulty  { background:#7f8c8d; }
.faulty { font-size:14px; font-weight:600; color:#E65100; }
.main {
    padding-top: 0; /* no extra padding needed if sticky inside main */
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

// Optional: live refresh of sensor dropdown
async function refreshSensors(){
    try{
        const res = await fetch('sensor_status.php'); // JSON array of sensors
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
let lastScroll = 0;
const topNav = document.querySelector('.top-nav');

window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
    if (currentScroll > lastScroll) {
        // scrolling down
        topNav.classList.add('hidden');
    } else {
        // scrolling up
        topNav.classList.remove('hidden');
    }
    lastScroll = currentScroll <= 0 ? 0 : currentScroll; // avoid negative scroll
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
