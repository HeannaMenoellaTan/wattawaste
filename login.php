<?php
session_start();
require_once('firebase_config.php');

$error = '';
$showLoginForm = true;

// This is a simplified version - Firebase Authentication handles the actual auth
// Users will be manually added to Firebase Authentication by admin
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - WattAWaste</title>
<style>
    body {
        background-color: #eaf5ea;
        font-family: 'Poppins', sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
        padding: 20px;
    }
    .login-container {
        background-color: white;
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 400px;
        text-align: center;
    }
    h2 {
        color: #2c4e32;
        margin-bottom: 10px;
    }
    .subtitle {
        color: #666;
        margin-bottom: 25px;
        font-size: 14px;
    }
    input[type="tel"], input[type="email"] {
        width: 100%;
        padding: 14px;
        margin: 12px 0;
        border-radius: 10px;
        border: 2px solid #e0e0e0;
        box-sizing: border-box;
        font-size: 15px;
        transition: border-color 0.3s;
    }
    input[type="tel"]:focus, input[type="email"]:focus {
        outline: none;
        border-color: #4caf50;
    }
    .login-method {
        display: flex;
        gap: 12px;
        margin: 20px 0;
    }
    .method-btn {
        flex: 1;
        padding: 15px;
        border: 2px solid #e0e0e0;
        background: white;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 14px;
        font-weight: 500;
        color: #666;
    }
    .method-btn:hover {
        border-color: #4caf50;
        background: #f8fdf9;
    }
    .method-btn.active {
        background: #4caf50;
        color: white;
        border-color: #4caf50;
    }
    .method-btn i {
        display: block;
        font-size: 24px;
        margin-bottom: 8px;
    }
    .login-btn {
        background-color: #4caf50;
        color: white;
        border: none;
        padding: 14px;
        width: 100%;
        border-radius: 10px;
        font-size: 16px;
        cursor: pointer;
        font-weight: 600;
        margin-top: 15px;
        transition: background-color 0.3s;
    }
    .login-btn:hover {
        background-color: #45a049;
    }
    .login-btn:disabled {
        background-color: #cccccc;
        cursor: not-allowed;
    }
    .error {
        color: #d32f2f;
        background-color: #ffebee;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 15px;
        font-size: 14px;
        border-left: 4px solid #d32f2f;
    }
    .success {
        color: #2e7d32;
        background-color: #e8f5e9;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 15px;
        font-size: 14px;
        border-left: 4px solid #2e7d32;
    }
    .otp-container {
        display: none;
    }
    .otp-container.active {
        display: block;
    }
    .otp-inputs {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin: 25px 0;
    }
    .otp-input {
        width: 50px;
        height: 55px;
        font-size: 24px;
        text-align: center;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-weight: bold;
        transition: border-color 0.3s;
    }
    .otp-input:focus {
        outline: none;
        border-color: #4caf50;
    }
    .back-link {
        color: #4caf50;
        text-decoration: none;
        font-size: 14px;
        display: inline-block;
        margin-top: 15px;
        font-weight: 500;
    }
    .back-link:hover {
        text-decoration: underline;
    }
    .info-text {
        color: #666;
        font-size: 13px;
        margin-top: 15px;
    }
    .resend-link {
        color: #4caf50;
        cursor: pointer;
        text-decoration: underline;
        font-size: 13px;
        margin-top: 10px;
        display: inline-block;
    }
    .resend-link:hover {
        color: #45a049;
    }
    #countdown {
        color: #666;
        font-size: 13px;
        margin-top: 10px;
    }
    .hidden {
        display: none;
    }
    .loading {
        display: none;
        margin: 10px 0;
    }
    .loading.active {
        display: block;
    }
    .spinner {
        border: 3px solid #f3f3f3;
        border-top: 3px solid #4caf50;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- Firebase SDK -->
<script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
</head>
<body>
<div class="login-container">
    <img src="images/qculogo.png" alt="Logo" width="80">
    <h2>Welcome Back!</h2>
    <p class="subtitle">Sign in with SMS or Email</p>
    
    <div id="errorMessage" class="error hidden"></div>
    <div id="successMessage" class="success hidden"></div>
    
    <!-- Login Method Selection -->
    <div id="loginMethodContainer">
        <div class="login-method">
            <button type="button" class="method-btn active" onclick="selectMethod('phone')" id="phoneBtn">
                <i class="fas fa-mobile-alt"></i>
                <span>Phone</span>
            </button>
            <button type="button" class="method-btn" onclick="selectMethod('email')" id="emailBtn">
                <i class="fas fa-envelope"></i>
                <span>Email</span>
            </button>
        </div>
        
        <!-- Phone Login -->
        <div id="phoneLogin">
            <input type="tel" id="phoneNumber" placeholder="+63 912 345 6789" 
                   pattern="[+]?[0-9]{10,15}">
            <div id="recaptcha-container"></div>
            <button onclick="sendPhoneOTP()" class="login-btn" id="sendPhoneBtn">
                Send OTP via SMS
            </button>
        </div>
        
        <!-- Email Login -->
        <div id="emailLogin" class="hidden">
            <input type="email" id="emailAddress" placeholder="your.email@example.com">
            <button onclick="sendEmailLink()" class="login-btn" id="sendEmailBtn">
                Send Sign-In Link
            </button>
        </div>
    </div>
    
    <!-- OTP Verification (for Phone) -->
    <div id="otpContainer" class="otp-container">
        <p>Enter the 6-digit code sent to your phone</p>
        <div class="otp-inputs">
            <input type="text" class="otp-input" maxlength="1" id="otp1">
            <input type="text" class="otp-input" maxlength="1" id="otp2">
            <input type="text" class="otp-input" maxlength="1" id="otp3">
            <input type="text" class="otp-input" maxlength="1" id="otp4">
            <input type="text" class="otp-input" maxlength="1" id="otp5">
            <input type="text" class="otp-input" maxlength="1" id="otp6">
        </div>
        <button onclick="verifyOTP()" class="login-btn" id="verifyBtn">
            Verify & Login
        </button>
        <div id="countdown" class="hidden"></div>
        <a onclick="resendOTP()" class="resend-link hidden" id="resendLink">Resend Code</a>
        <br>
        <a onclick="backToLogin()" class="back-link">← Back to login</a>
    </div>
    
    <div class="loading" id="loadingSpinner">
        <div class="spinner"></div>
        <p style="margin-top: 10px; color: #666;">Processing...</p>
    </div>
    
    <p class="info-text">
        Secure login powered by Firebase Authentication
    </p>
</div>

<script>
// Firebase configuration - REPLACE WITH YOUR CONFIG
const firebaseConfig = {
    apiKey: "YOUR_API_KEY",
    authDomain: "YOUR_AUTH_DOMAIN",
    databaseURL: "YOUR_DATABASE_URL",
    projectId: "YOUR_PROJECT_ID",
    storageBucket: "YOUR_STORAGE_BUCKET",
    messagingSenderId: "YOUR_MESSAGING_SENDER_ID",
    appId: "YOUR_APP_ID"
};

// Initialize Firebase
firebase.initializeApp(firebaseConfig);
const auth = firebase.auth();

let confirmationResult;
let countdownTimer;

// Initialize reCAPTCHA
window.recaptchaVerifier = new firebase.auth.RecaptchaVerifier('recaptcha-container', {
    'size': 'invisible',
    'callback': (response) => {
        // reCAPTCHA solved
    }
});

function selectMethod(method) {
    // Update UI
    document.getElementById('phoneBtn').classList.remove('active');
    document.getElementById('emailBtn').classList.remove('active');
    
    if (method === 'phone') {
        document.getElementById('phoneBtn').classList.add('active');
        document.getElementById('phoneLogin').classList.remove('hidden');
        document.getElementById('emailLogin').classList.add('hidden');
    } else {
        document.getElementById('emailBtn').classList.add('active');
        document.getElementById('phoneLogin').classList.add('hidden');
        document.getElementById('emailLogin').classList.remove('hidden');
    }
    
    hideMessage();
}

function sendPhoneOTP() {
    const phoneNumber = document.getElementById('phoneNumber').value.trim();
    
    if (!phoneNumber) {
        showError('Please enter your phone number');
        return;
    }
    
    // Format phone number (ensure it has country code)
    let formattedPhone = phoneNumber;
    if (!formattedPhone.startsWith('+')) {
        formattedPhone = '+63' + formattedPhone.replace(/^0/, '');
    }
    
    showLoading(true);
    hideMessage();
    
    const appVerifier = window.recaptchaVerifier;
    
    auth.signInWithPhoneNumber(formattedPhone, appVerifier)
        .then((result) => {
            confirmationResult = result;
            showLoading(false);
            showSuccess('OTP sent to your phone!');
            
            // Show OTP input
            document.getElementById('loginMethodContainer').classList.add('hidden');
            document.getElementById('otpContainer').classList.add('active');
            
            // Focus first OTP input
            document.getElementById('otp1').focus();
            
            // Start countdown
            startCountdown(60);
        })
        .catch((error) => {
            showLoading(false);
            showError('Failed to send OTP: ' + error.message);
            console.error(error);
        });
}

function verifyOTP() {
    const otp = 
        document.getElementById('otp1').value +
        document.getElementById('otp2').value +
        document.getElementById('otp3').value +
        document.getElementById('otp4').value +
        document.getElementById('otp5').value +
        document.getElementById('otp6').value;
    
    if (otp.length !== 6) {
        showError('Please enter the complete 6-digit code');
        return;
    }
    
    showLoading(true);
    hideMessage();
    
    confirmationResult.confirm(otp)
        .then((result) => {
            const user = result.user;
            showLoading(false);
            
            // Send user data to PHP session
            saveUserSession(user);
        })
        .catch((error) => {
            showLoading(false);
            showError('Invalid OTP. Please try again.');
            console.error(error);
        });
}

function sendEmailLink() {
    const email = document.getElementById('emailAddress').value.trim();
    
    if (!email) {
        showError('Please enter your email address');
        return;
    }
    
    const actionCodeSettings = {
        url: window.location.origin + '/email_login_handler.php',
        handleCodeInApp: true
    };
    
    showLoading(true);
    hideMessage();
    
    auth.sendSignInLinkToEmail(email, actionCodeSettings)
        .then(() => {
            window.localStorage.setItem('emailForSignIn', email);
            showLoading(false);
            showSuccess('Sign-in link sent to ' + email + '. Please check your inbox.');
        })
        .catch((error) => {
            showLoading(false);
            showError('Failed to send email: ' + error.message);
            console.error(error);
        });
}

function resendOTP() {
    document.getElementById('resendLink').classList.add('hidden');
    sendPhoneOTP();
}

function backToLogin() {
    document.getElementById('loginMethodContainer').classList.remove('hidden');
    document.getElementById('otpContainer').classList.remove('active');
    clearOTPInputs();
    hideMessage();
    
    if (countdownTimer) {
        clearInterval(countdownTimer);
    }
}

function clearOTPInputs() {
    for (let i = 1; i <= 6; i++) {
        document.getElementById('otp' + i).value = '';
    }
}

function startCountdown(seconds) {
    const countdownEl = document.getElementById('countdown');
    const resendLink = document.getElementById('resendLink');
    
    countdownEl.classList.remove('hidden');
    resendLink.classList.add('hidden');
    
    let remaining = seconds;
    countdownEl.textContent = `Resend code in ${remaining}s`;
    
    countdownTimer = setInterval(() => {
        remaining--;
        if (remaining > 0) {
            countdownEl.textContent = `Resend code in ${remaining}s`;
        } else {
            clearInterval(countdownTimer);
            countdownEl.classList.add('hidden');
            resendLink.classList.remove('hidden');
        }
    }, 1000);
}

function saveUserSession(user) {
    // Send user data to PHP to create session
    fetch('save_session.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            uid: user.uid,
            email: user.email,
            phone: user.phoneNumber,
            displayName: user.displayName
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect based on role
            window.location.href = data.redirect;
        } else {
            showError('Login failed. Please contact administrator.');
        }
    })
    .catch(error => {
        showError('Session error: ' + error.message);
    });
}

