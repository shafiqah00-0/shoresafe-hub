<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = isset($_SESSION['role_type']);
$role = $_SESSION['role_type'] ?? 'guest';
$username = $_SESSION['username'] ?? 'Guest';

require_once __DIR__ . '/../../config/database.php';



// ==================================================================
// 2. MAIN CORE DATA QUERIES
// ==================================================================
try {
    // Shared metric card data across both view modes
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM report");
    $total_reports = $stmtCount->fetchColumn();

    if ($is_logged_in) {
        // QUERY FOR SIGNED-IN USERS: Joining report, location, and generated_analysis tables
        $query = "SELECT l.locationid, 
                         l.state, 
                         r.description, 
                         COALESCE(ga.erosion_risk, 'Low') as risk_classification 
                  FROM public.report r
                  JOIN public.location l ON r.locationid = l.locationid
                  LEFT JOIN public.generated_analysis ga ON l.locationid = ga.locationid
                  ORDER BY l.locationid DESC";
                  
        $stmt = $pdo->query($query);
        $detailed_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // QUERY FOR PUBLIC GUESTS (Summary Analytics)
        $query = "SELECT state, COUNT(locationid) as total_incidents 
                  FROM public.location 
                  GROUP BY state 
                  ORDER BY total_incidents DESC";
                      
        $stmt = $pdo->query($query);
        $summary_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Dashboard Primary Query Error: " . $e->getMessage());
    $detailed_data = [];
    $summary_data = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Dashboard - ShoreSafe</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="/interface/css/publicreport.css">
</head>
<body>

    <div class="public-container">
        <header class="public-header">
            <div class="header-titles">
                <h1>🌊 ShoreSafe</h1>
                <?php if ($is_logged_in): ?>
                    <p>Detailed incident risk classification tracking feed</p>
                <?php else: ?>
                    <p>Summary of erosion reports by location</p>
                <?php endif; ?>
            </div>
            <div class="header-actions-wrapper">
                <a href="/interface/dashboard/public.php" class="home-nav-btn">
                    <i class="fas fa-home"></i> Home
                </a>
            <?php if ($is_logged_in): ?>
                <div class="user-profile-badge">
                    <i class="fas fa-user-check"></i> <span><?= ucfirst(htmlspecialchars($_SESSION['role_type'])) ?> Portal</span>
                </div>
            <?php else: ?>
                <a href="/interface/login.html" class="login-badge-btn">
                    <i class="fas fa-sign-in-alt"></i>Home
                </a>
            <?php endif; ?>
        </header>

        <table class="data-table">
            <thead>
                <tr>
                    <?php if ($is_logged_in): ?>
                        <th>ID</th>
                    <?php endif; ?>
                    
                    <th><i class="fas fa-map-marker-alt"></i> State</th>
                    
                    <?php if ($is_logged_in): ?>
                        <th><i class="fas fa-align-left"></i> Description / Details</th>
                        <th><i class="fas fa-exclamation-triangle"></i> Risk Level</th>
                    <?php else: ?>
                        <th><i class="fas fa-file-alt"></i> Total Erosion Reports</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($is_logged_in): ?>
                    <?php if (!empty($detailed_data)): ?>
                        <?php foreach ($detailed_data as $row): ?>
                            <tr>
                                <td style="font-weight: bold; color: #4a5568;">ES-<?= htmlspecialchars($row['locationid']) ?></td>
                                <td class="location-name"><?= htmlspecialchars($row['state']) ?></td>
                                <td style="color: #4a5568; font-size: 0.95rem; max-width: 400px;"><?= htmlspecialchars($row['description']) ?></td>
                                <td>
                                    <?php 
                                        $risk = strtolower($row['risk_classification']);
                                        $risk_class = 'risk-low';
                                        if ($risk === 'high') { $risk_class = 'risk-high'; }
                                        elseif ($risk === 'medium') { $risk_class = 'risk-medium'; }
                                    ?>
                                    <span class="risk-badge <?= $risk_class ?>">
                                        <?= ucfirst(htmlspecialchars($row['risk_classification'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="empty-row">No detailed records available.</td>
                        </tr>
                    <?php endif; ?>

                <?php else: ?>
                    <?php if (!empty($summary_data)): ?>
                        <?php foreach ($summary_data as $row): ?>
                            <tr>
                                <td class="location-name"><?= htmlspecialchars($row['state']) ?></td>
                                <td><span class="badge-count"><?= $row['total_incidents'] ?> Reports</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="empty-row">No incident records currently available.</td>
                        </tr>
                    <?php endif; ?>
                <?php endif; ?>
            </tbody>
        </table>
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

</body>
</html>
