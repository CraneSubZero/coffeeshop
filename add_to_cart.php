<?php
session_start();
require_once 'connection/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$item_id = $data['item_id'] ?? null;
$user_id = $_SESSION['id'];

if (!$item_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid item']);
    exit();
}

try {
    // Check if user has an active cart
    $stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $cart = $result->fetch_assoc();
        $cart_id = $cart['id'];
    } else {
        // Create new cart
        $stmt = $conn->prepare("INSERT INTO carts (user_id) VALUES (?)");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $cart_id = $conn->insert_id;
    }
    
    // Check if item already exists in cart
    $stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND menu_item_id = ?");
    $stmt->bind_param("ii", $cart_id, $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update quantity
        $item = $result->fetch_assoc();
        $new_quantity = $item['quantity'] + 1;
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $item['id']);
    } else {
        // Add new item
        $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, menu_item_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $cart_id, $item_id);
    }
    
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Item added to cart']);
    
} catch (Exception $e) {
    error_log("Cart error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error adding to cart']);
}

$conn->close();
?>