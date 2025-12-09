<?php
session_start();
$conn = new mysqli("localhost", "root", "", "wattawaste_system");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// Latest temperature
$latestRes = $conn->query("SELECT * FROM temperatures ORDER BY Temp_Id DESC LIMIT 1");
$latest = $latestRes ? $latestRes->fetch_assoc() : null;

$temp_level = isset($latest['Temp_Ave']) ? floatval($latest['Temp_Ave']) : 0.0;
$temp_id = $latest['Temp_Id'] ?? 0;
$created_at = $latest['created_at'] ?? date("Y-m-d H:i:s");

// Status determination
if ($temp_level >= 65) { $alert_class="temp-critical"; $status_label="Critical"; $alert_text="🔥 Mixer Activated!"; }
elseif ($temp_level>60) { $alert_class="temp-warning"; $status_label="Warning"; $alert_text="⚠️ Temperature Rising!"; }
elseif ($temp_level>=40){ $alert_class="temp-optimal"; $status_label="Optimal"; $alert_text="✅ Temperature Normal"; }
else { $alert_class="temp-below"; $status_label="Below Range"; $alert_text="🌡️ Below Optimal"; }

// Fetch last 10 readings for chart
$chartRes = $conn->query("SELECT Temp_Ave, created_at FROM temperatures ORDER BY Temp_Id DESC LIMIT 10");
$temp_data = $temp_labels = [];
if($chartRes){
    while($row = $chartRes->fetch_assoc()){
        $temp_data[] = floatval($row['Temp_Ave']);
        $temp_labels[] = $row['created_at'];
    }
}
$temp_data = array_reverse($temp_data);
$temp_labels = array_reverse($temp_labels);

// KPI calculation
$avg_temp = $temp_data ? round(array_sum($temp_data)/count($temp_data),2) : 0;
$max_temp = $temp_data ? max($temp_data) : 0;
$min_temp = $temp_data ? min($temp_data) : 0;

