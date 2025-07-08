<?php
require_once 'config.php'; // Your database configuration file

header('Content-Type: application/json');

if (!isset($_GET['house_id'])) {
    echo json_encode(['success' => false, 'message' => 'House ID not provided']);
    exit;
}

$house_id = intval($_GET['house_id']);

try {
    $stmt = $pdo->prepare("SELECT * FROM rental_houses WHERE id = ?");
    $stmt->execute([$house_id]);
    $house = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($house) {
        echo json_encode(['success' => true, 'house' => $house]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Property not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}