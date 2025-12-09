<?php
session_start();
include('db.php');

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Dummy admin data (replace with DB fetch)
$admin = [
    'name' => 'Admin Name',
    'username' => $_SESSION['username'],
    'email' => 'admin@example.com'
];

// Dummy users list (replace with DB fetch)
$users = [
    ['id'=>1, 'name'=>'John Doe', 'username'=>'john', 'role'=>'Staff', 'email'=>'john@example.com'],
    ['id'=>2, 'name'=>'Jane Smith', 'username'=>'jane', 'role'=>'Viewer', 'email'=>'jane@example.com']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Settings</title>
<?php  
include_once 'notif_bell.php';
?>
<style>
body { font-family: Arial, sans-serif; background:#f5f7f7; margin:0; color:#2f5233; }
.main { padding:20px; max-width:1200px; margin:auto; }
h2 { text-align:center; margin-bottom:30px; }
.tabs { display:flex; gap:10px; margin-bottom:20px; cursor:pointer; }
.tab { padding:10px 20px; background:#fff; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
.tab.active { background:#2f5233; color:#fff; }
.tab-content { display:none; background:#fff; padding:20px; border-radius:12px; box-shadow:0 3px 8px rgba(0,0,0,0.15); }
.tab-content.active { display:block; }
input, select { width:100%; padding:8px; margin:8px 0 15px; border-radius:6px; border:1px solid #ccc; }
button { padding:10px 20px; border:none; border-radius:6px; background:#2f5233; color:#fff; cursor:pointer; margin-top:10px; }
table { width:100%; border-collapse:collapse; margin-top:15px; }
table, th, td { border:1px solid #ccc; }
th, td { padding:10px; text-align:left; }
</style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main">
<?php include 'topnav.php';?>
<h2>⚙️ Admin Settings</h2>

<div class="tabs">
    <div class="tab active" onclick="openTab('profile')">Profile</div>
    <div class="tab" onclick="openTab('system')">System</div>
    <div class="tab" onclick="openTab('users')">Users</div>
    <div class="tab" onclick="openTab('api')">API</div>
    <div class="tab" onclick="openTab('reports')">Reports</div>
</div>

<!-- Profile Tab -->
<div class="tab-content active" id="profile">
    <h3>Profile Settings</h3>
    <form>
        <label>Name</label>
        <input type="text" value="<?= $admin['name'] ?>">
        <label>Username</label>
        <input type="text" value="<?= $admin['username'] ?>">
        <label>Email</label>
        <input type="email" value="<?= $admin['email'] ?>">
        <label>Change Password</label>
        <input type="password" placeholder="New Password">
        <button type="submit">Save Profile</button>
    </form>
</div>

<!-- System Tab -->
<div class="tab-content" id="system">
    <h3>System Settings</h3>
    <form>
        <label>Temperature Threshold (°C)</label>
        <input type="number" value="50">
        <label>Humidity Threshold (%)</label>
        <input type="number" value="70">
        <label>Gas Level Threshold (ppm)</label>
        <input type="number" value="100">
        <label>pH Level Threshold</label>
        <input type="number" value="7">
        <label>Fertilizer Alert (%)</label>
        <input type="number" value="20">
        <button type="submit">Save System Settings</button>
    </form>
</div>

<!-- Users Tab -->
<div class="tab-content" id="users">
    <h3>User Management</h3>
    <button onclick="alert('Add user form placeholder')">Add New User</button>
    <table>
        <thead>
            <tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Action</th></tr>
        </thead>
        <tbody>
            <?php foreach($users as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= $u['name'] ?></td>
                    <td><?= $u['username'] ?></td>
                    <td><?= $u['email'] ?></td>
                    <td><?= $u['role'] ?></td>
                    <td>
                        <button onclick="alert('Edit user <?= $u['name'] ?>')">Edit</button>
                        <button onclick="alert('Delete user <?= $u['name'] ?>')">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- API Tab -->
<div class="tab-content" id="api">
    <h3>API / Integration Settings</h3>
    <form>
        <label>Trefle API Token</label>
        <input type="text" value="usr-PAxkHSBd0vJNQXNK4hM8Ig8adcaIUmwy-74hO6EMyyw">
        <label>IoT Device Endpoint</label>
        <input type="text" value="http://example.com/api/device">
        <button type="submit">Save API Settings</button>
    </form>
</div>

<!-- Reports Tab -->
<div class="tab-content" id="reports">
    <h3>Reports Settings</h3>
    <form>
        <label>Default Report Type</label>
        <select>
            <option>Daily</option>
            <option>Weekly</option>
            <option>Monthly</option>
        </select>
        <label>Download Format</label>
        <select>
            <option>PDF</option>
            <option>CSV</option>
            <option>Excel</option>
        </select>
        <button type="submit">Save Reports Settings</button>
    </form>
</div>

</div>

<script>
function openTab(tabId){
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.querySelector('.tab[onclick="openTab(\''+tabId+'\')"]').classList.add('active');
    document.getElementById(tabId).classList.add('active');
}
</script>

</body>
</html>
