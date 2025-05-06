<?php
// Database connection
$db = new mysqli('localhost', 'root', '', 'coffee_shop');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Get all active products
$products = $db->query("SELECT * FROM products WHERE is_active = TRUE");

// Get all active flavors
$flavors = $db->query("SELECT * FROM flavors WHERE is_active = TRUE");

// Get all active addons
$addons = $db->query("SELECT * FROM addons WHERE is_active = TRUE");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // Start transaction
    $db->begin_transaction();
    
    try {
        // Create order
        $customer_name = $db->real_escape_string($_POST['customer_name']);
        $db->query("INSERT INTO orders (customer_name, total_amount) VALUES ('$customer_name', 0)");
        $order_id = $db->insert_id;
        $total_amount = 0;
        
        // Process each item
        foreach ($_POST['items'] as $item) {
            $product_id = intval($item['product_id']);
            $flavor_id = isset($item['flavor_id']) ? intval($item['flavor_id']) : NULL;
            $cup_size = $db->real_escape_string($item['cup_size']);
            $sugar_level = $db->real_escape_string($item['sugar_level']);
            $quantity = intval($item['quantity']);
            
            // Get product price
            $product = $db->query("SELECT base_price FROM products WHERE id = $product_id")->fetch_assoc();
            $price = $product['base_price'];
            
            // Add flavor price if selected
            if ($flavor_id) {
                $flavor = $db->query("SELECT additional_price FROM flavors WHERE id = $flavor_id")->fetch_assoc();
                $price += $flavor['additional_price'];
            }
            
            // Adjust price based on cup size
            switch ($cup_size) {
                case 'medium':
                    $price *= 1.2;
                    break;
                case 'large':
                    $price *= 1.5;
                    break;
            }
            
            // Insert order item
            $db->query("INSERT INTO order_items (order_id, product_id, flavor_id, cup_size, sugar_level, quantity, price) 
                        VALUES ($order_id, $product_id, $flavor_id, '$cup_size', '$sugar_level', $quantity, $price)");
            $order_item_id = $db->insert_id;
            $total_amount += $price * $quantity;
            
            // Process addons if any
            if (!empty($item['addons'])) {
                foreach ($item['addons'] as $addon_id) {
                    $addon_id = intval($addon_id);
                    $db->query("INSERT INTO order_item_addons (order_item_id, addon_id) VALUES ($order_item_id, $addon_id)");
                    
                    // Add addon price
                    $addon = $db->query("SELECT additional_price FROM addons WHERE id = $addon_id")->fetch_assoc();
                    $total_amount += $addon['additional_price'] * $quantity;
                }
            }
        }
        
        // Update order total
        $db->query("UPDATE orders SET total_amount = $total_amount WHERE id = $order_id");
        
        // Commit transaction
        $db->commit();
        
        // Success message
        $success = "Order placed successfully! Your order ID is #$order_id";
    } catch (Exception $e) {
        // Rollback on error
        $db->rollback();
        $error = "Error placing order: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kafèa-Kiosk | Order Dashboard</title>
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
        
        .nav-links {
            display: flex;
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
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        h1, h2, h3 {
            color: var(--primary-color);
        }
        
        .menu-section, .order-summary {
            background-color: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .menu-item {
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .menu-item img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        
        .menu-item-content {
            padding: 1rem;
        }
        
        .menu-item h3 {
            margin: 0 0 0.5rem;
        }
        
        .menu-item p {
            color: #666;
            margin: 0 0 1rem;
            font-size: 0.9rem;
        }
        
        .menu-item .price {
            font-weight: bold;
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        
        .add-to-order {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-top: 1rem;
            transition: background-color 0.3s;
        }
        
        .add-to-order:hover {
            background-color: var(--dark-color);
        }
        
        .order-items {
            margin-top: 1.5rem;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-item-details {
            flex: 1;
        }
        
        .order-item-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            transition: background-color 0.3s;
        }
        
        .btn-edit {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-remove {
            background-color: #ff6b6b;
            color: white;
        }
        
        .customization-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .close-modal {
            float: right;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .checkboxes {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            background-color: #f5f5f5;
            padding: 0.5rem;
            border-radius: 5px;
        }
        
        .checkout-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 1rem;
            width: 100%;
            transition: background-color 0.3s;
        }
        
        .checkout-btn:hover {
            background-color: var(--dark-color);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        @media (max-width: 768px) {
            .nav-links {
                gap: 1rem;
            }
            
            .menu-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="#" class="navbar-brand">
            <img src="assets/img/icon_t.png" alt="Kafèa-Kiosk Logo">
        </a>
        <?php if (isset($_SESSION['username'])): ?>
        <div class="user-profile" style="margin-left: auto; display: flex; align-items: center;">
            <span style="color: white; margin-right: 10px;">
                Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
            </span>
            <a href="/logout.php" style="color: #ccc;">Logout</a>
        </div>
    <?php endif; ?>
    </div>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>Order Dashboard</h1>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <section id="menu" class="menu-section">
            <h2>Our Menu</h2>
            <div class="menu-grid">
                <?php while ($product = $products->fetch_assoc()): ?>
                    <div class="menu-item" data-product-id="<?php echo $product['id']; ?>">
                        <img src="<?php echo $product['image_url'] ?: 'assets/img/coffee-placeholder.jpg'; ?>" alt="<?php echo $product['name']; ?>">
                        <div class="menu-item-content">
                            <h3><?php echo $product['name']; ?></h3>
                            <p><?php echo $product['description']; ?></p>
                            <div class="price">$<?php echo number_format($product['base_price'], 2); ?></div>
                            <button class="add-to-order" onclick="openCustomizationModal(<?php echo $product['id']; ?>, '<?php echo $product['name']; ?>', <?php echo $product['base_price']; ?>)">
                                Add to Order
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
        
        <section class="order-summary">
            <h2>Your Order</h2>
            <form id="order-form" method="POST">
                <div class="form-group">
                    <label for="customer_name">Your Name</label>
                    <input type="text" id="customer_name" name="customer_name" class="form-control" required>
                </div>
                
                <div id="order-items-container" class="order-items">
                    <!-- Order items will be added here dynamically -->
                    <p id="empty-order-message">Your order is empty. Please add items from the menu.</p>
                </div>
                
                <div id="order-total" style="text-align: right; font-size: 1.2rem; font-weight: bold; margin-top: 1rem; display: none;">
                    Total: $<span id="total-amount">0.00</span>
                </div>
                
                <input type="hidden" name="place_order" value="1">
                <button type="submit" id="checkout-btn" class="checkout-btn" disabled>Place Order</button>
            </form>
        </section>
    </div>
    
    <!-- Customization Modal -->
    <div id="customization-modal" class="customization-modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeCustomizationModal()">&times;</span>
            <h2 id="modal-product-name"></h2>
            <p>Base Price: $<span id="modal-product-price"></span></p>
            
            <form id="customization-form">
                <input type="hidden" id="modal-product-id">
                
                <div class="form-group">
                    <label for="cup-size">Cup Size</label>
                    <select id="cup-size" class="form-control" required>
                        <option value="small">Small (+0%)</option>
                        <option value="medium" selected>Medium (+20%)</option>
                        <option value="large">Large (+50%)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="sugar-level">Sugar Level</label>
                    <select id="sugar-level" class="form-control" required>
                        <option value="none">No Sugar</option>
                        <option value="light">Light</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                
                <?php if ($flavors->num_rows > 0): ?>
                <div class="form-group">
                    <label>Flavor (Optional)</label>
                    <select id="flavor" class="form-control">
                        <option value="">None</option>
                        <?php while ($flavor = $flavors->fetch_assoc()): ?>
                            <option value="<?php echo $flavor['id']; ?>" data-price="<?php echo $flavor['additional_price']; ?>">
                                <?php echo $flavor['name']; ?> (+$<?php echo number_format($flavor['additional_price'], 2); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <?php if ($addons->num_rows > 0): ?>
                <div class="form-group">
                    <label>Add-ons (Optional)</label>
                    <div class="checkboxes">
                        <?php 
                        $addons->data_seek(0); // Reset pointer
                        while ($addon = $addons->fetch_assoc()): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" id="addon-<?php echo $addon['id']; ?>" name="addons[]" value="<?php echo $addon['id']; ?>"
                                       data-price="<?php echo $addon['additional_price']; ?>">
                                <label for="addon-<?php echo $addon['id']; ?>">
                                    <?php echo $addon['name']; ?> (+$<?php echo number_format($addon['additional_price'], 2); ?>)
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" class="form-control" min="1" value="1" required>
                </div>
                
                <div style="text-align: right; margin-top: 1rem;">
                    <strong>Item Total: $<span id="modal-item-total">0.00</span></strong>
                </div>
                
                <button type="button" class="checkout-btn" onclick="addToOrder()">Add to Order</button>
            </form>
        </div>
    </div>
    
    <script>
        // Global variables
        let currentOrderItems = [];
        let editingIndex = -1;
        
        // Open customization modal
        function openCustomizationModal(productId, productName, basePrice) {
            document.getElementById('modal-product-id').value = productId;
            document.getElementById('modal-product-name').textContent = productName;
            document.getElementById('modal-product-price').textContent = basePrice.toFixed(2);
            document.getElementById('quantity').value = 1;
            document.getElementById('cup-size').value = 'medium';
            document.getElementById('sugar-level').value = 'medium';
            document.getElementById('flavor').value = '';
            
            // Reset checkboxes
            document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Calculate initial total
            calculateItemTotal();
            
            // Show modal
            document.getElementById('customization-modal').style.display = 'flex';
        }
        
        // Close customization modal
        function closeCustomizationModal() {
            document.getElementById('customization-modal').style.display = 'none';
            editingIndex = -1;
        }
        
        // Calculate item total based on selections
        function calculateItemTotal() {
            const basePrice = parseFloat(document.getElementById('modal-product-price').textContent);
            const quantity = parseInt(document.getElementById('quantity').value);
            const cupSize = document.getElementById('cup-size').value;
            const flavorSelect = document.getElementById('flavor');
            const flavorPrice = flavorSelect.selectedOptions[0]?.dataset.price ? parseFloat(flavorSelect.selectedOptions[0].dataset.price) : 0;
            
            // Calculate size multiplier
            let sizeMultiplier = 1;
            if (cupSize === 'medium') sizeMultiplier = 1.2;
            else if (cupSize === 'large') sizeMultiplier = 1.5;
            
            // Calculate addons total
            let addonsTotal = 0;
            document.querySelectorAll('input[type="checkbox"]:checked').forEach(checkbox => {
                addonsTotal += parseFloat(checkbox.dataset.price);
            });
            
            // Calculate total
            const total = (basePrice * sizeMultiplier + flavorPrice + addonsTotal) * quantity;
            document.getElementById('modal-item-total').textContent = total.toFixed(2);
        }
        
        // Add event listeners for form changes
        document.getElementById('cup-size').addEventListener('change', calculateItemTotal);
        document.getElementById('sugar-level').addEventListener('change', calculateItemTotal);
        document.getElementById('flavor').addEventListener('change', calculateItemTotal);
        document.getElementById('quantity').addEventListener('change', calculateItemTotal);
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', calculateItemTotal);
        });
        
        // Add item to order
        function addToOrder() {
            const productId = document.getElementById('modal-product-id').value;
            const productName = document.getElementById('modal-product-name').textContent;
            const basePrice = parseFloat(document.getElementById('modal-product-price').textContent);
            const cupSize = document.getElementById('cup-size').value;
            const sugarLevel = document.getElementById('sugar-level').value;
            const flavorSelect = document.getElementById('flavor');
            const flavorId = flavorSelect.value;
            const flavorName = flavorId ? flavorSelect.selectedOptions[0].text.split(' (+')[0] : '';
            const quantity = parseInt(document.getElementById('quantity').value);
            
            // Get selected addons
            const addons = [];
            document.querySelectorAll('input[type="checkbox"]:checked').forEach(checkbox => {
                addons.push({
                    id: checkbox.value,
                    name: checkbox.nextElementSibling.textContent.split(' (+')[0],
                    price: parseFloat(checkbox.dataset.price)
                });
            });
            
            // Calculate price
            let sizeMultiplier = 1;
            if (cupSize === 'medium') sizeMultiplier = 1.2;
            else if (cupSize === 'large') sizeMultiplier = 1.5;
            
            const flavorPrice = flavorSelect.selectedOptions[0]?.dataset.price ? parseFloat(flavorSelect.selectedOptions[0].dataset.price) : 0;
            
            let addonsTotal = 0;
            addons.forEach(addon => {
                addonsTotal += addon.price;
            });
            
            const price = (basePrice * sizeMultiplier + flavorPrice + addonsTotal) * quantity;
            
            // Create order item object
            const orderItem = {
                productId,
                productName,
                basePrice,
                cupSize,
                sugarLevel,
                flavorId,
                flavorName,
                addons,
                quantity,
                price
            };
            
            // Add to order or update existing item
            if (editingIndex >= 0) {
                currentOrderItems[editingIndex] = orderItem;
            } else {
                currentOrderItems.push(orderItem);
            }
            
            // Update order display
            updateOrderDisplay();
            
            // Close modal
            closeCustomizationModal();
        }
        
        // Update order display
        function updateOrderDisplay() {
            const container = document.getElementById('order-items-container');
            const emptyMessage = document.getElementById('empty-order-message');
            const orderTotal = document.getElementById('order-total');
            const checkoutBtn = document.getElementById('checkout-btn');
            
            if (currentOrderItems.length === 0) {
                emptyMessage.style.display = 'block';
                orderTotal.style.display = 'none';
                checkoutBtn.disabled = true;
                container.innerHTML = '<p id="empty-order-message">Your order is empty. Please add items from the menu.</p>';
                return;
            }
            
            emptyMessage.style.display = 'none';
            orderTotal.style.display = 'block';
            checkoutBtn.disabled = false;
            
            // Clear container
            container.innerHTML = '';
            
            // Add hidden inputs for form submission
            currentOrderItems.forEach((item, index) => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'order-item';
                itemDiv.dataset.index = index;
                
                // Create details div
                const detailsDiv = document.createElement('div');
                detailsDiv.className = 'order-item-details';
                
                // Product name and quantity
                const nameQuantity = document.createElement('h4');
                nameQuantity.textContent = `${item.productName} x ${item.quantity}`;
                detailsDiv.appendChild(nameQuantity);
                
                // Customizations
                const customizations = document.createElement('p');
                
                let customizationText = `Size: ${item.cupSize.charAt(0).toUpperCase() + item.cupSize.slice(1)}, Sugar: ${item.sugarLevel.charAt(0).toUpperCase() + item.sugarLevel.slice(1)}`;
                
                if (item.flavorName) {
                    customizationText += `, Flavor: ${item.flavorName}`;
                }
                
                if (item.addons.length > 0) {
                    customizationText += `, Add-ons: ${item.addons.map(a => a.name).join(', ')}`;
                }
                
                customizations.textContent = customizationText;
                detailsDiv.appendChild(customizations);
                
                // Price
                const price = document.createElement('p');
                price.textContent = `$${item.price.toFixed(2)}`;
                detailsDiv.appendChild(price);
                
                // Create actions div
                const actionsDiv = document.createElement('div');
                actionsDiv.className = 'order-item-actions';
                
                // Edit button
                const editBtn = document.createElement('button');
                editBtn.className = 'btn btn-edit';
                editBtn.textContent = 'Edit';
                editBtn.onclick = () => editOrderItem(index);
                actionsDiv.appendChild(editBtn);
                
                // Remove button
                const removeBtn = document.createElement('button');
                removeBtn.className = 'btn btn-remove';
                removeBtn.textContent = 'Remove';
                removeBtn.onclick = () => removeOrderItem(index);
                actionsDiv.appendChild(removeBtn);
                
                // Append to item div
                itemDiv.appendChild(detailsDiv);
                itemDiv.appendChild(actionsDiv);
                
                // Append to container
                container.appendChild(itemDiv);
                
                // Add hidden inputs for form submission
                const productInput = document.createElement('input');
                productInput.type = 'hidden';
                productInput.name = `items[${index}][product_id]`;
                productInput.value = item.productId;
                container.appendChild(productInput);
                
                if (item.flavorId) {
                    const flavorInput = document.createElement('input');
                    flavorInput.type = 'hidden';
                    flavorInput.name = `items[${index}][flavor_id]`;
                    flavorInput.value = item.flavorId;
                    container.appendChild(flavorInput);
                }
                
                const sizeInput = document.createElement('input');
                sizeInput.type = 'hidden';
                sizeInput.name = `items[${index}][cup_size]`;
                sizeInput.value = item.cupSize;
                container.appendChild(sizeInput);
                
                const sugarInput = document.createElement('input');
                sugarInput.type = 'hidden';
                sugarInput.name = `items[${index}][sugar_level]`;
                sugarInput.value = item.sugarLevel;
                container.appendChild(sugarInput);
                
                const quantityInput = document.createElement('input');
                quantityInput.type = 'hidden';
                quantityInput.name = `items[${index}][quantity]`;
                quantityInput.value = item.quantity;
                container.appendChild(quantityInput);
                
                item.addons.forEach(addon => {
                    const addonInput = document.createElement('input');
                    addonInput.type = 'hidden';
                    addonInput.name = `items[${index}][addons][]`;
                    addonInput.value = addon.id;
                    container.appendChild(addonInput);
                });
            });
            
            // Calculate and display total
            const total = currentOrderItems.reduce((sum, item) => sum + item.price, 0);
            document.getElementById('total-amount').textContent = total.toFixed(2);
        }
        
        // Edit order item
        function editOrderItem(index) {
            const item = currentOrderItems[index];
            editingIndex = index;
            
            // Open modal with item data
            openCustomizationModal(item.productId, item.productName, item.basePrice);
            
            // Set values
            document.getElementById('cup-size').value = item.cupSize;
            document.getElementById('sugar-level').value = item.sugarLevel;
            document.getElementById('flavor').value = item.flavorId || '';
            document.getElementById('quantity').value = item.quantity;
            
            // Check addons
            document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = item.addons.some(addon => addon.id === checkbox.value);
            });
            
            // Recalculate total
            calculateItemTotal();
        }
        
        // Remove order item
        function removeOrderItem(index) {
            currentOrderItems.splice(index, 1);
            updateOrderDisplay();
        }
    </script>
</body>
</html>