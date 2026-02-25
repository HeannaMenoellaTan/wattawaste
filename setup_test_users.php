<?php
/**
 * Setup Test Users in Firebase
 * Run this ONCE to create admin and user test accounts
 * 
 * Usage: Access via browser: http://localhost/your_project/setup_test_users.php
 */

require_once 'firebase_config.php';

// Security: Comment out this line after first run
// die("Setup already completed. Delete this file or comment out this line to run again.");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Test Users - WattAWaste</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #23ed99ff, #0f8156ff);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .setup-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #2E7D32;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .user-card {
            background: #f5f5f5;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 5px solid;
        }
        .admin-card {
            border-left-color: #ef4444;
        }
        .user-card-content {
            border-left-color: #3b82f6;
        }
        .user-type {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .admin-type {
            color: #ef4444;
        }
        .user-type-regular {
            color: #3b82f6;
        }
        .credential {
            margin: 5px 0;
            font-family: 'Courier New', monospace;
        }
        .credential strong {
            display: inline-block;
            width: 120px;
        }
        .btn {
            background: linear-gradient(135deg, #23ed99ff, #0f8156ff);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(35, 237, 153, 0.4);
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 10px;
            display: none;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <h1>🔧 Setup Test Users</h1>
        <p class="subtitle">Create admin and user test accounts in Firebase</p>

        <div class="warning">
            <strong>⚠️ Important:</strong> You must create these accounts in Firebase Authentication first!
            Go to Firebase Console → Authentication → Add User
        </div>

        <div class="user-card admin-card">
            <div class="user-type admin-type">👨‍💼 ADMIN ACCOUNT</div>
            <div class="credential"><strong>Email:</strong> admin@wattawaste.com</div>
            <div class="credential"><strong>Password:</strong> Admin@2024</div>
            <div class="credential"><strong>Username:</strong> admin</div>
            <div class="credential"><strong>Role:</strong> Admin</div>
            <div class="credential"><strong>Access:</strong> Full system access</div>
        </div>

        <div class="user-card user-card-content">
            <div class="user-type user-type-regular">👤 USER ACCOUNT</div>
            <div class="credential"><strong>Email:</strong> user@wattawaste.com</div>
            <div class="credential"><strong>Password:</strong> User@2024</div>
            <div class="credential"><strong>Username:</strong> testuser</div>
            <div class="credential"><strong>Role:</strong> User</div>
            <div class="credential"><strong>Access:</strong> Sensor monitoring only</div>
        </div>

        <button class="btn" onclick="createUsersInFirebase()">
            <i class="fas fa-plus-circle"></i> Create Users in Firebase Database
        </button>

        <div id="result" class="result"></div>

        <div style="margin-top: 30px; padding: 20px; background: #e3f2fd; border-radius: 10px;">
            <h3 style="margin-top: 0; color: #1976d2;">📝 Manual Setup Instructions:</h3>
            <ol style="line-height: 1.8;">
                <li>Go to <a href="https://console.firebase.google.com" target="_blank">Firebase Console</a></li>
                <li>Select your project: <strong>wattawaste-d3503</strong></li>
                <li>Click <strong>Authentication</strong> → <strong>Users</strong> tab</li>
                <li>Click <strong>Add User</strong> and create:
                    <ul>
                        <li><strong>Admin:</strong> admin@wattawaste.com / Admin@2024</li>
                        <li><strong>User:</strong> user@wattawaste.com / User@2024</li>
                    </ul>
                </li>
                <li>Then click the button above to add their data to Realtime Database</li>
            </ol>
        </div>
    </div>

    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
        import { getDatabase, ref, set } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-database.js';

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
        const database = getDatabase(app);

        window.createUsersInFirebase = async function() {
            const resultDiv = document.getElementById('result');
            resultDiv.style.display = 'block';
            resultDiv.className = 'result';
            resultDiv.innerHTML = '<p>Creating users...</p>';

            try {
                // Create Admin User
                await set(ref(database, 'users/admin_001'), {
                    Username: 'admin',
                    Email: 'admin@wattawaste.com',
                    Phone: '+639123456789',
                    Role: 'Admin',
                    Status: 'Active',
                    CreatedAt: Date.now()
                });

                // Create Regular User
                await set(ref(database, 'users/user_001'), {
                    Username: 'testuser',
                    Email: 'user@wattawaste.com',
                    Phone: '+639987654321',
                    Role: 'User',
                    Status: 'Active',
                    CreatedAt: Date.now()
                });

                resultDiv.className = 'result success';
                resultDiv.innerHTML = `
                    <h3>✅ Success!</h3>
                    <p><strong>Users created in Firebase Realtime Database!</strong></p>
                    <p>Now create these accounts in Firebase Authentication:</p>
                    <ul>
                        <li>admin@wattawaste.com (Password: Admin@2024)</li>
                        <li>user@wattawaste.com (Password: User@2024)</li>
                    </ul>
                    <p style="margin-top: 15px;">
                        <a href="login.html" style="color: #155724; font-weight: 700;">
                            → Go to Login Page
                        </a>
                    </p>
                `;

            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `
                    <h3>❌ Error</h3>
                    <p>${error.message}</p>
                    <p>Make sure you've set up Firebase correctly.</p>
                `;
            }
        };
    </script>
</body>
</html>