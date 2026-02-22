<?php
require_once 'firebase_config.php';
$database = getDatabase();

$temp       = $database->getReference("sensors/temperature/latest")->getValue() ?? 0;
$humidity   = $database->getReference("sensors/humidity/latest")->getValue() ?? 0;
$gas        = $database->getReference("sensors/gas/latest")->getValue() ?? 0;
$ph         = $database->getReference("sensors/ph/latest")->getValue() ?? 0;
$weight     = $database->getReference("sensors/weight/latest")->getValue() ?? 0;
$initWeight = $database->getReference("sensors/weight/initial")->getValue() ?? $weight;
$rawSessions = $database->getReference("compost_sessions")->getValue() ?? [];

$sessions = [];
foreach ($rawSessions as $s) {
    if (isset($s['temp'], $s['humidity'], $s['gas'], $s['ph'], $s['weightLoss'], $s['daysToReady'])) {
        $sessions[] = $s;
    }
}

$weightLoss = ($initWeight > 0)
    ? max(0, round((($initWeight - $weight) / $initWeight) * 100, 2))
    : 0;

// ── k-NN ML Predictor ─────────────────────────────────────────────────────────
function knnPredictDays(array $sessions, float $t, float $h, float $g, float $p, float $wl, int $k = 3): ?float {
    if (empty($sessions)) return null;
    $dist = [];
    foreach ($sessions as $i => $s) {
        $dist[$i] = sqrt(
            pow(($t - $s['temp'])          / 80,   2) +
            pow(($h - $s['humidity'])      / 100,  2) +
            pow(($g - $s['gas'])           / 1000, 2) +
            pow(($p - $s['ph'])            / 14,   2) +
            pow(($wl - $s['weightLoss'])   / 100,  2)
        );
    }
    asort($dist);
    $topK = array_slice(array_keys($dist), 0, $k, true);
    $ws = 0; $wt = 0;
    foreach ($topK as $i) {
        $w   = 1 / max($dist[$i], 0.0001);
        $ws += $w * $sessions[$i]['daysToReady'];
        $wt += $w;
    }
    return $wt > 0 ? round($ws / $wt, 1) : null;
}

$predictedDays = knnPredictDays($sessions, (float)$temp, (float)$humidity, (float)$gas, (float)$ph, (float)$weightLoss);
$sessionCount  = count($sessions);

// ── Readiness Score ───────────────────────────────────────────────────────────
function calcReadiness(float $t, float $h, float $g, float $p, float $wl): float {
    $s = 0;
    // Temperature (25pts)
    if ($t >= 45 && $t <= 65)      $s += 25;
    elseif ($t >= 30 && $t < 45)   $s += 12;
    elseif ($t >= 20 && $t < 30)   $s += 6;
    // Humidity (20pts)
    if ($h >= 45 && $h <= 60)      $s += 20;
    elseif ($h >= 61 && $h <= 69)  $s += 12;
    elseif ($h >= 35 && $h < 45)   $s += 10;
    // pH (20pts)
    if ($p >= 6.5 && $p <= 8.0)    $s += 20;
    elseif ($p >= 6.0 && $p < 6.5) $s += 10;
    elseif ($p > 8.0 && $p <= 8.5) $s += 10;
    // Gas (15pts)
    if ($g < 300)                   $s += 15;
    elseif ($g < 600)               $s += 10;
    elseif ($g < 800)               $s += 5;
    // Weight loss (20pts)
    if ($wl >= 40 && $wl <= 60)    $s += 20;
    elseif ($wl >= 25 && $wl < 40) $s += 12;
    elseif ($wl > 60)               $s += 16;
    return min(100, $s);
}
$readiness = calcReadiness((float)$temp, (float)$humidity, (float)$gas, (float)$ph, (float)$weightLoss);

