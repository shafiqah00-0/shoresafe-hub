<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Only run logging if there is an active session identity to trace
if (isset($_SESSION['userid'])) {
    try {
        $ipAddress = $_SERVER['HTTP_CLIENT_IP'] 
                     ?? $_SERVER['HTTP_X_FORWARDED_FOR'] 
                     ?? $_SERVER['REMOTE_ADDR'] 
                     ?? '0.0.0.0';

        if (strpos($ipAddress, ',') !== false) {
            $ipAddress = trim(explode(',', $ipAddress)[0]);
        }

        $payloadData = [
            'username' => $_SESSION['username'] ?? null,
            'status'   => 'session_terminated'
        ];

        // Prepare using exact table schema column names
        $auditStmt = $pdo->prepare("
            INSERT INTO audit_logs (
                userid, 
                actiondo, 
                auditable_type, 
                auditable_id, 
                new_values, 
                ip_address, 
                created_at
            ) 
            VALUES (?, 'LOGOUT', 'UserSession', NULL, ?, ?, NOW())
        ");

        $auditStmt->execute([
            $_SESSION['userid'],
            json_encode($payloadData),
            $ipAddress
        ]);

    } catch (PDOException $e) {
        // Fail silently or log error so it doesn't block the actual logout process
    }
}

// Clear the session array cleanly
$_SESSION = [];
session_destroy();

header("Location: /interface/login.html"); 
exit;