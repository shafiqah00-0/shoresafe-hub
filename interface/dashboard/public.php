<?php
session_start();

$role = $_SESSION['role_type'] ?? 'guest';
$username = $_SESSION['username'] ?? 'Guest';

// DB connection if needed
require_once __DIR__ . '/../../config/database.php';

$stmt = $pdo->query("SELECT COUNT(*) FROM report");
$total_reports = $stmt->fetchColumn();
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