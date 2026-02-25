<?php
require_once 'firebase_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Humidity Monitor - WattAWaste</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://kit.fontawesome.com/a2e0e6ad65.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script type="module">
import { initializeApp }               from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
import { getAuth, onAuthStateChanged }  from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
import { getDatabase, ref, onValue, query, orderByKey, limitToLast }
    from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-database.js';

const firebaseConfig = {
    apiKey:            "AIzaSyAu9hOwjiuAl9PCh50HefMGZU9XDosu68I",
    authDomain:        "wattawaste-d3503.firebaseapp.com",
    databaseURL:       "https://wattawaste-d3503-default-rtdb.asia-southeast1.firebasedatabase.app",
    projectId:         "wattawaste-d3503",
    storageBucket:     "wattawaste-d3503.firebasestorage.app",
    messagingSenderId: "842761118644",
    appId:             "1:842761118644:web:ddef65fd892486f67f88e1",
    measurementId:     "G-33Z8K3NBY1"
};
const app=initializeApp(firebaseConfig), auth=getAuth(app), database=getDatabase(app);
onAuthStateChanged(auth,(user)=>{
    if(!user){window.location.href='login.html';return;}
    sessionStorage.setItem('userEmail',user.email||user.phoneNumber||'');
    sessionStorage.setItem('userId',user.uid);
    window.initializeHumidityMonitoring();
});
window.firebaseDatabase=database; window.firebaseRef=ref; window.firebaseOnValue=onValue;
window.firebaseQuery=query; window.firebaseOrderByKey=orderByKey; window.firebaseLimitToLast=limitToLast;
</script>

