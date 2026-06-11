<?php
require 'connection.php';

header('Content-Type: application/json');

if (!isset($_GET['query']) || strlen($_GET['query']) < 2) {
    echo json_encode([]);
    exit;
}

$query = '%' . trim($_GET['query']) . '%';

try {
    $stmt = $connect->prepare("
        SELECT id, name, image, token, category, translator, release_year 
        FROM series 
        WHERE (name LIKE ? OR translator LIKE ?)
        ORDER BY name ASC
        LIMIT 8
    ");
    $stmt->bind_param('ss', $query, $query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $series = [];
    while ($row = $result->fetch_assoc()) {
        $series[] = $row;
    }
    
    echo json_encode($series);
} catch (Exception $e) {
    error_log("Search error: " . $e->getMessage());
    echo json_encode([]);
}