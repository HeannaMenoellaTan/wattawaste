<?php
// Get current page filename
$current_page = basename($_SERVER['PHP_SELF']);

if (!function_exists('isCurrentUserAdmin')) {
    function isCurrentUserAdmin() {
        return isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin';
    }
}

$is_admin = false;
try {
    $is_admin = isCurrentUserAdmin();
} catch (Exception $e) {
    $is_admin = false;
}
?>

<!-- Mobile Hamburger Button -->
<button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu">
    <span></span><span></span><span></span>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<div class="sidebar text-center" id="sidebar">
    <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close menu">✕</button>

    <div class="logo mb-4">
        <div class="logo-circle mb-2" style="width:80px;height:80px;border-radius:50%;overflow:hidden;margin:auto;box-shadow:0 0 10px rgba(76,175,80,0.4);">
            <img src="images/qculogo.png" alt="QCU Logo" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none';">
        </div>
        <h2>Leafcycle</h2>
        <p class="small-muted m-0">Aerobic & Anaerobic<br>Waste Hybrid Bin</p>
    </div>
    
    <ul class="menu">
        <li>
            <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                <i class="fa fa-home"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="temperature.php" class="<?php echo $current_page == 'temperature.php' ? 'active' : ''; ?>">
                <i class="fa fa-thermometer-half"></i> Temperature
            </a>
        </li>
        <li>
            <a href="humidity.php" class="<?php echo $current_page == 'humidity.php' ? 'active' : ''; ?>">
                <i class="fa fa-tint"></i> Humidity
            </a>
        </li>
        <li>
            <a href="gas.php" class="<?php echo $current_page == 'gas.php' ? 'active' : ''; ?>">
                <i class="fa fa-fire"></i> Gas Level
            </a>
        </li>
        <li>
            <a href="ph.php" class="<?php echo $current_page == 'ph.php' ? 'active' : ''; ?>">
                <i class="fa fa-flask"></i> pH Level
            </a>
        </li>
        <li>
            <a href="weight.php" class="<?php echo $current_page == 'weight.php' ? 'active' : ''; ?>">
                <i class="fa fa-balance-scale"></i> Weight
            </a>
        </li>
        <li>
            <a href="data.php" class="<?php echo $current_page == 'data.php' ? 'active' : ''; ?>">
                <i class="fa fa-chart-line"></i> Data Analytics
            </a>
        </li>
        
        <?php if ($is_admin): ?>
        <li class="menu-divider">
            <div class="divider"></div>
            <small style="color: rgba(255,255,255,0.7); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">
                <i class="fas fa-shield-alt"></i> Admin
            </small>
        </li>
        <li>
            <a href="admin_dashboard.php" class="<?php echo $current_page == 'admin_dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-shield-alt"></i> Admin Dashboard
            </a>
        </li>
        <li>
            <a href="admin_user.php" class="<?php echo $current_page == 'admin_user.php' ? 'active' : ''; ?>">
                <i class="fas fa-users-cog"></i> User Management
            </a>
        </li>
        <?php endif; ?>
    </ul>
    
    <hr style="opacity:0.2; margin:10px 0;">
    <div class="divider"></div>
    
    <div class="logout">
        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</div>

<style>
/* ── Hamburger Button (mobile only) ── */
.hamburger-btn {
    display: none;
    position: fixed;
    top: 14px;
    left: 14px;
    z-index: 1200;
    background: #0f8156ff;
    border: none;
    border-radius: 10px;
    width: 44px;
    height: 44px;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    padding: 0;
}
.hamburger-btn span {
    display: block;
    width: 22px;
    height: 2.5px;
    background: #fff;
    border-radius: 4px;
    transition: all 0.3s ease;
}
.hamburger-btn.open span:nth-child(1) { transform: translateY(7.5px) rotate(45deg); }
.hamburger-btn.open span:nth-child(2) { opacity: 0; }
.hamburger-btn.open span:nth-child(3) { transform: translateY(-7.5px) rotate(-45deg); }

