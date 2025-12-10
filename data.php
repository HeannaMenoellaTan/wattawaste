<?php
include('db.php');

// ==== SENSOR DATA FETCH ====
function fetchData($query) {
    global $conn;
    $result = mysqli_query($conn, $query);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return array_reverse($data);
}

$temperature = fetchData("SELECT Temp_id AS id, Temp_Ave AS value, created_at AS time FROM temperatures ORDER BY Temp_id DESC LIMIT 7");
$humidity    = fetchData("SELECT Humid_Id AS id, Humid_Lvl AS value, created_at AS time FROM humidity ORDER BY Humid_Id DESC LIMIT 7");
$gas         = fetchData("SELECT Gas_id AS id, Gas_Lvl AS value, created_at AS time FROM gas ORDER BY Gas_id DESC LIMIT 7");
$ph          = fetchData("SELECT pH_Id AS id, pH_Value AS value, created_at AS time FROM ph ORDER BY pH_Id DESC LIMIT 7");

// ==== KPI FUNCTION ====
function getKPI($data) {
    $latest = end($data)['value'] ?? 0;
    $previous = prev($data)['value'] ?? 0;
    $diff = $latest - $previous;
    $trend = $diff > 0 ? '▲' : ($diff < 0 ? '▼' : '▬');
    $avg = round(array_sum(array_column($data, 'value')) / count($data), 2);
    return ['latest' => $latest, 'diff' => $diff, 'trend' => $trend, 'avg' => $avg];
}

$tempKPI     = getKPI($temperature);
$humidityKPI = getKPI($humidity);
$gasKPI      = getKPI($gas);
$phKPI       = getKPI($ph);

// ==== PLANT DATA FROM TREFLE API ====
$apiToken = 'usr-PAxkHSBd0vJNQXNK4hM8Ig8adcaIUmwy-74hO6EMyyw';
$url = "https://trefle.io/api/v1/plants?token=$apiToken&limit=6";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$plantData = json_decode($response, true);
$plants = [];

