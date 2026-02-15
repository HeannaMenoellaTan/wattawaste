<?php
// profile.php - Fixed version with better error handling
require_once 'firebase_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - WattAWaste</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a2e0e6ad65.js" crossorigin="anonymous"></script>

    <!-- Firebase Auth and Database -->
    <script type="module">
    import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
    import { getAuth, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
    import { getDatabase, ref, set, onValue } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-database.js';

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
    const database = getDatabase(app);

    onAuthStateChanged(auth, (user) => {
        if (!user) {
            console.log('❌ No user found, redirecting to login...');
            window.location.href = 'login.php';
        } else {
            console.log('✅ User authenticated:', user.email || user.phoneNumber);
            sessionStorage.setItem('userEmail', user.email || user.phoneNumber || '');
            sessionStorage.setItem('userId', user.uid);
            
            // Load user profile data
            window.loadUserProfile(user);
        }
    });

    window.firebaseAuth = auth;
    window.firebaseDatabase = database;
    window.firebaseRef = ref;
    window.firebaseSet = set;
    window.firebaseOnValue = onValue;
    </script>

    <?php include_once 'notif_bell.php'; ?>

    <style>
        :root {
            --brand: #4CAF50;
            --brand-dark: #2E7D32;
            --brand-light: #23ed99ff;
            --brand-med: #0f8156ff;
            --ink: #333;
            --panel: #fff;
            --muted: #555;
            --bg: #F9FAFB;
            --purple: #3e61ff;
            --purple-dark: #8e44ff;
        }

        body {
            background: var(--bg);
            font-family: 'Poppins', system-ui, 'Segoe UI', Arial, sans-serif;
            color: var(--ink);
            min-height: 100vh;
        }

        .profile-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .profile-card {
            background: var(--panel);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 6px 16px rgba(2, 6, 23, 0.06);
            margin-bottom: 25px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .profile-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 26px rgba(2, 6, 23, 0.12);
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 30px;
            padding-bottom: 30px;
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--brand-light), var(--brand-med));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: 700;
            color: #ffffff;
            border: 4px solid rgba(76, 175, 80, 0.2);
            box-shadow: 0 10px 30px rgba(35, 237, 153, 0.3);
            position: relative;
            overflow: hidden;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .profile-info-header {
            flex: 1;
        }

        .profile-name {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 5px;
            background: linear-gradient(135deg, var(--brand-light), var(--brand-med));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .profile-email {
            color: var(--muted);
            font-size: 16px;
        }

        .verification-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: linear-gradient(135deg, var(--brand-light), var(--brand-med));
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            color: #ffffff;
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(35, 237, 153, 0.3);
        }

        .unverified-badge {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .verify-btn {
            padding: 12px 28px;
            background: linear-gradient(135deg, var(--purple), var(--purple-dark));
            border: none;
            border-radius: 12px;
            color: #ffffff;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 15px;
            box-shadow: 0 4px 12px rgba(98, 75, 255, 0.4);
            text-decoration: none;
            display: inline-block;
        }

        .verify-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(98, 75, 255, 0.6);
            color: #ffffff;
        }

        .info-grid {
            display: grid;
            gap: 20px;
        }

        .info-item {
            display: flex;
            align-items: center;
            padding: 20px;
            background: rgba(76, 175, 80, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(76, 175, 80, 0.1);
            transition: all 0.3s ease;
        }

        .info-item:hover {
            background: rgba(76, 175, 80, 0.08);
            border-color: rgba(35, 237, 153, 0.3);
            transform: translateX(5px);
        }

        .info-item i {
            font-size: 24px;
            margin-right: 20px;
            color: var(--brand);
            width: 40px;
            text-align: center;
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            font-size: 12px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .info-value {
            font-size: 16px;
            font-weight: 500;
            color: var(--ink);
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--brand);
        }

        /* Registration Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: var(--panel);
            border-radius: 24px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .modal-title {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--brand-light), var(--brand-med));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .modal-subtitle {
            color: var(--muted);
            font-size: 14px;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 30px;
        }

        .step {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .step.active {
            background: linear-gradient(135deg, var(--brand-light), var(--brand-med));
            width: 35px;
            border-radius: 5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.03);
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--brand);
            background: rgba(76, 175, 80, 0.05);
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--brand-light), var(--brand-med));
            border: none;
            border-radius: 12px;
            color: #ffffff;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(35, 237, 153, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-secondary {
            width: 100%;
            padding: 14px;
            background: transparent;
            border: 2px solid rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            color: var(--ink);
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-secondary:hover {
            background: rgba(0, 0, 0, 0.05);
            border-color: rgba(0, 0, 0, 0.3);
        }

        .bin-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
        }

        .bin-card {
            padding: 25px 15px;
            background: rgba(76, 175, 80, 0.05);
            border: 2px solid rgba(76, 175, 80, 0.2);
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .bin-card:hover {
            background: rgba(76, 175, 80, 0.1);
            border-color: var(--brand);
            transform: translateY(-3px);
        }

        .bin-card.selected {
            background: linear-gradient(135deg, rgba(35, 237, 153, 0.2), rgba(15, 129, 86, 0.2));
            border-color: var(--brand);
            box-shadow: 0 6px 20px rgba(35, 237, 153, 0.3);
        }

        .bin-card i {
            font-size: 36px;
            color: var(--brand);
            margin-bottom: 10px;
        }

        .bin-number {
            font-size: 18px;
            font-weight: 700;
            color: var(--ink);
        }

        .upload-area {
            width: 100%;
            padding: 40px 20px;
            background: rgba(76, 175, 80, 0.05);
            border: 3px dashed rgba(76, 175, 80, 0.3);
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }

        .upload-area:hover {
            background: rgba(76, 175, 80, 0.1);
            border-color: var(--brand);
        }

        .upload-area.has-image {
            padding: 15px;
            border-style: solid;
            border-color: var(--brand);
        }

        .upload-icon {
            font-size: 48px;
            color: var(--brand);
            margin-bottom: 10px;
        }

        .upload-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 15px;
            border: 3px solid var(--brand);
            box-shadow: 0 8px 20px rgba(35, 237, 153, 0.3);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: rgba(35, 237, 153, 0.15);
            border: 1px solid rgba(35, 237, 153, 0.3);
            color: var(--brand-dark);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #991b1b;
        }

        #imageInput {
            display: none;
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-name {
                font-size: 24px;
            }

            .bin-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<?php include 'sideabr.php'; ?>

<div class="main">
    <?php include 'topnav.php'; ?>

    <div class="profile-container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar" id="profileAvatar">
                    <span id="avatarInitial">U</span>
                </div>
                <div class="profile-info-header">
                    <h1 class="profile-name" id="profileName">Loading...</h1>
                    <p class="profile-email" id="profileEmail">Loading...</p>
                    <div id="verificationBadge"></div>
                </div>
            </div>

            <div class="section-title">
                <i class="fas fa-user-circle"></i>
                Account Information
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <div class="info-content">
                        <div class="info-label">Full Name</div>
                        <div class="info-value" id="displayName">Not set</div>
                    </div>
                </div>

                <div class="info-item">
                    <i class="fab fa-facebook"></i>
                    <div class="info-content">
                        <div class="info-label">Facebook</div>
                        <div class="info-value" id="displayFacebook">Not connected</div>
                    </div>
                </div>

                <div class="info-item">
                    <i class="fab fa-google"></i>
                    <div class="info-content">
                        <div class="info-label">Google</div>
                        <div class="info-value" id="displayGoogle">Not connected</div>
                    </div>
                </div>

                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <div class="info-content">
                        <div class="info-label">Contact Number</div>
                        <div class="info-value" id="displayPhone">Not set</div>
                    </div>
                </div>
            </div>
        </div>

        <div id="verifiedSection" style="display: none;">
            <div class="profile-card">
                <div class="section-title">
                    <i class="fas fa-check-circle"></i>
                    Verified Information
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div class="info-content">
                            <div class="info-label">Verified Address</div>
                            <div class="info-value" id="displayAddress">-</div>
                        </div>
                    </div>

                    <div class="info-item">
                        <i class="fas fa-trash-alt"></i>
                        <div class="info-content">
                            <div class="info-label">Assigned Compost Bin</div>
                            <div class="info-value" id="displayBin">-</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="unverifiedSection" style="display: none;">
            <div class="profile-card" style="text-align: center;">
                <div class="section-title" style="justify-content: center;">
                    <i class="fas fa-exclamation-circle"></i>
                    Account Not Verified
                </div>
                <p style="color: var(--muted); margin-bottom: 20px;">
                    Complete the verification process to access compost bin features.
                </p>
                <button class="verify-btn" onclick="openVerificationModal()">
                    <i class="fas fa-check-circle"></i> Start Verification
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Verification Modal -->
<div id="verificationModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Account Verification</h2>
            <p class="modal-subtitle">Complete these steps to verify your account</p>
        </div>

        <div class="step-indicator">
            <div class="step active" data-step="1"></div>
            <div class="step" data-step="2"></div>
            <div class="step" data-step="3"></div>
        </div>

        <div id="alertContainer"></div>

        <!-- Step 1: Address Verification -->
        <div id="step1" class="step-content">
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-map-marker-alt"></i> Enter Your Address
                </label>
                <input type="text" id="addressInput" class="form-input" 
                       placeholder="e.g., King George Street Kingspoint Homes 1">
            </div>
            <button class="btn-primary" onclick="verifyAddress()">Verify Address</button>
            <button class="btn-secondary" onclick="closeVerificationModal()">Cancel</button>
        </div>

        <!-- Step 2: Compost Bin Selection -->
        <div id="step2" class="step-content" style="display: none;">
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-trash-alt"></i> Select Your Compost Bin
                </label>
                <div class="bin-grid">
                    <div class="bin-card" onclick="selectBin(1)">
                        <i class="fas fa-dumpster"></i>
                        <div class="bin-number">Bin 1</div>
                    </div>
                    <div class="bin-card" onclick="selectBin(2)">
                        <i class="fas fa-dumpster"></i>
                        <div class="bin-number">Bin 2</div>
                    </div>
                    <div class="bin-card" onclick="selectBin(3)">
                        <i class="fas fa-dumpster"></i>
                        <div class="bin-number">Bin 3</div>
                    </div>
                </div>
            </div>
            <button class="btn-primary" id="selectBinBtn" disabled onclick="goToStep(3)">Continue to Photo Upload</button>
        </div>

        <!-- Step 3: Profile Picture Upload -->
        <div id="step3" class="step-content" style="display: none;">
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-camera"></i> Upload Profile Picture
                </label>
                <div class="upload-area" id="uploadArea" onclick="document.getElementById('imageInput').click()">
                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                    <div>Click to upload or drag and drop</div>
                    <div style="font-size: 13px; color: var(--muted); margin-top: 5px;">PNG, JPG or JPEG (Max 2MB)</div>
                </div>
                <input type="file" id="imageInput" accept="image/png,image/jpeg,image/jpg" onchange="handleImageUpload(event)">
            </div>
            <button class="btn-primary" id="completeVerificationBtn" disabled onclick="completeVerification()">Complete Verification</button>
        </div>
    </div>
</div>

<script>
// User data state
let userData = {
    name: '',
    facebook: '',
    google: '',
    phone: '',
    address: '',
    bin: '',
    profilePicture: null,
    isVerified: false
};

let selectedBin = null;
let uploadedImage = null;

// Load user profile from Firebase
window.loadUserProfile = function(user) {
    const userId = user.uid;
    const userEmail = user.email || user.phoneNumber || '';
    
    // Set basic info from Firebase Auth
    userData.google = userEmail;
    document.getElementById('profileEmail').textContent = userEmail;
    document.getElementById('displayGoogle').textContent = userEmail;
    
    // Load additional profile data from Firebase Database
    const userRef = window.firebaseRef(window.firebaseDatabase, `users/${userId}`);
    window.firebaseOnValue(userRef, (snapshot) => {
        const data = snapshot.val();
        if (data) {
            userData = { ...userData, ...data };
            updateProfileDisplay();
        } else {
            // New user - set defaults
            updateProfileDisplay();
        }
    });
};

// Update profile display
function updateProfileDisplay() {
    // Set name and email
    const displayName = userData.name || userData.google || 'User';
    document.getElementById('profileName').textContent = displayName;
    document.getElementById('displayName').textContent = userData.name || 'Not set';
    
    // Set avatar
    const avatarEl = document.getElementById('profileAvatar');
    const initialEl = document.getElementById('avatarInitial');
    
    if (userData.profilePicture) {
        initialEl.style.display = 'none';
        avatarEl.innerHTML = `<img src="${userData.profilePicture}" alt="Profile">`;
    } else {
        initialEl.textContent = displayName.charAt(0).toUpperCase();
    }
    
    // Set social info
    document.getElementById('displayFacebook').textContent = userData.facebook || 'Not connected';
    document.getElementById('displayPhone').textContent = userData.phone || 'Not set';
    
    // Set verification badge and sections
    const badgeEl = document.getElementById('verificationBadge');
    const verifiedSection = document.getElementById('verifiedSection');
    const unverifiedSection = document.getElementById('unverifiedSection');
    
    if (userData.isVerified) {
        badgeEl.innerHTML = '<div class="verification-badge"><i class="fas fa-check-circle"></i> Account Verified</div>';
        verifiedSection.style.display = 'block';
        unverifiedSection.style.display = 'none';
        document.getElementById('displayAddress').textContent = userData.address;
        document.getElementById('displayBin').textContent = `Compost Bin ${userData.bin}`;
    } else {
        badgeEl.innerHTML = '<div class="verification-badge unverified-badge"><i class="fas fa-exclamation-circle"></i> Not Verified</div>';
        verifiedSection.style.display = 'none';
        unverifiedSection.style.display = 'block';
    }
}

// Modal functions
function openVerificationModal() {
    document.getElementById('verificationModal').classList.add('active');
    goToStep(1);
}

function closeVerificationModal() {
    document.getElementById('verificationModal').classList.remove('active');
    resetVerification();
}

function goToStep(step) {
    // Update step indicators
    document.querySelectorAll('.step').forEach((s, index) => {
        if (index < step) {
            s.classList.add('active');
        } else {
            s.classList.remove('active');
        }
    });
    
    // Show/hide step content
    document.getElementById('step1').style.display = step === 1 ? 'block' : 'none';
    document.getElementById('step2').style.display = step === 2 ? 'block' : 'none';
    document.getElementById('step3').style.display = step === 3 ? 'block' : 'none';
}

function verifyAddress() {
    const address = document.getElementById('addressInput').value.trim();
    
    if (!address) {
        showAlert('Please enter an address', 'error');
        return;
    }
    
    const requiredAddress = 'King George Street Kingspoint Homes 1';
    
    if (address.includes(requiredAddress)) {
        userData.address = address;
        showAlert('Address verified successfully!', 'success');
        setTimeout(() => {
            goToStep(2);
        }, 1000);
    } else {
        showAlert('Address verification failed. You must reside at King George Street Kingspoint Homes 1.', 'error');
        setTimeout(() => {
            closeVerificationModal();
        }, 3000);
    }
}

function selectBin(binNumber) {
    selectedBin = binNumber;
    userData.bin = binNumber;
    
    document.querySelectorAll('.bin-card').forEach(card => card.classList.remove('selected'));
    event.target.closest('.bin-card').classList.add('selected');
    document.getElementById('selectBinBtn').disabled = false;
}

// Compress image to reduce size
function compressImage(base64Str, maxWidth = 800, maxHeight = 800, quality = 0.7) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement('canvas');
            let width = img.width;
            let height = img.height;

            // Calculate new dimensions
            if (width > height) {
                if (width > maxWidth) {
                    height = height * (maxWidth / width);
                    width = maxWidth;
                }
            } else {
                if (height > maxHeight) {
                    width = width * (maxHeight / height);
                    height = maxHeight;
                }
            }

            canvas.width = width;
            canvas.height = height;

            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, width, height);

            // Compress to JPEG
            const compressedBase64 = canvas.toDataURL('image/jpeg', quality);
            resolve(compressedBase64);
        };
        img.onerror = reject;
        img.src = base64Str;
    });
}

async function handleImageUpload(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    const validTypes = ['image/png', 'image/jpeg', 'image/jpg'];
    if (!validTypes.includes(file.type)) {
        showAlert('Please upload a PNG or JPEG image', 'error');
        return;
    }
    
    if (file.size > 2 * 1024 * 1024) {
        showAlert('Image size must be less than 2MB', 'error');
        return;
    }
    
    const reader = new FileReader();
    reader.onload = async (e) => {
        try {
            // Compress the image before storing
            const compressed = await compressImage(e.target.result, 400, 400, 0.7);
            uploadedImage = compressed;
            
            const uploadArea = document.getElementById('uploadArea');
            uploadArea.classList.add('has-image');
            uploadArea.innerHTML = `
                <img src="${compressed}" alt="Preview" class="upload-preview">
                <div>Image uploaded successfully!</div>
                <div style="font-size: 13px; color: var(--muted); margin-top: 5px;">Click to change image</div>
            `;
            
            document.getElementById('completeVerificationBtn').disabled = false;
        } catch (error) {
            console.error('Image compression error:', error);
            showAlert('Failed to process image. Please try another image.', 'error');
        }
    };
    reader.readAsDataURL(file);
}

async function completeVerification() {
    if (!uploadedImage) {
        showAlert('Please upload a profile picture', 'error');
        return;
    }
    
    // Disable button to prevent double submission
    const btn = document.getElementById('completeVerificationBtn');
    btn.disabled = true;
    btn.textContent = 'Saving...';
    
    userData.profilePicture = uploadedImage;
    userData.isVerified = true;
    
    // Save to Firebase
    try {
        const userId = sessionStorage.getItem('userId');
        if (!userId) {
            throw new Error('User ID not found. Please log in again.');
        }
        
        // Validate required fields
        if (!userData.address || !userData.bin) {
            throw new Error('Missing required verification data. Please start over.');
        }
        
        console.log('📤 Saving profile to Firebase...');
        
        const userRef = window.firebaseRef(window.firebaseDatabase, `users/${userId}`);
        
        // Save data to Firebase
        await window.firebaseSet(userRef, {
            name: userData.name || '',
            facebook: userData.facebook || '',
            google: userData.google || '',
            phone: userData.phone || '',
            address: userData.address,
            bin: userData.bin,
            profilePicture: uploadedImage,
            isVerified: true,
            verifiedDate: new Date().toISOString()
        });
        
        console.log('✅ Profile saved successfully to Firebase');
        showAlert('Verification complete! Your account is now fully verified.', 'success');
        
        setTimeout(() => {
            closeVerificationModal();
            updateProfileDisplay();
            // Refresh the page to update all verification-dependent features
            window.location.reload();
        }, 1500);
    } catch (error) {
        console.error('❌ Error saving profile:', error);
        
        // Show specific error message
        let errorMessage = 'Failed to save profile. ';
        if (error.message.includes('permission')) {
            errorMessage += 'Permission denied. Please check your Firebase rules.';
        } else if (error.message.includes('size') || error.message.includes('too large')) {
            errorMessage += 'Data is too large. Please use a smaller image.';
        } else {
            errorMessage += error.message || 'Please try again.';
        }
        
        showAlert(errorMessage, 'error');
        
        // Re-enable button
        btn.disabled = false;
        btn.textContent = 'Complete Verification';
    }
}

function resetVerification() {
    selectedBin = null;
    uploadedImage = null;
    document.getElementById('addressInput').value = '';
    document.getElementById('selectBinBtn').disabled = true;
    document.getElementById('completeVerificationBtn').disabled = true;
    document.querySelectorAll('.bin-card').forEach(c => c.classList.remove('selected'));
    
    const uploadArea = document.getElementById('uploadArea');
    uploadArea.classList.remove('has-image');
    uploadArea.innerHTML = `
        <i class="fas fa-cloud-upload-alt upload-icon"></i>
        <div>Click to upload or drag and drop</div>
        <div style="font-size: 13px; color: var(--muted); margin-top: 5px;">PNG, JPG or JPEG (Max 2MB)</div>
    `;
    
    document.getElementById('imageInput').value = '';
    document.getElementById('alertContainer').innerHTML = '';
}

function showAlert(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    document.getElementById('alertContainer').innerHTML = `
        <div class="alert ${alertClass}">
            <i class="fas ${icon}"></i>
            <div>${message}</div>
        </div>
    `;
    
    setTimeout(() => {
        document.getElementById('alertContainer').innerHTML = '';
    }, 5000);
}

// Close modal when clicking outside
document.getElementById('verificationModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeVerificationModal();
    }
});
</script>

</body>
</html>