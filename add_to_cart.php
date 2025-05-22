<?php
session_start();
require_once 'connection/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit;
}

$user_id = $_SESSION['id'];
$response = ['success' => false, 'message' => ''];

try {
    $conn->begin_transaction();

    // 1. Get or create active cart for user
    $stmt = $conn->prepare("
        INSERT INTO carts (user_id, status) 
        VALUES (?, 'active')
        ON DUPLICATE KEY UPDATE status = 'active'
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // 2. Get the active cart ID
    $stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ? AND status = 'active' LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart = $result->fetch_assoc();
    $cart_id = $cart['id'] ?? null;
    $stmt->close();

    if (!$cart_id) {
        throw new Exception("Failed to create or retrieve cart");
    }

    // 3. Process the item based on its type
    $item_id = $_POST['item_id'] ?? null;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $category = $_POST['category'] ?? null;

    // Validate required fields
    if (!$item_id || $quantity < 1) {
        throw new Exception("Invalid item or quantity");
    }

    // Check if item exists and is available
    $stmt = $conn->prepare("SELECT id, is_available FROM menu_items WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $menu_item = $result->fetch_assoc();
    $stmt->close();

    if (!$menu_item) {
        throw new Exception("Menu item not found");
    }

    if (!$menu_item['is_available']) {
        throw new Exception("This item is currently unavailable");
    }

    // Handle different item types
    if ($category === 'Coffee') {
        // Coffee with customizations
        $cup_size = $_POST['cup_size'] ?? null;
        $sugar_level = $_POST['sugar_level'] ?? null;
        $add_ons = $_POST['add_ons'] ?? [];
        $special_request = $_POST['special_request'] ?? null;

        // Validate required coffee fields
        if (!$cup_size || !$sugar_level) {
            throw new Exception("Please select cup size and sugar level");
        }

        // Insert the customized coffee item
        $stmt = $conn->prepare("
            INSERT INTO cart_items (cart_id, menu_item_id, quantity, cup_size, sugar_level, special_request)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iiisss", 
            $cart_id, 
            $item_id, 
            $quantity, 
            $cup_size, 
            $sugar_level, 
            $special_request
        );
        $stmt->execute();
        $cart_item_id = $stmt->insert_id;
        $stmt->close();

        // Insert add-ons if any
        if (!empty($add_ons)) {
            $stmt = $conn->prepare("INSERT INTO cart_item_addons (cart_item_id, addon_name) VALUES (?, ?)");
            foreach ($add_ons as $addon) {
                if (!empty(trim($addon))) {
                    $stmt->bind_param("is", $cart_item_id, trim($addon));
                    $stmt->execute();
                }
            }
            $stmt->close();
        }

    } else {
        // Regular item (Pastry or other)
        // Check if item already exists in cart
        $stmt = $conn->prepare("
            SELECT id, quantity FROM cart_items 
            WHERE cart_id = ? AND menu_item_id = ? 
            AND cup_size IS NULL AND sugar_level IS NULL
            LIMIT 1
        ");
        $stmt->bind_param("ii", $cart_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing_item = $result->fetch_assoc();
        $stmt->close();

        if ($existing_item) {
            // Update quantity if item already exists
            $new_quantity = $existing_item['quantity'] + $quantity;
            $stmt = $conn->prepare("
                UPDATE cart_items SET quantity = ? WHERE id = ?
            ");
            $stmt->bind_param("ii", $new_quantity, $existing_item['id']);
            $stmt->execute();
            $stmt->close();
        } else {
            // Insert new item
            $stmt = $conn->prepare("
                INSERT INTO cart_items (cart_id, menu_item_id, quantity)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("iii", $cart_id, $item_id, $quantity);
            $stmt->execute();
            $stmt->close();
        }
    }

    $conn->commit();
    $response = ['success' => true, 'message' => 'Item added to cart successfully'];

} catch (Exception $e) {
    $conn->rollback();
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
?>