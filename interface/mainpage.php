<?php
// session_start();
// Adjust this path to point correctly to your database configuration file
require_once __DIR__ . '/../config/database.php';

try {
    // 1. Count High Risk Locations
    $stmtHigh = $pdo->prepare("SELECT COUNT(*) FROM generated_analysis WHERE erosion_risk = 'High'");
    $stmtHigh->execute();
    $highCount = $stmtHigh->fetchColumn();

    // 2. Count Medium Risk Locations
    $stmtMed = $pdo->prepare("SELECT COUNT(*) FROM generated_analysis WHERE erosion_risk = 'Medium'");
    $stmtMed->execute();
    $mediumCount = $stmtMed->fetchColumn();

    // 3. Count Low Risk Locations
    $stmtLow = $pdo->prepare("SELECT COUNT(*) FROM generated_analysis WHERE erosion_risk = 'Low'");
    $stmtLow->execute();
    $lowCount = $stmtLow->fetchColumn();

} catch (PDOException $e) {
    // Fallback to 0 if something goes wrong with the database connection
    $highCount = $mediumCount = $lowCount = 0;
    error_log("Database Error in mainpage status cards: " . $e->getMessage());
}

// Fetch the 3 latest analysis records joined with their location names
$queryLatest = "
    SELECT l.exact_location, l.state, a.erosion_risk 
    FROM generated_analysis a
    JOIN location l ON a.locationid = l.locationid
    ORDER BY a.analysisid DESC 
    LIMIT 3
";
$stmtLatest = $pdo->prepare($queryLatest);
$stmtLatest->execute();
$latestUpdates = $stmtLatest->fetchAll(PDO::FETCH_ASSOC);

// Fetch all coordinates, location names, and risk ratings
$queryMap = "
    SELECT l.exact_location, l.latitude, l.longitude, a.erosion_risk 
    FROM location l
    LEFT JOIN generated_analysis a ON l.locationid = a.locationid
    WHERE l.latitude IS NOT NULL AND l.longitude IS NOT NULL
";
$stmtMap = $pdo->prepare($queryMap);
$stmtMap->execute();
$mapLocations = $stmtMap->fetchAll(PDO::FETCH_ASSOC);

// Safe encode data to instantly inject into JavaScript
$jsonLocations = json_encode($mapLocations);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ShoreSafe - Coastal Erosion Management System</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<link rel="stylesheet" href="/interface/css/mainpage.css">

<style>
    /* FLOATING GUIDELINES STYLES */
    .guidelines-fab {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        background-color: #0077b6;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        z-index: 9999;
        transition: transform 0.2s ease, background-color 0.2s;
        /* Gentle idle pulse animation */
        animation: subtlePulse 2s infinite;
    }

    .guidelines-fab:hover {
        background-color: #0096c7;
        transform: scale(1.05);
        animation: none; /* Stop pulsing on hover */
    }

    @keyframes subtlePulse {
        0% { box-shadow: 0 0 0 0 rgba(0, 119, 182, 0.5); }
        70% { box-shadow: 0 0 0 15px rgba(0, 119, 182, 0); }
        100% { box-shadow: 0 0 0 0 rgba(0, 119, 182, 0); }
    }

    /* SIDE DRAWER FOR GUIDELINES */
    .guidelines-drawer {
        position: fixed;
        top: 0;
        right: -350px; /* Hidden off-screen by default */
        width: 320px;
        height: 100%;
        background-color: #ffffff;
        box-shadow: -5px 0 15px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        transition: right 0.3s ease-in-out;
        padding: 30px 25px;
        box-sizing: border-box;
        overflow-y: auto;
        font-family: sans-serif;
    }

    .guidelines-drawer.open {
        right: 0;
    }

    .guidelines-drawer h3 {
        margin-top: 0;
        color: #0077b6;
        font-size: 1.4rem;
        border-bottom: 2px solid #e0e0e0;
        padding-bottom: 10px;
    }

    .guidelines-drawer .close-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        font-size: 28px;
        cursor: pointer;
        color: #888;
        line-height: 1;
    }

    .guidelines-drawer .close-btn:hover {
        color: #333;
    }

    .guideline-item {
        margin-bottom: 20px;
    }

    .guideline-item h4 {
        margin: 10px 0 5px 0;
        color: #333;
    }

    .guideline-item p {
        margin: 0;
        font-size: 0.9rem;
        color: #555;
        line-height: 1.4;
    }

    /* Backdrop to close easily */
    .drawer-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.3);
        z-index: 9998;
        display: none;
    }
    .drawer-overlay.active {
        display: block;
    }
</style>
</head>

<body>

<header>
    <div class="logo">🌊 ShoreSafe</div>
    <nav>
        <a href="/interface/dashboard/public.php">Information</a>
        <a href="/interface/report.html" class="btn-report">🚨 Report</a>
        <a href="/interface/login.html" class="btn-login"> 🔐 Login</a>
    </nav>
</header>

<section class="hero">
    <div class="hero-content">
        <h1>Coastal Information Hub</h1>
        <p>One stop centre for real-time information about coastal areas</p>
    </div>

    <div class="wave wave1"></div>
    <div class="wave wave2"></div>
    <div class="wave wave3"></div>
</section>

<section id="status" class="status">
    <h2>Current Coastal Risk Status</h2>

    <div class="status-cards">
        <div class="status-card high">
            <h3>🔴 High Risk</h3>
            <p><?= htmlspecialchars($highCount); ?> Locations</p>
        </div>

        <div class="status-card medium">
            <h3>🟡 Medium Risk</h3>
            <p><?= htmlspecialchars($mediumCount); ?> Locations</p>
        </div>

        <div class="status-card low">
            <h3>🟢 Low Risk</h3>
            <p><?= htmlspecialchars($lowCount); ?> Locations</p>
        </div>
    </div>
