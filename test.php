<?php
// test_db.php - Simple PostgreSQL connection test

echo "<h1>PostgreSQL Connection Test</h1>";

// Test with pg_connect (native PostgreSQL)
echo "<h2>Test 1: Using pg_connect()</h2>";
$host = 'localhost';
$port = '5432';
$dbname = 'psm';
$user = 'postgres';
$password = '1234';

$connection_string = "host=$host port=$port dbname=$dbname user=$user password=$password";
$dbconn = pg_connect($connection_string);

if (!$dbconn) {
    echo "❌ pg_connect FAILED: " . pg_last_error() . "<br>";
} else {
    echo "✅ pg_connect SUCCESS! Connected to PostgreSQL.<br>";
    pg_close($dbconn);
}

// Test with PDO
echo "<h2>Test 2: Using PDO</h2>";
try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ PDO SUCCESS! Connected to PostgreSQL.<br>";
    
    // Get PostgreSQL version
    $stmt = $pdo->query("SELECT version()");
    $version = $stmt->fetchColumn();
    echo "<br>PostgreSQL Version: <strong>" . $version . "</strong><br>";
    
    $pdo = null;
} catch (PDOException $e) {
    echo "❌ PDO FAILED: " . $e->getMessage() . "<br>";
}

// Show PHP extensions status
echo "<h2>PHP Extension Status</h2>";
echo "pgsql extension: " . (extension_loaded('pgsql') ? "✅ Loaded" : "❌ Not loaded") . "<br>";
echo "pdo_pgsql extension: " . (extension_loaded('pdo_pgsql') ? "✅ Loaded" : "❌ Not loaded") . "<br>";
?>