<?php
// Start session for authentication
session_start();

$authenticated = false;

// Database connection (replace with your credentials)
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "ussds";

// Create connection
// $conn = new mysqli($servername, $username, $password, $dbname);
$db = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);

// Get dashboard statistics
function getDashboardStats() {
    global $db;
    
    // Get total funds
    $stmt = $db->query("SELECT SUM(amount) as total FROM transactions");
    $total_funds = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Get total sessions
    $stmt = $db->query("SELECT COUNT(*) as total FROM orders");
    $total_sessions = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Get best client
    $stmt = $db->query("SELECT phone_number, COUNT(*) as count 
                       FROM orders 
                       GROUP BY phone_number 
                       ORDER BY count DESC 
                       LIMIT 1");
    $best_client = $stmt->fetch(PDO::FETCH_ASSOC)['phone_number'] ?? 'N/A';
    
    // Calculate growth percentage (comparing to last month)
    $stmt = $db->query("SELECT 
                        (SELECT SUM(amount) FROM transactions WHERE MONTH(created_at) = MONTH(CURRENT_DATE)) as this_month,
                        (SELECT SUM(amount) FROM transactions WHERE MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)) as last_month");
    $growth = $stmt->fetch(PDO::FETCH_ASSOC);
    $growth_percentage = $growth['last_month'] ? round(($growth['this_month'] - $growth['last_month']) / $growth['last_month'] * 100) : 0;
    
    return [
        'total_funds' => $total_funds,
        'growth_percentage' => $growth_percentage,
        'total_sessions' => $total_sessions,
        'best_client' => $best_client
    ];
}

$stats = getDashboardStats();


// Function to get pending orders
function getPendingOrders() {
    global $db;
    $stmt = $db->query("SELECT * FROM orders WHERE status = 'pending' ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle order dispatch
if (isset($_POST['dispatch_order'])) {
    $order_id = $_POST['order_id'];
    $stmt = $db->prepare("UPDATE orders SET status = 'dispatched' WHERE order_id = ?");
    $stmt->execute([$order_id]);
    
    // Return JSON response for AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Redirect for non-AJAX requests
    header('Location: dashboard.php');
    exit;

}

$pending_orders = getPendingOrders();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uji Power</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">
    
</head>
<body>
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Funds</h3>
                    <div class="stat-value">KSH <?= number_format($stats['total_funds']) ?></div>
                    <div class="growth">+<?= $stats['growth_percentage'] ?>%</div>
                </div>
                <div class="icon-container">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Orders</h3>
                    <div class="stat-value"><?= number_format($stats['total_sessions']) ?></div>
                </div>
                <div class="icon-container">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3>Best Client</h3>
                    <div class="stat-value"><?= $stats['best_client'] ?></div>
                </div>
                <div class="icon-container">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>
    <h2 class="new_orders">New Orders</h2>
    <div class="orders-section">
        
        <div class="orders-grid">
            <?php foreach ($pending_orders as $order): ?>
            <div class="order-card" id="order-<?= htmlspecialchars($order['order_id']) ?>">
                <div class="order-header">
                    <h3>Order #<?= htmlspecialchars($order['order_id']) ?></h3>
                    <span class="order-time"><?= date('H:i', strtotime($order['created_at'])) ?></span>
                </div>
                <div class="order-details">
                    <div class="detail-row">
                        <span class="label">Uji Type:</span>
                        <span class="value"><?= htmlspecialchars($order['tea_type']) ?></span>
                    </div>
                    <?php if ($order['flavor']): ?>
                    <div class="detail-row">
                        <span class="label">Flavor:</span>
                        <span class="value"><?= htmlspecialchars($order['flavor']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <span class="label">Quantity:</span>
                        <span class="value"><?= htmlspecialchars($order['quantity']) ?> cups</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Location:</span>
                        <span class="value"><?= htmlspecialchars($order['building']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Office:</span>
                        <span class="value"><?= htmlspecialchars($order['office']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Delivery Time:</span>
                        <span class="value"> <h2><?= htmlspecialchars($order['delivery_time']) ?></h2></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Phone:</span>
                        <span class="value"><?= htmlspecialchars($order['phone_number']) ?></span>
                    </div>
                </div>
                <form class="dispatch-form" method="POST" action="dispatch_order.php">
                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">
                    <button type="submit" name="dispatch_order" class="dispatch-btn">
                        Mark as Dispatched
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script src="dashboard.js"></script>
</body>
</html>