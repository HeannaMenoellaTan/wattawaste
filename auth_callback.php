<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signing in...</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #23ed99, #0f8156);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .box {
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            max-width: 380px;
            width: 90%;
        }
        .spinner {
            width: 52px; height: 52px;
            border: 5px solid #e0e0e0;
            border-top-color: #0f8156;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        h2 { color: #0f8156; font-size: 22px; margin-bottom: 8px; }
        p  { color: #888; font-size: 14px; }
        .error-box {
            display: none;
            background: #ffebee; color: #c62828;
            padding: 14px; border-radius: 10px;
            margin-top: 20px; font-size: 14px;
        }
        .back-btn {
            display: none;
            margin-top: 16px;
            padding: 10px 24px;
            background: #0f8156;
            color: white; border: none;
            border-radius: 10px; font-size: 14px;
            cursor: pointer; font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="spinner" id="spinner"></div>
        <h2 id="title">Signing you in...</h2>
        <p id="subtitle">Please wait while we verify your account.</p>
        <div class="error-box" id="errorBox"></div>
        <button class="back-btn" id="backBtn" onclick="window.location.replace('login.php')">
            ← Back to Login
        </button>
    </div>

    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
        import {
            getAuth,
            getRedirectResult,
            onAuthStateChanged
        } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
        import { getDatabase, ref, set, update, get } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-database.js';

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

        const app      = initializeApp(firebaseConfig);
        const auth     = getAuth(app);
        const database = getDatabase(app);

        const ADMIN_EMAILS = [
            'tan.heannamenoella.rebolledo@gmail.com',
            'quimoragwyneth61501@gmail.com'
        ];

        function showError(msg) {
            document.getElementById('spinner').style.display = 'none';
            document.getElementById('title').textContent = 'Sign-in Failed';
            document.getElementById('subtitle').textContent = '';
            const eb = document.getElementById('errorBox');
            eb.textContent = msg;
            eb.style.display = 'block';
            document.getElementById('backBtn').style.display = 'inline-block';
        }

        function getDestination(email) {
            return ADMIN_EMAILS.includes(email) ? 'admin_dashboard.php' : 'index.php';
        }

        async function syncUser(user) {
            if (!user || user.isAnonymous) return;
            let provider = 'email', providerId = 'password';
            if (user.providerData?.length > 0) {
                providerId = user.providerData[0].providerId;
                if (providerId === 'google.com')   provider = 'google';
                if (providerId === 'facebook.com') provider = 'facebook';
                if (providerId === 'phone')        provider = 'phone';
            }
            const userRef = ref(database, 'users/' + user.uid);
            const userData = {
                name: user.displayName || user.email || user.phoneNumber || 'User',
                Username: user.displayName || user.email || user.phoneNumber || 'User',
                email: user.email || 'N/A', Email: user.email || 'N/A',
                role: 'User', Role: 'User',
                status: 'Active', Status: 'Active',
                provider, providerId,
                photoURL: user.photoURL || null,
                phoneNumber: user.phoneNumber || null,
                emailVerified: user.emailVerified || false,
                lastLogin: new Date().toLocaleString(),
                lastActive: new Date().toLocaleString(),
                createdAt: user.metadata.creationTime,
                lastSignInTime: user.metadata.lastSignInTime
            };
            try {
                const snap = await get(userRef);
                if (snap.exists()) {
                    await update(userRef, {
                        lastLogin: userData.lastLogin,
                        lastActive: userData.lastActive,
                        lastSignInTime: userData.lastSignInTime,
                        emailVerified: userData.emailVerified,
                        photoURL: userData.photoURL,
                        phoneNumber: userData.phoneNumber
                    });
                } else {
                    await set(userRef, userData);
                }
            } catch(e) { console.error('DB sync error:', e); }
        }

        function handleUser(user) {
            if (!user) {
                showError('No user session found. Please try logging in again.');
                return;
            }
            console.log('✅ Got user:', user.email || user.phoneNumber);
            syncUser(user).then(() => {
                sessionStorage.setItem('userEmail', user.email || '');
                sessionStorage.setItem('userName',  user.displayName || '');
                sessionStorage.setItem('userId',    user.uid);
                document.getElementById('title').textContent = 'Success!';
                document.getElementById('subtitle').textContent = 'Redirecting to your dashboard...';
                setTimeout(() => {
                    window.location.replace(getDestination(user.email));
                }, 800);
            });
        }

        // ── Try getRedirectResult first ────────────────────────────────────────────
        console.log('auth_callback: checking getRedirectResult...');
        try {
            const result = await getRedirectResult(auth);
            console.log('getRedirectResult result:', result);
            if (result?.user) {
                handleUser(result.user);
            } else {
                // No redirect result — fall back to onAuthStateChanged
                console.log('No redirect result, checking auth state...');
                let resolved = false;
                const timeout = setTimeout(() => {
                    if (!resolved) showError('Sign-in timed out. Please try again.');
                }, 8000);

                onAuthStateChanged(auth, (user) => {
                    if (resolved) return;
                    resolved = true;
                    clearTimeout(timeout);
                    handleUser(user);
                });
            }
        } catch(err) {
            console.error('getRedirectResult error:', err);
            showError('Sign-in failed: ' + (err.message || err.code));
        }
    </script>
</body>
</html>