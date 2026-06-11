<?php
// Set the content type header to JSON
header('Content-Type: application/json');

// Include the database connection
require_once 'db.php';

// Get data from the POST request
$name = trim($_POST['name'] ?? '');
$comment = trim($_POST['comment'] ?? '');

$timezone = new DateTimeZone('Africa/Kigali');
$now = new DateTime('now', $timezone);
$created_at = $now->format('Y-m-d H:i:s');

// --- Validation ---
if (empty($name) || empty($comment)) {
    echo json_encode([
        'success' => false,
        'message' => 'Name and comment fields are required.'
    ]);
    exit;
}
// Add any other validation you need (e.g., strlen)
// --- End Validation ---


try {
    // --- 1. Insert the new comment ---
    // The query uses named placeholders (:name, :comment)
    $sql_insert = "INSERT INTO comments (name, comment, created_at) VALUES (:name, :comment, :created_at)";
    
    // Prepare the statement
    $stmt_insert = $pdo->prepare($sql_insert);
    
    // Execute the statement, passing the data as an array
    $stmt_insert->execute([
        'name' => $name,
        'comment' => $comment,
        'created_at' => $created_at
    ]);
    
    // Get the ID of the comment we just inserted
    $lastId = $pdo->lastInsertId();

    // --- 2. Fetch the new comment (with the DB-generated timestamp) ---
    // This ensures what we send back to the JS is exactly what's in the DB
    $sql_fetch = "SELECT name, comment, created_at AS timestamp 
                  FROM comments 
                  WHERE id = :id";
                  
    $stmt_fetch = $pdo->prepare($sql_fetch);
    $stmt_fetch->execute(['id' => $lastId]);
    $newComment = $stmt_fetch->fetch();
    
    // --- 3. Send the success response ---
    if ($newComment) {
        echo json_encode([
            'success' => true,
            'comment' => $newComment // Send the full comment object back
        ]);
    } else {
        throw new Exception("Failed to retrieve the new comment after insertion.");
    }

} catch (PDOException $e) {
    // Handle database errors
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Handle other general errors
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}