<style>
:root{--brand:#4CAF50;--brand-dark:#2E7D32;--ink:#333;--panel:#fff;--muted:#555;--bg:#F9FAFB;--ok:#22c55e;--warn:#f59e0b;--crit:#ef4444;}
body{background:var(--bg);font-family:Poppins,system-ui,Segoe UI,Arial;color:var(--ink);min-height:100vh;}
.card{border:none;border-radius:16px;background:var(--panel);box-shadow:0 6px 16px rgba(2,6,23,.06);transition:transform .2s,box-shadow .2s;}
.card:hover{transform:translateY(-3px);box-shadow:0 12px 26px rgba(2,6,23,.12);}
.page-title{font-size:28px;font-weight:700;color:var(--ink);margin-bottom:24px;}
.humidity-hero{background:linear-gradient(135deg,#00b4d8 0%,#48cae4 100%);border-radius:20px;padding:40px;color:white;text-align:center;position:relative;overflow:hidden;}
.humidity-hero::before{content:'';position:absolute;top:-50%;right:-50%;width:200%;height:200%;background:radial-gradient(circle,rgba(255,255,255,0.1) 0%,transparent 70%);animation:pulse 3s ease-in-out infinite;}
@keyframes pulse{0%,100%{transform:scale(1);opacity:.5}50%{transform:scale(1.1);opacity:.8}}
.humidity-display-large{font-size:96px;font-weight:900;line-height:1;text-shadow:0 4px 12px rgba(0,0,0,0.2);position:relative;z-index:1;}
.status-badge-large{display:inline-block;padding:12px 30px;border-radius:50px;font-weight:700;font-size:18px;background:rgba(255,255,255,0.9);margin-top:16px;position:relative;z-index:1;}
.status-badge-large.ok{color:var(--ok);}.status-badge-large.warn{color:var(--warn);}.status-badge-large.crit{color:var(--crit);}
.stat-card{text-align:center;padding:20px;}.stat-value{font-size:36px;font-weight:800;color:var(--brand-dark);line-height:1;}.stat-label{font-size:14px;color:var(--muted);margin-top:8px;}
.droplet-visual{width:100px;height:120px;background:linear-gradient(to bottom,#0088ff 0%,#00b4d8 50%,#48cae4 100%);border-radius:50% 50% 50% 50% / 60% 60% 40% 40%;position:relative;margin:0 auto;box-shadow:0 8px 20px rgba(0,180,216,0.3);overflow:hidden;}
.droplet-indicator{position:absolute;top:0;left:0;right:0;background:rgba(255,255,255,0.85);border-radius:50% 50% 0 0 / 60% 60% 0 0;transition:height 1s cubic-bezier(0.4,0,0.2,1);}
.history-item{display:flex;justify-content:space-between;align-items:center;padding:14px 16px;margin-bottom:8px;background:#f8fafc;border-radius:12px;border-left:4px solid #00b4d8;transition:all 0.2s;}
.history-item:hover{background:#f1f5f9;transform:translateX(4px);}
.history-value{font-size:22px;font-weight:700;color:#00688b;}.history-time{font-size:13px;color:var(--muted);}.small-muted{color:var(--muted);font-size:14px;}
.alert-custom{padding:16px 20px;border-radius:12px;border-left:4px solid;font-weight:500;}
.alert-custom.warning{background:#FFF8E1;color:#7A5A00;border-color:#f59e0b;}
.alert-custom.critical{background:#FFE5E5;color:#7F1D1D;border-color:#ef4444;}
.range-btn{border:1.5px solid #dee2e6;background:#fff;border-radius:8px;padding:5px 14px;font-size:.85rem;cursor:pointer;transition:all .15s;}
.range-btn:hover{background:#f1f5f9;}.range-btn.active{background:var(--brand-dark);color:#fff;border-color:var(--brand-dark);}
</style>
</head>
<body>

<?php include 'sideabr.php'; ?>
<div class="main">
<?php include 'topnav.php'; ?>

<div class="container-fluid px-4 py-4">
    <h1 class="page-title">💧 Humidity Monitoring</h1>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="humidity-hero">
                <div class="humidity-display-large" id="humidityDisplay">--%</div>
                <div class="status-badge-large ok" id="statusBadge">Loading...</div>
                <div class="mt-3 small" style="opacity:.9;position:relative;z-index:1;" id="statusDesc">Fetching humidity data from sensors...</div>
                <div class="mt-2 small" style="opacity:.8;position:relative;z-index:1;" id="lastUpdateText">Last updated: --</div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card p-4 h-100 d-flex align-items-center justify-content-center">
                <h6 class="text-center mb-3">Moisture Level</h6>
                <div class="droplet-visual">
                    <div class="droplet-indicator" id="dropletIndicator" style="height:100%;"></div>
                </div>
                <div class="mt-3 text-center small-muted">
                    <div><span style="color:#ef4444;">●</span> ≥70% Critical</div>
                    <div><span style="color:#10b981;">●</span> 61–69% Optimal</div>
                    <div><span style="color:#f59e0b;">●</span> ≤60% Warning</div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert-custom critical mb-4" id="criticalBanner" style="display:none;">
        <i class="fas fa-exclamation-triangle me-2"></i><strong>Critical:</strong> Humidity is too high. Risk of anaerobic conditions and odor.
    </div>
    <div class="alert-custom warning mb-4" id="warningBanner" style="display:none;">
        <i class="fas fa-exclamation-triangle me-2"></i><strong>Warning:</strong> Humidity is too low. May slow decomposition process.
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4"><div class="card stat-card"><i class="fas fa-tint text-danger mb-2" style="font-size:32px;"></i><div class="stat-value" id="maxHumidityStat">--%</div><div class="stat-label">Maximum (selected range)</div></div></div>
        <div class="col-md-4"><div class="card stat-card"><i class="fas fa-chart-line text-success mb-2" style="font-size:32px;"></i><div class="stat-value" id="avgHumidityStat">--%</div><div class="stat-label">Average (selected range)</div></div></div>
        <div class="col-md-4"><div class="card stat-card"><i class="fas fa-droplet text-info mb-2" style="font-size:32px;"></i><div class="stat-value" id="minHumidityStat">--%</div><div class="stat-label">Minimum (selected range)</div></div></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="mb-0"><i class="fas fa-chart-area text-primary me-2"></i>Humidity Trend <span id="readingsCount" class="small-muted fs-6"></span></h5>
                    <!-- 30d REMOVED -->
                    <div class="d-flex gap-1 flex-wrap">
                        <button class="range-btn" data-range="1h">1h</button>
                        <button class="range-btn" data-range="6h">6h</button>
                        <button class="range-btn active" data-range="24h">24h</button>
                        <button class="range-btn" data-range="7d">7d</button>
                    </div>
                </div>
                <canvas id="humidityChart" style="max-height:350px;"></canvas>
                <div id="chartNoData" class="text-center text-muted small mt-2" style="display:none;">No data yet for this range.</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card p-4">
                <h5 class="mb-3"><i class="fas fa-history text-info me-2"></i>Recent Live Readings <span class="small-muted fs-6 ms-1">(latest 20 from Firebase)</span></h5>
                <div id="historyContainer" style="max-height:500px;overflow-y:auto;"><p class="text-muted text-center py-4">Loading humidity history...</p></div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
let humidityChart=null, currentRange='24h';

function updateDropletIndicator(h){
    // Same logic as thermometer: white overlay at top shrinks as value rises
    const inverted=100-Math.min(Math.max(h,0),100);
    document.getElementById('dropletIndicator').style.height=inverted+'%';
}
function getHumidityStatus(h){
    if(h>=70)return{status:'Critical (Too Wet)',statusClass:'crit',desc:'Humidity is too high. Risk of anaerobic conditions.'};
    if(h>=61)return{status:'Optimal',statusClass:'ok',desc:'Perfect moisture level for composting.'};
    return{status:'Warning (Too Dry)',statusClass:'warn',desc:'Humidity is below optimal. May slow decomposition.'};
}
function formatTimestamp(tsMs){const d=new Date(Number(tsMs));if(isNaN(d.getTime()))return'Unknown';return d.toLocaleString('en-US',{month:'short',day:'numeric',year:'numeric',hour:'numeric',minute:'2-digit',hour12:true});}
function getRangeCutoffMs(range){const now=Date.now();const map={'1h':1*60*60*1000,'6h':6*60*60*1000,'24h':24*60*60*1000,'7d':7*24*60*60*1000};return now-(map[range]||map['24h']);}
function updateHumidityDisplay(h){
    const info=getHumidityStatus(h);
    document.getElementById('humidityDisplay').textContent=h.toFixed(1)+'%';
    document.getElementById('statusBadge').textContent=info.status;
    document.getElementById('statusDesc').textContent=info.desc;
    document.getElementById('lastUpdateText').textContent='Last updated: '+new Date().toLocaleString('en-US',{hour:'numeric',minute:'2-digit',month:'short',day:'numeric',year:'numeric'});
    updateDropletIndicator(h);
    const badge=document.getElementById('statusBadge');
    badge.classList.remove('ok','warn','crit');badge.classList.add(info.statusClass);
    document.getElementById('criticalBanner').style.display=h>=70?'block':'none';
    document.getElementById('warningBanner').style.display=(h<40)?'block':'none';
}
function updateStatistics(entries){
    if(!entries.length)return;
    const vals=entries.map(e=>e.value);
    document.getElementById('maxHumidityStat').textContent=Math.max(...vals).toFixed(1)+'%';
    document.getElementById('avgHumidityStat').textContent=(vals.reduce((a,b)=>a+b,0)/vals.length).toFixed(1)+'%';
    document.getElementById('minHumidityStat').textContent=Math.min(...vals).toFixed(1)+'%';
    document.getElementById('readingsCount').textContent=`(${entries.length} readings)`;
}
function renderChart(entries){
    if(humidityChart){humidityChart.destroy();humidityChart=null;}
    const labels=entries.map(e=>formatTimestamp(e.ts)),values=entries.map(e=>e.value);
    const ctx=document.getElementById('humidityChart').getContext('2d');
    const grad=ctx.createLinearGradient(0,0,0,400);grad.addColorStop(0,'rgba(0,180,216,0.3)');grad.addColorStop(1,'rgba(72,202,228,0.05)');
    const step=Math.ceil(labels.length/10),tickLabels=labels.map((l,i)=>i%step===0?l:'');
    humidityChart=new Chart(ctx,{type:'line',data:{labels:tickLabels,datasets:[{label:'Humidity (%)',data:values,
        borderColor:'#00b4d8',backgroundColor:grad,borderWidth:3,tension:0.4,fill:true,
        pointRadius:values.length>50?0:3,pointHoverRadius:6,pointBackgroundColor:'#00b4d8',pointBorderColor:'#fff',pointBorderWidth:2}]},
    options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{display:false},tooltip:{backgroundColor:'rgba(0,0,0,0.8)',padding:12,
        callbacks:{title:(items)=>labels[items[0].dataIndex],label:(ctx)=>' Humidity: '+ctx.parsed.y.toFixed(1)+'%'}}},
    scales:{y:{beginAtZero:true,min:0,max:100,ticks:{callback:v=>v+'%'},grid:{color:'rgba(0,0,0,0.05)'}},x:{ticks:{maxTicksLimit:10,maxRotation:0},grid:{display:false}}}}});
}
function renderHistory(entries){
    const container=document.getElementById('historyContainer');
    if(!entries.length){container.innerHTML='<p class="text-muted text-center py-4">No history data available</p>';return;}
    const recent=entries.slice(-20).reverse();
    container.innerHTML=recent.map(entry=>{
        const val=entry.value;let bc='success',bt='Optimal';
        if(val>=70){bc='danger';bt='Critical';}else if(val<61){bc='warning text-dark';bt='Warning';}
        return`<div class="history-item"><div><div class="history-value">${val.toFixed(1)}%</div><div class="history-time">${formatTimestamp(entry.ts)}</div></div><span class="badge bg-${bc}">${bt}</span></div>`;
    }).join('');
}
function loadHistoryData(range){
    currentRange=range;
    document.querySelectorAll('.range-btn').forEach(b=>b.classList.toggle('active',b.dataset.range===range));
    const db=window.firebaseDatabase,cutoffMs=getRangeCutoffMs(range);
    const histRef=window.firebaseQuery(window.firebaseRef(db,'sensors/humidity/history'),window.firebaseOrderByKey(),window.firebaseLimitToLast(500));
    window.firebaseOnValue(histRef,(snapshot)=>{
        const raw=snapshot.val();if(!raw){showNoData();return;}
        const entries=[];
        Object.entries(raw).forEach(([key,entry])=>{
            let val,tsMs;
            if(typeof entry==='object'&&entry!==null&&'value' in entry){val=parseFloat(entry.value);tsMs=entry.timestamp||parseFloat(key);}
            else{val=parseFloat(entry);tsMs=parseFloat(key);}
            if(!isNaN(val)&&!isNaN(tsMs)&&tsMs>=cutoffMs)entries.push({ts:tsMs,value:val});
        });
        entries.sort((a,b)=>a.ts-b.ts);
        if(!entries.length){showNoData();return;}
        document.getElementById('chartNoData').style.display='none';
        updateStatistics(entries);renderChart(entries);renderHistory(entries);
    },{onlyOnce:true});
}
function showNoData(){
    document.getElementById('chartNoData').style.display='block';
    ['maxHumidityStat','avgHumidityStat','minHumidityStat'].forEach(id=>document.getElementById(id).textContent='--%');
    document.getElementById('readingsCount').textContent='';
    document.getElementById('historyContainer').innerHTML='<p class="text-muted text-center py-4">No data found for this time range.</p>';
}
window.initializeHumidityMonitoring=function(){
    const db=window.firebaseDatabase;
    window.firebaseOnValue(window.firebaseRef(db,'sensors/humidity/latest'),(snap)=>{
        updateHumidityDisplay(snap.val()??0);
    });
    loadHistoryData('24h');
};
document.querySelectorAll('.range-btn').forEach(btn=>btn.addEventListener('click',()=>loadHistoryData(btn.dataset.range)));
</script>

<?php include_once 'notif_bell.php'; ?>
</body>
</html>