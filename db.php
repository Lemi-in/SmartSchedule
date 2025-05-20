<?php
// db.php
require 'config.php';

try {
    // Use the correct variable names from your config.php
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Make the connection available globally
    $pdo = $conn;

    // Test the connection
    $pdo->query("SELECT 1");

} catch (PDOException $e) {
    // Log the error securely
    error_log('Database connection error: ' . $e->getMessage());

    // Show user-friendly message
    die('Could not connect to the database. Please try again later.');
}
?>