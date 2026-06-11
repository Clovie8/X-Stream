<?php
// Database Configuration
$host = 'theonemovies-db.mysql.database.azure.com'; // Your database host (e.g., '127.0.0.1' or 'localhost')
$db_name = 'real_theone_db'; // The name of your database
$username = 'clovisadmin'; // Your database username (e.g., 'root')
$password = 'Is@AzrTheOnedb'; // Your database password

// --- Do not edit below this line ---

try {
    // Create a PDO connection object
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    // Set PDO attributes for error handling and fetching
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Handle connection error
    // For production, you might want to log this error instead of displaying it
    die("Database connection failed: " . $e->getMessage());
}