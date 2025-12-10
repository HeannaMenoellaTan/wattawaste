<?php
// sensor_dropdown.php
?>

<style>
.sensor-dropdown-btn {
    cursor: pointer;
    padding: 6px 10px;
    border-radius: 6px;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    gap: 6px;
}
.sensor-dropdown-menu {
    position: absolute;
    right: 0;
    top: 100%;
    margin-top: 8px;
    width: 260px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    display: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    padding: 10px;
    z-index: 999;
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


<!-- DROPDOWN BUTTON -->
<div class="dropdown" style="position:relative;">
    <div class="sensor-dropdown-btn" onclick="toggleSensorDropdown()">
        <i class="fa-solid fa-signal" style="color:#ff9900;"></i>
        <span id="faultyText">— Sensors</span>
        <span id="summaryDot" class="sensor-dot faulty"></span>
    </div>

    <!-- DROPDOWN CONTENT -->
    <div class="sensor-dropdown-menu" id="sensorDropdownMenu">
        <div id="sensor-status-container">Loading...</div>
    </div>
</div>

<script>
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

// ⬇️ YOU FORGOT THIS
setInterval(loadSensorStatus, 2000);
loadSensorStatus();

// Existing time update
setInterval(updateDateTime, 1000);
updateDateTime();
</script>
