<?php
header('Content-Type: application/json');

include "../functions/dbconnect.php";

try {    
    $stmt = $db->query("SELECT * FROM myorders WHERE status = 'pending' ORDER BY created_at DESC");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'myorders' => $orders]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}