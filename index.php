<?php
/**
 * index.php — Dashboard
 * FIXES:
 * 1. Auth timing bug — mixer permissions wait for Firebase auth to resolve
 * 2. Mixer count moved to Firebase (not localStorage) — cross-device consistent
 * 3. Mixer locked until sensor data is stabilized (temp, humidity, gas, ph all valid)
 * 4. Stage/readiness driven entirely by predict_readiness.php
 */
require_once 'firebase_config.php';
$database = getDatabase();

$temp          = floatval($database->getReference("sensors/temperature/latest")->getValue() ?? 0);
$humidity      = floatval($database->getReference("sensors/humidity/latest")->getValue() ?? 0);
$gas           = floatval($database->getReference("sensors/gas/latest")->getValue() ?? 0);
$ph            = floatval($database->getReference("sensors/ph/latest")->getValue() ?? 0);
$capacity      = 100;
$currentWeight = floatval($database->getReference("sensors/weight/latest")->getValue() ?? 0);
$initialWeight = floatval($database->getReference("sensors/weight/initial")->getValue() ?? $currentWeight);

include 'sideabr.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Leafcycle Bin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://kit.fontawesome.com/a2e0e6ad65.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script type="module">
import { initializeApp }                        from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
import { getAuth, onAuthStateChanged }           from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
import { getDatabase, ref, set, get, onValue }  from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-database.js';

const firebaseConfig = {
    apiKey:            "AIzaSyAu9hOwjiuAl9PCh50HefMGZU9XDosu68I",
    authDomain:        "wattawaste-d3503.firebaseapp.com",
    databaseURL:       "https://wattawaste-d3503-default-rtdb.asia-southeast1.firebasedatabase.app",
    projectId:         "wattawaste-d3503",
    storageBucket:     "wattawaste-d3503.firebasestorage.app",
    messagingSenderId: "842761118644",
    appId:             "1:842761118644:web:ddef65fd892486f67f88e1",
    measurementId:     "G-33Z8K3NBY1"
};

const app      = initializeApp(firebaseConfig);
const auth     = getAuth(app);
const database = getDatabase(app);

// ── Auth redirect guard ────────────────────────────────────────────────────
const redirectTime    = localStorage.getItem('fbRedirectTime');
const redirectAge     = redirectTime ? Date.now() - parseInt(redirectTime) : Infinity;
const comingFromLogin = redirectAge < 30000;
const WAIT_TIME       = comingFromLogin ? 10000 : 4000;

let authResolved = false;
const authTimeout = setTimeout(() => {
    if (!authResolved) {
        localStorage.removeItem('fbRedirectTime');
        window.location.href = 'login.html';
    }
}, WAIT_TIME);

// ── Sensor stability check ─────────────────────────────────────────────────
// Returns true only when all live sensor readings are within valid/active ranges.
// This prevents the user from running the mixer on stale or default boot values.
function sensorsAreStable() {
    const temp = parseFloat(document.getElementById('tempValue')?.textContent) || 0;
    const hum  = parseFloat(document.getElementById('humValue')?.textContent)  || 0;
    const gas  = parseFloat(document.getElementById('gasValue')?.textContent)  || 0;
    const ph   = parseFloat(document.getElementById('phValue')?.textContent)   || 0;

    const tempOk = temp > 5   && temp < 80;   // plausible compost temp range
    const humOk  = hum  > 5   && hum  < 100;  // sensor connected & reading
    const gasOk  = gas  >= 0;                  // gas can be 0 but must be present
    const phOk   = ph   >= 4   && ph   <= 9;   // constrained by firmware already

    return tempOk && humOk && gasOk && phOk;
}

onAuthStateChanged(auth, async (user) => {
    authResolved = true;
    clearTimeout(authTimeout);

    if (!user) {
        localStorage.removeItem('fbRedirectTime');
        window.location.href = 'login.html';
        return;
    }

    localStorage.removeItem('fbRedirectTime');
    sessionStorage.setItem('userEmail', user.email || user.phoneNumber || '');
    sessionStorage.setItem('userId', user.uid);

    // ── Check isVerified ──────────────────────────────────────────────────
    const userRef = ref(database, `users/${user.uid}`);
    onValue(userRef, (snapshot) => {
        const userData   = snapshot.val();
        const isVerified = userData && userData.isVerified === true;

        // Store as explicit string 'true'/'false' — never a boolean
        sessionStorage.setItem('isAuthorizedForMixer', isVerified ? 'true' : 'false');
        sessionStorage.setItem('authResolved', 'true'); // <-- flag that auth is done

        const mc = document.getElementById('mixerControls');
        const um = document.getElementById('unauthorizedMessage');
        if (!isVerified) {
            if (mc) mc.style.display = 'none';
            if (um) {
                um.style.display = 'block';
                um.innerHTML = `<i class="fas fa-lock me-2"></i>Your account is not verified. Please complete the <a href="profile.php" style="color:#E65100;text-decoration:underline;font-weight:700;">profile verification</a> to access mixer controls.`;
            }
        } else {
            if (mc) mc.style.display = 'block';
            if (um) um.style.display = 'none';
        }
    });

    // ── Start everything after auth ───────────────────────────────────────
    window.setupFirebaseListeners();
    window.initializeMixerControls(user.uid);
});

