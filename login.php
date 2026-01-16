<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Firebase Authentication</title>
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

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 50px 45px;
            width: 100%;
            max-width: 440px;
            backdrop-filter: blur(10px);
        }

        .logo {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo h1 {
            font-size: 36px;
            font-weight: 800;
            background: linear-gradient(135deg, #23ed99ff, #0f8156ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo p {
            color: #666;
            font-size: 14px;
            margin-top: 8px;
        }

        .method-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }

        .tab-btn {
            flex: 1;
            padding: 12px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s ease;
        }

        .tab-btn:hover {
            border-color: #23ed99ff;
            color: #0f8156ff;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #23ed99ff, #0f8156ff);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(35, 237, 153, 0.4);
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .input-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: white;
        }

        .input-group input:focus {
            outline: none;
            border-color: #23ed99ff;
            box-shadow: 0 0 0 3px rgba(35, 237, 153, 0.1);
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 25px;
        }

        .forgot-password a {
            color: #0f8156ff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #23ed99ff, #0f8156ff);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(35, 237, 153, 0.3);
        }

        .login-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(35, 237, 153, 0.4);
        }

        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
            color: #999;
            font-size: 13px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e0e0e0;
        }

        .divider span {
            padding: 0 15px;
        }

        .google-btn {
            width: 100%;
            padding: 14px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .google-btn:hover:not(:disabled) {
            border-color: #23ed99ff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .google-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .google-icon {
            width: 20px;
            height: 20px;
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        .hidden {
            display: none;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 40px 30px;
            }

            .logo h1 {
                font-size: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>Welcome Back</h1>
            <p>Login to your account</p>
        </div>

        <div class="error-message" id="errorMessage"></div>
        <div class="success-message" id="successMessage"></div>

        <div class="method-tabs">
            <button class="tab-btn active" id="emailTab">Email</button>
            <button class="tab-btn" id="phoneTab">Phone</button>
        </div>

        <form id="loginForm">
            <div id="emailForm">
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" placeholder="your.email@gmail.com" required>
                </div>

                <div class="input-group">
                    <label for="emailPassword">Password</label>
                    <input type="password" id="emailPassword" placeholder="Enter your password" required>
                </div>

                <div class="forgot-password">
                    <a id="forgotPasswordLink">Forgot Password?</a>
                </div>

                <button type="submit" class="login-btn" id="emailLoginBtn">Login with Email</button>
            </div>

            <div id="phoneForm" class="hidden">
                <div class="input-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" placeholder="+639212429795">
                    <small style="color: #666; font-size: 12px; margin-top: 4px; display: block;">
                        Format: +[country code][number] (e.g., +639212429795)
                    </small>
                </div>

                <div id="recaptcha-container" style="margin: 20px 0;"></div>

                <button type="submit" class="login-btn" id="phoneLoginBtn" style="margin-top: 20px;">Send Verification Code</button>

                <div id="verificationSection" class="hidden" style="margin-top: 20px;">
                    <div class="input-group">
                        <label for="verificationCode">Verification Code</label>
                        <input type="text" id="verificationCode" placeholder="Enter 6-digit code" maxlength="6">
                    </div>
                    <button type="button" class="login-btn" id="verifyCodeBtn">Verify & Login</button>
                </div>
            </div>
        </form>

        <div class="divider">
            <span>OR</span>
        </div>

        <button class="google-btn" id="googleLoginBtn">
            <svg class="google-icon" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Continue with Google
        </button>
    </div>

    <script type="module">
        // Firebase Configuration
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
        import { getAuth, signInWithEmailAndPassword, signInWithPhoneNumber, RecaptchaVerifier, signInWithPopup, GoogleAuthProvider, sendPasswordResetEmail } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
        import { getDatabase } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-database.js';

        // Your web app's Firebase configuration
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
        const database = getDatabase(app);

        // DOM Elements
        const emailTab = document.getElementById('emailTab');
        const phoneTab = document.getElementById('phoneTab');
        const emailForm = document.getElementById('emailForm');
        const phoneForm = document.getElementById('phoneForm');
        const loginForm = document.getElementById('loginForm');
        const errorMessage = document.getElementById('errorMessage');
        const successMessage = document.getElementById('successMessage');
        const googleLoginBtn = document.getElementById('googleLoginBtn');
        const forgotPasswordLink = document.getElementById('forgotPasswordLink');

        let recaptchaVerifier;
        let confirmationResult;

        // Tab Switching
        emailTab.addEventListener('click', () => {
            emailTab.classList.add('active');
            phoneTab.classList.remove('active');
            emailForm.classList.remove('hidden');
            phoneForm.classList.add('hidden');
            hideMessages();
        });

        phoneTab.addEventListener('click', () => {
            phoneTab.classList.add('active');
            emailTab.classList.remove('active');
            phoneForm.classList.remove('hidden');
            emailForm.classList.add('hidden');
            hideMessages();
            initRecaptcha();
        });

        // Initialize Recaptcha for Phone Auth
        function initRecaptcha() {
            if (!recaptchaVerifier) {
                try {
                    recaptchaVerifier = new RecaptchaVerifier(auth, 'recaptcha-container', {
                        'size': 'normal',
                        'callback': (response) => {
                            console.log('Recaptcha verified');
                        },
                        'expired-callback': () => {
                            console.log('Recaptcha expired');
                        }
                    });
                    recaptchaVerifier.render().then((widgetId) => {
                        console.log('Recaptcha rendered with widget ID:', widgetId);
                    }).catch((error) => {
                        console.error('Recaptcha render error:', error);
                        showError('Failed to load reCAPTCHA. Please refresh the page.');
                    });
                } catch (error) {
                    console.error('Recaptcha initialization error:', error);
                }
            }
        }

        // Show/Hide Messages
        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
            successMessage.style.display = 'none';
        }

        function showSuccess(message) {
            successMessage.textContent = message;
            successMessage.style.display = 'block';
            errorMessage.style.display = 'none';
        }

        function hideMessages() {
            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';
        }

        // Email/Password Login
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            hideMessages();

            if (!phoneForm.classList.contains('hidden')) {
                // Phone Login
                const phoneNumber = document.getElementById('phone').value;
                const phoneLoginBtn = document.getElementById('phoneLoginBtn');
                
                phoneLoginBtn.disabled = true;
                phoneLoginBtn.textContent = 'Sending...';

                try {
                    confirmationResult = await signInWithPhoneNumber(auth, phoneNumber, recaptchaVerifier);
                    showSuccess('Verification code sent to your phone!');
                    document.getElementById('verificationSection').classList.remove('hidden');
                    phoneLoginBtn.textContent = 'Code Sent';
                } catch (error) {
                    showError(error.message);
                    phoneLoginBtn.disabled = false;
                    phoneLoginBtn.textContent = 'Send Verification Code';
                }
            } else {
                // Email Login
                const email = document.getElementById('email').value;
                const password = document.getElementById('emailPassword').value;
                const emailLoginBtn = document.getElementById('emailLoginBtn');
                
                emailLoginBtn.disabled = true;
                emailLoginBtn.textContent = 'Logging in...';

                try {
                    const userCredential = await signInWithEmailAndPassword(auth, email, password);
                    showSuccess('Login successful! Redirecting...');
                    
                    // Store user info in sessionStorage
                    sessionStorage.setItem('userEmail', userCredential.user.email);
                    sessionStorage.setItem('userId', userCredential.user.uid);
                    
                    setTimeout(() => {
                        window.location.href = 'index.php'; // Redirect to your dashboard
                    }, 1500);
                } catch (error) {
                    console.error('Login error:', error); // Debug log
                    showError(getErrorMessage(error.code));
                    emailLoginBtn.disabled = false;
                    emailLoginBtn.textContent = 'Login with Email';
                }
            }
        });

        // Verify Phone Code
        document.getElementById('verifyCodeBtn').addEventListener('click', async () => {
            hideMessages();
            const code = document.getElementById('verificationCode').value;
            const verifyBtn = document.getElementById('verifyCodeBtn');
            
            verifyBtn.disabled = true;
            verifyBtn.textContent = 'Verifying...';

            try {
                const result = await confirmationResult.confirm(code);
                showSuccess('Phone verified! Logging in...');
                setTimeout(() => {
                    window.location.href = '/dashboard.html'; // Redirect to your dashboard
                }, 1500);
            } catch (error) {
                showError('Invalid verification code. Please try again.');
                verifyBtn.disabled = false;
                verifyBtn.textContent = 'Verify & Login';
            }
        });

        // Google Sign In - Only for whitelisted users
        googleLoginBtn.addEventListener('click', async () => {
            hideMessages();
            googleLoginBtn.disabled = true;
            
            const provider = new GoogleAuthProvider();
            provider.setCustomParameters({
                prompt: 'select_account'
            });
            
            try {
                const result = await signInWithPopup(auth, provider);
                const userEmail = result.user.email;
                const isNewUser = result.user.metadata.creationTime === result.user.metadata.lastSignInTime;
                
                // If this is a new user (first time signing in), check authorization
                if (isNewUser) {
                    // Check if user is authorized via backend
                    try {
                        const checkResponse = await fetch('check_user.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ email: userEmail })
                        });
                        
                        const { authorized } = await checkResponse.json();
                        
                        if (!authorized) {
                            // User not authorized - delete the account and sign them out
                            await result.user.delete(); // Delete the newly created user
                            await signOut(auth);
                            showError(`Access denied. The email "${userEmail}" is not authorized. Please contact the administrator.`);
                            googleLoginBtn.disabled = false;
                            return;
                        }
                    } catch (checkError) {
                        console.error('Authorization check error:', checkError);
                        // If check fails, deny access to be safe
                        await result.user.delete();
                        await signOut(auth);
                        showError('Unable to verify authorization. Please try again or contact the administrator.');
                        googleLoginBtn.disabled = false;
                        return;
                    }
                }
                
                // User is authorized (either existing or newly approved)
                
                // Sync user to database and get role
                let userRole = 'user';
                try {
                    const syncResponse = await fetch('sync_user.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            userId: result.user.uid,
                            email: userEmail,
                            name: result.user.displayName || ''
                        })
                    });
                    const syncData = await syncResponse.json();
                    userRole = syncData.user?.role || 'user';
                } catch (syncError) {
                    console.warn('User sync failed:', syncError);
                }
                
                showSuccess('Login successful! Redirecting...');
                
                // Store user info
                sessionStorage.setItem('userEmail', userEmail);
                sessionStorage.setItem('userName', result.user.displayName || '');
                sessionStorage.setItem('userId', result.user.uid);
                sessionStorage.setItem('userRole', userRole);
                
                setTimeout(() => {
                    // Redirect based on role
                    if (userRole === 'admin') {
                        window.location.href = 'admin_dashboard.php';
                    } else {
                        window.location.href = 'index.php';
                    }
                }, 1500);
                
            } catch (error) {
                console.error('Google login error:', error);
                
                if (error.code === 'auth/popup-closed-by-user') {
                    showError('Sign-in cancelled.');
                } else if (error.code === 'auth/unauthorized-domain') {
                    showError('This domain is not authorized for Google Sign-In. Please add your domain to Firebase Console.');
                } else if (error.code === 'auth/account-exists-with-different-credential') {
                    showError('An account already exists with this email using a different sign-in method.');
                } else {
                    showError(getErrorMessage(error.code));
                }
                googleLoginBtn.disabled = false;
            }
        });

        // Forgot Password
        forgotPasswordLink.addEventListener('click', async () => {
            const email = document.getElementById('email').value;
            
            if (!email) {
                showError('Please enter your email address first.');
                return;
            }

            try {
                await sendPasswordResetEmail(auth, email);
                showSuccess('Password reset email sent! Check your inbox.');
            } catch (error) {
                showError(getErrorMessage(error.code));
            }
        });

        // Error Message Mapping
        function getErrorMessage(errorCode) {
            const errorMessages = {
                'auth/invalid-email': 'Invalid email address.',
                'auth/user-disabled': 'This account has been disabled.',
                'auth/user-not-found': 'No account found with this email.',
                'auth/wrong-password': 'Incorrect password.',
                'auth/invalid-credential': 'Invalid email or password.',
                'auth/too-many-requests': 'Too many failed attempts. Please try again later.',
                'auth/network-request-failed': 'Network error. Please check your connection.',
                'auth/popup-closed-by-user': 'Sign-in popup was closed.',
                'auth/invalid-phone-number': 'Invalid phone number format.',
                'auth/missing-phone-number': 'Please enter a phone number.'
            };
            
            return errorMessages[errorCode] || 'An error occurred. Please try again.';
        }
    </script>
</body>
</html>