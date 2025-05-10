<?php 
session_start();

// Redirect if not logged in
if (!isset($_SESSION['id'])) {
    header("Location: signin.php");
    exit();
}

// Database connection
require_once 'connection/config.php'; // Assuming you have a file with database connection

// Fetch user data from database
$user_id = $_SESSION['id'];
$user_fullname = '';


try {
    $stmt = $conn->prepare("SELECT fullname FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        $user_fullname = $user_data['fullname'] ?? '';
      
        
        // Update session with fresh data if needed
        $_SESSION['user_fullname'] = $user_fullname;
    }
    $stmt->close();
} catch (Exception $e) {
    // Handle error (you might want to log this)
    error_log("Error fetching user data: " . $e->getMessage());
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kafèa-Kiosk | Customer Dashboard</title>
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

        .profile-section {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
        }

        .user-icon {
            font-size: 1.2rem;
            color: white;
            margin-right: 8px;
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
            transition: background-color 0.3s;
            cursor: pointer;
            border: none;
        }

        .logout-btn:hover {
            background-color: var(--accent-color);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .user-info {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <img src="assets/img/icon_t.png" alt="Kafèa-Kiosk Logo">
        </div>
        <div class="profile-section">
            <i class="fas fa-user-circle user-icon"></i>
            <span class="username"><?php echo htmlspecialchars($user_fullname); ?></span>
            <button class="logout-btn" onclick="confirmLogout()">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </div>

    <div class="container">
        <h1>Dashboard</h1>
    </div>

    <script>
        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                fetch('logout.php')
                    .then(response => {
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