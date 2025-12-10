<?php
// humidity.php
// Elder-friendly + language switcher (English <-> Filipino)
// Replace your existing humidity.php with this file.

if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = new mysqli("localhost", "root", "", "wattawaste_system");
    if ($conn->connect_error) {
        // Fail early but show friendly message for debugging
        die("Connection failed: " . htmlspecialchars($conn->connect_error, ENT_QUOTES, 'UTF-8'));
    }
}

// Helper: safe escape
function e($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

// Fetch latest humidity row (defensive)
$latestHumidityRow = null;
if ($q = $conn->query("SELECT * FROM humidity ORDER BY Humid_Id DESC LIMIT 1")) {
    $latestHumidityRow = $q->fetch_assoc();
}

// Normalize values with fallbacks
$currentHumidity = $latestHumidityRow && isset($latestHumidityRow['Humid_Lvl'])
    ? floatval($latestHumidityRow['Humid_Lvl']) : 0.0;

$capacity = $latestHumidityRow && isset($latestHumidityRow['Humid_Cap'])
    ? floatval($latestHumidityRow['Humid_Cap']) : 100.0;

// created_at fallback to now if not present
$created_at = $latestHumidityRow && !empty($latestHumidityRow['created_at'])
    ? $latestHumidityRow['created_at'] : date("Y-m-d H:i:s");

// server-side status (fallback)
if ($currentHumidity >= 70) {
    $server_status_key = "critical";
} elseif ($currentHumidity > 60) {
    $server_status_key = "optimal";
} else {
    $server_status_key = "warning";
}

// Fetch recent messages for popup (last 8) - defensive
$messages = [];
if ($messagesRes = $conn->query("SELECT * FROM reports ORDER BY date_created DESC LIMIT 8")) {
    while ($m = $messagesRes->fetch_assoc()) {
        $messages[] = $m;
    }
}

// For history table (latest 10 entries)
$historyRows = [];
if ($res = $conn->query("SELECT * FROM humidity ORDER BY Humid_Id DESC LIMIT 10")) {
    while ($r = $res->fetch_assoc()) {
        $historyRows[] = $r;
    }
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>WattAWaste — Humidity</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php include 'sideabr.php'; ?> <!-- ✅ Make sure file name is correct -->
 <?php include 'notif_bell.php'; ?>
<!-- FontAwesome -->
<script src="https://kit.fontawesome.com/a2e0e6ad65.js" crossorigin="anonymous"></script>
<style>
/* ---------- Compact elder-friendly styles ---------- */
:root{--bg:#f6fbf7;--muted:#64748b;--panel:#fff}
body{font-family:Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial; margin:0;background:var(--bg);color:#0f172a}

.header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px}
.header h1{font-size:1.15rem;margin:0}
.lang-select{display:flex;gap:10px;align-items:center}
.lang-select select{padding:8px 10px;border-radius:8px;border:1px solid #e5e7eb;font-size:14px}

/* Banner */
.status-banner{padding:18px;border-radius:12px;font-size:20px;font-weight:800;text-align:center;color:#000;margin-bottom:22px;box-shadow:0 6px 24px rgba(2,6,23,0.04)}

/* Card / gauge */
.humidity-card{background:var(--panel);padding:20px;border-radius:16px;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,0.06);max-width:980px;margin:0 auto 18px}
.humidity-card h3{font-size:22px;margin-bottom:8px;font-weight:800}
.humidity-big{font-size:72px;font-weight:900;margin-top:6px;color:#0b1220}
.humidity-sub{font-size:16px;margin-top:8px;color:#374151}

/* Gauge */
.gauge{width:240px;height:120px;margin:6px auto 8px auto;overflow:hidden}
.gauge-body{width:100%;height:240px;background:#e6e9ea;border-radius:100% 100% 0 0;position:relative}
.gauge-fill{width:100%;height:100%;background:green;transform-origin:center bottom;transform:rotate(0deg);transition:transform .5s ease, background .35s ease;border-radius:100% 100% 0 0}
.gauge-cover{width:160px;height:80px;background:white;position:absolute;top:70px;left:50%;transform:translateX(-50%);border-radius:100px 100px 0 0;font-size:22px;font-weight:800;line-height:80px;color:#0b1220}

.humidity-status{margin-top:12px;font-size:22px;font-weight:800}

.templegend{margin:12px auto;max-width:980px;background:var(--panel);padding:10px;border-radius:10px;border:1px solid #eef2f2;display:flex;gap:12px;justify-content:center}
.history{max-width:980px;margin:18px auto 60px auto}
.data-table{width:100%;border-collapse:collapse;background:var(--panel);border-radius:8px;overflow:hidden;box-shadow:0 6px 20px rgba(2,6,23,0.04)}
.data-table th{background:#24303a;color:#fff;padding:12px;text-align:left;font-weight:700}
.data-table td{padding:12px;border-bottom:1px solid #f1f5f9;color:#0f172a}
.data-table tr:hover td{background:#fbfdfc}

@media(max-width:1000px){
  .gauge{width:70%;height:auto}
  .humidity-big{font-size:48px}
}
</style>
</head>
<body>

<div class="main">
 <?php include 'topnav.php'; ?>
  <!-- big status banner -->
  <div id="statusBanner" class="status-banner" role="status" aria-live="polite"></div>

  <!-- humidity card -->
  <div class="humidity-card" role="region" aria-label="Humidity meter">
    <h3 id="labelHumidityLevel">Humidity Level</h3>

    <div class="gauge" aria-hidden="true">
      <div class="gauge-body">
        <div class="gauge-fill" id="humidityFill" aria-hidden="true"></div>
        <div class="gauge-cover" id="humidityValue"><?= e(number_format($currentHumidity,1)) ?>%</div>
      </div>
    </div>

    <div class="humidity-big" id="bigHumidity"><?= e(number_format($currentHumidity,1)) ?>%</div>
    <div class="humidity-sub" id="subText"><?= e("Recorded at: {$created_at}") ?></div>
    <div id="humidityStatus" class="humidity-status" aria-live="polite"></div>
  </div>

  <!-- legend -->
  <div class="templegend" id="legend">
    <div style="width:100%;text-align:center;font-weight:700">🟢 Optimal (61% - 69%) &nbsp;&nbsp; 🟡 Warning (&lt;=60%) &nbsp;&nbsp; 🔴 Critical (≥70%)</div>
  </div>

  <!-- history -->
  <div class="history">
    <h3 id="historyTitle">Humidity History</h3>
    <table class="data-table" aria-live="polite">
      <thead>
        <tr>
          <th id="thId">ID</th>
          <th id="thHum">Humidity</th>
          <th id="thStatus">Status</th>
          <th id="thRecorded">Recorded At</th>
        </tr>
      </thead>
      <tbody id="historyBody">
        <?php if (count($historyRows) > 0): ?>
          <?php foreach($historyRows as $r):
            $hval = isset($r['Humid_Lvl']) ? floatval($r['Humid_Lvl']) : 0.0;
            if ($hval >= 70) { $c = "critical"; $st = "Critical"; $col = "#991b1b"; }
            elseif ($hval > 60) { $c = "optimal"; $st = "Optimal"; $col = "#166534"; }
            else { $c = "warning"; $st = "Warning"; $col = "#92400e"; }
          ?>
          <tr>
            <td><?= e($r['Humid_Id']) ?></td>
            <td><?= e(number_format($hval,1)) ?>%</td>
            <td><span style="font-weight:700;color:<?= e($col) ?>;"><?= e($st) ?></span></td>
            <td><?= e($r['created_at'] ?? '') ?></td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="4" style="text-align:center;color:<?= e('--' ) ?>;padding:18px; color:var(--muted)">No history yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>


<script>
/* -------------------------
   LANGUAGE STRINGS (client-side)
   ------------------------- */
const STRINGS = {
  en: {
    pageTitle: "🌱 WattAWaste — Humidity",
    labelHumidityLevel: "Humidity Level",
    recordedAt: "Recorded at:",
    historyTitle: "Humidity History",
    thId: "ID",
    thHum: "Humidity",
    thStatus: "Status",
    thRecorded: "Recorded At",
    status_optimal: "🟢 OPTIMAL — OKAY",
    status_warning: "🟡 WARNING — MODERATELY HIGH",
    status_critical: "🔴 CRITICAL — TOO WET",
    legend: "🟢 Optimal (61% - 69%) &nbsp;&nbsp; 🟡 Warning (<=60%) &nbsp;&nbsp; 🔴 Critical (≥70%)"
  },
  ph: {
    pageTitle: "🌱 WattAWaste — Halumigmig",
    labelHumidityLevel: "Antas ng Halumigmig",
    recordedAt: "Naitala noong:",
    historyTitle: "Kasaysayan ng Halumigmig",
    thId: "ID",
    thHum: "Halumigmig",
    thStatus: "Kalagayan",
    thRecorded: "Naitala",
    status_optimal: "🟢 OKAY — NASA TAMANG ANTAS",
    status_warning: "🟡 BABALA — MEDYO MATAAS",
    status_critical: "🔴 DELIKADO — SOBRANG BASA",
    legend: "🟢 Optimal (61% - 69%) &nbsp;&nbsp; 🟡 Babala (<=60%) &nbsp;&nbsp; 🔴 Delikado (≥70%)"
  }
};

/* apply language (UI) */
function applyLanguage(lang) {
  const s = STRINGS[lang] || STRINGS.en;
  document.getElementById('pageTitle').textContent = s.pageTitle;
  document.getElementById('labelHumidityLevel').textContent = s.labelHumidityLevel;
  // Update recorded text label while preserving timestamp value (parse existing timestamp)
  const sub = document.getElementById('subText');
  let ts = '';
  if (sub) {
    const match = sub.textContent.match(/(\d{4}-\d{2}-\d{2}.*$)/); // naive ISO-ish match
    ts = match ? match[1] : sub.textContent.replace(/^[^\d]*/, '').trim();
    sub.textContent = `${s.recordedAt} ${ts}`;
  }
  document.getElementById('historyTitle').textContent = s.historyTitle;
  document.getElementById('thId').textContent = s.thId;
  document.getElementById('thHum').textContent = s.thHum;
  document.getElementById('thStatus').textContent = s.thStatus;
  document.getElementById('thRecorded').textContent = s.thRecorded;
  document.getElementById('legend').innerHTML = `<div style="width:100%;text-align:center;font-weight:700">${s.legend}</div>`;
}

/* Save / load language */
function setLanguage(lang) {
  try { localStorage.setItem('watta_lang', lang); } catch(e){}
  const sel = document.getElementById('langSelect');
  if (sel) sel.value = lang;
  applyLanguage(lang);
  // update banner text immediately
  updateBannerFromValue(currentHum);
}

document.getElementById('langSelect').addEventListener('change', (e) => {
  setLanguage(e.target.value);
});

// apply saved language on load
const savedLang = (function(){ try { return localStorage.getItem('watta_lang') || 'en'; } catch(e){ return 'en'; } })();
document.getElementById('langSelect').value = savedLang;
applyLanguage(savedLang);

/* -------------------------
   HUMIDITY DISPLAY + AUTO-REFRESH
   ------------------------- */
let currentHum = parseFloat(<?= json_encode($currentHumidity) ?>) || 0;
let currentCreated = "<?= e($created_at) ?>";

// DOM refs
const fillEl = document.getElementById('humidityFill');
const coverEl = document.getElementById('humidityValue');
const bigEl = document.getElementById('bigHumidity');
const statusEl = document.getElementById('humidityStatus');
const bannerEl = document.getElementById('statusBanner');
const subTextEl = document.getElementById('subText');

function updateBannerFromValue(hum) {
  const lang = (function(){ try { return localStorage.getItem('watta_lang') || 'en'; } catch(e){ return 'en'; } })();
  const S = STRINGS[lang] || STRINGS.en;
  // set banner + colors + status element
  if (hum >= 70) {
    statusEl.textContent = S.status_critical;
    statusEl.style.color = "#8b1010";
    bannerEl.style.background = "#ffdede";
    bannerEl.style.color = "#6b0f0f";
    bannerEl.textContent = S.status_critical;
    fillEl.style.background = "#ef4444";
  } else if (hum > 60) {
    statusEl.textContent = S.status_optimal;
    statusEl.style.color = "#0b6b2f";
    bannerEl.style.background = "#dcfce7";
    bannerEl.style.color = "#07543a";
    bannerEl.textContent = S.status_optimal;
    fillEl.style.background = "#10b981";
  } else {
    statusEl.textContent = S.status_warning;
    statusEl.style.color = "#7c4a00";
    bannerEl.style.background = "#fff7e6";
    bannerEl.style.color = "#7a4a00";
    bannerEl.textContent = S.status_warning;
    fillEl.style.background = "#f59e0b";
  }
}

function updateUI(humid, created_at_local) {
  currentHum = parseFloat(humid) || 0;
  currentCreated = created_at_local || currentCreated;

  // Gauge rotation 0..180
  let rotation = (currentHum / 100) * 180;
  rotation = Math.max(0, Math.min(180, rotation));
  if (fillEl) fillEl.style.transform = `rotate(${rotation}deg)`;

  if (coverEl) coverEl.textContent = currentHum.toFixed(1) + "%";
  if (bigEl) bigEl.textContent = currentHum.toFixed(1) + "%";

  if (subTextEl) subTextEl.textContent = `${STRINGS[localStorage.getItem('watta_lang')||'en'].recordedAt} ${currentCreated}`;

  // banner + status
  updateBannerFromValue(currentHum);
}

// initial render
updateUI(currentHum, currentCreated);

// Poll server every 8 seconds for fresh humidity (endpoint expected to return JSON { humid, cap, created } )
let refreshTimer = setInterval(refreshHumidity, 8000);
async function refreshHumidity() {
  try {
    const res = await fetch('get_humidity.php', { cache: 'no-store' });
    if (!res.ok) return;
    const data = await res.json();
    if (!data) return;

    const humid = parseFloat(data.humid ?? data.hum ?? data.Humid_Lvl) || 0;
    const created = data.created || data.created_at || data.timestamp || currentCreated;

    // update if value changed meaningfully or timestamp changed
    if (Math.abs(humid - currentHum) > 0.05 || created !== currentCreated) {
      updateUI(humid, created);
    }
  } catch (err) {
    console.log("refreshHumidity error:", err);
    // keep banner if offline - do nothing
  }
}

/* -------------------------
   Messages popup + live count
   ------------------------- */
const messageBtn = document.getElementById('messageBtn');
const messagePopup = document.getElementById('messagePopup');
const closeMsg = document.getElementById('closeMsg');
const messagesEl = document.getElementById('messages');
const sortSelect = document.getElementById('sortSelect');

if (messageBtn) {
  messageBtn.addEventListener('click', () => {
    messagePopup.style.display = 'flex';
    messagePopup.setAttribute('aria-hidden','false');
    loadMessages();
    const b = document.getElementById('msgCount');
    if (b) b.style.display = 'none';
  });
}
if (closeMsg) closeMsg.addEventListener('click', ()=> {
  messagePopup.style.display = 'none';
  messagePopup.setAttribute('aria-hidden','true');
});

async function loadMessages() {
  try {
    const sort = sortSelect ? sortSelect.value || 'desc' : 'desc';
    const res = await fetch(`get_messages.php?sort=${encodeURIComponent(sort)}`, {cache:'no-store'});
    if (!res.ok) throw new Error('Network');
    const data = await res.json();
    messagesEl.innerHTML = '';
    if (!data || data.length === 0) {
      messagesEl.innerHTML = '<div style="padding:12px;color:#64748b;text-align:center">No messages yet.</div>';
      return;
    }
    data.forEach(m => {
      const div = document.createElement('div');
      div.className = 'msg-item';
      const body = (m.Summary || m.message_text || m.Query || '');
      const ts = m.date_created || m.created_at || '';
      div.innerHTML = `<div>${(body+'').replace(/\n/g,'<br/>')}</div>${ts? '<time>' + ts + '</time>':''}`;
      messagesEl.appendChild(div);
    });
  } catch(e) {
    messagesEl.innerHTML = '<div style="padding:12px;color:#b91c1c;text-align:center">Error loading messages.</div>';
  }
}

// refresh message count badge
setInterval(()=> {
  fetch('get_messages.php?count=1', {cache:'no-store'}).then(r=>r.json()).then(j=>{
    if (!j) return;
    const c = j.count || 0;
    const badge = document.getElementById('msgCount');
    if (!badge) return;
    if (c>0) { badge.style.display='inline-block'; badge.textContent = c; } else { badge.style.display='none'; }
  }).catch(()=>{});
}, 12000);

/* Ensure language banner correct on load */
applyLanguage(savedLang);
updateUI(currentHum, currentCreated);
</script>
</body>
</html>