/* ── Sidebar Close Button (mobile only) ── */
.sidebar-close-btn {
    display: none;
    position: absolute;
    top: 12px;
    right: 14px;
    background: rgba(255,255,255,0.15);
    border: none;
    color: #fff;
    font-size: 18px;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    cursor: pointer;
    z-index: 10;
}

/* ── Overlay ── */
.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    z-index: 1050;
    backdrop-filter: blur(2px);
}
.sidebar-overlay.active { display: block; }

/* ── Sidebar ── */
.sidebar {
    width: 260px;
    height: 100vh;
    background: linear-gradient(to bottom, #17f498ff, #0f8156ff);
    color: #ffffff;
    position: fixed;
    left: 0;
    top: 0;
    padding: 20px 0;
    box-shadow: 4px 0 15px rgba(0,0,0,0.2);
    display: flex;
    overflow-y: auto;
    flex-direction: column;
    transition: transform 0.3s ease;
    scrollbar-width: none;
    z-index: 1100;
}
.sidebar::-webkit-scrollbar { display: none; }

.sidebar .logo {
    text-align: center;
    font-size: 15px;
    font-weight: 800;
    letter-spacing: 1px;
    margin-bottom: 35px;
    color: #140b5fff;
}
.sidebar .logo h2 { margin: 10px 0 5px 0; font-size: 22px; }
.sidebar .small-muted { color: #140b5fff; opacity: 0.8; font-size: 12px; }

.sidebar ul { list-style: none; padding: 0; margin: 0; }
.sidebar ul li { margin: 8px 0; }
.sidebar ul li.menu-divider { margin: 15px 0; text-align: center; }

.sidebar ul li a {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    text-decoration: none;
    color: #110345ff;
    font-size: 15px;
    border-radius: 12px;
    transition: 0.25s ease;
    font-weight: 400;
}
.sidebar ul li a i { margin-right: 12px; font-size: 20px; opacity: 0.9; width: 24px; text-align: center; }
.sidebar ul li a:hover {
    background: linear-gradient(135deg, #3e61ff, #8e44ff);
    color: #ffffff;
    transform: translateX(6px);
    box-shadow: 0 4px 12px rgba(98,75,255,0.4);
}
.sidebar ul li a.active {
    background: linear-gradient(135deg, #3e61ff, #8e44ff);
    color: #ffffff;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(98,75,255,0.4);
}

.sidebar .divider { width: 80%; height: 1px; background: rgba(255,255,255,0.15); margin: 18px auto; }

.sidebar .logout { margin-top: auto; padding: 0 20px; }
.sidebar .logout a {
    background: #ff4d4d;
    color: white !important;
    text-align: center;
    justify-content: center;
    padding: 12px 20px;
    border-radius: 12px;
    font-weight: 500;
    transition: 0.25s;
    display: flex;
    align-items: center;
    text-decoration: none;
}
.sidebar .logout a i { margin-right: 8px; }
.sidebar .logout a:hover {
    background: #ff2d2d;
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(255,77,77,0.4);
}

/* ── Main layout ── */
.main { margin-left: 260px; padding: 20px; min-height: 100vh; }

/* ── Mobile ── */
@media (max-width: 768px) {
    .hamburger-btn { display: flex; }
    .sidebar-close-btn { display: block; }

    .sidebar {
        transform: translateX(-100%);
        width: 260px;
    }
    .sidebar.open { transform: translateX(0); }

    .main {
        margin-left: 0;
        padding: 70px 12px 20px; /* top padding for hamburger */
    }
}
</style>

<script>
(function () {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const hamburger = document.getElementById('hamburgerBtn');
    const closeBtn = document.getElementById('sidebarCloseBtn');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        hamburger.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        hamburger.classList.remove('open');
        document.body.style.overflow = '';
    }

    hamburger.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
    closeBtn.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);

    // Close sidebar when a nav link is clicked on mobile
    sidebar.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) closeSidebar();
        });
    });
})();
</script>