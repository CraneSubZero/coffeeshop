<?php
session_start();
require_once 'connection/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['id'];
$data = json_decode(file_get_contents('php://input'), true);
$item_id = isset($data['item_id']) ? (int)$data['item_id'] : 0;

if ($item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item']);
    exit();
}

try {
    // 1. Find or create a cart for this user
    $stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_id = null;
    if ($row = $result->fetch_assoc()) {
        $cart_id = $row['id'];
    } else {
        // Create a new cart
        $stmt_insert = $conn->prepare("INSERT INTO carts (user_id, created_at) VALUES (?, NOW())");
        $stmt_insert->bind_param("i", $user_id);
        if ($stmt_insert->execute()) {
            $cart_id = $stmt_insert->insert_id;
        }
        $stmt_insert->close();
    }
    $stmt->close();

    if (!$cart_id) {
        echo json_encode(['success' => false, 'message' => 'Could not create cart']);
        exit();
    }

    // 2. Add or update cart item
    $stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND item_id = ?");
    $stmt->bind_param("ii", $cart_id, $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        // Update quantity
        $new_qty = $row['quantity'] + 1;
        $stmt_update = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt_update->bind_param("ii", $new_qty, $row['id']);
        $stmt_update->execute();
        $stmt_update->close();
    } else {
        // Insert new item
        $stmt_insert = $conn->prepare("INSERT INTO cart_items (cart_id, item_id, quantity) VALUES (?, ?, 1)");
        $stmt_insert->bind_param("ii", $cart_id, $item_id);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Item added to cart']);
    
} catch (Exception $e) {
    error_log("Cart error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error adding to cart']);
}

$conn->close();
?>