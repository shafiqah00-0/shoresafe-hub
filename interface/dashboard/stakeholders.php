<?php
session_start();
$role = $_SESSION['role_type'] ?? 'stakeholders';

// Enforce Database analytical connection configuration lines
require_once __DIR__ . '/../../config/database.php';

try {
    // 1. Total Reports Count
    $total_reports = $pdo->query("SELECT COUNT(*) FROM report")->fetchColumn();

    // 2. High Risk Hotspots Count
    $high_risk_hotspots = $pdo->query("SELECT COUNT(*) FROM generated_analysis WHERE LOWER(erosion_risk) = 'high'")->fetchColumn();

    // 3. Pending Verification Count
    $pending_verification = $pdo->query("
        SELECT COUNT(*) FROM report r 
        LEFT JOIN action_authorities a ON r.authoritiesid = a.authoritiesid 
        WHERE a.status_update IS NULL OR LOWER(a.status_update) = 'pending'
    ")->fetchColumn();

    // 4. Mitigated/Resolved Count 
    $resolved_actions = $pdo->query("
        SELECT COUNT(*) FROM action_authorities 
        WHERE LOWER(status_update) IN ('resolved', 'action taken')
    ")->fetchColumn();

    // 5. Average Suitability / Priority Score across critical zones
    $avg_suitability = $pdo->query("SELECT ROUND(AVG(suitability_score), 1) FROM generated_analysis WHERE LOWER(erosion_risk) = 'high'")->fetchColumn() ?? 0;

    // Fetch Breakdown of High, Medium, and Low Risk Groups for Interactive Blocks
    $risk_breakdown_stmt = $pdo->query("
        SELECT 
            LOWER(erosion_risk) as risk_level,
            COUNT(*) as risk_count
        FROM generated_analysis
        WHERE LOWER(erosion_risk) IN ('high', 'medium', 'low')
        GROUP BY LOWER(erosion_risk)
    ");
    $raw_breakdown = $risk_breakdown_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Normalize values with fallback defaults
    $risk_counts = [
        'high'   => $raw_breakdown['high'] ?? 0,
        'medium' => $raw_breakdown['medium'] ?? 0,
        'low'    => $raw_breakdown['low'] ?? 0
    ];
    $max_risk_count = max($risk_counts) ?: 1; // Avoid division by zero

    // 6. Updated Query: Fetch mixed risk location data metrics for interactive switching
    $urgent_stmt = $pdo->query("
        SELECT g.analysisid, 
               l.exact_location, 
               l.state, 
               l.district, 
               LOWER(g.erosion_risk) as erosion_risk, 
               ROUND(g.suitability_score * 100, 0) AS suitability_percentage,
               COALESCE(a.status_update, 'Pending') AS status
        FROM generated_analysis g
        INNER JOIN location l ON g.locationid = l.locationid
        LEFT JOIN report r ON l.locationid = r.locationid
        LEFT JOIN action_authorities a ON r.authoritiesid = a.authoritiesid
        WHERE LOWER(g.erosion_risk) IN ('high', 'medium', 'low')
        ORDER BY g.suitability_score DESC;
    ");
    $urgent_hotspots = $urgent_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 7. Group and count high-risk occurrences by State and District
    $geo_risk_stmt = $pdo->query("
        SELECT l.state, 
               l.district, 
               COUNT(*) as risk_count,
               ROUND(AVG(g.suitability_score * 100), 0) as avg_priority
        FROM generated_analysis g
        INNER JOIN location l ON g.locationid = l.locationid
        WHERE LOWER(g.erosion_risk) = 'high'
        GROUP BY l.state, l.district
        ORDER BY risk_count DESC, avg_priority DESC
    ");
    $geo_risks = $geo_risk_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Stakeholder Metrics Error: " . $e->getMessage());
    $total_reports = $high_risk_hotspots = $pending_verification = $resolved_actions = $avg_suitability = 0;
    $urgent_hotspots = $geo_risks = [];
    $risk_counts = ['high' => 0, 'medium' => 0, 'low' => 0];
    $max_risk_count = 1;
}

// Compute ratio proportions safely 
$verification_ratio = $total_reports > 0 ? round(($pending_verification / $total_reports) * 100) : 0;
$mitigation_ratio = $total_reports > 0 ? round(($resolved_actions / $total_reports) * 100) : 0;
$critical_ratio = $total_reports > 0 ? round(($high_risk_hotspots / $total_reports) * 100) : 0;

// SVG Stroke-dasharray map configuration parameters
$stroke_dasharray = $critical_ratio . ", 100";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stakeholder Dashboard - ShoreSafe</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="/interface/css/dashboard.css">
    <link rel="stylesheet" href="/interface/css/dashboard-extended.css">

    <style>
        .interactive-risk-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .risk-interactive-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .risk-interactive-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(31, 38, 135, 0.1);
            background: rgba(255, 255, 255, 0.9);
        }
        .risk-interactive-card.active-filter {
            border-color: #3182ce;
            box-shadow: 0 0 0 2px rgba(49, 130, 206, 0.2);
            background: rgba(255, 255, 255, 0.95);
        }
        .risk-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .risk-title {
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .risk-title.high-tier { color: #e53e3e; }
        .risk-title.medium-tier { color: #dd6b20; }
        .risk-title.low-tier { color: #38a169; }
        
        .risk-count-display {
            font-size: 2.25rem;
            font-weight: 800;
            color: #1a202c;
            line-height: 1.2;
        }
        .risk-bar-track {
            height: 8px;
            background: #edf2f7;
            border-radius: 9999px;
            margin-top: 1rem;
            overflow: hidden;
        }
        .risk-bar-fill {
            height: 100%;
            border-radius: 9999px;
            transition: width 1s ease-out;
        }
        .bg-high { background: linear-gradient(90deg, #feb2b2, #e53e3e); }
        .bg-medium { background: linear-gradient(90deg, #fbd38d, #dd6b20); }
        .bg-low { background: linear-gradient(90deg, #9ae6b4, #38a169); }

        /* Hidden Static List Blueprint Elements Container */
        #watchlist-items-wrapper {
            display: none;
        }

        /* Global Viewport Floating Tooltip Design */
        .custom-dashboard-popup {
            position: fixed;
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            color: #ffffff;
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 10px 10px -5px rgba(0, 0, 0, 0.3);
            pointer-events: none;
            z-index: 99999;
            opacity: 0;
            transform: scale(0.95) translateY(10px);
            transition: opacity 0.15s ease, transform 0.15s ease;
            border: 1px solid rgba(255, 255, 255, 0.15);
            max-width: 380px;
            min-width: 280px;
        }
        .custom-dashboard-popup.popup-visible {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
        .popup-heading {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
            padding-bottom: 0.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: block;
        }
        .popup-heading.high-risk { color: #f87171; }
        .popup-heading.medium-risk { color: #fb923c; }
        .popup-heading.low-risk { color: #4ade80; }

        .popup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.4rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .popup-item:last-child {
            border-bottom: none;
        }
        .popup-loc-details {
            display: flex;
            flex-direction: column;
            max-width: 75%;
        }
        .popup-loc-name {
            font-weight: 600;
            font-size: 0.85rem;
            color: #f8fafc;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .popup-loc-sub {
            font-size: 0.75rem;
            color: #94a3b8;
        }
        .popup-score-badge {
            font-size: 0.75rem;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.15rem 0.4rem;
            border-radius: 6px;
            color: #38bdf8;
        }
        .popup-no-data {
            font-size: 0.8rem;
            color: #94a3b8;
            font-style: italic;
            text-align: center;
            padding: 0.5rem 0;
        }
    </style>
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
        <a href="/interface/mainpage.php" class="active">
            <i class="fas fa-home"></i> <span>Overview</span>
        </a>
        <a href="/logic/controller/coastalanalysis.php">
            <i class="fas fa-file-alt"></i> <span>Report Hub</span>
        </a>
          <a href="/interface/report.html style="display: flex; align-items: center; gap: 12px; padding: 0.8rem 1rem; color: #a0aec0; border-radius: 8px; text-decoration: none; font-weight: 500; font-size: 0.95rem; transition: all 0.2s;" onmouseover="this.style.color='#fff'; this.style.background='rgba(255,255,255,0.04)';" onmouseout="this.style.color='#a0aec0'; this.style.background='transparent';">
                <i class="fas fa-plus-circle" style="width: 20px; color: #3182ce;"></i> <span>Submit New Report</span>
            </a>
          <a href="/logic/controller/logout.php" style="display: flex; align-items: center; gap: 12px; padding: 0.8rem 1rem; color: #fc8181; border-radius: 8px; text-decoration: none; font-weight: 500; font-size: 0.95rem; transition: all 0.2s;" onmouseover="this.style.color='#fff'; this.style.background='rgba(229, 62, 62, 0.2)';" onmouseout="this.style.color='#fc8181'; this.style.background='transparent';">
                <i class="fas fa-sign-out-alt" style="width: 20px;"></i> <span>Logout</span>
            </a>
    </nav>
</aside>

<main class="main-content" id="main-content">

    <header class="dashboard-header">
        <div>
            <h1>Stakeholder Overview</h1>
            <p class="header-subtitle">Coastal monitoring system monitoring summary dashboard view.</p>
        </div>
        <div class="user-badge">
            <i class="fas fa-user-shield"></i>
            <?= ucfirst(htmlspecialchars($role)) ?>
        </div>
    </header>

    <div class="section-title">Erosion Risk Count</div>
    <div class="interactive-risk-container">
        
        <div class="risk-interactive-card" onclick="highlightRiskSector('high')" id="card-risk-high" data-target-risk="high">
            <div class="risk-card-header">
                <span class="risk-title high-tier"><i class="fas fa-circle"></i> High Risk</span>
                <i class="fas fa-arrow-trend-up text-danger"></i>
            </div>
            <div class="risk-count-display"><?= $risk_counts['high'] ?></div>
            <div class="risk-bar-track">
                <div class="risk-bar-fill bg-high" style="width: <?= round(($risk_counts['high'] / $max_risk_count) * 100) ?>%;"></div>
            </div>
        </div>

        <div class="risk-interactive-card" onclick="highlightRiskSector('medium')" id="card-risk-medium" data-target-risk="medium">
            <div class="risk-card-header">
                <span class="risk-title medium-tier"><i class="fas fa-circle"></i> Medium Risk</span>
                <i class="fas fa-arrows-left-right text-warning"></i>
            </div>
            <div class="risk-count-display"><?= $risk_counts['medium'] ?></div>
            <div class="risk-bar-track">
                <div class="risk-bar-fill bg-medium" style="width: <?= round(($risk_counts['medium'] / $max_risk_count) * 100) ?>%;"></div>
            </div>
        </div>

        <div class="risk-interactive-card" onclick="highlightRiskSector('low')" id="card-risk-low" data-target-risk="low">
            <div class="risk-card-header">
                <span class="risk-title low-tier"><i class="fas fa-circle"></i> Low Risk</span>
                <i class="fas fa-arrow-trend-down text-success"></i>
            </div>
            <div class="risk-count-display"><?= $risk_counts['low'] ?></div>
            <div class="risk-bar-track">
                <div class="risk-bar-fill bg-low" style="width: <?= round(($risk_counts['low'] / $max_risk_count) * 100) ?>%;"></div>
            </div>
        </div>

    </div>

    <div class="stakeholder-metrics-row">
        <div class="chart-card-donut">
            <span class="card-meta-label">Risk Threat Density</span>
            
            <div class="progress-ring-box">
                <svg viewBox="0 0 36 36" class="circular-chart-render">
                    <circle class="circle-bg-track" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <circle class="circle-progress-fill" stroke-dasharray="<?= $stroke_dasharray ?>" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                </svg>
                <div class="chart-inner-percentage"><?= $critical_ratio ?>%</div>
            </div>

            <p class="donut-summary-text">
                <span class="danger-highlight"><?= $high_risk_hotspots ?> out of <?= $total_reports ?></span> locations are verified high-risk profiles.
            </p>

            <div class="nested-location-watchlist">
                <span class="watchlist-section-title">
                    <i class="fas fa-info-circle"></i> Hover profile cards to view locations
                </span>
                
                <div id="watchlist-items-wrapper">
                    <?php if (!empty($urgent_hotspots)): ?>
                        <?php foreach ($urgent_hotspots as $spot): ?>
                            <div class="database-item-row" 
                                 data-risk="<?= htmlspecialchars($spot['erosion_risk']) ?>" 
                                 data-location="<?= htmlspecialchars($spot['exact_location']) ?>"
                                 data-district="<?= htmlspecialchars($spot['district']) ?>, <?= htmlspecialchars($spot['state']) ?>"
                                 data-suitability="<?= $spot['suitability_percentage'] ?>%">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="linear-metrics-card">
            <div class="metric-progress-group">
                <div class="metric-progress-meta">
                    <span><i class="fas fa-exclamation-circle progress-icon-danger"></i> Critical/Danger Zone</span>
                    <span class="text-danger"><?= $high_risk_hotspots ?> Sectors (<?= $critical_ratio ?>%)</span>
                </div>
                <div class="progress-track-wrapper">
                    <div class="progress-bar-fill-indicator bg-danger" style="width: <?= $critical_ratio ?>%;"></div>
                </div>
            </div>

            <div class="metric-progress-group">
                <div class="metric-progress-meta">
                    <span><i class="fas fa-hourglass-half progress-icon-warning"></i> Pending From Authorities</span>
                    <span class="text-warning"><?= $pending_verification ?> Cases (<?= $verification_ratio ?>%)</span>
                </div>
                <div class="progress-track-wrapper">
                    <div class="progress-bar-fill-indicator bg-warning" style="width: <?= $verification_ratio ?>%;"></div>
                </div>
            </div>

            <div class="metric-progress-group">
                <div class="metric-progress-meta">
                    <span><i class="fas fa-shield-alt progress-icon-success"></i> Action taken by Authorities</span>
                    <span class="text-success"><?= $resolved_actions ?> Fixed (<?= $mitigation_ratio ?>%)</span>
                </div>
                <div class="progress-track-wrapper">
                    <div class="progress-bar-fill-indicator bg-success" style="width: <?= $mitigation_ratio ?>%;"></div>
                </div>
            </div>

            <div class="regional-risk-card-wrapper">
                <div class="regional-card-title">
                    <i class="fas fa-map-marked-alt text-primary"></i> Critical Coastal Vulnerability by Territory
                </div>
                <?php if (!empty($geo_risks)): ?>
                    <table class="geo-chart-table-compact">
                        <tbody>
                            <?php 
                            $max_intensity = max(array_column($geo_risks, 'risk_count')) ?? 1;
                            foreach (array_slice($geo_risks, 0, 3) as $row): 
                                $bar_width = round(($row['risk_count'] / $max_intensity) * 100);
                            ?>
                                <tr>
                                    <td class="geo-td-label"><?= htmlspecialchars($row['state']) ?> (<?= htmlspecialchars($row['district']) ?>)</td>
                                    <td class="geo-td-bar-cell">
                                        <div class="micro-bar-container">
                                            <div class="micro-bar-rail">
                                                <div class="micro-bar-fill" style="width: <?= $bar_width ?>%;"></div>
                                            </div>
                                            <span class="risk-badge-pill"><?= $row['risk_count'] ?> Hotspots</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
// Toggle Sidebar functionality
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('main-content');
const toggleBtn = document.getElementById('toggle-btn');
const toggleIcon = document.getElementById('toggle-icon');

if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
        if (sidebar.classList.contains('collapsed')) {
            toggleIcon.classList.replace('fa-chevron-left', 'fa-chevron-right');
        } else {
            toggleIcon.classList.replace('fa-chevron-right', 'fa-chevron-left');
        }
    });
}

// Fallback click filter behavior if needed
function highlightRiskSector(tier) {
    console.log("Filtering locked onto sector tier: " + tier);
}

// Global Mouse Tracker & Dynamic Risk Card Tooltip Compiler
document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize floating popup context wrapper
    const popup = document.createElement('div');
    popup.className = 'custom-dashboard-popup';
    document.body.appendChild(popup);

    // 2. Target interactive card handles
    const riskCards = document.querySelectorAll('.risk-interactive-card');
    const databaseRows = document.querySelectorAll('.database-item-row');

    riskCards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            const selectedRisk = card.getAttribute('data-target-risk');
            
            // Filter elements matching hovered risk level
            let activeItemsHtml = '';
            let counter = 0;

            databaseRows.forEach(row => {
                const itemRisk = row.getAttribute('data-risk').trim().toLowerCase();
                if (itemRisk === selectedRisk) {
                    counter++;
                    const location = row.getAttribute('data-location');
                    const district = row.getAttribute('data-district');
                    const valuePercentage = row.getAttribute('data-suitability');
                    
                    activeItemsHtml += `
                        <div class="popup-item">
                            <div class="popup-loc-details">
                                <span class="popup-loc-name">${location}</span>
                                <span class="popup-loc-sub">${district}</span>
                            </div>
                            <span class="popup-score-badge">${valuePercentage}</span>
                        </div>
                    `;
                }
            });

            // Set default empty state feedback inside popup framework if counter is empty
            if (counter === 0) {
                activeItemsHtml = `<div class="popup-no-data">No locations classified under this tier.</div>`;
            }

            const headerLabel = selectedRisk.toUpperCase() + " RISK LOCATIONS";
            popup.innerHTML = `
                <span class="popup-heading ${selectedRisk}-risk">
                    <i class="fas fa-shield-halved"></i> ${headerLabel} (${counter})
                </span>
                ${activeItemsHtml}
            `;
            
            popup.classList.add('popup-visible');
        });

        // 3. Track cursor positions precisely across the browser viewport
        card.addEventListener('mousemove', (e) => {
            popup.style.left = (e.clientX + 20) + 'px';
            popup.style.top = (e.clientY - 60) + 'px';
        });

        // 4. Remove elements completely when mouse departs profile metrics
        card.addEventListener('mouseleave', () => {
            popup.classList.remove('popup-visible');
        });
    });
});
</script>
</body>
</html>