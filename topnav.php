<?php
/**
 * Top Navigation Bar - Firebase Version with Role Indicators
 */

require_once 'check_role.php';

// For Firebase sensor status, we'll fetch via JavaScript
// But we can show the role immediately from PHP session
?>

<!-- ===== TOP NAV ===== -->
<div class="top-nav d-flex justify-content-between align-items-center p-2 px-3 shadow-sm bg-white rounded-3">
    <div class="d-flex align-items-center gap-3">
        <h1 class="page-title m-0">WattAWaste</h1>
        
        <!-- Role Badge -->
        <div class="role-badge-inline <?php echo isCurrentUserAdmin() ? 'admin' : 'user'; ?>">
            <?php if (isCurrentUserAdmin()): ?>
                <i class="fas fa-shield-alt"></i>
                <span>ADMIN</span>
            <?php else: ?>
                <i class="fas fa-user"></i>
                <span>USER</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="top-right d-flex align-items-center gap-3">

        <!-- DATE & TIME -->
        <div id="dateTime" class="datetime fw-semibold"></div>

        <!-- SENSOR STATUS DROPDOWN -->
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle sensor-status-btn" type="button" id="sensorDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-wifi me-2"></i>Sensor Status
                <span class="sensor-count-badge" id="sensorCountBadge">4</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end sensor-dropdown" aria-labelledby="sensorDropdown" id="sensorStatusContainer">
                <li class="dropdown-header">
                    <strong>Real-time Sensor Status</strong>
                </li>
                <li><hr class="dropdown-divider"></li>
                <!-- Sensors will be loaded via JavaScript -->
                <li>
                    <div class="dropdown-item text-center">
                        <div class="spinner-border spinner-border-sm text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <small class="d-block mt-2 text-muted">Loading sensors...</small>
                    </div>
                </li>
            </ul>
        </div>

        <!-- FAULTY SENSOR COUNT -->
        <div class="faulty-indicator" id="faultyText">
            <i class="fas fa-exclamation-triangle me-1"></i>
            <span id="faultyCount">0</span> Faulty
        </div>

        <!-- USER INFO & PROFILE DROPDOWN -->
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle profile-btn p-0 border-0" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr(getCurrentUsername(), 0, 1)); ?>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end profile-dropdown" aria-labelledby="profileDropdown">
                <li class="dropdown-header">
                    <div class="profile-info">
                        <strong><?php echo htmlspecialchars(getCurrentUsername()); ?></strong>
                        <small class="d-block text-muted"><?php echo htmlspecialchars(getCurrentUserEmail()); ?></small>
                        <div class="role-badge-small <?php echo isCurrentUserAdmin() ? 'admin' : 'user'; ?> mt-1">
                            <?php echo getCurrentUserRole(); ?>
                        </div>
                    </div>
                </li>
                <li><hr class="dropdown-divider"></li>
                
                <?php if (isCurrentUserAdmin()): ?>
                <li>
                    <a class="dropdown-item" href="admin_dashboard.php">
                        <i class="fas fa-shield-alt me-2"></i>Admin Dashboard
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="user_management.php">
                        <i class="fas fa-users-cog me-2"></i>User Management
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <?php endif; ?>
                
                <li>
                    <a class="dropdown-item" href="profile.php">
                        <i class="fas fa-user me-2"></i>My Profile
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="settings.php">
                        <i class="fas fa-cog me-2"></i>Settings
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>

    </div>
</div>

<!-- ===== STYLES ===== -->
<style>
body {
    margin: 0;
    padding: 0;
}

.top-nav {
    position: sticky;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background: rgba(255,255,255,0.98);
    backdrop-filter: blur(12px);
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.top-nav.hidden {
    transform: translateY(-120%);
}

.page-title { 
    color: #2E7D32; 
    font-weight: 700; 
    font-size: 1.25rem;
    margin: 0;
}

/* Role Badge Inline */
.role-badge-inline {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}

.role-badge-inline.admin {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    animation: adminPulse 2s infinite;
}

.role-badge-inline.user {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

@keyframes adminPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
    50% { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
}

.top-right .datetime { 
    color: #2E7D32; 
    opacity: .85; 
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}

.top-right .datetime::before {
    content: '🕐';
    font-size: 16px;
}

/* Sensor Status Button */
.sensor-status-btn {
    position: relative;
    font-weight: 600;
}

.sensor-count-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #4CAF50;
    color: white;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 10px;
    font-weight: 700;
}

/* Sensor Dropdown */
.sensor-dropdown {
    min-width: 300px;
    max-height: 400px;
    overflow-y: auto;
    z-index: 4000;
}

.sensor-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    transition: background 0.2s;
}

.sensor-item:hover {
    background: #f8f9fa;
}

.sensor-icon {
    font-size: 20px;
    margin-right: 10px;
}

.sensor-name {
    flex: 1;
    font-weight: 600;
}

.sensor-time {
    font-size: 11px;
    color: #6c757d;
    margin-left: 10px;
}

.dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin: 0 8px;
    display: inline-block;
}

