<?php
require_once 'connection/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['status'])) {
    $id = intval($_POST['id']);
    $status = $_POST['status'];

    // Optional: Validate allowed status transitions
    $valid_statuses = ['Pending', 'Accept', 'Done', 'Completed'];
    if (!in_array($status, $valid_statuses)) {
        http_response_code(400);
        echo 'Invalid status';
        exit;
    }

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute()) {
        echo 'Status updated successfully';
    } else {
        http_response_code(500);
        echo 'Failed to update status';
    }

    $stmt->close();
} else {
    http_response_code(400);
    echo 'Invalid request';
}
?>