// ── Stage Detection ───────────────────────────────────────────────────────────
function detectStage(float $t, float $p, float $g): array {
    if ($t > 70)
        return ['Too Hot',              'toohot',       'Overheating! Ventilation must trigger immediately.'];
    if ($t >= 45 && $t <= 70 && $p >= 6.5 && $p <= 8.0)
        return ['Thermophilic Active',  'thermophilic', 'High microbial activity. Pathogens are being destroyed.'];
    if ($t < 40 && $t >= 20 && $p >= 6.5 && $g < 300)
        return ['Maturation',           'maturation',   'Compost is stabilizing and forming nutrient-rich humus.'];
    if ($t >= 20 && $t < 45 && $p >= 5.5 && $p < 6.5)
        return ['Mesophilic Initial',   'mesophilic',   'Early-stage microbes breaking down simple materials.'];
    if ($t < 20)
        return ['Dormant / Too Cold',   'dormant',      'Microbial activity very low. Bin may need insulation.'];
    return ['Transition',               'transition',   'Compost is moving between phases. Continue monitoring.'];
}
[$stageName, $stageKey, $stageDesc] = detectStage((float)$temp, (float)$ph, (float)$gas);

// ── Automation + Recommendations ─────────────────────────────────────────────
$automations     = [];
$recommendations = [];
$phase           = 'aerobic';

// THERMAL: Too Hot
if ((float)$temp > 70) {
    $automations[]     = ['icon'=>'🌬️','label'=>'Ventilation','action'=>'OPEN TOP + BOTTOM VENTS',
        'reason'=>'Temperature '.(float)$temp.'°C exceeds 70°C. Vents opened to prevent microbial death.','severity'=>'crit'];
    $recommendations[] = '🔥 Critical: Temp above 70°C. Top and bottom vents triggered immediately.';
}
// MOISTURE: Critical ≥70%
if ((float)$humidity >= 70) {
    $automations[]     = ['icon'=>'⚙️','label'=>'Mixer Motor','action'=>'ENGAGE MOTOR',
        'reason'=>'Humidity '.(float)$humidity.'% is Critical (≥70%). Motor engaged to evaporate excess moisture.','severity'=>'crit'];
    $automations[]     = ['icon'=>'🌬️','label'=>'Vents','action'=>'OPEN VENTS',
        'reason'=>'Airflow increased to aid evaporation.','severity'=>'warn'];
    $recommendations[] = '💧 Critical moisture ('.(float)$humidity.'%). Mixer + vents auto-activated.';
}
// GAS + pH: Anaerobic shift
if ((float)$gas >= 800 && (float)$ph < 6.0) {
    $phase             = 'anaerobic';
    $automations[]     = ['icon'=>'⚙️','label'=>'Mixer Motor','action'=>'ENGAGE MOTOR',
        'reason'=>'Gas '.(float)$gas.' ppm + pH '.(float)$ph.' indicates anaerobic decay. Aerating mixture.','severity'=>'crit'];
    $automations[]     = ['icon'=>'🌬️','label'=>'Top Vent','action'=>'OPEN TOP VENT',
        'reason'=>'Increasing O₂ to restore aerobic decomposition.','severity'=>'warn'];
    $recommendations[] = '⚠️ Anaerobic shift detected (Gas: '.(float)$gas.' ppm, pH: '.(float)$ph.'). Motor running.';
} elseif ((float)$gas >= 800) {
    $recommendations[] = '💨 High gas ('.(float)$gas.' ppm). Monitor pH — if pH drops below 6.0, motor will auto-engage.';
}
// Aerobic normal
if ($phase === 'aerobic' && (float)$humidity < 70 && (float)$temp <= 70 && !((float)$gas >= 800 && (float)$ph < 6.0)) {
    $automations[] = ['icon'=>'🌀','label'=>'Mode','action'=>'AEROBIC PHASE ACTIVE',
        'reason'=>'All readings within aerobic range. High oxygen and periodic mixing maintained.','severity'=>'ok'];
}
// pH
if ((float)$ph < 6.0)       $recommendations[] = '⚗️ pH too acidic ('.(float)$ph.'). Add dry leaves or wood ash to raise pH toward 6.5–8.0.';
elseif ((float)$ph > 8.5)   $recommendations[] = '⚗️ pH too alkaline ('.(float)$ph.'). Add coffee grounds or acidic greens.';
else                         $recommendations[] = '⚗️ pH is optimal ('.(float)$ph.'). No action needed.';
// Temperature
if ((float)$temp < 20)      $recommendations[] = '🌡️ Temp too low ('.(float)$temp.'°C). Add nitrogen-rich material to heat the pile.';
elseif ((float)$temp >= 45 && (float)$temp <= 70)
                             $recommendations[] = '🌡️ Temp optimal ('.(float)$temp.'°C). Active decomposition occurring.';
