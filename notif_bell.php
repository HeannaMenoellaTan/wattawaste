<!-- notif_bell.php — Dynamic Firebase notification bell -->
<!-- Place JUST BEFORE </body> in every page, never inside <head> -->

<div id="notifBell" class="notif-btn" title="Notifications">
    <i class="fas fa-bell"></i>
    <span id="notifCountBell" class="notif-badge" style="display:none;">0</span>
</div>

<div id="notifPopup" class="notif-popup">
    <div class="notif-header">
        <span><i class="fas fa-bell me-2"></i>Notifications</span>
        <span id="closeNotifBell" style="cursor:pointer;font-weight:bold;font-size:18px;">✖</span>
    </div>
    <div id="notifListBell" class="notif-list">
        <div class="notif-loading"><i class="fas fa-spinner fa-spin me-2"></i>Loading...</div>
    </div>
</div>

<div id="notifToastContainer"></div>

<style>
/* ── Bell — z-index 99999 to sit above sidebar/topnav ── */
#notifBell.notif-btn {
    position: fixed !important;
    bottom: 25px !important;
    right: 25px !important;
    width: 58px !important;
    height: 58px !important;
    background: linear-gradient(135deg, #4CAF50, #2E7D32) !important;
    color: #fff !important;
    border-radius: 50% !important;
    font-size: 24px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    box-shadow: 0 4px 18px rgba(46,125,50,.45) !important;
    cursor: pointer !important;
    z-index: 99999 !important;
    transition: transform .2s, box-shadow .2s !important;
    border: none !important;
    outline: none !important;
}
#notifBell.notif-btn:hover {
    transform: scale(1.1) !important;
    box-shadow: 0 6px 24px rgba(46,125,50,.55) !important;
}
#notifBell.notif-btn.has-alerts {
    animation: nbShake .65s ease infinite alternate !important;
    background: linear-gradient(135deg, #ef4444, #b91c1c) !important;
    box-shadow: 0 4px 18px rgba(239,68,68,.5) !important;
}
@keyframes nbShake {
    0%  { transform: rotate(-9deg) scale(1.06); }
    100%{ transform: rotate( 9deg) scale(1.06); }
}

/* ── Badge ── */
#notifCountBell.notif-badge {
    position: absolute !important;
    top: -7px !important;
    right: -7px !important;
    background: #ef4444 !important;
    color: white !important;
    font-size: 11px !important;
    font-weight: 800 !important;
    min-width: 20px !important;
    height: 20px !important;
    padding: 0 5px !important;
    border-radius: 10px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    border: 2px solid white !important;
}

/* ── Popup panel ── */
#notifPopup.notif-popup {
    position: fixed !important;
    bottom: 95px !important;
    right: 25px !important;
    z-index: 99998 !important;
    width: 340px;
    max-height: 460px;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 8px 32px rgba(0,0,0,.2);
    display: none;
    flex-direction: column;
    overflow: hidden;
    animation: nbPopSlide .2s ease;
}
@keyframes nbPopSlide {
    from { opacity:0; transform:translateY(10px); }
    to   { opacity:1; transform:translateY(0); }
}

