<?php
include('db.php');

// ================= FETCH LATEST 10 GAS READINGS =================
$result = $conn->query("SELECT * FROM gas ORDER BY Gas_Id DESC LIMIT 10");
$gas_data = [];
$gas_labels = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $gas_data[] = floatval($row['Gas_Lvl']);
        $gas_labels[] = $row['created_at'] ?? 'N/A';
    }
}

// Latest Gas Level
$latest_gas = $gas_data[0] ?? 0.0;

// Determine Status
if ($latest_gas >= 65) {
    $status = "Critical";
    $desc = "🔥 Mixer Activated!";
    $color = "#ef4444"; // red
} elseif ($latest_gas >= 60) {
    $status = "Warning";
    $desc = "⚠️ Gas Level Rising!";
    $color = "#facc15"; // yellow
} elseif ($latest_gas >= 40) {
    $status = "Optimal";
    $desc = "✅ Gas Level Normal";
    $color = "#16a34a"; // green
} else {
    $status = "Below Range";
    $desc = "🌡️ Gas Level Below Range";
    $color = "#9ca3af"; // gray
}

// KPI calculation
$avg_gas = round(array_sum($gas_data)/count($gas_data), 2);
$max_gas = max($gas_data);
$min_gas = min($gas_data);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gas Dashboard | WattAWaste Bin</title>
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://kit.fontawesome.com/a2e0e6ad65.js" crossorigin="anonymous"></script>
<style>
body { background:#f5f7f5; font-family:Arial,sans-serif; }
.main { padding:30px; }
h2 { color:#2f5233; margin-bottom:20px; text-align:center; }

.flex { display:flex; gap:20px; flex-wrap:wrap; margin-bottom:15px; align-items:flex-start; }
.flex .card { flex:1 1 300px; max-width:600px; text-align:center; }

.card { background:#fff; border-radius:15px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.15); }
.card h3 { margin-bottom:10px; font-size:20px; }

.status-box { border-left:8px solid <?= $color ?>; }
.status-box p { font-size:18px; margin:8px 0; }

/* KPI */
.kpi-container { display:flex; justify-content:center; gap:30px; margin-bottom:20px; }
.kpi { flex:1; background:#fff; border-radius:12px; padding:15px; box-shadow:0 2px 5px rgba(0,0,0,0.1); text-align:center; }
.kpi i { font-size:24px; margin-bottom:5px; display:block; color:#333; }
.kpi span { display:block; font-size:18px; font-weight:bold; }

/* History Table */
.history table { width:100%; border-collapse:collapse; }
.history th, .history td { border-bottom:1px solid #ddd; padding:8px; text-align:left; font-size:15px; }

/* Legend */
.gas-legend { 
    margin:15px auto; 
    background:#fff; 
    padding:10px 18px; 
    border-radius:10px; 
    border:1px solid #ddd; 
    max-width:900px; 
    display:flex; 
    gap:15px; 
    justify-content:center; 
}
.badge { 
    width:14px; 
    height:14px; 
    display:inline-block; 
    border-radius:50%; 
    margin-right:5px; 
}
.badge.legend-good { background:#3b82f6; }
.badge.legend-optimal { background:#16a34a; }
.badge.legend-warning { background:#facc15; }
.badge.legend-critical { background:#ef4444; }
.badge.legend-below { background:#9ca3af; }

/* Chart container */
.chart-container { position:relative; width:100%; height:250px; }
</style>
</head>
<body>

<?php include 'sideabr.php'; ?>
<div class="main">
<?php include 'topnav.php'; ?>

<h2>💨 Gas Dashboard</h2>

<div class="flex">
   <div class="card status-box">
    <h3>Status: <?= $status ?></h3>
    <p>Latest Gas Level:</p>
    <div style="display:flex; align-items:center; justify-content:center; gap:20px; margin-top:15px;">
        <!-- Gas Gauge Circle -->
        <div style="
            width:80px; height:80px;
            border-radius:50%;
            background:<?= $color ?>;
            display:flex; align-items:center; justify-content:center;
            font-weight:bold; font-size:20px; color:white;
            box-shadow:0 0 12px <?= $color ?>66;
        ">
            <?= $latest_gas ?> ppm
        </div>

        <!-- Description -->
        <div style="font-size:16px; font-weight:bold; color:<?= $color ?>;">
            <?= $desc ?>
        </div>
    </div>
</div>

<!-- Gas Trend Chart -->
<div class="card">
    <h3>Gas Trend</h3>
    <div class="chart-container">
        <canvas id="gasChart"></canvas>
    </div>
</div>
</div>

<!-- KPI Cards -->
<div class="kpi-container">
    <div class="kpi" style="color:#16a34a;"><i class="fas fa-calculator"></i><span>Avg</span><span><?= $avg_gas ?></span></div>
    <div class="kpi" style="color:#ef4444;"><i class="fas fa-arrow-up"></i><span>Max</span><span><?= $max_gas ?></span></div>
    <div class="kpi" style="color:#f59e0b;"><i class="fas fa-arrow-down"></i><span>Min</span><span><?= $min_gas ?></span></div>
</div>

<!-- History Table -->
<div class="card history">
    <h3>Gas Level History</h3>
    <table>
        <thead>
            <tr><th>ID</th><th>Gas Level</th><th>Status</th><th>Recorded At</th></tr>
        </thead>
        <tbody>
        <?php
        $historyRes = $conn->query("SELECT * FROM gas ORDER BY Gas_Id DESC LIMIT 10");
        while($row = $historyRes->fetch_assoc()):
            $lvl = floatval($row['Gas_Lvl']);
            if($lvl >= 65){$cls="legend-critical"; $st="Critical";}
            elseif($lvl >= 60){$cls="legend-warning"; $st="Warning";}
            elseif($lvl >= 40){$cls="legend-optimal"; $st="Optimal";}
            elseif($lvl >= 35){$cls="legend-good"; $st="Good";}
            else{$cls="legend-below"; $st="Below Range";}
        ?>
            <tr>
                <td><?= $row['Gas_Id'] ?></td>
                <td><?= number_format($lvl,2) ?> ppm</td>
                <td><span class="badge <?= $cls ?>"><?= $st ?></span></td>
                <td><?= $row['created_at'] ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="gas-legend">
    <span><span class="badge legend-good"></span> Good (35-50 ppm)</span>
    <span><span class="badge legend-optimal"></span> Optimal (40-60 ppm)</span>
    <span><span class="badge legend-warning"></span> Warning (60-70 ppm)</span>
    <span><span class="badge legend-critical"></span> Critical (70+ ppm)</span>
    <span><span class="badge legend-below"></span> Below Range (&lt;35 ppm)</span>
</div>

<script>
const gasData = <?= json_encode(array_reverse($gas_data)) ?>;
const gasLabels = <?= json_encode(array_reverse($gas_labels)) ?>;

new Chart(document.getElementById('gasChart'), {
    type:'line',
    data:{
        labels: gasLabels,
        datasets:[{
            label:'Gas Level (ppm)',
            data: gasData,
            borderColor:'#4CAF50',
            backgroundColor:'rgba(76,175,80,0.2)',
            tension:0.4,
            fill:true,
            pointRadius:4
        }]
    },
    options:{
        responsive:true,
        maintainAspectRatio:true,
        scales:{ y:{ suggestedMin:0, suggestedMax:100 } }
    }
});
</script>

</div>
</body>
</html>
