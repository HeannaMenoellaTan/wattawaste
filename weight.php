<?php
require_once 'firebase_config.php';

// ==== Get Latest Weight Data from Firebase ====
try {
    $database = getDatabase();
    
    // Get latest weight
    $latestRef = $database->getReference('sensors/weight/latest');
    $latestSnapshot = $latestRef->getSnapshot();
    
    if ($latestSnapshot->exists()) {
        $latestData = $latestSnapshot->getValue();
        $currentWeight = $latestData['value'] ?? 0;
        $weightCapacity = $latestData['capacity'] ?? 25;
    } else {
        $currentWeight = 0;
        $weightCapacity = 25;
    }
    
    // Calculate fertilizer output (50% of current weight)
    $fertilizerOutput = round($currentWeight * 0.5, 2);
    
    // Determine if bin is almost full (>80%)
    $weightPercentage = ($currentWeight / $weightCapacity) * 100;
    $showWarning = $weightPercentage >= 80;
    
    // ==== Fetch Weight History ====
    $historyRef = $database->getReference('sensors/weight/history');
    $historySnapshot = $historyRef
        ->orderByChild('timestamp')
        ->limitToLast(10)
        ->getSnapshot();
    
    $historyData = [];
    if ($historySnapshot->exists()) {
        $historyValues = $historySnapshot->getValue();
        foreach ($historyValues as $key => $item) {
            $historyData[] = [
                'timestamp' => $item['timestamp'] ?? time(),
                'weight_added' => $item['value'] ?? 0,
                'datetime' => isset($item['timestamp']) ? date('g:i A - F j, Y', $item['timestamp']) : 'N/A'
            ];
        }
    }
    
    // Reverse to show newest first
    $historyData = array_reverse($historyData);
    
} catch (Exception $e) {
    error_log("Weight page error: " . $e->getMessage());
    $currentWeight = 0;
    $weightCapacity = 25;
    $fertilizerOutput = 0;
    $showWarning = false;
    $historyData = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Weight Level | WattAWaste</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f0f4f0;
    margin: 0;
    padding: 0;
}

.main { 
    padding: 30px;
    max-width: 1400px;
    margin: 0 auto;
}

/* Warning Popup */
.warning-popup {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border: 3px solid #ff6b6b;
    border-radius: 15px;
    padding: 30px 40px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    z-index: 10000;
    text-align: center;
    min-width: 400px;
    animation: slideDown 0.4s ease;
}

.warning-popup.show {
    display: block;
}

.warning-popup-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
}

.warning-popup-overlay.show {
    display: block;
}

.warning-popup i {
    color: #ff6b6b;
    font-size: 48px;
    margin-bottom: 15px;
    animation: pulse 2s infinite;
}

.warning-popup h3 {
    color: #d63031;
    font-size: 20px;
    margin: 10px 0;
}

.warning-popup p {
    color: #636e72;
    margin-bottom: 20px;
}

.warning-popup button {
    background: #ff6b6b;
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.3s ease;
}

.warning-popup button:hover {
    background: #ee5a6f;
}

@keyframes slideDown {
    from {
        transform: translate(-50%, -60%);
        opacity: 0;
    }
    to {
        transform: translate(-50%, -50%);
        opacity: 1;
    }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Cards Container */
.cards-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-bottom: 30px;
}

.weight-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.weight-card h3 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 20px;
    color: #2d3436;
}

.weight-value {
    font-size: 42px;
    font-weight: bold;
    color: #2d3436;
    margin-bottom: 5px;
}

.weight-capacity {
    font-size: 14px;
    color: #636e72;
    margin-bottom: 15px;
}

/* Progress Bar */
.progress-bar-container {
    width: 100%;
    height: 12px;
    background: #e8e8e8;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 10px;
}

.progress-bar-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 0.5s ease, background 0.3s ease;
}

/* History Table */
.history-section {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.history-section h3 {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 20px;
    color: #2d3436;
}

.history-table {
    width: 100%;
    border-collapse: collapse;
}

.history-table thead {
    background: #5f6368;
    color: white;
}

.history-table th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
}

