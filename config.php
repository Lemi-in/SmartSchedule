<?php
// Database configuration
$host = 'localhost:3400';       // Remove :3400 unless you specifically need it
$dbname = 'university_scheduler';
$username = 'root';
$password = '';

try {
    // Create connection without selecting database first
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verify database exists
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
    if ($stmt->rowCount() == 0) {
        die("Database '$dbname' does not exist. Please create it first.");
    }

    // Now connect with database selected
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Rest of your configuration...
?>