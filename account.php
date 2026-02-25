
<?php

include('db.php');

// Get user info
if(isset($_SESSION['User_Id'])){
    $userId = $_SESSION['User_Id'];
    $stmt = $conn->prepare("SELECT Username, Profile_Pic FROM users WHERE User_Id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userRow = $stmt->get_result()->fetch_assoc();
    $username = $userRow['Username'] ?? "Guest";
    $profilePic = $userRow['Profile_Pic'] ?? "";
} else {
    $username = "Guest";
    $profilePic = "";
}
?>

<!-- User Account Dropdown -->
<div class="user-icon" onclick="toggleDropdown(this)" title="<?= htmlspecialchars($username) ?>">
    <?php if($profilePic): ?>
        <img src="images/<?= htmlspecialchars($profilePic) ?>" alt="User">
    <?php else: ?>
        <i class="fas fa-user-circle fa-2x"></i>
    <?php endif; ?>
    <span><?= htmlspecialchars($username) ?></span>

    <div class="dropdown">
        <a href="profile.php">Profile</a>
        <a href="settings.php">Settings</a>
        <a href="login.html">Logout</a>
    </div>
</div>

<!-- Styles -->
<style>
.user-icon {
    display: flex;
    align-items: center;
    gap: 8px;
    color: green;
    cursor: pointer;
    position: relative;
}
.user-icon img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid white;
}
.user-icon span {
    font-weight: 500;
}
.navbar .nav-item {
    position: relative; /* so dropdown is positioned relative to the button */
}

.dropdown {
    display: none;
    position: absolute; /* relative to nav-item */
    top: 100%;          /* right below the button */
    left: 0;            /* align left edges */
    background: white;
    color: black;
    min-width: 140px;
    box-shadow: 0 0 10px rgba(0,0,0,0.3);
    border-radius: 5px;
    overflow: hidden;
    z-index: 9999;      /* above cards */
}

.dropdown a {
    padding: 10px;
    display: block;
    text-decoration: none;
    color: black;
}
.dropdown a:hover {
    background-color: #f1f1f1;
}
</style>

<!-- Script -->
<script>
function toggleDropdown(el) {
    const dropdown = el.querySelector('.dropdown');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}
window.addEventListener('click', function(e){
    if(!e.target.closest('.user-icon')){
        document.querySelectorAll('.dropdown').forEach(d => d.style.display = 'none');
    }
});
</script>
