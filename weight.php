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
import { initializeApp }               from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
import { getAuth, onAuthStateChanged }  from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
import { getDatabase, ref, onValue, query, orderByKey, limitToLast, set, push, get }
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
    if (!user) {
        window.location.href = 'login.php';
    } else {
        sessionStorage.setItem('userEmail', user.email || user.phoneNumber || '');
        sessionStorage.setItem('userId',    user.uid);
        window.initializeWeightMonitoring();
    }
});

window.firebaseDatabase    = database;
window.firebaseRef         = ref;
window.firebaseOnValue     = onValue;
window.firebaseQuery       = query;
window.firebaseOrderByKey  = orderByKey;
window.firebaseLimitToLast = limitToLast;
window.firebaseSet         = set;
window.firebasePush        = push;
window.firebaseGet         = get;
</script>

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
    margin: 0; padding: 0;
    color: var(--ink);
    min-height: 100vh;
}

.card {
    border: none; border-radius: 16px; background: var(--panel);
    box-shadow: 0 6px 16px rgba(2,6,23,.06);
    transition: transform .2s, box-shadow .2s;
}
.card:hover { transform: translateY(-3px); box-shadow: 0 12px 26px rgba(2,6,23,.12); }

.page-title { font-size: 28px; font-weight: 700; color: var(--ink); margin-bottom: 24px; }

