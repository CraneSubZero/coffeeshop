<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: signin.php");
    exit();
}

$user = [
    'fullname' => $_SESSION['user_fullname'] 
        ?? $_SESSION['admin_name'] 
        ?? 'Kafea Kiosk'
];

require_once 'connection/config.php';

$sql = "SELECT orders.*, users.fullname 
        FROM orders 
        JOIN users ON orders.user_id = users.id 
        ORDER BY orders.id DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kafèa-Kiosk | Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Add DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <style>
        :root {
            --primary-color: #6F4E37;
            --secondary-color: #C4A484;
            --accent-color: #D2B48C;
            --dark-color: #3E2723;
            --light-color: #F5F5DC;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            align-items: center;
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

        .profile-section {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
        }

        .user-icon {
            font-size: 1.2rem;
            color: white;
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
            cursor: pointer;
            border: none;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: var(--accent-color);
        }

        .orders-container {
            width: 95%;
            margin: 2rem auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            border-radius: 5px;
        }

        .dataTables_wrapper {
            margin-top: 20px;
        }

        .dataTables_filter input {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .dataTables_length select {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .orders-title {
            text-align: center;
            margin-top: 2rem;
            font-size: 2rem;
        }
        
        .status-pending {
            background-color: #FFF3CD;
            color: #856404;
        }
        
        .status-accept {
            background-color: #D4EDDA;
            color: #155724;
        }
        
        .status-done {
            background-color: #D1ECF1;
            color: #0C5460;
        }
        
        .status-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            min-width: 100px;
            text-align: center;
            display: inline-block;
        }
        
        .btn-pending {
            background-color: #FFC107;
            color: #856404;
        }
        
        .btn-accept {
            background-color: #28A745;
            color: white;
        }
        
        .btn-done {
            background-color: #17A2B8;
            color: white;
        }
        
        .btn-completed {
            background-color: #6C757D;
            color: white;
            cursor: default;
        }
        
        .status-btn:hover:not(.btn-completed) {
            opacity: 0.9;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .marked-status {
            font-weight: bold;
        }
        
        .marked-processing {
            color: #FFC107;
        }
        
        .marked-complete {
            color: #28A745;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="#" class="navbar-brand">
            <img src="assets/img/icon_t.png" alt="Kafèa-Kiosk Logo">
        </a>
        <div class="nav-links">
            <a href="admin_dashboard.php">Add Item</a>
            <a href="orders.php">Orders</a>
        </div>
        <div class="profile-section">
            <i class="fas fa-user-circle user-icon"></i>
            <span class="username"><?php echo htmlspecialchars($user['fullname']); ?></span>
            <button class="logout-btn" onclick="confirmLogout()">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </div>

    <h2 class="orders-title">Orders</h2>

    <div class="orders-container">
       <table id="ordersTable" class="display" style="width:100%">
    <thead>
        <tr>
            <th>#</th>
            <th>Customer Name</th>
            <th>Order Date</th>
            <th>Total Amount</th>
            <th>Payment Method</th>
            <th>Delivery Address</th>
            <th>Contact Number</th>
            <th>Instructions</th>
            <th>View Order</th>
            <th>Status</th>
            <th>Marked</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                    <td><?= htmlspecialchars(date("M d, Y h:i A", strtotime($row['order_date']))) ?></td>
                    <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                    <td><?= htmlspecialchars($row['payment_method']) ?></td>
                    <td><?= htmlspecialchars($row['delivery_address']) ?></td>
                    <td><?= htmlspecialchars($row['contact_number']) ?></td>
                    <td><?= htmlspecialchars($row['special_instructions']) ?></td>
                    
                    <!-- Corrected View Order column -->
                    <td>
                        <a href="view_orders.php?id=<?= urlencode($row['id']) ?>" class="btn-view-order">View</a>
                    </td>
                    
                    <td>
                        <?php if ($row['status'] === 'Pending'): ?>
                            <button class="status-btn btn-pending" onclick="updateStatus(<?= $row['id'] ?>, 'Accept')">Pending</button>
                        <?php elseif ($row['status'] === 'Accept'): ?>
                            <button class="status-btn btn-accept" onclick="updateStatus(<?= $row['id'] ?>, 'Done')">Accepted</button>
                        <?php elseif ($row['status'] === 'Done'): ?>
                            <button class="status-btn btn-done" onclick="updateStatus(<?= $row['id'] ?>, 'Completed')">Ready</button>
                        <?php elseif ($row['status'] === 'Completed'): ?>
                            <span class="status-btn btn-completed">Completed</span>
                        <?php endif; ?>
                    </td>
                    
                    <td class="marked-status <?= ($row['status'] === 'Completed') ? 'marked-complete' : 'marked-processing' ?>">
                        <?= ($row['status'] === 'Completed') ? 'Complete' : 'Processing' ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="11" style="text-align:center;">No orders found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

    </div>

    <!-- Add jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#ordersTable').DataTable({
                "order": [[ 0, "desc" ]], // Order by ID descending by default
                "pageLength": 10, // Show 10 entries per page by default
                "responsive": true // Enable responsive feature
            });
        });
        
       function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                // First send request to logout.php to clear session
                fetch('logout.php')
                    .then(response => {
                        // After logout completes, redirect to signin.php
                        window.location.href = 'signin.php';
                    })
                    .catch(error => {
                        console.error('Logout error:', error);
                        window.location.href = 'signin.php';
                    });
            }
        }
        
        function updateStatus(orderId, newStatus) {
            let confirmMessage = `Change status to ${newStatus}?`;
            if (newStatus === 'Completed') {
                confirmMessage = "Mark this order as Complete?";
            }
            
            if (confirm(confirmMessage)) {
                fetch('update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${orderId}&status=${newStatus}`
                })
                .then(response => {
                    if (response.ok) {
                        if (newStatus === 'Completed') {
                            alert('Order marked as Complete!');
                        }
                        location.reload();
                    } else {
                        alert('Error updating status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating status');
                });
            }
        }
    </script>
</body>
</html>