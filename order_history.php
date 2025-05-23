<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: signin.php");
    exit();
}

require_once 'connection/config.php';

$user_id = $_SESSION['id'];
$user_fullname = $_SESSION['user_fullname'] ?? 'Guest';

// Get total quantity of items in the cart
$cart_count = 0;
try {
    $stmt = $conn->prepare("
        SELECT SUM(ci.quantity) as total_quantity 
        FROM cart_items ci
        JOIN carts c ON ci.cart_id = c.id
        WHERE c.user_id = ? AND c.status = 'active'
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_data = $result->fetch_assoc();
    $cart_count = $cart_data['total_quantity'] ?? 0;
    $stmt->close();
} catch (Exception $e) {
    error_log("Cart count error: " . $e->getMessage());
}

// Fetch all orders by this user
$order_sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC";
$stmt = $conn->prepare($order_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History | Kafèa-Kiosk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6F4E37;
            --secondary-color: #C4A484;
            --accent-color: #D2B48C;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5dc;
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
        }

        .logout-btn:hover {
            background-color: var(--accent-color);
        }

        .cart-btn, .history-btn {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .cart-count {
            background: #fff;
            color: #6F4E37;
            padding: 0 6px;
            border-radius: 12px;
            font-weight: bold;
        }

        .content {
            max-width: 900px;
            margin: 2rem auto;
            padding: 20px;
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .order-card {
            border: 1px solid #ddd;
            border-left: 5px solid var(--primary-color);
            padding: 15px;
            margin-bottom: 20px;
            background-color: #fdfcfb;
            border-radius: 5px;
        }

        .order-card p {
            margin: 5px 0;
        }

        .order-link {
            display: inline-block;
            margin-top: 10px;
            color: #007bff;
            text-decoration: none;
        }

        .order-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
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

    <!-- Order History Content -->
    <div class="content">
        <h1>My Order History</h1>

        <?php if (count($orders) > 0): ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <p><strong>Order ID:</strong> <?= $order['id'] ?></p>
                    <p><strong>Date:</strong> <?= htmlspecialchars($order['order_date']) ?></p>
                    <p><strong>Total Amount:</strong> ₱<?= number_format($order['total_amount'], 2) ?></p>
                    <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>
                    <a class="order-link" href="order_details.php?id=<?= $order['id'] ?>">View Details →</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>You have no orders yet.</p>
        <?php endif; ?>
    </div>

    <script>
        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                fetch('logout.php')
                    .then(() => window.location.href = 'signin.php')
                    .catch(() => window.location.href = 'signin.php');
            }
        }
    </script>
</body>
</html>
