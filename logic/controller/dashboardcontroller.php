<?php
session_start();


class DashboardController {

    public function index() {

        // 🔐 Security check
        if (!isset($_SESSION['userid'])) {
            header("Location: index.php?page=login");
            exit();
        }

        // 📦 Safe session data
        $username = $_SESSION['username'] ?? 'Guest';
        $role = $_SESSION['role_type'] ?? 'public';


        // 🧠 Pass data to view (IMPORTANT)
        require __DIR__ . '/../dashboard/public.php';
    }
} 
