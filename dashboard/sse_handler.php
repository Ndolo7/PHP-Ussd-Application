<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// Database connection
try {
    $servername = "localhost";
    $username = "root";
    $password = ""; 
    $dbname = "ussds";
    $db = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    exit();
}

// Send initial retry interval (5 seconds)
echo "retry: 5000\n\n";

// Keep track of last check
$lastCheck = isset($_GET['lastCheck']) ? $_GET['lastCheck'] : 0;

while (true) {
    // Check for new orders
    $stmt = $db->prepare("SELECT * FROM myorders WHERE status = 'pending' AND created_at > FROM_UNIXTIME(?)");
    $stmt->execute([$lastCheck]);
    $newOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($newOrders)) {
        echo "event: newOrders\n";
        echo "data: " . json_encode($newOrders) . "\n\n";
    }
    
    // Update last check time
    $lastCheck = time();
    
    // Clear output buffer and send data
    ob_flush();
    flush();
    
    // Sleep for 5 seconds before next check
    sleep(5);
}