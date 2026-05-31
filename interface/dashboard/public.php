<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. FIXED: Moved DB connection to the absolute top so $pdo is available for the alert query!
require_once __DIR__ . '/../../config/database.php';

$showPublicAlert = false;
$alertLocationData = null;

if (!isset($_SESSION['public_warning_dismissed'])) {
    try {
        // Look for high-risk data from the last 14 days
        $queryAlert = "
            SELECT l.exact_location, l.state, a.erosion_risk, a.analysisid
            FROM public.generated_analysis a
            JOIN public.location l ON a.locationid = l.locationid
            WHERE LOWER(a.erosion_risk) = 'high'
              AND a.analysis_update >= CURRENT_DATE - INTERVAL '14 days'
            ORDER BY a.analysisid DESC 
            LIMIT 1
        ";
        $stmtAlert = $pdo->prepare($queryAlert);
        $stmtAlert->execute();
        $alertLocationData = $stmtAlert->fetch(PDO::FETCH_ASSOC);

        if ($alertLocationData) { $showPublicAlert = true; }
    } catch (PDOException $e) {
        error_log("Guest Dashboard Alert Error: " . $e->getMessage());
    }
}

$role = $_SESSION['role_type'] ?? 'guest';
$username = $_SESSION['username'] ?? 'Guest';

// Fetch total counts for stat cards
$stmt = $pdo->query("SELECT COUNT(*) FROM report");
$total_reports = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Guest Dashboard - ShoreSafe</title>
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
            <i class="fas fa-water"></i> <span>ShoreSafe</span> 
        </div>

        <nav>
            <a href="index.php?page=dashboard" class="active">
                <i class="fas fa-home"></i> <span>Overview</span>
            </a>
            
            <a href="/interface/dashboard/publicreport.php">
                <i class="fas fa-chart-pie"></i> <span>Report Statistics </span>
            </a>

            <a href="/interface/report.html">
                <i class="fas fa-file-alt"></i> <span>Reports</span>
            </a>
            

            <a href="/logic/controller/logout.php" style="margin-top: auto;">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- Added ID "main-content" here so the JS works -->
    <main class="main-content" id="main-content">
        <header>
            <div>
                <h1>Welcome, <?= htmlspecialchars($username) ?></h1>
                <p style="color: #718096;">Here is what's happening with the coastline today.</p>
            </div>
            <div class="user-badge">
                <i class="fas fa-user-circle"></i> <?= ucfirst($role) ?>
            </div>
        </header>

        <div class="stats-grid">
            <!-- Common Stat -->
            <div class="stat-card">
                <div class="stat-number"><?= $total_reports ?></div>
                <p>Total Incidents Reported</p>
            </div>
            
          
        </div>  

        <div class="section-title" style="margin-bottom: 1.5rem; font-weight: bold; color: #4a5568;">Quick Actions</div>
        
    <?php if ($showPublicAlert && $alertLocationData): ?>
<div id="risk-modal" class="warn-notification-overlay">
    <div class="warn-notification-card">
        <button id="dismiss-alert-x" class="zus-close-btn">&times;</button>
        <div class="warn-card-accent-bar"></div>
        <div class="warn-card-content">
            <div class="warn-warning-header">
                <span class="warn-pulse-dot"></span>
                <h2>High-Risk Incident Tracking Advisory</h2>
            </div>
            <div class="warn-modal-body">
                <p class="warn-location-badge">📍 <?= htmlspecialchars($alertLocationData['exact_location']) ?>, <?= htmlspecialchars($alertLocationData['state']) ?></p>
                <p class="warn-warning-text">Analytical metrics processing has flagged critical shoreline displacement changes within this quadrant inside the last 14 days. Monitor tracking data maps below.</p>
            </div>
            <div class="warn-action-footer">
                <a href="#status" class="warn-btn-primary">View Current Risk Status</a>
            </div>
        </div>
    </div>
</div>   
<?php endif; ?>

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
<script>
document.addEventListener("DOMContentLoaded", () => {
    const closeBtn = document.getElementById("dismiss-alert-x");
    const modal = document.getElementById("risk-modal");
    if (closeBtn && modal) {
        closeBtn.addEventListener("click", () => {
            modal.classList.add("warn-fade-out");
            setTimeout(() => { modal.style.display = "none"; }, 250);
            fetch("/interface/dashboard/warningpopup.php", { method: "POST" });
        });
    }
});
</script>
</body>
</html>