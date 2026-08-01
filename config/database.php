<?php
// config/database.php

// Check $_ENV first (used by FrankenPHP/Caddy), then getenv(), then default
$host     = $_ENV['PGHOST']     ?? getenv('PGHOST')     ?? $_ENV['DB_HOST']     ?? getenv('DB_HOST')     ?? 'localhost';
$port     = $_ENV['PGPORT']     ?? getenv('PGPORT')     ?? $_ENV['DB_PORT']     ?? getenv('DB_PORT')     ?? '5432';
$dbname   = $_ENV['PGDATABASE'] ?? getenv('PGDATABASE') ?? $_ENV['DB_NAME']     ?? getenv('DB_NAME')     ?? 'psm';
$user     = $_ENV['PGUSER']     ?? getenv('PGUSER')     ?? $_ENV['DB_USER']     ?? getenv('DB_USER')     ?? 'postgres';
$password = $_ENV['PGPASSWORD'] ?? getenv('PGPASSWORD') ?? $_ENV['DB_PASS']     ?? getenv('DB_PASS')     ?? '1234';

try {
    // PostgreSQL DSN Connection String
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Helpful debugging output if host isn't picked up
    die("Database connection failed for host '{$host}': " . $e->getMessage());
}