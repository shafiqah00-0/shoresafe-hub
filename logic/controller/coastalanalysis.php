<?php
session_start();

// 1. Check if the user is logged in and has a valid role
if (!isset($_SESSION['role_type'])) {
    // No role found in session -> Redirect to login page
    header("Location: /interface/login.php"); 
    exit();
}

$role = $_SESSION['role_type'];

// 2. Define allowed roles for this specific page
$allowedRoles = ['authorities', 'stakeholders']; 

// 3. Strict Role Verification Check
if (!in_array($role, $allowedRoles)) {
    // Role is not authorized -> Redirect to login
    header("Location: /interface/login.html"); 
    exit();
}

// 4. Define the dedicated PUBLIC Power BI URLs for each distinct role view
// FIXED: The authority link now contains the complete, unbroken base64 token string
$authorityUrl   = "https://app.powerbi.com/view?r=eyJrIjoiY2FhY2Y5ZTAtZGI5Yy00NThhLWI2NDAtNzU4MGUyNjUyN2EwIiwidCI6IjY3N2VlYjU2LWYyZGYtNDY3NS05ZTBjLWNjZDYwMGE5MTU4MCIsImMiOjEwfQ%3D%3D";
$stakeholderUrl = "https://app.powerbi.com/view?r=eyJrIjoiYzY4YTVmNDktYjFmNy00M2E0LTk4OWEtMDYwYjQyNTU3ZGVmIiwidCI6IjY3N2VlYjU2LWYyZGYtNDY3NS05ZTBjLWNjZDYwMGE5MTU4MCIsImMiOjEwfQ%3D%3D";

if ($role === 'authorities') {
    $embedUrl = $authorityUrl;
    $overviewUrl = "/interface/dashboard/authorities.php";
} else {
    $embedUrl = $stakeholderUrl;
    $overviewUrl = "/interface/dashboard/stakeholders.php";
}
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
<aside class="sidebar" id="sidebar">
    <button class="toggle-btn" id="toggle-btn">
        <i class="fas fa-chevron-left" id="toggle-icon"></i>
    </button>
    <div class="logo">
         <span> 🌊 ShoreSafe</span>
    </div>
    <nav>
        <a href="<?= htmlspecialchars($overviewUrl) ?>" class="active">
            <i class="fas fa-home"></i> <span>Overview</span>
        </a>
        <a href="/logic/controller/coastalanalysis.php">
            <i class="fas fa-file-alt"></i> <span>Coastal Analysis</span>
        </a>
        <a href="/logic/controller/logout.php" style="margin-top: auto;">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </nav>
</aside>

<div class="dashboard-wrapper" style="display: flex; min-height: 100vh;">
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

        <div class="analytics-card">
            <div class="card-header">
                <div class="header-title">
                    <span class="pulse-icon"></span> Real-Time GIS Analytics
                </div>
                <span class="system-badge">Power BI Public Stream</span>
            </div>
            <div class="iframe-responsive-wrapper">
                <iframe 
                    src="<?= htmlspecialchars($embedUrl) ?>"
                    frameborder="0" 
                    allowFullScreen="true">
                </iframe>
            </div>
        </div>
    </main>
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