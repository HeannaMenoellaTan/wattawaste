 <!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>WattAWaste Bin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
 
 <div class="sidebar text-center">
    <div class="logo mb-4">
      <div class="logo-circle mb-2" style="width:80px;height:80px;border-radius:50%;overflow:hidden;margin:auto;box-shadow:0 0 10px rgba(76,175,80,0.4);">
        <img src="images/qculogo.png" alt="QCU Logo" style="width:100%;height:100%;object-fit:cover;">
      </div>
      <h2>WattAWaste</h2>
      <p class="small-muted m-0">Aerobic & Anaerobic<br>Waste Hybrid Bin</p>
    </div>
    <ul class="menu">
  <li><a href="admin_dasboard.php"><i class="fa fa-home"></i> Dashboard</a></li>

    <!-- Sensors Main -->
  

    <!-- 🔽 Sensor Categories -->
    <li><a href="Users.php"><i class="fa fa-thermometer-half"></i> users</a></li>
    <li><a href="Plant.php"><i class="fa fa-tint"></i> Plant Data</a></li>
    <li><a href="report.php"><i class="fa fa-fire"></i> Reports</a></li>
    <li><a href="settings.php"><i class="fa fa-flask"></i> Settings</a></li>
   
</ul>

  <hr style="opacity:0.2; margin:10px 0;">
  <div class="divider"></div>

    <div class="logout">
        <a href="login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</div>

<!-- FontAwesome Icons -->

<style>


  /* Sidebar Container */
  .sidebar {
    width: 260px;
    height: 100vh;
    background: linear-gradient(to bottom, #23ed99ff, #0f8156ff);
    color: #ffffff;
    position: fixed;
    left: 0;
    top: 0;
    padding: 20px 0;
    box-shadow: 4px 0 15px rgba(0,0,0,0.2);
    display: flex;
     overflow-y: auto;    /* ✅ allows scrolling */
    flex-direction: column;
    transition: 0.3s ease;
     scrollbar-width: none;   /* Firefox */
  }
.sidebar::-webkit-scrollbar {
    display: none;           /* Chrome, Edge, Safari */
}
.sidebar .logo {
    text-align: center;
    font-size: 15px;
    font-weight: 800;
    letter-spacing: 1px;
    margin-bottom: 35px;
    color: #140b5fff;

  }



  /* Menu List */
  .sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  /* Each Menu Item */
  .sidebar ul li {
    margin: 8px 0;
  }

  /* Premium Button Style */
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

  /* Icon spacing */
  .sidebar ul li a i {
    margin-right: 12px;
    font-size: 30px;
    opacity: 0.9;
  }

  /* Hover Effect – Premium Vibrant Glow */
  .sidebar ul li a:hover {
    background: linear-gradient(135deg, #3e61ff, #8e44ff);
    color: #ffffff;
    transform: translateX(6px);
    box-shadow: 0 4px 12px rgba(98, 75, 255, 0.4);
  }

  /* Active / Current Page */
  .sidebar ul li a.active {
    background: linear-gradient(135deg, #3e61ff, #8e44ff);
    color: #ffffff;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(98, 75, 255, 0.4);
  }

  /* Separator Line */
  .sidebar .divider {
    width: 80%;
    height: 1px;
    background: rgba(255,255,255,0.15);
    margin: 18px auto;
  }

  /* Logout Button */
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
  margin-left: 250px; /* same as sidebar width */
  padding-top: 70px;  /* same as topnav height */
  padding-left: 20px; /* optional for spacing */
  padding-right: 20px;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}




</style>
</body>
</html>