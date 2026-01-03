<?php

/** 
 * 1. ✅ Load Firebase
 */
require_once 'firebase_config.php';
$database = getDatabase();

/**
 * 2. 🔥 Fetch latest sensor values from Firebase
 */
$firebaseTemp     = $database->getReference("sensors/temperature/latest/value")->getValue();
$firebaseHumidity = $database->getReference("sensors/humidity/latest/value")->getValue();
$firebaseGas      = $database->getReference("sensors/gas/latest/value")->getValue();
$firebasePH       = $database->getReference("sensors/ph/latest/value")->getValue();
$firebaseWeight   = $database->getReference("sensors/weight/latest/value")->getValue();
$firebaseCapacity = $database->getReference("sensors/weight/latest/capacity")->getValue();

/**
 * 3. 🛑 Set sensor values (with fallback to 0 if null)
 */
$temp = $firebaseTemp ?? 0;
$humidity = $firebaseHumidity ?? 0;
$gas = $firebaseGas ?? 0;
$ph = $firebasePH ?? 0;
$capacity = $firebaseCapacity ?? 0;
$currentWeight = $firebaseWeight ?? 0;

/**
 * 4. 🌡 Compost Stage Calculation (unchanged)
 */
if ($temp >= 45 && $temp <= 70 && $ph >= 6.5 && $ph <= 8.0 && $humidity >= 40 && $humidity <= 60) {
  $stage = "Thermophilic Stage (Active Decomposition)";
  $stage_desc = "The compost is in its most active phase. High heat indicates rapid microbial activity and pathogen destruction.";
} elseif ($temp < 40 && $ph >= 7.0 && $ph <= 8.0 && $humidity <= 50 && $gas < 100) {
  $stage = "Maturation Stage (Curing)";
  $stage_desc = "Temperature is cooling down. Compost is stabilizing and turning into nutrient-rich humus.";
} elseif ($temp >= 20 && $temp < 45 && $ph >= 5.5 && $ph < 6.5) {
  $stage = "Mesophilic Stage (Initial)";
  $stage_desc = "The composting process has just started. Microbes are breaking down simple organic materials.";
} else {
  $stage = "Transition Stage";
  $stage_desc = "Readings suggest the compost is moving between phases. Continue monitoring sensor changes.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>WattAWaste Bin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://kit.fontawesome.com/a2e0e6ad65.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php include_once 'notif_bell.php'; ?>

<!-- ADD THIS FIREBASE AUTH SCRIPT -->
<script type="module">
import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
import { getAuth, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';

const firebaseConfig = {
    apiKey: "AIzaSyAu9hOwjiuAl9PCh50HefMGZU9XDosu68I",
    authDomain: "wattawaste-d3503.firebaseapp.com",
    databaseURL: "https://wattawaste-d3503-default-rtdb.asia-southeast1.firebasedatabase.app",
    projectId: "wattawaste-d3503",
    storageBucket: "wattawaste-d3503.firebasestorage.app",
    messagingSenderId: "842761118644",
    appId: "1:842761118644:web:ddef65fd892486f67f88e1",
    measurementId: "G-33Z8K3NBY1"
};

const app = initializeApp(firebaseConfig);
const auth = getAuth(app);

// Check authentication status
onAuthStateChanged(auth, (user) => {
    if (!user) {
        // Not logged in - redirect to login
        console.log('❌ No user found, redirecting to login...');
        window.location.href = 'login.php';
    } else {
        // User is logged in
        console.log('✅ User authenticated:', user.email || user.phoneNumber);
        sessionStorage.setItem('userEmail', user.email || user.phoneNumber || '');
        sessionStorage.setItem('userId', user.uid);
    }
});

// Make auth available globally for logout
window.firebaseAuth = auth;
</script>


<?php  
include_once 'notif_bell.php';
?>

<style>
  :root{
    --brand:#4CAF50; --brand-dark:#2E7D32; --ink:#333;
    --panel:#fff; --muted:#555; --bg:#F9FAFB;
    --ok:#22c55e; --warn:#f59e0b; --crit:#ef4444; --info:#38bdf8;}
  
  body{background:var(--bg);font-family:Poppins,system-ui,Segoe UI,Arial;color:var(--ink);min-height:100vh}
 
  .card{border:none;border-radius:16px;background:var(--panel);
        box-shadow:0 6px 16px rgba(2,6,23,.06);transition:transform .2s, box-shadow .2s}
  .card:hover{transform:translateY(-3px);box-shadow:0 12px 26px rgba(2,6,23,.12)}
  .chart-container{position:relative;width:320px;max-width:100%;aspect-ratio:1/1;margin:auto}
  .chart-label{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center}
  .chart-label #readinessLabel{font-weight:800;font-size:28px;line-height:1}
  .chart-label #readinessStatus{color:var(--muted);font-size:.95rem}

  .bar-container{width:100%;background:#E9ECEF;height:12px;border-radius:10px;overflow:hidden;position:relative}
  .bar{height:100%;width:0%;border-radius:10px;transition:width .9s ease}

  .bar::after{content:"";position:absolute;inset:0;transform:translateX(-100%);
              background:linear-gradient(90deg,transparent,rgba(255,255,255,.55),transparent);
              animation:shimmer 1.8s infinite}
  @keyframes shimmer{50%{transform:translateX(0)}100%{transform:translateX(100%)}}

  .small-muted{color:var(--muted);opacity:.9;font-size:.92rem}

  .dashboard-container{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:20px;padding:20px}
  .sensor-card{position:relative;border-radius:18px;padding:22px;text-align:center;color:#0f172a;background:#ffffff}
  .sensor-card .icon-wrap{
    width:58px;height:58px;border-radius:50%;display:grid;place-items:center;margin:0 auto 10px;
    background:#E8F5E9;position:relative;
    box-shadow:0 0 0 0 rgba(76,175,80,.5);animation:ringPulse 2.6s infinite;
  }
  @keyframes ringPulse{
    0%{box-shadow:0 0 0 0 rgba(76,175,80,.42)}
    70%{box-shadow:0 0 0 14px rgba(76,175,80,0)}
    100%{box-shadow:0 0 0 0 rgba(76,175,80,0)}
  }
  .sensor-card .icon{font-size:28px;color:var(--brand-dark)}
  .sensor-card h3{margin:.25rem 0 .25rem}
  .sensor-card .value{font-size:26px;font-weight:800;margin-bottom:12px}
 
  .thermo-meter,.droplet-meter,.gas-meter,.ph-meter{
    width:100%;height:18px;border-radius:50px;background:#f1f5f9;overflow:hidden;position:relative
  }
  .thermo-fill{height:100%;width:0%;background:linear-gradient(90deg,#ff7b00,#ff0000);
               box-shadow:0 0 12px rgba(255,90,0,.45);transition:width 1s ease}
  .droplet-fill{height:100%;width:0%;background:linear-gradient(90deg,#00b4d8,#48cae4);
                box-shadow:0 0 10px rgba(0,180,216,.45);transition:width 1s ease}
  .gas-fill{height:100%;width:0%;background:linear-gradient(90deg,#ffba08,#f48c06);
            box-shadow:0 0 10px rgba(244,140,6,.45);transition:width 1s ease}
  .ph-fill{height:100%;width:0%;background:linear-gradient(90deg,#ff0000,#ffae00,#00ff00,#0088ff,#4b0082);
           transition:width 1s ease}


  .ok-glow{box-shadow:0 0 0 0 rgba(34,197,94,.45), 0 14px 34px rgba(34,197,94,.12)}
  .warn-glow{box-shadow:0 0 0 0 rgba(245,158,11,.45), 0 14px 34px rgba(245,158,11,.12)}
  .crit-glow{box-shadow:0 0 0 0 rgba(239,68,68,.55), 0 16px 36px rgba(239,68,68,.18); animation:shake .4s ease}
  @keyframes shake{20%{transform:translateX(-2px)} 40%{transform:translateX(2px)} 60%{transform:translateX(-1px)} 80%{transform:translateX(1px)}}

  .mixer-alert{position:fixed;top:80px;left:50%;transform:translateX(-50%);z-index:1500;display:none;
    padding:10px 14px;border-radius:10px;font-weight:600}
  .mixer-alert.success{background:#E9F8EC;color:#256333;border-left:6px solid var(--brand-dark)}
  .mixer-alert.warning{background:#FFF8E1;color:#7A5A00;border-left:6px solid #fbbf24}
  .mixer-alert.error{background:#FFE5E5;color:#7F1D1D;border-left:6px solid #ef4444}
  </style>
</head>
<body>

<?php include 'sideabr.php'; ?>
<div class="main">
<?php include 'topnav.php';?>


    <div class="row g-4 align-items-center">
      <div class="col-lg-5">
        <div class="card p-3 chart-container" id="chartCard">
          <canvas id="progressChart" aria-label="Compost Readiness"></canvas>
          <div class="chart-label">
            <div id="readinessLabel">--%</div>
            <div id="readinessStatus" class="small-muted">Loading...</div>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="card p-3" id="weightCard">
              <small class="small-muted">Current Waste Weight</small>
              <h4 class="mb-1"><?php echo $currentWeight; ?> kg</h4>
              <div class="small-muted mb-2">Capacity: <?php echo $capacity; ?> kg</div>
              <div class="bar-container"><div id="weightBar" class="bar" style="background:linear-gradient(90deg,#81C784,#4CAF50)"></div></div>
            </div>
          </div>
          <div class="col-md-6">
            <div id="fertCard" class="card p-3">
              <small class="small-muted fw-bold">🌾 Fertilizer Output</small>
              <div class="mt-2">
                <div class="d-flex justify-content-between"><span>Predicted Output:</span><span id="predictedOutput" class="fw-bold text-success">-- kg</span></div>
                <div class="d-flex justify-content-between"><span>Actual Output:</span><span id="actualOutput" class="fw-bold text-primary">--</span></div>
              </div>
              <div class="bar-container mt-3"><div id="fertBar" class="bar" style="background:linear-gradient(90deg,#a7f3d0,#10b981)"></div></div>
            </div>
          </div>

          <div class="col-12">
            <div class="card p-3">
    <small class="small-muted fw-bold">🧬 Compost Stage</small>
    <h4 class="mt-2 text-success"><?php echo $stage; ?></h4>
    <div class="mt-1"><?php echo $stage_desc; ?></div>
    
            </div>
          </div>
        </div>
      </div>
    </div>
<div class="dashboard-container">
  <div class="sensor-card" id="tempCard">
    <div class="icon-wrap"><i class="icon fas fa-thermometer-half"></i></div>
    <h3>Temperature</h3>
    <div class="value" id="tempValue">-- °C</div>
    <div class="thermo-meter"><div class="thermo-fill" id="tempFill"></div></div>
  </div>

  <div class="sensor-card" id="gasCard">
    <div class="icon-wrap"><i class="icon fas fa-wind"></i></div>
    <h3>Gas Level</h3>
    <div class="value" id="gasValue">-- ppm</div>
    <div class="gas-meter"><div class="gas-fill" id="gasFill"></div></div>
  </div>

  <div class="sensor-card" id="humCard">
    <div class="icon-wrap"><i class="icon fas fa-tint"></i></div>
    <h3>Humidity</h3>
    <div class="value" id="humValue">-- %</div>
    <div class="droplet-meter"><div class="droplet-fill" id="humFill"></div></div>
  </div>

  <div class="sensor-card" id="phCard">
    <div class="icon-wrap"><i class="icon fas fa-vial"></i></div>
    <h3>pH Level</h3>
    <div class="value" id="phValue">--</div>
    <div class="ph-meter"><div class="ph-fill" id="phFill"></div></div>
  </div>
</div>
<div class="text-center mt-5 mb-4">
  <div id="mixerAlert" class="mixer-alert"></div>
  <div id="mixerToggle" 
       style="width:70px;height:36px;background:#cfd8cf;border-radius:20px;position:relative;cursor:pointer;margin:auto;">
    <div id="mixerKnob" 
         style="width:30px;height:30px;background:#fff;border-radius:50%;position:absolute;top:3px;left:4px;transition:left .25s;"></div>
  </div>
  <div id="mixerText" class="fw-medium mt-2">Mixer is OFF</div>
  <div class="small-muted">You can turn mixer ON twice per day</div>
</div>
</div>
</div> 
</div>

<script>
const ctx = document.getElementById('progressChart').getContext('2d');
function ring(ctx){ const g=ctx.createLinearGradient(0,0,300,0); g.addColorStop(0,'#a5d6a7'); g.addColorStop(1,'#388e3c'); return g; }
let chart = new Chart(ctx,{
  type:'doughnut',
  data:{labels:['Ready','Remaining'],datasets:[{data:[0,100],backgroundColor:[ring(ctx),'#e5e7eb'],borderWidth:0}]},
  options:{rotation:-90,cutout:'70%',plugins:{legend:{display:false}},animation:{duration:700}}
});

function clamp(n,min,max){ return Math.max(min,Math.min(max,n)); }
function setBar(id, value, max, card){
  const el=document.getElementById(id); if(!el) return;
  const pct = max>0 ? clamp((value/max)*100,0,100) : 0;
  el.style.width = pct + '%';
  el.parentElement.classList.add('ping'); setTimeout(()=>el.parentElement.classList.remove('ping'), 300);
  if(!card) return;
  card.classList.remove('ok-glow','warn-glow','crit-glow');
  if(pct >= 85) card.classList.add('ok-glow');
  else if(pct >= 60) card.classList.add('warn-glow');
}
function statusGlow(card, value, warnAt, critAt){
  card.classList.remove('ok-glow','warn-glow','crit-glow');
  if(value >= critAt) card.classList.add('crit-glow');
  else if(value >= warnAt) card.classList.add('warn-glow');
  else card.classList.add('ok-glow');
}

function updateChart(){
  fetch('predict_readiness.php',{cache:'no-store'})
    .then(r=>r.json()).then(data=>{
      const readiness = clamp(parseFloat(data.readiness)||0,0,100);
      chart.data.datasets[0].data=[readiness,100-readiness];
      chart.update();
      document.getElementById('readinessLabel').textContent = readiness.toFixed(1)+'%';
      const statusEl=document.getElementById('readinessStatus');
      const chartCard=document.getElementById('chartCard');
      chartCard.classList.remove('ok-glow','warn-glow','crit-glow');
      if(readiness>=90){ statusEl.textContent='🌿 Compost ready!'; chartCard.classList.add('ok-glow'); }
      else if(readiness>=60){ statusEl.textContent='🌱 Almost ready'; chartCard.classList.add('warn-glow'); }
      else if(readiness>=30){ statusEl.textContent='🔥 Heating up'; }
      else { statusEl.textContent='🧤 Just started'; }
      const weight = <?php echo (float)$currentWeight; ?>, capacity = <?php echo (float)$capacity; ?>;
      const predicted = (weight * (readiness/100) * 0.5);
      const actual = readiness>=100 ? (weight*0.5) : 0;
      document.getElementById('predictedOutput').textContent = predicted.toFixed(2)+' kg';
      document.getElementById('actualOutput').textContent = readiness>=100 ? actual.toFixed(2)+' kg' : 'Not ready yet';
      document.getElementById('fertBar').style.width = (capacity>0? clamp((predicted/capacity)*100,0,100):0) + '%';
      setBar('weightBar', weight, Math.max(capacity,1), document.getElementById('weightCard'));
    }).catch(()=>{});
}

async function fetchDashboardData(){
  try{
    const res = await fetch('api/get_latest.php',{cache:'no-store'});
    const { latest } = await res.json();
    if(!latest) return;
    const { temperature, humidity, gas, ph } = latest;

    
    document.getElementById('tempValue').textContent = temperature + ' °C';
    document.getElementById('humValue').textContent  = humidity + ' %';
    document.getElementById('gasValue').textContent  = gas + ' ppm';
    document.getElementById('phValue').textContent   = ph;

    document.getElementById('tempFill').style.width = clamp(temperature,0,100) + '%';
    document.getElementById('humFill').style.width  = clamp(humidity,0,100) + '%';
    document.getElementById('gasFill').style.width  = clamp(gas/10,0,100) + '%';
    document.getElementById('phFill').style.width   = clamp((ph/14)*100,0,100) + '%';

    statusGlow(document.getElementById('tempCard'), temperature, 60, 65);
    statusGlow(document.getElementById('humCard'), humidity, 80, 90);
    statusGlow(document.getElementById('gasCard'), gas, 600, 800);
    const phCard=document.getElementById('phCard');
    phCard.classList.remove('ok-glow','warn-glow','crit-glow');
    if(ph<6.5||ph>8.0) phCard.classList.add('warn-glow'); else phCard.classList.add('ok-glow');
  }catch(e){}
}

const toggle=document.getElementById('mixerToggle');
const knob=document.getElementById('mixerKnob');
const mixerAlert=document.getElementById('mixerAlert');
const mixerText=document.getElementById('mixerText');

function showMixerAlert(msg,type='success'){
  mixerAlert.style.display='block'; mixerAlert.className='mixer-alert '+type; mixerAlert.textContent=msg;
  setTimeout(()=>mixerAlert.style.display='none', 4500);
}
const today=new Date().toLocaleDateString();
let mixerData=JSON.parse(localStorage.getItem('mixerData'))||{date:today,count:0,on:false};
if(mixerData.date!==today){mixerData={date:today,count:0,on:false};localStorage.setItem('mixerData',JSON.stringify(mixerData));}
function applyMixerUI(){
  if(mixerData.on){ knob.style.left='36px'; toggle.style.background='#4caf50'; mixerText.textContent='Mixer is ON'; }
  else{ knob.style.left='4px'; toggle.style.background='#cfd8cf'; mixerText.textContent='Mixer is OFF'; }
}
applyMixerUI();
toggle.addEventListener('click',()=>{
  if(!mixerData.on){
    if(mixerData.count>=2){ showMixerAlert('⚠️ You can only turn the mixer ON twice per day.','warning'); return; }
    mixerData.on=true; mixerData.count++; mixerData.date=today;
    localStorage.setItem('mixerData',JSON.stringify(mixerData)); applyMixerUI(); showMixerAlert(`✅ Mixer turned ON (${mixerData.count}/2)`,'success');
  }else{
    mixerData.on=false; localStorage.setItem('mixerData',JSON.stringify(mixerData));
    applyMixerUI(); showMixerAlert('🛑 Mixer turned OFF','error');
  }
});

updateChart();
fetchDashboardData();
setInterval(updateChart, 10000);
setInterval(fetchDashboardData, 3000);


function loadSensorStatus() {
    fetch("sensor_status.php")
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById("sensor-status-container");
            container.innerHTML = "";

            let faultyCount = 0;

            data.forEach(sensor => {
                if (sensor.class === "faulty") faultyCount++;

                let icon = "📡";
                if (sensor.name === "Temperature") icon = "🌡️";
                if (sensor.name === "Humidity") icon = "💧";
                if (sensor.name === "Gas") icon = "🔥";
                if (sensor.name === "pH") icon = "⚗️";

                container.innerHTML += `
                    <div class="sensor-top-badge ${sensor.class}">
                        <span class="icon">${icon}</span>
                        <span>${sensor.name}</span>
                        <span class="dot ${sensor.class}"></span>
                        <span>${sensor.status}</span>
                        <span class="sensor-time">(${sensor.lastUpdate})</span>
                    </div>
                `;
            });

            document.getElementById("faultyText").textContent =
                `${faultyCount} Faulty Sensor${faultyCount !== 1 ? 's' : ''}`;
        })
        .catch(err => console.error("FETCH ERROR:", err));
}

setInterval(loadSensorStatus, 2000);
loadSensorStatus();

setInterval(updateDateTime, 1000);


function updateDateTime() {
    const now = new Date();
    const options = { 
        month: "short", 
        day: "numeric", 
        year: "numeric", 
        hour: "2-digit", 
        minute: "2-digit"
    };
    document.getElementById("dateTime").innerHTML = now.toLocaleString("en-US", options);
}

setInterval(updateDateTime, 1000);
updateDateTime();
</script>
</body>
</html>