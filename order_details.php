<?php
session_start();
require_once 'connection/config.php';

if (!isset($_SESSION['id'])) {
    header("Location: signin.php");
    exit();
}

$user = [
    'fullname' => $_SESSION['user_fullname'] 
        ?? $_SESSION['admin_name'] 
        ?? 'Kafea Kiosk'
];

if (!isset($_GET['id'])) {
    echo "Order ID not provided.";
    exit();
}

$order_id = intval($_GET['id']);

// Get order and user info
$order_sql = "SELECT orders.*, users.fullname 
              FROM orders 
              JOIN users ON orders.user_id = users.id 
              WHERE orders.id = ?";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows === 0) {
    echo "Order not found.";
    exit();
}

$order = $order_result->fetch_assoc();

// Get ordered items
$items_sql = "
    SELECT 
        order_items.quantity, 
        order_items.price, 
        menu_items.item_name
    FROM order_items
    JOIN menu_items ON order_items.menu_item_id = menu_items.id
    WHERE order_items.order_id = ?
";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

// Merge duplicate items by item_name
$order_items = [];
while ($row = $items_result->fetch_assoc()) {
    $item_name = $row['item_name'];
    if (isset($order_items[$item_name])) {
        // Increase quantity and add total price (price * quantity)
        $order_items[$item_name]['quantity'] += $row['quantity'];
        $order_items[$item_name]['price'] += $row['price'] * $row['quantity'];
    } else {
        // Add new item, set price as total for quantity
        $order_items[$item_name] = [
            'item_name' => $item_name,
            'quantity' => $row['quantity'],
            'price' => $row['price'] * $row['quantity'],
        ];
    }
}

// Re-index to numeric array for easier iteration
$order_items = array_values($order_items);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order #<?= $order_id ?> Details</title>
    <style>
        :root {
            --primary-color: #6F4E37;
            --secondary-color: #C4A484;
            --accent-color: #D2B48C;
            --dark-color: #3E2723;
            --light-color: #F5F5DC;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--accent-color);
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
            cursor: pointer;
            border: none;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: var(--accent-color);
        }

        .content {
            max-width: 800px;
            margin: 2rem auto;
            padding: 20px;
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .item-box {
            background: #f0f0f0;
            margin-top: 1rem;
            padding: 10px;
            border-radius: 5px;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 1rem;
            padding: 8px 14px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .back-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="#" class="navbar-brand">
            <img src="assets/img/icon_t.png" alt="Kafèa-Kiosk Logo">
        </a>
        <div class="profile-section">
            <i class="fas fa-user-circle user-icon"></i>
            <span class="username"><?= htmlspecialchars($user['fullname']) ?></span>
            <button class="logout-btn" onclick="confirmLogout()">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </div>

    <div class="content">
        <a href="order_history.php" class="back-btn">&larr; Back to Orders</a>

        <h1>Order #<?= $order_id ?> Details</h1>

        <p><strong>Customer:</strong> <?= htmlspecialchars($order['fullname']) ?></p>
        <p><strong>Date:</strong> <?= htmlspecialchars($order['order_date']) ?></p>
        <p><strong>Total Amount:</strong> ₱<?= number_format($order['total_amount'], 2) ?></p>
        <p><strong>Payment:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($order['delivery_address']) ?></p>
        <p><strong>Contact:</strong> <?= htmlspecialchars($order['contact_number']) ?></p>
        <p><strong>Instructions:</strong> <?= htmlspecialchars($order['special_instructions']) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>

        <h2>Ordered Items</h2>
        <?php if (!empty($order_items)): ?>
            <?php foreach ($order_items as $item): ?>
                <div class="item-box">
                    <p><strong>Item:</strong> <?= htmlspecialchars($item['item_name']) ?></p>
                    <p>Quantity: <?= htmlspecialchars($item['quantity']) ?></p>
                    <p>Price: ₱<?= number_format($item['price'], 2) ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No items found for this order.</p>
        <?php endif; ?>
    </div>

    <script>
        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                fetch('logout.php')
                    .then(response => window.location.href = 'signin.php')
                    .catch(() => window.location.href = 'signin.php');
            }
        }
    </script>
</body>
</html>