window.firebaseAuth     = auth;
window.firebaseDatabase = database;
window.firebaseRef      = ref;
window.firebaseSet      = set;
window.firebaseGet      = get;
window.firebaseOnValue  = onValue;

// =============================================================================
// MIXER CONTROLS
// FIX 1: Auth timing — we wait for sessionStorage 'authResolved' before allowing
//         any toggle action, preventing false "Access Denied" on fast clicks.
// FIX 2: Daily count stored in Firebase under users/{uid}/mixerUsage/{date}
//         so it is consistent across all devices and browsers.
// FIX 3: Mixer is locked (disabled state) until sensorsAreStable() returns true.
//         A status message explains why it is locked.
// =============================================================================
window.initializeMixerControls = function(uid) {
    const toggle     = document.getElementById('mixerToggle');
    const knob       = document.getElementById('mixerKnob');
    const mixerAlert = document.getElementById('mixerAlert');
    const mixerText  = document.getElementById('mixerText');
    const mixerLimit = document.getElementById('mixerLimitInfo');
    if (!toggle) return;

    const motorRef       = window.firebaseRef(window.firebaseDatabase, 'controls/motor/command');
    const motorStatusRef = window.firebaseRef(window.firebaseDatabase, 'controls/motor/status');

    // Today's date key e.g. "2026-02-26"
    function todayKey() {
        return new Date().toISOString().slice(0, 10);
    }

    function mixerUsageRef() {
        return window.firebaseRef(window.firebaseDatabase, `users/${uid}/mixerUsage/${todayKey()}`);
    }

    function showMixerAlert(msg, type = 'success') {
        if (!mixerAlert) return;
        mixerAlert.style.display = 'block';
        mixerAlert.className     = 'mixer-alert ' + type;
        mixerAlert.textContent   = msg;
        setTimeout(() => { mixerAlert.style.display = 'none'; }, 4500);
    }

    // ── Apply toggle UI from a boolean ───────────────────────────────────
    function applyMixerUI(isOn, usageCount) {
        if (!toggle || !knob || !mixerText) return;
        if (isOn) {
            knob.style.left         = '36px';
            toggle.style.background = '#4caf50';
            mixerText.textContent   = 'Mixer is ON';
        } else {
            knob.style.left         = '4px';
            toggle.style.background = '#cfd8cf';
            mixerText.textContent   = 'Mixer is OFF';
        }
        const count = typeof usageCount === 'number' ? usageCount : 0;
        if (mixerLimit) {
            mixerLimit.textContent = `Used today: ${count}/2`;
            mixerLimit.style.color = count >= 2 ? '#ef4444' : '#555';
        }
    }

    // ── Lock / unlock toggle visually based on sensor stability ──────────
    function updateSensorLock() {
        const stable    = sensorsAreStable();
        const lockBadge = document.getElementById('sensorLockBadge');
        if (lockBadge) {
            if (!stable) {
                lockBadge.style.display = 'block';
                lockBadge.textContent   = '⚠️ Waiting for sensor readings to stabilize before mixer can be used...';
            } else {
                lockBadge.style.display = 'none';
            }
        }
        // Visually dim the toggle when locked
        toggle.style.opacity      = stable ? '1'        : '0.45';
        toggle.style.cursor       = stable ? 'pointer'  : 'not-allowed';
        toggle.style.pointerEvents = stable ? 'auto'    : 'none';
    }

    // ── Listen to motor status from Firebase (handles ESP32 auto-stop) ───
    window.firebaseOnValue(motorStatusRef, async (snapshot) => {
        const status     = snapshot.val();
        const usageSnap  = await window.firebaseGet(mixerUsageRef());
        const usageCount = usageSnap.val() || 0;
        applyMixerUI(status === 'running', usageCount);
    });

    window.firebaseOnValue(motorRef, async (snapshot) => {
        if (snapshot.val() === false) {
            const usageSnap  = await window.firebaseGet(mixerUsageRef());
            const usageCount = usageSnap.val() || 0;
            applyMixerUI(false, usageCount);
        }
    });

    // ── Initial load: sync UI from Firebase usage count ──────────────────
    (async () => {
        const usageSnap  = await window.firebaseGet(mixerUsageRef());
        const usageCount = usageSnap.val() || 0;
        const statusSnap = await window.firebaseGet(motorStatusRef);
        applyMixerUI(statusSnap.val() === 'running', usageCount);
    })();

    // ── Poll sensor lock every 3 seconds ─────────────────────────────────
    updateSensorLock();
    setInterval(updateSensorLock, 3000);

    // ── Toggle click handler ──────────────────────────────────────────────
    toggle.addEventListener('click', async () => {

        // FIX 1: Block if auth hasn't resolved yet
        if (sessionStorage.getItem('authResolved') !== 'true') {
            showMixerAlert('⏳ Still loading permissions, please wait a moment...', 'warning');
            return;
        }

        // FIX 3: Block if sensors not stable
        if (!sensorsAreStable()) {
            showMixerAlert('⚠️ Sensors are still stabilizing. Please wait before using the mixer.', 'warning');
            return;
        }

        if (sessionStorage.getItem('isAuthorizedForMixer') !== 'true') {
            showMixerAlert('🔒 Access Denied: Please verify your profile to control the mixer.', 'error');
            return;
        }

        // FIX 2: Read usage count from Firebase, not localStorage
        const usageSnap  = await window.firebaseGet(mixerUsageRef());
        const usageCount = usageSnap.val() || 0;

        const statusSnap  = await window.firebaseGet(motorStatusRef);
        const motorIsOn   = statusSnap.val() === 'running';

        if (!motorIsOn) {
            // ── Turn ON ───────────────────────────────────────────────────
            if (usageCount >= 2) {
                showMixerAlert('⚠️ You can only turn the mixer ON twice per day.', 'warning');
                applyMixerUI(false, usageCount);
                return;
            }
            try {
                await window.firebaseSet(motorRef, true);
                const newCount = usageCount + 1;
                await window.firebaseSet(mixerUsageRef(), newCount);
                applyMixerUI(true, newCount);
                showMixerAlert(`✅ Mixer turned ON (${newCount}/2 uses today)`, 'success');
            } catch (e) {
                showMixerAlert('❌ Failed to turn on mixer: ' + e.message, 'error');
            }
        } else {
            // ── Turn OFF ──────────────────────────────────────────────────
            try {
                await window.firebaseSet(motorRef, false);
                applyMixerUI(false, usageCount);
                showMixerAlert('🛑 Mixer turned OFF', 'error');
            } catch (e) {
                showMixerAlert('❌ Failed to turn off mixer: ' + e.message, 'error');
            }
        }
    });
};
</script>

