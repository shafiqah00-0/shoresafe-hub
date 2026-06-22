<?php
// Load backend processing and access guards dynamically
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Enforce strict Admin-only security gate
if (!isset($_SESSION['role_type']) || strtolower($_SESSION['role_type']) !== 'admin') {
    header("HTTP/1.1 403 Forbidden");
    echo "<h1>403 Forbidden</h1><p>Access denied. Administrators only.</p>";
    exit();
}

// 2. Database connection
require_once __DIR__ . '/../../config/database.php';

$role = $_SESSION['role_type'] ?? 'admin';
$username = $_SESSION['username'] ?? 'Admin';

// 3. Handle Filters & Pagination
$limit = 15;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$filter_action = $_GET['actiondo'] ?? '';
$where_clauses = [];
$params = [];

if (!empty($filter_action)) {
    $where_clauses[] = "a.actiondo = :actiondo";
    $params[':actiondo'] = $filter_action;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

try {
    // Get total logs count for pagination calculations
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM public.audit_logs a $where_sql");
    $count_stmt->execute($params);
    $total_logs = $count_stmt->fetchColumn();
    $total_pages = ceil($total_logs / $limit);

    // FIXED: Fetch paginated log records without requesting the dropped old_values column
    $queryLogs = "
        SELECT a.id, a.userid, a.actiondo, a.auditable_type, a.auditable_id, 
               a.new_values, a.ip_address, a.created_at
        FROM public.audit_logs a
        $where_sql
        ORDER BY a.created_at DESC 
        LIMIT $limit OFFSET $offset
    ";
    
    $stmtLogs = $pdo->prepare($queryLogs);
    $stmtLogs->execute($params);
    $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Admin Audit Log Error: " . $e->getMessage());
    $logs = [];
    $total_pages = 1;
}