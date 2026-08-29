<?php
// /app/logic/controller/auditlogs.php

require_once __DIR__ . '/../../config/database.php';

// Fetch system activity/audit logs
$stmt = $pdo->query("SELECT * FROM audit_logs ORDER BY created_at DESC");
$audit_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>