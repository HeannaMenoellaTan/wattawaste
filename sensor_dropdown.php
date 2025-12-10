<?php
// sensor_dropdown.php
$initialText = "— Sensors"; 
?>

<!-- SENSOR DROPDOWN -->
<div class="dropdown" style="position:relative; z-index: 9999;">
    <div class="sensor-dropdown-btn" onclick="toggleSensorDropdown()">
        <i class="fa-solid fa-signal" style="color:#ff9900;"></i>
        <span id="faultyText"><?php echo $initialText; ?></span>
        <span id="summaryDot" class="sensor-dot faulty"></span>
    </div>

    <!-- DROPDOWN CONTENT -->
    <div class="sensor-dropdown-menu" id="sensorDropdownMenu">
        <div id="sensor-status-container">Loading...</div>
    </div>
</div>

<!-- SENSOR DROPDOWN CSS -->
<style>
.sensor-dropdown-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    cursor: pointer;
    border-radius: 6px;
    background: #f3f4f6;
    position: relative; 
    z-index: 2000;
    user-select: none;
}
.sensor-dropdown-menu {
    position: absolute; 
    top: 100%;           
    right: 0;            
    margin-top: 6px;
    width: 260px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    display: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    padding: 10px;
    z-index: 9999; 
}
.sensor-status-row {
    display: flex;
    align-items: center;
    padding: 6px 4px;
    border-radius: 5px;
    font-size: 14px;
}
.sensor-status-row:hover {
    background: #f2f2f2;
}
.sensor-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}
.online { background: #2ecc71; }
.delayed { background: #f1c40f; }
.offline { background: #7f8c8d; }
.faulty { background: #e74c3c; }
</style>

<!-- SENSOR DROPDOWN JS -->
<script>
function toggleSensorDropdown() {
    const menu = document.getElementById("sensorDropdownMenu");
    menu.style.display = (menu.style.display === "block") ? "none" : "block";
}

// Close dropdown when clicking outside
document.addEventListener("click", function(e) {
    const menu = document.getElementById("sensorDropdownMenu");
    const btn = document.querySelector(".sensor-dropdown-btn");

    if (!btn.contains(e.target) && !menu.contains(e.target)) {
        menu.style.display = "none";
    }
});

// AUTO REFRESH FUNCTION
async function loadSensorStatus() {
    const container = document.getElementById("sensor-status-container");
    try {
        const res = await fetch("sensor_status.php");
        if (!res.ok) throw new Error("Failed to fetch sensor data");
        const data = await res.json();

        container.innerHTML = "";

        let faultyCount = 0;
        const severityRank = { faulty:4, offline:3, delayed:2, online:1 };
        let worstClass = "online", worstRank = 0;

        data.forEach(sensor => {
            if (sensor.class === "faulty") faultyCount++;

            if (severityRank[sensor.class] > worstRank) {
                worstRank = severityRank[sensor.class];
                worstClass = sensor.class;
            }

            let icon = "📡";
            if (sensor.name === "Temperature") icon = "🌡️";
            if (sensor.name === "Humidity") icon = "💧";
            if (sensor.name === "Gas") icon = "🔥";
            if (sensor.name === "pH") icon = "⚗️";

            container.innerHTML += `
                <div class="sensor-status-row">
                    <span style="font-size:18px; width:28px;">${icon}</span>
                    <span style="flex:1;">${sensor.name}</span>
                    <span class="sensor-dot ${sensor.class}"></span>
                    <span style="margin-left:6px;">${sensor.status}</span>
                </div>
            `;
        });

        document.getElementById("faultyText").textContent =
            faultyCount > 0 ? `${faultyCount} Faulty Sensor${faultyCount !== 1 ? 's' : ''}` : "<?php echo $initialText; ?>";

        document.getElementById("summaryDot").className =
            `sensor-dot ${worstClass}`;

    } catch (err) {
        console.error(err);
        container.innerHTML = "<span style='color:red;'>Error loading sensors</span>";
        document.getElementById("faultyText").textContent = "<?php echo $initialText; ?>";
        document.getElementById("summaryDot").className = "sensor-dot faulty";
    }
}

// Refresh every 2 seconds
setInterval(loadSensorStatus, 2000);
loadSensorStatus();
</script>
