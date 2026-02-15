<?php
require_once 'firebase_config.php';

// ==== SENSOR DATA FETCH FROM FIREBASE ====
function fetchFirebaseData($sensorType, $limit = 7) {
    try {
        $database = getDatabase();
        
        $historyRef = $database->getReference("sensors/$sensorType/history");
        $snapshot = $historyRef
            ->orderByChild('timestamp')
            ->limitToLast($limit)
            ->getSnapshot();
        
        if (!$snapshot->exists()) {
            return [];
        }
        
        $data = [];
        $historyData = $snapshot->getValue();
        
        foreach ($historyData as $key => $item) {
            $data[] = [
                'id' => $key,
                'value' => $item['value'] ?? 0,
                'time' => isset($item['timestamp']) ? date('Y-m-d H:i:s', $item['timestamp']) : date('Y-m-d H:i:s')
            ];
        }
        
        return $data;
        
    } catch (Exception $e) {
        error_log("Firebase fetch error for $sensorType: " . $e->getMessage());
        return [];
    }
}

$temperature = fetchFirebaseData('temperature', 7);
$humidity    = fetchFirebaseData('humidity', 7);
$gas         = fetchFirebaseData('gas', 7);
$ph          = fetchFirebaseData('ph', 7);

// ==== KPI FUNCTION ====
function getKPI($data) {
    if (empty($data)) {
        return ['latest' => 0, 'diff' => 0, 'trend' => '▬', 'avg' => 0];
    }
    
    $latest = end($data)['value'] ?? 0;
    $previous = count($data) > 1 ? $data[count($data) - 2]['value'] : 0;
    $diff = round($latest - $previous, 2);
    $trend = $diff > 0 ? '▲' : ($diff < 0 ? '▼' : '▬');
    $avg = round(array_sum(array_column($data, 'value')) / count($data), 2);
    return ['latest' => $latest, 'diff' => $diff, 'trend' => $trend, 'avg' => $avg];
}

$tempKPI     = getKPI($temperature);
$humidityKPI = getKPI($humidity);
$gasKPI      = getKPI($gas);
$phKPI       = getKPI($ph);

