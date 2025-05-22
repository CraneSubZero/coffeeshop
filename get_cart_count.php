<?php
session_start();
require_once 'connection/config.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit();
}

$user_id = $_SESSION['id'];

try {
    $stmt = $conn->prepare("
        SELECT SUM(ci.quantity) as count 
        FROM cart_items ci
        JOIN carts c ON ci.cart_id = c.id
        WHERE c.user_id = ? AND c.status = 'active'
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'] ?? 0;

    // If count is null, set to 0
    $count = $count ?: 0;
    
    echo json_encode(['success' => true, 'count' => $count]);
} catch (Exception $e) {
    error_log("Cart count error: " . $e->getMessage());
    echo json_encode(['success' => false, 'count' => 0]);
}

$conn->close();
?>
