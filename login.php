<?php
session_start();
require_once('firebase_config.php');

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        
        // Get Firebase database instance
        $database = getDatabase();
        
        // Query users from Firebase
        // Assuming your Firebase structure is: users/{userId}/{Username, Password, Role, Status}
        $usersRef = $database->getReference('users');
        $snapshot = $usersRef->orderByChild('Username')->equalTo($username)->getSnapshot();
        
        if ($snapshot->exists()) {
            $users = $snapshot->getValue();
            
            // Get the first (and should be only) matching user
            $userData = reset($users);
            $userId = key($users);
            
            // Verify password and status
            if ($userData['Password'] === $password && $userData['Status'] === 'Active') {
                // Store data in session
                $_SESSION['username'] = $userData['Username'];
                $_SESSION['role'] = $userData['Role'];
                $_SESSION['user_id'] = $userId;
                
                // Redirect depending on role
                if ($userData['Role'] === 'Admin') {
                    header("Location: admin_dasboard.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $error = "Invalid Credentials";
            }
        } else {
            $error = "Invalid Credentials";
        }
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "An error occurred. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<style>
    body {
        background-color: #eaf5ea;
        font-family: 'Poppins', sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }
    .login-container {
        background-color: white;
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        width: 350px;
        text-align: center;
    }
    h2 {
        color: #2c4e32;
        margin-bottom: 10px;
    }
    input[type="text"], input[type="password"] {
        width: 100%;
        padding: 10px;
        margin: 8px 0;
        border-radius: 8px;
        border: 1px solid #ccc;
        box-sizing: border-box;
    }
    .login-btn {
        background-color: #4caf50;
        color: white;
        border: none;
        padding: 10px;
        width: 100%;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
    }
    .login-btn:hover {
        background-color: #45a049;
    }
    .error {
        color: red;
        background-color: #ffe5e5;
        padding: 5px;
        border-radius: 6px;
        margin-bottom: 10px;
    }
</style>
</head>
<body>
<div class="login-container">
    <img src="images/qculogo.png" alt="Logo" width="80">
    <h2>Welcome Back!</h2>
    <p>Please enter your details</p>
    <?php if ($error) echo "<div class='error'>$error</div>"; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="User name" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit" class="login-btn">Log-In</button>
    </form>
    <p>Don't have an account? <a href="#">Sign Up</a></p>
</div>
</body>
</html>