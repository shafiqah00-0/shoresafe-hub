<?php
// session_start();
// Adjust this path to point correctly to your database configuration file
require_once __DIR__ . '/../config/database.php';

try {
    // 1. Count High Risk Locations
    $stmtHigh = $pdo->prepare("SELECT COUNT(*) FROM generated_analysis WHERE erosion_risk = 'High' AND analysisid > 0");
    $stmtHigh->execute();
    $highCount = $stmtHigh->fetchColumn();

    // 2. Count Medium Risk Locations
    $stmtMed = $pdo->prepare("SELECT COUNT(*) FROM generated_analysis WHERE erosion_risk = 'Medium' AND analysisid > 0");
    $stmtMed->execute();
    $mediumCount = $stmtMed->fetchColumn();

    // 3. Count Low Risk Locations
    $stmtLow = $pdo->prepare("SELECT COUNT(*) FROM generated_analysis WHERE erosion_risk = 'Low' AND analysisid > 0");
    $stmtLow->execute();
    $lowCount = $stmtLow->fetchColumn();

} catch (PDOException $e) {
    // Fallback to 0 if something goes wrong with the database connection
    $highCount = $mediumCount = $lowCount = 0;
    error_log("Database Error in mainpage status cards: " . $e->getMessage());
}
// Fetch the 3 latest analysis records joined with their location names
$queryLatest = "
    SELECT l.exact_location,l.state, a.erosion_risk 
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
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<link rel="stylesheet" href="/interface/css/mainpage.css">
</head>

<body>

<!-- HEADER -->
<header>
    <div class="logo">🌊 ShoreSafe</div>
    <nav>
        <a href="/interface/dashboard/public.php">Information</a>
        <a href="/interface/report.html" class="btn-report">🚨 Report</a>
        <a href="/interface/login.html" class="btn-login"> 🔐 Login</a>
    </nav>
</header>

<!-- HERO -->
<section class="hero">
    <div class="hero-content">
        <h1>Coastal Information Hub</h1>
        <p>
            One stop centre for real-time information about coastal areas
        </p>
    </div>

    <!-- WAVES -->
    <div class="wave wave1"></div>
    <div class="wave wave2"></div>
    <div class="wave wave3"></div>
</section>

<!-- STATUS SECTION (LIKE NADMA DASHBOARD) -->
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

   <!-- MAP SECTION -->
<section class="map-section">
    <h2>Coastal Risk Map</h2>
    <div id="map"></div>
</section>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
    // 1. Initialize map view centered broadly on Malaysia's coastlines
    var map = L.map('map').setView([2.5000, 102.2500], 8);

    // 2. Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // 3. Import the database JSON object directly from your PHP script layer
    var locationData = <?= $jsonLocations; ?>;

    // 4. Loop through each record and pin it onto your Leaflet layer canvas
   // 4. Loop through each record and pin it onto your Leaflet layer canvas
locationData.forEach(function(place) {
    // Validate that coordinates exist before plotting markers
    if (place.latitude && place.longitude) {
        
        // Determine pin color based on erosion severity threat rating
        var badgeColor = '#2ecc71'; // Low Risk (Green)
        if (place.erosion_risk && place.erosion_risk.toLowerCase() === 'high') {
            badgeColor = '#ff4d4d'; // High Risk (Red)
        } else if (place.erosion_risk && place.erosion_risk.toLowerCase() === 'medium') {
            badgeColor = '#ffb300'; // Medium Risk (Yellow)
        }

        // Create a custom popup content block
        var popupContent = `
            <div style="font-family: sans-serif; padding: 2px;">
                <h4 style="margin: 0 0 5px 0; color: #333;">${place.exact_location}</h4>
                <p style="margin: 0; font-size: 0.9rem;">
                    Status: <strong style="color: ${badgeColor};">${place.erosion_risk || 'Not Evaluated'}</strong>
                </p>
                <small style="color: #777;">Lat: ${place.latitude}, Long: ${place.longitude}</small>
            </div>
        `;

        // FIX: Use circleMarker instead of regular marker to apply dynamic colors instantly
        L.circleMarker([parseFloat(place.latitude), parseFloat(place.longitude)], {
            radius: 8,          // Adjust the size of your map dot
            fillColor: badgeColor,
            color: '#ffffff',   // White border around the circle
            weight: 2,          // Border thickness
            opacity: 1,
            fillOpacity: 0.9    // Opacity of the circle fill color
        })
        .addTo(map)
        .bindPopup(popupContent);
    }
});
</script>
<!-- INFORMATION -->
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
                    // Dynamically adjust description based on database risk text
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
<!-- CALL TO ACTION -->
<section class="cta">
    <h2>See Something? Report It Now</h2>
    <a href="/interface/report.html" class="btn-big">🚨 Submit Report</a>
</section>

<!-- ABOUT -->
<section class="about">
    <h2>About Coastal Erosion</h2>
    <p>
        Coastal erosion is caused by wave action, rising sea levels, and human activities.
        This system helps monitor and report incidents to improve response time and safety.
    </p>
</section>


<!-- FOOTER -->
<footer>
    <p>© 2026 ShoreSafe | Coastal Erosion Management System</p>
</footer>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</body>
</html>