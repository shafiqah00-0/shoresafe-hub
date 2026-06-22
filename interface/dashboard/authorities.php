<?php
session_start();

$role = $_SESSION['role_type'] ?? 'authorities';

// DB connection if needed
require_once __DIR__ . '/../../config/database.php';

$stmt = $pdo->query("SELECT COUNT(*) FROM report");
$total_reports = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM generated_analysis WHERE analysisid>0 AND erosion_risk IS NOT NULL AND erosion_risk != '';");
$total_risk = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM report WHERE authoritiesid IS NULL");
$total_verify = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT r.report_date, l.exact_location, l.state, l.district FROM report r LEFT JOIN location l ON r.locationid = l.locationid ORDER BY r.report_date DESC LIMIT 1");
$new_reports = $stmt->fetch(PDO::FETCH_ASSOC);
$display_date = $new_reports ? date("d M Y", strtotime($new_reports['report_date'])) : 'No date';

$stmt = $pdo->query("SELECT r.report_date, l.exact_location, l.state, l.district FROM report r LEFT JOIN location l ON r.locationid = l.locationid WHERE analysisid IS NULL ORDER BY r.report_date DESC");
$pending_reports = $stmt->fetch(PDO::FETCH_ASSOC);

// UPDATED: Inverted the JOIN condition since r.analysisid points to g.analysisid
$stmt = $pdo->query("
    SELECT 
        g.anaysis_update, 
        l.exact_location, 
        l.state, 
        l.district 
    FROM generated_analysis g
    LEFT JOIN report r ON r.analysisid = g.analysisid
    LEFT JOIN location l ON r.locationid = l.locationid 
    WHERE r.authoritiesid IS NOT NULL 
    ORDER BY g.anaysis_update DESC 
    LIMIT 1
");
$completed_task = $stmt->fetch(PDO::FETCH_ASSOC);

// Uses 'anaysis_update' string timestamp to formulate live audit log date
$completed_date = ($completed_task && !empty($completed_task['anaysis_update'])) 
    ? date("d M Y", strtotime($completed_task['anaysis_update'])) 
    : 'No date';
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
         <span>🌊 ShoreSafe</span>
    </div>

    <nav>
        <a href="/interface/dashboard/authorities.php">
            <i class="fas fa-home"></i> <span>Overview</span>
        </a>
        <a href="/logic/controller/managereport.php">
            <i class="fas fa-chart-pie"></i> <span>Report Management</span>
        </a>
        <a href="/logic/controller/coastalanalysis.php">
            <i class="fas fa-file-alt"></i> <span>Coastal Analysis</span>
        </a>
        <a href="/interface/report.html" style="display: flex; align-items: center; gap: 12px; padding: 0.8rem 1rem; color: #a0aec0; border-radius: 8px; text-decoration: none; font-weight: 500; font-size: 0.95rem; transition: all 0.2s;" onmouseover="this.style.color='#fff'; this.style.background='rgba(255,255,255,0.04)';" onmouseout="this.style.color='#a0aec0'; this.style.background='transparent';">
            <i class="fas fa-plus-circle" style="width: 20px; color: #3182ce;"></i> <span>Submit New Report</span>
        </a>
        <a href="/logic/controller/logout.php" style="display: flex; align-items: center; gap: 12px; padding: 0.8rem 1rem; color: #fc8181; border-radius: 8px; text-decoration: none; font-weight: 500; font-size: 0.95rem; transition: all 0.2s;" onmouseover="this.style.color='#fff'; this.style.background='rgba(229, 62, 62, 0.2)';" onmouseout="this.style.color='#fc8181'; this.style.background='transparent';">
            <i class="fas fa-sign-out-alt" style="width: 20px;"></i> <span>Logout</span>
        </a>
    </nav>
</aside>

<main class="main-content" id="main-content">
    <header>
        <div>
            <h1>Authority Overview</h1>
            <p style="color:#718096;">Coastal monitoring system status and analytics summary.</p>
        </div>
        <div class="user-badge">
            <i class="fas fa-user-shield"></i>
            <?= htmlspecialchars(ucfirst($role)) ?>
        </div>
    </header>

    <div class="analytics-filter-ribbon" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 24px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; gap: 30px;">
        <div style="display: flex; gap: 32px; align-items: center;">
            <div class="ribbon-metric active" onclick="updateRibbonContext('all', this)" style="cursor:pointer;">
                <span class="ribbon-dot" style="background: #3b82f6; display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:6px;"></span>
                <span class="ribbon-label">All Reports: <strong><?= $total_reports ?></strong></span>
            </div>
            <div class="ribbon-metric" onclick="updateRibbonContext('high', this)" style="cursor:pointer;">
                <span class="ribbon-dot" style="background: #ef4444; display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:6px;"></span>
                <span class="ribbon-label">Hotspots: <strong class="text-danger"><?= $total_risk ?></strong></span>
            </div>
            <div class="ribbon-metric" onclick="updateRibbonContext('pending', this)" style="cursor:pointer;">
                <span class="ribbon-dot" style="background: #f59e0b; display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:6px;"></span>
                <span class="ribbon-label">Pending: <strong class="text-warning"><?= $total_verify ?></strong></span>
            </div>
        </div>

        <div style="flex-grow: 1; max-width: 400px; display: flex; flex-direction: column; gap: 6px;">
            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: #64748b; font-weight: 600;">
                <span>Overall Report Progress</span>
                <span><?= $total_reports > 0 ? round((($total_reports - $total_verify) / $total_reports) * 100) : 0 ?>% Actioned</span>
            </div>
            <div style="display: flex; height: 8px; border-radius: 99px; overflow: hidden; background: #f1f5f9; width: 100%;">
                <?php 
                    $verified_pct = $total_reports > 0 ? (($total_reports - $total_verify) / $total_reports) * 100 : 0;
                    $pending_pct = $total_reports > 0 ? ($total_verify / $total_reports) * 100 : 0;
                ?>
                <div style="width: <?= $verified_pct ?>%; background: #3b82f6;" title="Verified Tasks"></div>
                <div style="width: <?= $pending_pct ?>%; background: #f59e0b;" title="Pending Tasks"></div>
            </div>
        </div>
    </div>

    <div class="section-title" style="margin: 2rem 0 1rem 0; font-weight: bold; color: #4a5568;">
        Live Report Activity Tracking 
    </div>

    <div class="audit-stream-container" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; padding:24px; box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
        <div class="stream-timeline" style="display:flex; flex-direction:column; gap:20px; position:relative; padding-left: 20px; border-left: 2px dashed #e2e8f0;">
            
            <?php if ($completed_task): ?>
                <div class="stream-item" style="position:relative;">
                    <span style="position:absolute; left:-27px; top:2px; background:#eff6ff; color:#3b82f6; width:12px; height:12px; border-radius:50%; border:3px solid #ffffff; box-shadow:0 0 0 2px #3b82f6;"></span>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                        <strong style="font-size:0.9rem; color:#0f172a;"><i class="fas fa-check-circle" style="color: #3b82f6; margin-right: 4px;"></i> Assessment Task Completed</strong>
                        <span style="font-size:0.75rem; color:#94a3b8;"><?= htmlspecialchars($completed_date) ?></span>
                    </div>
                    <p style="margin:0; font-size:0.85rem; color:#64748b;">
                        The verification review process for <strong><?= htmlspecialchars($completed_task['exact_location']) ?></strong> has been finalized by system authorities.
                    </p>
                </div>
            <?php endif; ?>

            <?php if ($new_reports): ?>
                <div class="stream-item" style="position:relative;">
                    <span style="position:absolute; left:-27px; top:2px; background:#ecfdf5; color:#10b981; width:12px; height:12px; border-radius:50%; border:3px solid #ffffff; box-shadow:0 0 0 2px #10b981;"></span>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                        <strong style="font-size:0.9rem; color:#0f172a;">Latest Environmental Report Submitted</strong>
                        <span style="font-size:0.75rem; color:#94a3b8;"><?= htmlspecialchars($display_date) ?></span>
                    </div>
                    <p style="margin:0; font-size:0.85rem; color:#64748b;">
                        A new assessment has been logged for <strong><?= htmlspecialchars($new_reports['exact_location']) ?></strong> (<?= htmlspecialchars($new_reports['district']) ?>, <?= htmlspecialchars($new_reports['state']) ?>).
                    </p>
                </div>
            <?php else: ?>
                <p style="font-size:0.85rem; color:#64748b;">No recent reports found.</p>
            <?php endif; ?>

            <?php if ($pending_reports): ?>
                <div class="stream-item" style="position:relative;">
                    <span style="position:absolute; left:-27px; top:2px; background:#fffbf2; color:#f59e0b; width:12px; height:12px; border-radius:50%; border:3px solid #ffffff; box-shadow:0 0 0 2px #f59e0b;"></span>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                        <strong style="font-size:0.9rem; color:#0f172a;">Pending Assessment Required</strong>
                    </div>
                    <p style="margin:0; font-size:0.85rem; color:#64748b;">
                        <strong><?= htmlspecialchars($pending_reports['exact_location']) ?></strong> (<?= htmlspecialchars($pending_reports['district']) ?>) status is currently <span style="color:#f59e0b; font-weight:700;">AWAITING EVALUATION</span>.
                    </p>
                </div>
            <?php else: ?>
                <div class="stream-item" style="position:relative;">
                    <span style="position:absolute; left:-27px; top:2px; background:#f0fdf4; color:#16a34a; width:12px; height:12px; border-radius:50%; border:3px solid #ffffff; box-shadow:0 0 0 2px #16a34a;"></span>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                        <strong style="font-size:0.9rem; color:#0f172a;">All Assessments Cleared</strong>
                        <span style="font-size:0.75rem; color:#94a3b8;">Just Now</span>
                    </div>
                    <p style="margin:0; font-size:0.85rem; color:#64748b;">No beaches are currently awaiting risk evaluation overrides.</p>
                </div>
            <?php endif; ?>
            
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

function updateRibbonContext(mode, element) {
    document.querySelectorAll('.ribbon-metric').forEach(el => el.classList.remove('active'));
    element.classList.add('active');
    console.log("Filtered context modified to: " + mode);
}
</script>

</body>
</html>