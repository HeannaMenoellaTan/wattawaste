<?php
include('db.php');

// ==== Get Latest Weight Data ====
$query = "SELECT * FROM weights ORDER BY Weight_Id DESC LIMIT 1";
$result = $conn->query($query);
$row = $result->fetch_assoc();

$weight = $row['Weight_Lvl'] ?? 0;
$weightCapacity = $row['Weight_Capacity'] ?? 100;
$weightStatus = "Normal";

// Determine weight status
if ($weight >= 80) {
    $weightStatus = "Overload";
    $statusColor = "#ef4444"; // red
    $statusIcon = "fa-exclamation-triangle";
} elseif ($weight >= 60) {
    $weightStatus = "High";
    $statusColor = "#f59e0b"; // yellow
    $statusIcon = "fa-exclamation-circle";
} else {
    $weightStatus = "Normal";
    $statusColor = "#22c55e"; // green
    $statusIcon = "fa-check-circle";
}

// ==== Fetch Weight History ====
$queryHistory = "SELECT * FROM weights ORDER BY Weight_Id DESC LIMIT 10";
$resultHistory = $conn->query($queryHistory);
$historyData = [];
while ($r = $resultHistory->fetch_assoc()) {
    $historyData[] = $r;
}

// ==== Calculate KPI ====
$values = array_column($historyData, 'Weight_Lvl');
$avgWeight = round(array_sum($values)/count($values),2);
$maxWeight = max($values);
$minWeight = min($values);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Weight Dashboard</title>
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://kit.fontawesome.com/a2e0e6ad65.js" crossorigin="anonymous"></script>
<style>
body { font-family: Arial, sans-serif; background:#f4f7f6; color:#2f5233; }
.main { padding:20px; }
h2 { text-align:center; margin-bottom:20px; }
.top-row { display:flex; gap:15px; flex-wrap:wrap; justify-content:center; }
.card { background:#fff; border-radius:12px; padding:15px; box-shadow:0 3px 6px rgba(0,0,0,0.1); text-align:center; }
.current-weight { flex:1; min-width:220px; max-width:260px; }
.graph-card { flex:2; min-width:300px; }
.progress-bar { background:#eee; border-radius:6px; overflow:hidden; height:8px; margin:5px 0; }
.progress { height:8px; border-radius:6px; }
.kpi-row { display:flex; justify-content:center; gap:30px; margin:15px 0; font-size:14px; }
.kpi { text-align:center; }
.data-table { width:100%; border-collapse:collapse; margin-top:20px; }
.data-table th, .data-table td { border:1px solid #ccc; padding:8px; text-align:center; }
.data-table th { background:#e5f1e5; }
.status-normal { color:#22c55e; }
.status-high { color:#f59e0b; }
.status-overload { color:#ef4444; }
</style>
</head>
<body>

<?php include 'sideabr.php'; ?>

<div class="main">


<?php include 'topnav.php'; ?>
<h2>⚖️ Weight Dashboard</h2>
<div class="top-row">
    <!-- Current Weight -->
    <div class="card current-weight">
        <h3><i class="fas fa-weight-hanging"></i> Current Weight</h3>
        <p style="font-size:22px; font-weight:bold; margin:5px 0;"><?= $weight ?> kg</p>
        <p style="color:#555; font-size:14px; margin:2px 0;">Capacity: <?= $weightCapacity ?> kg</p>
        <div class="progress-bar">
            <div class="progress" style="width:<?= ($weight/$weightCapacity)*100 ?>%; background:<?= $statusColor ?>;"></div>
        </div>
        <div style="font-size:14px; margin-top:4px; color:<?= $statusColor ?>;">
            <i class="fas <?= $statusIcon ?>"></i> <?= $weightStatus ?>
        </div>
    </div>

    <!-- Weight Graph -->
    <div class="card graph-card">
        <h3><i class="fas fa-chart-line"></i> Weight History</h3>
        <canvas id="weightChart" style="height:180px;"></canvas>
    </div>
</div>
<div class="kpi-row" style="gap:40px; margin:20px 0; font-size:18px; justify-content:center;">
    <div class="kpi" style="display:flex; flex-direction:column; align-items:center;">
        <i class="fas fa-calculator" style="font-size:24px; color:#22c55e;"></i>
        <strong>Average</strong>
        <span style="font-size:20px; font-weight:bold; color:#22c55e;"><?= $avgWeight ?> kg</span>
    </div>
    <div class="kpi" style="display:flex; flex-direction:column; align-items:center;">
        <i class="fas fa-arrow-up" style="font-size:24px; color:#ef4444;"></i>
        <strong>Maximum</strong>
        <span style="font-size:20px; font-weight:bold; color:#ef4444;"><?= $maxWeight ?> kg</span>
    </div>
    <div class="kpi" style="display:flex; flex-direction:column; align-items:center;">
        <i class="fas fa-arrow-down" style="font-size:24px; color:#f59e0b;"></i>
        <strong>Minimum</strong>
        <span style="font-size:20px; font-weight:bold; color:#f59e0b;"><?= $minWeight ?> kg</span>
    </div>
</div>

<h3>Weight History</h3>
<table class="data-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Weight (kg)</th>
            <th>Status</th>
            <th>Timestamp</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($historyData as $h): 
        $status = $h['Weight_Status'] ?? 'Normal';
        $statusClass = strtolower($status);
    ?>
        <tr class="status-<?= $statusClass ?>">
            <td><?= $h['Weight_Id'] ?></td>
            <td><?= $h['Weight_Lvl'] ?></td>
            <td><?= $status ?></td>
            <td><?= $h['created_at'] ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</div>

<script>
const historyData = <?= json_encode(array_reverse($historyData)) ?>;
const ctx = document.getElementById('weightChart').getContext('2d');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: historyData.map(d => new Date(d.created_at).toLocaleTimeString()),
        datasets: [{
            label: 'Weight (kg)',
            data: historyData.map(d => d.Weight_Lvl),
            borderColor: '#22c55e',
            backgroundColor: 'rgba(34,197,94,0.2)',
            fill:true,
            tension:0.4,
            pointRadius:3
        }]
    },
    options: {
        responsive:true,
        scales: { y:{ beginAtZero:true } },
        plugins: { legend:{ display:false } }
    }
});
</script>

</body>
</html>
