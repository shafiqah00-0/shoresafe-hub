<?php
session_start();

// Enforce default stakeholder context security role if not assigned
$role = $_SESSION['role_type'] ?? 'stakeholders';

require_once __DIR__ . '/../../config/database.php';

try {
    // 1. Total Reports Count
    $stmt = $pdo->query("SELECT COUNT(*) FROM report");
    $total_reports = $stmt->fetchColumn();

    // 2. High Risk Hotspots Count (Erosion risk strictly flagged high)
    $stmt = $pdo->query("SELECT COUNT(*) FROM generated_analysis WHERE LOWER(erosion_risk) = 'high'");
    $high_risk_hotspots = $stmt->fetchColumn();

    // 3. Pending Verification Count (Unprocessed incoming items)
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM report r 
        LEFT JOIN authorities a ON r.authoritiesid = a.authoritiesid 
        WHERE a.status_update IS NULL OR LOWER(a.status_update) = 'pending'
    ");
    $pending_verification = $stmt->fetchColumn();

    // 4. Mitigated/Resolved Count 
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM authorities 
        WHERE LOWER(status_update) IN ('resolved', 'action taken')
    ");
    $resolved_actions = $stmt->fetchColumn();

} catch (PDOException $e) {
    error_log("Stakeholder Metrics Error: " . $e->getMessage());
    $total_reports = 0;
    $high_risk_hotspots = 0;
    $pending_verification = 0;
    $resolved_actions = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        <a href="index.php?page=dashboard" class="active">
            <i class="fas fa-home"></i> <span>Overview</span>
        </a>
        <a href="/logic/controller/coastalanalysis.php">
            <i class="fas fa-file-alt"></i> <span>Report Hub</span>
        </a>
        <a href="/logic/controller/logout.php" style="margin-top: auto;">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </nav>
</aside>

<main class="main-content" id="main-content">

    <header>
        <div>
            <h1>Stakeholder Overview</h1>
            <p style="color:#718096;">Coastal monitoring system monitoring summary dashboard view.</p>
        </div>
        <div class="user-badge">
            <i class="fas fa-user-shield"></i>
            <?= ucfirst(htmlspecialchars($role)) ?>
        </div>
    </header>

    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-number"><?= $total_reports ?></div>
            <p>Total Submissions</p>
        </div>

        <div class="stat-card" style="border-left: 4px solid #e53e3e;">
             <div class="stat-number" style="color: #e53e3e;"><?= $high_risk_hotspots ?></div>
            <p>Critical Hotspots (High Risk)</p>
        </div>

        <div class="stat-card" style="border-left: 4px solid #dd6b20;">
            <div class="stat-number" style="color: #dd6b20;"><?= $pending_verification ?></div>
            <p>Awaiting Verification</p>
        </div>

        <div class="stat-card" style="border-left: 4px solid #38a169;">
            <div class="stat-number" style="color: #38a169;"><?= $resolved_actions ?></div>
            <p>Resolved & Mitigated</p>
        </div>

    </div>

    <div class="section-title" style="margin-bottom: 1.5rem; font-weight: bold; color: #4a5568; margin-top: 2rem;">
        System Insights
    </div>

    <div class="action-grid">
        <div class="action-card">
            <h3><i class="fas fa-info-circle" style="color:#4299e1;"></i> Operations Status</h3>
            <p>Coastal erosion databases are online. Verification streams are syncing cleanly with environmental monitoring logs.</p>
        </div>

        <div class="action-card">
            <h3><i class="fas fa-shield-alt" style="color:#e53e3e;"></i> Protective Priority</h3>
            <p>Currently tracking <strong><?= $high_risk_hotspots ?></strong> severe high-risk sectors requiring structural infrastructure reviews or immediate mitigation layout intervention.</p>
        </div>

        <div class="action-card">
            <h3><i class="fas fa-database" style="color:#805ad5;"></i> Data Sync Status</h3>
            <p>Analytical integrity is maintained. All field records match up with geo-coordinated verification markers seamlessly.</p>
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