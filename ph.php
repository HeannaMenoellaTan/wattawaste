<?php
include('db.php');

// Fetch latest 10 pH readings
$query = "SELECT * FROM ph ORDER BY pH_Id DESC LIMIT 10";
$result = $conn->query($query);

$ph_data = [];
$ph_labels = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ph_data[] = $row['pH_Value'];
        $ph_labels[] = $row['created_at'] ?? 'N/A';
    }
}

// Latest pH
$latest_ph = $ph_data[0] ?? 7.0;

// Determine pH status and colors
if ($latest_ph < 6) {
    $status = "Too Acidic";
    $desc = "Add brown/lime materials";
    $popup_color = "#e74c3c"; // red
    $recommendation = "Add brown/lime materials";
} elseif ($latest_ph > 8) {
    $status = "Too Alkaline";
    $desc = "Add green/acidic materials";
    $popup_color = "#3498db"; // blue
    $recommendation = "Add green/acidic materials";
} else {
    $status = "Healthy";
    $desc = "Compost is optimal";
    $popup_color = "#f5c542"; // yellow
    $recommendation = "Compost is healthy, monitor daily";
}

// Calculate KPI
$avg_ph = round(array_sum($ph_data) / count($ph_data), 2);
$max_ph = max($ph_data);
$min_ph = min($ph_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>pH Dashboard | WattAWaste Bin</title>
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://kit.fontawesome.com/a2e0e6ad65.js" crossorigin="anonymous"></script>
<style>
body { background: #f5f7f5; font-family: Arial, sans-serif; }
.main { padding: 30px; }
h2 { color:#2f5233; margin-bottom:20px; text-align:center; }

.flex { 
    display: flex; 
    gap: 20px; 
    flex-wrap: wrap; 
    margin-bottom: 15px; 
    align-items: flex-start; 
}

.flex .card { 
    flex: none; 
    width: 600px; 
    text-align: center;
}

#phChart { 
    height: 150px !important;
}
.card { background:#fff; border-radius:15px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.15); text-align:center; }

.card h3 { margin-bottom:10px; font-size:20px; }

.status-box { border-left: 8px solid <?= $popup_color ?>; }

.status-box p { font-size:18px; margin:8px 0; }

.kpi-container { display:flex; justify-content:center; gap:30px; margin-bottom:20px; }
.kpi { flex:1; background:#fff; border-radius:12px; padding:15px; box-shadow:0 2px 5px rgba(0,0,0,0.1); text-align:center; }
.kpi i { font-size:24px; margin-bottom:5px; display:block; color:#333; }
.kpi span { display:block; font-size:18px; font-weight:bold; }

.history table { width:100%; border-collapse:collapse; }
.history th, .history td { border-bottom:1px solid #ddd; padding:8px; text-align:left; font-size:15px; }

.legend { display:flex; justify-content:center; gap:20px; margin-top:10px; }
.legend div { display:flex; align-items:center; gap:5px; font-weight:bold; }
.legend span { display:inline-block; width:15px; height:15px; border-radius:3px; }

#recommendationPopup {
    position: fixed;
    top: 20px;
    right: 20px;
    background: <?= $popup_color ?>;
    color: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    z-index: 9999;
    display: none;
}
#recommendationPopup button {
    background: rgba(255,255,255,0.8);
    border: none;
    padding: 5px 10px;
    border-radius:5px;
    cursor: pointer;
    margin-top:10px;
}
@media (max-width:768px) {
    .flex { flex-direction:column; }
    .kpi-container { flex-direction:column; }
}
</style>
</head>
<body>

<?php include 'sideabr.php'; ?>

<div class="main">
  <?php include 'topnav.php'; ?>
<h2>🌿 pH Dashboard</h2>

<!-- Current pH & Chart -->
<div class="flex">
    <div class="card status-box">
        <h3>Status: <?= $status ?></h3>
        <p>Latest pH: <strong><?= $latest_ph ?></strong></p>
        <p><?= $desc ?></p>
    </div>

    <div class="card">
        <h3>pH Trend</h3>
        <canvas id="phChart" style="height:150px;"></canvas>
    </div>
</div>

<!-- KPI Cards -->
<div class="kpi-container">
    <div class="kpi" style="color:#6fc276;">
        <i class="fas fa-calculator"></i>
        <span>Avg</span>
        <span><?= $avg_ph ?></span>
    </div>
    <div class="kpi" style="color:#ef4444;">
        <i class="fas fa-arrow-up"></i>
        <span>Max</span>
        <span><?= $max_ph ?></span>
    </div>
    <div class="kpi" style="color:#f59e0b;">
        <i class="fas fa-arrow-down"></i>
        <span>Min</span>
        <span><?= $min_ph ?></span>
    </div>
</div>

<!-- History Table -->
<div class="card history">
    <h3>pH History</h3>
    <table>
        <thead>
            <tr><th>Date & Time</th><th>pH Value</th></tr>
        </thead>
        <tbody>
            <?php foreach(array_reverse($ph_labels) as $i => $label): 
                $value = $ph_data[$i];
                $row_color = ($value < 6) ? "#e74c3c" : (($value > 8) ? "#3498db" : "#f5c542");
            ?>
                <tr style="background:<?= $row_color ?>22"><!-- light color -->
                    <td><?= $label ?></td>
                    <td><?= $value ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Legend -->
    <div class="legend">
        <div><span style="background:#e74c3c;"></span> Too Acidic</div>
        <div><span style="background:#f5c542;"></span> Healthy</div>
        <div><span style="background:#3498db;"></span> Too Alkaline</div>
    </div>
</div>

<div id="recommendationPopup">
    <strong>Recommendation:</strong>
    <p><?= $recommendation ?></p>
    <button onclick="document.getElementById('recommendationPopup').style.display='none'">Close</button>
</div>

<script>
const phData = <?= json_encode(array_reverse($ph_data)) ?>;
const phLabels = <?= json_encode(array_reverse($ph_labels)) ?>;

// Chart.js
new Chart(document.getElementById('phChart'), {
    type: 'line',
    data: {
        labels: phLabels,
        datasets:[{
            label:'pH Value',
            data: phData,
            borderColor:'#4CAF50',
            backgroundColor:'rgba(76, 175, 80, 0.2)',
            tension:0.4,
            fill:true,
            pointRadius:4
        }]
    },
    options: {
        responsive:true,
        maintainAspectRatio:false,
        scales:{ y:{ suggestedMin:4, suggestedMax:9 } }
    }
});

// Show popup if pH is out of healthy range
const latestPH = <?= $latest_ph ?>;
document.getElementById('recommendationPopup').style.display = 'block';
</script>

</div>
</body>
</html>