// ==== PHILIPPINE SEASONAL PLANTS DATA (NO API) ====
// Plant nutrient requirements per m² (in grams of N-P-K)
$plantDatabase = [
    // Amihan Season (Cool & Dry: Nov-Feb)
    'peanuts' => [
        'name' => 'Peanuts (Mani)',
        'scientific_name' => 'Arachis hypogaea',
        'icon' => '🥜',
        'season' => 'amihan',
        'fertilizer_type' => 'Aerobic',
        'nitrogen_per_m2' => 8,  // Legumes fix nitrogen, need less
        'phosphorus_per_m2' => 20,
        'potassium_per_m2' => 15,
        'description' => 'Protein-rich legumes thriving in cool, dry conditions.',
        'tags' => ['Legumes', 'Full Sun']
    ],
    'lettuce' => [
        'name' => 'Lettuce',
        'scientific_name' => 'Lactuca sativa',
        'icon' => '🥬',
        'season' => 'amihan',
        'fertilizer_type' => 'Aerobic',
        'nitrogen_per_m2' => 12,
        'phosphorus_per_m2' => 8,
        'potassium_per_m2' => 12,
        'description' => 'Crisp, fresh lettuce perfect for cool season.',
        'tags' => ['Leafy Greens', 'Partial Shade']
    ],
    'okra' => [
        'name' => 'Okra',
        'scientific_name' => 'Abelmoschus esculentus',
        'icon' => '🫛',
        'season' => 'amihan',
        'fertilizer_type' => 'Aerobic',
        'nitrogen_per_m2' => 15,
        'phosphorus_per_m2' => 12,
        'potassium_per_m2' => 18,
        'description' => 'Fast-growing vegetable producing tender pods.',
        'tags' => ['Vegetables', 'Full Sun']
    ],
    'carrots' => [
        'name' => 'Carrots',
        'scientific_name' => 'Daucus carota',
        'icon' => '🥕',
        'season' => 'amihan',
        'fertilizer_type' => 'Aerobic',
        'nitrogen_per_m2' => 10,
        'phosphorus_per_m2' => 15,
        'potassium_per_m2' => 20,
        'description' => 'Sweet root vegetables flourishing in cool weather.',
        'tags' => ['Root Vegetables', 'Full Sun']
    ],
    'corn' => [
        'name' => 'Sweet Corn',
        'scientific_name' => 'Zea mays',
        'icon' => '🌽',
        'season' => 'amihan',
        'fertilizer_type' => 'Aerobic',
        'nitrogen_per_m2' => 18,
        'phosphorus_per_m2' => 14,
        'potassium_per_m2' => 16,
        'description' => 'Golden ears of corn perfect for the dry season.',
        'tags' => ['Grains', 'Full Sun']
    ],
    'onions' => [
        'name' => 'Onions',
        'scientific_name' => 'Allium cepa',
        'icon' => '🧅',
        'season' => 'amihan',
        'fertilizer_type' => 'Aerobic',
        'nitrogen_per_m2' => 12,
        'phosphorus_per_m2' => 10,
        'potassium_per_m2' => 14,
        'description' => 'Cool-season bulb crop ideal for Amihan.',
        'tags' => ['Bulb Vegetables', 'Full Sun']
    ],
    
    // Tag-init Season (Hot & Dry: Mar-May)
    'tomatoes' => [
        'name' => 'Tomatoes',
        'scientific_name' => 'Solanum lycopersicum',
        'icon' => '🍅',
        'season' => 'tag-init',
        'fertilizer_type' => 'Aerobic',
        'nitrogen_per_m2' => 14,
        'phosphorus_per_m2' => 18,
        'potassium_per_m2' => 20,
        'description' => 'Heat-loving plants producing abundant fruit.',
        'tags' => ['Fruit Vegetables', 'Full Sun']
    ],
    'hot_peppers' => [
        'name' => 'Hot Peppers (Sili)',
        'scientific_name' => 'Capsicum frutescens',
        'icon' => '🌶️',
        'season' => 'tag-init',
        'fertilizer_type' => 'Aerobic',
        'nitrogen_per_m2' => 12,
        'phosphorus_per_m2' => 16,
        'potassium_per_m2' => 18,
        'description' => 'Fiery peppers thriving in hot weather.',
        'tags' => ['Spicy', 'Full Sun']
    ],
    'bitter_gourd' => [
        'name' => 'Bitter Gourd (Ampalaya)',
        'scientific_name' => 'Momordica charantia',
        'icon' => '🥒',
        'season' => 'tag-init',
        'fertilizer_type' => 'Aerobic',
        'nitrogen_per_m2' => 16,
        'phosphorus_per_m2' => 14,
        'potassium_per_m2' => 18,
        'description' => 'Hardy vine producing nutritious fruit.',
        'tags' => ['Vining Crops', 'Full Sun']
    ],
    'eggplant' => [
        'name' => 'Eggplant (Talong)',
        'scientific_name' => 'Solanum melongena',
        'icon' => '🍆',
        'season' => 'tag-init',
        'fertilizer_type' => 'Aerobic',
        'nitrogen_per_m2' => 15,
        'phosphorus_per_m2' => 16,
        'potassium_per_m2' => 19,
        'description' => 'Heat-tolerant crop producing glossy fruits.',
        'tags' => ['Vegetables', 'Full Sun']
    ],
    'yard_long_beans' => [
        'name' => 'Yard Long Beans (Sitaw)',
        'scientific_name' => 'Vigna unguiculata',
        'icon' => '🫘',
        'season' => 'tag-init',
        'fertilizer_type' => 'Aerobic',
        'nitrogen_per_m2' => 8,
        'phosphorus_per_m2' => 12,
        'potassium_per_m2' => 14,
        'description' => 'Fast-growing beans perfect for hot season.',
        'tags' => ['Legumes', 'Full Sun']
    ],
    'sunflowers' => [
        'name' => 'Sunflowers',
        'scientific_name' => 'Helianthus annuus',
        'icon' => '🌻',
        'season' => 'tag-init',
        'fertilizer_type' => 'Aerobic',
        'nitrogen_per_m2' => 10,
        'phosphorus_per_m2' => 12,
        'potassium_per_m2' => 15,
        'description' => 'Sun-loving ornamentals reaching impressive heights.',
        'tags' => ['Ornamental', 'Full Sun']
    ],
    
    // Tag-ulan Season (Rainy: Jun-Oct)
    'rice' => [
        'name' => 'Rice (Palay)',
        'scientific_name' => 'Oryza sativa',
        'icon' => '🌾',
        'season' => 'tag-ulan',
        'fertilizer_type' => 'Anaerobic',
        'nitrogen_per_m2' => 16,
        'phosphorus_per_m2' => 10,
        'potassium_per_m2' => 12,
        'description' => 'Traditional rainy season crop.',
        'tags' => ['Grains', 'Wetland']
    ],
    'taro' => [
        'name' => 'Taro (Gabi)',
        'scientific_name' => 'Colocasia esculenta',
        'icon' => '🥔',
        'season' => 'tag-ulan',
        'fertilizer_type' => 'Anaerobic',
        'nitrogen_per_m2' => 14,
        'phosphorus_per_m2' => 12,
        'potassium_per_m2' => 16,
        'description' => 'Root crop thriving in wet conditions.',
        'tags' => ['Root Vegetables', 'Partial Shade']
    ],
    'mung_beans' => [
        'name' => 'Mung Beans (Munggo)',
        'scientific_name' => 'Vigna radiata',
        'icon' => '🫛',
        'season' => 'tag-ulan',
        'fertilizer_type' => 'Aerobic',
        'nitrogen_per_m2' => 8,
        'phosphorus_per_m2' => 10,
        'potassium_per_m2' => 12,
        'description' => 'Quick-growing legume ideal for rainy season.',
        'tags' => ['Legumes', 'Full Sun']
    ],
    'cucumber' => [
        'name' => 'Cucumber (Pipino)',
        'scientific_name' => 'Cucumis sativus',
        'icon' => '🥒',
        'season' => 'tag-ulan',
        'fertilizer_type' => 'Aerobic',
        'nitrogen_per_m2' => 13,
        'phosphorus_per_m2' => 11,
        'potassium_per_m2' => 15,
        'description' => 'Moisture-loving vine producing crisp fruits.',
        'tags' => ['Vining Crops', 'Full Sun']
    ],
    'water_spinach' => [
        'name' => 'Water Spinach (Kangkong)',
        'scientific_name' => 'Ipomoea aquatica',
        'icon' => '🌿',
        'season' => 'tag-ulan',
        'fertilizer_type' => 'Anaerobic',
        'nitrogen_per_m2' => 15,
        'phosphorus_per_m2' => 8,
        'potassium_per_m2' => 10,
        'description' => 'Semi-aquatic leafy green perfect for rainy season.',
        'tags' => ['Leafy Greens', 'Wetland']
    ],
    'lemongrass' => [
        'name' => 'Lemongrass (Tanglad)',
        'scientific_name' => 'Cymbopogon citratus',
        'icon' => '🎋',
        'season' => 'tag-ulan',
        'fertilizer_type' => 'Aerobic',
        'nitrogen_per_m2' => 10,
        'phosphorus_per_m2' => 8,
        'potassium_per_m2' => 12,
        'description' => 'Aromatic grass thriving in humid conditions.',
        'tags' => ['Herbs', 'Full Sun']
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Analytics | WattAWaste</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://kit.fontawesome.com/a2e0e6ad65.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Firebase Auth and Database -->
<script type="module">
import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
import { getAuth, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
import { getDatabase, ref, set, push, onValue, remove, get } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-database.js';

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

let currentUser = null;
let isVerified = false;

onAuthStateChanged(auth, (user) => {
    if (!user) {
        console.log('❌ No user found, redirecting to login...');
        window.location.href = 'login.php';
    } else {
        console.log('✅ User authenticated:', user.email || user.phoneNumber);
        currentUser = user;
        sessionStorage.setItem('userEmail', user.email || user.phoneNumber || '');
        sessionStorage.setItem('userId', user.uid);
        
        // Check verification status
        const userRef = ref(database, `users/${user.uid}`);
        onValue(userRef, (snapshot) => {
            const userData = snapshot.val();
            isVerified = userData && userData.isVerified === true;
            
            // Show/hide add plant button based on verification
            const addPlantBtn = document.getElementById('addPlantBtn');
            if (addPlantBtn) {
                addPlantBtn.style.display = isVerified ? 'flex' : 'none';
            }
            
            // Load user's plants
            window.loadUserPlants();
        });
    }
});

window.firebaseAuth = auth;
window.firebaseDatabase = database;
window.firebaseRef = ref;
window.firebaseSet = set;
window.firebasePush = push;
window.firebaseOnValue = onValue;
window.firebaseRemove = remove;
window.firebaseGet = get;
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
}

body { 
    font-family: 'Poppins', Arial, sans-serif; 
    background-color: var(--bg); 
    color: var(--ink); 
}

h2 { 
    font-size: 28px; 
    margin-bottom: 30px; 
    text-align: center; 
    font-weight: 700;
}

/* Charts Container */
.charts-container { 
    display: grid; 
    grid-template-columns: repeat(2, 1fr); 
    gap: 20px; 
    margin-bottom: 40px; 
    padding: 0 20px;
}

.chart-card { 
    background: var(--panel); 
    border-radius: 16px; 
    box-shadow: 0 6px 16px rgba(2,6,23,.06); 
    padding: 25px; 
    font-size: 18px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.chart-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 26px rgba(2,6,23,.12);
}

.kpi { 
    display: flex; 
    flex-direction: column; 
    margin-bottom: 15px; 
}

.kpi-value { 
    font-size: 28px; 
    font-weight: bold; 
}

.kpi-trend { 
    font-size: 22px; 
    font-weight: bold; 
    margin-top: 5px; 
}

.kpi-avg { 
    font-size: 16px; 
    color: var(--muted); 
}

.trend.up { color: #28a745; }
.trend.down { color: #dc3545; }
.trend.same { color: #6c757d; }

canvas { 
    width: 100% !important; 
    height: 250px !important; 
}

/* Plant Section */
.plant-section {
    background: var(--panel);
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 6px 16px rgba(2,6,23,.06);
    margin: 40px 20px;
    position: relative;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
}

.section-header h3 { 
    font-size: 24px; 
    margin: 0;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Season Selector */
.season-selector {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.season-btn {
    padding: 10px 20px;
    border: 2px solid rgba(0,0,0,0.1);
    background: white;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.season-btn:hover {
    border-color: var(--brand);
    background: rgba(76, 175, 80, 0.05);
}

.season-btn.active {
    background: linear-gradient(135deg, var(--brand-light), var(--brand-med));
    color: white;
    border-color: transparent;
    box-shadow: 0 4px 12px rgba(35, 237, 153, 0.3);
}

/* Plant Grid */
.plant-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); 
    gap: 20px;
    margin-top: 20px;
}

.plant-card { 
    background: linear-gradient(135deg, var(--brand-light), var(--brand-med));
    padding: 20px;
    border-radius: 16px;
    text-align: center; 
    font-size: 16px;
    cursor: pointer; 
    transition: transform 0.3s, box-shadow 0.3s;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(35, 237, 153, 0.2);
}

.plant-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle, transparent 30%, rgba(0,0,0,0.1));
}

.plant-card:hover { 
    transform: translateY(-8px); 
    box-shadow: 0 12px 24px rgba(35, 237, 153, 0.3);
}

.plant-icon {
    font-size: 60px;
    margin-bottom: 10px;
    position: relative;
    z-index: 1;
}

.plant-card h4 { 
    font-size: 18px; 
    color: white;
    margin: 10px 0 5px;
    font-weight: 700;
    position: relative;
    z-index: 1;
}

.plant-scientific {
    font-size: 12px;
    color: rgba(255,255,255,0.9);
    font-style: italic;
    margin-bottom: 10px;
    position: relative;
    z-index: 1;
}

.plant-tags {
    display: flex;
    gap: 5px;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 10px;
    position: relative;
    z-index: 1;
}

.plant-tag {
    padding: 4px 10px;
    background: rgba(255,255,255,0.3);
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    color: white;
    backdrop-filter: blur(5px);
}

/* User Plants Section */
.user-plants-section {
    margin-top: 30px;
    padding-top: 30px;
    border-top: 2px solid rgba(0,0,0,0.05);
}

.user-plant-card {
    background: white;
    border: 2px solid var(--brand);
    border-radius: 16px;
    padding: 20px;
    position: relative;
}

.user-plant-card .delete-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #ef4444;
    color: white;
    border: none;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.3s ease;
}

.user-plant-card .delete-btn:hover {
    background: #dc2626;
    transform: scale(1.1);
}

.user-plant-card h4 {
    color: var(--brand-dark);
    margin-bottom: 5px;
}

.plot-size-badge {
    display: inline-block;
    padding: 6px 12px;
    background: rgba(76, 175, 80, 0.1);
    border-radius: 20px;
    font-weight: 600;
    color: var(--brand-dark);
    font-size: 14px;
    margin-top: 10px;
}

/* Floating Add Button */
.add-plant-btn {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--brand-light), var(--brand-med));
    border: none;
    border-radius: 50%;
    color: white;
    font-size: 28px;
    cursor: pointer;
    box-shadow: 0 6px 20px rgba(35, 237, 153, 0.4);
    transition: all 0.3s ease;
    z-index: 1000;
    display: none;
}

.add-plant-btn:hover {
    transform: scale(1.1) rotate(90deg);
    box-shadow: 0 8px 30px rgba(35, 237, 153, 0.6);
}

/* Modal Styles */
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

.form-input, .form-select {
    width: 100%;
    padding: 12px 16px;
    background: rgba(0, 0, 0, 0.03);
    border: 2px solid rgba(0, 0, 0, 0.1);
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.3s ease;
}

.form-input:focus, .form-select:focus {
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

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(35, 237, 153, 0.4);
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
}

/* Fertilizer Calculator */
.fertilizer-calc-section {
    background: linear-gradient(135deg, rgba(35, 237, 153, 0.1), rgba(15, 129, 86, 0.1));
    border-radius: 16px;
    padding: 30px;
    margin: 40px 20px;
}

.calc-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.calc-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.calc-result {
    font-size: 32px;
    font-weight: 800;
    background: linear-gradient(135deg, var(--brand-light), var(--brand-med));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin: 10px 0;
}

/* Tips Section */
.tips-section {
    background: white;
    border-radius: 16px;
    padding: 30px;
    margin: 40px 20px;
    box-shadow: 0 6px 16px rgba(2,6,23,.06);
}

.tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.tip-card {
    padding: 20px;
    background: rgba(76, 175, 80, 0.05);
    border-radius: 12px;
    border-left: 4px solid var(--brand);
}

.tip-card h4 {
    color: var(--brand-dark);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.tip-card ul {
    margin: 10px 0 0 20px;
    color: var(--muted);
}

.tip-card li {
    margin-bottom: 8px;
    line-height: 1.6;
}

.no-data { 
    text-align: center; 
    padding: 40px 20px;
    color: var(--muted);
    font-style: italic; 
}

/* Plant Detail Popup */
.plant-popup {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    text-align: center;
    max-width: 400px;
    width: 90%;
    z-index: 10000;
}

.plant-popup.show {
    display: block;
    animation: popupSlideIn 0.3s ease;
}

@keyframes popupSlideIn {
    from {
        opacity: 0;
        transform: translate(-50%, -60%);
    }
    to {
        opacity: 1;
        transform: translate(-50%, -50%);
    }
}

.popup-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
}

.popup-overlay.show {
    display: block;
}

.plant-popup .close {
    position: absolute;
    top: 15px;
    right: 15px;
    font-size: 28px;
    cursor: pointer;
    color: #666;
    transition: color 0.3s;
}

.plant-popup .close:hover {
    color: #000;
}

.popup-icon {
    font-size: 80px;
    margin-bottom: 15px;
}

.popup-content h4 {
    font-size: 24px;
    color: var(--brand-dark);
    margin-bottom: 5px;
}

.popup-scientific {
    font-style: italic;
    color: var(--muted);
    margin-bottom: 20px;
}

.popup-info {
    text-align: left;
    margin: 20px 0;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.info-label {
    font-weight: 600;
    color: var(--ink);
}

.info-value {
    color: var(--muted);
}

.fertilizer-badge {
    display: inline-block;
    padding: 6px 14px;
    background: linear-gradient(135deg, var(--brand-light), var(--brand-med));
    color: white;
    border-radius: 20px;
    font-weight: 600;
    margin-top: 15px;
}

@media(max-width:768px) { 
    .charts-container { 
        grid-template-columns: 1fr; 
    }
    
    .plant-grid { 
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); 
    }
    
    .season-selector {
        width: 100%;
    }
    
    .season-btn {
        flex: 1;
        justify-content: center;
    }
    
    .add-plant-btn {
        bottom: 20px;
        right: 20px;
    }
}
</style>
</head>
<body>

<?php  
include 'sideabr.php';
include_once 'notif_bell.php';
?>

<div class="main">
<?php include 'topnav.php';?>

<h2>📊 Data Analytics Overview</h2>

<!-- Sensor Charts -->
<div class="charts-container">
  <?php
  $sensors = [
      ['title'=>'Temperature (°C)','kpi'=>$tempKPI,'id'=>'tempChart','color'=>'#ff5733','data'=>$temperature],
      ['title'=>'Humidity (%)','kpi'=>$humidityKPI,'id'=>'humidityChart','color'=>'#70c575','data'=>$humidity],
      ['title'=>'Gas Level (ppm)','kpi'=>$gasKPI,'id'=>'gasChart','color'=>'#2e86de','data'=>$gas],
      ['title'=>'pH Level','kpi'=>$phKPI,'id'=>'phChart','color'=>'#28a745','data'=>$ph]
  ];
  foreach($sensors as $s):
  ?>
  <div class="chart-card">
      <h4><?= $s['title'] ?></h4>
      <?php if (!empty($s['data'])): ?>
      <div class="kpi">
          <span class="kpi-value"><?= $s['kpi']['latest'] ?><?= strpos($s['title'],'%')!==false?'%':($s['title']=='Temperature (°C)'?'°C':'') ?></span>
          <span class="kpi-trend <?= $s['kpi']['diff']>0?'up':($s['kpi']['diff']<0?'down':'same') ?>"><?= $s['kpi']['trend'] ?> <?= abs($s['kpi']['diff']) ?></span>
          <div class="kpi-avg">(Avg: <?= $s['kpi']['avg'] ?>)</div>
      </div>
      <canvas id="<?= $s['id'] ?>"></canvas>
      <?php else: ?>
      <div class="no-data">No data available</div>
      <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<!-- Philippine Seasonal Plants -->
<div class="plant-section">
    <div class="section-header">
        <h3><i class="fas fa-seedling"></i> Philippine Seasonal Plants</h3>
        <div class="season-selector">
            <button class="season-btn active" data-season="amihan" onclick="filterPlantsBySeason('amihan')">
                <i class="fas fa-wind"></i> Amihan
            </button>
            <button class="season-btn" data-season="tag-init" onclick="filterPlantsBySeason('tag-init')">
                <i class="fas fa-sun"></i> Tag-init
            </button>
            <button class="season-btn" data-season="tag-ulan" onclick="filterPlantsBySeason('tag-ulan')">
                <i class="fas fa-cloud-rain"></i> Tag-ulan
            </button>
        </div>
    </div>

    <div class="plant-grid" id="seasonalPlantsGrid">
        <?php foreach($plantDatabase as $key => $plant): ?>
        <div class="plant-card" data-season="<?= $plant['season'] ?>" onclick='showPlantDetails(<?= json_encode($plant) ?>)'>
            <div class="plant-icon"><?= $plant['icon'] ?></div>
            <h4><?= htmlspecialchars($plant['name']) ?></h4>
            <div class="plant-scientific"><?= htmlspecialchars($plant['scientific_name']) ?></div>
            <div class="plant-tags">
                <?php foreach($plant['tags'] as $tag): ?>
                    <span class="plant-tag"><?= htmlspecialchars($tag) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- User's Plants -->
<div class="plant-section user-plants-section">
    <div class="section-header">
        <h3><i class="fas fa-user-circle"></i> My Garden</h3>
    </div>
    <div class="plant-grid" id="userPlantsGrid">
        <div class="no-data">No plants added yet. Click the + button to add your first plant!</div>
    </div>
</div>

<!-- Fertilizer Calculator -->
<div class="fertilizer-calc-section">
    <h3 style="text-align: center; margin-bottom: 20px;">
        <i class="fas fa-calculator"></i> Fertilizer Calculator
    </h3>
    <div class="calc-grid" id="fertilizerCalc">
        <div class="calc-card">
            <h4><i class="fas fa-weight"></i> Available Fertilizer</h4>
            <div class="calc-result" id="availableFertilizer">-- kg</div>
            <small style="color: var(--muted);">From compost bin</small>
        </div>
        <div class="calc-card">
            <h4><i class="fas fa-chart-pie"></i> Total Required</h4>
            <div class="calc-result" id="totalRequired">-- kg</div>
            <small style="color: var(--muted);">For all your plants</small>
        </div>
        <div class="calc-card">
            <h4><i class="fas fa-balance-scale"></i> Balance</h4>
            <div class="calc-result" id="fertilizerBalance">-- kg</div>
            <small style="color: var(--muted);">Surplus / Deficit</small>
        </div>
    </div>
</div>

<!-- Fertilizer Loss Prevention Tips -->
<div class="tips-section">
    <h3 style="text-align: center; margin-bottom: 20px;">
        <i class="fas fa-lightbulb"></i> How to Prevent Fertilizer Loss
    </h3>
    <div class="tips-grid">
        <div class="tip-card">
            <h4><i class="fas fa-box"></i> Proper Storage</h4>
            <ul>
                <li>Store in cool, dry place away from sunlight</li>
                <li>Keep in airtight containers to prevent moisture</li>
                <li>Label containers with date and type</li>
                <li>Elevate containers off ground</li>
            </ul>
        </div>
        <div class="tip-card">
            <h4><i class="fas fa-clock"></i> Application Timing</h4>
            <ul>
                <li>Apply during early morning or late afternoon</li>
                <li>Avoid application before heavy rain</li>
                <li>Apply when soil is slightly moist</li>
                <li>Split applications for better absorption</li>
            </ul>
        </div>
        <div class="tip-card">
            <h4><i class="fas fa-tachometer-alt"></i> Correct Dosage</h4>
            <ul>
                <li>Use calculator above for accurate amounts</li>
                <li>Don't over-apply (causes runoff and waste)</li>
                <li>Match fertilizer type to plant needs</li>
                <li>Consider soil test results</li>
            </ul>
        </div>
        <div class="tip-card">
            <h4><i class="fas fa-seedling"></i> Application Method</h4>
            <ul>
                <li>Incorporate into soil when possible</li>
                <li>Water lightly after application</li>
                <li>Apply around plant base, not on leaves</li>
                <li>Use mulch to reduce leaching</li>
            </ul>
        </div>
    </div>
</div>

<!-- Floating Add Plant Button -->
<button class="add-plant-btn" id="addPlantBtn" onclick="openAddPlantModal()">
    <i class="fas fa-plus"></i>
</button>

<!-- Add Plant Modal -->
<div class="modal-overlay" id="addPlantModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Add Plant to My Garden</h2>
            <p style="color: var(--muted); font-size: 14px;">Track your plants and calculate fertilizer needs</p>
        </div>

        <div id="modalAlertContainer"></div>

        <form id="addPlantForm">
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-seedling"></i> Select Plant
                </label>
                <select class="form-select" id="plantSelect" required>
                    <option value="">Choose a plant...</option>
                    <?php foreach($plantDatabase as $key => $plant): ?>
                    <option value='<?= json_encode($plant) ?>'>
                        <?= $plant['icon'] ?> <?= htmlspecialchars($plant['name']) ?> 
                        (<?= htmlspecialchars($plant['season']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-ruler"></i> Plot Size (m²)
                </label>
                <input type="number" class="form-input" id="plotSize" 
                       placeholder="Enter plot size in square meters" 
                       min="0.1" step="0.1" required>
            </div>

            <div class="form-group" id="fertilizerPreview" style="display: none;">
                <div style="background: rgba(76, 175, 80, 0.1); padding: 15px; border-radius: 10px;">
                    <strong>Fertilizer Calculation Preview:</strong>
                    <div style="margin-top: 10px;">
                        <div>• Nitrogen (N): <span id="previewN">--</span> g</div>
                        <div>• Phosphorus (P): <span id="previewP">--</span> g</div>
                        <div>• Potassium (K): <span id="previewK">--</span> g</div>
                        <div style="margin-top: 8px; font-weight: 700; color: var(--brand-dark);">
                            Total Compost Needed: <span id="previewTotal">--</span> kg
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-primary">
                <i class="fas fa-plus-circle"></i> Add Plant
            </button>
            <button type="button" class="btn-secondary" onclick="closeAddPlantModal()">
                Cancel
            </button>
        </form>
    </div>
</div>

<!-- Plant Detail Popup -->
<div class="popup-overlay" id="popupOverlay" onclick="closePlantPopup()"></div>
<div class="plant-popup" id="plantPopup">
    <span class="close" onclick="closePlantPopup()">&times;</span>
    <div class="popup-content">
        <div class="popup-icon" id="popupIcon"></div>
        <h4 id="popupName"></h4>
        <div class="popup-scientific" id="popupScientific"></div>
        <p id="popupDescription"></p>
        <div class="popup-info" id="popupInfo"></div>
        <div class="fertilizer-badge" id="popupFertilizer"></div>
    </div>
</div>

</div><!-- end .main -->

<script>
// Sensor Charts
const sensorsData = {
    tempChart: <?php echo json_encode($temperature); ?>,
    humidityChart: <?php echo json_encode($humidity); ?>,
    gasChart: <?php echo json_encode($gas); ?>,
    phChart: <?php echo json_encode($ph); ?>
};

const sensorColors = {
    tempChart: '#ff5733',
    humidityChart: '#70c575',
    gasChart: '#2e86de',
    phChart: '#28a745'
};

function getGradient(ctx,color){
    const g = ctx.createLinearGradient(0,0,0,250);
    g.addColorStop(0,color+'80');
    g.addColorStop(1,color+'10');
    return g;
}

Object.keys(sensorsData).forEach(id=>{
    if (sensorsData[id].length === 0) return;
    
    const ctx = document.getElementById(id).getContext('2d');
    const color = sensorColors[id];
    
    new Chart(ctx,{
        type: id==='humidityChart'?'bar':'line',
        data:{
            labels: sensorsData[id].map(d=>{
                const date = new Date(d.time);
                return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            }),
            datasets:[{
                label: id.replace('Chart', ''),
                data:sensorsData[id].map(d=>d.value),
                borderColor: id==='humidityChart'?'':color,
                backgroundColor: id==='humidityChart'
                    ? sensorsData[id].map(()=> color+'CC')
                    : getGradient(ctx, color),
                fill:true,
                tension:0.4,
                pointRadius:5,
                pointHoverRadius:8,
                pointBackgroundColor: color,
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options:{ 
            responsive:true, 
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales:{ 
                x:{
                    ticks:{color:'#2f5233', font: {size: 10}},
                    grid: {display: false}
                }, 
                y:{
                    beginAtZero:true,
                    ticks:{color:'#2f5233'},
                    grid: {color: 'rgba(0,0,0,0.05)'}
                } 
            } 
        }
    });
});

// Plant Database
const plantDatabase = <?php echo json_encode($plantDatabase); ?>;

// Filter plants by season
function filterPlantsBySeason(season) {
    // Update active button
    document.querySelectorAll('.season-btn').forEach(btn => {
        if (btn.dataset.season === season) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    // Filter plant cards
    document.querySelectorAll('#seasonalPlantsGrid .plant-card').forEach(card => {
        if (card.dataset.season === season) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Initialize with current season on load
window.addEventListener('DOMContentLoaded', () => {
    const currentSeason = getCurrentPhilippineSeason();
    filterPlantsBySeason(currentSeason);
    calculateFertilizerNeeds();
});

// Get current Philippine season
function getCurrentPhilippineSeason() {
    const month = new Date().getMonth() + 1;
    if (month >= 11 || month <= 2) return 'amihan';
    if (month >= 3 && month <= 5) return 'tag-init';
    return 'tag-ulan';
}

// Show plant details popup
function showPlantDetails(plant) {
    document.getElementById('popupIcon').textContent = plant.icon;
    document.getElementById('popupName').textContent = plant.name;
    document.getElementById('popupScientific').textContent = plant.scientific_name;
    document.getElementById('popupDescription').textContent = plant.description;
    
    const infoHTML = `
        <div class="info-row">
            <span class="info-label">Season:</span>
            <span class="info-value">${plant.season}</span>
        </div>
        <div class="info-row">
            <span class="info-label">N-P-K per m²:</span>
            <span class="info-value">${plant.nitrogen_per_m2}-${plant.phosphorus_per_m2}-${plant.potassium_per_m2}g</span>
        </div>
    `;
    document.getElementById('popupInfo').innerHTML = infoHTML;
    document.getElementById('popupFertilizer').textContent = `Uses ${plant.fertilizer_type} Compost`;
    
    document.getElementById('plantPopup').classList.add('show');
    document.getElementById('popupOverlay').classList.add('show');
}

function closePlantPopup() {
    document.getElementById('plantPopup').classList.remove('show');
    document.getElementById('popupOverlay').classList.remove('show');
}

// Add Plant Modal Functions
function openAddPlantModal() {
    document.getElementById('addPlantModal').classList.add('active');
}

function closeAddPlantModal() {
    document.getElementById('addPlantModal').classList.remove('active');
    document.getElementById('addPlantForm').reset();
    document.getElementById('fertilizerPreview').style.display = 'none';
    document.getElementById('modalAlertContainer').innerHTML = '';
}

// Calculate fertilizer preview
document.getElementById('plotSize')?.addEventListener('input', updateFertilizerPreview);
document.getElementById('plantSelect')?.addEventListener('change', updateFertilizerPreview);

function updateFertilizerPreview() {
    const plantData = document.getElementById('plantSelect').value;
    const plotSize = parseFloat(document.getElementById('plotSize').value);
    
    if (plantData && plotSize > 0) {
        const plant = JSON.parse(plantData);
        const n = (plant.nitrogen_per_m2 * plotSize).toFixed(1);
        const p = (plant.phosphorus_per_m2 * plotSize).toFixed(1);
        const k = (plant.potassium_per_m2 * plotSize).toFixed(1);
        const total = ((plant.nitrogen_per_m2 + plant.phosphorus_per_m2 + plant.potassium_per_m2) * plotSize / 1000).toFixed(2);
        
        document.getElementById('previewN').textContent = n;
        document.getElementById('previewP').textContent = p;
        document.getElementById('previewK').textContent = k;
        document.getElementById('previewTotal').textContent = total;
        document.getElementById('fertilizerPreview').style.display = 'block';
    } else {
        document.getElementById('fertilizerPreview').style.display = 'none';
    }
}

// Add plant form submission
document.getElementById('addPlantForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const plantData = JSON.parse(document.getElementById('plantSelect').value);
    const plotSize = parseFloat(document.getElementById('plotSize').value);
    const userId = sessionStorage.getItem('userId');
    
    if (!userId) {
        showModalAlert('Please log in to add plants', 'error');
        return;
    }
    
    try {
        const userPlantsRef = window.firebaseRef(window.firebaseDatabase, `user_plants/${userId}`);
        const newPlantRef = window.firebasePush(userPlantsRef);
        
        await window.firebaseSet(newPlantRef, {
            ...plantData,
            plotSize: plotSize,
            addedDate: new Date().toISOString(),
            totalNitrogen: (plantData.nitrogen_per_m2 * plotSize).toFixed(2),
            totalPhosphorus: (plantData.phosphorus_per_m2 * plotSize).toFixed(2),
            totalPotassium: (plantData.potassium_per_m2 * plotSize).toFixed(2),
            totalFertilizerNeeded: ((plantData.nitrogen_per_m2 + plantData.phosphorus_per_m2 + plantData.potassium_per_m2) * plotSize / 1000).toFixed(2)
        });
        
        showModalAlert('Plant added successfully!', 'success');
        setTimeout(() => {
            closeAddPlantModal();
            loadUserPlants();
            calculateFertilizerNeeds();
        }, 1500);
    } catch (error) {
        console.error('Error adding plant:', error);
        showModalAlert('Failed to add plant. Please try again.', 'error');
    }
});

// Load user's plants
window.loadUserPlants = async function() {
    const userId = sessionStorage.getItem('userId');
    if (!userId) return;
    
    const userPlantsRef = window.firebaseRef(window.firebaseDatabase, `user_plants/${userId}`);
    window.firebaseOnValue(userPlantsRef, (snapshot) => {
        const plants = snapshot.val();
        const grid = document.getElementById('userPlantsGrid');
        
        if (!plants) {
            grid.innerHTML = '<div class="no-data">No plants added yet. Click the + button to add your first plant!</div>';
            return;
        }
        
        let html = '';
        Object.entries(plants).forEach(([id, plant]) => {
            html += `
                <div class="plant-card user-plant-card">
                    <button class="delete-btn" onclick="deletePlant('${id}')">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="plant-icon">${plant.icon}</div>
                    <h4>${plant.name}</h4>
                    <div class="plant-scientific">${plant.scientific_name}</div>
                    <div class="plot-size-badge">
                        <i class="fas fa-ruler-combined"></i> ${plant.plotSize} m²
                    </div>
                    <div style="margin-top: 10px; font-size: 13px; color: var(--muted);">
                        Needs: ${plant.totalFertilizerNeeded} kg compost
                    </div>
                </div>
            `;
        });
        grid.innerHTML = html;
        
        calculateFertilizerNeeds();
    });
};

// Delete plant
window.deletePlant = async function(plantId) {
    if (!confirm('Are you sure you want to remove this plant?')) return;
    
    const userId = sessionStorage.getItem('userId');
    const plantRef = window.firebaseRef(window.firebaseDatabase, `user_plants/${userId}/${plantId}`);
    
    try {
        await window.firebaseRemove(plantRef);
        calculateFertilizerNeeds();
    } catch (error) {
        console.error('Error deleting plant:', error);
        alert('Failed to delete plant. Please try again.');
    }
};

// Calculate fertilizer needs
async function calculateFertilizerNeeds() {
    const userId = sessionStorage.getItem('userId');
    if (!userId) return;
    
    // Get available fertilizer from weight sensor
    const weightRef = window.firebaseRef(window.firebaseDatabase, 'sensors/weight/latest');
    const weightSnapshot = await window.firebaseGet(weightRef);
    const currentWeight = weightSnapshot.val() || 0;
    const availableFertilizer = (currentWeight * 0.5).toFixed(2); // 50% conversion rate
    
    // Get user's plants
    const userPlantsRef = window.firebaseRef(window.firebaseDatabase, `user_plants/${userId}`);
    const plantsSnapshot = await window.firebaseGet(userPlantsRef);
    const plants = plantsSnapshot.val();
    
    let totalRequired = 0;
    if (plants) {
        Object.values(plants).forEach(plant => {
            totalRequired += parseFloat(plant.totalFertilizerNeeded || 0);
        });
    }
    
    const balance = (availableFertilizer - totalRequired).toFixed(2);
    
    document.getElementById('availableFertilizer').textContent = availableFertilizer + ' kg';
    document.getElementById('totalRequired').textContent = totalRequired.toFixed(2) + ' kg';
    document.getElementById('fertilizerBalance').textContent = balance + ' kg';
    
    // Color code the balance
    const balanceEl = document.getElementById('fertilizerBalance');
    if (balance >= 0) {
        balanceEl.style.background = 'linear-gradient(135deg, #22c55e, #16a34a)';
        balanceEl.style.webkitBackgroundClip = 'text';
        balanceEl.style.webkitTextFillColor = 'transparent';
    } else {
        balanceEl.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
        balanceEl.style.webkitBackgroundClip = 'text';
        balanceEl.style.webkitTextFillColor = 'transparent';
    }
}

// Modal alert
function showModalAlert(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    document.getElementById('modalAlertContainer').innerHTML = `
        <div class="alert ${alertClass}">${message}</div>
    `;
    setTimeout(() => {
        document.getElementById('modalAlertContainer').innerHTML = '';
    }, 5000);
}

// Close popup with Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closePlantPopup();
        if (document.getElementById('addPlantModal').classList.contains('active')) {
            closeAddPlantModal();
        }
    }
});
</script>

</body>
</html>