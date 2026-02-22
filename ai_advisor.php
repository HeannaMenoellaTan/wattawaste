<?php
require_once 'firebase_config.php';
$database = getDatabase();

$temp       = $database->getReference("sensors/temperature/latest")->getValue() ?? 0;
$humidity   = $database->getReference("sensors/humidity/latest")->getValue() ?? 0;
$gas        = $database->getReference("sensors/gas/latest")->getValue() ?? 0;
$ph         = $database->getReference("sensors/ph/latest")->getValue() ?? 0;
$weight     = $database->getReference("sensors/weight/latest")->getValue() ?? 0;
$initWeight = $database->getReference("sensors/weight/initial")->getValue() ?? $weight;

$weightLoss = ($initWeight > 0)
    ? max(0, round((($initWeight - $weight) / $initWeight) * 100, 2))
    : 0;
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

<!-- Firebase Auth + Realtime listeners -->
<script type="module">
import { initializeApp }               from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
import { getAuth, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
import { getDatabase, ref, onValue }   from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-database.js';

const firebaseConfig = {
    apiKey:            "AIzaSyAu9hOwjiuAl9PCh50HefMGZU9XDosu68I",
    authDomain:        "wattawaste-d3503.firebaseapp.com",
    databaseURL:       "https://wattawaste-d3503-default-rtdb.asia-southeast1.firebasedatabase.app",
    projectId:         "wattawaste-d3503",
    storageBucket:     "wattawaste-d3503.firebasestorage.app",
    messagingSenderId: "842761118644",
    appId:             "1:842761118644:web:ddef65fd892486f67f88e1"
};

const app  = initializeApp(firebaseConfig);
const auth = getAuth(app);
const db   = getDatabase(app);

onAuthStateChanged(auth, (user) => {
    if (!user) { window.location.href = 'login.php'; return; }
    sessionStorage.setItem('userEmail', user.email || user.phoneNumber || '');
    sessionStorage.setItem('userId', user.uid);

    // Map: sensorPath → { displayId, inputId, unit, decimals }
    const sensorMap = {
        'sensors/temperature/latest': { display: 'liveTemp',   input: 'inp_temp',   unit: '°C',  dec: 1 },
        'sensors/humidity/latest':    { display: 'liveHum',    input: 'inp_hum',    unit: '%',   dec: 1 },
        'sensors/gas/latest':         { display: 'liveGas',    input: 'inp_gas',    unit: ' ppm',dec: 2 },
        'sensors/ph/latest':          { display: 'livePH',     input: 'inp_ph',     unit: '',    dec: 1 },
        'sensors/weight/latest':      { display: 'liveWeight', input: 'inp_weight', unit: ' kg', dec: 4 },
        'sensors/weight/initial':     { display: null,         input: 'inp_initW',  unit: '',    dec: 4 },
    };

    Object.entries(sensorMap).forEach(([path, cfg]) => {
        onValue(ref(db, path), (snap) => {
            const val = parseFloat(snap.val()) || 0;

            // Update hidden input
            const inp = document.getElementById(cfg.input);
            if (inp) inp.value = val;

            // Update display pill
            if (cfg.display) {
                const el = document.getElementById(cfg.display);
                if (el) el.textContent = val.toFixed(cfg.dec) + cfg.unit;
            }

            // Recalculate weight loss whenever weight or initial changes
            if (path === 'sensors/weight/latest' || path === 'sensors/weight/initial') {
                const cur  = parseFloat(document.getElementById('inp_weight').value) || 0;
                const init = parseFloat(document.getElementById('inp_initW').value)  || 0;
                const loss = init > 0 ? Math.max(0, ((init - cur) / init) * 100) : 0;
                const lossEl = document.getElementById('liveWeightLoss');
                if (lossEl) lossEl.textContent = loss.toFixed(1) + '%';
                const lossInp = document.getElementById('inp_loss');
                if (lossInp) lossInp.value = loss.toFixed(2);
            }
        });
    });
});
</script>

<?php include_once 'notif_bell.php'; ?>

<style>
:root {
    --brand: #4CAF50; --brand-dark: #2E7D32;
    --ink: #333; --panel: #fff; --muted: #555; --bg: #F9FAFB;
    --ok: #22c55e; --warn: #f59e0b; --crit: #ef4444;
}
body { background: var(--bg); font-family: Poppins, system-ui, sans-serif; color: var(--ink); min-height: 100vh; }

/* ── Layout ── */
.main { margin-left: 260px; padding: 20px; min-height: 100vh; }
@media (max-width: 768px) { .main { margin-left: 0; padding: 70px 12px 20px; } }

/* ── Cards ── */
.card {
    border: none; border-radius: 16px; background: var(--panel);
    box-shadow: 0 6px 16px rgba(2,6,23,.06);
}

/* ── Sensor strip ── */
.sensor-strip {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
    gap: 12px; margin-bottom: 20px;
}
.sensor-pill {
    background: #fff; border-radius: 14px; padding: 14px 10px;
    text-align: center; box-shadow: 0 4px 12px rgba(2,6,23,.06);
    border-top: 3px solid var(--brand);
}
.sensor-pill .s-icon  { font-size: 20px; margin-bottom: 4px; }
.sensor-pill .s-label { font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 2px; }
.sensor-pill .s-value { font-size: 15px; font-weight: 800; color: #1a2e1a; }

/* ── Run button ── */
.btn-ai {
    background: linear-gradient(135deg, var(--brand-dark), var(--brand));
    color: #fff; border: none; border-radius: 14px;
    padding: 16px 28px; font-size: 15px; font-weight: 700;
    width: 100%; cursor: pointer; transition: all .2s;
    box-shadow: 0 4px 20px rgba(76,175,80,.25); font-family: Poppins, sans-serif;
}
.btn-ai:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(76,175,80,.4); }
.btn-ai:disabled { opacity: .55; cursor: not-allowed; transform: none; }

/* ── Spinner ── */
.spinner-wrap { display: none; text-align: center; padding: 36px 20px; }
.spinner-wrap.show { display: block; }
.spin-ring {
    width: 48px; height: 48px; margin: 0 auto 14px;
    border: 4px solid #e2e8f0; border-top-color: var(--brand);
    border-radius: 50%; animation: spin .8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Results ── */
.result-wrap { display: none; }
.result-wrap.show { display: block; animation: fadeUp .4s ease; }
@keyframes fadeUp { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }

/* ── Readiness ring ── */
.ring-outer { position: relative; width: 150px; height: 150px; flex-shrink: 0; }
.ring-outer svg { transform: rotate(-90deg); }
.ring-center {
    position: absolute; inset: 0;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.ring-pct   { font-size: 30px; font-weight: 900; line-height: 1; }
.ring-sub   { font-size: 11px; color: var(--muted); }

/* ── Stage badge ── */
.stage-badge {
    display: inline-block; padding: 4px 14px; border-radius: 20px;
    font-size: 12px; font-weight: 600; margin-bottom: 10px;
}
.stage-mesophilic  { background: #dbeafe; color: #1d4ed8; }
.stage-thermophilic{ background: #ffedd5; color: #c2410c; }
.stage-cooling     { background: #fef9c3; color: #a16207; }
.stage-maturation  { background: #f3e8ff; color: #7e22ce; }
.stage-ready       { background: #dcfce7; color: #15803d; }

/* ── Param chips ── */
.param-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
@media (max-width: 480px) { .param-grid { grid-template-columns: 1fr; } }

.param-chip {
    background: #f8fafc; border-radius: 12px; padding: 14px;
    border-left: 3px solid var(--brand); transition: transform .15s;
}
.param-chip:hover { transform: translateY(-2px); }
.param-chip .p-icon  { font-size: 18px; margin-bottom: 4px; }
.param-chip .p-label { font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 4px; }
.param-chip .p-value { font-size: 13px; font-weight: 600; color: #1a2e1a; line-height: 1.4; }

/* ── Narrative ── */
.narrative-box {
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border: 1px solid #bbf7d0; border-radius: 14px; padding: 20px;
    font-size: 14px; line-height: 1.9; color: #14532d;
    white-space: pre-line; min-height: 60px;
}

/* ── Error ── */
.ai-error {
    display: none; background: #fef2f2; border: 1px solid #fecaca;
    border-radius: 12px; padding: 14px 18px; color: #b91c1c;
    font-size: 13px; margin-bottom: 16px;
}
.ai-error.show { display: block; }

/* ── Weight loss bar ── */
.wl-bar-wrap { height: 6px; background: #e9ecef; border-radius: 6px; overflow: hidden; margin-top: 6px; }
.wl-bar      { height: 100%; background: linear-gradient(90deg, #81C784, #4CAF50); border-radius: 6px; transition: width .9s ease; }

/* ── Typing cursor ── */
.typing-cursor::after { content: '▌'; animation: blink .7s step-end infinite; }
@keyframes blink { 50% { opacity: 0; } }
</style>
</head>
<body>

<?php include 'sideabr.php'; ?>

<div class="main">
<?php include 'topnav.php'; ?>

<!-- Hidden inputs — updated live by Firebase listeners -->
<input type="hidden" id="inp_temp"   value="<?php echo (float)$temp; ?>">
<input type="hidden" id="inp_hum"    value="<?php echo (float)$humidity; ?>">
<input type="hidden" id="inp_gas"    value="<?php echo (float)$gas; ?>">
<input type="hidden" id="inp_ph"     value="<?php echo (float)$ph; ?>">
<input type="hidden" id="inp_weight" value="<?php echo (float)$weight; ?>">
<input type="hidden" id="inp_initW"  value="<?php echo (float)$initWeight; ?>">
<input type="hidden" id="inp_loss"   value="<?php echo $weightLoss; ?>">

<div style="max-width:860px; margin: 0 auto;">

    <!-- Page header -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <div style="width:44px;height:44px;background:linear-gradient(135deg,#166534,#4ade80);
                    border-radius:12px;display:flex;align-items:center;justify-content:center;
                    font-size:20px;box-shadow:0 4px 14px rgba(74,222,128,.3);">🤖</div>
        <div>
            <h4 class="mb-0 fw-bold">AI Compost Advisor</h4>
            <div style="font-size:11px;color:var(--muted);letter-spacing:.08em;text-transform:uppercase;">
                Garden Waste · Powered by Claude AI
            </div>
        </div>
        <div class="ms-auto">
            <span style="font-size:11px;background:#dcfce7;color:#166534;padding:5px 12px;
                         border-radius:20px;font-weight:600;">
                🌿 Garden Waste Mode
            </span>
        </div>
    </div>

    <!-- Live sensor strip -->
    <div class="sensor-strip">
        <div class="sensor-pill">
            <div class="s-icon">🌡️</div>
            <div class="s-label">Temperature</div>
            <div class="s-value" id="liveTemp"><?php echo number_format((float)$temp,1); ?>°C</div>
        </div>
        <div class="sensor-pill">
            <div class="s-icon">💧</div>
            <div class="s-label">Humidity</div>
            <div class="s-value" id="liveHum"><?php echo number_format((float)$humidity,1); ?>%</div>
        </div>
        <div class="sensor-pill">
            <div class="s-icon">💨</div>
            <div class="s-label">Gas / CO₂</div>
            <div class="s-value" id="liveGas"><?php echo number_format((float)$gas,2); ?> ppm</div>
        </div>
        <div class="sensor-pill">
            <div class="s-icon">⚗️</div>
            <div class="s-label">pH Level</div>
            <div class="s-value" id="livePH"><?php echo number_format((float)$ph,1); ?></div>
        </div>
        <div class="sensor-pill">
            <div class="s-icon">⚖️</div>
            <div class="s-label">Weight</div>
            <div class="s-value" id="liveWeight"><?php echo number_format((float)$weight,4); ?> kg</div>
        </div>
        <div class="sensor-pill">
            <div class="s-icon">📉</div>
            <div class="s-label">Weight Loss</div>
            <div class="s-value" id="liveWeightLoss"><?php echo $weightLoss; ?>%</div>
        </div>
    </div>

    <!-- Weight loss bar -->
    <div class="card p-3 mb-3">
        <div class="d-flex justify-content-between mb-1">
            <small class="text-muted fw-semibold">Decomposition Progress (Weight Loss)</small>
            <small class="fw-bold text-success" id="wlPctLabel"><?php echo $weightLoss; ?>%</small>
        </div>
        <div class="wl-bar-wrap">
            <div class="wl-bar" id="wlBar" style="width:<?php echo min($weightLoss,100); ?>%"></div>
        </div>
        <div class="d-flex justify-content-between mt-1">
            <small class="text-muted">Initial: <?php echo number_format((float)$initWeight,4); ?> kg</small>
            <small class="text-muted">Current: <span id="wlCurrent"><?php echo number_format((float)$weight,4); ?></span> kg</small>
        </div>
    </div>

    <!-- Error box -->
    <div class="ai-error" id="aiError"></div>

    <!-- Run button -->
    <div class="card p-4 mb-3" id="runCard">
        <p class="text-muted mb-3" style="font-size:14px;">
            Click below to send your current sensor readings to Claude AI. It will calculate a
            <strong>compost readiness score</strong>, identify the current decomposition stage,
            estimate time to completion, and give you specific actions to take today.
        </p>
        <button class="btn-ai" id="runBtn" onclick="runAI()">
            🤖 &nbsp; Predict Compost Readiness with AI
        </button>
    </div>

    <!-- Spinner -->
    <div class="card spinner-wrap" id="spinnerCard">
        <div class="spin-ring"></div>
        <div class="fw-semibold" style="color:var(--brand-dark);">AI Analyzing Your Compost...</div>
        <div class="text-muted" style="font-size:13px;margin-top:4px;">Sending sensor data to Claude · Please wait</div>
    </div>

    <!-- Results -->
    <div class="result-wrap" id="resultWrap">

        <!-- Readiness + Stage -->
        <div class="card p-4 mb-3 d-flex flex-row align-items-center gap-4 flex-wrap">
            <!-- Ring -->
            <div class="ring-outer" id="ringOuter">
                <svg width="150" height="150" viewBox="0 0 150 150">
                    <circle cx="75" cy="75" r="62" fill="none" stroke="#e9ecef" stroke-width="13"/>
                    <circle id="ringArc" cx="75" cy="75" r="62" fill="none"
                            stroke="#4CAF50" stroke-width="13" stroke-linecap="round"
                            stroke-dasharray="389.56" stroke-dashoffset="389.56"
                            style="transition: stroke-dashoffset 1.2s cubic-bezier(.4,0,.2,1);"/>
                </svg>
                <div class="ring-center">
                    <div class="ring-pct" id="ringPct" style="color:#4CAF50;">0%</div>
                    <div class="ring-sub">readiness</div>
                </div>
            </div>

            <!-- Stage + ETA -->
            <div>
                <div class="text-muted mb-1" style="font-size:11px;text-transform:uppercase;letter-spacing:.06em;">Current Stage</div>
                <div class="stage-badge" id="stageBadge">—</div>
                <div class="text-muted mb-1 mt-2" style="font-size:11px;text-transform:uppercase;letter-spacing:.06em;">Estimated Time to Ready</div>
                <div style="font-size:24px;font-weight:900;color:var(--ink);" id="etaDisplay">—</div>
            </div>
        </div>

        <!-- Adaptive Params -->
        <div class="param-grid" id="paramGrid">
            <div class="param-chip"><div class="p-icon">🌬️</div><div class="p-label">Aeration Frequency</div><div class="p-value" id="pAeration">—</div></div>
            <div class="param-chip"><div class="p-icon">⚙️</div><div class="p-label">Mixer Cycles / Day</div><div class="p-value" id="pMixer">—</div></div>
            <div class="param-chip"><div class="p-icon">💧</div><div class="p-label">Moisture Action</div><div class="p-value" id="pMoisture">—</div></div>
            <div class="param-chip"><div class="p-icon">⚗️</div><div class="p-label">pH Action</div><div class="p-value" id="pPH">—</div></div>
        </div>

        <!-- AI Narrative -->
        <div class="card p-4 mb-3">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span style="font-size:16px;">🤖</span>
                <span style="font-size:11px;color:var(--brand-dark);font-weight:700;
                              text-transform:uppercase;letter-spacing:.08em;">AI Assessment</span>
            </div>
            <div class="narrative-box" id="narrativeBox"></div>
        </div>

        <!-- Run again -->
        <button class="btn-ai" style="background:linear-gradient(135deg,#475569,#64748b);
                box-shadow:0 4px 16px rgba(0,0,0,.1);" onclick="resetUI()">
            ↩ &nbsp; Run New Analysis
        </button>

    </div><!-- end result-wrap -->

</div><!-- end max-width container -->
</div><!-- end .main -->

<script>
// ── Typing effect ──────────────────────────────────────────────────────────────
function typeText(el, text, speed = 16) {
    el.classList.add('typing-cursor');
    let i = 0;
    el.textContent = '';
    const t = setInterval(() => {
        el.textContent += text[i++];
        if (i >= text.length) { clearInterval(t); el.classList.remove('typing-cursor'); }
    }, speed);
}

// ── Readiness ring ─────────────────────────────────────────────────────────────
function setRing(pct) {
    const circ   = 2 * Math.PI * 62; // 389.56
    const offset = circ - (pct / 100) * circ;
    const arc    = document.getElementById('ringArc');
    const label  = document.getElementById('ringPct');
    const color  = pct >= 80 ? '#4CAF50' : pct >= 50 ? '#f59e0b' : '#ef4444';
    arc.style.stroke          = color;
    arc.style.strokeDashoffset = offset;
    label.textContent          = pct.toFixed(0) + '%';
    label.style.color          = color;
}

// ── Stage badge ────────────────────────────────────────────────────────────────
const STAGE_CLASS = {
    'Mesophilic Initial':  'stage-mesophilic',
    'Thermophilic Active': 'stage-thermophilic',
    'Cooling Down':        'stage-cooling',
    'Maturation':          'stage-maturation',
    'Ready':               'stage-ready',
};
function setStageBadge(stage) {
    const el = document.getElementById('stageBadge');
    el.textContent  = stage;
    el.className    = 'stage-badge ' + (STAGE_CLASS[stage] || 'stage-maturation');
}

// ── Update weight-loss UI from inputs ─────────────────────────────────────────
function refreshWeightLossUI() {
    const cur  = parseFloat(document.getElementById('inp_weight').value) || 0;
    const init = parseFloat(document.getElementById('inp_initW').value)  || 0;
    const loss = init > 0 ? Math.max(0, ((init - cur) / init) * 100) : 0;
    document.getElementById('liveWeightLoss').textContent = loss.toFixed(1) + '%';
    document.getElementById('inp_loss').value             = loss.toFixed(2);
    document.getElementById('wlBar').style.width          = Math.min(loss, 100) + '%';
    document.getElementById('wlPctLabel').textContent     = loss.toFixed(1) + '%';
    document.getElementById('wlCurrent').textContent      = cur.toFixed(4);
}

// ── Main AI call ───────────────────────────────────────────────────────────────
async function runAI() {
    // Read latest live values from hidden inputs
    const temp       = parseFloat(document.getElementById('inp_temp').value)   || 0;
    const humidity   = parseFloat(document.getElementById('inp_hum').value)    || 0;
    const gas        = parseFloat(document.getElementById('inp_gas').value)     || 0;
    const ph         = parseFloat(document.getElementById('inp_ph').value)      || 0;
    const weight     = parseFloat(document.getElementById('inp_weight').value) || 0;
    const initWeight = parseFloat(document.getElementById('inp_initW').value)  || 0;
    const weightLoss = parseFloat(document.getElementById('inp_loss').value)   || 0;

    // Show spinner, hide everything else
    document.getElementById('runCard').style.display    = 'none';
    document.getElementById('spinnerCard').classList.add('show');
    document.getElementById('resultWrap').classList.remove('show');
    document.getElementById('aiError').classList.remove('show');

    const prompt = `You are the embedded AI for "Leafcycle", a smart IoT compost bin that processes GARDEN WASTE ONLY (leaves, grass clippings, plant trimmings, garden debris — moderate nitrogen, high carbon).

REAL-TIME SENSOR DATA (just fetched from Firebase):
- Temperature : ${temp.toFixed(1)}°C
- Humidity    : ${humidity.toFixed(1)}%
- pH          : ${ph.toFixed(2)}
- Gas / CO₂   : ${gas.toFixed(2)} ppm
- Current Weight : ${weight.toFixed(4)} kg
- Initial Weight : ${initWeight.toFixed(4)} kg
- Weight Loss    : ${weightLoss.toFixed(1)}%

OPTIMAL RANGES FOR GARDEN COMPOST:
  Temperature 45–65°C | Humidity 45–60% | pH 6.5–8.0 | Gas < 600 ppm
  Weight loss of 40–60% typically indicates mature compost.

TASK: Analyze ALL readings together (not just one sensor) and respond ONLY with valid JSON — no markdown, no text outside the JSON:

{
  "readiness_pct": <integer 0-100, your overall readiness score>,
  "stage": "<exactly one of: Mesophilic Initial | Thermophilic Active | Cooling Down | Maturation | Ready>",
  "eta_days": <integer, estimated days until fully composted; 0 if ready>,
  "aeration": "<specific aeration frequency recommendation>",
  "mixer": "<specific mixer cycles per day recommendation>",
  "moisture_action": "<exact action to take for moisture right now>",
  "ph_action": "<exact action to take for pH right now>",
  "narrative": "<3-4 sentences: what the sensor profile reveals about this garden waste batch right now, which single factor needs the most attention, and one concrete action the user should take today. Be direct and specific with numbers.>"
}`;

    try {
        const res = await fetch('https://api.anthropic.com/v1/messages', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                model: 'claude-sonnet-4-20250514',
                max_tokens: 1000,
                messages: [{ role: 'user', content: prompt }]
            })
        });

        const data   = await res.json();
        const raw    = (data.content || []).map(b => b.text || '').join('');
        const clean  = raw.replace(/```json|```/g, '').trim();
        const parsed = JSON.parse(clean);

        // Populate UI
        const pct = Math.min(100, Math.max(0, parseFloat(parsed.readiness_pct) || 0));
        setRing(pct);
        setStageBadge(parsed.stage || 'Maturation');

        const eta = parseInt(parsed.eta_days) || 0;
        document.getElementById('etaDisplay').textContent = eta === 0 ? '✅ Ready now!' : `~${eta} days`;

        document.getElementById('pAeration').textContent = parsed.aeration        || '—';
        document.getElementById('pMixer').textContent    = parsed.mixer           || '—';
        document.getElementById('pMoisture').textContent = parsed.moisture_action || '—';
        document.getElementById('pPH').textContent       = parsed.ph_action       || '—';

        typeText(document.getElementById('narrativeBox'), parsed.narrative || '');

        document.getElementById('spinnerCard').classList.remove('show');
        document.getElementById('resultWrap').classList.add('show');

    } catch (err) {
        document.getElementById('spinnerCard').classList.remove('show');
        document.getElementById('runCard').style.display = 'block';
        const errEl = document.getElementById('aiError');
        errEl.textContent = '❌ AI error: ' + err.message + ' — Check your network or API key.';
        errEl.classList.add('show');
    }
}

// ── Reset ──────────────────────────────────────────────────────────────────────
function resetUI() {
    document.getElementById('resultWrap').classList.remove('show');
    document.getElementById('runCard').style.display = 'block';
    document.getElementById('aiError').classList.remove('show');
}

// Initial weight loss bar render
refreshWeightLossUI();
</script>
</body>
</html>