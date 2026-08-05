<?php
session_start();

$role = $_SESSION['role_type'] ?? 'authorities';

// DB connection
require_once __DIR__ . '/../../config/database.php';

// --- METRICS & COUNTS ---
$stmt = $pdo->query("SELECT COUNT(*) FROM report");
$total_reports = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM generated_analysis WHERE analysisid > 0 AND erosion_risk IS NOT NULL AND erosion_risk != '';");
$total_risk = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM report WHERE authoritiesid IS NULL");
$total_verify = $stmt->fetchColumn();

// Count locations currently "In Progress" (and not yet resolved)
$stmt = $pdo->query("
    SELECT COUNT(DISTINCT r.locationid) 
    FROM action_authorities a 
    JOIN report r ON a.authoritiesid = r.authoritiesid 
    WHERE LOWER(a.status_update) IN ('in progress', 'action taken')
      AND NOT EXISTS (
          SELECT 1 FROM action_authorities a2 
          JOIN report r2 ON a2.authoritiesid = r2.authoritiesid 
          WHERE r2.locationid = r.locationid AND LOWER(a2.status_update) = 'resolved'
      )
");
$total_in_progress = $stmt->fetchColumn();

// Count locations successfully "Resolved"
$stmt = $pdo->query("
    SELECT COUNT(DISTINCT r.locationid) 
    FROM action_authorities a 
    JOIN report r ON a.authoritiesid = r.authoritiesid 
    WHERE LOWER(a.status_update) = 'resolved'
");
$total_resolved = $stmt->fetchColumn();


// --- RECENT ACTIVITY DATA ---
$stmt = $pdo->query("SELECT r.report_date, l.exact_location, l.state, l.district FROM report r LEFT JOIN location l ON r.locationid = l.locationid ORDER BY r.report_date DESC LIMIT 1");
$new_reports = $stmt->fetch(PDO::FETCH_ASSOC);
$display_date = $new_reports ? date("d M Y", strtotime($new_reports['report_date'])) : 'No date';

$stmt = $pdo->query("SELECT r.report_date, l.exact_location, l.state, l.district FROM report r LEFT JOIN location l ON r.locationid = l.locationid WHERE analysisid IS NULL ORDER BY r.report_date DESC LIMIT 1");
$pending_reports = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch latest "In Progress" location needing priority action
$stmt = $pdo->query("
    SELECT 
        l.exact_location, 
        r.report_date,
        a.update_date AS action_date, 
        (CURRENT_DATE - r.report_date) AS total_days_open
    FROM action_authorities a 
    JOIN report r ON a.authoritiesid = r.authoritiesid 
    JOIN location l ON r.locationid = l.locationid
    WHERE LOWER(a.status_update) IN ('in progress', 'action taken')
      AND NOT EXISTS (
          SELECT 1 
          FROM action_authorities a2
          JOIN report r2 ON a2.authoritiesid = r2.authoritiesid
          WHERE r2.locationid = r.locationid
            AND LOWER(a2.status_update) = 'resolved'
      )
    ORDER BY total_days_open ASC
    LIMIT 1
");
$in_progress_task = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch latest "Resolved" location info
$stmt = $pdo->query("
    SELECT 
        l.exact_location, 
        r.report_date,
        a.update_date AS resolved_date, 
        (a.update_date - r.report_date) AS action_period
    FROM action_authorities a 
    JOIN report r ON a.authoritiesid = r.authoritiesid 
    JOIN location l ON r.locationid = l.locationid
    WHERE LOWER(a.status_update) = 'resolved'
    ORDER BY a.update_date DESC
    LIMIT 1
");
$resolved_task = $stmt->fetch(PDO::FETCH_ASSOC);
$resolved_date = ($resolved_task && !empty($resolved_task['resolved_date'])) 
    ? date("d M Y", strtotime($resolved_task['resolved_date'])) 
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

    <!-- Priority Analytics Filter Ribbon -->
    <div class="analytics-filter-ribbon" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 24px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; gap: 20px;">
        <div style="display: flex; gap: 24px; align-items: center;">
            <div class="ribbon-metric active" onclick="updateRibbonContext('all', this)" style="cursor:pointer;">
                <span class="ribbon-dot" style="background: #64748b; display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:6px;"></span>
                <span class="ribbon-label">All Reports: <strong><?= $total_reports ?></strong></span>
            </div>
            <!-- In Progress (Prioritised) -->
            <div class="ribbon-metric" onclick="updateRibbonContext('in_progress', this)" style="cursor:pointer;">
                <span class="ribbon-dot" style="background: #3b82f6; display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:6px;"></span>
                <span class="ribbon-label">In Progress: <strong style="color:#2563eb;"><?= $total_in_progress ?></strong></span>
            </div>
            <!-- Pending -->
            <div class="ribbon-metric" onclick="updateRibbonContext('pending', this)" style="cursor:pointer;">
                <span class="ribbon-dot" style="background: #f59e0b; display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:6px;"></span>
                <span class="ribbon-label">Pending: <strong style="color:#d97706;"><?= $total_verify ?></strong></span>
            </div>
            <!-- Resolved Locations -->
            <div class="ribbon-metric" onclick="updateRibbonContext('resolved', this)" style="cursor:pointer;">
                <span class="ribbon-dot" style="background: #10b981; display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:6px;"></span>
                <span class="ribbon-label">Resolved Locations: <strong style="color:#059669;"><?= $total_resolved ?></strong></span>
            </div>
        </div>

        <!-- Tiered Progress Bar -->
        <div style="flex-grow: 1; max-width: 360px; display: flex; flex-direction: column; gap: 6px;">
            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: #64748b; font-weight: 600;">
                <span>Resolution Progress</span>
                <span><?= $total_reports > 0 ? round(($total_resolved / $total_reports) * 100) : 0 ?>% Resolved</span>
            </div>
            <div style="display: flex; height: 8px; border-radius: 99px; overflow: hidden; background: #f1f5f9; width: 100%;">
                <?php 
                    $resolved_pct    = $total_reports > 0 ? ($total_resolved / $total_reports) * 100 : 0;
                    $in_progress_pct = $total_reports > 0 ? ($total_in_progress / $total_reports) * 100 : 0;
                    $pending_pct     = $total_reports > 0 ? ($total_verify / $total_reports) * 100 : 0;
                ?>
                <div style="width: <?= $resolved_pct ?>%; background: #10b981;" title="Resolved Locations"></div>
                <div style="width: <?= $in_progress_pct ?>%; background: #3b82f6;" title="In Progress Tasks"></div>
                <div style="width: <?= $pending_pct ?>%; background: #f59e0b;" title="Pending Tasks"></div>
            </div>
        </div>
    </div>

    <div class="section-title" style="margin: 2rem 0 1rem 0; font-weight: bold; color: #4a5568;">
        Live Report Activity & Action Stream
    </div>

    <div class="audit-stream-container" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; padding:24px; box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
        <div class="stream-timeline" style="display:flex; flex-direction:column; gap:20px; position:relative; padding-left: 20px; border-left: 2px dashed #e2e8f0;">
            
            <!-- High Priority: In Progress Action Item -->
            <?php if ($in_progress_task): ?>
                <div class="stream-item" style="position:relative;">
                    <span style="position:absolute; left:-27px; top:2px; background:#eff6ff; color:#3b82f6; width:12px; height:12px; border-radius:50%; border:3px solid #ffffff; box-shadow:0 0 0 2px #3b82f6;"></span>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                        <strong style="font-size:0.9rem; color:#1e40af;"><i class="fas fa-spinner fa-spin" style="margin-right:4px;"></i> Active Mitigation In Progress</strong>
                        <span style="font-size:0.75rem; color:#2563eb; font-weight:600;"><?= htmlspecialchars($in_progress_task['total_days_open']) ?> days open</span>
                    </div>
                    <p style="margin:0; font-size:0.85rem; color:#475569;">
                        Field actions are currently active at <strong><?= htmlspecialchars($in_progress_task['exact_location']) ?></strong>. Awaiting final resolution confirmation.
                    </p>
                </div>
            <?php endif; ?>

            <!-- Resolved Location Notification -->
            <?php if ($resolved_task): ?>
                <div class="stream-item" style="position:relative;">
                    <span style="position:absolute; left:-27px; top:2px; background:#ecfdf5; color:#10b981; width:12px; height:12px; border-radius:50%; border:3px solid #ffffff; box-shadow:0 0 0 2px #10b981;"></span>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                        <strong style="font-size:0.9rem; color:#065f46;"><i class="fas fa-check-circle" style="color: #10b981; margin-right: 4px;"></i> Location Issue Resolved</strong>
                        <span style="font-size:0.75rem; color:#94a3b8;"><?= htmlspecialchars($resolved_date) ?></span>
                    </div>
                    <p style="margin:0; font-size:0.85rem; color:#64748b;">
                        Coastal protection measures at <strong><?= htmlspecialchars($resolved_task['exact_location']) ?></strong> have been successfully completed and marked as resolved.
                    </p>
                </div>
            <?php endif; ?>

            <!-- New Report Submission -->
            <?php if ($new_reports): ?>
                <div class="stream-item" style="position:relative;">
                    <span style="position:absolute; left:-27px; top:2px; background:#f0f9ff; color:#0284c7; width:12px; height:12px; border-radius:50%; border:3px solid #ffffff; box-shadow:0 0 0 2px #0284c7;"></span>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                        <strong style="font-size:0.9rem; color:#0f172a;">Latest Environmental Report Submitted</strong>
                        <span style="font-size:0.75rem; color:#94a3b8;"><?= htmlspecialchars($display_date) ?></span>
                    </div>
                    <p style="margin:0; font-size:0.85rem; color:#64748b;">
                        A new assessment has been logged for <strong><?= htmlspecialchars($new_reports['exact_location']) ?></strong> (<?= htmlspecialchars($new_reports['district']) ?>, <?= htmlspecialchars($new_reports['state']) ?>).
                    </p>
                </div>
            <?php endif; ?>

            <!-- Pending Review Requirement -->
            <?php if ($pending_reports): ?>
                <div class="stream-item" style="position:relative;">
                    <span style="position:absolute; left:-27px; top:2px; background:#fffbf2; color:#f59e0b; width:12px; height:12px; border-radius:50%; border:3px solid #ffffff; box-shadow:0 0 0 2px #f59e0b;"></span>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                        <strong style="font-size:0.9rem; color:#0f172a;">Pending Assessment Required</strong>
                    </div>
                    <p style="margin:0; font-size:0.85rem; color:#64748b;">
                        <strong><?= htmlspecialchars($pending_reports['exact_location']) ?></strong> (<?= htmlspecialchars($pending_reports['district']) ?>) is currently <span style="color:#f59e0b; font-weight:700;">AWAITING EVALUATION</span>.
                    </p>
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