<?php 
session_start();

// Redirect if not logged in
if (!isset($_SESSION['id'])) {
    header("Location: signin.php");
    exit();
}

// Database connection
require_once 'connection/config.php';

$user_id = $_SESSION['id'];
$user_fullname = '';
$menu_items = [];

try {
    // Fetch user info
    $stmt = $conn->prepare("SELECT fullname FROM users WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        $user_fullname = $user_data['fullname'] ?? '';
        $_SESSION['user_fullname'] = $user_fullname;
    }
    $stmt->close();

    // Fetch menu items
    $stmt = $conn->prepare("SELECT id, item_name, description, price, image_path, is_available FROM menu_items");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $menu_items[] = $row;
    }
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    // You might want to show a user-friendly message
    $error_message = "We're experiencing technical difficulties. Please try again later.";
} finally {

}
// Check for order status message
$order_status = $_SESSION['order_status'] ?? null;
if (isset($_SESSION['order_status'])) {
    unset($_SESSION['order_status']);
}

// Get cart item count
$cart_count = 0;
try {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM cart_items ci
        JOIN carts c ON ci.cart_id = c.id
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_count = $result->fetch_assoc()['count'] ?? 0;
    $stmt->close();
} catch (Exception $e) {
    error_log("Cart count error: " . $e->getMessage());
}

// ✅ Corrected: close connection AFTER all DB operations
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kafèa-Kiosk | Customer Dashboard</title>
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

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .menu-card {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .menu-card:hover {
            transform: translateY(-5px);
        }

        .menu-card img {
            max-width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .menu-card h3 {
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .menu-card p {
            font-size: 0.9rem;
            color: #555;
        }

        .price {
            display: inline-block;
            margin-top: 1rem;
            font-weight: bold;
            color: var(--dark-color);
            font-size: 1.1rem;
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
        <span class="cart-count"><?php echo $cart_count; ?></span>
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
        
        <h1>Welcome, <?php echo htmlspecialchars($user_fullname); ?>!</h1>
        <?php if ($order_status): ?>
   <div class="<?php echo $order_status['success'] ? 'success-message' : 'error-message'; ?>">

        <?php echo htmlspecialchars($order_status['message']); ?>
    </div>
<?php endif; ?>
        <h2>Coffee Menu</h2>

        <div class="menu-grid">
            <?php if (!empty($menu_items)): ?>
                <?php foreach ($menu_items as $item): ?>
<div class="menu-card" data-id="<?php echo htmlspecialchars($item['id']); ?>" style="<?php echo !$item['is_available'] ? 'opacity: 0.6;' : ''; ?>">

                        <?php if (!empty($item['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                        <?php else: ?>
                            <img src="assets/img/placeholder.jpg" alt="No image available">
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($item['item_name']); ?></h3>
                        <p><?php echo htmlspecialchars($item['description']); ?></p>
                        <span class="price">₱<?php echo number_format($item['price'], 2); ?></span>
                        <br>
                       <?php if ($item['is_available']): ?>
    <button class="add-to-cart" style="margin-top: 10px; padding: 5px 10px; background: var(--primary-color); color: white; border: none; border-radius: 4px; cursor: pointer;">
        Add to Cart
    </button>
<?php else: ?>
    <div style="margin-top: 10px; padding: 5px 10px; background: #ccc; color: #333; border-radius: 4px;">
        Not Available
    </div>
<?php endif; ?>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No menu items available at the moment.</p>
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

        // Add to cart functionality
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const menuItemId = this.closest('.menu-card').dataset.id;
                addToCart(menuItemId);
            });
        });

        function addToCart(itemId) {
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ item_id: itemId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Item added to cart!');
                } else {
                    alert('Error: ' + (data.message || 'Failed to add item to cart'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding to cart');
            });
        }
    </script>
</body>
</html>