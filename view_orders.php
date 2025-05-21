<?php
session_start();
require_once 'connection/config.php';

if (!isset($_SESSION['id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['id'];
$orders = [];

try {
    // Fetch all orders for the user
    $stmt = $conn->prepare("
        SELECT o.*, 
               COUNT(oi.id) as item_count,
               GROUP_CONCAT(mi.item_name SEPARATOR ', ') as items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error loading orders: " . $e->getMessage();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Orders | Kafèa-Kiosk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6F4E37;
            --secondary-color: #C4A484;
            --light-color: #F5F5DC;
            --dark-color: #3E2723;
            --accent-color: #D2B48C;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--light-color);
            color: var(--dark-color);
        }
        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .orders-table th,
        .orders-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .orders-table th {
            background-color: var(--primary-color);
            color: white;
        }
        .orders-table tr:last-child td {
            border-bottom: none;
        }
        .order-status {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-preparing { background-color: #cce5ff; color: #004085; }
        .status-ready { background-color: #d4edda; color: #155724; }
        .status-completed { background-color: #d1e7dd; color: #0f5132; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: var(--dark-color);
        }
        .empty-orders {
            text-align: center;
            padding: 3rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .back-btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background-color: var(--secondary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 1rem;
            transition: background-color 0.3s;
        }
        .back-btn:hover {
            background-color: var(--accent-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="customer_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Menu
        </a>
        
        <h1>Your Orders</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php 
                echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <h2>No Orders Yet</h2>
                <p>You haven't placed any orders yet.</p>
                <a href="customer_dashboard.php" class="btn">
                    <i class="fas fa-utensils"></i> Browse Menu
                </a>
            </div>
        <?php else: ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo $order['id']; ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                            <td>
                                <?php 
                                $items = explode(', ', $order['items']);
                                $item_count = count($items);
                                if ($item_count > 2) {
                                    echo htmlspecialchars($items[0] . ', ' . $items[1] . ' +' . ($item_count - 2) . ' more');
                                } else {
                                    echo htmlspecialchars($order['items']);
                                }
                                ?>
                            </td>
                            <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="order_confirmation.php?id=<?php echo $order['id']; ?>" class="btn">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html> 