if(isset($plantData['data'])){
    foreach($plantData['data'] as $plant){
        $plants[] = [
            'name' => $plant['common_name'] ?? $plant['scientific_name'],
            'scientific_name' => $plant['scientific_name'] ?? 'N/A',
            'image' => $plant['image_url'] ?? 'https://via.placeholder.com/150',
            'growth_days' => $plant['growth']['duration'] ?? 'N/A',
            'sunlight' => $plant['growth']['light'] ?? 'N/A'
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Analytics | WattAWaste Bin</title>
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { font-family: Arial, sans-serif; background-color: #f4f7f6; color: #2f5233; }
h2 { font-size: 28px; margin-bottom: 30px; text-align: center; }
.charts-container { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 40px; }
.chart-card { background: #ffffff; border-radius: 15px; box-shadow: 0 3px 8px rgba(0,0,0,0.15); padding: 25px; font-size: 18px; }
.kpi { display: flex; flex-direction: column; margin-bottom: 15px; }
.kpi-value { font-size: 28px; font-weight: bold; }
.kpi-trend { font-size: 22px; font-weight: bold; margin-top: 5px; }
.kpi-avg { font-size: 16px; color: #555; }
.trend.up { color: #28a745; }
.trend.down { color: #dc3545; }
.trend.same { color: #6c757d; }
canvas { width: 100% !important; height: 250px !important; }
.plant-overview { background: #ffffff; border-radius: 15px; padding: 25px; box-shadow: 0 3px 8px rgba(0,0,0,0.15); margin: 40px auto; max-width: 900px; }
.plant-overview h3 { font-size: 24px; margin-bottom: 20px; text-align:center; }
.plant-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; }
.plant-card { background-color: #f3f9f3; padding: 15px; border-radius: 12px; text-align: center; font-size: 16px; line-height: 1.4; cursor:pointer; }
.plant-card img { width: 80px; height: 80px; object-fit: cover; border-radius: 10px; }
.plant-card h4 { font-size: 18px; color: #204b2d; margin-bottom: 10px; }
.popup {
  display: none; 
  position: fixed; 
  top: 50%; left: 50%; 
  transform: translate(-50%, -50%);
  background: #fff; 
  padding: 25px; 
  border-radius: 15px; 
  box-shadow: 0 5px 15px rgba(0,0,0,0.3);
  text-align: center; 
  max-width: 300px;
  z-index: 1000;
}
.popup img { width: 120px; height: 120px; object-fit: cover; border-radius: 10px; margin-bottom: 10px; }
.popup .close { position: absolute; top: 10px; right: 15px; font-size: 25px; cursor: pointer; }
@media(max-width:768px) { .charts-container { grid-template-columns: 1fr; } .plant-grid { grid-template-columns: 1fr 1fr; } }
</style>
</head>
<body>

<?php  
include 'sideabr.php';
include_once 'notif_bell.php';
?>
<div class="main">
<?php include 'topnav.php';?>

<h2>📊 Data Analytics Overview</h2>

<div class="charts-container">
  <?php
  $sensors = [
      ['title'=>'Temperature (°C)','kpi'=>$tempKPI,'id'=>'tempChart','color'=>'#ff5733'],
      ['title'=>'Humidity (%)','kpi'=>$humidityKPI,'id'=>'humidityChart','color'=>'#70c575'],
      ['title'=>'Gas Level (ppm)','kpi'=>$gasKPI,'id'=>'gasChart','color'=>'#2e86de'],
      ['title'=>'pH Level','kpi'=>$phKPI,'id'=>'phChart','color'=>'#28a745']
  ];
  foreach($sensors as $s):
  ?>
  <div class="chart-card">
      <h4><?= $s['title'] ?></h4>
      <div class="kpi">
          <span class="kpi-value"><?= $s['kpi']['latest'] ?><?= strpos($s['title'],'%')!==false?'%':($s['title']=='Temperature (°C)'?'°C':'') ?></span>
          <span class="kpi-trend <?= $s['kpi']['diff']>0?'up':($s['kpi']['diff']<0?'down':'same') ?>"><?= $s['kpi']['trend'] ?> <?= abs($s['kpi']['diff']) ?></span>
          <div class="kpi-avg">(Avg: <?= $s['kpi']['avg'] ?>)</div>
      </div>
      <canvas id="<?= $s['id'] ?>"></canvas>
  </div>
  <?php endforeach; ?>
</div>

<div class="plant-overview">
  <h3>🌿 Plant Overview</h3>
  <div class="plant-grid">
    <?php foreach($plants as $i => $plant): ?>
      <div class="plant-card" onclick="showPlantPopup(<?= $i ?>)">
        <img src="<?= $plant['image'] ?>" alt="<?= htmlspecialchars($plant['name']) ?>">
        <h4><?= htmlspecialchars($plant['name']) ?></h4>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Popup -->
<div id="plantPopup" class="popup">
  <span class="close" onclick="closePopup()">&times;</span>
  <img id="popupImage" src="" alt="">
  <h4 id="popupName"></h4>
  <p id="popupDescription"></p>
</div>

<script>
const sensorsData = {
    tempChart: <?php echo json_encode($temperature); ?>,
    humidityChart: <?php echo json_encode($humidity); ?>,
    gasChart: <?php echo json_encode($gas); ?>,
    phChart: <?php echo json_encode($ph); ?>
};

function getGradient(ctx,color){
    const g = ctx.createLinearGradient(0,0,0,250);
    g.addColorStop(0,color+'80');
    g.addColorStop(1,color+'10');
    return g;
}

Object.keys(sensorsData).forEach(id=>{
    const ctx = document.getElementById(id).getContext('2d');
    new Chart(ctx,{
        type: id==='humidityChart'?'bar':'line',
        data:{
            labels: sensorsData[id].map(d=>new Date(d.time).toLocaleDateString()),
            datasets:[{
                label:id,
                data:sensorsData[id].map(d=>d.value),
                borderColor:id==='humidityChart'?'':'#ff5733',
                backgroundColor:id==='humidityChart'?sensorsData[id].map(()=> 'rgba(111, 194, 118, 0.8)'):getGradient(ctx,'#ff5733'),
                fill:true,
                tension:0.4,
                pointRadius:5,
                pointHoverRadius:8
            }]
        },
        options:{ responsive:true, scales:{ x:{ticks:{color:'#2f5233'}}, y:{beginAtZero:true,ticks:{color:'#2f5233'}} } }
    });
});

const plantData = <?php echo json_encode($plants); ?>;

function showPlantPopup(index) {
    const plant = plantData[index];
    document.getElementById('popupImage').src = plant.image;
    document.getElementById('popupName').innerText = plant.name;

    let desc = `Scientific Name: ${plant.scientific_name}<br>`;
    desc += `Growth Days: ${plant.growth_days}<br>`;
    desc += `Sunlight: ${plant.sunlight}`;
    document.getElementById('popupDescription').innerHTML = desc;

    document.getElementById('plantPopup').style.display = 'block';
}

function closePopup() {
    document.getElementById('plantPopup').style.display = 'none';
}
</script>
</body>
</html>