</section>

<section class="map-section">
    <h2>Coastal Risk Map</h2>
    <div id="map"></div>
</section>

<section id="info" class="info">
    <h2>Latest Updates</h2>

    <div class="info-box">
        <?php if (!empty($latestUpdates)): ?>
            <?php foreach ($latestUpdates as $update): ?>
                <p>
                    <strong>
                        <?= htmlspecialchars($update['exact_location']); ?>, 
                        <?= htmlspecialchars($update['state']); ?>:
                    </strong> 
                    
                    <?php 
                        if (strtolower($update['erosion_risk']) === 'high') {
                            echo '<span style="color: #ff4d4d; font-weight: bold;">Severe shoreline retreat observed</span>';
                        } elseif (strtolower($update['erosion_risk']) === 'medium') {
                            echo '<span style="color: #ffb300; font-weight: bold;">Continuous monitoring in progress</span>';
                        } else {
                            echo '<span style="color: #2ecc71;">Minor erosion detected</span>';
                        }
                    ?>
                </p>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No recent analysis logs recorded.</p>
        <?php endif; ?>
    </div>
</section>

<section class="cta">
    <h2>See Something? Report It Now</h2>
    <a href="/interface/report.html" class="btn-big">🚨 Submit Report</a>
</section>

<section class="about">
    <h2>About Coastal Erosion</h2>
    <p>
        Coastal erosion is caused by wave action, rising sea levels, and human activities.
        This system helps monitor and report incidents to improve response time and safety.
    </p>
</section>

<footer>
    <p>© 2026 ShoreSafe | Coastal Erosion Management System</p>
</footer>

<div class="guidelines-fab" id="guidelinesBtn" title="View Guidelines">ℹ️</div>

<div class="drawer-overlay" id="drawerOverlay"></div>

<div class="guidelines-drawer" id="guidelinesDrawer">
    <span class="close-btn" id="closeDrawerBtn">&times;</span>
    <h2>Hello, fellas! </h2>
    <h3>Short Guidelines for this website.</h3>
    
    <div class="guideline-item" >
        <h4>✨ New User? Register Here!</h4>
        <p>Don't have an account yet? Click on the <strong>🔐 Login</strong> button, then click the <strong>Register Account</strong> link inside to create your account.</p>
    </div>

    <div class="guideline-item" >
        <h4>🔐 Account Login</h4>
        <p>Enter your credentials to access to the secure system pages.</p>
    </div>

    <div class="guideline-item">
        <h4>📋 Information</h4>
        <p>Click the <strong>Information</strong> word to view a summary of coastal statuses instantly!</p>
    </div>
    
    <div class="guideline-item">
        <h4>📢 Reporting Incidents</h4>
        <p>See coastal or beach erosion? Click <strong>🚨Report</strong> to submit details. You can click directly on our interactive map or type out the specific location name.</p>
    </div>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
    // Initialize map view centered broadly on Malaysia's coastlines
    var map = L.map('map').setView([4.5000, 102.2500], 7);

    // Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Import the database JSON object directly from your PHP script layer
    var locationData = <?= $jsonLocations; ?>;

    // Loop through each record and pin it onto your Leaflet layer canvas
    locationData.forEach(function(place) {
        if (place.latitude && place.longitude) {
            var badgeColor = '#2ecc71'; // Low Risk (Green)
            if (place.erosion_risk && place.erosion_risk.toLowerCase() === 'high') {
                badgeColor = '#ff4d4d'; // High Risk (Red)
            } else if (place.erosion_risk && place.erosion_risk.toLowerCase() === 'medium') {
                badgeColor = '#ffb300'; // Medium Risk (Yellow)
            }

            var popupContent = `
                <div style="font-family: sans-serif; padding: 2px;">
                    <h4 style="margin: 0 0 5px 0; color: #333;">${place.exact_location}</h4>
                    <p style="margin: 0; font-size: 0.9rem;">
                        Status: <strong style="color: ${badgeColor};">${place.erosion_risk || 'Not Evaluated'}</strong>
                    </p>
                    <small style="color: #777;">Lat: ${place.latitude}, Long: ${place.longitude}</small>
                </div>
            `;

            L.circleMarker([parseFloat(place.latitude), parseFloat(place.longitude)], {
                radius: 8,
                fillColor: badgeColor,
                color: '#ffffff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.9
            })
            .addTo(map)
            .bindPopup(popupContent);
        }
    });

    // GUIDELINES DRAWER INTERACTION JAVASCRIPT
    const guidelinesBtn = document.getElementById('guidelinesBtn');
    const guidelinesDrawer = document.getElementById('guidelinesDrawer');
    const drawerOverlay = document.getElementById('drawerOverlay');
    const closeDrawerBtn = document.getElementById('closeDrawerBtn');

    function openDrawer() {
        guidelinesDrawer.classList.add('open');
        drawerOverlay.classList.add('active');
    }

    function closeDrawer() {
        guidelinesDrawer.classList.remove('open');
        drawerOverlay.classList.remove('active');
    }

    guidelinesBtn.addEventListener('click', openDrawer);
    closeDrawerBtn.addEventListener('click', closeDrawer);
    drawerOverlay.addEventListener('click', closeDrawer);
</script>
</body>
</html>