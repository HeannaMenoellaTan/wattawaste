<?php
/**
 * Temperature Dashboard - Firebase Version
 */

session_start();

require_once 'firebase_config.php';

try {
    $database = getDatabase();
    
    // Fetch latest temperature from Firebase
    $latestTemp = $database->getReference("sensors/temperature/latest/value")->getValue() ?? 0;
    $latestTimestamp = $database->getReference("sensors/temperature/latest/timestamp")->getValue() ?? time();
    
    $temp_level = floatval($latestTemp);
    $created_at = date("Y-m-d H:i:s", $latestTimestamp);
    
    // Determine status based on temperature
    if ($temp_level >= 65) {
        $alert_text = "🔥 Mixer Activated! (Critical Temperature)";
        $alert_class = "critical";
        $status_label = "Critical";
    } elseif ($temp_level > 60) {
        $alert_text = "⚠️ Warning: Mixer Temperature Rising!";
        $alert_class = "warning";
        $status_label = "Warning";
    } elseif ($temp_level >= 40) {
        $alert_text = "✅ Temperature Level Normal";
        $alert_class = "optimal";
        $status_label = "Optimal";
    } else {
        $alert_text = "🌡️ Below Optimal Temperature";
        $alert_class = "below";
        $status_label = "Below Range";
    }
    
    // Get history filter
    $allowed = ['hour','day','week','month'];
    $history = isset($_GET['history']) && in_array($_GET['history'], $allowed) ? $_GET['history'] : 'hour';
    
    // Calculate time range for filtering
    switch ($history) {
        case 'day':
            $timeLimit = time() - (24 * 60 * 60);
            $history_label = "Last 24 Hours";
            break;
        case 'week':
            $timeLimit = time() - (7 * 24 * 60 * 60);
            $history_label = "Last 7 Days";
            break;
        case 'month':
            $timeLimit = time() - (30 * 24 * 60 * 60);
            $history_label = "Last 30 Days";
            break;
        case 'hour':
        default:
            $timeLimit = time() - (60 * 60);
            $history_label = "Last 60 Minutes";
            break;
    }
    
    // Fetch temperature history from Firebase
    $historyRef = $database->getReference("sensors/temperature/history");
    $historySnapshot = $historyRef->getSnapshot();
    
    $historyData = [];
    if ($historySnapshot->exists()) {
        foreach ($historySnapshot->getValue() as $key => $item) {
            $timestamp = $item['timestamp'] ?? 0;
            
            // Filter by time range
            if ($timestamp >= $timeLimit) {
                $historyData[] = [
                    'id' => $key,
                    'value' => $item['value'] ?? 0,
                    'timestamp' => $timestamp,
                    'created_at' => date('Y-m-d H:i:s', $timestamp)
                ];
            }
        }
    }
    
    // Sort by timestamp (newest first)
    usort($historyData, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    
    // Limit to 2000 records
    $historyData = array_slice($historyData, 0, 2000);
    
} catch (Exception $e) {
    error_log("Temperature page error: " . $e->getMessage());
    $temp_level = 0;
    $created_at = date("Y-m-d H:i:s");
    $alert_text = "⚠️ Error loading data";
    $alert_class = "below";
    $status_label = "Error";
    $historyData = [];
}

function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WattAWaste Dashboard - Temperature</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://kit.fontawesome.com/a2e0e6ad65.js" crossorigin="anonymous"></script>

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
        window.location.href = 'login.html';
    } else {
        console.log('✅ User authenticated:', user.email || user.phoneNumber);
    }
});

window.firebaseAuth = auth;
</script>

<?php include_once 'notif_bell.php'; ?>

