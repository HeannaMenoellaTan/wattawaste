<!-- Sidebar -->
<div class="sidebar text-center">
    <div class="logo mb-4">
      <div class="logo-circle mb-2" style="width:80px;height:80px;border-radius:50%;overflow:hidden;margin:auto;box-shadow:0 0 10px rgba(76,175,80,0.4);">
        <img src="images/qculogo.png" alt="QCU Logo" style="width:100%;height:100%;object-fit:cover;">
      </div>
      <h2>WattAWaste</h2>
      <p class="small-muted m-0">Aerobic & Anaerobic<br>Waste Hybrid Bin</p>
    </div>
    <ul class="menu">
      <li><a href="index.php"><i class="fa fa-home"></i> Dashboard</a></li>
      <li><a href="temperature.php"><i class="fa fa-thermometer-half"></i> Temperature</a></li>
      <li><a href="humidity.php"><i class="fa fa-tint"></i> Humidity</a></li>
      <li><a href="gas.php"><i class="fa fa-fire"></i> Gas Level</a></li>
      <li><a href="ph.php"><i class="fa fa-flask"></i> pH Level</a></li>
      <li><a href="weight.php" class="active"><i class="fa fa-balance-scale"></i> Weight</a></li>
      <li><a href="data.php"><i class="fa fa-chart-line"></i> Data Analytics</a></li>
    </ul>

    <hr style="opacity:0.2; margin:10px 0;">
    <div class="divider"></div>

    <div class="logout">
        <a href="login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</div>

<style>
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
    transition: 0.3s ease;
    scrollbar-width: none;
  }
  
  .sidebar::-webkit-scrollbar {
    display: none;
  }
  
  .sidebar .logo {
    text-align: center;
    font-size: 15px;
    font-weight: 800;
    letter-spacing: 1px;
    margin-bottom: 35px;
    color: #140b5fff;
  }

  .sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .sidebar ul li {
    margin: 8px 0;
  }

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

  .sidebar ul li a i {
    margin-right: 12px;
    font-size: 30px;
    opacity: 0.9;
  }

  .sidebar ul li a:hover {
    background: linear-gradient(135deg, #3e61ff, #8e44ff);
    color: #ffffff;
    transform: translateX(6px);
    box-shadow: 0 4px 12px rgba(98, 75, 255, 0.4);
  }

  .sidebar ul li a.active {
    background: linear-gradient(135deg, #3e61ff, #8e44ff);
    color: #ffffff;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(98, 75, 255, 0.4);
  }

  .sidebar .divider {
    width: 80%;
    height: 1px;
    background: rgba(255,255,255,0.15);
    margin: 18px auto;
  }

  .sidebar .logout {
    margin-top: auto;
    padding: 0 20px;
  }

  .sidebar .logout a {
    background: #ff4d4d;
    color: white !important;
    text-align: center;
    justify-content: center;
    padding: 12px 20px;
    border-radius: 12px;
    font-weight: 500;
    transition: 0.25s;
  }

  .sidebar .logout a:hover {
    background: #ff2d2d;
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(255, 77, 77, 0.4);
  }
  
  .main {
    margin-left: 260px;
    padding: 20px;
  }
</style>