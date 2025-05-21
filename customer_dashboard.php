<?php 
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: signin.php");
    exit();
}

require_once 'connection/config.php';

$user_id = $_SESSION['id'];
$user_fullname = '';
$menu_items_by_category = [];

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
    $stmt = $conn->prepare("SELECT id, item_name, description, price, image_path, is_available, category FROM menu_items");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $result = $stmt->get_result();
    $menu_items = [];
    while ($row = $result->fetch_assoc()) {
        $menu_items[] = $row;
    }
    $stmt->close();

    // Group menu items by category
    foreach ($menu_items as $row) {
        $category = $row['category'] ?? 'Uncategorized';
        $menu_items_by_category[$category][] = $row;
    }

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "We're experiencing technical difficulties. Please try again later.";
}

// Get order status message
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
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* reduced min width from 250px to 200px */
    gap: 1rem; /* reduce gap slightly for tighter layout */
    margin-top: 2rem;
}

.menu-card {
    background-color: white;
    border-radius: 8px;
    padding: 1rem; /* reduce padding */
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.3s;
}

.menu-card:hover {
    transform: translateY(-5px);
}

.menu-card img {
    max-width: 100%;
    height: 120px; /* reduce image height */
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 0.8rem; /* reduce margin */
}

.menu-card h3 {
    margin-bottom: 0.3rem; /* reduce margin */
    color: var(--primary-color);
    font-size: 1.1rem; /* reduce font size */
}

.menu-card p {
    font-size: 0.8rem; /* reduce font size */
    color: #555;
    margin-bottom: 0.5rem;
}

.price {
    display: inline-block;
    margin-top: 0.8rem; /* reduce margin */
    font-weight: bold;
    color: var(--dark-color);
    font-size: 1rem; /* reduce font size */
}