function showError(message) {
    const errorEl = document.getElementById('errorMessage');
    errorEl.textContent = message;
    errorEl.classList.remove('hidden');
    document.getElementById('successMessage').classList.add('hidden');
}

function showSuccess(message) {
    const successEl = document.getElementById('successMessage');
    successEl.textContent = message;
    successEl.classList.remove('hidden');
    document.getElementById('errorMessage').classList.add('hidden');
}

function hideMessage() {
    document.getElementById('errorMessage').classList.add('hidden');
    document.getElementById('successMessage').classList.add('hidden');
}

function showLoading(show) {
    const loader = document.getElementById('loadingSpinner');
    if (show) {
        loader.classList.add('active');
    } else {
        loader.classList.remove('active');
    }
}

// OTP input auto-focus
document.querySelectorAll('.otp-input').forEach((input, index) => {
    input.addEventListener('input', (e) => {
        if (e.target.value.length === 1 && index < 5) {
            document.getElementById('otp' + (index + 2)).focus();
        }
    });
    
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
            document.getElementById('otp' + index).focus();
        }
    });
});

// Auto-verify when all 6 digits entered
document.getElementById('otp6').addEventListener('input', () => {
    const allFilled = Array.from(document.querySelectorAll('.otp-input'))
        .every(input => input.value.length === 1);
    
    if (allFilled) {
        setTimeout(() => verifyOTP(), 300);
    }
});
</script>
</body>
</html>