/* Warning Popup */
.warning-popup {
    display: none; position: fixed; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    background: white; border: 3px solid #ff6b6b; border-radius: 15px;
    padding: 30px 40px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    z-index: 10000; text-align: center; min-width: 400px;
    animation: slideDown 0.4s ease;
}
.warning-popup.show { display: block; }
.warning-popup-overlay {
    display: none; position: fixed; top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); z-index: 9999;
}
.warning-popup-overlay.show { display: block; }
.warning-popup i { color: #ff6b6b; font-size: 48px; margin-bottom: 15px; animation: pulse 2s infinite; }
.warning-popup h3 { color: #d63031; font-size: 20px; margin: 10px 0; }
.warning-popup p  { color: #636e72; margin-bottom: 20px; }
.warning-popup button {
    background: #ff6b6b; color: white; border: none;
    padding: 12px 30px; border-radius: 8px;
    font-size: 16px; cursor: pointer; font-weight: 600;
    transition: background 0.3s ease;
}
.warning-popup button:hover { background: #ee5a6f; }

@keyframes slideDown {
    from { transform: translate(-50%, -60%); opacity: 0; }
    to   { transform: translate(-50%, -50%); opacity: 1; }
}
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50%       { transform: scale(1.1); }
}

/* Cards */
.cards-row {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 25px; margin-bottom: 30px;
}
.weight-card {
    background: white; border-radius: 15px; padding: 30px;
    box-shadow: 0 6px 16px rgba(2,6,23,.06);
    transition: transform .2s, box-shadow .2s;
}
.weight-card:hover { transform: translateY(-3px); box-shadow: 0 12px 26px rgba(2,6,23,.12); }
.weight-card h3 { font-size: 18px; font-weight: 600; margin-bottom: 20px; color: var(--ink); }
.weight-value   { font-size: 42px; font-weight: bold; color: var(--brand-dark); margin-bottom: 5px; }
.weight-capacity { font-size: 14px; color: var(--muted); margin-bottom: 15px; }

.progress-bar-container {
    width: 100%; height: 12px; background: #e8e8e8;
    border-radius: 10px; overflow: hidden; margin-top: 10px;
}
.progress-bar-fill {
    height: 100%; border-radius: 10px;
    transition: width 0.5s ease, background 0.3s ease;
}

.stat-card { text-align: center; padding: 20px; }
.stat-value { font-size: 36px; font-weight: 800; color: var(--brand-dark); line-height: 1; }
.stat-label { font-size: 14px; color: var(--muted); margin-top: 8px; }

/* Fertilizer status badge */
.fertilizer-status {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 20px; font-size: 13px;
    font-weight: 600; margin-top: 10px;
}
.fertilizer-status.no-weight {
    background: #fef3c7; color: #92400e;
}
.fertilizer-status.composting {
    background: #dcfce7; color: #166534;
}
.fertilizer-status.ready {
    background: #d1fae5; color: #065f46;
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

.alert-custom { padding: 16px 20px; border-radius: 12px; border-left: 4px solid; font-weight: 500; }
.alert-custom.warning { background: #FFF8E1; color: #7A5A00; border-color: var(--warning); }
.alert-custom.info    { background: #E3F2FD; color: #0D47A1; border-color: #2196F3; }

/* No compost state */
.empty-state {
    text-align: center; padding: 40px; color: var(--muted);
}
.empty-state i { font-size: 48px; opacity: 0.3; margin-bottom: 16px; display: block; }

/* Range selector buttons */
.range-btn {
    border: 1.5px solid #dee2e6; background: #fff; border-radius: 8px;
    padding: 5px 14px; font-size: .85rem; cursor: pointer; transition: all .15s;
}
.range-btn:hover  { background: #f1f5f9; }
.range-btn.active { background: var(--brand-dark); color: #fff; border-color: var(--brand-dark); }

/* Weight change indicator */
.weight-change-badge {
    display: inline-block; padding: 3px 10px; border-radius: 12px;
    font-size: 12px; font-weight: 600; margin-left: 8px;
}
.weight-change-badge.decrease { background: #dcfce7; color: #166534; }
.weight-change-badge.increase { background: #fee2e2; color: #991b1b; }
.weight-change-badge.stable   { background: #f3f4f6; color: #6b7280; }

@media (max-width: 768px) {
    .cards-row { grid-template-columns: 1fr; }
    .warning-popup { min-width: 300px; padding: 20px 30px; }
    .history-value { font-size: 18px; }
    .history-time  { font-size: 11px; }
}
</style>
</head>
<body>

<?php include 'sideabr.php'; ?>

<div class="main">
<?php include 'topnav.php'; ?>

<div class="container-fluid px-4 py-4">

<!-- Warning Popup -->
<div class="warning-popup-overlay" id="warningOverlay" onclick="closeWarning()"></div>
<div class="warning-popup" id="warningPopup">
    <i class="fas fa-exclamation-triangle"></i>
    <h3>⚠️ Warning!</h3>
    <p id="warningPopupText">The bin is almost full!</p>
    <button onclick="closeWarning()">Got it</button>
</div>

<h1 class="page-title">⚖️ Weight Monitoring</h1>

<!-- Two-Card Design -->
<div class="cards-row">
    <!-- Current Weight -->
    <div class="weight-card">
        <h3><i class="fas fa-weight me-2"></i>Current Weight</h3>
        <div class="weight-value" id="currentWeightDisplay">-- kg</div>
        <div class="weight-capacity">Capacity: <span id="capacityDisplay">100</span> kg</div>
        <div class="progress-bar-container">
            <div class="progress-bar-fill" id="weightProgress" style="width:0%;"></div>
        </div>
        <div class="mt-2 text-muted" style="font-size:13px;" id="weightPercentText">0.0% Full</div>
        <div class="mt-1 text-muted" style="font-size:12px;" id="lastUpdateText">Last updated: --</div>
        <!-- Weight change indicator -->
        <div class="mt-2" id="weightChangeContainer" style="display:none;">
            <small class="text-muted">24h change: </small>
            <span class="weight-change-badge" id="weightChangeBadge">--</span>
        </div>
    </div>

    <!-- Total Compost Fertilizer -->
    <div class="weight-card">
        <h3><i class="fas fa-seedling me-2"></i>Total Compost Fertilizer</h3>
        <div class="weight-value" id="fertilizerDisplay">-- kg</div>

        <!-- Status indicator: shown based on weight state -->
        <div id="fertilizerStatusArea">
            <span class="fertilizer-status no-weight" id="fertilizerStatusBadge">
                <i class="fas fa-clock"></i> Waiting for compost
            </span>
        </div>

        <div class="weight-capacity mt-2">Current yield (50% conversion)</div>
        <div class="progress-bar-container">
            <div class="progress-bar-fill" id="fertilizerProgress" style="width:0%;"></div>
        </div>
        <div class="mt-2 text-muted" style="font-size:13px;" id="fertilizerMaxText">Maximum: 50 kg</div>

        <!-- Initial weight info -->
        <div class="mt-2 text-muted" style="font-size:12px;">
            Initial batch: <span id="initialWeightText" style="font-weight:600;">-- kg</span>
        </div>
    </div>
</div>

<!-- Warning / Info Banner -->
<div class="alert-custom warning mb-4" id="warningBanner" style="display:none;">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Warning:</strong> <span id="warningBannerText"></span>
</div>
<div class="alert-custom info mb-4" id="infoBanner" style="display:none;">
    <i class="fas fa-info-circle me-2"></i>
    <span id="infoBannerText"></span>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <i class="fas fa-weight-hanging text-success mb-2" style="font-size:32px;"></i>
            <div class="stat-value" id="maxWeightStat">-- kg</div>
            <div class="stat-label">Maximum (selected range)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <i class="fas fa-chart-line text-primary mb-2" style="font-size:32px;"></i>
            <div class="stat-value" id="avgWeightStat">-- kg</div>
            <div class="stat-label">Average (selected range)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <i class="fas fa-arrow-down text-info mb-2" style="font-size:32px;"></i>
            <div class="stat-value" id="minWeightStat">-- kg</div>
            <div class="stat-label">Minimum (selected range)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <i class="fas fa-seedling text-warning mb-2" style="font-size:32px;"></i>
            <div class="stat-value" id="fertilizerStat">-- kg</div>
            <div class="stat-label">Fertilizer Output</div>
        </div>
    </div>
</div>

<!-- Weight Trend Chart -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h5 class="mb-0">
                    <i class="fas fa-chart-area text-primary me-2"></i>
                    Weight Trend
                    <span id="readingsCount" class="small-muted fs-6"></span>
                </h5>
                <div class="d-flex gap-1 flex-wrap" id="rangeButtons">
                    <button class="range-btn" data-range="1h">1h</button>
                    <button class="range-btn" data-range="6h">6h</button>
                    <button class="range-btn active" data-range="24h">24h</button>
                    <button class="range-btn" data-range="7d">7d</button>
                    <button class="range-btn" data-range="30d">30d</button>
                </div>
            </div>
            <canvas id="weightChart" style="max-height:350px;"></canvas>
            <div id="chartNoData" class="text-center text-muted small mt-2" style="display:none;">
                No data yet for this range. Waiting for ESP32 readings...
            </div>
        </div>
    </div>
</div>

<!-- Recent Weight Readings -->
<div class="row g-4">
    <div class="col-12">
        <div class="card p-4">
            <h5 class="mb-3">
                <i class="fas fa-history text-success me-2"></i>
                Recent Live Readings
                <span class="small-muted fs-6 ms-1">(latest 20, duplicates excluded)</span>
            </h5>
            <div id="historyContainer" style="max-height:500px;overflow-y:auto;">
                <p class="text-muted text-center py-4">Loading weight history...</p>
            </div>
        </div>
    </div>
</div>

</div>
</div><!-- end .main -->

<script>
let weightChart  = null;
let currentRange = '24h';
const CAPACITY = 100; // kg

// ── Duplicate Detection Settings ──────────────────────────────────────────────
// Min difference in kg to count as a new reading (avoids recording sensor noise)
const MIN_WEIGHT_CHANGE_KG = 0.05;
// Min time between same-value recordings (ms) — 6 hours for stable readings
const STABLE_RECORD_INTERVAL_MS = 6 * 60 * 60 * 1000;

// ── Helpers ────────────────────────────────────────────────────────────────────
function closeWarning() {
    document.getElementById('warningPopup').classList.remove('show');
    document.getElementById('warningOverlay').classList.remove('show');
}

function showWarningPopup(percentage) {
    document.getElementById('warningPopupText').textContent =
        `The bin is almost full (${percentage.toFixed(1)}%)!`;
    document.getElementById('warningPopup').classList.add('show');
    document.getElementById('warningOverlay').classList.add('show');
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
        '7d':  7  * 24 * 60 * 60 * 1000,
        '30d': 30 * 24 * 60 * 60 * 1000
    };
    return now - (map[range] || map['24h']);
}

// ── DEDUPLICATION: Remove consecutive same-or-noise readings ──────────────────
// Rules:
//   1. Skip if value difference from last recorded < MIN_WEIGHT_CHANGE_KG
//      UNLESS enough time has passed (STABLE_RECORD_INTERVAL_MS)
//      This way, a stable weight still records once every 6h to show it's holding
//   2. Always keep the first entry
function deduplicateEntries(entries) {
    if (!entries.length) return [];

    const result = [entries[0]];
    let lastRecorded = entries[0];

    for (let i = 1; i < entries.length; i++) {
        const current = entries[i];
        const valueDiff = Math.abs(current.value - lastRecorded.value);
        const timeDiff  = current.ts - lastRecorded.ts;

        const hasSignificantChange = valueDiff >= MIN_WEIGHT_CHANGE_KG;
        const isStableCheckpoint   = timeDiff >= STABLE_RECORD_INTERVAL_MS;

        if (hasSignificantChange || isStableCheckpoint) {
            result.push(current);
            lastRecorded = current;
        }
        // else: skip — duplicate/noise reading
    }

    return result;
}

// ── Update the two hero cards ──────────────────────────────────────────────────
function updateWeightDisplay(weight, initialWeight) {
    const percentage  = (weight / CAPACITY) * 100;
    const fertilizer  = weight > 0 ? weight * 0.5 : 0;

    document.getElementById('currentWeightDisplay').textContent = weight.toFixed(2) + ' kg';
    document.getElementById('capacityDisplay').textContent      = CAPACITY.toLocaleString();
    document.getElementById('weightPercentText').textContent    = percentage.toFixed(2) + '% Full';
    document.getElementById('lastUpdateText').textContent       =
        'Last updated: ' + new Date().toLocaleString('en-US', {
            month: 'short', day: 'numeric', year: 'numeric',
            hour: 'numeric', minute: '2-digit', hour12: true
        });

    // Initial weight display
    if (initialWeight > 0) {
        document.getElementById('initialWeightText').textContent = initialWeight.toFixed(2) + ' kg';
    } else {
        document.getElementById('initialWeightText').textContent = 'Not set';
    }

    // Progress bar for weight
    const weightProgress = document.getElementById('weightProgress');
    weightProgress.style.width = Math.min(100, percentage) + '%';

    const banner = document.getElementById('warningBanner');
    const infoBanner = document.getElementById('infoBanner');

    if (weight <= 0) {
        // Empty bin
        weightProgress.style.background = '#e8e8e8';
        banner.style.display = 'none';
        infoBanner.style.display = 'block';
        document.getElementById('infoBannerText').textContent =
            'The compost bin is empty. Add organic waste to begin the composting process.';
    } else if (percentage >= 80) {
        weightProgress.style.background = 'linear-gradient(90deg, #ff6b6b, #ee5a6f)';
        banner.style.display = 'block';
        infoBanner.style.display = 'none';
        document.getElementById('warningBannerText').textContent =
            `The bin is reaching capacity (${percentage.toFixed(1)}%). Consider emptying soon.`;
    } else if (percentage >= 60) {
        weightProgress.style.background = 'linear-gradient(90deg, #ffd93d, #f6c23e)';
        banner.style.display = 'none';
        infoBanner.style.display = 'none';
    } else {
        weightProgress.style.background = 'linear-gradient(90deg, #6fcf97, #27ae60)';
        banner.style.display = 'none';
        infoBanner.style.display = 'none';
    }

    // ── Fertilizer card ────────────────────────────────────────────────────────
    const fertDisplay   = document.getElementById('fertilizerDisplay');
    const fertProgress  = document.getElementById('fertilizerProgress');
    const fertMaxText   = document.getElementById('fertilizerMaxText');
    const fertStat      = document.getElementById('fertilizerStat');
    const statusBadge   = document.getElementById('fertilizerStatusBadge');

    if (weight <= 0) {
        // No compost → no fertilizer
        fertDisplay.textContent  = '0.00 kg';
        fertStat.textContent     = '0.00 kg';
        fertProgress.style.width = '0%';
        fertMaxText.textContent  = 'Add compost to start tracking';
        statusBadge.className    = 'fertilizer-status no-weight';
        statusBadge.innerHTML    = '<i class="fas fa-clock"></i> Waiting for compost';
    } else {
        // Has weight — show fertilizer estimate
        const maxFertilizer = CAPACITY * 0.5;
        const fertPct = Math.min(100, (fertilizer / maxFertilizer) * 100);

        fertDisplay.textContent  = fertilizer.toFixed(2) + ' kg';
        fertStat.textContent     = fertilizer.toFixed(2) + ' kg';
        fertMaxText.textContent  = 'Maximum: ' + maxFertilizer.toLocaleString() + ' kg';
        fertProgress.style.width = fertPct + '%';
        fertProgress.style.background = 'linear-gradient(90deg, #ffd93d, #f6c23e)';

        statusBadge.className = 'fertilizer-status composting';
        statusBadge.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Composting in progress';
    }
}

// ── Stats row ──────────────────────────────────────────────────────────────────
function updateStatistics(entries) {
    if (!entries.length) return;

    const values = entries.map(e => e.value);
    const maxW   = Math.max(...values);
    const minW   = Math.min(...values);
    const avgW   = values.reduce((a, b) => a + b, 0) / values.length;

    document.getElementById('maxWeightStat').textContent = maxW.toFixed(2) + ' kg';
    document.getElementById('avgWeightStat').textContent = avgW.toFixed(2) + ' kg';
    document.getElementById('minWeightStat').textContent = minW.toFixed(2) + ' kg';
    document.getElementById('readingsCount').textContent  = `(${entries.length} readings)`;
}

// ── Chart ──────────────────────────────────────────────────────────────────────
function renderChart(entries) {
    if (weightChart) { weightChart.destroy(); weightChart = null; }

    const labels = entries.map(e => formatTimestamp(e.ts));
    const values = entries.map(e => e.value);

    const ctx  = document.getElementById('weightChart').getContext('2d');
    const grad = ctx.createLinearGradient(0, 0, 0, 400);
    grad.addColorStop(0, 'rgba(76,175,80,0.3)');
    grad.addColorStop(1, 'rgba(76,175,80,0.05)');

    const maxTicks  = 10;
    const step      = Math.ceil(labels.length / maxTicks);
    const tickLabels = labels.map((l, i) => (i % step === 0) ? l : '');

    weightChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: tickLabels,
            datasets: [{
                label: 'Weight (kg)',
                data: values,
                borderColor: '#4CAF50',
                backgroundColor: grad,
                borderWidth: 3, tension: 0.4, fill: true,
                pointRadius: values.length > 50 ? 0 : 4,
                pointHoverRadius: 7,
                pointBackgroundColor: '#4CAF50',
                pointBorderColor: '#fff', pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)', padding: 12,
                    callbacks: {
                        title: (items) => labels[items[0].dataIndex],
                        label: (ctx)   => ' Weight: ' + ctx.parsed.y.toFixed(2) + ' kg'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true, min: 0, max: CAPACITY,
                    ticks: { callback: v => v.toLocaleString() + ' kg' },
                    grid:  { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    ticks: { maxTicksLimit: 10, maxRotation: 0 },
                    grid:  { display: false }
                }
            }
        }
    });
}

// ── History list ───────────────────────────────────────────────────────────────
function renderHistory(entries) {
    const container = document.getElementById('historyContainer');

    if (!entries.length) {
        container.innerHTML = '<p class="text-muted text-center py-4">No history data available</p>';
        return;
    }

    const recent = entries.slice(-20).reverse();
    let html = '';

    recent.forEach((entry, idx) => {
        const pct = (entry.value / CAPACITY) * 100;
        let badgeClass = 'success', badgeText = 'Normal';
        if      (pct >= 80) { badgeClass = 'danger';            badgeText = 'Almost Full'; }
        else if (pct >= 60) { badgeClass = 'warning text-dark'; badgeText = 'Filling';    }
        else if (entry.value <= 0) { badgeClass = 'secondary';  badgeText = 'Empty';      }

        // Show weight change from previous reading
        let changeHtml = '';
        if (idx < recent.length - 1) {
            const prev = recent[idx + 1];
            const diff = entry.value - prev.value;
            if (Math.abs(diff) >= MIN_WEIGHT_CHANGE_KG) {
                const cls  = diff < 0 ? 'decrease' : 'increase';
                const sign = diff < 0 ? '▼' : '▲';
                changeHtml = `<span class="weight-change-badge ${cls}">${sign} ${Math.abs(diff).toFixed(2)} kg</span>`;
            }
        }

        html += `
            <div class="history-item">
                <div>
                    <div class="history-value">${entry.value.toFixed(2)} kg ${changeHtml}</div>
                    <div class="history-time">${formatTimestamp(entry.ts)}</div>
                </div>
                <span class="badge bg-${badgeClass}">${badgeText}</span>
            </div>`;
    });

    container.innerHTML = html;
}

// ── Load history from Firebase filtered by range ───────────────────────────────
function loadHistoryData(range) {
    currentRange = range;

    document.querySelectorAll('.range-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.range === range);
    });

    const db       = window.firebaseDatabase;
    const cutoffMs = getRangeCutoffMs(range);

    const histRef = window.firebaseQuery(
        window.firebaseRef(db, 'sensors/weight/history'),
        window.firebaseOrderByKey(),
        window.firebaseLimitToLast(1000) // fetch more, we'll dedupe client-side
    );

    window.firebaseOnValue(histRef, (snapshot) => {
        const raw = snapshot.val();

        if (!raw) {
            showNoData();
            return;
        }

        const rawEntries = [];

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
                rawEntries.push({ ts: tsMs, value: val });
            }
        });

        // Sort ascending by timestamp
        rawEntries.sort((a, b) => a.ts - b.ts);

        // ── DEDUPLICATION: remove redundant same-value entries ─────────────────
        const entries = deduplicateEntries(rawEntries);

        if (!entries.length) {
            showNoData();
            return;
        }

        document.getElementById('chartNoData').style.display = 'none';

        updateStatistics(entries);
        renderChart(entries);
        renderHistory(entries);

        // Show how many were filtered
        const filtered = rawEntries.length - entries.length;
        if (filtered > 0) {
            document.getElementById('readingsCount').textContent =
                `(${entries.length} readings, ${filtered} duplicates removed)`;
        }

    }, { onlyOnce: true });
}

function showNoData() {
    document.getElementById('chartNoData').style.display = 'block';
    ['maxWeightStat','avgWeightStat','minWeightStat'].forEach(id =>
        document.getElementById(id).textContent = '-- kg');
    document.getElementById('readingsCount').textContent = '';
    document.getElementById('historyContainer').innerHTML =
        '<p class="text-muted text-center py-4">No data found for this time range.</p>';
}

// ── Initialize (called after Firebase auth) ────────────────────────────────────
window.initializeWeightMonitoring = function () {
    const db = window.firebaseDatabase;

    // Fetch initial weight for reference
    let initialWeight = 0;
    window.firebaseOnValue(window.firebaseRef(db, 'sensors/weight/initial'), (snap) => {
        initialWeight = snap.val() ?? 0;
    });

    // Live latest weight — real-time listener
    const latestRef = window.firebaseRef(db, 'sensors/weight/latest');
    window.firebaseOnValue(latestRef, (snapshot) => {
        const weight = snapshot.val() ?? 0;
        updateWeightDisplay(weight, initialWeight);

        const pct = (weight / CAPACITY) * 100;
        if (weight > 0 && pct >= 80) showWarningPopup(pct);
    });

    // Initial history load
    loadHistoryData('24h');
};

// ── Wire range buttons ─────────────────────────────────────────────────────────
document.querySelectorAll('.range-btn').forEach(btn => {
    btn.addEventListener('click', () => loadHistoryData(btn.dataset.range));
});

window.addEventListener('load', () => {
    document.querySelectorAll('.progress-bar-fill').forEach(bar => {
        const w = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => { bar.style.width = w; }, 100);
    });
});
</script>
<?php include_once 'notif_bell.php'; ?>
</body>
</html>