<style>
body { font-family: Poppins, sans-serif; margin:0; padding:0; background:#f8fafc; color:#0f172a; }

/* ALERT BOX */
.alert-box { padding:12px; border-radius:10px; margin-bottom:18px; font-weight:700; }
.alert-box.optimal{ background:#dcfce7;color:#166534; }
.alert-box.warning{ background:#fef9c3;color:#92400e; }
.alert-box.critical{ background:#fee2e2;color:#991b1b; }
.alert-box.below{ background:#e5e7eb;color:#374151; }

/* THERMO CARD */
.thermo-card { background:#fff; padding:22px; border-radius:18px; max-width:900px; margin:auto; box-shadow:0 6px 20px rgba(0,0,0,0.06); position:relative; }
.meter-wrap { display:flex; align-items:center; gap:24px; position:relative; padding-top:40px; }
.thermometer { flex:1; height:60px; background:linear-gradient(180deg,#dfe6ee,#f7fafc); border-radius:50px; position:relative; overflow:hidden; box-shadow:inset 0 0 8px rgba(255,255,255,0.8), inset 0 0 18px rgba(0,0,0,0.2), 0 2px 10px rgba(0,0,0,0.08); }
.thermo-fill { height:100%; width:0%; transition: width .9s cubic-bezier(.4,0,.2,1); border-radius:50px; }
.thermo-fill.optimal  { background: linear-gradient(90deg,#3b82f6,#1e40af); } 
.thermo-fill.warning  { background: linear-gradient(90deg,#facc15,#eab308); }
.thermo-fill.critical { background: linear-gradient(90deg,#ef4444,#dc2626); }
.thermo-fill.below    { background: linear-gradient(90deg,#9ca3af,#6b7280); }

.thermo-label { font-weight:800; font-size:1.6rem; color:#0f172a; position:absolute; }
.status-label { font-weight:500; padding:4px 8px; border-radius:6px; display:inline-block; font-size:0.9rem; }

/* Tick labels */
.ticks { display:flex; justify-content:space-between; margin-top:8px; font-size:0.85rem; color:#475569; padding:0 10px; }

/* FIXED LEGEND */
.legend-inline {
    margin-top: 12px;
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    padding: 10px 15px;
    background: #f9fafb;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
}

.legend-inline .legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.9rem;
    color: #334155;
    font-weight: 500;
}

.legend-dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    display: inline-block;
}

/* COLORS SHOW NOW */
.legend-dot.optimal  { background:#3b82f6 !important; }
.legend-dot.warning  { background:#facc15 !important; }
.legend-dot.critical { background:#ef4444 !important; }
.legend-dot.below    { background:#9ca3af !important; }

/* TABLE */
.data-table { width:100%; max-width:900px; margin:20px auto; background:#fff; border-radius:10px; border-collapse:collapse; overflow:hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.data-table th { background:#334155;color:white;padding:12px; text-align:left; font-weight:600; }
.data-table td { padding:12px; border-bottom:1px solid #eee; }
.data-table tr:hover td { background:#f8fafc; }

/* HISTORY CONTROLS */
.history-controls { 
    max-width:900px; 
    margin:20px auto; 
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    gap:12px; 
    background: white;
    padding: 15px 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.history-controls select { 
    padding:10px 14px; 
    border-radius:8px; 
    border: 2px solid #e5e7eb;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.history-controls select:hover {
    border-color: #3b82f6;
}

.history-controls h3 {
    margin: 0;
    color: #0f172a;
    font-size: 1.1rem;
}

.small-muted {
    color: #64748b;
    font-size: 0.9rem;
}
</style>
</head>
<body>

<?php
include 'sideabr.php'; 
?>

<div class="main">
<?php include 'topnav.php';?>

<!-- ALERT -->
<div class="alert-box <?= e($alert_class) ?>">
    <?= e($alert_text) ?>
</div>

<!-- THERMO CARD -->
<div class="thermo-card">
    <?php $label_percent = min(max($temp_level,0),100); ?>
    
    <div class="thermo-label" style="left:<?= $label_percent ?>%; top:-10px; transform:translateX(-50%)">
        <?= number_format($temp_level,2) ?>°C
    </div>

    <div class="meter-wrap">
        <div class="thermometer">
            <div class="thermo-fill <?= e($alert_class) ?>" id="thermoFill" style="width:<?= $label_percent ?>%"></div>
        </div>

        <div style="min-width:160px;">
            <div><strong>Status:</strong>
                <span class="status-label <?= e($alert_class) ?>" id="statusLabel"><?= e($status_label) ?></span>
            </div>
            <div class="mt-2">
                <small class="small-muted">Recorded: <strong id="recordedTime"><?= e($created_at) ?></strong></small>
            </div>
        </div>
    </div>

    <div class="ticks">
        <span>0°C</span><span>20°C</span><span>30°C</span><span>40°C</span>
        <span>60°C</span><span>70°C</span><span>80°C</span><span>90°C</span><span>100°C</span>
    </div>

    <!-- FIXED LEGEND -->
    <div class="legend-inline">
        <div class="legend-item"><span class="legend-dot below"></span> Below Optimal (&lt;40°C)</div>
        <div class="legend-item"><span class="legend-dot optimal"></span> Optimal (40-60°C)</div>
        <div class="legend-item"><span class="legend-dot warning"></span> Warning (60-65°C)</div>
        <div class="legend-item"><span class="legend-dot critical"></span> Critical (≥65°C)</div>
    </div>
</div>

<!-- HISTORY CONTROLS -->
<div class="history-controls">
    <h3>📊 Temperature History</h3>
    <div>
        <label for="historySelect" style="margin-right: 10px; font-weight: 600;">View:</label>
        <select id="historySelect" onchange="changeHistory()">
            <option value="hour" <?= $history == 'hour' ? 'selected' : '' ?>>Last 60 Minutes</option>
            <option value="day" <?= $history == 'day' ? 'selected' : '' ?>>Last 24 Hours</option>
            <option value="week" <?= $history == 'week' ? 'selected' : '' ?>>Last 7 Days</option>
            <option value="month" <?= $history == 'month' ? 'selected' : '' ?>>Last 30 Days</option>
        </select>
    </div>
</div>

<!-- HISTORY TABLE -->
<table class="data-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Temperature</th>
            <th>Status</th>
            <th>Recorded At</th>
        </tr>
    </thead>
    <tbody id="historyTableBody">
        <?php if (empty($historyData)): ?>
            <tr>
                <td colspan="4" style="text-align:center; color:#64748b; padding:20px;">
                    No data available for this time period
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($historyData as $index => $record): ?>
            <?php
                $temp = $record['value'];
                if ($temp >= 65) {
                    $row_class = 'critical';
                    $row_status = 'Critical';
                } elseif ($temp > 60) {
                    $row_class = 'warning';
                    $row_status = 'Warning';
                } elseif ($temp >= 40) {
                    $row_class = 'optimal';
                    $row_status = 'Optimal';
                } else {
                    $row_class = 'below';
                    $row_status = 'Below Range';
                }
            ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><strong><?= number_format($temp, 2) ?>°C</strong></td>
                <td><span class="status-label <?= $row_class ?>"><?= $row_status ?></span></td>
                <td><?= e($record['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</div>

<script>
function changeHistory() {
    const sel = document.getElementById('historySelect');
    window.location = "?history=" + encodeURIComponent(sel.value);
}

// Real-time temperature updates
async function updateTemperature() {
    try {
        const res = await fetch('api/get_latest.php', { cache: 'no-store' });
        const { latest } = await res.json();
        
        if (latest && latest.temperature !== undefined) {
            const temp = parseFloat(latest.temperature);
            
            // Update display
            const label = document.querySelector('.thermo-label');
            if (label) {
                label.textContent = temp.toFixed(2) + '°C';
                
                // Update position
                const percent = Math.min(Math.max(temp, 0), 100);
                label.style.left = percent + '%';
            }
            
            // Update fill bar
            const fill = document.getElementById('thermoFill');
            if (fill) {
                const percent = Math.min(Math.max(temp, 0), 100);
                fill.style.width = percent + '%';
                
                // Update class
                fill.className = 'thermo-fill';
                if (temp >= 65) {
                    fill.classList.add('critical');
                } else if (temp > 60) {
                    fill.classList.add('warning');
                } else if (temp >= 40) {
                    fill.classList.add('optimal');
                } else {
                    fill.classList.add('below');
                }
            }
            
            // Update status
            const statusLabel = document.getElementById('statusLabel');
            if (statusLabel) {
                statusLabel.className = 'status-label';
                if (temp >= 65) {
                    statusLabel.textContent = 'Critical';
                    statusLabel.classList.add('critical');
                } else if (temp > 60) {
                    statusLabel.textContent = 'Warning';
                    statusLabel.classList.add('warning');
                } else if (temp >= 40) {
                    statusLabel.textContent = 'Optimal';
                    statusLabel.classList.add('optimal');
                } else {
                    statusLabel.textContent = 'Below Range';
                    statusLabel.classList.add('below');
                }
            }
            
            // Update time
            const timeEl = document.getElementById('recordedTime');
            if (timeEl) {
                const now = new Date();
                timeEl.textContent = now.toLocaleString('en-US', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
            }
        }
    } catch (e) {
        console.error('Error updating temperature:', e);
    }
}

// Update every 3 seconds
setInterval(updateTemperature, 3000);
</script>

</body>
</html>