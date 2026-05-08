<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['citizen_logged_in']) || $_SESSION['citizen_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $survey_number = trim($_POST['survey_number']);
    $village = trim($_POST['village']);
    $area_type = $_POST['area_type'];
    $asking_price = $_POST['asking_price'];
    $description = trim($_POST['description']);
    $address_details = trim($_POST['address_details']);
    $total_area = trim($_POST['total_area']);
    $seller_id = $_SESSION['citizen_id'];
    $lat = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? $_POST['latitude'] : null;
    $lng = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? $_POST['longitude'] : null;
    
    // File upload logic for PDF
    $pdf_path = null;
    if (isset($_FILES['layout_pdf']) && $_FILES['layout_pdf']['error'] == 0) {
        $allowed = ['pdf'];
        $filename = $_FILES['layout_pdf']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $dest = 'uploads/layouts/' . uniqid('layout_') . '.pdf';
            if (move_uploaded_file($_FILES['layout_pdf']['tmp_name'], $dest)) $pdf_path = $dest;
            else $message = "Error uploading layout PDF. ";
        } else {
            $message = "Invalid PDF file. ";
        }
    }

    // File upload logic for Image
    $img_path = null;
    if (isset($_FILES['property_image']) && $_FILES['property_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['property_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $dest = 'uploads/images/' . uniqid('img_') . '.' . $ext;
            if (move_uploaded_file($_FILES['property_image']['tmp_name'], $dest)) $img_path = $dest;
            else $message .= "Error uploading property image. ";
        } else {
            $message .= "Invalid image format. ";
        }
    }

    if (empty($message)) {
        if ($lat === null || $lng === null) {
            $message = "<div class='error-msg'>Please use 'Locate on Map' or drop a pin to set the exact location.</div>";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO property_listings (seller_id, survey_number, village, address_details, total_area, area_type, asking_price, description, layout_pdf_path, image_path, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$seller_id, $survey_number, $village, $address_details, $total_area, $area_type, $asking_price, $description, $pdf_path, $img_path, $lat, $lng]);
                $message = "<div class='success-msg'>Property listed successfully! Buyers can now view it in the Marketplace.</div>";
            } catch (PDOException $e) {
                $message = "<div class='error-msg'>Database Error: " . $e->getMessage() . "</div>";
            }
        }
    } else {
        $message = "<div class='error-msg'>" . $message . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sell Property - e-Bhumi Marketplace</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<!-- LEAFLET CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
    :root {
        --saffron: #E8621A;
        --saffron-light: #F5894A;
        --navy: #0F1F3D;
        --cream: #FAF7F2;
        --border: #E2DAD0;
        --white: #FFFFFF;
        --green: #1A7A3C;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'DM Sans', sans-serif; background: var(--cream); color: var(--navy); }
    
    /* NAV */
    nav{display:flex;align-items:center;justify-content:space-between;padding:1rem 2.5rem;background:rgba(255,255,255,.92);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;}
    .nav-brand{font-size:1.1rem;color:var(--navy);font-weight:700;text-decoration:none;}
    .nav-links{display:flex;gap:1.5rem;align-items:center;}
    .nav-links a{font-size:.875rem;color:var(--navy);text-decoration:none;font-weight:500;}
    
    .container { max-width: 800px; margin: 3rem auto; padding: 2rem; background: var(--white); border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .page-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem; text-align: center; }
    
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
    
    .form-group { margin-bottom: 1.25rem; }
    .form-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--navy); }
    .form-group input, .form-group select, .form-group textarea {
        width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 0.9rem;
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
        border-color: var(--saffron); outline: none; box-shadow: 0 0 0 3px rgba(232,98,26,0.1);
    }
    .submit-btn { width: 100%; padding: 14px; background: var(--saffron); color: var(--white); border: none; border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: 0.3s; margin-top: 1rem; }
    .submit-btn:hover { background: var(--saffron-light); }
    
    .success-msg { background: #E8F5EE; color: var(--green); padding: 12px; border-radius: 8px; margin-bottom: 1.5rem; text-align: center; font-weight: 500; border: 1px solid var(--green); }
    .error-msg { background: #FEF0EE; color: #C0321A; padding: 12px; border-radius: 8px; margin-bottom: 1.5rem; text-align: center; font-weight: 500; border: 1px solid #C0321A; }
    
    #picker-map { height: 300px; width: 100%; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 0.5rem; }
</style>
</head>
<body>

<nav>
  <a href="index.php" class="nav-brand">e-Bhumi Marketplace</a>
  <div class="nav-links">
    <a href="marketplace.php">Browse Properties</a>
    <a href="user_dashboard.php">My Dashboard</a>
    <a href="logout.php" style="color: #C0321A;">Logout</a>
  </div>
</nav>

<div class="container">
    <h1 class="page-title">List Your Property for Sale</h1>
    <?php echo $message; ?>
    
    <form action="sell_property.php" method="POST" enctype="multipart/form-data">
        <div class="grid-2">
            <div>
                <div class="form-group">
                    <label>Survey Number</label>
                    <input type="text" name="survey_number" required placeholder="e.g. SRV-102">
                </div>
                <div class="form-group">
                    <label>Village Name</label>
                    <input type="text" name="village" id="inp-village" required placeholder="e.g. Shirol">
                </div>
                <div class="form-group">
                    <label>Detailed Address & Landmarks</label>
                    <textarea name="address_details" id="inp-address" rows="2" placeholder="e.g. Near the old banyan tree, 2km from highway..."></textarea>
                </div>
                <div class="form-group">
                    <label>Total Area</label>
                    <input type="text" name="total_area" required placeholder="e.g. 2.5 Acres or 10000 Sq.Ft">
                </div>
                <div class="form-group">
                    <label>Area Type (Location Value)</label>
                    <select name="area_type" required>
                        <option value="Highway Touch">Highway Touch (High Value)</option>
                        <option value="Roadside">Roadside (Medium Value)</option>
                        <option value="Village Interior">Village Interior (Standard)</option>
                        <option value="Agricultural">Agricultural Zone</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Asking Price (₹)</label>
                    <input type="number" name="asking_price" required min="1000" placeholder="e.g. 5000000">
                </div>
                <div class="form-group">
                    <label>Property Description</label>
                    <textarea name="description" rows="3" placeholder="Describe the land, nearby amenities, water availability, etc."></textarea>
                </div>
                <div class="form-group">
                    <label>Upload Property Photo (JPG, PNG)</label>
                    <input type="file" name="property_image" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Upload Land Layout / Floor Plan (PDF ONLY)</label>
                    <input type="file" name="layout_pdf" accept="application/pdf">
                </div>
            </div>
            
            <div>
                <div class="form-group">
                    <label>Locate Your Property</label>
                    <p style="font-size:0.75rem; color:#8C8278; margin-bottom:5px;">Type your village and address on the left, then click Auto-Locate. You can also drag the pin manually.</p>
                    <button type="button" onclick="autoLocate()" style="margin-bottom: 10px; padding: 8px 12px; background: #0F1F3D; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600;">Auto-Locate on Map</button>
                    <div id="picker-map"></div>
                    <!-- Hidden inputs to store coordinates -->
                    <input type="hidden" name="latitude" id="lat-input">
                    <input type="hidden" name="longitude" id="lng-input">
                    <p id="coord-display" style="font-size:0.8rem; color:var(--green); font-weight:600; margin-top:5px;"></p>
                </div>
            </div>
        </div>
        
        <button type="submit" class="submit-btn">Publish Listing to Marketplace</button>
    </form>
</div>

<script>
    // Initialize Map centered on Maharashtra
    const map = L.map('picker-map').setView([19.7515, 75.7139], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    let marker = null;
    const latInput = document.getElementById('lat-input');
    const lngInput = document.getElementById('lng-input');
    const coordDisplay = document.getElementById('coord-display');

    function setPin(lat, lng) {
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], {draggable: true}).addTo(map);
            marker.on('dragend', function(e) {
                const pos = marker.getLatLng();
                latInput.value = pos.lat;
                lngInput.value = pos.lng;
                coordDisplay.textContent = `Location Set: ${pos.lat.toFixed(4)}, ${pos.lng.toFixed(4)}`;
            });
        }
        latInput.value = lat;
        lngInput.value = lng;
        coordDisplay.textContent = `Location Set: ${lat.toFixed(4)}, ${lng.toFixed(4)}`;
        map.setView([lat, lng], 14, {animate: true});
    }

    map.on('click', function(e) {
        setPin(e.latlng.lat, e.latlng.lng);
    });

    async function autoLocate() {
        const village = document.getElementById('inp-village').value.trim();
        const address = document.getElementById('inp-address').value.trim();
        
        if (!village) {
            alert("Please enter a Village name first!");
            return;
        }

        const btn = document.querySelector('button[onclick="autoLocate()"]');
        btn.textContent = "Locating...";
        
        const query = `${address} ${village} Maharashtra India`.trim();
        
        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`);
            const data = await res.json();
            
            if (data && data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lng = parseFloat(data[0].lon);
                setPin(lat, lng);
            } else {
                // Fallback to just village if specific address fails
                const resFallback = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(village + " Maharashtra India")}`);
                const dataFallback = await resFallback.json();
                if (dataFallback && dataFallback.length > 0) {
                    const lat = parseFloat(dataFallback[0].lat);
                    const lng = parseFloat(dataFallback[0].lon);
                    setPin(lat, lng);
                    alert("Could not find the exact street. Placed pin in the village center. Please drag the pin to your exact land.");
                } else {
                    alert("Could not locate on map automatically. Please click on the map to drop the pin manually.");
                }
            }
        } catch (e) {
            alert("Error connecting to location service.");
        }
        btn.textContent = "Auto-Locate on Map";
    }
</script>

</body>
</html>
