<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: signin.php");
    exit();
}

require_once 'connection/config.php';

$user_id = $_SESSION['id'];
$user_fullname = $_SESSION['user_fullname'] ?? '';
$error_message = '';
$orders = [];

try {
    // Fetch user's order history
    $stmt = $conn->prepare("
        SELECT 
            c.id AS cart_id,
            c.created_at AS order_date,
            c.status AS order_status,
            c.total_price,
            c.payment_method,
            c.payment_status
        FROM carts c
        WHERE c.user_id = ?
        AND c.status != 'active'
        ORDER BY c.created_at DESC
    ");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $result = $stmt->get_result();
    
    while ($order = $result->fetch_assoc()) {
        $order_id = $order['cart_id'];
        
        // Fetch items for each order
        $item_stmt = $conn->prepare("
            SELECT 
                ci.id,
                ci.menu_item_id,
                ci.quantity,
                ci.price,
                ci.cup_size,
                ci.sugar_level,
                ci.special_request,
                mi.item_name,
                mi.image_path
            FROM cart_items ci
            JOIN menu_items mi ON ci.menu_item_id = mi.id
            WHERE ci.cart_id = ?
        ");
        $item_stmt->bind_param("i", $order_id);
        $item_stmt->execute();
        $items_result = $item_stmt->get_result();
        $order_items = [];
        
        while ($item = $items_result->fetch_assoc()) {
            // Fetch addons for each item
            $addon_stmt = $conn->prepare("
                SELECT addon_name 
                FROM cart_item_addons 
                WHERE cart_item_id = ?
            ");
            $addon_stmt->bind_param("i", $item['id']);
            $addon_stmt->execute();
            $addons_result = $addon_stmt->get_result();
            $addons = [];
            
            while ($addon = $addons_result->fetch_assoc()) {
                $addons[] = $addon['addon_name'];
            }
            
            $item['addons'] = $addons;
            $order_items[] = $item;
            $addon_stmt->close();
        }
        
        $order['items'] = $order_items;
        $orders[] = $order;
        $item_stmt->close();
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "We're experiencing technical difficulties. Please try again later.";
}

// Get total quantity of items in the cart
$cart_count = 0;
try {
    $stmt = $conn->prepare("
        SELECT SUM(ci.quantity) as total_quantity 
        FROM cart_items ci
        JOIN carts c ON ci.cart_id = c.id
        WHERE c.user_id = ? AND c.status = 'active'
    ");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $result = $stmt->get_result();
    $cart_data = $result->fetch_assoc();
    $cart_count = $cart_data['total_quantity'] ?? 0;
    $stmt->close();
} catch (Exception $e) {
    error_log("Cart count error: " . $e->getMessage());
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kafèa-Kiosk | Order History</title>
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

        .navbar {
            background-color: var(--primary-color);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand img {
            height: 50px;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
        }

        .user-icon {
            font-size: 1.2rem;
            color: white;
            margin-right: 8px;
        }

        .username {
            font-weight: 500;
        }

        .logout-btn {
            background-color: var(--secondary-color);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
            cursor: pointer;
            border: none;
        }

        .logout-btn:hover {
            background-color: var(--accent-color);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        h1, h2 {
            color: var(--primary-color);
        }

        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        
        .success-message {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }

        .cart-btn {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .cart-btn:hover {
            opacity: 0.8;
        }

        .history-btn {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .history-btn:hover {
            opacity: 0.8;
        }

        /* Order History Styles */
        .order-list {
            margin-top: 2rem;
        }

        .order-card {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .order-id {
            font-weight: bold;
            color: var(--primary-color);
        }

        .order-date {
            color: #666;
            font-size: 0.9rem;
        }

        .order-status {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .order-details {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
        }

        .order-items {
            flex: 1;
        }

        .order-summary {
            width: 250px;
            margin-left: 2rem;
            padding-left: 2rem;
            border-left: 1px solid #eee;
        }

        .order-item {
            display: flex;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f5f5f5;
        }

        .order-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 1rem;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: bold;
            margin-bottom: 0.3rem;
            color: var(--primary-color);
        }

        .item-price {
            color: var(--dark-color);
            font-weight: bold;
        }

        .item-quantity {
            color: #666;
            font-size: 0.9rem;
        }

        .item-customization {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #666;
        }

        .customization-label {
            font-weight: bold;
            color: #555;
        }

        .addon-list {
            display: inline;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .addon-list li {
            display: inline;
            margin-right: 0.5rem;
        }

        .addon-list li:after {
            content: ", ";
        }

        .addon-list li:last-child:after {
            content: "";
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .summary-label {
            color: #666;
        }

        .summary-value {
            font-weight: bold;
        }

        .total-row {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
            font-size: 1.1rem;
        }

        .payment-method {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .no-orders {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-orders i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ccc;
        }

        @media (max-width: 768px) {
            .order-details {
                flex-direction: column;
            }
            
            .order-summary {
                width: 100%;
                margin-left: 0;
                padding-left: 0;
                border-left: none;
                margin-top: 1.5rem;
                padding-top: 1.5rem;
                border-top: 1px solid #eee;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-status {
                margin-top: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <img src="assets/img/icon_t.png" alt="Kafèa-Kiosk Logo">
        </div>
        <div class="profile-section">
            <i class="fas fa-user-circle user-icon"></i>
            <span class="username"><?php echo htmlspecialchars($user_fullname); ?></span>
            <a href="customer_dashboard.php" class="history-btn">
                <i class="fas fa-home"></i> Menu
            </a>
            <a href="cart_view.php" class="cart-btn">   
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count"><?php echo $cart_count; ?></span>
            </a>
            <button class="logout-btn" onclick="confirmLogout()">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </div>

    <div class="container">
        <h1>Your Order History</h1>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <div class="order-list">
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span class="order-id">Order #<?php echo htmlspecialchars($order['cart_id']); ?></span>
                                <span class="order-date"><?php echo date('F j, Y \a\t g:i A', strtotime($order['order_date'])); ?></span>
                            </div>
                            <span class="order-status status-<?php echo strtolower($order['order_status']); ?>">
                                <?php echo htmlspecialchars($order['order_status']); ?>
                            </span>
                        </div>
                        
                        <div class="order-details">
                            <div class="order-items">
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="order-item">
                                        <?php if (!empty($item['image_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>" class="item-image">
                                        <?php else: ?>
                                            <img src="assets/img/placeholder.jpg" alt="No image available" class="item-image">
                                        <?php endif; ?>
                                        
                                        <div class="item-details">
                                            <div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                            <div class="item-price">₱<?php echo number_format($item['price'], 2); ?></div>
                                            <div class="item-quantity">Quantity: <?php echo htmlspecialchars($item['quantity']); ?></div>
                                            
                                            <?php if ($item['cup_size'] || $item['sugar_level'] || !empty($item['addons']) || $item['special_request']): ?>
                                                <div class="item-customization">
                                                    <?php if ($item['cup_size']): ?>
                                                        <div><span class="customization-label">Size:</span> <?php echo htmlspecialchars($item['cup_size']); ?></div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($item['sugar_level']): ?>
                                                        <div><span class="customization-label">Sugar:</span> <?php echo htmlspecialchars($item['sugar_level']); ?></div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($item['addons'])): ?>
                                                        <div>
                                                            <span class="customization-label">Add-ons:</span>
                                                            <ul class="addon-list">
                                                                <?php foreach ($item['addons'] as $addon): ?>
                                                                    <li><?php echo htmlspecialchars($addon); ?></li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($item['special_request']): ?>
                                                        <div><span class="customization-label">Note:</span> <?php echo htmlspecialchars($item['special_request']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="order-summary">
                                <div class="summary-row">
                                    <span class="summary-label">Subtotal:</span>
                                    <span class="summary-value">₱<?php echo number_format($order['total_price'], 2); ?></span>
                                </div>
                                
                                <div class="summary-row total-row">
                                    <span class="summary-label">Total:</span>
                                    <span class="summary-value">₱<?php echo number_format($order['total_price'], 2); ?></span>
                                </div>
                                
                                <div class="payment-method">
                                    <div class="summary-row">
                                        <span class="summary-label">Payment Method:</span>
                                        <span class="summary-value"><?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label">Payment Status:</span>
                                        <span class="summary-value"><?php echo htmlspecialchars($order['payment_status'] ?? 'N/A'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-orders">
                    <i class="fas fa-history"></i>
                    <h3>No orders yet</h3>
                    <p>Your order history will appear here once you place an order.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                fetch('logout.php')
                    .then(response => {
                        window.location.href = 'signin.php';
                    })
                    .catch(error => {
                        console.error('Logout error:', error);
                        window.location.href = 'signin.php';
                    });
            }
        }
    </script>
</body>
</html>