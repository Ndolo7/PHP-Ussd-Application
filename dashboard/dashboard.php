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
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #E91E63, #9C27B0);
            --card-bg: #ffffff;
            --text-primary: #333333;
            --text-secondary: #666666;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f5f5;
            color: var(--text-primary);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-info h3 {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .growth {
            color: #4CAF50;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .icon-container {
            width: 48px;
            height: 48px;
            background: var(--primary-gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon-container svg {
            width: 24px;
            height: 24px;
            color: white;
        }

        .main-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .main-content h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .main-content p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }

        .illustration {
            background: var(--primary-gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .orders-section {
            margin-top: 2rem;
        }

        .orders-section h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .order-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, opacity 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-2px);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }

        .order-header h3 {
            font-size: 1.1rem;
            color: var(--text-primary);
        }

        .order-time {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .detail-row .label {
            color: var(--text-secondary);
        }

        .detail-row .value {
            font-weight: 500;
            color: var(--text-primary);
        }
        .new_orders {
            background: var(--primary-gradient);
            color: white;
            border-radius: 8px;
            width: 100%;
            font-weight: 1000;
            padding: 0.75rem;
            display: grid;
            place-items: center;
            

        }
        .dispatch-btn {
            width: 100%;
            padding: 0.75rem;
            margin-top: 1rem;
            background: var(--primary-gradient);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            cursor: pointer;
            transition: opacity 0.2s ease;
        }

        .dispatch-btn:hover {
            opacity: 0.9;
        }

        .no-orders {
            text-align: center;
            padding: 2rem;
            background: var(--card-bg);
            border-radius: 12px;
            color: var(--text-secondary);
            font-size: 1.1rem;
            grid-column: 1 / -1;
        }

        @media (max-width: 768px) {
            .orders-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .main-card {
                grid-template-columns: 1fr;
            }

            .stat-card {
                padding: 1rem;
            }

            .stat-value {
                font-size: 1.25rem;
            }
        }
    </style>
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
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dispatchForms = document.querySelectorAll('.dispatch-form');
            
            dispatchForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault(); // Prevent normal form submission
                    
                    const orderCard = this.closest('.order-card');
                    const formData = new FormData(this);
                    const button = this.querySelector('button');
                    
                    // Show loading state
                    button.disabled = true;
                    button.textContent = 'Processing...';
                    orderCard.style.opacity = '0.5';
                    
                    // Make sure this path matches your file structure
                    fetch(this.action, {  // Using the form's action attribute
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Successful dispatch
                            orderCard.style.opacity = '0';
                            setTimeout(() => {
                                orderCard.remove();
                                
                                // Check if there are any orders left
                                const remainingOrders = document.querySelectorAll('.order-card');
                                if (remainingOrders.length === 0) {
                                    const ordersGrid = document.querySelector('.orders-grid');
                                    ordersGrid.innerHTML = '<div class="no-orders">No pending orders</div>';
                                }
                            }, 300);
                        } else {
                            throw new Error('Dispatch failed');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Reset the card state
                        orderCard.style.opacity = '1';
                        button.disabled = false;
                        button.textContent = 'Mark as Dispatched';
                        alert('Failed to dispatch order. Please try again.');
                    });
                });
            });
        });
    </script>
</body>
</html>