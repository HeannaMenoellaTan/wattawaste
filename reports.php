<?php
session_start();
include('db.php');

// Restrict access
if (!isset($_SESSION['username'])) {
  header("Location: login.html");
  exit();
}

// Get date range
$startDate = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-6 days'));
$endDate = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');

// Fetch latest temperature & humidity
$tempQuery = "SELECT Temp_Ave FROM temperatures ORDER BY Temp_id DESC LIMIT 1";
$humidityQuery = "SELECT Humid_Lvl FROM humidity ORDER BY Humid_id DESC LIMIT 1";

$tempResult = $conn->query($tempQuery)->fetch_assoc();
$humidityResult = $conn->query($humidityQuery)->fetch_assoc();

$currentTemp = $tempResult ? $tempResult['Temp_Ave'] : 0;
$currentHumidity = $humidityResult ? $humidityResult['Humid_Lvl'] : 0;

// Fetch chart data for selected date range
function getChartData($conn, $table, $column, $start, $end) {
  $query = "
    SELECT DATE(created_at) AS date, AVG($column) AS value
    FROM $table
    WHERE DATE(created_at) BETWEEN '$start' AND '$end'
    GROUP BY DATE(created_at)
  ";
  $result = $conn->query($query);
  $data = [];
  while ($row = $result->fetch_assoc()) {
    $data[$row['date']] = $row['value'];
  }
  return $data;
}

$weightsData = getChartData($conn, 'weights', 'Weight_Lvl', $startDate, $endDate);
$tempData = getChartData($conn, 'temperatures', 'Temp_Ave', $startDate, $endDate);
$humidityData = getChartData($conn, 'humidity', 'Humid_Lvl', $startDate, $endDate);
$gasData = getChartData($conn, 'gas', 'Gas_Lvl', $startDate, $endDate);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports | WattAWaste</title>
   <link rel="stylesheet" href="assets/css/style.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body {
  background-color: #f9f9f9;
  font-family: 'Poppins', sans-serif;
}

.main-content {
  margin-left: 260px;
  padding: 40px;
}
.chart-card {
  background: white;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 3px 6px rgba(0,0,0,0.1);
  margin-bottom: 20px;
}
.stats-box {
  display: flex;
  justify-content: space-around;
  align-items: center;
  background: white;
  border-radius: 10px;
  box-shadow: 0 3px 6px rgba(0,0,0,0.1);
  padding: 20px;
  height: 120px;
}
.stats-item {
  text-align: center;
}
.stats-item h5 {
  font-size: 14px;
  color: #555;
}
.stats-item p {
  font-size: 26px;
  font-weight: 600;
  margin: 0;
}
.date-filter {
  background: white;
  border-radius: 10px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
  padding: 10px 15px;
  width: fit-content;
}
</style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
        <img src="images/qculogo.png" alt="logo" width="80">
        <h3>WattAWaste Admin</h3>
           <link rel="stylesheet" href="assets/css/style.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
      

         <ul>
      <li class="active"><a href="admin_dasboard.php">Dashboard</a></li>
      <li><a href="Users.php">Users</a></li>
      <li><a href="reports.php">Reports</a></li>
      <li><a href="settings.php">Settings</a></li>
      <li><a href="login.html">Log Out</a></li>
    </ul>
  </div>

<!-- Main Content -->
<div class="main-content">
  <h2 class="fw-bold mb-4">Reports</h2>

  <!-- Date Filter -->
  <form method="GET" class="mb-4">
    <div class="date-filter">
      <input type="date" name="start" value="<?= $startDate ?>"> -
      <input type="date" name="end" value="<?= $endDate ?>">
      <button class="btn btn-sm btn-success ms-2">Apply</button>
    </div>
  </form>

  <!-- Charts -->
  <div class="row">
    <div class="col-md-8">
      <div class="chart-card">
        <h5>Waste Processed</h5>
        <canvas id="weightsChart" height="100"></canvas>

      </div>
    </div>
    <div class="col-md-4">
      <div class="stats-box">
        <div class="stats-item">
          <h5>Current Temperature</h5>
          <p><?= $currentTemp ?> °C</p>
        </div>
        <div class="stats-item">
          <h5>Current Humidity</h5>
          <p><?= $currentHumidity ?> %</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Smaller charts -->
  <div class="row mt-4">
    <div class="col-md-4">
      <div class="chart-card"><h6>Temperature</h6><canvas id="tempChart"></canvas></div>
    </div>
    <div class="col-md-4">
      <div class="chart-card"><h6>Humidity</h6><canvas id="humidityChart"></canvas></div>
    </div>
    <div class="col-md-4">
      <div class="chart-card"><h6>Gas</h6><canvas id="gasChart"></canvas></div>
    </div>
  </div>
</div>

<script>
// Chart Data from PHP
const labels = <?= json_encode(array_keys($weightsData)) ?>;

const weightsData = <?= json_encode(array_values($weightsData)) ?>;
const tempData = <?= json_encode(array_values($tempData)) ?>;
const humidityData = <?= json_encode(array_values($humidityData)) ?>;
const gasData = <?= json_encode(array_values($gasData)) ?>;

// Waste Bar Chart
new Chart(document.getElementById('weightsChart'), {
  type: 'bar',
  data: {
    labels: labels,
    datasets: [{
      label: 'Waste Processed',
      data: weightsData,
      backgroundColor: ['#52734D', '#9CCC65']
    }]
  },
  options: { responsive: true, scales: { y: { beginAtZero: true } } }
});

// Temperature Line Chart
new Chart(document.getElementById('tempChart'), {
  type: 'line',
  data: {
    labels: labels,
    datasets: [{
      label: 'Temperature (°C)',
      data: tempData,
      borderColor: '#e74c3c',
      backgroundColor: 'rgba(231, 76, 60, 0.2)',
      tension: 0.4,
      fill: true
    }]
  },
  options: { responsive: true, scales: { y: { beginAtZero: true } } }
});

// Humidity Line Chart
new Chart(document.getElementById('humidityChart'), {
  type: 'line',
  data: {
    labels: labels,
    datasets: [{
      label: 'Humidity (%)',
      data: humidityData,
      borderColor: '#3498db',
      backgroundColor: 'rgba(52, 152, 219, 0.2)',
      tension: 0.4,
      fill: true
    }]
  },
  options: { responsive: true, scales: { y: { beginAtZero: true } } }
});

// Gas Bar Chart
new Chart(document.getElementById('gasChart'), {
  type: 'bar',
  data: {
    labels: labels,
    datasets: [{
      label: 'Gas',
      data: gasData,
      backgroundColor: ['#2E7D32', '#81C784']
    }]
  },
  options: { responsive: true, scales: { y: { beginAtZero: true } } }
});
</script>

</body>
</html>
