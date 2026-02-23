<?php
require_once 'firebase_config.php';
$database = getDatabase();
$currentTemp = $database->getReference("sensors/temperature/latest")->getValue() ?? 0;
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

<script type="module">
import { initializeApp }        from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
import { getAuth, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
import { getDatabase, ref, onValue, query, orderByKey, limitToLast }
    from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-database.js';

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

onAuthStateChanged(auth, (user) => {
    if (!user) { window.location.href = 'login.php'; return; }
    sessionStorage.setItem('userEmail', user.email || user.phoneNumber || '');
    sessionStorage.setItem('userId',    user.uid);
    window.initializeLiveTemp();
});

window.firebaseDatabase    = database;
window.firebaseRef         = ref;
window.firebaseOnValue     = onValue;
window.firebaseQuery       = query;
window.firebaseOrderByKey  = orderByKey;
window.firebaseLimitToLast = limitToLast;
</script>

<style>
:root {
    --brand: #4CAF50; --brand-dark: #2E7D32;
    --ink: #333; --panel: #fff; --muted: #555; --bg: #F9FAFB;
    --ok: #22c55e; --warn: #f59e0b; --crit: #ef4444;
}
body { background: var(--bg); font-family: Poppins, system-ui, Segoe UI, Arial; color: var(--ink); min-height: 100vh; }
.card { border: none; border-radius: 16px; background: var(--panel); box-shadow: 0 6px 16px rgba(2,6,23,.06); transition: transform .2s, box-shadow .2s; }
.card:hover { transform: translateY(-3px); box-shadow: 0 12px 26px rgba(2,6,23,.12); }
.page-title { font-size: 28px; font-weight: 700; color: var(--ink); margin-bottom: 24px; }

.temp-hero {
    background: linear-gradient(135deg, #ff7b00 0%, #ff0000 100%);
    border-radius: 20px; padding: 40px; color: white; text-align: center;
    position: relative; overflow: hidden;
}
.temp-hero::before {
    content: ''; position: absolute; top: -50%; right: -50%;
    width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse 3s ease-in-out infinite;
}
@keyframes pulse { 0%,100%{transform:scale(1);opacity:.5} 50%{transform:scale(1.1);opacity:.8} }
.temp-display-large {
    font-size: 96px; font-weight: 900; line-height: 1;
    text-shadow: 0 4px 12px rgba(0,0,0,0.2); position: relative; z-index: 1;
}
.status-badge-large {
    display: inline-block; padding: 12px 30px; border-radius: 50px;
    font-weight: 700; font-size: 18px; background: rgba(255,255,255,0.9);
    margin-top: 16px; position: relative; z-index: 1;
}
.status-badge-large.ok   { color: var(--ok); }
.status-badge-large.warn { color: var(--warn); }
.status-badge-large.crit { color: var(--crit); }

.stat-card { text-align: center; padding: 20px; }
.stat-value { font-size: 36px; font-weight: 800; color: var(--brand-dark); line-height: 1; }
.stat-label { font-size: 14px; color: var(--muted); margin-top: 8px; }

/* ── FIXED Thermometer ── */
/* The gradient bar is the "background" showing the full temperature scale.
   The white overlay (.thermo-indicator) sits at the TOP and shrinks downward
   as temperature rises, revealing more of the colored bar underneath.
   So: 0°C → overlay covers 100% (bar hidden), 100°C → overlay covers 0% (full color). */
.thermo-visual {
    width: 80px; height: 300px;
    background: linear-gradient(to top, #0088ff 0%, #00ff00 33%, #ffae00 66%, #ff0000 100%);
    border-radius: 40px; position: relative; margin: 0 auto;
    box-shadow: inset 0 0 20px rgba(0,0,0,0.1);
    overflow: hidden;
}
.thermo-indicator {
    position: absolute;
    top: 0; left: 0; right: 0;           /* anchored at the TOP */
    background: rgba(255,255,255,0.88);   /* white mask */
    border-radius: 40px 40px 0 0;
    transition: height 1s cubic-bezier(0.4,0,0.2,1);
}

.history-item {
    display: flex; justify-content: space-between; align-items: center;
    padding: 14px 16px; margin-bottom: 8px;
    background: #f8fafc; border-radius: 12px;
    border-left: 4px solid var(--brand); transition: all 0.2s;
}
.history-item:hover { background: #f1f5f9; transform: translateX(4px); }
.history-value { font-size: 22px; font-weight: 700; color: var(--brand-dark); }
.history-time  { font-size: 13px; color: var(--muted); }
.small-muted   { color: var(--muted); font-size: 14px; }
.alert-custom  { padding: 16px 20px; border-radius: 12px; border-left: 4px solid; font-weight: 500; }
.alert-custom.warning { background: #FFF8E1; color: #7A5A00; border-color: #f59e0b; }

.range-btn { border: 1.5px solid #dee2e6; background: #fff; border-radius: 8px; padding: 5px 14px; font-size: .85rem; cursor: pointer; transition: all .15s; }
.range-btn:hover  { background: #f1f5f9; }
.range-btn.active { background: var(--brand-dark); color: #fff; border-color: var(--brand-dark); }
</style>
</head>
<body>

<?php include 'sideabr.php'; ?>
<div class="main">
<?php include 'topnav.php'; ?>

<div class="container-fluid px-4 py-4">
    <h1 class="page-title">🌡️ Temperature Monitoring</h1>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="temp-hero">
                <div class="temp-display-large" id="tempDisplay">
                    <?php echo number_format((float)$currentTemp, 1); ?>°C
                </div>
                <div class="status-badge-large ok" id="statusBadge">Loading...</div>
                <div class="mt-3 small" style="opacity:.9;position:relative;z-index:1;" id="statusDesc">
                    Fetching temperature data from sensors...
                </div>
                <div class="mt-2 small" style="opacity:.8;position:relative;z-index:1;" id="lastUpdateText">
                    Last updated: --
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card p-4 h-100 d-flex align-items-center justify-content-center">
                <h6 class="text-center mb-3">Visual Indicator</h6>
                <div class="thermo-visual">
                    <!-- White overlay shrinks from top as temp rises -->
                    <div class="thermo-indicator" id="thermoIndicator" style="height:100%;"></div>
                </div>
                <div class="mt-3 text-center small-muted">
                    <div><span style="color:#ff0000;">●</span> 70°C+ Too Hot</div>
                    <div><span style="color:#ff7b00;">●</span> 45–70°C Active</div>
                    <div><span style="color:#ffae00;">●</span> 20–45°C Initial</div>
                    <div><span style="color:#0088ff;">●</span> &lt;20°C Too Cold</div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert-custom warning mb-4" id="warningBanner" style="display:none;">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Warning:</strong>
        <span id="warningText">Temperature is outside the optimal composting range.</span>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card">
                <i class="fas fa-temperature-high text-danger mb-2" style="font-size:32px;"></i>
                <div class="stat-value" id="maxTempStat">--°C</div>
                <div class="stat-label">Maximum (selected range)</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <i class="fas fa-chart-line text-success mb-2" style="font-size:32px;"></i>
                <div class="stat-value" id="avgTempStat">--°C</div>
                <div class="stat-label">Average (selected range)</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <i class="fas fa-temperature-low text-info mb-2" style="font-size:32px;"></i>
                <div class="stat-value" id="minTempStat">--°C</div>
                <div class="stat-label">Minimum (selected range)</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-area text-primary me-2"></i>
                        Temperature Trend
                        <span id="readingsCount" class="small-muted fs-6"></span>
                    </h5>
                    <!-- 30d REMOVED -->
                    <div class="d-flex gap-1 flex-wrap" id="rangeButtons">
                        <button class="range-btn" data-range="1h">1h</button>
                        <button class="range-btn" data-range="6h">6h</button>
                        <button class="range-btn active" data-range="24h">24h</button>
                        <button class="range-btn" data-range="7d">7d</button>
                    </div>
                </div>
                <canvas id="tempChart" style="max-height:350px;"></canvas>
                <div id="chartNoData" class="text-center text-muted small mt-2" style="display:none;">
                    No data yet for this range. Waiting for ESP32 readings...
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card p-4">
                <h5 class="mb-3">
                    <i class="fas fa-history text-info me-2"></i>
                    Recent Live Readings
                    <span class="small-muted fs-6 ms-1">(latest 20 from Firebase)</span>
                </h5>
                <div id="historyContainer" style="max-height:500px;overflow-y:auto;">
                    <p class="text-muted text-center py-4">Loading temperature history...</p>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
let tempChart    = null;
let currentRange = '24h';

function getTemperatureStatus(temp) {
    if (temp >= 45 && temp <= 70)
        return { status: 'Thermophilic (Active)', cls: 'ok',   desc: 'Optimal composting temperature. High microbial activity.' };
    if (temp >= 20 && temp < 45)
        return { status: 'Mesophilic (Initial)',  cls: 'warn', desc: 'Initial composting phase. Microbes breaking down materials.' };
    if (temp < 20)
        return { status: 'Too Cold', cls: 'crit', desc: 'Temperature too low for effective composting.' };
    return     { status: 'Too Hot',  cls: 'crit', desc: 'Temperature exceeds safe composting range.' };
}

/* ── FIXED: white overlay at top shrinks as temp rises ── */
function updateThermoIndicator(temp) {
    // At 0°C  → overlay = 100% (bar fully hidden → appears empty)
    // At 100°C → overlay = 0%  (bar fully visible → appears full)
    const pct      = Math.min(Math.max((temp / 100) * 100, 0), 100);
    const inverted = 100 - pct;
    document.getElementById('thermoIndicator').style.height = inverted + '%';
}

function formatTimestamp(tsMs) {
    const d = new Date(Number(tsMs));
    if (isNaN(d.getTime())) return 'Unknown time';
    return d.toLocaleString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: 'numeric', minute: '2-digit', hour12: true
    });
}

function getRangeCutoffMs(range) {
    const now = Date.now();
    const map = {
        '1h':  1  * 60 * 60 * 1000,
        '6h':  6  * 60 * 60 * 1000,
        '24h': 24 * 60 * 60 * 1000,
        '7d':  7  * 24 * 60 * 60 * 1000
    };
    return now - (map[range] || map['24h']);
}

window.initializeLiveTemp = function () {
    const db = window.firebaseDatabase;

    const latestRef = window.firebaseRef(db, 'sensors/temperature/latest');
    window.firebaseOnValue(latestRef, (snapshot) => {
        const temp = snapshot.val() || 0;
        const info = getTemperatureStatus(temp);
        document.getElementById('tempDisplay').textContent        = temp.toFixed(1) + '°C';
        document.getElementById('statusDesc').textContent         = info.desc;
        document.getElementById('lastUpdateText').textContent     =
            'Last updated: ' + new Date().toLocaleString('en-US', {
                hour: 'numeric', minute: '2-digit',
                month: 'short', day: 'numeric', year: 'numeric'
            });
        updateThermoIndicator(temp);
        const badge = document.getElementById('statusBadge');
        badge.textContent = info.status;
        badge.className   = 'status-badge-large ' + info.cls;
        document.getElementById('warningBanner').style.display = (temp < 15 || temp > 75) ? 'block' : 'none';
    });

    loadHistoryData('24h');
};

function loadHistoryData(range) {
    currentRange = range;
    document.querySelectorAll('.range-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.range === range);
    });

    const db       = window.firebaseDatabase;
    const cutoffMs = getRangeCutoffMs(range);

    const histRef = window.firebaseQuery(
        window.firebaseRef(db, 'sensors/temperature/history'),
        window.firebaseOrderByKey(),
        window.firebaseLimitToLast(500)
    );

    window.firebaseOnValue(histRef, (snapshot) => {
        const raw = snapshot.val();
        if (!raw) { showNoData(); return; }

        const entries = [];
        Object.entries(raw).forEach(([key, entry]) => {
            let val, tsMs;
            if (typeof entry === 'object' && entry !== null && 'value' in entry) {
                val  = parseFloat(entry.value);
                tsMs = entry.timestamp || parseFloat(key);
            } else {
                val  = parseFloat(entry);
                tsMs = parseFloat(key);
            }
            if (!isNaN(val) && !isNaN(tsMs) && tsMs >= cutoffMs) {
                entries.push({ ts: tsMs, value: val });
            }
        });
        entries.sort((a, b) => a.ts - b.ts);

        if (!entries.length) { showNoData(); return; }
        document.getElementById('chartNoData').style.display = 'none';

        const values = entries.map(e => e.value);
        document.getElementById('maxTempStat').textContent   = Math.max(...values).toFixed(1) + '°C';
        document.getElementById('avgTempStat').textContent   = (values.reduce((a,b)=>a+b,0)/values.length).toFixed(1) + '°C';
        document.getElementById('minTempStat').textContent   = Math.min(...values).toFixed(1) + '°C';
        document.getElementById('readingsCount').textContent = `(${entries.length} readings)`;

        renderChart(entries);
        renderHistory(entries);
    }, { onlyOnce: true });
}

function showNoData() {
    document.getElementById('chartNoData').style.display = 'block';
    ['maxTempStat','avgTempStat','minTempStat'].forEach(id =>
        document.getElementById(id).textContent = '--°C');
    document.getElementById('readingsCount').textContent = '';
    document.getElementById('historyContainer').innerHTML =
        '<p class="text-muted text-center py-4">No data found for this time range.</p>';
}

function renderChart(entries) {
    if (tempChart) { tempChart.destroy(); tempChart = null; }
    const labels = entries.map(e => formatTimestamp(e.ts));
    const values = entries.map(e => e.value);
    const ctx    = document.getElementById('tempChart').getContext('2d');
    const grad   = ctx.createLinearGradient(0,0,0,400);
    grad.addColorStop(0,'rgba(255,123,0,0.3)');
    grad.addColorStop(1,'rgba(255,0,0,0.05)');
    const step       = Math.ceil(labels.length / 10);
    const tickLabels = labels.map((l,i) => i % step === 0 ? l : '');
    tempChart = new Chart(ctx, {
        type: 'line',
        data: { labels: tickLabels, datasets: [{ label:'Temperature (°C)', data:values,
            borderColor:'#ff7b00', backgroundColor:grad, borderWidth:3, tension:0.4, fill:true,
            pointRadius: values.length > 50 ? 0 : 3, pointHoverRadius:6,
            pointBackgroundColor:'#ff7b00', pointBorderColor:'#fff', pointBorderWidth:2 }] },
        options: { responsive:true, maintainAspectRatio:true,
            interaction:{ mode:'index', intersect:false },
            plugins:{ legend:{display:false}, tooltip:{ backgroundColor:'rgba(0,0,0,0.8)', padding:12,
                callbacks:{ title:(items)=>labels[items[0].dataIndex], label:(ctx)=>` ${ctx.parsed.y.toFixed(1)}°C` } } },
            scales:{
                y:{ min:0, max:100, ticks:{callback:v=>v+'°C'}, grid:{color:'rgba(0,0,0,0.05)'} },
                x:{ ticks:{maxTicksLimit:10, maxRotation:0}, grid:{display:false} }
            }
        }
    });
}

function renderHistory(entries) {
    const container = document.getElementById('historyContainer');
    if (!entries.length) { container.innerHTML='<p class="text-muted text-center py-4">No history data available</p>'; return; }
    const recent = entries.slice(-20).reverse();
    container.innerHTML = recent.map(entry => {
        const val = entry.value;
        let badgeClass='success', badgeText='Active';
        if (val>=45&&val<=70)      { badgeClass='success'; badgeText='Active';  }
        else if (val>=20&&val<45)  { badgeClass='warning'; badgeText='Initial'; }
        else                       { badgeClass='danger';  badgeText='Alert';   }
        return `<div class="history-item">
            <div>
                <div class="history-value">${val.toFixed(1)}°C</div>
                <div class="history-time">${formatTimestamp(entry.ts)}</div>
            </div>
            <span class="badge bg-${badgeClass}">${badgeText}</span>
        </div>`;
    }).join('');
}

document.querySelectorAll('.range-btn').forEach(btn => {
    btn.addEventListener('click', () => loadHistoryData(btn.dataset.range));
});

(function () {
    const initTemp = <?php echo (float)$currentTemp; ?>;
    if (initTemp > 0) {
        const info  = getTemperatureStatus(initTemp);
        const badge = document.getElementById('statusBadge');
        badge.textContent = info.status;
        badge.className   = 'status-badge-large ' + info.cls;
        document.getElementById('statusDesc').textContent = info.desc;
        updateThermoIndicator(initTemp);
    }
})();
</script>

<?php include_once 'notif_bell.php'; ?>
</body>
</html>