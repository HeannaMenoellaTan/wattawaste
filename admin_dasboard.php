<?php
session_start();
include('db.php');

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

if (!function_exists('fetch_row')) {
    function fetch_row($conn, $sql) {
        $res = mysqli_query($conn, $sql);
        if ($res && mysqli_num_rows($res) > 0) return mysqli_fetch_assoc($res);
        return null;
    }
}

// SAMPLE VALUES
$sample_fertilizer_consumed = 400;

$latestWeightRow = fetch_row($conn, "SELECT * FROM weights ORDER BY Weight_T_D DESC LIMIT 1");
$weight_lvl = $latestWeightRow ? floatval($latestWeightRow['Weight_Lvl']) : 0;
$weight_capacity = $latestWeightRow ? floatval($latestWeightRow['Weight_Capacity']) : 0;
$w_percent = $weight_capacity > 0 ? round(($weight_lvl / $weight_capacity) * 100, 1) : 0;
$dailyWaste = $weight_lvl;

$fertilizerConsumed = $sample_fertilizer_consumed;
$fertilizerRemainingPercent = $w_percent;

$latestTemp = fetch_row($conn, "SELECT Temp_Ave FROM temperatures ORDER BY created_at DESC LIMIT 1");
$currentTemp = $latestTemp ? $latestTemp['Temp_Ave'] : 'N/A';

$latestGas = fetch_row($conn, "SELECT Gas_Lvl FROM gas ORDER BY created_at DESC LIMIT 1");
$currentGas = $latestGas ? $latestGas['Gas_Lvl'] : 'N/A';

$latestPH = fetch_row($conn, "SELECT pH_Value FROM ph ORDER BY created_at DESC LIMIT 1");
$currentPH = $latestPH ? $latestPH['pH_Value'] : 'N/A';

// ===== FETCH GRAPH DATA =====
function fetchGraphData($conn, $table, $valueCol) {
    $res = mysqli_query($conn, "SELECT $valueCol, created_at FROM $table WHERE $valueCol IS NOT NULL ORDER BY created_at ASC");
    $data = [];
    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $data[] = $row;
        }
    }
    return $data;
}

$temperature = fetchGraphData($conn, "temperatures", "Temp_Ave");
$humidity    = fetchGraphData($conn, "humidity", "Humid_Lvl");
$gas         = fetchGraphData($conn, "gas", "Gas_Lvl");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php  
include_once 'notif_bell.php';
?>
<style>
body {
    font-family: Arial;
    background:#f5f7f7;
    margin:0;
}
.container {
    width: 1000px;
    margin:auto;
    padding:20px;
}
.top-cards {
    display:flex;
    gap:20px;
}
.card {
    background:#fff;
    width:230px;
    border-radius:10px;
    padding:20px;
    text-align:center;
    box-shadow:0 2px 6px rgba(0,0,0,0.1);
}
.graph-row {
    display:flex;
    gap:20px;
    margin-top:20px;
}
.graph-card {
    background:#fff;
    width:300px;
    height:220px;
    padding:10px;
    border-radius:10px;
    box-shadow:0 2px 6px rgba(0,0,0,0.1);
}
.bottom-row {
    display:flex;
    gap:20px;
    margin-top:25px;
}
.bottom-card {
    background:#fff;
    width:300px;
    padding:20px;
    border-radius:10px;
    box-shadow:0 2px 6px rgba(0,0,0,0.1);
}
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>
<div class="main">
<?php include 'topnav.php';?>

<div class="container">

<h2 style="margin-bottom:20px;">Admin Dashboard</h2>

<div class="top-cards">
    <div class="card">
        <h3>Total Users</h3>
        <div style="font-size:30px;font-weight:bold;"><?= mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users")); ?></div>
    </div>
    <div class="card">
        <h3>Daily Waste Weight (kg)</h3>
        <div style="font-size:30px;font-weight:bold;"><?= $dailyWaste ?></div>
    </div>
    <div class="card">
        <h3>Fertilizer Consumed</h3>
        <div style="font-size:30px;font-weight:bold;">400 kg</div>
    </div>
    <div class="card">
        <h3>Fertilizer Capacity Remaining</h3>
        <div style="font-size:30px;font-weight:bold;"><?= $fertilizerRemainingPercent ?>%</div>
    </div>
</div>

<div class="graph-row">
    <div class="graph-card"><canvas id="tempChart"></canvas></div>
    <div class="graph-card"><canvas id="humChart"></canvas></div>
    <div class="graph-card"><canvas id="gasChart"></canvas></div>
</div>

<div class="bottom-row">
    <div class="bottom-card">
        <h3>Current Temperature</h3>
        <div style="font-size:28px;font-weight:bold;"><?= $currentTemp ?> °C</div>
    </div>
    <div class="bottom-card">
        <h3>Current Gas</h3>
        <div style="font-size:28px;font-weight:bold;"><?= $currentGas ?> ppm</div>
    </div>
    <div class="bottom-card">
        <h3>Current pH</h3>
        <div style="font-size:28px;font-weight:bold;"><?= $currentPH ?></div>
    </div>
</div>

</div>

<script>
// ===== CHARTS =====
new Chart(document.getElementById('tempChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($temperature, 'created_at')); ?>,
        datasets: [{
            label: 'Temperature (°C)',
            data: <?= json_encode(array_column($temperature, 'Temp_Ave')); ?>,
            borderColor: '#ff5733',
            backgroundColor: 'rgba(255,87,51,0.2)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { 
            x: { ticks: { color:'#2f5233' } }, 
            y: { beginAtZero: true, ticks: { color:'#2f5233' } } 
        }
    }
});

new Chart(document.getElementById('humChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($humidity, 'created_at')); ?>,
        datasets: [{
            label: 'Humidity (%)',
            data: <?= json_encode(array_column($humidity, 'Humid_Lvl')); ?>,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40,167,69,0.2)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { 
            x: { ticks: { color:'#2f5233' } }, 
            y: { beginAtZero: true, ticks: { color:'#2f5233' } } 
        }
    }
});

new Chart(document.getElementById('gasChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($gas, 'created_at')); ?>,
        datasets: [{
            label: 'Gas Level (ppm)',
            data: <?= json_encode(array_column($gas, 'Gas_Lvl')); ?>,
            backgroundColor: '#2e86de'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { 
            x: { ticks: { color:'#2f5233' } }, 
            y: { beginAtZero: true, ticks: { color:'#2f5233' } } 
        }
    }
});
</script>

</body>
</html>
