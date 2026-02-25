<?php
session_start();
include('db.php');

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.html");
    exit();
}

// Helper to fetch single row
function fetch_row($conn, $sql) {
    $res = mysqli_query($conn, $sql);
    if ($res && mysqli_num_rows($res) > 0) return mysqli_fetch_assoc($res);
    return null;
}

// ===== SAMPLE DATA FETCH =====
// Waste
$latestWeightRow = fetch_row($conn, "SELECT * FROM weights ORDER BY Weight_T_D DESC LIMIT 1");
$weight_lvl = $latestWeightRow ? floatval($latestWeightRow['Weight_Lvl']) : 0;
$weight_capacity = $latestWeightRow ? floatval($latestWeightRow['Weight_Capacity']) : 1;
$w_percent = round(($weight_lvl / $weight_capacity) * 100, 1);
$dailyWaste = $weight_lvl;

// Fertilizer
$sample_fertilizer_consumed = 400;
$fertilizerConsumed = $sample_fertilizer_consumed;
$fertilizerRemainingPercent = $w_percent;

// Sensors
function fetchGraphData($conn, $table, $col){
    $res = mysqli_query($conn, "SELECT $col, created_at FROM $table ORDER BY created_at ASC");
    $data = [];
    if($res && mysqli_num_rows($res) > 0){
        while($row = mysqli_fetch_assoc($res)){
            $data[] = $row;
        }
    }
    return $data;
}
$temperature = fetchGraphData($conn, "temperatures", "Temp_Ave");
$humidity = fetchGraphData($conn, "humidity", "Humid_Lvl");
$gas = fetchGraphData($conn, "gas", "Gas_Lvl");

$latestTemp = end($temperature)['Temp_Ave'] ?? 'N/A';
$latestGas = end($gas)['Gas_Lvl'] ?? 'N/A';
$latestHum = end($humidity)['Humid_Lvl'] ?? 'N/A';

// Users
$totalUsers = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { font-family: Arial; margin:0; background:#f5f7f7; }
.container { max-width: 1200px; margin:auto; padding:20px; }
.top-cards, .graph-row, .bottom-row { display:flex; gap:20px; flex-wrap: wrap; }
.card, .graph-card, .bottom-card { background:#fff; padding:20px; border-radius:12px; box-shadow:0 3px 8px rgba(0,0,0,0.1); text-align:center; }
.card { flex:1 1 200px; }
.graph-card, .bottom-card { flex:1 1 300px; }
h2 { margin-bottom:20px; text-align:center; }
.chart-container { width:100%; height:220px; }
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>
<div class="main">
<?php include 'topnav.php'; ?>
<div class="container">

<h2>Admin Dashboard</h2>

<!-- Top Summary Cards -->
<div class="top-cards">
    <div class="card"><h3>Total Users</h3><div style="font-size:30px;font-weight:bold;"><?= $totalUsers ?></div></div>
    <div class="card"><h3>Daily Waste (kg)</h3><div style="font-size:30px;font-weight:bold;"><?= $dailyWaste ?></div></div>
    <div class="card"><h3>Fertilizer Consumed (kg)</h3><div style="font-size:30px;font-weight:bold;"><?= $fertilizerConsumed ?></div></div>
    <div class="card"><h3>Fertilizer Remaining (%)</h3><div style="font-size:30px;font-weight:bold;"><?= $fertilizerRemainingPercent ?>%</div></div>
</div>

<!-- Graphs Row -->
<div class="graph-row">
    <div class="graph-card">
        <h4>Temperature Trend (°C)</h4>
        <canvas id="tempChart" class="chart-container"></canvas>
    </div>
    <div class="graph-card">
        <h4>Humidity Trend (%)</h4>
        <canvas id="humChart" class="chart-container"></canvas>
    </div>
    <div class="graph-card">
        <h4>Gas Level Trend (ppm)</h4>
        <canvas id="gasChart" class="chart-container"></canvas>
    </div>
</div>

<!-- Bottom Row Current Values -->
<div class="bottom-row">
    <div class="bottom-card"><h4>Current Temperature</h4><div style="font-size:28px;font-weight:bold;"><?= $latestTemp ?> °C</div></div>
    <div class="bottom-card"><h4>Current Humidity</h4><div style="font-size:28px;font-weight:bold;"><?= $latestHum ?>%</div></div>
    <div class="bottom-card"><h4>Current Gas</h4><div style="font-size:28px;font-weight:bold;"><?= $latestGas ?> ppm</div></div>
</div>

</div>
</div>

<script>
// Charts
const tempData = <?= json_encode(array_column($temperature,'Temp_Ave')) ?>;
const tempLabels = <?= json_encode(array_column($temperature,'created_at')) ?>;
const humData = <?= json_encode(array_column($humidity,'Humid_Lvl')) ?>;
const humLabels = <?= json_encode(array_column($humidity,'created_at')) ?>;
const gasData = <?= json_encode(array_column($gas,'Gas_Lvl')) ?>;
const gasLabels = <?= json_encode(array_column($gas,'created_at')) ?>;

new Chart(document.getElementById('tempChart'), {
    type:'line',
    data:{labels:tempLabels,datasets:[{label:'Temp °C',data:tempData,borderColor:'#ff5733',backgroundColor:'rgba(255,87,51,0.2)',fill:true,tension:0.3}]},
    options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}
});
new Chart(document.getElementById('humChart'), {
    type:'line',
    data:{labels:humLabels,datasets:[{label:'Humidity %',data:humData,borderColor:'#28a745',backgroundColor:'rgba(40,167,69,0.2)',fill:true,tension:0.3}]},
    options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}
});
new Chart(document.getElementById('gasChart'), {
    type:'bar',
    data:{labels:gasLabels,datasets:[{label:'Gas ppm',data:gasData,backgroundColor:'#2e86de'}]},
    options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}
});
</script>

</body>
</html>