// Humidity
if ((float)$humidity < 45)  $recommendations[] = '💧 Humidity too low ('.(float)$humidity.'%). Add ~200ml water to reach 45–60%.';
elseif ((float)$humidity >= 61 && (float)$humidity <= 69)
                             $recommendations[] = '💧 Humidity slightly elevated ('.(float)$humidity.'%). Approaching critical threshold of 70%.';

if (count($recommendations) === 1 && strpos($recommendations[0],'⚗️') !== false) {
    $recommendations[] = '✅ All other readings within optimal range. Continue current routine.';
}

// ── Params ────────────────────────────────────────────────────────────────────
$aerationFreq = ((float)$humidity >= 70 || (float)$temp > 70) ? 'Every 2 hours (emergency)' : ((float)$temp >= 45 ? 'Every 4 hours' : 'Every 6 hours');
$mixerCycles  = ((float)$humidity >= 70 || ((float)$gas >= 800 && (float)$ph < 6.0)) ? '4× per day (auto)' : ((float)$temp >= 45 ? '2× per day' : '1× per day');
$moistureAct  = ((float)$humidity >= 70) ? 'Open vents + engage motor immediately' : ((float)$humidity < 45 ? 'Add 200ml water evenly' : 'No adjustment needed');
$phAction     = ((float)$ph < 6.0) ? 'Add wood ash or dry leaves' : ((float)$ph > 8.5 ? 'Add coffee grounds or acidic greens' : 'pH nominal — no action');

$sColors = ['ok'=>'#22c55e','warn'=>'#f59e0b','crit'=>'#ef4444'];
$ringColor = $readiness >= 80 ? '#4CAF50' : ($readiness >= 50 ? '#f59e0b' : '#ef4444');
$circ   = 2 * M_PI * 62;
$offset = $circ - ($readiness / 100) * $circ;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>AI Advisor - Leafcycle</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://kit.fontawesome.com/a2e0e6ad65.js" crossorigin="anonymous"></script>

