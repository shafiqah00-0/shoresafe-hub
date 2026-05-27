<?php
session_start();

// 1. Identify User Status
$page = $_GET['page'] ?? 'home'; 
$is_logged_in = isset($_SESSION['userid']);
$role = $_SESSION['role_type'] ?? 'guest'; // Defaults to guest if not logged in

// 2. Security Logic
// Pages that are 100% public (Guest can see these)
$public_pages = ['home', 'login', 'report', 'dashboard']; 

// Redirect to login ONLY if they try to access a page that is NOT in the public list
if (!$is_logged_in && !in_array($page, $public_pages)) {
    header("Location: index.php?page=login");
    exit();
}

// If already logged in and tries to go to login page, send to dashboard
if ($is_logged_in && $page === 'login') {
    header("Location: index.php?page=dashboard");
    exit();
}

// 3. Page Routing (The Switch)
switch($page) {

    case 'home':
        // Pass login status to the mainpage
        include __DIR__ . '/interface/mainpage.php'; 
        break;

    case 'login':
        include __DIR__ . '/interface/login.html';
        break;

    case 'dashboard':
        // This file will now use the $role variable to hide/show buttons
        include __DIR__ . '/interface/dashboard/public.php'; 
        break;

    case 'report':
        include __DIR__ . '/interface/report.html';
        break;

    case 'logout':
        session_unset(); // Clear variables
        session_destroy();
        header("Location: index.php?page=home");
        exit();

    default:
        http_response_code(404);
        echo "<h1>404 - Page not found</h1>";
}