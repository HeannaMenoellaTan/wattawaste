<?php
/**
 * Admin Navigation - Custom Design
 * For Admin Dashboard Only
 */

require_once 'check_role.php';

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php 
        $titles = [
            'admin_dashboard.php' => 'Admin Dashboard',
            'admin_user.php' => 'User Management',
            'index.php' => "Plant's Data",
            'admin_settings.php' => 'System Settings'
        ];
        echo ($titles[$current_page] ?? 'Admin Panel') . ' - WattAWaste';
    ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f5f7fa;
}

.admin-layout {
    display: flex;
    min-height: 100vh;
}

/* Sidebar Styles */
.admin-sidebar {
    width: 238px;
    background: linear-gradient(180deg, #d4f1d4 0%, #b8e6b8 100%);
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    display: flex;
    flex-direction: column;
    box-shadow: 2px 0 10px rgba(0,0,0,0.05);
    z-index: 1000;
}

/* Logo Section */
.admin-logo {
    padding: 30px 20px;
    text-align: center;
    background: white;
    margin: 20px 20px 30px 20px;
    border-radius: 20px;
}

.logo-circle {
    width: 80px;
    height: 80px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.logo-icon {
    width: 50px;
    height: 50px;
}

.logo-title {
    font-size: 14px;
    color: #2d5016;
    font-weight: 600;
    line-height: 1.4;
    margin-top: 10px;
}

/* Navigation Menu */
.admin-menu {
    flex: 1;
    padding: 0 10px;
}

.admin-menu-item {
    display: flex;
    align-items: center;
    padding: 14px 20px;
    margin: 5px 0;
    color: #2d5016;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s ease;
    position: relative;
}

.admin-menu-item i {
    width: 24px;
    margin-right: 12px;
    font-size: 18px;
}

.admin-menu-item:hover {
    background: rgba(255,255,255,0.5);
    transform: translateX(5px);
}

.admin-menu-item.active {
    background: #2d5016;
    color: white;
    box-shadow: 0 4px 12px rgba(45,80,22,0.3);
}

.admin-menu-item.active i {
    color: white;
}

/* Logout Button */
.admin-logout {
    padding: 20px;
    margin-top: auto;
}

.logout-btn {
    display: flex;
    align-items: center;
    padding: 14px 20px;
    background: rgba(239,68,68,0.1);
    color: #dc2626;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.logout-btn:hover {
    background: #dc2626;
    color: white;
    border-color: white;
    transform: translateY(-2px);
}

.logout-btn i {
    margin-right: 10px;
}

.version-text {
    text-align: center;
    color: #6b7280;
    font-size: 11px;
    margin-top: 10px;
}

/* Main Content Area */
.admin-main {
    margin-left: 238px;
    flex: 1;
    padding: 20px;
    min-height: 100vh;
}

/* Top Bar */
.admin-topbar {
    background: white;
    border-radius: 12px;
    padding: 15px 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-header {
    display: flex;
    align-items: center;
    gap: 15px;
}

.page-icon {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #d4f1d4, #b8e6b8);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}

.page-title-text {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
}

.admin-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.admin-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
    animation: adminPulse 2s infinite;
}

@keyframes adminPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
    50% { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
}

.admin-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(135deg, #23ed99ff, #0f8156ff);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 18px;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.datetime-display {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6b7280;
    font-size: 14px;
    font-weight: 600;
}

.datetime-display i {
    color: #4CAF50;
}

/* Security Notice */
.security-notice {
    background: #FFF3CD;
    border-left: 4px solid #FFC107;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
}

.security-notice i {
    color: #FF6B00;
    font-size: 18px;
}

/* Content Wrapper */
.admin-content {
    /* Content from individual pages goes here */
}

/* Responsive Design */
@media (max-width: 768px) {
    .admin-sidebar {
        width: 200px;
    }
    
    .admin-main {
        margin-left: 200px;
    }
    
    .admin-topbar {
        flex-direction: column;
        gap: 15px;
    }
}
</style>

<script>
// Update DateTime
function updateDateTime() {
    const now = new Date();
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: true 
    };
    const dateTimeElement = document.getElementById('currentDateTime');
    if (dateTimeElement) {
        dateTimeElement.textContent = now.toLocaleDateString('en-US', options);
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    updateDateTime();
    setInterval(updateDateTime, 60000); // Update every minute
});
</script>
</head>
<body>

<div class="admin-layout">
    <!-- Admin Sidebar -->
    <div class="admin-sidebar">
        <!-- Logo Section -->
        <div class="admin-logo">
            <div class="logo-circle">
                <img src="assets/images/logo.png" alt="WattAWaste" class="logo-icon" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <i class="fas fa-recycle" style="font-size: 32px; color: #4CAF50; display: none;"></i>
            </div>
            <div class="logo-title">" WattAWaste " Aerobic and Anaerobic<br>Waste Hybrid Bin</div>
        </div>

        <!-- Navigation Menu -->
        <nav class="admin-menu">
            <a href="admin_dashboard.php" class="admin-menu-item <?php echo $current_page == 'admin_dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Admin Dashboard</span>
            </a>

            <a href="admin_user.php" class="admin-menu-item <?php echo $current_page == 'admin_user.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>User Management</span>
            </a>

            <a href="index.php" class="admin-menu-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-seedling"></i>
                <span>Plant Data</span>
            </a>

            <a href="admin_settings.php" class="admin-menu-item <?php echo $current_page == 'admin_settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </nav>

        <!-- Logout Section -->
        <div class="admin-logout">
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Log Out</span>
            </a>
            <div class="version-text">Hybrid Compost Bin V 1.0</div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="admin-main">
        <!-- Top Bar -->
        <div class="admin-topbar">
            <div class="page-header">
                <div class="page-icon">
                    <?php 
                    // Dynamic icon based on page
                    $icons = [
                        'admin_dashboard.php' => '📊',
                        'admin_user.php' => '👥',
                        'index.php' => '🌱',
                        'admin_settings.php' => '⚙️'
                    ];
                    echo $icons[$current_page] ?? '📄';
                    ?>
                </div>
                <div>
                    <div class="page-title-text">
                        <?php 
                        $titles = [
                            'admin_dashboard.php' => 'Admin Dashboard',
                            'admin_user.php' => 'User Management',
                            'index.php' => "Plant's Data",
                            'admin_settings.php' => 'System Settings'
                        ];
                        echo $titles[$current_page] ?? 'Admin Panel';
                        ?>
                    </div>
                </div>
            </div>

            <div class="admin-info">
                <div class="datetime-display">
                    <i class="far fa-clock"></i>
                    <span id="currentDateTime">Loading...</span>
                </div>

                <div class="admin-badge">
                    <i class="fas fa-shield-alt"></i>
                    <span>ADMIN</span>
                </div>

                <div class="admin-avatar" title="<?php echo htmlspecialchars(getCurrentUsername()); ?>">
                    <?php echo strtoupper(substr(getCurrentUsername(), 0, 1)); ?>
                </div>
            </div>
        </div>

        <!-- Security Notice (optional - shows on dashboard only) -->
        <?php if ($current_page == 'admin_dashboard.php'): ?>
        <div class="security-notice">
            <i class="fas fa-shield-alt"></i>
            <div>
                <strong>Security Notice:</strong> You are logged in as an administrator. 
                Please be careful with user data and system settings.
            </div>
        </div>
        <?php endif; ?>

        <!-- Page Content Goes Here -->
        <div class="admin-content">