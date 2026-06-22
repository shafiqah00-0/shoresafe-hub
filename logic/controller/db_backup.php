<?php
session_start();

// 1. Security Check
if (!isset($_SESSION['role_type']) || strtolower($_SESSION['role_type']) !== 'admin') {
    die("<h1>403 Forbidden</h1><p>Access denied.</p>");
}

// 2. Database configuration
$db_host = 'localhost';
$db_user = 'postgres'; 
$db_pass = '1234'; 
$db_name = 'psm';

// 3. Exact path to your pg_dump executable
$pg_dump_path = 'C:\laragon\bin\PostgreSQL\18\bin\pg_dump.exe'; 
$backupFile = 'Erosion_Backup_' . date('Y-m-d_H-i-s') . '.sql';

// 4. Set headers for browser download
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $backupFile . '"');
header('Pragma: no-cache');
header('Expires: 0');

// 5. Build command and stream to browser
putenv("PGPASSWORD=$db_pass");

// We wrap the path in quotes to handle spaces in folder names
$command = "\"$pg_dump_path\" -h $db_host -U $db_user -d $db_name";

// passthru executes the command and streams the raw output directly to the browser
passthru($command, $returnVar);

// If $returnVar is not 0, the command failed. 
// Check your browser's Network Tab (F12) to see any error messages sent as the file content.
if ($returnVar !== 0) {
    exit;
}
?>