<style>
:root{
    --brand:#4CAF50; --brand-dark:#2E7D32; --ink:#333;
    --panel:#fff; --muted:#555; --bg:#F9FAFB;
    --ok:#22c55e; --warn:#f59e0b; --crit:#ef4444;
}
body { background:var(--bg); font-family:Poppins,system-ui,Segoe UI,Arial; color:var(--ink); min-height:100vh; }
.card { border:none; border-radius:16px; background:var(--panel); box-shadow:0 6px 16px rgba(2,6,23,.06); transition:transform .2s,box-shadow .2s; }
.card:hover { transform:translateY(-3px); box-shadow:0 12px 26px rgba(2,6,23,.12); }

.chart-container { position:relative; width:min(320px,100%); aspect-ratio:1/1; margin:auto; }
.chart-label { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); text-align:center; }
.chart-label #readinessLabel  { font-weight:800; font-size:clamp(20px,6vw,28px); line-height:1; }
.chart-label #readinessStatus { color:var(--muted); font-size:.9rem; }

.stage-card-inner {
    background:linear-gradient(135deg,#4CAF5018,#4CAF5008);
    border-left:4px solid #4CAF50;
    border-radius:12px; padding:14px 16px;
}
.stage-name  { font-size:16px; font-weight:700; color:#4CAF50; margin-bottom:6px; }
.stage-debug { font-size:10px; color:var(--muted); margin-top:6px; opacity:.8; }

.bar-container { width:100%; background:#E9ECEF; height:12px; border-radius:10px; overflow:hidden; position:relative; }
.bar { height:100%; width:0%; border-radius:10px; transition:width .9s ease; }
.bar::after { content:""; position:absolute; inset:0; transform:translateX(-100%); background:linear-gradient(90deg,transparent,rgba(255,255,255,.55),transparent); animation:shimmer 1.8s infinite; }
@keyframes shimmer { 50%{transform:translateX(0)} 100%{transform:translateX(100%)} }

.small-muted { color:var(--muted); opacity:.9; font-size:.92rem; }
.dashboard-container { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; padding:16px; }
.sensor-card { position:relative; border-radius:18px; padding:20px; text-align:center; color:#0f172a; background:#ffffff; box-shadow:0 6px 16px rgba(2,6,23,.06); }
.sensor-card .icon-wrap { width:52px; height:52px; border-radius:50%; display:grid; place-items:center; margin:0 auto 10px; background:#E8F5E9; box-shadow:0 0 0 0 rgba(76,175,80,.5); animation:ringPulse 2.6s infinite; }
@keyframes ringPulse { 0%{box-shadow:0 0 0 0 rgba(76,175,80,.42)} 70%{box-shadow:0 0 0 14px rgba(76,175,80,0)} 100%{box-shadow:0 0 0 0 rgba(76,175,80,0)} }
.sensor-card .icon { font-size:24px; color:var(--brand-dark); }
.sensor-card h3    { margin:.25rem 0 .25rem; font-size:clamp(.9rem,2.5vw,1rem); }
.sensor-card .value { font-size:clamp(20px,5vw,26px); font-weight:800; margin-bottom:12px; }

.thermo-meter,.droplet-meter,.gas-meter,.ph-meter { width:100%; height:14px; border-radius:50px; background:#f1f5f9; overflow:hidden; }
.thermo-fill  { height:100%; width:0%; background:linear-gradient(90deg,#ff7b00,#ff0000); transition:width 1s ease; }
.droplet-fill { height:100%; width:0%; background:linear-gradient(90deg,#00b4d8,#48cae4); transition:width 1s ease; }
.gas-fill     { height:100%; width:0%; background:linear-gradient(90deg,#ffba08,#f48c06); transition:width 1s ease; }
.ph-fill      { height:100%; width:0%; background:linear-gradient(90deg,#ff0000,#ffae00,#00ff00,#0088ff,#4b0082); transition:width 1s ease; }

.ok-glow   { box-shadow:0 0 0 0 rgba(34,197,94,.45),0 14px 34px rgba(34,197,94,.12); }
.warn-glow { box-shadow:0 0 0 0 rgba(245,158,11,.45),0 14px 34px rgba(245,158,11,.12); }
.crit-glow { box-shadow:0 0 0 0 rgba(239,68,68,.55),0 16px 36px rgba(239,68,68,.18); animation:shake .4s ease; }
@keyframes shake { 20%{transform:translateX(-2px)} 40%{transform:translateX(2px)} 60%{transform:translateX(-1px)} 80%{transform:translateX(1px)} }

.mixer-alert { position:fixed; top:70px; left:50%; transform:translateX(-50%); z-index:1500; display:none; padding:10px 16px; border-radius:10px; font-weight:600; max-width:calc(100vw - 32px); text-align:center; }
.mixer-alert.success { background:#E9F8EC; color:#256333; border-left:6px solid var(--brand-dark); }
.mixer-alert.warning { background:#FFF8E1; color:#7A5A00; border-left:6px solid #fbbf24; }
.mixer-alert.error   { background:#FFE5E5; color:#7F1D1D; border-left:6px solid #ef4444; }

.unauthorized-message { background:#FFF8E1; border:2px solid #fbbf24; border-radius:12px; padding:14px 18px; color:#7A5A00; font-weight:600; text-align:center; display:none; margin:0 16px; }

/* Sensor lock badge */
#sensorLockBadge {
    display:none;
    background:#FFF3E0; border:1.5px solid #fb923c; border-radius:10px;
    color:#92400e; font-size:.85rem; font-weight:600;
    padding:10px 14px; margin:0 0 12px; text-align:center;
}

.main { margin-left:260px; padding:20px; min-height:100vh; }
.top-section { display:grid; grid-template-columns:2fr 3fr; gap:20px; align-items:start; padding:0 0 20px; }
.top-section .right-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.top-section .right-grid .stage-card { grid-column:1/-1; }
.history-card select,.history-card .btn { font-size:.85rem; }

@media(max-width:768px){
    .main { margin-left:0; padding:70px 12px 20px; }
    .top-section { grid-template-columns:1fr; }
    .top-section .right-grid { grid-template-columns:1fr 1fr; }
    .dashboard-container { grid-template-columns:1fr 1fr; gap:12px; padding:0 0 12px; }
    .sensor-card { padding:14px 10px; }
    .chart-container { width:min(260px,85vw); }
}
@media(max-width:400px){
    .dashboard-container { grid-template-columns:1fr; }
    .top-section .right-grid { grid-template-columns:1fr; }
}
</style>

<div class="main">
<?php include 'topnav.php'; ?>

<div class="top-section">

    <!-- LEFT: Chart.js Donut -->
    <div class="card p-3 chart-container" id="chartCard">
        <canvas id="progressChart" aria-label="Compost Readiness"></canvas>
        <div class="chart-label">
            <div id="readinessLabel">--%</div>
            <div id="readinessStatus" class="small-muted">Loading...</div>
        </div>
    </div>

    <!-- RIGHT GRID -->
    <div class="right-grid">

        <!-- Weight card -->
        <div class="card p-3" id="weightCard">
            <small class="small-muted">Current Waste Weight</small>
            <h4 class="mb-1" id="currentWeightDisplay"><?php echo number_format($currentWeight,4); ?> kg</h4>
            <div class="small-muted mb-2">Capacity: <span id="capacityDisplay"><?php echo $capacity; ?></span> kg</div>
            <div class="bar-container">
                <div id="weightBar" class="bar" style="background:linear-gradient(90deg,#81C784,#4CAF50)"></div>
            </div>
        </div>

        <!-- Fertilizer card -->
        <div class="card p-3" id="fertCard">
            <small class="small-muted fw-bold">🌾 Fertilizer Output</small>
            <div class="mt-2">
                <div class="d-flex justify-content-between flex-wrap gap-1">
                    <span>Predicted:</span>
                    <span id="predictedOutput" class="fw-bold text-success">0.0000 kg</span>
                </div>
                <div class="d-flex justify-content-between flex-wrap gap-1">
                    <span>Actual:</span>
                    <span id="actualOutput" class="fw-bold text-primary">Not ready yet</span>
                </div>
            </div>
            <div class="bar-container mt-3">
                <div id="fertBar" class="bar" style="background:linear-gradient(90deg,#a7f3d0,#10b981)"></div>
            </div>
        </div>

        <!-- Stage card -->
        <div class="card p-3 stage-card">
            <small class="small-muted fw-bold">🧬 Compost Stage</small>
            <div class="stage-card-inner mt-2" id="stageCardInner">
                <div class="stage-name" id="compostStage">Loading...</div>
                <div id="compostStageDesc" class="small" style="line-height:1.6;">Fetching stage from server...</div>
                <div class="stage-debug" id="stageDebug"></div>
            </div>
        </div>

    </div>
</div>

<!-- Sensor cards -->
<div class="dashboard-container">
    <div class="sensor-card" id="tempCard">
        <div class="icon-wrap"><i class="icon fas fa-thermometer-half"></i></div>
        <h3>Temperature</h3>
        <div class="value" id="tempValue">-- °C</div>
        <div class="thermo-meter"><div class="thermo-fill" id="tempFill"></div></div>
    </div>
    <div class="sensor-card" id="gasCard">
        <div class="icon-wrap"><i class="icon fas fa-wind"></i></div>
        <h3>Gas Level</h3>
        <div class="value" id="gasValue">-- ppm</div>
        <div class="gas-meter"><div class="gas-fill" id="gasFill"></div></div>
    </div>
    <div class="sensor-card" id="humCard">
        <div class="icon-wrap"><i class="icon fas fa-tint"></i></div>
        <h3>Humidity</h3>
        <div class="value" id="humValue">-- %</div>
        <div class="droplet-meter"><div class="droplet-fill" id="humFill"></div></div>
    </div>
    <div class="sensor-card" id="phCard">
        <div class="icon-wrap"><i class="icon fas fa-vial"></i></div>
        <h3>pH Level</h3>
        <div class="value" id="phValue">--</div>
        <div class="ph-meter"><div class="ph-fill" id="phFill"></div></div>
    </div>
</div>

<!-- Sensor History Chart -->
<div class="card p-3 p-md-4 mt-2 mx-0 history-card" id="historyChartCard">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h5 class="mb-0 fw-bold">📈 Sensor History</h5>
        <div class="d-flex gap-2 flex-wrap w-100 w-md-auto">
            <select id="chartSensorSelect" class="form-select form-select-sm" style="width:auto;flex:1;min-width:130px">
                <option value="temperature">🌡️ Temperature</option>
                <option value="humidity">💧 Humidity</option>
                <option value="gas">💨 Gas Level</option>
                <option value="ph">⚗️ pH</option>
                <option value="weight">⚖️ Weight</option>
            </select>
            <select id="chartRangeSelect" class="form-select form-select-sm" style="width:auto;flex:1;min-width:120px">
                <option value="1h">Last 1 hour</option>
                <option value="6h">Last 6 hours</option>
                <option value="24h" selected>Last 24 hours</option>
                <option value="7d">Last 7 days</option>
                <option value="30d">Last 30 days</option>
            </select>
            <button id="refreshHistoryBtn" class="btn btn-sm btn-outline-success">🔄 Refresh</button>
        </div>
    </div>
    <div style="position:relative;height:clamp(200px,40vw,280px);">
        <canvas id="historyChart"></canvas>
    </div>
    <div class="row g-3 mt-2 text-center" id="historyStats">
        <div class="col-4"><div class="small-muted">Average</div><div class="fw-bold fs-6" id="statAvg">--</div></div>
        <div class="col-4"><div class="small-muted">Min</div><div class="fw-bold fs-6 text-info" id="statMin">--</div></div>
        <div class="col-4"><div class="small-muted">Max</div><div class="fw-bold fs-6 text-danger" id="statMax">--</div></div>
    </div>
    <div id="historyChartMsg" class="text-center text-muted small mt-2" style="display:none">
        No aggregated data yet — run aggregate.php to populate history.
    </div>
</div>

<!-- Mixer -->
<div class="text-center mt-5 mb-4 px-3">
    <div id="mixerAlert" class="mixer-alert"></div>
    <div id="unauthorizedMessage" class="unauthorized-message"></div>
    <div id="mixerControls">

        <!-- Sensor lock badge — shown when readings aren't stable yet -->
        <div id="sensorLockBadge"></div>

        <div id="mixerToggle" style="width:70px;height:36px;background:#cfd8cf;border-radius:20px;position:relative;cursor:pointer;margin:auto;touch-action:manipulation;">
            <div id="mixerKnob" style="width:30px;height:30px;background:#fff;border-radius:50%;position:absolute;top:3px;left:4px;transition:left .25s;pointer-events:none;"></div>
        </div>
        <div id="mixerText" class="fw-medium mt-2">Mixer is OFF</div>

        <!-- FIX: Now shows live Firebase usage count, not localStorage -->
        <div id="mixerLimitInfo" class="small-muted">Used today: 0/2</div>
        <div class="small-muted">You can turn mixer ON twice per day</div>
    </div>
</div>

</div><!-- end .main -->

<script>
function clamp(n,a,b){ return Math.max(a,Math.min(b,n)); }

const STAGE_COLORS = {
    'no_compost'  : '#94a3b8',
    'initial'     : '#fb923c',
    'mesophilic'  : '#f59e0b',
    'thermophilic': '#84cc16',
    'late_thermo' : '#4CAF50',
    'maturation'  : '#22c55e',
};

const STAGE_DESCS = {
    'no_compost'  : 'No compost detected. Add organic waste to begin composting.',
    'initial'     : 'Fresh batch just added. Microorganisms beginning to establish. Weight loss and heat will build over the next few days.',
    'mesophilic'  : 'Early-stage microbes colonizing organic material. Temperature and gas building toward thermophilic phase.',
    'thermophilic': 'Peak microbial activity! Temperature elevated, pathogens being destroyed. Active decomposition underway.',
    'late_thermo' : 'Active decomposition winding down. Microbial activity has processed most material. Final curing phase approaching.',
    'maturation'  : 'Compost is cooling and stabilizing into nutrient-rich humus. <strong>Nearly ready to harvest!</strong>',
};

// ── Chart.js donut ─────────────────────────────────────────────────────────
const ctx = document.getElementById('progressChart').getContext('2d');
function makeGradient(c){ const g=c.createLinearGradient(0,0,300,0); g.addColorStop(0,'#a5d6a7'); g.addColorStop(1,'#388e3c'); return g; }
const chart = new Chart(ctx, {
    type: 'doughnut',
    data: { labels:['Ready','Remaining'], datasets:[{data:[0,100], backgroundColor:[makeGradient(ctx),'#e5e7eb'], borderWidth:0}] },
    options: { rotation:-90, cutout:'70%', plugins:{legend:{display:false}}, animation:{duration:700} }
});

function updateDonut(readiness) {
    readiness = clamp(parseFloat(readiness)||0, 0, 100);
    chart.data.datasets[0].data = [readiness, 100 - readiness];
    chart.update();
    const label  = document.getElementById('readinessLabel');
    const status = document.getElementById('readinessStatus');
    const card   = document.getElementById('chartCard');
    if (label)  label.textContent = readiness.toFixed(1) + '%';
    if (status) {
        if      (readiness >= 90) status.textContent = '🌿 Compost ready!';
        else if (readiness >= 65) status.textContent = '🌱 Almost there';
        else if (readiness >= 35) status.textContent = '🔥 Heating up';
        else if (readiness >= 12) status.textContent = '🌿 Building up';
        else if (readiness >  0 ) status.textContent = '🧤 Just started';
        else                      status.textContent = '📦 Awaiting compost';
    }
    if (card) {
        card.classList.remove('ok-glow','warn-glow','crit-glow');
        if      (readiness >= 80) card.classList.add('ok-glow');
        else if (readiness >= 35) card.classList.add('warn-glow');
    }
}

function updateStageCard(data) {
    const stage     = data.stage || 'no_compost';
    const stageName = data.stage_name || 'Unknown';
    const color     = STAGE_COLORS[stage] || '#94a3b8';
    const desc      = STAGE_DESCS[stage]  || 'Monitoring...';

    const inner   = document.getElementById('stageCardInner');
    const nameEl  = document.getElementById('compostStage');
    const descEl  = document.getElementById('compostStageDesc');
    const debugEl = document.getElementById('stageDebug');

    if (nameEl)  { nameEl.textContent = stageName; nameEl.style.color = color; }
    if (descEl)  descEl.innerHTML = desc;
    if (inner)   {
        inner.style.borderLeftColor = color;
        inner.style.background = `linear-gradient(135deg,${color}18,${color}08)`;
    }
    if (debugEl && data.analytics) {
        const h  = data.analytics.elapsed_hours || 0;
        const c  = data.ml ? data.ml.stage_ceiling : '?';
        const wl = data.weight_tracking ? data.weight_tracking.credited_weight_loss : '?';
        debugEl.textContent = `${h.toFixed(1)}h elapsed · credited loss: ${wl}% · ceiling: ${c}%`;
    }
}

function updateChart() {
    fetch('predict_readiness.php', {cache:'no-store'})
        .then(r => r.json())
        .then(data => {
            updateDonut(data.readiness);
            updateStageCard(data);

            const fo      = data.fertilizer_output || {};
            const pred    = parseFloat(fo.predicted_output_kg) || 0;
            const act     = parseFloat(fo.actual_output_kg)    || 0;
            const isReady = fo.is_ready || false;

            const predEl = document.getElementById('predictedOutput');
            if (predEl) predEl.textContent = pred.toFixed(4) + ' kg';

            const actEl = document.getElementById('actualOutput');
            if (actEl) {
                if (data.status === 'no_compost') {
                    actEl.textContent = 'Waiting for compost';
                    actEl.className   = 'fw-bold text-secondary';
                } else if (isReady) {
                    actEl.textContent = act.toFixed(4) + ' kg';
                    actEl.className   = 'fw-bold text-success';
                } else {
                    actEl.textContent = 'Not ready yet';
                    actEl.className   = 'fw-bold text-primary';
                }
            }

            const fertBar = document.getElementById('fertBar');
            if (fertBar) fertBar.style.width = clamp(parseFloat(fo.fertilizer_percentage)||0, 0, 100) + '%';
        })
        .catch(e => console.error('Chart fetch error:', e));
}

updateChart();
setInterval(updateChart, 10000);

// ── Firebase live sensor listeners ────────────────────────────────────────
window.setupFirebaseListeners = function() {
    const db = window.firebaseDatabase;

    function statusGlow(card,value,warnAt,critAt){
        card.classList.remove('ok-glow','warn-glow','crit-glow');
        if(value>=critAt)      card.classList.add('crit-glow');
        else if(value>=warnAt) card.classList.add('warn-glow');
        else                   card.classList.add('ok-glow');
    }

    window.firebaseOnValue(window.firebaseRef(db,'sensors/temperature/latest'),(s)=>{
        const v=parseFloat(s.val())||0;
        document.getElementById('tempValue').textContent=v.toFixed(1)+' °C';
        document.getElementById('tempFill').style.width=clamp(v,0,100)+'%';
        statusGlow(document.getElementById('tempCard'),v,60,65);
    });
    window.firebaseOnValue(window.firebaseRef(db,'sensors/humidity/latest'),(s)=>{
        const v=parseFloat(s.val())||0;
        document.getElementById('humValue').textContent=v.toFixed(1)+' %';
        document.getElementById('humFill').style.width=clamp(v,0,100)+'%';
        statusGlow(document.getElementById('humCard'),v,80,90);
    });
    window.firebaseOnValue(window.firebaseRef(db,'sensors/gas/latest'),(s)=>{
        const v=parseFloat(s.val())||0;
        document.getElementById('gasValue').textContent=v.toFixed(2);
        document.getElementById('gasFill').style.width=clamp(v/10,0,100)+'%';
        statusGlow(document.getElementById('gasCard'),v,600,800);
    });
    window.firebaseOnValue(window.firebaseRef(db,'sensors/ph/latest'),(s)=>{
        const v=parseFloat(s.val())||0;
        document.getElementById('phValue').textContent=v.toFixed(1);
        document.getElementById('phFill').style.width=clamp((v/14)*100,0,100)+'%';
        const c=document.getElementById('phCard');
        c.classList.remove('ok-glow','warn-glow','crit-glow');
        if(v<6.5||v>8.0) c.classList.add('warn-glow'); else c.classList.add('ok-glow');
    });
    window.firebaseOnValue(window.firebaseRef(db,'sensors/weight/latest'),(s)=>{
        const v=parseFloat(s.val())||0;
        document.getElementById('currentWeightDisplay').textContent=v.toFixed(4)+' kg';
        const bar=document.getElementById('weightBar');
        if(bar) bar.style.width=clamp((v/100)*100,0,100)+'%';
    });

    document.getElementById('capacityDisplay').textContent='100';
};

// ── Sensor status ─────────────────────────────────────────────────────────
function loadSensorStatus(){
    fetch("sensor_status.php").then(r=>r.json()).then(data=>{
        const container=document.getElementById("sensor-status-container");
        if(!container) return;
        container.innerHTML="";
        let faultyCount=0;
        data.forEach(sensor=>{
            if(sensor.class==="faulty") faultyCount++;
            let icon="📡";
            if(sensor.name==="Temperature") icon="🌡️";
            if(sensor.name==="Humidity")    icon="💧";
            if(sensor.name==="Gas")         icon="🔥";
            if(sensor.name==="pH")          icon="⚗️";
            container.innerHTML+=`<div class="sensor-top-badge ${sensor.class}"><span class="icon">${icon}</span><span>${sensor.name}</span><span class="dot ${sensor.class}"></span><span>${sensor.status}</span><span class="sensor-time">(${sensor.lastUpdate})</span></div>`;
        });
        const ft=document.getElementById("faultyText");
        if(ft) ft.textContent=`${faultyCount} Faulty Sensor${faultyCount!==1?'s':''}`;
    }).catch(()=>{});
}
setInterval(loadSensorStatus,2000);
loadSensorStatus();

function updateDateTime(){
    const now=new Date();
    const el=document.getElementById("dateTime");
    if(el) el.innerHTML=now.toLocaleString("en-US",{month:"short",day:"numeric",year:"numeric",hour:"2-digit",minute:"2-digit"});
}
setInterval(updateDateTime,1000);
updateDateTime();

// ── History Chart ─────────────────────────────────────────────────────────
(function(){
    let historyChartInstance=null;
    function loadHistoryChart(){
        const sensor=document.getElementById('chartSensorSelect').value;
        const range=document.getElementById('chartRangeSelect').value;
        const btn=document.getElementById('refreshHistoryBtn');
        const msg=document.getElementById('historyChartMsg');
        btn.disabled=true; btn.textContent='⏳ Loading...';
        if(msg) msg.style.display='none';
        fetch(`chart_data.php?sensor=${sensor}&range=${range}`,{cache:'no-store'})
            .then(r=>r.json())
            .then(data=>{
                btn.disabled=false; btn.textContent='🔄 Refresh';
                if(data.error){console.error('Chart API error:',data.error);return;}
                const labels=data.labels||[];
                const avg=data.datasets?.average||[];
                const minArr=data.datasets?.min||[];
                const maxArr=data.datasets?.max||[];
                const meta=data.meta||{};
                const color=meta.color||'#4CAF50';
                const unit=meta.unit||'';
                if(labels.length===0&&msg) msg.style.display='block';
                if(avg.length){
                    const totalAvg=avg.reduce((a,b)=>a+b,0)/avg.length;
                    document.getElementById('statAvg').textContent=totalAvg.toFixed(2)+' '+unit;
                    document.getElementById('statMin').textContent=Math.min(...minArr).toFixed(2)+' '+unit;
                    document.getElementById('statMax').textContent=Math.max(...maxArr).toFixed(2)+' '+unit;
                } else { ['statAvg','statMin','statMax'].forEach(id=>document.getElementById(id).textContent='--'); }
                if(historyChartInstance){historyChartInstance.destroy();historyChartInstance=null;}
                const hCtx=document.getElementById('historyChart').getContext('2d');
                historyChartInstance=new Chart(hCtx,{
                    type:'line',
                    data:{labels,datasets:[
                        {label:`Avg ${meta.label||sensor} (${unit})`,data:avg,borderColor:color,backgroundColor:color+'22',borderWidth:2,fill:true,tension:0.4,pointRadius:labels.length>50?0:3},
                        {label:'Min',data:minArr,borderColor:color+'88',borderDash:[4,4],borderWidth:1,fill:false,pointRadius:0,tension:0.4},
                        {label:'Max',data:maxArr,borderColor:color+'88',borderDash:[4,4],borderWidth:1,fill:false,pointRadius:0,tension:0.4},
                    ]},
                    options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},
                        plugins:{legend:{display:true,position:'bottom'},tooltip:{callbacks:{label:ctx=>` ${ctx.dataset.label}: ${ctx.parsed.y} ${unit}`}}},
                        scales:{x:{ticks:{maxTicksLimit:8,maxRotation:0},grid:{color:'rgba(0,0,0,0.05)'}},y:{ticks:{callback:v=>v+' '+unit},grid:{color:'rgba(0,0,0,0.05)'}}}}
                });
            })
            .catch(err=>{btn.disabled=false;btn.textContent='🔄 Refresh';console.error(err);});
    }
    document.getElementById('chartSensorSelect').addEventListener('change',loadHistoryChart);
    document.getElementById('chartRangeSelect').addEventListener('change',loadHistoryChart);
    document.getElementById('refreshHistoryBtn').addEventListener('click',loadHistoryChart);
    loadHistoryChart();
    setInterval(loadHistoryChart,5*60*1000);
})();
</script>

<?php include_once 'notif_bell.php'; ?>
</body>
</html>