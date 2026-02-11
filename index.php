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

<!-- Firebase Auth and Database -->
<script type="module">
import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
import { getAuth, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
import { getDatabase, ref, set, onValue } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-database.js';

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
const database = getDatabase(app);

onAuthStateChanged(auth, (user) => {
    if (!user) {
        console.log('❌ No user found, redirecting to login...');
        window.location.href = 'login.php';
    } else {
        console.log('✅ User authenticated:', user.email || user.phoneNumber);
        sessionStorage.setItem('userEmail', user.email || user.phoneNumber || '');
        sessionStorage.setItem('userId', user.uid);
        
        // Check if user has verified profile for mixer control
        const userRef = ref(database, `users/${user.uid}`);
        onValue(userRef, (snapshot) => {
            const userData = snapshot.val();
            console.log('👤 User profile data:', userData);
            
            // User is authorized if they have a verified profile
            const isVerified = userData && userData.isVerified === true;
            console.log('🔐 Profile verification status:', isVerified);
            
            sessionStorage.setItem('isAuthorizedForMixer', isVerified);
            
            // Show/hide mixer controls based on verification
            const mixerControls = document.getElementById('mixerControls');
            const unauthorizedMessage = document.getElementById('unauthorizedMessage');
            
            if (!isVerified) {
                if (mixerControls) mixerControls.style.display = 'none';
                if (unauthorizedMessage) {
                    unauthorizedMessage.style.display = 'block';
                    unauthorizedMessage.innerHTML = `
                        <i class="fas fa-lock me-2"></i>
                        Your account is not verified. Please complete the 
                        <a href="profile.php" style="color: #E65100; text-decoration: underline; font-weight: 700;">profile verification</a> 
                        to access mixer controls.
                    `;
                }
            } else {
                if (mixerControls) mixerControls.style.display = 'block';
                if (unauthorizedMessage) unauthorizedMessage.style.display = 'none';
            }
        });
        
        // Initialize Firebase real-time listeners after authentication
        console.log('🔥 Initializing Firebase listeners...');
        window.setupFirebaseListeners();
        
        // Initialize mixer controls after Firebase is ready
        window.initializeMixerControls();
    }
});

window.firebaseAuth = auth;
window.firebaseDatabase = database;
window.firebaseRef = ref;
window.firebaseSet = set;
window.firebaseOnValue = onValue;

// Initialize mixer controls function (called after Firebase is ready)
window.initializeMixerControls = function() {
  console.log('🎛️ Initializing mixer controls...');
  
  const toggle = document.getElementById('mixerToggle');
  const knob = document.getElementById('mixerKnob');
  const mixerAlert = document.getElementById('mixerAlert');
  const mixerText = document.getElementById('mixerText');

  // Debug: Check if elements exist
  console.log('🔧 Mixer elements check:');
  console.log('  - toggle:', toggle ? '✅ Found' : '❌ Not found');
  console.log('  - knob:', knob ? '✅ Found' : '❌ Not found');
  console.log('  - mixerAlert:', mixerAlert ? '✅ Found' : '❌ Not found');
  console.log('  - mixerText:', mixerText ? '✅ Found' : '❌ Not found');

  if (!toggle) {
    console.error('❌ CRITICAL: Mixer toggle element not found!');
    return;
  }

  // Create Firebase references
  const motorRef = window.firebaseRef(window.firebaseDatabase, 'controls/motor/command');
  const motorStatusRef = window.firebaseRef(window.firebaseDatabase, 'controls/motor/status');

  console.log('🔥 Firebase refs created:');
  console.log('  - motorRef:', motorRef);
  console.log('  - motorStatusRef:', motorStatusRef);

  function showMixerAlert(msg, type='success'){
    mixerAlert.style.display='block'; 
    mixerAlert.className='mixer-alert '+type; 
    mixerAlert.textContent=msg;
    setTimeout(()=>mixerAlert.style.display='none', 4500);
  }

  const today = new Date().toLocaleDateString();
  let mixerData = JSON.parse(localStorage.getItem('mixerData')) || {date:today, count:0, on:false};
  if(mixerData.date !== today){
    mixerData = {date:today, count:0, on:false};
    localStorage.setItem('mixerData', JSON.stringify(mixerData));
  }

  function applyMixerUI(){
    if(mixerData.on){ 
      knob.style.left='36px'; 
      toggle.style.background='#4caf50'; 
      mixerText.textContent='Mixer is ON'; 
    } else { 
      knob.style.left='4px'; 
      toggle.style.background='#cfd8cf'; 
      mixerText.textContent='Mixer is OFF'; 
    }
  }

  applyMixerUI();

  // Listen to motor status changes from Firebase
  window.firebaseOnValue(motorStatusRef, (snapshot) => {
    const status = snapshot.val();
    console.log('🔥 Motor status from Firebase:', status);
    if (status === 'running') {
      mixerData.on = true;
      applyMixerUI();
    } else if (status === 'stopped') {
      mixerData.on = false;
      applyMixerUI();
    }
  });

  // Also listen to motor command to detect remote/timeout stops
  window.firebaseOnValue(motorRef, (snapshot) => {
    const command = snapshot.val();
    console.log('🔥 Motor command from Firebase:', command);
    if (command === false && mixerData.on) {
      // Motor was stopped remotely or by timeout
      mixerData.on = false;
      applyMixerUI();
    }
  });

  // Toggle click handler with Firebase control
  toggle.addEventListener('click', async () => {
    console.log('🖱️ Toggle clicked!');
    
    const isAuthorized = sessionStorage.getItem('isAuthorizedForMixer');
    console.log('🔐 isAuthorizedForMixer from sessionStorage:', isAuthorized);
    console.log('🔐 Checking authorization:', isAuthorized === 'true');
    
    if (isAuthorized !== 'true') {
      console.log('❌ NOT AUTHORIZED - showing error');
      showMixerAlert('🔒 Access Denied: Please verify your profile to control the mixer.', 'error');
      return;
    }
    
    console.log('✅ User is authorized, proceeding...');
    
    if (!mixerData.on) {
      console.log('💡 Mixer is currently OFF, attempting to turn ON...');
      if (mixerData.count >= 2) { 
        console.log('⚠️ Daily limit reached:', mixerData.count);
        showMixerAlert('⚠️ You can only turn the mixer ON twice per day.', 'warning'); 
        return; 
      }
      
      // Send command to Firebase
      try {
        console.log('📤 Sending motor ON command to Firebase...');
        console.log('📍 Motor ref path:', motorRef.toString());
        await window.firebaseSet(motorRef, true);
        console.log('✅ Firebase command sent successfully');
        
        mixerData.on = true; 
        mixerData.count++; 
        mixerData.date = today;
        localStorage.setItem('mixerData', JSON.stringify(mixerData)); 
        applyMixerUI(); 
        showMixerAlert(`✅ Mixer turned ON (${mixerData.count}/2)`, 'success');
      } catch (error) {
        console.error('❌ Firebase error:', error);
        console.error('❌ Error details:', error.message, error.code);
        showMixerAlert('❌ Failed to turn on mixer: ' + error.message, 'error');
      }
    } else {
      console.log('🛑 Mixer is currently ON, attempting to turn OFF...');
      // Send stop command to Firebase
      try {
        console.log('📤 Sending motor OFF command to Firebase...');
        await window.firebaseSet(motorRef, false);
        console.log('✅ Firebase stop command sent successfully');
        
        mixerData.on = false; 
        localStorage.setItem('mixerData', JSON.stringify(mixerData));
        applyMixerUI(); 
        showMixerAlert('🛑 Mixer turned OFF', 'error');
      } catch (error) {
        console.error('❌ Firebase error:', error);
        console.error('❌ Error details:', error.message, error.code);
        showMixerAlert('❌ Failed to turn off mixer: ' + error.message, 'error');
      }
    }
  });
  
  console.log('✅ Mixer controls initialized successfully');
};
</script>

<?php include_once 'notif_bell.php'; ?>

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
  
  .unauthorized-message{
    background:#FFF8E1;
    border:2px solid #fbbf24;
    border-radius:12px;
    padding:16px 20px;
    color:#7A5A00;
    font-weight:600;
    text-align:center;
    display:none;
  }
  
  .unauthorized-message a {
    color: #E65100;
    text-decoration: underline;
    font-weight: 700;
  }
  
  .unauthorized-message a:hover {
    color: #BF360C;
  }
  
  .mixer-disabled {
    opacity: 0.5;
    cursor: not-allowed !important;
    pointer-events: none;
  }
  
  /* Ensure mixer toggle is always clickable */
  #mixerToggle {
    cursor: pointer !important;
    pointer-events: auto !important;
    z-index: 1000;
  }
  
  #mixerToggle:hover {
    opacity: 0.9;
    transform: scale(1.05);
    transition: all 0.2s ease;
  }
