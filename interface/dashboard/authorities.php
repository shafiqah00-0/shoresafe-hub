<?php
session_start();

$role = $_SESSION['role_type'] ?? 'authorities';

// DB connection if needed
require_once __DIR__ . '/../../config/database.php';

 $stmt = $pdo->query("SELECT COUNT(*) FROM report");
 $total_reports = $stmt->fetchColumn();
 $stmt = $pdo->query("SELECT COUNT(*) FROM generated_analysis WHERE analysisid>0 AND erosion_risk IS NOT NULL 
  AND erosion_risk != '';");
 $total_risk = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Authority Dashboard - ShoreSafe</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/interface/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

<aside class="sidebar" id="sidebar">
    <button class="toggle-btn" id="toggle-btn">
        <i class="fas fa-chevron-left" id="toggle-icon"></i>
    </button>

    <div class="logo">
         <span> 🌊 ShoreSafe</span>
    </div>

    <nav>
        <a href="index.php?page=dashboard">
            <i class="fas fa-home"></i> <span>Overview</span>
        </a>

        <a href="/logic/controller/managereport.php">
            <i class="fas fa-chart-pie"></i> <span>Report Management</span>
        </a>

        <a href="/logic/controller/coastalanalysis.php">
            <i class="fas fa-file-alt"></i> <span>Coastal Analysis</span>
        </a>

        <a href="/logic/controller/logout.php" style="margin-top: auto;">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </nav>
</aside>

<main class="main-content" id="main-content">

    <!-- HEADER -->
    <header>
        <div>
            <h1>Authority Overview</h1>
            <p style="color:#718096;">
                Coastal monitoring system status and analytics summary.
            </p>
        </div>

        <div class="user-badge">
            <i class="fas fa-user-shield"></i>
            <?= ucfirst($role ?? 'authority') ?>
        </div>
    </header>

    <!-- STATS -->
    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-number"><?= $total_reports ?></div>
            <p>Total Reports Submitted</p>
        </div>

        <div class="stat-card">
             <div class="stat-number"><?= $total_risk ?></div>
            <p>Detected Hotspots</p>
        </div>

        <div class="stat-card" style="border-left: 4px solid #4299e1;">
            <div class="stat-number">7</div>
            <p>Pending Verification</p>
        </div>

    </div>

    <!-- INFORMATION PANEL -->
    <div class="section-title" style="margin-bottom: 1.5rem; font-weight: bold; color: #4a5568;">
        System Insights
    </div>

    <div class="action-grid">

        <div class="action-card">
            <h3><i class="fas fa-info-circle" style="color:#4299e1;"></i> Monitoring Status</h3>
            <p>
                Coastal erosion detection systems are currently active and collecting environmental data from monitored zones.
            </p>
        </div>

        <div class="action-card">
            <h3><i class="fas fa-chart-line" style="color:#48bb78;"></i> Risk Overview</h3>
            <p>
                Moderate erosion activity detected in selected coastal segments. Further analysis recommended.
            </p>
        </div>

        <div class="action-card">
            <h3><i class="fas fa-database" style="color:#805ad5;"></i> Data Integrity</h3>
            <p>
                All incoming reports are synchronized with the central database and validated for consistency.
            </p>
        </div>

    </div>

</main>

<script>
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('main-content');
const toggleBtn = document.getElementById('toggle-btn');
const toggleIcon = document.getElementById('toggle-icon');

toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');

    if (sidebar.classList.contains('collapsed')) {
        toggleIcon.classList.replace('fa-chevron-left', 'fa-chevron-right');
    } else {
        toggleIcon.classList.replace('fa-chevron-right', 'fa-chevron-left');
    }
});
</script>

</body>
</html>