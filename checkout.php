<?php
session_start();
require_once 'connection/config.php';

if (!isset($_SESSION['id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['id'];
$error = '';
$success = '';

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Get user's cart
    $stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("No items in cart");
    }
    
    $cart = $result->fetch_assoc();
    $cart_id = $cart['id'];
    
    // Get cart items with prices
    $stmt = $conn->prepare("
        SELECT ci.menu_item_id, ci.quantity, mi.price 
        FROM cart_items ci
        JOIN menu_items mi ON ci.menu_item_id = mi.id
        WHERE ci.cart_id = ?
    ");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($items)) {
        throw new Exception("No items in cart");
    }
    
    // Calculate total
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    // Create order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount) VALUES (?, ?)");
    $stmt->bind_param("id", $user_id, $total);
    $stmt->execute();
    $order_id = $conn->insert_id;
    
    // Add order items
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($items as $item) {
        $stmt->bind_param("iiid", $order_id, $item['menu_item_id'], $item['quantity'], $item['price']);
        $stmt->execute();
    }
    
    // Clear cart (optional - you might want to keep cart history)
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    $success = "Order placed successfully! Order ID: #" . $order_id;
    
} catch (Exception $e) {
    $conn->rollback();
    $error = "Error processing order: " . $e->getMessage();
}

// Redirect back with status
$_SESSION['order_status'] = ['success' => empty($error), 'message' => $error ?: $success];
header("Location: customer_dashboard.php");
exit();
?>