</style>
</head>
<body>

<?php
/**
 * Enhanced Compost Stage Detection
 * Replace the stage detection section in your index.php with this code
 */

require_once 'firebase_config.php';
$database = getDatabase();

$firebaseTemp     = $database->getReference("sensors/temperature/latest")->getValue();
$firebaseHumidity = $database->getReference("sensors/humidity/latest")->getValue();
$firebaseGas      = $database->getReference("sensors/gas/latest")->getValue();
$firebasePH       = $database->getReference("sensors/ph/latest")->getValue();
$firebaseWeight   = $database->getReference("sensors/weight/latest")->getValue();
$firebaseInitialWeight = $database->getReference("sensors/weight/initial")->getValue();

$temp = $firebaseTemp ?? 0;
$humidity = $firebaseHumidity ?? 0;
$gas = $firebaseGas ?? 0;
$ph = $firebasePH ?? 0;
$capacity = 100; // 100 kg maximum capacity
$currentWeight = $firebaseWeight ?? 0;
$initialWeight = $firebaseInitialWeight ?? $currentWeight;

// Calculate weight loss percentage
$weightLoss = 0;
if ($initialWeight > 0) {
    $weightLoss = (($initialWeight - $currentWeight) / $initialWeight) * 100;
}

// ========== ENHANCED STAGE DETECTION ==========
// Now considers BOTH sensor readings AND weight decomposition

