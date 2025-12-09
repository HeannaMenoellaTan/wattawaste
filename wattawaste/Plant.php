<?php
session_start();
include('db.php'); // important for $conn, so topnav.php can use it

$apiToken = 'usr-PAxkHSBd0vJNQXNK4hM8Ig8adcaIUmwy-74hO6EMyyw';
$url = "https://trefle.io/api/v1/plants?token=$apiToken&limit=6";

// Fetch data from Trefle API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$plantData = json_decode($response, true);
$plants = [];

if (isset($plantData['data'])) {
    foreach ($plantData['data'] as $plant) {
        $plants[] = [
            'name' => $plant['common_name'] ?? $plant['scientific_name'],
            'scientific_name' => $plant['scientific_name'] ?? 'N/A',
            'image' => $plant['image_url'] ?? 'https://via.placeholder.com/150',
            'growth_days' => $plant['growth']['duration'] ?? 'N/A',
            'sunlight' => $plant['growth']['light'] ?? 'N/A'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Plant Summary</title>
<?php  
include_once 'notif_bell.php';
?>
<style>
body { font-family: Arial, sans-serif; background: #f4f7f6; color: #2f5233; padding: 20px; }
h2 { text-align: center; margin-bottom: 30px; }
.plant-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; }
.plant-card { background: #fff; border-radius: 12px; padding: 15px; text-align: center; box-shadow: 0 3px 8px rgba(0,0,0,0.15); cursor:pointer; }
.plant-card img { width: 80px; height: 80px; object-fit: cover; border-radius: 10px; }
.plant-card h4 { font-size: 18px; margin: 10px 0 5px; }
.plant-card p { font-size: 14px; color: #555; }
.popup { display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; padding:25px; border-radius:15px; box-shadow:0 5px 15px rgba(0,0,0,0.3); text-align:center; max-width:300px; z-index:1000; }
.popup img { width:120px; height:120px; object-fit:cover; border-radius:10px; margin-bottom:10px; }
.popup .close { position:absolute; top:10px; right:15px; font-size:25px; cursor:pointer; }
</style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main">
<?php include 'topnav.php';?>
<h2>🌿 Plant Summary</h2>
<div class="plant-grid">
    <?php foreach($plants as $i => $plant): ?>
        <div class="plant-card" onclick="showPlantPopup(<?= $i ?>)">
            <img src="<?= $plant['image'] ?>" alt="<?= htmlspecialchars($plant['name']) ?>">
            <h4><?= htmlspecialchars($plant['name']) ?></h4>
        </div>
    <?php endforeach; ?>
</div>

<div id="plantPopup" class="popup">
    <span class="close" onclick="closePopup()">&times;</span>
    <img id="popupImage" src="" alt="">
    <h4 id="popupName"></h4>
    <p id="popupDescription"></p>
</div>

<script>
const plantData = <?php echo json_encode($plants); ?>;
function showPlantPopup(index) {
    const plant = plantData[index];
    document.getElementById('popupImage').src = plant.image;
    document.getElementById('popupName').innerText = plant.name;
    document.getElementById('popupDescription').innerHTML = `
        Scientific Name: ${plant.scientific_name}<br>
        Growth Days: ${plant.growth_days}<br>
        Sunlight: ${plant.sunlight}
    `;
    document.getElementById('plantPopup').style.display = 'block';
}
function closePopup() {
    document.getElementById('plantPopup').style.display = 'none';
}
</script>

</body>
</html>
