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
$error_message = '';
$success_message = '';

try {
    // Get active cart with items
    $stmt = $conn->prepare("
        SELECT 
            ci.id AS cart_item_id,
            mi.id AS menu_item_id,
            mi.item_name,
            mi.price,
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
    
    // Check if cart is empty
    if (empty($cart_items)) {
        header("Location: cart_view.php");
        exit();
    }

    // Process checkout form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $conn->begin_transaction();
        
        try {
            $payment_method = $_POST['payment_method'] ?? '';
            $delivery_address = $_POST['delivery_address'] ?? '';
            $contact_number = $_POST['contact_number'] ?? '';
            $special_instructions = $_POST['special_instructions'] ?? '';
            
            // Validate inputs
            if (empty($payment_method) || empty($delivery_address) || empty($contact_number)) {
                throw new Exception("Please fill in all required fields");
            }
            
            if (!preg_match('/^[0-9]{10,15}$/', $contact_number)) {
                throw new Exception("Please enter a valid contact number");
            }
            
            // 1. Create a new order
            $stmt = $conn->prepare("
                INSERT INTO orders (user_id, total_amount, payment_method, delivery_address, contact_number, special_instructions)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "idssss", 
                $user_id, 
                $total_amount, 
                $payment_method, 
                $delivery_address, 
                $contact_number, 
                $special_instructions
            );
            $stmt->execute();
            $order_id = $stmt->insert_id;
            $stmt->close();
            
            // 2. Move cart items to order items
            foreach ($cart_items as $item) {
                $stmt = $conn->prepare("
                    INSERT INTO order_items (
                        order_id, 
                        menu_item_id, 
                        item_name, 
                        quantity, 
                        price, 
                        cup_size, 
                        sugar_level, 
                        addons, 
                        special_request
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    "iisidssss", 
                    $order_id,
                    $item['menu_item_id'],
                    $item['item_name'],
                    $item['quantity'],
                    $item['price'],
                    $item['cup_size'],
                    $item['sugar_level'],
                    $item['addons'],
                    $item['special_request']
                );
                $stmt->execute();
                $stmt->close();
            }
            
            // 3. Mark cart as completed
            $stmt = $conn->prepare("
                UPDATE carts SET status = 'completed' 
                WHERE user_id = ? AND status = 'active'
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            
            // Set success message and redirect
            $_SESSION['order_status'] = [
                'success' => true,
                'message' => "Order #$order_id placed successfully! Your food will be prepared soon."
            ];
            header("Location: customer_dashboard.php");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = $e->getMessage();
        }
    }
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "We're experiencing technical difficulties. Please try again later.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kafèa-Kiosk | Checkout</title>
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
        
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .order-summary, .checkout-form {
            background-color: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .order-items {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 1.5rem;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-name {
            font-weight: 500;
        }
        
        .item-price {
            color: var(--primary-color);
        }
        
        .item-quantity {
            color: #666;
            font-size: 0.9rem;
        }
        
        .customization-details {
            font-size: 0.8rem;
            color: #666;
            margin-top: 5px;
        }
        
        .total-summary {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--primary-color);
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .grand-total {
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .radio-group {
            margin: 10px 0;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .radio-option input {
            margin-right: 10px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .submit-btn:hover {
            background-color: var(--dark-color);
        }
        
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
        }
        .back-to-cart-btn {
    display: inline-block;
    background-color: var(--secondary-color);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    transition: background-color 0.3s ease;
    margin-bottom: 1rem;
}

.back-to-cart-btn:hover {
    background-color: var(--accent-color);
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
            <button class="logout-btn" onclick="confirmLogout()">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </div>

    <div class="container">
        <a href="cart_view.php" class="back-to-cart-btn">
    <i class="fas fa-arrow-left"></i> Back to Cart
</a>

        <h1>Checkout</h1>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <div class="checkout-grid">
            <div class="order-summary">
                <h2>Order Summary</h2>
                <div class="order-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="order-item">
                            <div>
                                <div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                <div class="item-quantity">Qty: <?php echo $item['quantity']; ?></div>
                                <?php if ($item['cup_size'] || $item['sugar_level'] || $item['addons'] || $item['special_request']): ?>
                                    <div class="customization-details">
                                        <?php if ($item['cup_size']): ?>
                                            <div><strong>Size:</strong> <?php echo htmlspecialchars($item['cup_size']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($item['sugar_level']): ?>
                                            <div><strong>Sugar:</strong> <?php echo htmlspecialchars($item['sugar_level']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($item['addons']): ?>
                                            <div><strong>Add-ons:</strong> <?php echo htmlspecialchars($item['addons']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($item['special_request']): ?>
                                            <div><strong>Note:</strong> <?php echo htmlspecialchars($item['special_request']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="item-price">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="total-summary">
                    <div class="total-row grand-total">
                        <span>Total Amount:</span>
                        <span>₱<?php echo number_format($total_amount, 2); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="checkout-form">
                <h2>Payment & Delivery</h2>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Payment Method *</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="cash" name="payment_method" value="Cash on Delivery" checked>
                                <label for="cash">Cash on Delivery</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="gcash" name="payment_method" value="GCash">
                                <label for="gcash">GCash</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="card" name="payment_method" value="Credit/Debit Card">
                                <label for="card">Credit/Debit Card</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="delivery_address" class="form-label">Delivery Address *</label>
                        <input type="text" id="delivery_address" name="delivery_address" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_number" class="form-label">Contact Number *</label>
                        <input type="tel" id="contact_number" name="contact_number" class="form-control" 
                               placeholder="e.g. 09123456789" pattern="[0-9]{10,15}" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="special_instructions" class="form-label">Special Instructions</label>
                        <textarea id="special_instructions" name="special_instructions" class="form-control" 
                                  rows="3" placeholder="Any special delivery instructions?"></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-check-circle"></i> Place Order
                    </button>
                </form>
            </div>
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