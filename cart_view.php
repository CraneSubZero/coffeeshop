<?php
session_start();
require_once 'connection/config.php';

// Redirect if not logged in
if (!isset($_SESSION['id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['id'];
$user_fullname = $_SESSION['user_fullname'] ?? '';
$cart_items = [];
$total = 0;

// Fetch cart items from database
try {
    $stmt = $conn->prepare("
        SELECT ci.id as cart_item_id, mi.id as menu_item_id, mi.name, mi.price, ci.quantity, mi.image_url
        FROM cart_items ci
        JOIN menu_items mi ON ci.menu_item_id = mi.id
        JOIN carts c ON ci.cart_id = c.id
        WHERE c.user_id = ?
        ORDER BY ci.added_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_items = $result->fetch_all(MYSQLI_ASSOC);
    
    // Calculate total
    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
} catch (Exception $e) {
    error_log("Cart view error: " . $e->getMessage());
    $error = "Error loading cart";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart | Kafèa-Kiosk</title>
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

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2rem 0;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .cart-table th {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            text-align: left;
        }

        .cart-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .cart-table tr:last-child td {
            border-bottom: none;
        }

        .cart-item-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .quantity-control {
            display: flex;
            align-items: center;
        }

        .quantity-btn {
            background: var(--secondary-color);
            color: white;
            border: none;
            width: 25px;
            height: 25px;
            border-radius: 4px;
            cursor: pointer;
        }

        .quantity-input {
            width: 40px;
            text-align: center;
            margin: 0 0.5rem;
            padding: 0.3rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .remove-btn {
            color: #dc3545;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .total-row {
            font-weight: bold;
            background-color: var(--light-color);
        }

        .checkout-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
            float: right;
        }

        .checkout-btn:hover {
            background-color: var(--dark-color);
        }

        .empty-cart {
            text-align: center;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .back-btn {
            background-color: var(--secondary-color);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
            margin-right: 1rem;
        }

        .back-btn:hover {
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
            <a href="customer_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Menu
            </a>
            <button class="logout-btn" onclick="confirmLogout()">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </div>

    <div class="container">
        <h1>Your Cart</h1>
        
        <?php if (!empty($cart_items)): ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <?php if (!empty($item['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-img">
                                    <?php else: ?>
                                        <img src="assets/img/placeholder.jpg" alt="No image" class="cart-item-img">
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($item['name']); ?></span>
                                </div>
                            </td>
                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <div class="quantity-control">
                                    <button class="quantity-btn minus-btn" data-id="<?php echo $item['cart_item_id']; ?>">-</button>
                                    <input type="number" class="quantity-input" value="<?php echo $item['quantity']; ?>" min="1" data-id="<?php echo $item['cart_item_id']; ?>">
                                    <button class="quantity-btn plus-btn" data-id="<?php echo $item['cart_item_id']; ?>">+</button>
                                </div>
                            </td>
                            <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            <td>
                                <button class="remove-btn" data-id="<?php echo $item['cart_item_id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="3" align="right"><strong>Total:</strong></td>
                        <td><strong>₱<?php echo number_format($total, 2); ?></strong></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
            
            <form action="checkout.php" method="POST">
                <button type="submit" class="checkout-btn">
                    <i class="fas fa-shopping-bag"></i> Proceed to Purchase
                </button>
            </form>
            
            <div style="clear: both;"></div>
        <?php else: ?>
            <div class="empty-cart">
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any items yet.</p>
                <a href="customer_dashboard.php" class="checkout-btn" style="display: inline-block; text-decoration: none;">
                    <i class="fas fa-utensils"></i> Browse Menu
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Quantity controls
        document.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.parentElement.querySelector('.quantity-input');
                let value = parseInt(input.value);
                const cartItemId = this.dataset.id;
                
                if (this.classList.contains('minus-btn')) {
                    if (value > 1) value--;
                } else {
                    value++;
                }
                
                input.value = value;
                updateCartItem(cartItemId, value);
            });
        });
        
        // Quantity input change
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const value = parseInt(this.value);
                const cartItemId = this.dataset.id;
                
                if (value < 1) {
                    this.value = 1;
                    updateCartItem(cartItemId, 1);
                } else {
                    updateCartItem(cartItemId, value);
                }
            });
        });
        
        // Remove item
        document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (confirm('Are you sure you want to remove this item from your cart?')) {
                    removeCartItem(this.dataset.id);
                }
            });
        });
        
        function updateCartItem(cartItemId, quantity) {
            fetch('update_cart_item.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart_item_id: cartItemId,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Error updating cart: ' + (data.message || 'Unknown error'));
                    location.reload();
                } else {
                    location.reload(); // Refresh to show updated totals
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating cart');
            });
        }
        
        function removeCartItem(cartItemId) {
            fetch('remove_cart_item.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart_item_id: cartItemId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error removing item: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while removing item');
            });
        }
        
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