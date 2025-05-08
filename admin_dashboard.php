<?php
session_start();

// Redirect if not admin
if (!isset($_SESSION['email']) {
    header("Location: signin.php");
    exit();
}

// Check if admin (using the email from your signin.php)
if ($_SESSION['email'] !== "maepacquiao0405@gmail.com") {
    header("Location: customer_dashboard.php");
    exit();
}

include 'connection/config.php';

// Get stats for dashboard
$orders_today = $conn->query("SELECT COUNT(*) FROM orders WHERE DATE(order_date) = CURDATE()")->fetch_row()[0];
$revenue_today = $conn->query("SELECT SUM(total_amount) FROM orders WHERE DATE(order_date) = CURDATE()")->fetch_row()[0];
$pending_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetch_row()[0];
$total_customers = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];

// Get recent orders
$recent_orders = $conn->query("SELECT o.id, u.fullname, o.total_amount, o.order_date, o.status 
                              FROM orders o JOIN users u ON o.customer_name = u.fullname 
                              ORDER BY o.order_date DESC LIMIT 5");

// Get popular products
$popular_products = $conn->query("SELECT p.name, COUNT(oi.id) as order_count 
                                 FROM order_items oi JOIN products p ON oi.product_id = p.id 
                                 GROUP BY p.name ORDER BY order_count DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kafèa-Kiosk | Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
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
            background-color: #f5f5f5;
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
        
        .sidebar {
            width: 250px;
            background-color: white;
            position: fixed;
            height: 100%;
            padding: 1rem;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin-top: 2rem;
        }
        
        .sidebar-menu li {
            margin-bottom: 1rem;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            color: var(--dark-color);
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .stat-card h3 {
            margin-top: 0;
            font-size: 1rem;
            color: #666;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            margin: 0.5rem 0;
            color: var(--primary-color);
        }
        
        .stat-card .icon {
            font-size: 2rem;
            color: var(--secondary-color);
        }
        
        .card {
            background-color: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            background-color: #f5f5f5;
            color: var(--primary-color);
        }
        
        .status {
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #FFF3CD;
            color: #856404;
        }
        
        .status-completed {
            background-color: #D4EDDA;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #F8D7DA;
            color: #721C24;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--dark-color);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 1rem;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
        }
        
        .user-profile img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="#" class="navbar-brand">
            <img src="assets/img/icon_t.png" alt="Kafèa-Kiosk Logo">
        </a>
        <div class="user-profile">
            <span>Welcome, Admin</span>
            <a href="logout.php" style="color: #ccc; margin-left: 1rem;">Logout</a>
        </div>
    </div>
    
    <div class="sidebar">
        <div class="sidebar-brand">
            <h2>Admin Panel</h2>
        </div>
        
        <ul class="sidebar-menu">
            <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="#orders"><i class="fas fa-shopping-bag"></i> Orders</a></li>
            <li><a href="#products"><i class="fas fa-coffee"></i> Products</a></li>
            <li><a href="#customers"><i class="fas fa-users"></i> Customers</a></li>
            <li><a href="#reports"><i class="fas fa-chart-bar"></i> Reports</a></li>
            <li><a href="#settings"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="dashboard-header">
            <h1>Dashboard Overview</h1>
            <div>
                <button class="btn btn-primary"><i class="fas fa-download"></i> Export Report</button>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-shopping-bag"></i></div>
                <h3>Orders Today</h3>
                <div class="value"><?php echo $orders_today; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                <h3>Revenue Today</h3>
                <div class="value">₱<?php echo number_format($revenue_today, 2); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <h3>Pending Orders</h3>
                <div class="value"><?php echo $pending_orders; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="icon"><i class="fas fa-users"></i></div>
                <h3>Total Customers</h3>
                <div class="value"><?php echo $total_customers; ?></div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <h2>Recent Orders</h2>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo $order['fullname']; ?></td>
                                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <span class="status status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm">View</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <h2>Popular Products</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Orders</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = $popular_products->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo $product['order_count']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>Sales Analytics</h2>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script>
        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                datasets: [{
                    label: 'Monthly Sales',
                    data: [120000, 150000, 180000, 140000, 200000, 220000, 240000],
                    backgroundColor: 'rgba(111, 78, 55, 0.2)',
                    borderColor: 'rgba(111, 78, 55, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.raw.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>