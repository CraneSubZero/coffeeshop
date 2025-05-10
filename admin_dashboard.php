<?php
session_start();

// Check if user is logged in, otherwise redirect to signin.php
if (!isset($_SESSION['id'])) {
    header("Location: signin.php");
    exit();
}

// Sample user data (in a real app, this would come from your database)
$user = [
    'id' => $_SESSION['id'],
    'fullname' => isset($_SESSION['user_fullname']) ? $_SESSION['user_fullname'] : 'Kafea Kiosk',
    'email' => isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'info@kafeakiosk.com',
]
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kafèa-Kiosk | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
        }

        .username {
            font-weight: 500;
        }

        .user-icon {
    font-size: 1.2rem;
    color: white;
    margin-right: 8px;
}

.logout-btn i {
    margin-right: 5px;
}

        .logout-btn {
            background-color: var(--secondary-color);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
            cursor: pointer;
            border: none;
        }

        .logout-btn:hover {
            background-color: var(--accent-color);
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="#" class="navbar-brand">
            <img src="assets/img/icon_t.png" alt="Kafèa-Kiosk Logo">
        </a>
        <div class="profile-section">
    <i class="fas fa-user-circle user-icon"></i>
    <span class="username"><?php echo htmlspecialchars($user['fullname']); ?></span>
    <button class="logout-btn" onclick="confirmLogout()">
        <i class="fas fa-sign-out-alt"></i> Logout
    </button>
</div>
        </div>

    <div class="container">
        <h1>Dashboard</h1>
        <!-- Your dashboard content here -->
    </div>

    <script>
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
    </script>
</body>
</html>