.notif-header {
    background: linear-gradient(135deg, #4CAF50, #2E7D32);
    color: #fff;
    padding: 14px 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 700;
    font-size: 15px;
    flex-shrink: 0;
}
.notif-list {
    padding: 10px;
    overflow-y: auto;
    flex: 1;
    max-height: 380px;
}
.notif-loading { text-align:center; padding:20px; color:#888; font-size:.9rem; }
.notif-empty   { text-align:center; padding:30px 15px; color:#aaa; font-size:.9rem; }
.notif-empty i { font-size:30px; margin-bottom:8px; display:block; }

.nb-item {
    padding: 11px 13px;
    border-radius: 10px;
    margin-bottom: 7px;
    font-size: .875rem;
    border-left: 4px solid #f59e0b;
    background: #fffbeb;
    transition: transform .12s;
}
.nb-item:hover  { transform: translateX(3px); }
.nb-item.crit   { border-color:#ef4444; background:#fef2f2; }
.nb-item.warn   { border-color:#f59e0b; background:#fffbeb; }
.nb-item.info   { border-color:#3b82f6; background:#eff6ff; }
.nb-item-title  { font-weight:700; margin-bottom:3px; }
.nb-item-body   { color:#555; line-height:1.4; font-size:.84rem; }
.nb-item-time   { font-size:.73rem; color:#999; margin-top:4px; text-align:right; }

.nb-clear-btn {
    display: block;
    width: calc(100% - 20px);
    margin: 0 10px 10px;
    padding: 8px;
    background: transparent;
    border: 1.5px solid #dee2e6;
    border-radius: 10px;
    font-size: .82rem;
    color: #666;
    cursor: pointer;
    flex-shrink: 0;
    transition: background .15s;
}
.nb-clear-btn:hover { background: #f1f5f9; }

/* ── Toast container ── */
#notifToastContainer {
    position: fixed !important;
    top: 20px !important;
    right: 20px !important;
    z-index: 99997 !important;
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-width: 330px;
    width: 100%;
    pointer-events: none;
}
.nb-toast {
    background: white;
    border-radius: 14px;
    padding: 13px 16px;
    box-shadow: 0 6px 20px rgba(0,0,0,.15);
    border-left: 5px solid #f59e0b;
    font-size: .875rem;
    animation: nbToastIn .3s ease;
    position: relative;
    overflow: hidden;
    pointer-events: all;
    transition: opacity .3s, transform .3s;
}
.nb-toast.crit { border-color:#ef4444; }
.nb-toast.warn { border-color:#f59e0b; }
.nb-toast.info { border-color:#3b82f6; }
.nb-toast.ok   { border-color:#22c55e; }
@keyframes nbToastIn {
    from { opacity:0; transform:translateX(40px); }
    to   { opacity:1; transform:translateX(0); }
}
.nb-toast::after {
    content:'';
    position:absolute; bottom:0; left:0;
    height:3px; width:100%;
    background:rgba(0,0,0,.1);
    animation: nbTimer 5s linear forwards;
}
@keyframes nbTimer { from{width:100%} to{width:0%} }
.nb-toast-title { font-weight:700; margin-bottom:2px; }
.nb-toast-body  { color:#555; font-size:.84rem; }

/* ── Responsive ── */
@media (max-width:480px) {
    #notifPopup.notif-popup { width:calc(100vw - 24px); right:12px; bottom:80px; }
    #notifBell.notif-btn    { bottom:15px; right:15px; }
    #notifToastContainer    { right:12px; top:65px; max-width:calc(100vw - 24px); }
}
</style>

<script>
(function(){
'use strict';

// ── Thresholds (must match aggregate.php) ──────────────────────────────────
const THRESHOLDS = {
    temperature: { min:15,  max:75,  unit:'°C',  label:'🌡️ Temperature' },
    humidity:    { min:30,  max:90,  unit:'%',   label:'💧 Humidity'    },
    gas:         { min:0,   max:800, unit:'ppm', label:'💨 Gas Level'   },
    ph:          { min:5.5, max:8.5, unit:'pH',  label:'⚗️ pH Level'    },
    weight:      { min:0,   max:80,  unit:'kg',  label:'⚖️ Weight'      },
};

// ── State ──────────────────────────────────────────────────────────────────
let notifs    = [];
let seenKeys  = new Set(JSON.parse(sessionStorage.getItem('_nbSeen') || '[]'));
let toastQ    = [];
let toastBusy = false;

// ── DOM ────────────────────────────────────────────────────────────────────
const bell      = document.getElementById('notifBell');
const popup     = document.getElementById('notifPopup');
const closeBtn  = document.getElementById('closeNotifBell');
const badge     = document.getElementById('notifCountBell');
const list      = document.getElementById('notifListBell');
const toastCont = document.getElementById('notifToastContainer');

// ── Toggle popup ──────────────────────────────────────────────────────────
bell.addEventListener('click', (e) => {
    e.stopPropagation();
    const open = popup.style.display === 'flex';
    popup.style.display = open ? 'none' : 'flex';
    if (!open) renderList();
});
closeBtn.addEventListener('click', () => { popup.style.display = 'none'; });
document.addEventListener('click', (e) => {
    if (!bell.contains(e.target) && !popup.contains(e.target))
        popup.style.display = 'none';
});

// ── Helpers ────────────────────────────────────────────────────────────────
function sev(sensor, value) {
    const t = THRESHOLDS[sensor];
    if (!t) return null;
    if (value > t.max * 1.15 || (t.min > 0 && value < t.min * 0.7)) return 'crit';
    if (value > t.max || value < t.min) return 'warn';
    return null;
}
function ico(s) { return s === 'crit' ? '🚨' : '⚠️'; }
function fmtTs(ts) {
    return new Date(ts).toLocaleString('en-US',{
        month:'short', day:'numeric',
        hour:'numeric', minute:'2-digit', hour12:true
    });
}

// ── Add notification ──────────────────────────────────────────────────────
function addNotif(type, severity, title, body) {
    const key = `${type}|${title}`;
    if (seenKeys.has(key)) return;
    seenKeys.add(key);
    setTimeout(() => seenKeys.delete(key), 5 * 60 * 1000); // debounce 5 min
    sessionStorage.setItem('_nbSeen', JSON.stringify([...seenKeys]));

    notifs.unshift({ type, severity, title, body, ts: Date.now() });
    if (notifs.length > 30) notifs.length = 30;

    updateBadge();
    enqueueToast({ severity, title, body });
    if (popup.style.display === 'flex') renderList();
}

// ── Badge ──────────────────────────────────────────────────────────────────
function updateBadge() {
    const n = notifs.length;
    badge.textContent   = n > 99 ? '99+' : n;
    badge.style.display = n > 0 ? 'flex' : 'none';
    bell.classList.toggle('has-alerts', notifs.some(x => x.severity === 'crit'));
}

// ── Render list ────────────────────────────────────────────────────────────
function renderList() {
    const old = popup.querySelector('.nb-clear-btn');
    if (old) old.remove();

    if (!notifs.length) {
        list.innerHTML = `<div class="notif-empty">
            <i class="fas fa-check-circle" style="color:#22c55e;"></i>
            All sensors normal — no alerts!
        </div>`;
        return;
    }

    list.innerHTML = notifs.map(n => `
        <div class="nb-item ${n.severity}">
            <div class="nb-item-title">${ico(n.severity)} ${n.title}</div>
            <div class="nb-item-body">${n.body}</div>
            <div class="nb-item-time">${fmtTs(n.ts)}</div>
        </div>`).join('');

    const btn = document.createElement('button');
    btn.className = 'nb-clear-btn';
    btn.innerHTML = '<i class="fas fa-trash-alt me-1"></i> Clear all';
    btn.onclick = () => {
        notifs = [];
        seenKeys.clear();
        sessionStorage.removeItem('_nbSeen');
        updateBadge();
        renderList();
    };
    popup.appendChild(btn);
}

// ── Toast queue ────────────────────────────────────────────────────────────
function enqueueToast(n) { toastQ.push(n); if (!toastBusy) nextToast(); }
function nextToast() {
    if (!toastQ.length) { toastBusy = false; return; }
    toastBusy = true;
    const n  = toastQ.shift();
    const el = document.createElement('div');
    el.className = `nb-toast ${n.severity}`;
    el.innerHTML = `<div class="nb-toast-title">${ico(n.severity)} ${n.title}</div>
                    <div class="nb-toast-body">${n.body}</div>`;
    toastCont.appendChild(el);
    setTimeout(() => {
        el.style.opacity   = '0';
        el.style.transform = 'translateX(40px)';
        setTimeout(() => { el.remove(); setTimeout(nextToast, 200); }, 300);
    }, 5000);
}

// ── Check sensor against thresholds ───────────────────────────────────────
function checkSensor(sensor, value) {
    const t = THRESHOLDS[sensor];
    const s = sev(sensor, value);
    if (!s || !t) return;
    const v   = parseFloat(value).toFixed(2);
    const dir = value > t.max ? 'above' : 'below';
    addNotif(sensor, s, `${t.label} Alert`,
        `Reading (${v} ${t.unit}) is ${dir} safe range (${t.min}–${t.max} ${t.unit}).`);
}

// ── Check fertilizer vs plant needs ───────────────────────────────────────
function checkFertilizer(available, plants) {
    if (!plants) return;
    let total = 0;
    Object.values(plants).forEach(p => { total += parseFloat(p.totalFertilizerNeeded || 0); });
    if (total > 0 && available < total) {
        addNotif('fertilizer', 'warn', '🌾 Fertilizer Insufficient',
            `Available compost (${available.toFixed(2)} kg) cannot cover your plants. Deficit: ${(total - available).toFixed(2)} kg.`);
    }
}

// ── Attach Firebase real-time listeners ───────────────────────────────────
function attachListeners() {
    const db  = window.firebaseDatabase;
    const ref = window.firebaseRef;
    const on  = window.firebaseOnValue;

    // Live latest sensor values
    Object.keys(THRESHOLDS).forEach(sensor => {
        on(ref(db, `sensors/${sensor}/latest`), snap => {
            const v = snap.val();
            if (v !== null) checkSensor(sensor, parseFloat(v));
        });
    });

    // Alerts written by aggregate.php cron
    on(ref(db, 'alerts'), snap => {
        const all = snap.val();
        if (!all) return;
        Object.entries(all).forEach(([sensor, entries]) => {
            const t = THRESHOLDS[sensor];
            if (!t || !entries) return;
            const recent = Object.values(entries)
                .filter(a => !a.resolved && (Date.now() - a.timestamp) < 30 * 60 * 1000)
                .sort((a, b) => b.timestamp - a.timestamp)[0];
            if (recent) {
                addNotif(`cron_${sensor}`, 'crit', `🚨 ${t.label} Critical`,
                    recent.message || `Abnormal reading: ${recent.value} ${t.unit}`);
            }
        });
    });

    // Fertilizer check
    const userId = sessionStorage.getItem('userId');
    if (userId) {
        on(ref(db, 'sensors/weight/latest'), wSnap => {
            const w = wSnap.val();
            if (w === null) return;
            on(ref(db, `user_plants/${userId}`), pSnap => {
                checkFertilizer(parseFloat(w) * 0.5, pSnap.val());
            }, { onlyOnce: true });
        });
    }
}

// ── Wait for Firebase module to expose globals ─────────────────────────────
function waitAndInit() {
    if (window.firebaseDatabase && window.firebaseRef && window.firebaseOnValue) {
        attachListeners();
    } else {
        setTimeout(waitAndInit, 500);
    }
}

// ── Boot ───────────────────────────────────────────────────────────────────
updateBadge();
waitAndInit();

// Re-evaluate sensors every 2 minutes for long-running pages
setInterval(() => {
    if (!window.firebaseOnValue || !window.firebaseRef || !window.firebaseDatabase) return;
    Object.keys(THRESHOLDS).forEach(sensor => {
        window.firebaseOnValue(
            window.firebaseRef(window.firebaseDatabase, `sensors/${sensor}/latest`),
            snap => { if (snap.val() !== null) checkSensor(sensor, parseFloat(snap.val())); },
            { onlyOnce: true }
        );
    });
}, 2 * 60 * 1000);

})();
</script>