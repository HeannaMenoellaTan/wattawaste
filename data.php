<?php
require_once 'firebase_config.php';

// ==== SENSOR DATA FETCH FROM FIREBASE ====
function fetchFirebaseData($sensorType, $limit = 7) {
    try {
        $database = getDatabase();
        
        // Fetch history data from Firebase
        $historyRef = $database->getReference("sensors/$sensorType/history");
        $snapshot = $historyRef
            ->orderByChild('timestamp')
            ->limitToLast($limit)
            ->getSnapshot();
        
        if (!$snapshot->exists()) {
            return [];
        }
        
        $data = [];
        $historyData = $snapshot->getValue();
        
        foreach ($historyData as $key => $item) {
            $data[] = [
                'id' => $key,
                'value' => $item['value'] ?? 0,
                'time' => isset($item['timestamp']) ? date('Y-m-d H:i:s', $item['timestamp']) : date('Y-m-d H:i:s')
            ];
        }
        
        return $data;
        
    } catch (Exception $e) {
        error_log("Firebase fetch error for $sensorType: " . $e->getMessage());
        return [];
    }
}

$temperature = fetchFirebaseData('temperature', 7);
$humidity    = fetchFirebaseData('humidity', 7);
$gas         = fetchFirebaseData('gas', 7);
$ph          = fetchFirebaseData('ph', 7);

// ==== KPI FUNCTION ====
function getKPI($data) {
    if (empty($data)) {
        return ['latest' => 0, 'diff' => 0, 'trend' => '▬', 'avg' => 0];
    }
    
    $latest = end($data)['value'] ?? 0;
    $previous = count($data) > 1 ? $data[count($data) - 2]['value'] : 0;
    $diff = round($latest - $previous, 2);
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
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
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
.plant-card { background-color: #f3f9f3; padding: 15px; border-radius: 12px; text-align: center; font-size: 16px; line-height: 1.4; cursor:pointer; transition: transform 0.2s; }
.plant-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
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
.popup .close { position: absolute; top: 10px; right: 15px; font-size: 25px; cursor: pointer; color: #666; }
.popup .close:hover { color: #000; }
.popup-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; }
.no-data { text-align: center; padding: 20px; color: #999; font-style: italic; }
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
      ['title'=>'Temperature (°C)','kpi'=>$tempKPI,'id'=>'tempChart','color'=>'#ff5733','data'=>$temperature],
      ['title'=>'Humidity (%)','kpi'=>$humidityKPI,'id'=>'humidityChart','color'=>'#70c575','data'=>$humidity],
      ['title'=>'Gas Level (ppm)','kpi'=>$gasKPI,'id'=>'gasChart','color'=>'#2e86de','data'=>$gas],
      ['title'=>'pH Level','kpi'=>$phKPI,'id'=>'phChart','color'=>'#28a745','data'=>$ph]
  ];
  foreach($sensors as $s):
  ?>
  <div class="chart-card">
      <h4><?= $s['title'] ?></h4>
      <?php if (!empty($s['data'])): ?>
      <div class="kpi">
          <span class="kpi-value"><?= $s['kpi']['latest'] ?><?= strpos($s['title'],'%')!==false?'%':($s['title']=='Temperature (°C)'?'°C':'') ?></span>
          <span class="kpi-trend <?= $s['kpi']['diff']>0?'up':($s['kpi']['diff']<0?'down':'same') ?>"><?= $s['kpi']['trend'] ?> <?= abs($s['kpi']['diff']) ?></span>
          <div class="kpi-avg">(Avg: <?= $s['kpi']['avg'] ?>)</div>
      </div>
      <canvas id="<?= $s['id'] ?>"></canvas>
      <?php else: ?>
      <div class="no-data">No data available</div>
      <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<div class="plant-overview">
  <h3>🌿 Plant Overview</h3>
  <?php if (!empty($plants)): ?>
  <div class="plant-grid">
    <?php foreach($plants as $i => $plant): ?>
      <div class="plant-card" onclick="showPlantPopup(<?= $i ?>)">
        <img src="<?= $plant['image'] ?>" alt="<?= htmlspecialchars($plant['name']) ?>">
        <h4><?= htmlspecialchars($plant['name']) ?></h4>
      </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="no-data">Unable to load plant data</div>
  <?php endif; ?>
</div>

<!-- Popup Overlay -->
<div id="popupOverlay" class="popup-overlay" onclick="closePopup()"></div>

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

const sensorColors = {
    tempChart: '#ff5733',
    humidityChart: '#70c575',
    gasChart: '#2e86de',
    phChart: '#28a745'
};

function getGradient(ctx,color){
    const g = ctx.createLinearGradient(0,0,0,250);
    g.addColorStop(0,color+'80');
    g.addColorStop(1,color+'10');
    return g;
}

Object.keys(sensorsData).forEach(id=>{
    if (sensorsData[id].length === 0) return;
    
    const ctx = document.getElementById(id).getContext('2d');
    const color = sensorColors[id];
    
    new Chart(ctx,{
        type: id==='humidityChart'?'bar':'line',
        data:{
            labels: sensorsData[id].map(d=>{
                const date = new Date(d.time);
                return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            }),
            datasets:[{
                label: id.replace('Chart', ''),
                data:sensorsData[id].map(d=>d.value),
                borderColor: id==='humidityChart'?'':color,
                backgroundColor: id==='humidityChart'
                    ? sensorsData[id].map(()=> color+'CC')
                    : getGradient(ctx, color),
                fill:true,
                tension:0.4,
                pointRadius:5,
                pointHoverRadius:8,
                pointBackgroundColor: color,
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options:{ 
            responsive:true, 
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales:{ 
                x:{
                    ticks:{color:'#2f5233', font: {size: 10}},
                    grid: {display: false}
                }, 
                y:{
                    beginAtZero:true,
                    ticks:{color:'#2f5233'},
                    grid: {color: 'rgba(0,0,0,0.05)'}
                } 
            } 
        }
    });
});

const plantData = <?php echo json_encode($plants); ?>;

function showPlantPopup(index) {
    const plant = plantData[index];
    document.getElementById('popupImage').src = plant.image;
    document.getElementById('popupName').innerText = plant.name;

    let desc = `<strong>Scientific Name:</strong> ${plant.scientific_name}<br>`;
    desc += `<strong>Growth Days:</strong> ${plant.growth_days}<br>`;
    desc += `<strong>Sunlight:</strong> ${plant.sunlight}`;
    document.getElementById('popupDescription').innerHTML = desc;

    document.getElementById('plantPopup').style.display = 'block';
    document.getElementById('popupOverlay').style.display = 'block';
}

function closePopup() {
    document.getElementById('plantPopup').style.display = 'none';
    document.getElementById('popupOverlay').style.display = 'none';
}

// Close popup with Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closePopup();
});

// Auto-refresh data every 30 seconds
setInterval(() => {
    location.reload();
}, 30000);
</script>
</body>
</html>