if ($temp >= 45 && $temp <= 70 && $ph >= 6.5 && $ph <= 8.0 && $humidity >= 40 && $humidity <= 60) {
    $stage = "Thermophilic Stage (Active Decomposition)";
    $stage_desc = "The compost is in its most active phase. High heat indicates rapid microbial activity and pathogen destruction.";
    
    // Verify with weight
    if ($weightLoss < 10) {
        $stage_desc .= " <strong>Note:</strong> Weight loss is low for this stage. Decomposition just started.";
    } elseif ($weightLoss >= 20) {
        $stage_desc .= " <strong>Excellent!</strong> Significant weight loss (" . round($weightLoss, 1) . "%) shows active decomposition.";
    }
    
} elseif ($temp < 40 && $temp >= 15 && $ph >= 7.0 && $ph <= 8.0 && $gas < 100) {
    $stage = "Maturation Stage (Curing)";
    $stage_desc = "Temperature is cooling down. Compost is stabilizing and turning into nutrient-rich humus.";
    
    // Verify with weight
    if ($weightLoss >= 40) {
        $stage_desc .= " <strong>Nearly ready!</strong> Weight has reduced by " . round($weightLoss, 1) . "%, indicating mature compost.";
    } elseif ($weightLoss >= 25) {
        $stage_desc .= " Weight reduced by " . round($weightLoss, 1) . "%. Getting close to finished compost.";
    } else {
        $stage_desc .= " <strong>Warning:</strong> Weight loss (" . round($weightLoss, 1) . "%) seems low for maturation stage. May need more time.";
    }
    
} elseif ($temp >= 20 && $temp < 45 && $ph >= 5.5 && $ph < 6.5) {
    $stage = "Mesophilic Stage (Initial)";
    $stage_desc = "The composting process has just started. Microbes are breaking down simple organic materials.";
    
    if ($weightLoss < 15) {
        $stage_desc .= " Weight loss: " . round($weightLoss, 1) . "%. This is normal for the initial stage.";
    }
    
} else {
    $stage = "Transition Stage";
    
    // Use weight to provide better insight
    if ($weightLoss >= 35) {
        $stage_desc = "Despite mixed sensor readings, significant weight loss (" . round($weightLoss, 1) . "%) suggests advanced decomposition. Compost may be nearing readiness.";
    } elseif ($weightLoss >= 20) {
        $stage_desc = "Readings suggest the compost is moving between phases. Moderate weight loss (" . round($weightLoss, 1) . "%) shows good progress.";
    } else {
        $stage_desc = "Readings suggest the compost is moving between phases. Weight loss: " . round($weightLoss, 1) . "%. Continue monitoring.";
    }
}

// Add weight loss indicator
$weightLossIndicator = "<div class='mt-2 small-muted'><strong>Decomposition Progress:</strong> " . 
                       round($weightLoss, 1) . "% weight reduction from initial " . 
                       round($initialWeight, 2) . " kg</div>";

include 'sideabr.php'; 
?>

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
          <h4 class="mb-1" id="currentWeightDisplay"><?php echo $currentWeight; ?> kg</h4>
          <div class="small-muted mb-2">Capacity: <span id="capacityDisplay"><?php echo $capacity; ?></span> kg</div>
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
          <h4 class="mt-2 text-success" id="compostStage"><?php echo $stage; ?></h4>
          <div class="mt-1" id="compostStageDesc"><?php echo $stage_desc; ?></div>
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
  
  <!-- Unauthorized Message -->
  <div id="unauthorizedMessage" class="unauthorized-message">
    <i class="fas fa-lock me-2"></i>
    You are not authorized to control the mixer. Only verified accounts can access this feature.
  </div>
  
  <!-- Mixer Controls (Hidden for unverified users) -->
  <div id="mixerControls">
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

// ==================== COMPLETE REPLACEMENT FOR updateChart() FUNCTION ====================
// Find the existing updateChart() function in your index.php and REPLACE IT ENTIRELY with this:

