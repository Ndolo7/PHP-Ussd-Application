<?php
header('Content-Type: application/json'); // Always return JSON


try {
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = ""; 
    $dbname = "ussds";
    
    $db = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if order_id is provided
    if (!isset($_POST['order_id'])) {
        throw new Exception('Order ID is required');
    }

    // Update order status
    $stmt = $db->prepare("UPDATE orders SET status = 'dispatched' WHERE order_id = ?");
    $success = $stmt->execute([$_POST['order_id']]);

    if ($success && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Order successfully dispatched'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Order not found or already dispatched'
        ]);
    }

} catch (PDOException $e) {
    // Database error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    // Other errors
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}