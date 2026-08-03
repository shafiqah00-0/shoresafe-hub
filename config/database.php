<?php
// config/database.php

// Helper function to safely fetch environment variables (handles empty strings "")
function env($key, $default = '') {
    $val = $_ENV[$key] ?? getenv($key) ?? '';
    return ($val !== '') ? $val : $default;
}

$host     = env('PGHOST', env('DB_HOST', 'localhost'));
$port     = env('PGPORT', env('DB_PORT', '31884'));
$dbname   = env('PGDATABASE', env('DB_NAME', 'railway'));
$user     = env('PGUSER', env('DB_USER', 'postgres'));
$password = env('PGPASSWORD', env('DB_PASS', 'ZmMHYfMFOdubjVohxRrmUytvPMKgiDQm'));

try {
    // PostgreSQL DSN Connection String
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed for host '{$host}': " . $e->getMessage());
}