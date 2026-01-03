<?php
require_once 'check_role.php';

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
.sidebar {
    width: 265px;
    height: 100vh;
    background: linear-gradient(180deg, #23ed99ff 0%, #0f8156ff 100%);
    position: fixed;
    left: 0;
    top: 0;
    overflow-y: auto;
    box-shadow: 4px 0 10px rgba(0,0,0,0.1);
    z-index: 1000;
}

.sidebar-header {
    padding: 30px 20px;
    text-align: center;
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
}

.sidebar-logo {
    width: 80px;
    height: 80px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.sidebar-logo i {
    font-size: 36px;
    color: #0f8156ff;
}

.sidebar-title {
    color: white;
    font-size: 22px;
    font-weight: 800;
    margin: 0;
}

.sidebar-subtitle {
    color: rgba(255,255,255,0.9);
    font-size: 13px;
    margin-top: 5px;
}

.user-info {
    background: rgba(255,255,255,0.15);
    padding: 15px;
    margin: 15px;
    border-radius: 12px;
    text-align: center;
}

.user-name {
    color: white;
    font-weight: 700;
    font-size: 16px;
    margin-bottom: 5px;
}

.role-badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    margin-top: 5px;
}

.admin-badge {
    background: #ef4444;
    color: white;
    animation: adminPulse 2s infinite;
}

@keyframes adminPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
    50% { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
}

.user-badge {
    background: #3b82f6;
    color: white;
}

.nav-menu {
    padding: 20px 0;
}

.nav-item {
    display: flex;
    align-items: center;
    padding: 14px 25px;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    font-weight: 600;
    font-size: 15px;
    border-left: 4px solid transparent;
}

.nav-item:hover {
    background: rgba(255,255,255,0.15);
    border-left-color: white;
    padding-left: 30px;
}

.nav-item.active {
    background: rgba(255,255,255,0.2);
    border-left-color: white;
}

.nav-item i {
    width: 24px;
    margin-right: 12px;
    font-size: 18px;
}

.nav-divider {
    height: 1px;
    background: rgba(255,255,255,0.2);
    margin: 15px 20px;
}

.nav-section-title {
    padding: 10px 25px;
    color: rgba(255,255,255,0.7);
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.logout-btn {
    display: flex;
    align-items: center;
    padding: 14px 25px;
    color: white;
    text-decoration: none;
    font-weight: 600;
    font-size: 15px;
    background: rgba(239, 68, 68, 0.3);
    margin: 20px 15px;
    border-radius: 12px;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.logout-btn:hover {
    background: #ef4444;
    border-color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.logout-btn i {
    margin-right: 10px;
}

.main {
    margin-left: 265px;
    padding: 20px;
    min-height: 100vh;
    background: #F9FAFB;
}

/* Admin-only indicator */
.admin-only-badge {
    display: inline-block;
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 700;
    margin-left: 8px;
}
</style>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="fas fa-recycle"></i>
        </div>
        <h1 class="sidebar-title">WattAWaste</h1>
        <p class="sidebar-subtitle">Aerobic & Anaerobic<br>Waste Hybrid Bin</p>
    </div>

    <div class="user-info">
        <div class="user-name"><?php echo htmlspecialchars(getCurrentUsername()); ?></div>
        <?php echo getRoleBadge(); ?>
    </div>

    <nav class="nav-menu">
        <!-- Dashboard Section -->
        <a href="index.php" class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            Dashboard
        </a>

        <!-- Sensors Section -->
        <div class="nav-section-title">Sensors</div>
        
        <a href="temperature.php" class="nav-item <?php echo $current_page == 'temperature.php' ? 'active' : ''; ?>">
            <i class="fas fa-thermometer-half"></i>
            Temperature
        </a>

        <a href="humidity.php" class="nav-item <?php echo $current_page == 'humidity.php' ? 'active' : ''; ?>">
            <i class="fas fa-tint"></i>
            Humidity
        </a>

        <a href="gas.php" class="nav-item <?php echo $current_page == 'gas.php' ? 'active' : ''; ?>">
            <i class="fas fa-wind"></i>
            Gas Level
        </a>

        <a href="ph.php" class="nav-item <?php echo $current_page == 'ph.php' ? 'active' : ''; ?>">
            <i class="fas fa-vial"></i>
            pH Level
        </a>

        <a href="weight.php" class="nav-item <?php echo $current_page == 'weight.php' ? 'active' : ''; ?>">
            <i class="fas fa-weight"></i>
            Weight
        </a>

        <!-- Admin Section - Only visible for admins -->
        <?php if (isCurrentUserAdmin()): ?>
        <div class="nav-divider"></div>
        <div class="nav-section-title">
            <i class="fas fa-shield-alt me-1"></i> Admin Controls
        </div>

        <a href="admin_dashboard.php" class="nav-item <?php echo $current_page == 'admin_dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i>
            Admin Dashboard
            <span class="admin-only-badge">ADMIN</span>
        </a>

        <a href="user_management.php" class="nav-item <?php echo $current_page == 'user_management.php' ? 'active' : ''; ?>">
            <i class="fas fa-users-cog"></i>
            User Management
            <span class="admin-only-badge">ADMIN</span>
        </a>

        <a href="system_logs.php" class="nav-item <?php echo $current_page == 'system_logs.php' ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-list"></i>
            System Logs
            <span class="admin-only-badge">ADMIN</span>
        </a>
        <?php endif; ?>

        <!-- Analytics -->
        <div class="nav-divider"></div>
        
        <a href="analytics.php" class="nav-item <?php echo $current_page == 'analytics.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            Data Analytics
        </a>
    </nav>

    <a href="logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        Logout
    </a>
</div>