// History records
$historyRes = $conn->query("SELECT * FROM temperatures ORDER BY Temp_Id DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Temperature Dashboard | WattAWaste</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { font-family:Arial,sans-serif; margin:0; padding:0; background:#f5f7f7; color:#1f2937; }
.main { padding:30px; max-width:1200px; margin:auto; }

/* ALERT BOX */
.alert-box { padding:14px; border-radius:12px; font-weight:600; margin-bottom:20px; text-align:center; font-size:1.05rem; transition:all 0.3s ease; }
.temp-optimal  { background:#dcfce7;color:#166534; }
.temp-warning  { background:#fef9c3;color:#92400e; }
.temp-critical { background:#fee2e2;color:#991b1b; }
.temp-below    { background:#e5e7eb;color:#374151; }

/* KPI Cards */
.kpi-container { display:flex; flex-wrap:wrap; gap:20px; margin-bottom:25px; justify-content:center; }
.kpi { flex:1 1 200px; background:#fff; border-radius:12px; padding:20px; box-shadow:0 3px 15px rgba(0,0,0,0.05); text-align:center; transition: transform 0.3s ease; }
.kpi:hover { transform: translateY(-4px); }
.kpi i { font-size:28px; margin-bottom:8px; color:#2563eb; }
.kpi span.label { display:block; font-weight:600; font-size:0.95rem; color:#64748b; }
.kpi span.value { display:block; font-size:1.4rem; font-weight:700; margin-top:6px; }

/* Thermometer */
.thermo-card {
    background: #fff;
    border-radius: 15px;
    padding: 25px;       /* slightly bigger padding */
    max-width: 180px;    /* increase width */
    margin: auto;
    box-shadow: 0 4px 18px rgba(0,0,0,0.07);
    text-align: center;
}
.thermo-bar {
    width: 40px;          /* wider */
    height: 500px;        /* taller */
    background: #e5e7eb;
    border-radius: 25px;
    margin: auto;
    position: relative;
    overflow: hidden;
}
.thermo-fill {
    position: absolute;
    bottom: 0;
    width: 100%;
    border-radius: 25px;
    transition: height 1s ease;
}.thermo-fill.temp-optimal  { background: linear-gradient(to right, #3b82f6, #1e40af); }
.thermo-fill.temp-warning  { background: linear-gradient(to right, #facc15, #eab308); }
.thermo-fill.temp-critical { background: linear-gradient(to right, #ef4444, #dc2626); }
.thermo-fill.temp-below    { background: linear-gradient(to right, #9ca3af, #6b7280); }
.thermo-label { margin-top:12px; font-weight:600; font-size:1.2rem; }

/* Table */
.data-table { width:100%; max-width:1000px; margin:25px auto; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 3px 15px rgba(0,0,0,0.05); }
.data-table th { background:#2563eb; color:#fff; padding:12px; text-align:left; }
.data-table td { padding:10px; border-bottom:1px solid #eee; }
.data-table tr:hover td { background:#f3f4f6; }
.status-label { padding:4px 10px; border-radius:6px; font-weight:600; font-size:0.85rem; }
.status-label.temp-optimal  { background:#3b82f6;color:#fff; }
.status-label.temp-warning  { background:#facc15;color:#000; }
.status-label.temp-critical { background:#ef4444;color:#fff; }
.status-label.temp-below    { background:#9ca3af;color:#fff; }

/* Chart */
.chart-container { width:100%; max-width:1000px; margin:30px auto; background:#fff; padding:15px; border-radius:12px; box-shadow:0 3px 15px rgba(0,0,0,0.05); }

/* Legend */
.legend-inline { display:flex; justify-content:center; gap:20px; margin-top:10px; }
.legend-inline .legend-item { display:flex; align-items:center; gap:6px; font-weight:500; font-size:0.85rem; }
.legend-inline .temp-badge { width:14px; height:14px; border-radius:50%; display:inline-block; }
.temp-badge.temp-optimal  { background:#3b82f6; }
.temp-badge.temp-warning  { background:#facc15; }
.temp-badge.temp-critical { background:#ef4444; }
.temp-badge.temp-below    { background:#9ca3af; }

</style>
</head>
<body>
<?php include 'sideabr.php'; include 'notif_bell.php'; ?>

<div class="main">
<?php include 'topnav.php'; ?>
<!-- ALERT -->
<div class="alert-box <?= e($alert_class) ?>">
    <?= e($alert_text) ?>
</div>

<!-- KPI CARDS -->
<div class="kpi-container">
    <div class="kpi"><i class="fas fa-thermometer-half"></i><span class="label">Latest</span><span class="value"><?= number_format($temp_level,2) ?>°C</span></div>
    <div class="kpi"><i class="fas fa-chart-line"></i><span class="label">Average</span><span class="value"><?= $avg_temp ?>°C</span></div>
    <div class="kpi"><i class="fas fa-arrow-up"></i><span class="label">Max</span><span class="value"><?= $max_temp ?>°C</span></div>
    <div class="kpi"><i class="fas fa-arrow-down"></i><span class="label">Min</span><span class="value"><?= $min_temp ?>°C</span></div>
</div>

<div class="thermo-card" style="max-width:500px; margin:25px auto;">
    <div style="display:flex; align-items:center; gap:12px;">
        <!-- Bar Background -->
        <div style="flex:1; height:30px; background:#e5e7eb; border-radius:15px; overflow:hidden; position:relative;">
            <!-- Fill -->
            <div class="thermo-fill <?= e($alert_class) ?>" 
                 style="width:<?= min(max($temp_level,0),100) ?>%; height:100%; transition: width 1s ease;">
            </div>
        </div>
        <!-- Label -->
        <div style="min-width:70px; text-align:center; font-weight:600; color:#1f2937;">
            <?= number_format($temp_level,2) ?>°C
        </div>
    </div>
    <div style="margin-top:6px; text-align:center;">
        <span class="status-label <?= e($alert_class) ?>"><?= e($status_label) ?></span>
    </div>
</div>


<div class="chart-container" style="height:350px;">
    <canvas id="tempChart" style="width:100%; height:100%;"></canvas>
    <div class="legend-inline">
        <div class="legend-item"><span class="temp-badge temp-below"></span> Below (0–39°C)</div>
        <div class="legend-item"><span class="temp-badge temp-optimal"></span> Optimal (40–59°C)</div>
        <div class="legend-item"><span class="temp-badge temp-warning"></span> Warning (60–64°C)</div>
        <div class="legend-item"><span class="temp-badge temp-critical"></span> Critical (65°C+)</div>
    </div>
</div>
<!-- HISTORY TABLE -->
<table class="data-table">
    <thead>
        <tr>
            <th>ID</th><th>Sensor</th><th>Temperature</th><th>Range</th><th>Status</th><th>Recorded At</th>
        </tr>
    </thead>
    <tbody>
    <?php if($historyRes && $historyRes->num_rows): 
        while($row=$historyRes->fetch_assoc()):
            $t = is_null($row['Temp_Ave']) ? null : floatval($row['Temp_Ave']);
            if(is_null($t)){$cls="temp-below";$st="Sensor Error";}
            elseif($t>=65){$cls="temp-critical";$st="Critical";}
            elseif($t>60){$cls="temp-warning";$st="Warning";}
            elseif($t>=40){$cls="temp-optimal";$st="Optimal";}
            else{$cls="temp-below";$st="Below";}
    ?>
        <tr>
            <td><?= e($row['Temp_Id']) ?></td>
            <td><?= e($row['Temp_Sensor']) ?></td>
            <td><?= is_null($t)?'N/A':number_format($t,2) ?>°C</td>
            <td><?= e($row['Temp_Range']) ?></td>
            <td><span class="status-label <?= e($cls) ?>"><?= e($st) ?></span></td>
            <td><?= e($row['created_at']) ?></td>
        </tr>
    <?php endwhile; else: ?>
        <tr><td colspan="6" style="text-align:center;">No records available.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

</div>

<script>
const ctx = document.getElementById('tempChart').getContext('2d');
new Chart(ctx,{
    type:'line',
    data:{
        labels: <?= json_encode($temp_labels) ?>,
        datasets:[{
            label:'Temperature (°C)',
            data: <?= json_encode($temp_data) ?>,
            borderColor:'#3b82f6',
            backgroundColor:'rgba(59,130,246,0.2)',
            tension:0.4,
            fill:true,
            pointRadius:4
        }]
    },
    options:{
        responsive:true,
        maintainAspectRatio:false, // important for fixed container height
        layout:{ padding: { top: 10, bottom: 10 } },
        scales:{
            y:{ suggestedMin:0, suggestedMax:100 },
            x:{ ticks:{ maxRotation:45, minRotation:0 } }
        }
    }
});
</script>

</body>
</html>