.dot.online {
    background: #2ecc71;
    box-shadow: 0 0 8px rgba(46, 204, 113, 0.6);
    animation: dotPulse 2s infinite;
}

.dot.delayed { 
    background: #f1c40f;
    box-shadow: 0 0 8px rgba(241, 196, 15, 0.6);
}

.dot.offline { 
    background: #e74c3c;
}

.dot.faulty { 
    background: #7f8c8d;
}

@keyframes dotPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Faulty Indicator */
.faulty-indicator {
    display: flex;
    align-items: center;
    padding: 6px 12px;
    background: #FFF3E0;
    border: 1px solid #FFB74D;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    color: #E65100;
}

.faulty-indicator i {
    font-size: 14px;
}

/* Profile Avatar */
.profile-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #23ed99ff, #0f8156ff);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 16px;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    transition: transform 0.2s;
}

.profile-avatar:hover {
    transform: scale(1.1);
}

/* Profile Dropdown */
.profile-dropdown {
    min-width: 250px;
}

.profile-info {
    padding: 8px 0;
}

.role-badge-small {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
}

.role-badge-small.admin {
    background: #ef4444;
    color: white;
}

.role-badge-small.user {
    background: #3b82f6;
    color: white;
}

.dropdown-item i {
    width: 20px;
    text-align: center;
}

.card, .dashboard-container {
    position: relative;
    z-index: 1;
}
</style>

<!-- ===== SCRIPTS ===== -->
<script>
// Update date/time
function updateDateTime() {
    const now = new Date();
    const options = { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit',
        second: '2-digit'
    };
    document.getElementById('dateTime').textContent = now.toLocaleString('en-US', options);
}
setInterval(updateDateTime, 1000);
updateDateTime();

// Fetch sensor status from Firebase
async function refreshSensors() {
    try {
        const res = await fetch('api/get_latest.php', { cache: 'no-store' });
        const { latest } = await res.json();
        
        if (!latest) return;
        
        const container = document.getElementById('sensorStatusContainer');
        
        // Define sensors with their data
        const sensors = [
            { 
                name: 'Temperature', 
                icon: '🌡️', 
                value: latest.temperature, 
                unit: '°C',
                optimal: latest.temperature >= 20 && latest.temperature <= 70
            },
            { 
                name: 'Humidity', 
                icon: '💧', 
                value: latest.humidity, 
                unit: '%',
                optimal: latest.humidity >= 40 && latest.humidity <= 80
            },
            { 
                name: 'Gas Level', 
                icon: '💨', 
                value: latest.gas, 
                unit: ' ppm',
                optimal: latest.gas < 600
            },
            { 
                name: 'pH Level', 
                icon: '⚗️', 
                value: latest.ph, 
                unit: '',
                optimal: latest.ph >= 6.0 && latest.ph <= 8.5
            }
        ];
        
        let html = '<li class="dropdown-header"><strong>Real-time Sensor Status</strong></li>';
        html += '<li><hr class="dropdown-divider"></li>';
        
        let faultyCount = 0;
        
        sensors.forEach(sensor => {
            const status = sensor.optimal ? 'online' : 'delayed';
            const statusText = sensor.optimal ? 'Normal' : 'Alert';
            
            if (!sensor.optimal) faultyCount++;
            
            html += `
                <li>
                    <div class="dropdown-item sensor-item">
                        <span class="sensor-icon">${sensor.icon}</span>
                        <span class="sensor-name">${sensor.name}</span>
                        <span class="fw-bold">${sensor.value !== undefined ? sensor.value.toFixed(1) : '--'}${sensor.unit}</span>
                        <span class="dot ${status}"></span>
                        <span class="sensor-time">${statusText}</span>
                    </div>
                </li>
            `;
        });
        
        html += '<li><hr class="dropdown-divider"></li>';
        html += '<li><small class="dropdown-item text-muted text-center">Updated just now</small></li>';
        
        container.innerHTML = html;
        
        // Update faulty count
        document.getElementById('faultyCount').textContent = faultyCount;
        document.getElementById('sensorCountBadge').textContent = sensors.length;
        
    } catch (e) {
        console.error('Sensor fetch error:', e);
        document.getElementById('sensorStatusContainer').innerHTML = `
            <li class="dropdown-item text-center text-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Failed to load sensors
            </li>
        `;
    }
}

// Refresh sensors every 3 seconds
refreshSensors();
setInterval(refreshSensors, 3000);

// Hide/show topnav on scroll
let lastScroll = 0;
const topNav = document.querySelector('.top-nav');

window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
    if (currentScroll > lastScroll && currentScroll > 100) {
        topNav.classList.add('hidden');
    } else {
        topNav.classList.remove('hidden');
    }
    lastScroll = currentScroll <= 0 ? 0 : currentScroll;
});

// Log user role
console.log('<?php echo isCurrentUserAdmin() ? "🛡️ Logged in as: ADMIN" : "👤 Logged in as: USER"; ?>');
console.log('User: <?php echo htmlspecialchars(getCurrentUsername()); ?>');
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>