<?php
session_start();

// Enforce role-based access control if needed, matching your system's session variables
$role = $_SESSION['role_type'] ?? 'authorities';

// Optional: If you want to restrict this page to only Authorities and Admins, uncomment the lines below:
/*
if (!in_array($role, ['authorities', 'admin'])) {
    header("Location: /login.php");
    exit();
}
*/
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coastal Analysis - ShoreSafe</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="/interface/css/dashboard.css">
</head>
<body>

<div class="dashboard-wrapper" style="display: flex; min-height: 100vh;">

    <aside class="sidebar" id="sidebar">
        <button class="toggle-btn" id="toggle-btn" aria-label="Toggle Navigation Sidebar">
            <i class="fas fa-chevron-left" id="toggle-icon"></i>
        </button>

        <div class="logo">
            <i class="fas fa-water"></i> <span>ShoreSafe</span>
        </div>

        <nav>
            <a href="index.php?page=dashboard">
                <i class="fas fa-home"></i> <span>Overview</span>
            </a>

            <a href="/logic/controller/managereport.php">
                <i class="fas fa-chart-pie"></i> <span>Report Management</span>
            </a>

            <a href="coastal_analysis.php" class="active">
                <i class="fas fa-file-alt"></i> <span>Coastal Analysis</span>
            </a>

            <a href="/logic/controller/logout.php" style="margin-top: auto;">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </nav>
    </aside>

    <main class="main-content fade-in" id="main-content">
        
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1 style="font-size: 1.75rem; font-weight: 700; color: #1a202c;">Coastal Analysis Dashboard</h1>
                <p style="color:#718096; margin-top: 0.25rem;">
                    Data-driven insights, predictive risk modeling, and shoreline erosion analytics.
                </p>
            </div>

            <div class="user-badge" style="background: #ebf8ff; color: #2b6cb0; padding: 0.5rem 1rem; border-radius: 9999px; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-chart-bar"></i>
                <?= ucfirst($role) ?> View
            </div>
        </header>

        <div class="analytics-container">
            <div class="iframe-responsive-wrapper">
                <iframe title="locreportpsm" width="600" height="373.5" src="https://app.powerbi.com/view?r=eyJrIjoiY2FhY2Y5ZTAtZGI5Yy00NThhLWI2NDAtNzU4MGUyNjUyN2EwIiwidCI6IjY3N2VlYjU2LWYyZGYtNDY3NS05ZTBjLWNjZDYwMGE5MTU4MCIsImMiOjEwfQ%3D%3D" frameborder="0" allowFullScreen="true"></iframe>
            </div>
        </div>

    </main>
</div>

<script>
// Capture layout control DOM anchors
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('main-content');
const toggleBtn = document.getElementById('toggle-btn');
const toggleIcon = document.getElementById('toggle-icon');

/**
 * Event listener managing structural CSS updates. Responsible for layout animations 
 * when breaking fluid grids back and forth for screen space optimization rules.
 */
toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');

    // Smoothly swap graphic vectors based on structural widths
    if (sidebar.classList.contains('collapsed')) {
        toggleIcon.classList.replace('fa-chevron-left', 'fa-chevron-right');
    } else {
        toggleIcon.classList.replace('fa-chevron-right', 'fa-chevron-left');
    }
});
</script>

</body>
</html>