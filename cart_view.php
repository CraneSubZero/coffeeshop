<?php
session_start();
require_once 'connection/config.php';

if (!isset($_SESSION['id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['id'];
$user_fullname = $_SESSION['user_fullname'] ?? '';
$cart_items = [];
$total_amount = 0;

try {
    // Get active cart with items
    $stmt = $conn->prepare("
        SELECT 
            ci.id AS cart_item_id,
            mi.id AS menu_item_id,
            mi.item_name,
            mi.price,
            mi.image_path,
            ci.quantity,
            ci.cup_size,
            ci.sugar_level,
            ci.special_request,
            GROUP_CONCAT(cia.addon_name SEPARATOR ', ') AS addons
        FROM carts c
        JOIN cart_items ci ON c.id = ci.cart_id
        JOIN menu_items mi ON ci.menu_item_id = mi.id
        LEFT JOIN cart_item_addons cia ON cia.cart_item_id = ci.id
        WHERE c.user_id = ? AND c.status = 'active'
        GROUP BY ci.id
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $total_amount += $row['price'] * $row['quantity'];
    }
    $stmt->close();
    
    // Get cart count for navbar
    $cart_count = array_sum(array_column($cart_items, 'quantity'));
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "We're experiencing technical difficulties. Please try again later.";
}

// Handle remove item request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    try {
        $conn->begin_transaction();
        
        $cart_item_id = $_POST['cart_item_id'];
        
        // First delete addons (due to foreign key constraint)
        $stmt = $conn->prepare("DELETE FROM cart_item_addons WHERE cart_item_id = ?");
        $stmt->bind_param("i", $cart_item_id);
        $stmt->execute();
        $stmt->close();
        
        // Then delete the cart item
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ?");
        $stmt->bind_param("i", $cart_item_id);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        
        // Refresh the page to show updated cart
        header("Location: cart_view.php");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Remove item error: " . $e->getMessage());
        $error_message = "Failed to remove item. Please try again.";
    }
}

// Handle update quantity request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    try {
        $cart_item_id = $_POST['cart_item_id'];
        $new_quantity = (int)$_POST['quantity'];
        
        if ($new_quantity < 1) {
            throw new Exception("Quantity must be at least 1");
        }
        
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $cart_item_id);
        $stmt->execute();
        $stmt->close();
        
        // Refresh the page to show updated cart
        header("Location: cart_view.php");
        exit();
        
    } catch (Exception $e) {
        error_log("Update quantity error: " . $e->getMessage());
        $error_message = $e->getMessage();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kafèa-Kiosk | My Cart</title>
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
        
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .cart-table th {
            background-color: var(--primary-color);
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        .cart-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        .cart-table tr:last-child td {
            border-bottom: none;
        }
        
        .cart-item-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .quantity-input {
            width: 50px;
            text-align: center;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .update-btn, .remove-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .update-btn {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .update-btn:hover {
            background-color: var(--accent-color);
        }
        
        .remove-btn {
            background-color: #f44336;
            color: white;
        }
        
        .remove-btn:hover {
            background-color: #d32f2f;
        }
        
        .cart-summary {
            margin-top: 2rem;
            background-color: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .total-amount {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .checkout-btn {
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .checkout-btn:hover {
            background-color: var(--dark-color);
        }
        
        .customization-details {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }
        
        .customization-details p {
            margin: 3px 0;
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
        
        .empty-cart {
            text-align: center;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .empty-cart i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }
        
        .empty-cart p {
            color: #666;
            margin-bottom: 1.5rem;
        }
        
        .continue-shopping {
            padding: 8px 16px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .continue-shopping:hover {
            background-color: var(--dark-color);
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
            <a href="cart_view.php" class="cart-btn">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count"><?php echo $cart_count ?? 0; ?></span>
            </a>
            <button class="logout-btn" onclick="confirmLogout()">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </div>

    <div class="container">
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <h1>Your Cart</h1>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Your cart is empty</h2>
                <p>Browse our menu and add some delicious items to your cart</p>
                <a href="customer_dashboard.php" class="continue-shopping">
                    <i class="fas fa-utensils"></i> Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <?php if (!empty($item['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>" class="cart-item-img">
                                    <?php else: ?>
                                        <img src="assets/img/placeholder.jpg" alt="No image" class="cart-item-img">
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                        <?php if ($item['cup_size'] || $item['sugar_level'] || $item['addons'] || $item['special_request']): ?>
                                            <div class="customization-details">
                                                <?php if ($item['cup_size']): ?>
                                                    <p><strong>Size:</strong> <?php echo htmlspecialchars($item['cup_size']); ?></p>
                                                <?php endif; ?>
                                                <?php if ($item['sugar_level']): ?>
                                                    <p><strong>Sugar:</strong> <?php echo htmlspecialchars($item['sugar_level']); ?></p>
                                                <?php endif; ?>
                                                <?php if ($item['addons']): ?>
                                                    <p><strong>Add-ons:</strong> <?php echo htmlspecialchars($item['addons']); ?></p>
                                                <?php endif; ?>
                                                <?php if ($item['special_request']): ?>
                                                    <p><strong>Note:</strong> <?php echo htmlspecialchars($item['special_request']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <form method="post" class="quantity-control">
                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="quantity-input">
                                    <button type="submit" name="update_quantity" class="update-btn">Update</button>
                                </form>
                            </td>
                            <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                    <button type="submit" name="remove_item" class="remove-btn">
                                        <i class="fas fa-trash-alt"></i> Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="cart-summary">
                <div>
                    <span class="total-amount">Total: ₱<?php echo number_format($total_amount, 2); ?></span>
                </div>
                <div>
                    <a href="customer_dashboard.php" class="continue-shopping" style="margin-right: 10px;">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                    <button class="checkout-btn" onclick="proceedToCheckout()">
                        <i class="fas fa-credit-card"></i> Proceed to Checkout
                    </button>
                </div>
            </div>
        <?php endif; ?>
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
        
        function proceedToCheckout() {
            window.location.href = 'checkout.php';
        }
    </script>
</body>
</html>