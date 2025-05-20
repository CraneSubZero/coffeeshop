<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $item_id = $_POST['item_id'];
    $item_name = $_POST['item_name'];
    $price = $_POST['price'];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Prevent duplicates
    if (!isset($_SESSION['cart'][$item_id])) {
        $_SESSION['cart'][$item_id] = [
            'item_name' => $item_name,
            'price' => $price,
            'quantity' => 1
        ];
    } else {
        $_SESSION['cart'][$item_id]['quantity'] += 1;
    }

    header("Location: cart_view.php");
    exit();
}
