<?php
require_once 'firebase_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>pH Monitor - WattAWaste</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://kit.fontawesome.com/a2e0e6ad65.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script type="module">
import { initializeApp }               from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
import { getAuth, onAuthStateChanged }  from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
import { getDatabase, ref, onValue, query, orderByKey, limitToLast }
    from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-database.js';
const firebaseConfig={apiKey:"AIzaSyAu9hOwjiuAl9PCh50HefMGZU9XDosu68I",authDomain:"wattawaste-d3503.firebaseapp.com",
    databaseURL:"https://wattawaste-d3503-default-rtdb.asia-southeast1.firebasedatabase.app",
    projectId:"wattawaste-d3503",storageBucket:"wattawaste-d3503.firebasestorage.app",
    messagingSenderId:"842761118644",appId:"1:842761118644:web:ddef65fd892486f67f88e1",measurementId:"G-33Z8K3NBY1"};
const app=initializeApp(firebaseConfig),auth=getAuth(app),database=getDatabase(app);
onAuthStateChanged(auth,(user)=>{
    if(!user){window.location.href='login.php';return;}
    sessionStorage.setItem('userEmail',user.email||user.phoneNumber||'');
    sessionStorage.setItem('userId',user.uid);
    window.initializePHMonitoring();
});
window.firebaseDatabase=database;window.firebaseRef=ref;window.firebaseOnValue=onValue;
window.firebaseQuery=query;window.firebaseOrderByKey=orderByKey;window.firebaseLimitToLast=limitToLast;
</script>