function updateChart(){
  console.log('🔄 Fetching prediction data...');
  
  fetch('predict_readiness.php',{cache:'no-store'})
    .then(r=>r.json())
    .then(data=>{
      console.log('📊 Full API Response:', data);
      
      // ========== UPDATE READINESS CHART ==========
      const readiness = clamp(parseFloat(data.readiness)||0, 0, 100);
      console.log('📈 Readiness score:', readiness);
      
      chart.data.datasets[0].data = [readiness, 100-readiness];
      chart.update();
      document.getElementById('readinessLabel').textContent = readiness.toFixed(1)+'%';
      
      const statusEl = document.getElementById('readinessStatus');
      const chartCard = document.getElementById('chartCard');
      chartCard.classList.remove('ok-glow','warn-glow','crit-glow');
      
      if(readiness >= 90){ 
        statusEl.textContent = '🌿 Compost ready!'; 
        chartCard.classList.add('ok-glow'); 
      } else if(readiness >= 60){ 
        statusEl.textContent = '🌱 Almost ready'; 
        chartCard.classList.add('warn-glow'); 
      } else if(readiness >= 30){ 
        statusEl.textContent = '🔥 Heating up'; 
      } else { 
        statusEl.textContent = '🧤 Just started'; 
      }
      
      // ========== GET WEIGHT DATA FROM API ==========
      const weightTracking = data.weight_tracking || {};
      const currentWeight = parseFloat(weightTracking.current_weight) || 0;
      const initialWeight = parseFloat(weightTracking.initial_weight) || 0;
      const weightLossPercent = parseFloat(weightTracking.weight_loss_percent) || 0;
      const weightLossKg = parseFloat(weightTracking.weight_loss_kg) || 0;
      
      console.log('⚖️ Weight Data:');
      console.log('  - Initial:', initialWeight, 'kg');
      console.log('  - Current:', currentWeight, 'kg');
      console.log('  - Loss:', weightLossKg, 'kg ('+weightLossPercent+'%)');
      
      // ========== UPDATE FERTILIZER OUTPUT ==========
      const fertilizerOutput = data.fertilizer_output || {};
      const predictedOutput = parseFloat(fertilizerOutput.predicted_output_kg) || 0;
      const actualOutput = parseFloat(fertilizerOutput.actual_output_kg) || 0;
      const isReady = fertilizerOutput.is_ready || false;
      
      console.log('🌾 Fertilizer Output:');
      console.log('  - Predicted:', predictedOutput, 'kg');
      console.log('  - Actual:', actualOutput, 'kg');
      console.log('  - Ready:', isReady);
      
      // Display predicted output
      const predictedEl = document.getElementById('predictedOutput');
      if (predictedEl) {
        predictedEl.textContent = predictedOutput.toFixed(4) + ' kg';
      }
      
      // Display actual output (only when ready)
      const actualOutputEl = document.getElementById('actualOutput');
      if (actualOutputEl) {
        if (isReady) {
          actualOutputEl.textContent = actualOutput.toFixed(4) + ' kg';
          actualOutputEl.classList.remove('text-primary');
          actualOutputEl.classList.add('text-success', 'fw-bold');
        } else {
          actualOutputEl.textContent = 'Not ready yet';
          actualOutputEl.classList.remove('text-success', 'fw-bold');
          actualOutputEl.classList.add('text-primary');
        }
      }
      
      // Update fertilizer bar
      const fertPercentage = parseFloat(fertilizerOutput.fertilizer_percentage) || 0;
      const fertBar = document.getElementById('fertBar');
      if (fertBar) {
        fertBar.style.width = clamp(fertPercentage, 0, 100) + '%';
      }
      
      console.log('✅ Chart update complete!');
      
    })
    .catch(e => {
      console.error('❌ Chart update error:', e);
    });
}

// Initialize - call immediately and then every 10 seconds
updateChart();
setInterval(updateChart, 10000);

function loadSensorStatus() {
    fetch("sensor_status.php")
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById("sensor-status-container");
            if (!container) return;
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

            const faultyTextEl = document.getElementById("faultyText");
            if (faultyTextEl) {
                faultyTextEl.textContent = `${faultyCount} Faulty Sensor${faultyCount !== 1 ? 's' : ''}`;
            }
        })
        .catch(err => console.error("FETCH ERROR:", err));
}

setInterval(loadSensorStatus, 2000);
loadSensorStatus();

function updateDateTime() {
    const now = new Date();
    const options = { 
        month: "short", 
        day: "numeric", 
        year: "numeric", 
        hour: "2-digit", 
        minute: "2-digit"
    };
    const dateTimeEl = document.getElementById("dateTime");
    if (dateTimeEl) {
        dateTimeEl.innerHTML = now.toLocaleString("en-US", options);
    }
}

setInterval(updateDateTime, 1000);
updateDateTime();
</script>
</body>
</html>