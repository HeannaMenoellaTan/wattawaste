<?php
// Minimal PHP - just for auth check and page structure
// All data will be fetched from Firebase via JavaScript
require_once 'firebase_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Weight Level | WattAWaste - DEBUG</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Firebase Auth and Database -->
<script type="module">
import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
import { getAuth, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
import { getDatabase, ref, onValue, query, limitToLast } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-database.js';

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
        window.initializeWeightMonitoring();
    }
});

window.firebaseAuth = auth;
window.firebaseDatabase = database;
window.firebaseRef = ref;
window.firebaseOnValue = onValue;
window.firebaseQuery = query;
window.firebaseLimitToLast = limitToLast;
</script>

<style>
body { 
    font-family: monospace;
    background: #1e1e1e;
    color: #00ff00;
    padding: 20px;
}

.debug-box {
    background: #2a2a2a;
    border: 2px solid #00ff00;
    padding: 20px;
    margin: 20px 0;
    border-radius: 8px;
}

h2 {
    color: #ffff00;
}

.timestamp-test {
    margin: 10px 0;
    padding: 10px;
    background: #333;
    border-left: 4px solid #00ff00;
}

.error {
    color: #ff0000;
}

.success {
    color: #00ff00;
}

.info {
    color: #00aaff;
}
</style>
</head>
<body>

<h1>🔍 Firebase Timestamp Debug Tool</h1>

<div class="debug-box">
    <h2>📊 Raw Firebase Data</h2>
    <div id="rawData">Loading...</div>
</div>

<div class="debug-box">
    <h2>🕐 Timestamp Analysis</h2>
    <div id="timestampAnalysis">Analyzing...</div>
</div>

<div class="debug-box">
    <h2>✅ Recommended Fix</h2>
    <div id="recommendation">Calculating...</div>
</div>

<script>
window.initializeWeightMonitoring = function() {
    console.log('🔍 Starting Firebase timestamp debug...');
    
    const database = window.firebaseDatabase;
    const debugOutput = document.getElementById('rawData');
    const analysisOutput = document.getElementById('timestampAnalysis');
    const recommendationOutput = document.getElementById('recommendation');
    
    // Listen to weight history
    const historyQuery = window.firebaseQuery(
        window.firebaseRef(database, 'sensors/weight/history'),
        window.firebaseLimitToLast(5)
    );
    
    window.firebaseOnValue(historyQuery, (snapshot) => {
        let rawHtml = '<h3 class="info">Raw Firebase Keys and Values:</h3>';
        let analysisHtml = '';
        let firstKey = null;
        let firstValue = null;
        
        if (snapshot.exists()) {
            snapshot.forEach((childSnapshot) => {
                const key = childSnapshot.key;
                const value = childSnapshot.val();
                
                if (!firstKey) {
                    firstKey = key;
                    firstValue = value;
                }
                
                rawHtml += `
                    <div class="timestamp-test">
                        <strong>Key:</strong> ${key}<br>
                        <strong>Value:</strong> ${value} kg<br>
                    </div>
                `;
            });
        } else {
            rawHtml += '<p class="error">No data found in sensors/weight/history</p>';
        }
        
        debugOutput.innerHTML = rawHtml;
        
        // Analyze the timestamp
        if (firstKey) {
            const keyNum = parseInt(firstKey);
            const now = Date.now();
            const nowSeconds = Math.floor(now / 1000);
            
            // Test different interpretations
            const asMilliseconds = new Date(keyNum);
            const asSeconds = new Date(keyNum * 1000);
            const asMicroseconds = new Date(keyNum / 1000);
            
            analysisHtml = `
                <h3 class="info">Testing Key: ${firstKey}</h3>
                
                <div class="timestamp-test">
                    <strong>Current Time:</strong><br>
                    Milliseconds: ${now}<br>
                    Seconds: ${nowSeconds}<br>
                    Display: ${new Date().toLocaleString()}<br>
                </div>
                
                <div class="timestamp-test">
                    <strong>Interpretation 1: Key as Milliseconds</strong><br>
                    Date: ${asMilliseconds.toLocaleString()}<br>
                    Year: ${asMilliseconds.getFullYear()}<br>
                    ${asMilliseconds.getFullYear() === 1970 ? '<span class="error">❌ This gives 1970 - WRONG</span>' : '<span class="success">✅ This looks correct!</span>'}
                </div>
                
                <div class="timestamp-test">
                    <strong>Interpretation 2: Key as Seconds (multiply by 1000)</strong><br>
                    Date: ${asSeconds.toLocaleString()}<br>
                    Year: ${asSeconds.getFullYear()}<br>
                    ${asSeconds.getFullYear() > 2020 && asSeconds.getFullYear() <= 2026 ? '<span class="success">✅ This looks correct!</span>' : '<span class="error">❌ This gives wrong year</span>'}
                </div>
                
                <div class="timestamp-test">
                    <strong>Interpretation 3: Key as Microseconds (divide by 1000)</strong><br>
                    Date: ${asMicroseconds.toLocaleString()}<br>
                    Year: ${asMicroseconds.getFullYear()}<br>
                    ${asMicroseconds.getFullYear() > 2020 && asMicroseconds.getFullYear() <= 2026 ? '<span class="success">✅ This looks correct!</span>' : '<span class="error">❌ This gives wrong year</span>'}
                </div>
                
                <div class="timestamp-test">
                    <strong>Key Length Analysis:</strong><br>
                    Key length: ${firstKey.length} digits<br>
                    ${firstKey.length === 10 ? '<span class="info">📌 10 digits = Unix timestamp in SECONDS</span>' : ''}
                    ${firstKey.length === 13 ? '<span class="info">📌 13 digits = Unix timestamp in MILLISECONDS</span>' : ''}
                    ${firstKey.length === 16 ? '<span class="info">📌 16 digits = Unix timestamp in MICROSECONDS</span>' : ''}
                </div>
            `;
            
            analysisOutput.innerHTML = analysisHtml;
            
            // Provide recommendation
            let recommendation = '<h3 class="success">✅ Recommended Code Fix:</h3>';
            
            if (firstKey.length === 10) {
                // Seconds - need to multiply by 1000
                recommendation += `
                    <p class="info">Your Firebase keys are in SECONDS. You need to multiply by 1000:</p>
                    <pre style="background: #000; padding: 15px; color: #0f0; overflow-x: auto;">
historyData.push({
    timestamp: timestamp * 1000,  // Convert seconds to milliseconds
    value: value
});

// Later when displaying:
const date = new Date(record.timestamp);  // Use directly
                    </pre>
                `;
            } else if (firstKey.length === 13) {
                // Milliseconds - use directly
                recommendation += `
                    <p class="info">Your Firebase keys are in MILLISECONDS. Use them directly:</p>
                    <pre style="background: #000; padding: 15px; color: #0f0; overflow-x: auto;">
historyData.push({
    timestamp: timestamp,  // Already in milliseconds
    value: value
});

// Later when displaying:
const date = new Date(record.timestamp);  // Use directly
                    </pre>
                `;
            } else {
                recommendation += `
                    <p class="error">Unusual timestamp format detected. Manual investigation needed.</p>
                    <p>Key length: ${firstKey.length} digits</p>
                `;
            }
            
            recommendationOutput.innerHTML = recommendation;
        }
    });
    
    console.log('✅ Debug tool initialized');
};
</script>

</body>
</html>