<?php
// config/database.php

// Check Railway's standard PostgreSQL env variables, then custom env variables, then local defaults
$host     = getenv('PGHOST')     ?: getenv('DB_HOST')     ?: 'localhost';
$port     = getenv('PGPORT')     ?: getenv('DB_PORT')     ?: '5432';
$dbname   = getenv('PGDATABASE') ?: getenv('DB_NAME')     ?: 'shoresafe';
$user     = getenv('PGUSER')     ?: getenv('DB_USER')     ?: 'postgres';
$password = getenv('PGPASSWORD') ?: getenv('DB_PASS')     ?: '1234';

try {
    // PostgreSQL DSN Connection String
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Return explicit connection message for debugging
    die("Database connection failed: " . $e->getMessage());
}