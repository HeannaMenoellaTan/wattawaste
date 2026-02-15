<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - WattAWaste</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #23ed99ff, #0f8156ff);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .logout-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 50px 45px;
            width: 100%;
            max-width: 440px;
            text-align: center;
            backdrop-filter: blur(10px);
        }

        .logout-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #23ed99ff, #0f8156ff);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            animation: pulse 2s infinite;
        }

        .logout-icon i {
            font-size: 40px;
            color: white;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(35, 237, 153, 0.7);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 0 0 20px rgba(35, 237, 153, 0);
            }
        }

        h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 12px;
        }

        p {
            color: #666;
            font-size: 16px;
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .spinner {
            width: 50px;
            height: 50px;
            margin: 20px auto;
            border: 4px solid #e0e0e0;
            border-top: 4px solid #23ed99ff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .redirect-text {
            font-size: 14px;
            color: #999;
            margin-top: 20px;
        }

        .manual-link {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 28px;
            background: linear-gradient(135deg, #23ed99ff, #0f8156ff);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(35, 237, 153, 0.3);
        }

        .manual-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(35, 237, 153, 0.4);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="logout-container">
        <div class="logout-icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        
        <h1>Logging You Out...</h1>
        <p>Thank you for using WattAWaste. Your session is being securely terminated.</p>
        
        <div class="spinner"></div>
        
        <p class="redirect-text">Redirecting to home page...</p>
        
        <a href="landing.php" class="manual-link" style="display: none;" id="manualLink">
            <i class="fas fa-home"></i> Return to Home
        </a>
    </div>

    <!-- Firebase Sign Out -->
    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
        import { getAuth, signOut } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';

        // Firebase Configuration
        const firebaseConfig = {
            apiKey: "AIzaSyAu9hOwjiuAl9PCh50HefMGZU9XDosu68I",
            authDomain: "wattawaste-d3503.firebaseapp.com",
            databaseURL: "https://wattawaste-d3503-default-rtdb.asia-southeast1.firebasedatabase.app",
            projectId: "wattawaste-d3503",
            storageBucket: "wattawaste-d3503.firebasestorage.app",
            messagingSenderId: "842761118644",
            appId: "1:842761118644:web:ddef65fd892486f67f88e1",
            measurementId: "G-33Z8K3NBY1"
        };

        // Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);

        // Sign out from Firebase
        signOut(auth).then(() => {
            console.log('✅ Firebase sign-out successful');
            
            // Clear session storage
            sessionStorage.clear();
            localStorage.removeItem('mixerData');
            
            console.log('✅ Session cleared');
            
            // Redirect to landing page after 2 seconds
            setTimeout(() => {
                window.location.href = 'landing.php';
            }, 2000);
            
        }).catch((error) => {
            console.error('❌ Sign-out error:', error);
            
            // Still redirect even if there's an error
            setTimeout(() => {
                window.location.href = 'landing.php';
            }, 2000);
        });

        // Show manual link after 5 seconds as fallback
        setTimeout(() => {
            document.getElementById('manualLink').style.display = 'inline-block';
        }, 5000);
    </script>

    <?php
    /**
     * Logout Script - PHP Session Cleanup
     * Handles user logout and session cleanup
     */
    
    // Start session
    session_start();
    
    // Store username for logging
    $username = $_SESSION['username'] ?? $_SESSION['userEmail'] ?? 'Unknown';
    
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    // Log the logout
    error_log("[" . date('Y-m-d H:i:s') . "] User logged out: $username");
    
    // Note: Redirect is handled by JavaScript for Firebase sign-out
    // PHP session is cleaned up above
    ?>
</body>
</html>