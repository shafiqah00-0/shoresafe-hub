<?php
// /app/logic/controller/auditlogs.php

// 1. Include your database connection file first
require_once __DIR__ . '/../../config/database.php'; 

// 2. Ensure $pdo is initialized before running queries
if (!isset($pdo) || $pdo === null) {
    die("Database connection failed: \$pdo is not defined.");
}

// 3. Now run your query safely
$stmt = $pdo->query("SELECT COUNT(*) as total_pending FROM users WHERE status = 'pending' AND role_type IN ('authorities', 'stakeholders')");
$pending_data = $stmt->fetch(PDO::FETCH_ASSOC);
$total_pending = $pending_data['total_pending'];