<style>
:root{--brand:#4CAF50;--brand-dark:#2E7D32;--ink:#333;--panel:#fff;--muted:#555;--bg:#F9FAFB;--ok:#22c55e;--warn:#f59e0b;--acidic:#ef4444;--alkaline:#3b82f6;}
body{background:var(--bg);font-family:Poppins,system-ui,Segoe UI,Arial;color:var(--ink);min-height:100vh;}
.card{border:none;border-radius:16px;background:var(--panel);box-shadow:0 6px 16px rgba(2,6,23,.06);transition:transform .2s,box-shadow .2s;}
.card:hover{transform:translateY(-3px);box-shadow:0 12px 26px rgba(2,6,23,.12);}
.page-title{font-size:28px;font-weight:700;color:var(--ink);margin-bottom:24px;}
.ph-hero{background:linear-gradient(135deg,#f59e0b 0%,#eab308 100%);border-radius:20px;padding:40px;color:white;text-align:center;position:relative;overflow:hidden;}
.ph-hero.acidic{background:linear-gradient(135deg,#ef4444 0%,#dc2626 100%);}
.ph-hero.alkaline{background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%);}
.ph-hero.ok{background:linear-gradient(135deg,#22c55e 0%,#16a34a 100%);}
.ph-hero::before{content:'';position:absolute;top:-50%;right:-50%;width:200%;height:200%;background:radial-gradient(circle,rgba(255,255,255,0.1) 0%,transparent 70%);animation:pulse 3s ease-in-out infinite;}
@keyframes pulse{0%,100%{transform:scale(1);opacity:.5}50%{transform:scale(1.1);opacity:.8}}
.ph-display-large{font-size:96px;font-weight:900;line-height:1;text-shadow:0 4px 12px rgba(0,0,0,0.2);position:relative;z-index:1;}
.status-badge-large{display:inline-block;padding:12px 30px;border-radius:50px;font-weight:700;font-size:18px;background:rgba(255,255,255,0.9);margin-top:16px;position:relative;z-index:1;}
.status-badge-large.ok{color:var(--ok);}.status-badge-large.acidic{color:var(--acidic);}.status-badge-large.alkaline{color:var(--alkaline);}
.stat-card{text-align:center;padding:20px;}.stat-value{font-size:36px;font-weight:800;color:var(--brand-dark);line-height:1;}.stat-label{font-size:14px;color:var(--muted);margin-top:8px;}
.ph-scale{width:100%;height:40px;background:linear-gradient(to right,#ff0000 0%,#ff7f00 14%,#ffff00 28%,#00ff00 42%,#0000ff 57%,#4b0082 71%,#9400d3 85%,#ff00ff 100%);border-radius:20px;position:relative;margin:20px 0;box-shadow:0 4px 12px rgba(0,0,0,0.2);}
.ph-indicator{position:absolute;top:-10px;width:20px;height:60px;background:white;border:3px solid #333;border-radius:10px;transition:left 1s cubic-bezier(0.4,0,0.2,1);box-shadow:0 4px 8px rgba(0,0,0,0.3);}
.ph-scale-labels{display:flex;justify-content:space-between;margin-top:8px;font-size:12px;color:var(--muted);font-weight:600;}
.history-item{display:flex;justify-content:space-between;align-items:center;padding:14px 16px;margin-bottom:8px;background:#f8fafc;border-radius:12px;border-left:4px solid #eab308;transition:all 0.2s;}
.history-item:hover{background:#f1f5f9;transform:translateX(4px);}
.history-value{font-size:22px;font-weight:700;color:#ca8a04;}.history-time{font-size:13px;color:var(--muted);}.small-muted{color:var(--muted);font-size:14px;}
.alert-custom{padding:16px 20px;border-radius:12px;border-left:4px solid;font-weight:500;}
.alert-custom.acidic{background:#FEE2E2;color:#7F1D1D;border-color:#ef4444;}
.alert-custom.alkaline{background:#DBEAFE;color:#1E3A8A;border-color:#3b82f6;}
.recommendation-popup{position:fixed;top:100px;right:30px;background:white;padding:20px;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,0.15);z-index:1000;max-width:350px;width:90%;border-left:5px solid;animation:slideIn 0.3s ease;display:none;}
@keyframes slideIn{from{transform:translateX(400px);opacity:0}to{transform:translateX(0);opacity:1}}
.recommendation-popup.acidic{border-color:#ef4444;}.recommendation-popup.alkaline{border-color:#3b82f6;}.recommendation-popup.ok{border-color:#22c55e;}
.close-popup{position:absolute;top:10px;right:10px;background:none;border:none;font-size:20px;cursor:pointer;color:#666;}
.range-btn{border:1.5px solid #dee2e6;background:#fff;border-radius:8px;padding:5px 14px;font-size:.85rem;cursor:pointer;transition:all .15s;}
.range-btn:hover{background:#f1f5f9;}.range-btn.active{background:var(--brand-dark);color:#fff;border-color:var(--brand-dark);}
</style>
</head>
<body>

<?php include 'sideabr.php'; ?>
<div class="main">
<?php include 'topnav.php'; ?>

<div class="container-fluid px-4 py-4">
    <h1 class="page-title">⚗️ pH Level Monitoring</h1>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="ph-hero ok" id="phHero">
                <div class="ph-display-large" id="phDisplay">--</div>
                <div class="status-badge-large ok" id="statusBadge">Loading...</div>
                <div class="mt-3 small" style="opacity:.9;position:relative;z-index:1;" id="statusDesc">Fetching pH data from sensors...</div>
                <div class="mt-2 small" style="opacity:.8;position:relative;z-index:1;" id="lastUpdateText">Last updated: --</div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card p-4 h-100">
                <h6 class="text-center mb-3">pH Scale (0–14)</h6>
                <div class="ph-scale"><div class="ph-indicator" id="phIndicator" style="left:calc(50% - 10px);"></div></div>
                <div class="ph-scale-labels"><span>0</span><span>7</span><span>14</span></div>
                <div class="mt-3 text-center small-muted">
                    <div><span style="color:#ef4444;">●</span> &lt;6.0 Too Acidic</div>
                    <div><span style="color:#22c55e;">●</span> 6.0–8.0 Optimal</div>
                    <div><span style="color:#3b82f6;">●</span> &gt;8.0 Too Alkaline</div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert-custom acidic mb-4" id="warningBanner" style="display:none;">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Action Required:</strong> <span id="warningText"></span>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4"><div class="card stat-card"><i class="fas fa-vial text-danger mb-2" style="font-size:32px;"></i><div class="stat-value" id="maxPHStat">--</div><div class="stat-label">Maximum (selected range)</div></div></div>
        <div class="col-md-4"><div class="card stat-card"><i class="fas fa-chart-line text-success mb-2" style="font-size:32px;"></i><div class="stat-value" id="avgPHStat">--</div><div class="stat-label">Average (selected range)</div></div></div>
        <div class="col-md-4"><div class="card stat-card"><i class="fas fa-flask text-info mb-2" style="font-size:32px;"></i><div class="stat-value" id="minPHStat">--</div><div class="stat-label">Minimum (selected range)</div></div></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="mb-0"><i class="fas fa-chart-area text-primary me-2"></i>pH Level Trend <span id="readingsCount" class="small-muted fs-6"></span></h5>
                    <!-- 30d REMOVED -->
                    <div class="d-flex gap-1 flex-wrap">
                        <button class="range-btn" data-range="1h">1h</button>
                        <button class="range-btn" data-range="6h">6h</button>
                        <button class="range-btn active" data-range="24h">24h</button>
                        <button class="range-btn" data-range="7d">7d</button>
                    </div>
                </div>
                <canvas id="phChart" style="max-height:350px;"></canvas>
                <div id="chartNoData" class="text-center text-muted small mt-2" style="display:none;">No data yet for this range.</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card p-4">
                <h5 class="mb-3"><i class="fas fa-history text-info me-2"></i>Recent Live Readings <span class="small-muted fs-6 ms-1">(latest 20 from Firebase)</span></h5>
                <div id="historyContainer" style="max-height:500px;overflow-y:auto;"><p class="text-muted text-center py-4">Loading pH history...</p></div>
            </div>
        </div>
    </div>
</div>

<div class="recommendation-popup ok" id="recommendationPopup">
    <button class="close-popup" onclick="closePopup()">×</button>
    <h6><i class="fas fa-lightbulb me-2"></i>Recommendation</h6>
    <p class="mb-0 mt-2" id="recommendationText">Compost is healthy, continue monitoring daily</p>
</div>

</div>

<script>
let phChart=null,currentRange='24h';

function closePopup(){document.getElementById('recommendationPopup').style.display='none';}
function updatePHIndicator(ph){document.getElementById('phIndicator').style.left=`calc(${Math.min(Math.max((ph/14)*100,0),100)}% - 10px)`;}
function getPHStatus(ph){
    if(ph<6.0)return{status:'Too Acidic',statusClass:'acidic',desc:'Add brown materials or lime to increase pH.',recommendation:'Add brown/lime materials to neutralize acidity'};
    if(ph>8.0)return{status:'Too Alkaline',statusClass:'alkaline',desc:'Add green materials or acidic materials to decrease pH.',recommendation:'Add green/acidic materials to reduce alkalinity'};
    return{status:'Optimal (Healthy)',statusClass:'ok',desc:'Perfect pH range for composting. Keep monitoring.',recommendation:'Compost is healthy, continue monitoring daily'};
}
function formatTimestamp(tsMs){const d=new Date(Number(tsMs));if(isNaN(d.getTime()))return'Unknown';return d.toLocaleString('en-US',{month:'short',day:'numeric',year:'numeric',hour:'numeric',minute:'2-digit',hour12:true});}
function getRangeCutoffMs(range){const now=Date.now();const map={'1h':1*60*60*1000,'6h':6*60*60*1000,'24h':24*60*60*1000,'7d':7*24*60*60*1000};return now-(map[range]||map['24h']);}
function updatePHDisplay(ph){
    const info=getPHStatus(ph);
    document.getElementById('phDisplay').textContent=ph.toFixed(1);
    document.getElementById('statusBadge').textContent=info.status;
    document.getElementById('statusDesc').textContent=info.desc;
    document.getElementById('lastUpdateText').textContent='Last updated: '+new Date().toLocaleString('en-US',{month:'short',day:'numeric',year:'numeric',hour:'numeric',minute:'2-digit',hour12:true});
    updatePHIndicator(ph);
    const hero=document.getElementById('phHero'),badge=document.getElementById('statusBadge'),popup=document.getElementById('recommendationPopup'),banner=document.getElementById('warningBanner');
    ['acidic','alkaline','ok'].forEach(c=>{hero.classList.remove(c);badge.classList.remove(c);popup.classList.remove(c);});
    banner.classList.remove('acidic','alkaline');
    hero.classList.add(info.statusClass);badge.classList.add(info.statusClass);popup.classList.add(info.statusClass);
    if(ph<6.0||ph>8.0){banner.classList.add(info.statusClass);banner.style.display='block';document.getElementById('warningText').textContent=info.recommendation;document.getElementById('recommendationText').textContent=info.recommendation;popup.style.display='block';}
    else{banner.style.display='none';popup.style.display='none';}
}
function updateStatistics(entries){
    if(!entries.length)return;
    const vals=entries.map(e=>e.value);
    document.getElementById('maxPHStat').textContent=Math.max(...vals).toFixed(2);
    document.getElementById('avgPHStat').textContent=(vals.reduce((a,b)=>a+b,0)/vals.length).toFixed(2);
    document.getElementById('minPHStat').textContent=Math.min(...vals).toFixed(2);
    document.getElementById('readingsCount').textContent=`(${entries.length} readings)`;
}
function renderChart(entries){
    if(phChart){phChart.destroy();phChart=null;}
    const labels=entries.map(e=>formatTimestamp(e.ts)),values=entries.map(e=>e.value);
    const ctx=document.getElementById('phChart').getContext('2d');
    const grad=ctx.createLinearGradient(0,0,0,400);grad.addColorStop(0,'rgba(234,179,8,0.3)');grad.addColorStop(1,'rgba(245,158,11,0.05)');
    const step=Math.ceil(labels.length/10),tickLabels=labels.map((l,i)=>i%step===0?l:'');
    phChart=new Chart(ctx,{type:'line',data:{labels:tickLabels,datasets:[{label:'pH Level',data:values,
        borderColor:'#eab308',backgroundColor:grad,borderWidth:3,tension:0.4,fill:true,
        pointRadius:values.length>50?0:4,pointHoverRadius:7,pointBackgroundColor:'#eab308',pointBorderColor:'#fff',pointBorderWidth:2}]},
    options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{display:false},tooltip:{backgroundColor:'rgba(0,0,0,0.8)',padding:12,
        callbacks:{title:(items)=>labels[items[0].dataIndex],label:(ctx)=>' pH: '+ctx.parsed.y.toFixed(2)}}},
    scales:{y:{beginAtZero:false,min:4,max:10,ticks:{callback:v=>v.toFixed(1)},grid:{color:'rgba(0,0,0,0.05)'}},x:{ticks:{maxTicksLimit:10,maxRotation:0},grid:{display:false}}}}});
}
function renderHistory(entries){
    const container=document.getElementById('historyContainer');
    if(!entries.length){container.innerHTML='<p class="text-muted text-center py-4">No history data available</p>';return;}
    const recent=entries.slice(-20).reverse();
    container.innerHTML=recent.map(entry=>{
        const val=entry.value;let bc='success',bt='Optimal';
        if(val<6.0){bc='danger';bt='Too Acidic';}else if(val>8.0){bc='primary';bt='Too Alkaline';}
        return`<div class="history-item"><div><div class="history-value">${val.toFixed(2)}</div><div class="history-time">${formatTimestamp(entry.ts)}</div></div><span class="badge bg-${bc}">${bt}</span></div>`;
    }).join('');
}
function loadHistoryData(range){
    currentRange=range;
    document.querySelectorAll('.range-btn').forEach(b=>b.classList.toggle('active',b.dataset.range===range));
    const db=window.firebaseDatabase,cutoffMs=getRangeCutoffMs(range);
    const histRef=window.firebaseQuery(window.firebaseRef(db,'sensors/ph/history'),window.firebaseOrderByKey(),window.firebaseLimitToLast(500));
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
    ['maxPHStat','avgPHStat','minPHStat'].forEach(id=>document.getElementById(id).textContent='--');
    document.getElementById('readingsCount').textContent='';
    document.getElementById('historyContainer').innerHTML='<p class="text-muted text-center py-4">No data found for this time range.</p>';
}
window.initializePHMonitoring=function(){
    const db=window.firebaseDatabase;
    window.firebaseOnValue(window.firebaseRef(db,'sensors/ph/latest'),(snap)=>{updatePHDisplay(snap.val()??7.0);});
    loadHistoryData('24h');
};
document.querySelectorAll('.range-btn').forEach(btn=>btn.addEventListener('click',()=>loadHistoryData(btn.dataset.range)));
document.addEventListener('keydown',(e)=>{if(e.key==='Escape'){closePopup();}});
</script>

<?php include_once 'notif_bell.php'; ?>
</body>
</html>