.history-table td {
    padding: 15px;
    border-bottom: 1px solid #e8e8e8;
    font-size: 14px;
    color: #2d3436;
}

.history-table tbody tr {
    transition: background 0.2s ease;
}

.history-table tbody tr:hover {
    background: #f8f9fa;
}

.history-table tbody tr:nth-child(even) {
    background: #fafafa;
}

.history-table tbody tr:nth-child(even):hover {
    background: #f0f0f0;
}

.history-table tbody tr.highlight {
    background: #ffe5e5;
}

.history-table tbody tr.highlight:hover {
    background: #ffd0d0;
}

.no-data {
    text-align: center;
    padding: 40px;
    color: #999;
    font-style: italic;
}

@media (max-width: 768px) {
    .cards-row {
        grid-template-columns: 1fr;
    }
    
    .history-table {
        font-size: 12px;
    }
    
    .history-table th,
    .history-table td {
        padding: 10px;
    }
}
</style>
</head>
<body>

<?php include 'sideabr.php'; ?>

<div class="main">
<?php include 'topnav.php'; ?>

<!-- Warning Popup Overlay -->
<?php if ($showWarning): ?>
<div class="warning-popup-overlay show" id="warningOverlay" onclick="closeWarning()"></div>
<div class="warning-popup show" id="warningPopup">
    <i class="fas fa-exclamation-triangle"></i>
    <h3>⚠️ Warning!</h3>
    <p>The bin is almost full!</p>
    <button onclick="closeWarning()">Got it</button>
</div>
<?php endif; ?>

<div class="cards-row">
    <!-- Current Weight Card -->
    <div class="weight-card">
        <h3>Current Weight</h3>
        <div class="weight-value"><?= number_format($currentWeight, 2) ?> kg</div>
        <div class="weight-capacity">Capacity: <?= $weightCapacity ?> kg</div>
        <div class="progress-bar-container">
            <div class="progress-bar-fill" style="
                width: <?= min(100, $weightPercentage) ?>%; 
                background: <?= $weightPercentage >= 80 ? 'linear-gradient(90deg, #ff6b6b, #ee5a6f)' : 
                              ($weightPercentage >= 60 ? 'linear-gradient(90deg, #ffd93d, #f6c23e)' : 
                              'linear-gradient(90deg, #6fcf97, #27ae60)') ?>;
            "></div>
        </div>
    </div>
    
    <!-- Total Compost Fertilizer Card -->
    <div class="weight-card">
        <h3>Total Compost Fertilizer</h3>
        <div class="weight-value"><?= number_format($fertilizerOutput, 1) ?> kg</div>
        <div class="weight-capacity">Current yield</div>
        <div class="progress-bar-container">
            <div class="progress-bar-fill" style="
                width: <?= min(100, ($fertilizerOutput / ($weightCapacity * 0.5)) * 100) ?>%; 
                background: linear-gradient(90deg, #ffd93d, #f6c23e);
            "></div>
        </div>
    </div>
</div>

<!-- Weight History Table -->
<div class="history-section">
    <h3>Weight History Table</h3>
    
    <?php if (!empty($historyData)): ?>
    <table class="history-table">
        <thead>
            <tr>
                <th>Time and Date</th>
                <th>Weight added</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($historyData as $index => $entry): ?>
            <tr>
                <td><?= htmlspecialchars($entry['datetime']) ?></td>
                <td><?= number_format($entry['weight_added'], 2) ?> kg</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="no-data">
        <i class="fas fa-database" style="font-size: 48px; color: #ccc; margin-bottom: 10px; display: block;"></i>
        No weight history data available
    </div>
    <?php endif; ?>
</div>

</div>

<script>
function closeWarning() {
    document.getElementById('warningPopup').classList.remove('show');
    document.getElementById('warningOverlay').classList.remove('show');
}

// Smooth progress bar animation on load
window.addEventListener('load', () => {
    document.querySelectorAll('.progress-bar-fill').forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
        }, 100);
    });
});
</script>

</body>
</html>

