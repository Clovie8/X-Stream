<?php
// Set the content type header to JSON
header('Content-Type: application/json');

// Privent comment to be cached 
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Include the database connection
require_once 'db.php';

$response = [];

try {
    // Prepare and execute the SQL query
    // We select `created_at AS timestamp` so the key name matches what the JavaScript expects
    $sql = "SELECT name, comment, created_at AS timestamp 
            FROM comments 
            ORDER BY id DESC";
            
    $stmt = $pdo->query($sql);
    
    // Fetch all comments
    $comments = $stmt->fetchAll();
    
    // Send the comments as JSON
    echo json_encode($comments);

} catch (PDOException $e) {
    // Send a JSON error message if something goes wrong
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch comments: ' . $e->getMessage()
    ]);
}