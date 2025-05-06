<?php
session_start();
include 'connection/config.php';

// Set the timezone for the database connection
$conn->query("SET time_zone = '+08:00'"); // Adjust +08:00 to your timezone

// Ensure token is retained in both GET and POST requests
$submitted_token = ''; // Initialize to prevent undefined variable warning

$token = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : '');

if (empty($token)) {
    die("No token provided!");
}

// echo "Submitted Token: " . htmlspecialchars($submitted_token) . "<br>";


// echo "Received token: " . htmlspecialchars($token) . "<br>";

// Check if token exists in the database
$check_token = "SELECT email, token_expiry FROM users WHERE reset_token = ?";
$stmt = $conn->prepare($check_token);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Invalid or expired reset link. Please request a new password reset.');</script>";
    echo "<script>window.location.href = 'forgot-password.php';</script>";
    exit();
}

$row = $result->fetch_assoc();
$email = $row['email'];
$token_expiry = $row['token_expiry'];

// Ensure the token is not expired
if (strtotime($token_expiry) < time()) {
    echo "<script>alert('Token has expired. Please request a new password reset.');</script>";
    echo "<script>window.location.href = 'forgot-password.php';</script>";
    exit();
}

// Handle password reset submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_password'], $_POST['token'])) {
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $submitted_token = $_POST['token'];

    // Debug: Check if token exists
    echo "Submitted Token: " . htmlspecialchars($submitted_token) . "<br>";

    // Verify token is still in the database
    $check_existing_token = "SELECT reset_token, token_expiry FROM users WHERE reset_token = ?";
    $stmt = $conn->prepare($check_existing_token);
    $stmt->bind_param("s", $submitted_token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("<script>alert('Error: Token is invalid or already used.');</script>");
    }

    $row = $result->fetch_assoc();
    $token_expiry = $row['token_expiry'];

    // Ensure token is still valid
    if (strtotime($token_expiry) > time()) {
        $update_password = "UPDATE users SET password = ?, reset_token = NULL, token_expiry = NULL WHERE reset_token = ?";
        $stmt = $conn->prepare($update_password);
        $stmt->bind_param("ss", $new_password, $submitted_token);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo "<script>alert('Password successfully reset!');</script>";
                echo "<script>window.location.href = 'signin.php';</script>";
                exit();
            } else {
                echo "<script>alert('No rows were updated. Possible reasons: Token expired, incorrect token, or token already used.');</script>";
            }
        } else {
            echo "<script>alert('Database error: " . $stmt->error . "');</script>";
        }
    } else {
        echo "<script>alert('Token has expired. Please request a new password reset.');</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="icon" href="assets/img/icon.png">
    <link rel="stylesheet" href="css/mainpage.css">
    <link rel="stylesheet" href="css/signup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"/>
</head>
<body>
    <div class="bg-img">
        <div class="content">
            <header>Reset Password</header>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="field space">
                    <span class="fa fa-lock"></span>
                    <input type="password" name="new_password" class="pass-key" required placeholder="Enter new password">
                    <span class="show">SHOW</span>
                </div>
                <div class="field space">
                    <span class="fa fa-lock"></span>
                    <input type="password" name="confirm_password" class="pass-key"  required placeholder="Confirm new password">
                    <span class="show">SHOW</span>
                </div><br>
                <div class="field">
                    <input type="submit" value="Reset Password">
                </div>
            </form>
        </div>
    </div>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    var password = document.querySelector('input[name="new_password"]').value;
    var confirm = document.querySelector('input[name="confirm_password"]').value;
    
    if (password !== confirm) {
        e.preventDefault();
        alert('Passwords do not match!');
    }
});

  const pass_fields = document.querySelectorAll('.pass-key');
        const showBtns = document.querySelectorAll('.show');
         
        showBtns.forEach((showBtn, index) => {
            showBtn.addEventListener('click', function(){
                const pass_field = pass_fields[index];
                if(pass_field.type === "password"){
                    pass_field.type = "text";
                    showBtn.textContent = "HIDE";
                    showBtn.style.color = "#3498db";
                } else {
                    pass_field.type = "password";
                    showBtn.textContent = "SHOW";
                    showBtn.style.color = "#222";
                }
            });
        });


</script>

</body>
</html>
