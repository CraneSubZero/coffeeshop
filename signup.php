<?php
session_start();

include 'connection/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add input validation
   if (empty($_POST['fullname']) || empty($_POST['email']) || empty($_POST['phone']) || empty($_POST['password']) || empty($_POST['confirm_password'])) {
        echo "<script>alert('All fields are required');</script>";
    } else if ($_POST['password'] !== $_POST['confirm_password']) {
        echo "<script>alert('Passwords do not match');</script>";
    } else {
        $fullname = $conn->real_escape_string($_POST['fullname']);
        $email = $conn->real_escape_string($_POST['email']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Check if email already exists
        $check_email = "SELECT id FROM users WHERE email = '$email'";
        $result = $conn->query($check_email);
        
      if ($result->num_rows > 0) {
            echo "<script>alert('This email is already registered. Please use another email');</script>";
            // Store other form values to repopulate
            $_POST['email'] = ''; // Clear email field
        } else {
            $sql = "INSERT INTO users (fullname, email, phone, password) 
                    VALUES ('$fullname', '$email', '$phone', '$password')";

            if ($conn->query($sql) === TRUE) {
                echo "<script>alert('Account created successfully! You can now log in.');</script>";
                $_SESSION['signup_success'] = true;
                echo "<script>window.location.href = 'signin.php';</script>";
                exit();
            } else {
                echo "<script>alert('Error: " . $conn->error . "');</script>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kafèa-Kiosk | Sign Up</title>
    <link rel="icon" href="assets/img/icon.png">
    <link rel="stylesheet" href="css/mainpage.css">
    <link rel="stylesheet" href="css/signup.css">
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
            <header>Sign Up Form</header>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
              <div class="field">
                <span class="fa fa-user"></span>
                <input type="text" name="fullname" required placeholder="Full Name" 
                    value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>">
            </div>
            <div class="field space">
                <span class="fa fa-envelope"></span>
                <input type="email" name="email" required placeholder="Email">
            </div>
            <div class="field space">
                <span class="fa fa-phone"></span>
                <input type="tel" name="phone" required placeholder="Phone Number (09xxxxxxxxx)" 
                       pattern="09[0-9]{9}" 
                       maxlength="11"
                       inputmode="numeric" 
                       onkeypress="return /[0-9]/i.test(event.key)"
                       oninput="validatePhoneNumber(this)"
                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>
                <div class="field space">
                    <span class="fa fa-lock"></span>
                    <input type="password" class="pass-key" name="password" id="password" required placeholder="Password">
                    <span class="show">SHOW</span>
                </div>
                <div class="field space">
                    <span class="fa fa-lock"></span>
                    <input type="password" class="pass-key" name="confirm_password" id="confirm_password" required placeholder="Confirm Password">
                    <span class="show">SHOW</span>
                </div><br>
                <div class="password-message"></div><br>
                <div class="field">
                    <input type="submit" value="SIGN UP">
                </div><br>
                <div class="signup">
                    Already have an account?
                    <a href="signin.php">Login Now</a>
                </div>
            </form>
        </div>
    </div>

    <script>
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

        function validatePhoneNumber(input) {
            // Remove non-numeric characters
            input.value = input.value.replace(/[^0-9]/g, '');
             
            // If it's empty, don't enforce the 09
            if (input.value.length === 0) return;
             
            // If first digit isn't 0, force it to start with 0
            if (input.value[0] !== '0') {
                input.value = '0' + input.value;
            }
             
            // If second digit isn't 9 after 0, force it to 09
            if (input.value.length >= 2 && input.value[1] !== '9') {
                input.value = '09' + input.value.substring(2);
            }
             
            // Ensure it doesn't exceed 11 digits
            if (input.value.length > 11) {
                input.value = input.value.slice(0, 11);
            }
        }

        // Password confirmation validation
        document.getElementById("confirm_password").addEventListener("input", function () {
            const password = document.getElementById("password").value;
            const confirmPassword = this.value;
            const message = document.querySelector(".password-message");

            if (confirmPassword === "") {
                message.style.display = "none";
            } else if (password === confirmPassword) {
                message.style.display = "block";
                message.textContent = "✔ Passwords match";
                message.style.color = "green";
                message.style.backgroundColor = "#C7DB9C";
            } else {
                message.style.display = "block";
                message.textContent = "✖ Passwords do not match";
                message.style.color = "red";
                message.style.backgroundColor = "beige";
            }
        });

        // Prevent form submission if passwords don't match
        document.querySelector("form").addEventListener("submit", function (e) {
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirm_password").value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert("Error: Passwords do not match. Please check again.");
            }
        });
    </script>
</body>
</html>