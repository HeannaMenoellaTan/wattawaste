<!-- notif_bell.php -->
<div id="notifBell" class="notif-btn">
    🔔
    <span id="notifCountBell" class="notif-badge">0</span>
</div>

<div id="notifPopup" class="notif-popup">
  <div class="notif-header">
    <span>🔔 Notifications</span>
    <span id="closeNotifBell" style="cursor:pointer; font-weight:bold;">✖</span>
  </div>
  <div id="notifListBell" class="notif-list">
    <!-- Notifications loaded dynamically -->
  </div>
</div>

<style>
.notif-btn {
    position: fixed;
    bottom: 25px;
    right: 25px;
    width: 60px;
    height: 60px;
  
    color: #fff;
    border-radius: 50%;
    font-size: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 14px rgba(0,0,0,0.3);
    cursor: pointer;
    z-index: 2000;
}
.notif-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: red;
    color: white;
    font-size: 12px;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 50%;
}
.notif-popup {
    position: fixed;
    bottom: 100px;
    right: 25px;
    width: 320px;
    max-height: 400px;
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    display: none;
    flex-direction: column;
    overflow: hidden;
    z-index: 3000;
}
.notif-header {
    background: #eec173ff;
    color: #fff;
    padding: 10px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 700;
}
.notif-list {
    padding: 10px;
    max-height: 340px;
    overflow-y: auto;
}
.notif-item {
    background: #fff7e6;
    padding: 8px 10px;
    border-radius: 8px;
    margin-bottom: 10px;
    font-size: .9rem;
    border-left: 4px solid #f59e0b;
}
.notif-time {
    font-size: .75rem;
    color: #666;
    text-align: right;
}
</style>

<script>
// DOM Elements
const notifBell = document.getElementById("notifBell");
const notifPopup = document.getElementById("notifPopup");
const closeNotif = document.getElementById("closeNotifBell");
const notifCount = document.getElementById("notifCountBell");
const notifList = document.getElementById("notifListBell");

// Toggle popup
notifBell.addEventListener("click", () => {
    if (notifPopup.style.display === "flex") {
        notifPopup.style.display = "none";
    } else {
        notifPopup.style.display = "flex";
        loadNotifications();
        notifCount.style.display = "none"; // hide badge after opening
    }
});

// Close popup
closeNotif.addEventListener("click", () => {
    notifPopup.style.display = "none";
});

// Fetch notifications
async function loadNotifications() {
    try {
        const res = await fetch('notif_fetch.php'); // fetch from safe AJAX endpoint
        const data = await res.json();
        notifList.innerHTML = '';
        if (!Array.isArray(data) || data.length === 0) {
            notifList.innerHTML = '<div class="text-center text-muted">No notifications.</div>';
            notifCount.style.display = 'none';
            return;
        }

        data.forEach(msg => {
            const div = document.createElement('div');
            div.className = 'notif-item';
            div.innerHTML = `
                <div>${msg.message_text || 'No message'}</div>
                <div class="notif-time">${msg.created_at || ''}</div>
            `;
            notifList.appendChild(div);
        });

        // Update badge
        const unread = data.length;
        notifCount.textContent = unread;
        notifCount.style.display = unread ? 'inline-block' : 'none';
    } catch (err) {
        console.error("Failed to load notifications:", err);
        notifList.innerHTML = '<div class="text-danger">Error loading notifications.</div>';
    }
}

// Auto-refresh every 10s
setInterval(loadNotifications, 10000);
loadNotifications();
</script>
