<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - Leafcycle</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #23ed99ff, #0f8156ff);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: clamp(16px, 5vw, 40px);
        }

        .logout-container {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
            padding: clamp(32px, 6vw, 56px) clamp(24px, 6vw, 50px);
            width: 100%;
            max-width: 440px;
            text-align: center;
            backdrop-filter: blur(10px);
        }

        /* Brand */
        .brand {
            font-size: clamp(16px, 4vw, 20px);
            font-weight: 800;
            background: linear-gradient(135deg, #23ed99ff, #0f8156ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .brand i { color: #0f8156ff; -webkit-text-fill-color: #0f8156ff; }

        /* Icon */
        .logout-icon {
            width: clamp(64px, 18vw, 84px);
            height: clamp(64px, 18vw, 84px);
            background: linear-gradient(135deg, #23ed99ff, #0f8156ff);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            animation: pulse 2s infinite;
        }
        .logout-icon i { font-size: clamp(28px, 7vw, 40px); color: white; }

        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(35,237,153,0.7); }
            50%       { transform: scale(1.05); box-shadow: 0 0 0 20px rgba(35,237,153,0); }
        }

        h1 {
            font-size: clamp(20px, 5vw, 28px);
            font-weight: 700;
            color: #333;
            margin-bottom: 12px;
        }

        p {
            color: #666;
            font-size: clamp(13px, 3vw, 16px);
            margin-bottom: 20px;
            line-height: 1.6;
        }

        /* Spinner */
        .spinner {
            width: clamp(36px, 10vw, 50px);
            height: clamp(36px, 10vw, 50px);
            margin: 16px auto;
            border: 4px solid #e0e0e0;
            border-top: 4px solid #23ed99ff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        .redirect-text { font-size: clamp(12px, 2.5vw, 14px); color: #999; margin-top: 16px; }

        .manual-link {
            display: none;
            margin-top: 20px;
            padding: clamp(10px, 2.5vw, 14px) clamp(20px, 5vw, 32px);
            background: linear-gradient(135deg, #23ed99ff, #0f8156ff);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: clamp(13px, 3vw, 15px);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(35,237,153,0.3);
        }
        .manual-link:hover { transform: translateY(-2px); box-shadow: 0 6px 25px rgba(35,237,153,0.4); }

        /* Progress bar */
        .progress-bar-wrap {
            width: 100%;
            height: 6px;
            background: #e8f5e9;
            border-radius: 10px;
            overflow: hidden;
            margin: 20px 0 8px;
        }
        .progress-bar {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #23ed99ff, #0f8156ff);
            border-radius: 10px;
            transition: width 2s linear;
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="brand"><i class="fas fa-leaf"></i> Leafcycle</div>

        <div class="logout-icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        
        <h1>Logging You Out…</h1>
        <p>Thank you for using Leafcycle. Your session is being securely terminated.</p>
        
        <div class="spinner"></div>

        <div class="progress-bar-wrap">
            <div class="progress-bar" id="progressBar"></div>
        </div>
        
        <p class="redirect-text">Redirecting to home page in a moment…</p>
        
        <a href="landing.php" class="manual-link" id="manualLink">
            <i class="fas fa-home"></i> Return to Home
        </a>
    </div>

    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
        import { getAuth, signOut } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';

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

        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);

        // Animate progress bar
        requestAnimationFrame(() => {
            document.getElementById('progressBar').style.width = '100%';
        });

        signOut(auth).then(() => {
            sessionStorage.clear();
            localStorage.removeItem('mixerData');
            setTimeout(() => { window.location.href = 'landing.php'; }, 2000);
        }).catch(() => {
            setTimeout(() => { window.location.href = 'landing.php'; }, 2000);
        });

        // Fallback manual link after 5s
        setTimeout(() => {
            document.getElementById('manualLink').style.display = 'inline-block';
        }, 5000);
    </script>

    <?php
    session_start();
    $username = $_SESSION['username'] ?? $_SESSION['userEmail'] ?? 'Unknown';
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
    error_log("[" . date('Y-m-d H:i:s') . "] User logged out: $username");
    ?>
</body>
</html>