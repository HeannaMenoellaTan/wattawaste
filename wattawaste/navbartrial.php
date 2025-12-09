<?php
session_start();

$conn = new mysqli("localhost", "root", "", "wattawaste_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

$latestRes = $conn->query("SELECT * FROM temperatures ORDER BY Temp_Id DESC LIMIT 1");
$latest = $latestRes ? $latestRes->fetch_assoc() : null;

$temp_level = isset($latest['Temp_Ave']) ? floatval($latest['Temp_Ave']) : 0.0;
$created_at = $latest['created_at'] ?? date("Y-m-d H:i:s");

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

$allowed = ['hour','day','week','month'];
$history = isset($_GET['history']) && in_array($_GET['history'], $allowed) ? $_GET['history'] : 'hour';

switch ($history) {
    case 'day':
        $time_where = "created_at >= (NOW() - INTERVAL 24 HOUR)";
        $history_label = "Last 24 Hours";
        break;
    case 'week':
        $time_where = "created_at >= (NOW() - INTERVAL 7 DAY)";
        $history_label = "Last 7 Days";
        break;
    case 'month':
        $time_where = "created_at >= (NOW() - INTERVAL 30 DAY)";
        $history_label = "Last 30 Days";
        break;
    case 'hour':
    default:
        $time_where = "created_at >= (NOW() - INTERVAL 60 MINUTE)";
        $history_label = "Last 60 Minutes";
        break;
}

$historyQuery = "SELECT Temp_Id, Temp_Sensor, Temp_Ave, Temp_Range, created_at
                 FROM temperatures
                 WHERE $time_where
                 ORDER BY created_at DESC
                 LIMIT 2000";
$historyRes = $conn->query($historyQuery);

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>WattAWaste Dashboard - Temperature</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body { font-family: sans-serif; margin:0; padding:0; background:#f8fafc; color:#0f172a; }

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
.data-table { width:100%; max-width:900px; margin:10px auto; background:#fff; border-radius:10px; border-collapse:collapse; overflow:hidden; }
.data-table th { background:#334155;color:white;padding:10px; text-align:left; }
.data-table td { padding:10px; border-bottom:1px solid:#eee; }
.data-table tr:hover td { background:#f8fafc; }

/* HISTORY CONTROLS */
.history-controls { max-width:900px; margin:12px auto; display:flex; justify-content:space-between; align-items:center; gap:12px; }
.history-controls select { padding:8px 10px; border-radius:8px; }
</style>
</head>
<body>

<?php
include 'sideabr.php'; 
include 'notif_bell.php';
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
            <div class="thermo-fill <?= e($alert_class) ?>" style="width:<?= $label_percent ?>%"></div>
        </div>

        <div style="min-width:160px;">
            <div><strong>Status:</strong>
                <span class="status-label <?= e($alert_class) ?>"><?= e($status_label) ?></span>
            </div>
            <div class="mt-2">
                <small class="muted">Recorded: <strong><?= e($created_at) ?></strong></small>
            </div>
        </div>
    </div>

    <div class="ticks">
        <span>0°C</span><span>20°C</span><span>30°C</span><span>40°C</span><span>50°C</span>
        <span>60°C</span><span>70°C</span><span>80°C</span><span>90°C</span><span>100°C</span>
    </div>

    <!-- FIXED LEGEND (NOW WORKS) -->
    <div class="legend-inline">
        <div class="legend-item"><span class="legend-dot below"></span> Below Optimal</div>
        <div class="legend-item"><span class="legend-dot optimal"></span> Optimal</div>
        <div class="legend-item"><span class="legend-dot warning"></span> Warning</div>
        <div class="legend-item"><span class="legend-dot critical"></span> Critical</div>
    </div>
</div>


<script>
function changeHistory() {
    const sel = document.getElementById('historySelect');
    window.location = "?history=" + encodeURIComponent(sel.value);
}
</script>

</body>
</html>