.add-to-cart {
    margin-top: 8px;
    padding: 4px 8px; /* smaller padding */
    font-size: 0.9rem; /* smaller text */
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

.customization-form .form-group {
    margin-bottom: 10px;
    display: flex;
    flex-direction: column;
    text-align: left;
}

.customization-form label {
    font-weight: 500;
    margin-bottom: 4px;
    color: var(--dark-color);
}

.customization-form select,
.customization-form input[type="text"],
.customization-form input[type="number"] {
    padding: 8px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 0.9rem;
    transition: border-color 0.3s;
}

.customization-form select:focus,
.customization-form input:focus {
    border-color: var(--primary-color);
    outline: none;
}

.customization-form small {
    font-size: 0.75rem;
    color: #666;
    margin-top: 2px;
}

.modern-btn {
    padding: 8px 14px;
    background-color: var(--primary-color);
    color: #fff;
    font-weight: bold;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.modern-btn:hover {
    background-color: #5c3c28;
}

.modern-form {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
    align-items: center;
    margin-top: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    width: 100%;
}

.form-group label {
    font-size: 0.85rem;
    margin-bottom: 4px;
    color: var(--primary-color);
}

.modern-input {
    padding: 6px 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 0.9rem;
    width: 100%;
    max-width: 120px;
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
    transition: border-color 0.3s;
}

.modern-input:focus {
    border-color: var(--primary-color);
    outline: none;
}

.modern-button {
    padding: 8px 14px;
    font-size: 0.9rem;
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.modern-button:hover {
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

    <?php 
    $category_order = ['Coffee', 'Pastry']; 
    $printed_categories = [];

    if (!empty($menu_items_by_category)): 
        foreach ($category_order as $ordered_category) {
            if (isset($menu_items_by_category[$ordered_category])) {
                $printed_categories[] = $ordered_category;
                ?>
                <h2><?php echo htmlspecialchars($ordered_category); ?></h2>
                <div class="menu-grid">
             <?php foreach ($menu_items_by_category[$ordered_category] as $item): ?>
    <div class="menu-card" data-id="<?php echo htmlspecialchars($item['id']); ?>" style="<?php echo !$item['is_available'] ? 'opacity: 0.6;' : ''; ?>">
        <?php if (!empty($item['image_path'])): ?>
            <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
        <?php else: ?>
            <img src="assets/img/placeholder.jpg" alt="No image available">
        <?php endif; ?>
        <h3><?php echo htmlspecialchars($item['item_name']); ?></h3>
        <p><?php echo htmlspecialchars($item['description']); ?></p>
        <span class="price">₱<?php echo number_format($item['price'], 2); ?></span>
        
        <?php if ($item['is_available']): ?>
            <?php if ($ordered_category === 'Coffee'): ?>
                <!-- Coffee customization form -->
              <form class="customization-form" onsubmit="event.preventDefault(); addToCartWithOptions(this);">
    <div class="form-group">
        <label for="cup_size">Cup Size</label>
        <select name="cup_size" required>
            <option value="" disabled selected>Choose cup size</option>
            <option value="Small">Small</option>
            <option value="Medium">Medium</option>
            <option value="Large">Large</option>
        </select>
    </div>

    <div class="form-group">
        <label for="sugar_level">Sugar Level</label>
        <select name="sugar_level" required>
            <option value="" disabled selected>Choose sugar level</option>
            <option value="No sugar">No sugar</option>
            <option value="Less sugar">Less sugar</option>
            <option value="Regular">Regular</option>
            <option value="Extra sugar">Extra sugar</option>
        </select>
    </div>

    <div class="form-group">
        <label for="add_ons">Add-ons</label>
        <select name="add_ons" multiple size="3">
            <option value="Extra shot">Extra shot</option>
            <option value="Whipped cream">Whipped cream</option>
            <option value="Syrup">Syrup</option>
        </select>
        <small>(Hold Ctrl or Cmd to select multiple)</small>
    </div>

    <div class="form-group">
        <label for="special_request">Special Request</label>
        <input type="text" name="special_request" placeholder="Any special instructions?">
    </div>

    <div class="form-group">
        <label for="quantity">Quantity</label>
        <input type="number" name="quantity" min="1" value="1" required>
    </div>

    <button type="submit" class="modern-btn">Add to Cart</button>
</form>

            <?php elseif ($ordered_category === 'Pastry'): ?>
                <!-- Pastry quantity form -->
             <form class="modern-form" onsubmit="event.preventDefault(); addToCartWithQuantity(this);">
    <div class="form-group">
        <label for="quantity-<?php echo $item['id']; ?>">Quantity</label>
        <input 
            type="number" 
            name="quantity" 
            id="quantity-<?php echo $item['id']; ?>" 
            min="1" 
            value="1" 
            required 
            class="modern-input"
        >
    </div>
    <button type="submit" class="modern-button">
        <i class="fas fa-cart-plus"></i> Add to Cart
    </button>
</form>

            <?php else: ?>
                <!-- Default add to cart button for other categories -->
                <button class="add-to-cart" style="margin-top: 10px; padding: 5px 10px; background: var(--primary-color); color: white; border: none; border-radius: 4px; cursor: pointer;" onclick="addToCart('<?php echo htmlspecialchars($item['id']); ?>')">
                    Add to Cart
                </button>
            <?php endif; ?>
        <?php else: ?>
            <div style="margin-top: 10px; padding: 5px 10px; background: #ccc; color: #333; border-radius: 4px;">
                Not Available
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>


                </div>
                <?php
            }
        }

        // Print remaining categories
        foreach ($menu_items_by_category as $category => $items) {
            if (!in_array($category, $printed_categories)) {
                ?>
                <h2><?php echo htmlspecialchars($category); ?></h2>
                <div class="menu-grid">
                    <?php foreach ($items as $item): ?>
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
                </div>
                <?php
            }
        }
    else: ?>
        <p>No menu items available at the moment.</p>
    <?php endif; ?>
</div>

        </div>
    </div>

  <script>
document.addEventListener('DOMContentLoaded', () => {
    // Add to cart functionality
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const menuItemId = this.closest('.menu-card').dataset.id;
            addToCart(menuItemId);
        });
    });
});

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


function addToCart(itemId) {
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `item_id=${encodeURIComponent(itemId)}&quantity=1`
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message || 'Item added to cart!');
        location.reload();
    })
    .catch(error => {
        console.error('Add to cart failed:', error);
        alert('Something went wrong. Try again.');
    });
}

function addToCartWithOptions(form) {
    const itemId = form.closest('.menu-card').dataset.id;
    const formData = new FormData(form);
    formData.append('item_id', itemId);
    formData.append('type', 'coffee');

    fetch('add_to_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message || 'Coffee item added to cart!');
        location.reload();
    })
    .catch(error => {
        console.error('Add to cart with options failed:', error);
        alert('Failed to add item. Please try again.');
    });
}

function addToCartWithQuantity(form) {
    const itemId = form.closest('.menu-card').dataset.id;
    const quantity = form.querySelector('input[name="quantity"]').value;

    fetch('add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `item_id=${encodeURIComponent(itemId)}&quantity=${encodeURIComponent(quantity)}`
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message || 'Item added to cart!');
        location.reload();
    })
    .catch(error => {
        console.error('Add to cart with quantity failed:', error);
        alert('Something went wrong. Try again.');
    });
}
</script>



</body>
</html>