<script type="module">
import { initializeApp }               from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
import { getAuth, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
import { getDatabase, ref, onValue }   from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-database.js';
const cfg = {
    apiKey:"AIzaSyAu9hOwjiuAl9PCh50HefMGZU9XDosu68I",
    authDomain:"wattawaste-d3503.firebaseapp.com",
    databaseURL:"https://wattawaste-d3503-default-rtdb.asia-southeast1.firebasedatabase.app",
    projectId:"wattawaste-d3503",storageBucket:"wattawaste-d3503.firebasestorage.app",
    messagingSenderId:"842761118644",appId:"1:842761118644:web:ddef65fd892486f67f88e1"
};
const app=initializeApp(cfg), auth=getAuth(app), db=getDatabase(app);
onAuthStateChanged(auth,user=>{
    if(!user){window.location.href='login.php';return;}
    const map={
        'sensors/temperature/latest':['liveTemp','°C',1],
        'sensors/humidity/latest':   ['liveHum','%',1],
        'sensors/gas/latest':        ['liveGas',' ppm',2],
        'sensors/ph/latest':         ['livePH','',1],
        'sensors/weight/latest':     ['liveWeight',' kg',4],
    };
    Object.entries(map).forEach(([path,[id,unit,dec]])=>{
        onValue(ref(db,path),s=>{
            const el=document.getElementById(id);
            if(el) el.textContent=(parseFloat(s.val())||0).toFixed(dec)+unit;
        });
    });
    let cw=<?php echo (float)$weight;?>,iw=<?php echo (float)$initWeight;?>;
    function upd(){
        const l=iw>0?Math.max(0,((iw-cw)/iw)*100):0;
        const el=document.getElementById('liveWeightLoss');
        if(el) el.textContent=l.toFixed(1)+'%';
        const bar=document.getElementById('wlBar');
        if(bar) bar.style.width=Math.min(l,100)+'%';
    }
    onValue(ref(db,'sensors/weight/latest'),s=>{cw=parseFloat(s.val())||0;upd();});
    onValue(ref(db,'sensors/weight/initial'),s=>{iw=parseFloat(s.val())||0;upd();});
});
</script>
<?php include_once 'notif_bell.php'; ?>

<style>
:root{--brand:#4CAF50;--brand-dark:#2E7D32;--ink:#333;--panel:#fff;--muted:#555;--bg:#F9FAFB;--ok:#22c55e;--warn:#f59e0b;--crit:#ef4444;}
body{background:var(--bg);font-family:Poppins,system-ui,sans-serif;color:var(--ink);min-height:100vh;}
.main{margin-left:260px;padding:20px;min-height:100vh;}
@media(max-width:768px){.main{margin-left:0;padding:70px 12px 20px;}}
.card{border:none;border-radius:16px;background:var(--panel);box-shadow:0 6px 16px rgba(2,6,23,.06);transition:.2s;}
.card:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(2,6,23,.1);}

.sensor-strip{display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:12px;margin-bottom:20px;}
.sensor-pill{background:#fff;border-radius:14px;padding:14px 10px;text-align:center;
             box-shadow:0 4px 12px rgba(2,6,23,.06);border-top:3px solid var(--brand);}
.s-icon{font-size:20px;margin-bottom:4px;}
.s-label{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:2px;}
.s-value{font-size:15px;font-weight:800;color:#1a2e1a;}

.ring-wrap{position:relative;width:150px;height:150px;flex-shrink:0;}
.ring-wrap svg{transform:rotate(-90deg);}
.ring-center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;}
.ring-pct{font-size:30px;font-weight:900;line-height:1;}
.ring-sub{font-size:11px;color:var(--muted);}

.badge-thermophilic{background:#ffedd5;color:#c2410c;}
.badge-mesophilic{background:#dbeafe;color:#1d4ed8;}
.badge-maturation{background:#f3e8ff;color:#7e22ce;}
.badge-dormant{background:#f1f5f9;color:#475569;}
.badge-toohot{background:#fee2e2;color:#b91c1c;}
.badge-transition{background:#fef9c3;color:#a16207;}
.stage-badge{display:inline-block;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:600;}

.auto-card{border-radius:14px;padding:16px;border-left:4px solid;display:flex;align-items:flex-start;gap:12px;margin-bottom:8px;}
.auto-card.ok{background:#f0fdf4;border-color:var(--ok);}
.auto-card.warn{background:#fffbeb;border-color:var(--warn);}
.auto-card.crit{background:#fef2f2;border-color:var(--crit);animation:alertPulse 1.4s ease infinite;}
@keyframes alertPulse{0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,.2)}50%{box-shadow:0 0 0 8px rgba(239,68,68,0)}}

.rec-item{padding:12px 16px;border-radius:12px;background:#f8fafc;border-left:3px solid var(--brand);font-size:14px;margin-bottom:8px;line-height:1.6;}

.param-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
@media(max-width:480px){.param-grid{grid-template-columns:1fr;}}
.param-chip{background:#f8fafc;border-radius:12px;padding:14px;border-left:3px solid var(--brand);}
.p-label{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;}
.p-value{font-size:13px;font-weight:600;color:#1a2e1a;line-height:1.4;}

.section-title{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;font-weight:700;margin-bottom:12px;}
.wl-bar-wrap{height:6px;background:#e9ecef;border-radius:6px;overflow:hidden;margin-top:6px;}
.wl-bar{height:100%;background:linear-gradient(90deg,#81C784,#4CAF50);border-radius:6px;transition:width .9s ease;}

.btn-save{background:linear-gradient(135deg,var(--brand-dark),var(--brand));color:#fff;border:none;
          border-radius:12px;padding:12px 24px;font-size:14px;font-weight:700;cursor:pointer;
          transition:.2s;font-family:Poppins,sans-serif;box-shadow:0 4px 16px rgba(76,175,80,.25);width:100%;}
.btn-save:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(76,175,80,.35);}

.phase-pill{padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;}
.phase-aerobic{background:#dcfce7;color:#15803d;}
.phase-anaerobic{background:#fef9c3;color:#a16207;}
.ml-badge{background:linear-gradient(135deg,#1e3a8a,#3b82f6);color:#fff;font-size:11px;padding:3px 10px;border-radius:20px;font-weight:600;}
.ml-badge-warn{background:linear-gradient(135deg,#78350f,#f59e0b);}
</style>
</head>
<body>
<?php include 'sideabr.php'; ?>

<div class="main">
<?php include 'topnav.php'; ?>

<div style="max-width:900px;margin:0 auto;">

    <!-- Header -->
    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <div style="width:44px;height:44px;background:linear-gradient(135deg,#166534,#4ade80);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;box-shadow:0 4px 14px rgba(74,222,128,.3);">🧠</div>
        <div>
            <h4 class="mb-0 fw-bold">AI Compost Advisor</h4>
            <div style="font-size:11px;color:var(--muted);letter-spacing:.08em;text-transform:uppercase;">Garden Waste · Local ML · No External API</div>
        </div>
        <div class="ms-auto d-flex gap-2 flex-wrap">
            <span class="phase-pill <?php echo $phase==='aerobic'?'phase-aerobic':'phase-anaerobic';?>">
                <?php echo $phase==='aerobic'?'🌀 Aerobic Phase':'🫧 Anaerobic Phase';?>
            </span>
            <span style="background:#dcfce7;color:#166534;padding:5px 12px;border-radius:20px;font-size:11px;font-weight:600;">🌿 Garden Waste</span>
        </div>
    </div>

    <!-- Sensors -->
    <div class="sensor-strip">
        <div class="sensor-pill"><div class="s-icon">🌡️</div><div class="s-label">Temperature</div><div class="s-value" id="liveTemp"><?php echo number_format((float)$temp,1);?>°C</div></div>
        <div class="sensor-pill"><div class="s-icon">💧</div><div class="s-label">Humidity</div><div class="s-value" id="liveHum"><?php echo number_format((float)$humidity,1);?>%</div></div>
        <div class="sensor-pill"><div class="s-icon">💨</div><div class="s-label">Gas / CO₂</div><div class="s-value" id="liveGas"><?php echo number_format((float)$gas,2);?> ppm</div></div>
        <div class="sensor-pill"><div class="s-icon">⚗️</div><div class="s-label">pH Level</div><div class="s-value" id="livePH"><?php echo number_format((float)$ph,1);?></div></div>
        <div class="sensor-pill"><div class="s-icon">⚖️</div><div class="s-label">Weight</div><div class="s-value" id="liveWeight"><?php echo number_format((float)$weight,4);?> kg</div></div>
        <div class="sensor-pill"><div class="s-icon">📉</div><div class="s-label">Weight Loss</div><div class="s-value" id="liveWeightLoss"><?php echo $weightLoss;?>%</div></div>
    </div>

    <!-- Weight bar -->
    <div class="card p-3 mb-3">
        <div class="d-flex justify-content-between mb-1">
            <small class="fw-semibold" style="color:var(--muted);">Decomposition Progress (Weight Loss)</small>
            <small class="fw-bold text-success"><?php echo $weightLoss;?>%</small>
        </div>
        <div class="wl-bar-wrap"><div class="wl-bar" id="wlBar" style="width:<?php echo min($weightLoss,100);?>%"></div></div>
        <div class="d-flex justify-content-between mt-1">
            <small class="text-muted">Initial: <?php echo number_format((float)$initWeight,4);?> kg</small>
            <small class="text-muted">Current: <?php echo number_format((float)$weight,4);?> kg</small>
        </div>
    </div>

    <!-- Readiness + ML Prediction -->
    <div class="row g-3 mb-3">
        <div class="col-md-5">
            <div class="card p-4 h-100 d-flex align-items-center gap-3 flex-row">
                <div class="ring-wrap">
                    <svg width="150" height="150" viewBox="0 0 150 150">
                        <circle cx="75" cy="75" r="62" fill="none" stroke="#e9ecef" stroke-width="13"/>
                        <circle cx="75" cy="75" r="62" fill="none" stroke="<?php echo $ringColor;?>" stroke-width="13"
                                stroke-linecap="round"
                                stroke-dasharray="<?php echo round($circ,2);?>"
                                stroke-dashoffset="<?php echo round($offset,2);?>"/>
                    </svg>
                    <div class="ring-center">
                        <div class="ring-pct" style="color:<?php echo $ringColor;?>"><?php echo round($readiness);?>%</div>
                        <div class="ring-sub">readiness</div>
                    </div>
                </div>
                <div>
                    <div class="section-title">Compost Stage</div>
                    <span class="stage-badge badge-<?php echo $stageKey;?> mb-2 d-inline-block"><?php echo $stageName;?></span>
                    <div style="font-size:12px;color:var(--muted);line-height:1.5;"><?php echo $stageDesc;?></div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span style="font-size:18px;">🧠</span>
                    <span class="section-title mb-0">k-NN Prediction from Past Sessions</span>
                    <span class="ms-auto <?php echo $sessionCount>0?'ml-badge':'ml-badge ml-badge-warn';?>">
                        <?php echo $sessionCount;?> session<?php echo $sessionCount!==1?'s':'';?>
                    </span>
                </div>

                <?php if ($predictedDays !== null): ?>
                <div class="mb-3">
                    <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;">Estimated Days to Ready</div>
                    <div style="font-size:36px;font-weight:900;color:var(--brand-dark);line-height:1.1;">
                        ~<?php echo $predictedDays;?> <span style="font-size:16px;font-weight:500;">days</span>
                    </div>
                    <div style="font-size:12px;color:var(--muted);">Based on <?php echo $sessionCount;?> past session<?php echo $sessionCount!==1?'s':'';?> via weighted k-NN.</div>
                </div>
                <?php else: ?>
                <div class="mb-3" style="background:#f8fafc;border-radius:12px;padding:16px;text-align:center;">
                    <div style="font-size:24px;margin-bottom:8px;">📊</div>
                    <div style="font-weight:600;margin-bottom:4px;">No Past Sessions Yet</div>
                    <div style="font-size:12px;color:var(--muted);line-height:1.6;">Complete a composting cycle then click <strong>"Save Session"</strong> to train the AI. More sessions = smarter predictions.</div>
                </div>
                <?php endif; ?>

                <div style="background:#f0fdf4;border-radius:12px;padding:14px;border:1px solid #bbf7d0;">
                    <div style="font-size:12px;font-weight:600;color:#166534;margin-bottom:10px;">📝 Save Session as Training Data</div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label style="font-size:11px;color:var(--muted);">Days it actually took</label>
                            <input type="number" id="actualDays" min="1" max="365" placeholder="e.g. 42"
                                   class="form-control form-control-sm" style="border-radius:8px;">
                        </div>
                        <div class="col-6">
                            <label style="font-size:11px;color:var(--muted);">Outcome</label>
                            <select id="outcomeSelect" class="form-select form-select-sm" style="border-radius:8px;">
                                <option value="success">✅ Successful</option>
                                <option value="partial">⚠️ Partial</option>
                                <option value="failed">❌ Failed</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn-save" onclick="saveSession()">💾 &nbsp;Save Session to Train AI</button>
                    <div id="saveMsg" style="display:none;font-size:12px;margin-top:8px;text-align:center;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Automation Decisions -->
    <div class="card p-4 mb-3">
        <div class="section-title">⚡ Automated Control Decisions</div>
        <?php foreach ($automations as $a): ?>
        <div class="auto-card <?php echo $a['severity'];?>">
            <div style="font-size:22px;flex-shrink:0;"><?php echo $a['icon'];?></div>
            <div>
                <div style="font-weight:700;font-size:13px;"><?php echo $a['label'];?>
                    <span style="font-size:11px;font-weight:800;padding:2px 10px;border-radius:10px;margin-left:8px;
                        background:<?php echo $sColors[$a['severity']];?>22;color:<?php echo $sColors[$a['severity']];?>;">
                        <?php echo $a['action'];?>
                    </span>
                </div>
                <div style="font-size:12px;color:var(--muted);margin-top:2px;"><?php echo $a['reason'];?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Recommendations -->
    <div class="card p-4 mb-3">
        <div class="section-title">💡 AI Recommendations</div>
        <?php foreach ($recommendations as $r): ?>
            <div class="rec-item"><?php echo htmlspecialchars($r);?></div>
        <?php endforeach; ?>
    </div>

    <!-- Params -->
    <div class="card p-4 mb-3">
        <div class="section-title">🎛️ Adaptive Parameters</div>
        <div class="param-grid">
            <div class="param-chip"><div class="p-label">🌬️ Aeration Frequency</div><div class="p-value"><?php echo $aerationFreq;?></div></div>
            <div class="param-chip"><div class="p-label">⚙️ Mixer Cycles / Day</div><div class="p-value"><?php echo $mixerCycles;?></div></div>
            <div class="param-chip"><div class="p-label">💧 Moisture Action</div><div class="p-value"><?php echo $moistureAct;?></div></div>
            <div class="param-chip"><div class="p-label">⚗️ pH Action</div><div class="p-value"><?php echo $phAction;?></div></div>
        </div>
    </div>

    <!-- Hybrid Phase -->
    <div class="card p-4 mb-4">
        <div class="section-title">🔄 Hybrid Process Status</div>
        <div class="row g-3">
            <div class="col-md-6">
                <div style="background:<?php echo $phase==='aerobic'?'#f0fdf4':'#f8fafc';?>;border:1.5px solid <?php echo $phase==='aerobic'?'#86efac':'#e2e8f0';?>;border-radius:12px;padding:14px;">
                    <div style="font-weight:700;margin-bottom:4px;">🌀 Aerobic Phase</div>
                    <div style="font-size:12px;color:var(--muted);line-height:1.6;">High oxygen and mixing motor active. Normal conditions: temp 20–70°C, pH 6.0–8.5, gas &lt;800 ppm.</div>
                    <?php if($phase==='aerobic'):?><span style="font-size:11px;background:#dcfce7;color:#166534;padding:3px 10px;border-radius:10px;font-weight:700;margin-top:8px;display:inline-block;">▶ ACTIVE NOW</span><?php endif;?>
                </div>
            </div>
            <div class="col-md-6">
                <div style="background:<?php echo $phase==='anaerobic'?'#fffbeb':'#f8fafc';?>;border:1.5px solid <?php echo $phase==='anaerobic'?'#fde68a':'#e2e8f0';?>;border-radius:12px;padding:14px;">
                    <div style="font-weight:700;margin-bottom:4px;">🫧 Anaerobic Phase</div>
                    <div style="font-size:12px;color:var(--muted);line-height:1.6;">Triggered when gas ≥800 ppm AND pH &lt;6.0. Motor engages to restore aerobic conditions.</div>
                    <?php if($phase==='anaerobic'):?><span style="font-size:11px;background:#fef9c3;color:#a16207;padding:3px 10px;border-radius:10px;font-weight:700;margin-top:8px;display:inline-block;">▶ ACTIVE NOW</span><?php endif;?>
                </div>
            </div>
        </div>
    </div>

</div>
</div>

<script>
async function saveSession() {
    const days    = parseInt(document.getElementById('actualDays').value);
    const outcome = document.getElementById('outcomeSelect').value;
    const msg     = document.getElementById('saveMsg');
    if (!days || days < 1) {
        msg.style.display='block'; msg.style.color='#ef4444';
        msg.textContent='⚠️ Please enter the number of days it took.'; return;
    }
    const payload = {
        temp:        <?php echo (float)$temp;?>,
        humidity:    <?php echo (float)$humidity;?>,
        gas:         <?php echo (float)$gas;?>,
        ph:          <?php echo (float)$ph;?>,
        weightLoss:  <?php echo (float)$weightLoss;?>,
        daysToReady: days,
        outcome:     outcome,
        readiness:   <?php echo round($readiness,1);?>,
        savedAt:     new Date().toISOString(),
    };
    try {
        const res  = await fetch('save_compost_session.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
        const data = await res.json();
        if (data.success) {
            msg.style.display='block'; msg.style.color='#166534';
            msg.textContent='✅ Session saved! AI will use this for future predictions.';
            document.getElementById('actualDays').value='';
        } else { throw new Error(data.error||'Save failed'); }
    } catch(e) {
        msg.style.display='block'; msg.style.color='#ef4444';
        msg.textContent='❌ '+e.message;
    }
}
</script>
</body>
</html>