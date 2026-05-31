<?php
// 1. Define all configuration variables properly
$host = "localhost";   
$port = "5432";        
$dbname = "registration";  
$username = "utem";
$password = "1234";

// 2. Create the connection string using the variables defined above
$connection_string = "host=$host port=$port dbname=$dbname user=$username password=$password";

// 3. Establish the connection
$conn = pg_connect($connection_string);

// 4. Validate the connection handle explicitly
if (!$conn) {
    die("Connection failed!");
}

echo "Connected to PostgreSQL successfully!";
pg_close($conn);
?>