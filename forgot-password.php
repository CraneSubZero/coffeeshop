<?php
session_start();
include 'connection/config.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    
    // Check if email exists in database
    $check_email = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($check_email);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Generate token
        $token = bin2hex(random_bytes(32)); // 64 characters long
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Set expiry to 1 hour from now

       // Store token in database
        $update_token = "UPDATE users SET reset_token = ?, token_expiry = ? WHERE email = ?";
        $stmt_update = $conn->prepare($update_token);  // Changed from $stmt to $stmt_update
        $stmt_update->bind_param("sss", $token, $expiry, $email);
        
        if ($stmt_update->execute()) {
            // Create reset password link
            $reset_link = "http://localhost/kkc/reset-password.php?token=" . urlencode($token);
            
            // Initialize PHPMailer
            $mail = new PHPMailer(true);  // Add this line
            
            // Email setup
            $mail->Body = "Click the following link to reset your password: " . $reset_link;
            try {
              // Server settings
        $mail->isSMTP();
       $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'maepacquiao0405@gmail.com';
        $mail->Password   = 'shhj idao nsot hrdt'; // You need to replace this with your actual App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;  // Changed from 3306 to 587

        
                // Recipients
      $mail->setFrom('maepacquiao0405@gmail.com', 'Kafèa-Kiosk');
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = "Password Reset Request";
                $mail->Body    = "
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; }
                            .container { padding: 20px; }
                            .button {
                                background-color: #4CAF50;
                                border: none;
                                color: white;
                                padding: 15px 32px;
                                text-align: center;
                                text-decoration: none;
                                display: inline-block;
                                font-size: 16px;
                                margin: 4px 2px;
                                cursor: pointer;
                                border-radius: 4px;
                            }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <h2>Password Reset Request</h2>
                            <p>Hello,</p>
                            <p>We received a request to reset your password. Click the button below to reset it:</p>
                            <p><a href='$reset_link' class='button'>Reset Password</a></p>
                            <p>Or copy and paste this link in your browser:</p>
                            <p>$reset_link</p>
                            <p>This link will expire in 1 hour.</p>
                            <p>If you didn't request this, please ignore this email.</p>
                            <br>
                            <p>Best regards,</p>
                            <p>Kafèa-Kiosk Team</p>
                        </div>
                    </body>
                    </html>";
                
                $mail->AltBody = "Hello,\n\n
                                We received a request to reset your password. 
                                Please click the following link to reset your password:\n
                                $reset_link\n\n
                                This link will expire in 1 hour.\n\n
                                If you didn't request this, please ignore this email.\n\n
                                Best regards,\n
                                Kafèa-Kiosk Team";

                $mail->send();
                echo "<script>alert('Password reset instructions have been sent to your email.');</script>";
                echo "<script>window.location.href = 'signin.php';</script>";
                exit();
            } catch (Exception $e) {
                echo "<script>alert('Error sending email. Please try again later. Mailer Error: {$mail->ErrorInfo}');</script>";
            }
        } else {
            echo "<script>alert('Error updating token. Please try again.');</script>";
        }
    } else {
        echo "<script>alert('Email address not found.');</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kafèa-Kiosk | Forgot Password</title>
    <link rel="icon" href="assets/img/icon.png">
    <link rel="stylesheet" href="css/mainpage.css">
    <link rel="stylesheet" href="css/signup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"/>
</head>
<body>
    <div class="navbar">
        <a href="#" class="navbar-brand">
            <img src="assets/img/icon_t.png" alt="Kafèa-Kiosk Logo">
        </a>
        <div class="nav-links">
            <a href="main.php">Home</a>
            <a href="#menu">Menu</a>
            <a href="#about">About</a>
            <a href="#contact">Contact</a>
        </div>
    </div>

    <div class="bg-img">
        <div class="content">
            <header>Forgot Password</header>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <div class="field space">
                    <span class="fa fa-envelope"></span>
                    <input type="email" name="email" required placeholder="Enter your email">
                </div><br>
                <div class="field">
                    <input type="submit" value="Reset Password">
                </div><br>
                <div class="signup">
                    Remember your password? 
                    <a href="signin.php">Login Here</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>