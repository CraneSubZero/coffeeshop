<?php
session_start();
include('connection/config.php'); // Include database configuration

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate input
    if (empty($email) || empty($password)) {
        echo "<script>alert('Both email and password are required.'); window.location.href='signin.php';</script>";
        exit();
    }

    // Prepare SQL statement
    $stmt = $conn->prepare("SELECT id, fullname, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $fullname, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            session_regenerate_id(true);
            $_SESSION['id'] = $id;
            $_SESSION['fullname'] = $fullname;

            // Redirect based on user type
            if ($email == "info@kafeakiosk.com") {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: customer_dashboard.php");
            }
            exit();
        } else {
            echo "<script>alert('Incorrect password. Please try again.'); window.location.href='signin.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('No user found with this email.'); window.location.href='signin.php';</script>";
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kafèa-Kiosk | Sign In</title>
    <link rel="stylesheet" href="css/mainpage.css">
    <link rel="stylesheet" href="css/signup.css">
    <link rel="icon" href="assets/img/icon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="navbar">
        <a href="#" class="navbar-brand">
            <img src="assets/img/icon_t.png" alt="Kafèa-Kiosk Logo">
        </a>
        <div class="nav-links">
            <a href="main.php">Home</a>

        </div>
    </div>
    <div class="bg-img">
        <div class="content">
            <header>Login Form</header>

            <!-- Display Error Messages -->
            <?php if (!empty($_SESSION['error_message'])): ?>
                <p style="color: red; text-align: center;"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
            <?php endif; ?>

            <form action="signin.php" method="POST">
                <div class="field">
                    <span class="fa fa-user"></span>
                    <input type="email" name="email" required placeholder="Email">
                </div>
                <div class="field space">
                    <span class="fa fa-lock"></span>
                    <input type="password" name="password" class="pass-key" required placeholder="Password">
                    <span class="show">SHOW</span>
                </div>
                <div class="pass">
                    <a href="forgot-password.php">Forgot Password?</a>
                </div>
                <div class="field">
                    <input type="submit" value="SIGN IN">
                </div>
            </form><br>
            <div class="signup">
                Don't have an account?
                <a href="signup.php">Signup Now</a>
            </div>
        </div>
    </div>
    <script>
        const pass_field = document.querySelector('.pass-key');
        const showBtn = document.querySelector('.show');

        showBtn.addEventListener('click', function(){
            if(pass_field.type === "password"){
                pass_field.type = "text";
                showBtn.textContent = "HIDE";
                showBtn.style.color = "#3498db";
            }else{
                pass_field.type = "password";
                showBtn.textContent = "SHOW";
                showBtn.style.color = "#222";
            }
        });
    </script>
</body>
</html>
