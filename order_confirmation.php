<?php
session_start();
require_once 'connection/config.php';

if (!isset($_SESSION['id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    header("Location: customer_dashboard.php");
    exit();
}

// Fetch order details
try {
    $stmt = $conn->prepare("
        SELECT o.*, u.fullname, u.email, u.phone
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Order not found");
    }
    
    $order = $result->fetch_assoc();
    
    // Fetch order items
    $stmt = $conn->prepare("
        SELECT oi.*, mi.item_name
        FROM order_items oi
        JOIN menu_items mi ON oi.menu_item_id = mi.id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error loading order: " . $e->getMessage();
    header("Location: customer_dashboard.php");
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation | Kafèa-Kiosk</title>
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
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .order-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .order-header {
            border-bottom: 2px solid var(--light-color);
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .order-status {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            margin-top: 0.5rem;
        }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-preparing { background-color: #cce5ff; color: #004085; }
        .status-ready { background-color: #d4edda; color: #155724; }
        .status-completed { background-color: #d1e7dd; color: #0f5132; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        .order-items {
            margin: 1.5rem 0;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        .order-total {
            text-align: right;
            font-size: 1.2rem;
            font-weight: bold;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid var(--light-color);
        }
        .customer-info {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 2px solid var(--light-color);
        }
        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: var(--dark-color);
        }
        .back-btn {
            background-color: var(--secondary-color);
            margin-right: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="order-card">
            <div class="order-header">
                <h1>Order Confirmation</h1>
                <p>Order #<?php echo $order_id; ?></p>
                <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>
            
            <div class="order-items">
                <h2>Order Items</h2>
                <?php foreach ($order_items as $item): ?>
                    <div class="order-item">
                        <div>
                            <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                            <br>
                            <small>Quantity: <?php echo $item['quantity']; ?></small>
                        </div>
                        <div>
                            ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="order-total">
                    Total: ₱<?php echo number_format($order['total_amount'], 2); ?>
                </div>
            </div>
            
            <div class="customer-info">
                <h2>Customer Information</h2>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($order['fullname']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                <p><strong>Order Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
            </div>
            
            <div style="margin-top: 2rem;">
                <a href="customer_dashboard.php" class="btn back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Menu
                </a>
                <a href="view_orders.php" class="btn">
                    <i class="fas fa-list"></i> View All Orders
                </a>
            </div>
        </div>
    </div>
</body>
</html> 