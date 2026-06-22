<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Database connection is loaded first
require_once __DIR__ . '/../../config/database.php';

$is_logged_in = isset($_SESSION['role_type']);
$role = $_SESSION['role_type'] ?? 'guest';
$username = $_SESSION['username'] ?? 'Guest';

$showPublicAlert = false;
$alertLocationData = null;

// BLOCK 1: Independent Alert Query
if (!isset($_SESSION['public_warning_dismissed'])) {
    try {
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

$chartLabels = [];
$chartCounts = [];
$debugMessage = ""; 
$summary_data = [];

// BLOCK 2: Chart & Aggregated Content Feed Queries
try {
    // A. Chart Data Fetching
    $reportDistributionQuery = "
        SELECT 
            l.state,
            l.district,
            COUNT(*) as total_incidents
        FROM public.report r
        JOIN public.location l ON r.locationid = l.locationid
        GROUP BY l.state, l.district
        ORDER BY total_incidents DESC, l.state ASC
        LIMIT 15;
    ";
    
    $reportDistributionStmt = $pdo->query($reportDistributionQuery);
    $distributionData = $reportDistributionStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($distributionData as $row) {
        $chartLabels[] = htmlspecialchars($row['state'] . ' (' . $row['district'] . ')');
        $chartCounts[] = (int)$row['total_incidents'];
    }

    // B. Consolidated Data Content Feed 
    // Both states pull an aggregate row framework, but registered sessions query deep nested fields into a JSON string aggregate
    if ($is_logged_in) {
        $query = "SELECT l.state, 
                         COUNT(l.locationid) as total_incidents,
                         json_agg(json_build_object(
                            'exact_location', l.exact_location,
                            'risk', COALESCE(ga.erosion_risk, 'Low')
                         )) as detailed_json_records
                  FROM public.location l
                  LEFT JOIN public.report r ON r.locationid = l.locationid
                  LEFT JOIN public.generated_analysis ga ON l.locationid = ga.locationid
                  GROUP BY l.state 
                  ORDER BY total_incidents DESC";
    } else {
        $query = "SELECT state, 
                         COUNT(locationid) as total_incidents,
                         STRING_AGG(DISTINCT exact_location, ', ') as beach_list
                  FROM public.location 
                  GROUP BY state 
                  ORDER BY total_incidents DESC";
    }
                      
    $stmt = $pdo->query($query);
    $summary_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $debugMessage = "DATABASE ERROR TRAP: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - ShoreSafe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/interface/css/dashboard.css">
    <link rel="stylesheet" href="/interface/css/publicreport.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <a href="/interface/report.html" style="display: flex; align-items: center; gap: 12px; padding: 0.8rem 1rem; color: #a0aec0; border-radius: 8px; text-decoration: none; font-weight: 500; font-size: 0.95rem; transition: all 0.2s;" onmouseover="this.style.color='#fff'; this.style.background='rgba(255,255,255,0.04)';" onmouseout="this.style.color='#a0aec0'; this.style.background='transparent';">
                <i class="fas fa-plus-circle" style="width: 20px; color: #3182ce;"></i> <span>Submit New Report</span>
            </a>

            <a href="/logic/controller/logout.php" style="display: flex; align-items: center; gap: 12px; padding: 0.8rem 1rem; color: #fc8181; border-radius: 8px; text-decoration: none; font-weight: 500; font-size: 0.95rem; transition: all 0.2s;" onmouseover="this.style.color='#fff'; this.style.background='rgba(229, 62, 62, 0.2)';" onmouseout="this.style.color='#fc8181'; this.style.background='transparent';">
                <i class="fas fa-sign-out-alt" style="width: 20px;"></i> <span>Logout</span>
            </a>
        </nav>
    </aside>

    <main class="main-content" id="main-content">
        <header style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div>
                <h1>Welcome, <?= htmlspecialchars($username) ?></h1>
                <p style="color: #718096;">Summary of details of the coastline today.</p>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="user-badge">
                    <i class="fas fa-user-circle"></i> <?= ucfirst($role) ?>
                </div>
            </div>
        </header>

        <?php if (!empty($debugMessage)): ?>
            <div style="background: #fff5f5; border: 1px solid #e53e3e; padding: 1rem; margin-bottom: 1rem; border-radius: 12px; font-family: 'Plus Jakarta Sans', sans-serif; color: #c53030; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-triangle"></i> <span><?= htmlspecialchars($debugMessage) ?></span>
            </div>
        <?php elseif ($showPublicAlert && $alertLocationData): ?>
            <div style="background: #fffaf0; border: 1px solid #dd6b20; padding: 1.2rem; margin-bottom: 1rem; border-radius: 12px; font-family: 'Plus Jakarta Sans', sans-serif; color: #dd6b20; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 10px rgba(221, 107, 32, 0.05);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="background: #feebc8; padding: 10px; border-radius: 50%; display: flex; align-items: center; justify-content: center; animation: pulse 2s infinite;">
                        <i class="fas fa-bullhorn" style="font-size: 1.2rem; color: #dd6b20;"></i>
                    </div>
                    <div>
                        <strong style="font-size: 1rem; display: block; margin-bottom: 2px;">New Coastal Alert!</strong>
                        <span style="font-size: 0.88rem; color: #718096;">High erosion risk detected recently at: <strong><?= htmlspecialchars($alertLocationData['exact_location']) ?>, <?= htmlspecialchars($alertLocationData['state']) ?></strong></span>
                    </div>
                </div>
                <span style="font-size: 0.75rem; background: #dd6b20; color: #fff; padding: 4px 10px; border-radius: 6px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">High Risk</span>
            </div>
            <style>
                @keyframes pulse {
                    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(221, 107, 32, 0.4); }
                    70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(221, 107, 32, 0); }
                    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(221, 107, 32, 0); }
                }
            </style>
        <?php else: ?>
            <div style="background: #f0fff4; border: 1px solid #38a169; padding: 1.2rem; margin-bottom: 1rem; border-radius: 12px; font-family: 'Plus Jakarta Sans', sans-serif; color: #276749; display: flex; align-items: center; gap: 12px; box-shadow: 0 2px 10px rgba(56, 161, 105, 0.05);">
                <div style="background: #c6f6d5; padding: 10px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-shield-alt" style="font-size: 1.1rem; color: #38a169;"></i>
                </div>
                <div>
                    <strong style="font-size: 1rem; display: block; margin-bottom: 2px;">All in control</strong>
                    <span style="font-size: 0.88rem; color: #718096;">No critical/endangered zone location reported for the past 14 days.</span>
                </div>
            </div>
        <?php endif; ?>

        <div class="analytics-container" style="background: #ffffff; border-radius: 16px; padding: 1.75rem; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0; margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <div style="font-weight: 700; font-size: 1.1rem; color: #2d3748;">
                    <i class="fas fa-chart-bar" style="color: #3182ce; margin-right: 0.5rem;"></i> Incident Reports by State & District
                </div>
                <span style="font-size: 0.75rem; background: #ebf8ff; color: #2b6cb0; padding: 0.25rem 0.75rem; border-radius: 50px; font-weight: 600;">Community Data</span>
            </div>
            <div class="chart-wrapper" style="position: relative; margin: auto; height: 420px; width: 100%;">
                <canvas id="publicReportChart" 
                        data-labels='<?= json_encode($chartLabels, JSON_HEX_APOS | JSON_HEX_QUOT) ?>' 
                        data-volumes='<?= json_encode($chartCounts) ?>'>
                </canvas>
            </div>
        </div>  

        <div id="data-feed-section" class="public-container" style="padding: 0; margin-top: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: #2d3748; margin: 0;">
                    <i class="fas fa-database" style="color: #4a5568; margin-right: 0.5rem;"></i> Coastal Incident Location List
                </h2>
                <span style="font-size: 0.75rem; color: #718096; background: #edf2f7; padding: 4px 10px; border-radius: 6px;">
                    <?= $is_logged_in ? "Showing Verified Area Links" : "Public Information" ?>
                </span>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-map-marker-alt"></i> State</th>
                        <th><i class="fas fa-file-alt"></i> Total Erosion Reports</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($summary_data)): ?>
                        <?php foreach ($summary_data as $row): ?>
                            <tr>
                                <td class="location-name"><?= htmlspecialchars($row['state']) ?></td>
                                <td>
                                    <?php if ($is_logged_in): ?>
                                        <span class="badge-count location-trigger-btn" 
                                              data-state="<?= htmlspecialchars($row['state']) ?>"
                                              data-is-logged="true"
                                              data-records='<?= htmlspecialchars($row['detailed_json_records'], ENT_QUOTES, 'UTF-8') ?>'
                                              style="cursor: pointer; transition: background 0.2s;"
                                              title="Click to view locations and erosion risk levels">
                                            <?= htmlspecialchars($row['total_incidents']) ?> Reports <i class="fas fa-search-plus" style="font-size: 0.75rem; margin-left: 4px; opacity: 0.7;"></i>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-count location-trigger-btn" 
                                              data-state="<?= htmlspecialchars($row['state']) ?>"
                                              data-is-logged="false"
                                              data-beaches="<?= htmlspecialchars($row['beach_list'] ?? '') ?>"
                                              style="cursor: pointer; transition: background 0.2s;"
                                              title="Click to view locations">
                                            <?= htmlspecialchars($row['total_incidents']) ?> Reports <i class="fas fa-external-link-alt" style="font-size: 0.75rem; margin-left: 4px; opacity: 0.7;"></i>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="empty-row">No incident records currently available.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </main> 

    <?php if ($showPublicAlert && $alertLocationData): ?>
    <div id="risk-modal" class="warn-notification-overlay">
        <div class="warn-notification-card">
            <button id="dismiss-alert-x" class="warn-close-btn">&times;</button>
            <div class="warn-card-accent-bar"></div>
            <div class="warn-card-content">
                <div class="warn-warning-header">
                    <span class="warn-pulse-dot"></span>
                    <h2>High-Risk Alert Location</h2>
                </div>
                <div class="warn-modal-body">
                    <p class="warn-location-badge">📍 <?= htmlspecialchars($alertLocationData['exact_location']) ?>, <?= htmlspecialchars($alertLocationData['state']) ?></p>
                    <p class="warn-warning-text">This high risk location was detected within the last 14 days. Please avoid this area for safety purposes.</p>
                </div>
            </div>
        </div>
    </div>   
    <?php endif; ?>

    <div id="beachModal" class="beach-modal-overlay">
        <div class="beach-modal-card">
            <div class="beach-modal-header">
                <h3 id="modalStateName">Affected Areas</h3>
                <button id="closeBeachModal" class="beach-close-btn">&times;</button>
            </div>
            <div id="modalBeachContent"></div>
        </div>
    </div>

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
        // --- 1. Dynamic Details Modal Handling Logic ---
        const modal = document.getElementById('beachModal');
        const closeModalBtn = document.getElementById('closeBeachModal');
        const modalStateName = document.getElementById('modalStateName');
        const modalBeachContent = document.getElementById('modalBeachContent');
        const triggerButtons = document.querySelectorAll('.location-trigger-btn');

        triggerButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const state = btn.getAttribute('data-state');
                const isLoggedIn = btn.getAttribute('data-is-logged') === 'true';
                
                modalStateName.innerText = `📍 Locations in ${state}`;
                modalBeachContent.innerHTML = ''; 

                if (isLoggedIn) {
                    // Logged-in/Registered view parses detailed JSON objects to read risk level statuses
                    const recordsRaw = btn.getAttribute('data-records');
                    try {
                        const recordsArray = JSON.parse(recordsRaw || '[]');
                        if (recordsArray && recordsArray.length > 0) {
                            recordsArray.forEach(item => {
                                if(!item.exact_location) return;
                                
                                const div = document.createElement('div');
                                div.className = 'beach-list-item';
                                div.style.display = 'flex';
                                div.style.justifyContent = 'space-between';
                                div.style.alignItems = 'center';
                                div.style.padding = '0.5rem 0';
                                div.style.borderBottom = '1px solid #edf2f7';

                                let badgeColor = '#e2e8f0';
                                let textColor = '#4a5568';
                                const riskLower = item.risk.toLowerCase();
                                
                                if (riskLower === 'high') { badgeColor = '#fed7d7'; textColor = '#9b2c2c'; }
                                else if (riskLower === 'medium') { badgeColor = '#feebc8'; textColor = '#9c4221'; }
                                else if (riskLower === 'low') { badgeColor = '#c6f6d5'; textColor = '#22543d'; }

                                div.innerHTML = `
                                    <span><i class="fas fa-umbrella-beach" style="color: #3182ce; margin-right: 8px;"></i> ${item.exact_location}</span>
                                    <span style="font-size: 0.75rem; font-weight: 700; background: ${badgeColor}; color: ${textColor}; padding: 2px 8px; border-radius: 4px; text-transform: uppercase;">${item.risk} Risk</span>
                                `;
                                modalBeachContent.appendChild(div);
                            });
                        } else {
                            modalBeachContent.innerHTML = `<div style="color: #718096; text-align: center; padding: 1rem;">No detailed beach records found.</div>`;
                        }
                    } catch(e) {
                        console.error("Error parsing JSON data block structures", e);
                    }
                } else {
                    // Unregistered fallback loop
                    const beachesRaw = btn.getAttribute('data-beaches');
                    if (beachesRaw && beachesRaw.trim() !== '') {
                        const beachesArray = beachesRaw.split(',');
                        beachesArray.forEach(beach => {
                            const div = document.createElement('div');
                            div.className = 'beach-list-item';
                            div.style.padding = '0.5rem 0';
                            div.innerHTML = `<i class="fas fa-umbrella-beach" style="color: #3182ce; margin-right: 8px;"></i> ${beach.trim()}`;
                            modalBeachContent.appendChild(div);
                        });
                    } else {
                        modalBeachContent.innerHTML = `<div style="color: #718096; text-align: center; padding: 1rem;">No specific beach zones listed.</div>`;
                    }
                }
                modal.classList.add('active');
            });
        });

        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', () => modal.classList.remove('active'));
        }
        window.addEventListener('click', (e) => {
            if (e.target === modal) { modal.classList.remove('active'); }
        });

        // --- 2. High Risk Advisory Banner Dismiss Actions ---
        const closeBannerBtn = document.getElementById("dismiss-alert-x");
        const alertModal = document.getElementById("risk-modal");
        if (closeBannerBtn && alertModal) {
            closeBannerBtn.addEventListener("click", () => {
                alertModal.classList.add("warn-fade-out");
                setTimeout(() => { alertModal.style.display = "none"; }, 250);
                fetch("/interface/dashboard/warningpopup.php", { method: "POST" });
            });
        }

        // --- 3. ChartJS Presentation Engine Config ---
        const chartElement = document.getElementById('publicReportChart');
        if (chartElement) {
            const ctx = chartElement.getContext('2d');
            
            const labelsData = JSON.parse(chartElement.getAttribute('data-labels') || '[]');
            const volumesData = JSON.parse(chartElement.getAttribute('data-volumes') || '[]');

            const primaryBarColor = 'rgba(49, 130, 206, 0.85)'; 
            const primaryBorderColor = '#3182ce';

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labelsData,
                    datasets: [{
                        label: 'Total Incidents Reported',
                        data: volumesData,
                        backgroundColor: primaryBarColor, 
                        borderColor: primaryBorderColor,
                        borderWidth: 1,
                        borderRadius: 6, 
                        barThickness: 16
                    }]
                },
                options: {
                    indexAxis: 'y', 
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                color: '#64748b',
                                font: { family: "'Plus Jakarta Sans', sans-serif", size: 11 },
                                stepSize: 1 
                            },
                            title: {
                                display: true,
                                text: 'Number of Reports',
                                color: '#475569',
                                font: { family: "'Plus Jakarta Sans', sans-serif", size: 12, weight: '600' }
                            }
                        },
                        y: {
                            grid: { display: false },
                            ticks: {
                                color: '#1e293b',
                                font: { family: "'Plus Jakarta Sans', sans-serif", size: 11, weight: '500' }
                            }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12 },
                            bodyFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12 },
                            padding: 10,
                            callbacks: {
                                label: function(context) {
                                    return ` 📊 Incidents Count: ${context.raw} total`;
                                }
                            }
                        }
                    }
                }
            });
        }
    });
    </script>
</body>
</html>