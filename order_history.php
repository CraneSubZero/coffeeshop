<?php
session_start();
require_once 'connection/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to reorder items']);
    exit;
}

$user_id = $_SESSION['id'];
$order_id = $_POST['order_id'] ?? null;
$response = ['success' => false, 'message' => ''];

try {
    $conn->begin_transaction();
    
    // 1. Get or create active cart
    $stmt = $conn->prepare("
        INSERT INTO carts (user_id, status)
        VALUES (?, 'active')
        ON DUPLICATE KEY UPDATE status = 'active'
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // 2. Get the cart ID
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
    
    // 3. Get order items
    $stmt = $conn->prepare("
        SELECT 
            menu_item_id, 
            item_name, 
            quantity, 
            price,
            cup_size,
            sugar_level,
            addons,
            special_request
        FROM order_items 
        WHERE order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order_items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // 4. Add items to cart
    foreach ($order_items as $item) {
        if ($item['cup_size']) {
            // Handle coffee items with customizations
            $stmt = $conn->prepare("
                INSERT INTO cart_items (
                    cart_id, 
                    menu_item_id, 
                    quantity, 
                    cup_size, 
                    sugar_level, 
                    special_request
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "iiisss", 
                $cart_id, 
                $item['menu_item_id'], 
                $item['quantity'], 
                $item['cup_size'], 
                $item['sugar_level'], 
                $item['special_request']
            );
            $stmt->execute();
            $cart_item_id = $stmt->insert_id;
            $stmt->close();
            
            // Add add-ons if any
            if (!empty($item['addons'])) {
                $addons = explode(', ', $item['addons']);
                $stmt = $conn->prepare("INSERT INTO cart_item_addons (cart_item_id, addon_name) VALUES (?, ?)");
                foreach ($addons as $addon) {
                    if (!empty(trim($addon))) {
                        $stmt->bind_param("is", $cart_item_id, trim($addon));
                        $stmt->execute();
                    }
                }
                $stmt->close();
            }
        } else {
            // Handle regular items
            $stmt = $conn->prepare("
                INSERT INTO cart_items (cart_id, menu_item_id, quantity)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("iii", $cart_id, $item['menu_item_id'], $item['quantity']);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    $conn->commit();
    $response = ['success' => true, 'message' => 'Items added to cart successfully'];
    
} catch (Exception $e) {
    $conn->rollback();
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
?>