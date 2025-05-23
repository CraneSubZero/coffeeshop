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
$error_message = ''; // Initialize error message variable

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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .menu-card {
            background-color: white;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .menu-card:hover {
            transform: translateY(-5px);
        }

        .menu-card img {
            max-width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 0.8rem;
        }

        .menu-card h3 {
            margin-bottom: 0.3rem;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .menu-card p {
            font-size: 0.8rem;
            color: #555;
            margin-bottom: 0.5rem;
        }

        .price {
            display: inline-block;
            margin-top: 0.8rem;
            font-weight: bold;
            color: var(--dark-color);
            font-size: 1rem;
        }

        .add-to-cart {
            margin-top: 8px;
            padding: 4px 8px;
            font-size: 0.9rem;
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
  .search-container {
        margin: 20px 0;
        display: flex;
        justify-content: center;
    }
    
    .search-bar {
        width: 100%;
        max-width: 500px;
        padding: 10px 15px;
        border: 2px solid var(--primary-color);
        border-radius: 25px;
        font-size: 1rem;
        outline: none;
        transition: all 0.3s;
    }
    
    .search-bar:focus {
        box-shadow: 0 0 8px rgba(111, 78, 55, 0.3);
    }
    
    .no-results {
        text-align: center;
        padding: 20px;
        color: var(--dark-color);
        font-style: italic;
    }
    
    .hidden {
        display: none;
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
            <a href="order_history.php" class="history-btn">
                <i class="fas fa-history"></i> History
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
        <div class="search-container">
    <input type="text" id="searchInput" class="search-bar" placeholder="Search for drinks, pastries..." oninput="searchMenuItems()">
</div>
        <?php if (!empty($error_message)): ?>
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
                                        <form class="customization-form" onsubmit="addCoffeeToCart(event, this);">
                                            <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item['id']); ?>">
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
                                                <select name="add_ons[]" multiple size="3">
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
                                        <form class="modern-form" onsubmit="event.preventDefault(); addToCartWithQuantity(event, this);">
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
                                        <button class="add-to-cart" onclick="addToCart('<?php echo htmlspecialchars($item['id']); ?>')">
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
                                    <button class="add-to-cart" onclick="addToCart('<?php echo htmlspecialchars($item['id']); ?>')">
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Add to cart functionality for simple items
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

        function addToCart(itemId, quantity = 1) {
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `item_id=${encodeURIComponent(itemId)}&quantity=${encodeURIComponent(quantity)}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Item added to cart!');
                    updateCartCount();
                } else {
                    alert(data.message || 'Failed to add item to cart');
                }
            })
            .catch(error => {
                console.error('Add to cart failed:', error);
                alert('Something went wrong. Please try again.');
            });
        }

        function addToCartWithQuantity(event, form) {
            event.preventDefault();
            const itemId = form.closest('.menu-card').dataset.id;
            const quantityInput = form.querySelector('input[name="quantity"]');
            const quantity = parseInt(quantityInput.value);
            
            if (isNaN(quantity) || quantity < 1) {
                alert("Please enter a valid quantity");
                quantityInput.focus();
                return;
            }
            
            addToCart(itemId, quantity);
        }

        function updateCartCount() {
            fetch('get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelectorAll('.cart-count').forEach(el => {
                            el.textContent = data.count;
                        });
                    }
                })
                .catch(error => {
                    console.error('Failed to update cart count:', error);
                });
        }

        function addCoffeeToCart(event, form) {
            event.preventDefault();
            
            // Get all form data
            const formData = new FormData(form);
            const itemId = formData.get('item_id');
            const cupSize = formData.get('cup_size');
            const sugarLevel = formData.get('sugar_level');
            const addOns = formData.getAll('add_ons[]');
            const specialRequest = formData.get('special_request');
            const quantity = formData.get('quantity');

            // Validate required fields
            if (!cupSize || !sugarLevel || !quantity || quantity < 1) {
                alert("Please fill out all required fields correctly.");
                return;
            }

            // Prepare the data to send
            const data = new URLSearchParams();
            data.append('item_id', itemId);
            data.append('cup_size', cupSize);
            data.append('sugar_level', sugarLevel);
            data.append('quantity', quantity);
            data.append('category', 'Coffee');
            
            // Add add-ons if any selected
            addOns.forEach(addOn => {
                data.append('add_ons[]', addOn);
            });
            
            // Add special request if provided
            if (specialRequest) {
                data.append('special_request', specialRequest);
            }

            // Send the request
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: data
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Coffee added to cart successfully!');
                    updateCartCount();
                } else {
                    alert(data.message || 'Failed to add coffee to cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding to cart. Please try again.');
            });
        }
        function searchMenuItems() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const menuCards = document.querySelectorAll('.menu-card');
        let hasResults = false;
        
        menuCards.forEach(card => {
            const itemName = card.querySelector('h3').textContent.toLowerCase();
            const description = card.querySelector('p').textContent.toLowerCase();
            
            if (itemName.includes(searchTerm) || description.includes(searchTerm)) {
                card.style.display = '';
                hasResults = true;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Show/hide category headers based on visibility of their items
        document.querySelectorAll('h2').forEach(header => {
            const category = header.textContent;
            const nextElement = header.nextElementSibling;
            
            if (nextElement && nextElement.classList.contains('menu-grid')) {
                const visibleItems = nextElement.querySelectorAll('.menu-card:not([style*="display: none"])').length;
                header.style.display = visibleItems > 0 ? '' : 'none';
                nextElement.style.display = visibleItems > 0 ? '' : 'none';
                
                if (visibleItems > 0) hasResults = true;
            }
        });
        
        // Show "no results" message if needed
        const noResultsElement = document.getElementById('noResultsMessage');
        if (!hasResults) {
            if (!noResultsElement) {
                const container = document.querySelector('.container');
                const message = document.createElement('div');
                message.id = 'noResultsMessage';
                message.className = 'no-results';
                message.textContent = 'No items found matching your search.';
                container.appendChild(message);
            }
        } else if (noResultsElement) {
            noResultsElement.remove();
        }
    }
    
    // Optional: Add a clear search button
    function addClearSearchButton() {
        const searchContainer = document.querySelector('.search-container');
        const clearButton = document.createElement('button');
        clearButton.innerHTML = '<i class="fas fa-times"></i>';
        clearButton.style.marginLeft = '10px';
        clearButton.style.background = 'none';
        clearButton.style.border = 'none';
        clearButton.style.cursor = 'pointer';
        clearButton.style.color = 'var(--primary-color)';
        clearButton.style.fontSize = '1.2rem';
        clearButton.title = 'Clear search';
        
        clearButton.addEventListener('click', () => {
            document.getElementById('searchInput').value = '';
            searchMenuItems();
        });
        
        searchContainer.appendChild(clearButton);
    }
    
    // Initialize the clear button when DOM is loaded
    document.addEventListener('DOMContentLoaded', addClearSearchButton);
    </script>
</body>
</html>