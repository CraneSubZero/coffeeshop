<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kafèa-Kiosk  |  Loading</title>
    
    <style>
        body {
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f5f5f5;
            font-family: Arial, sans-serif;
        }

        .loading-container {
            text-align: center;
        }

          .coffee-mug {
            width: 80px;
            height: 100px;
            background-color: #fff;
            border: 8px solid #333;
            border-radius: 5px 5px 50px 50px;
            position: relative;
            margin: 20px auto;
            animation: steam 2s infinite;
        }

        /* New modern café cup style */
        .cafe-cup {
            width: 70px;
            height: 60px;
            background-color: #fff;
            border: 6px solid #333;
            border-radius: 5px 5px 35px 35px;
            position: relative;
            margin: 20px auto;
            animation: steam 2s infinite;
        }

        .cafe-cup .handle {
            width: 20px;
            height: 30px;
            border: 6px solid #333;
            border-left: 0;
            border-radius: 0 15px 15px 0;
            position: absolute;
            right: -26px;
            top: 10px;
        }

        .cafe-cup .saucer {
            width: 100px;
            height: 10px;
            background-color: #fff;
            border: 6px solid #333;
            border-radius: 50%;
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
        }

        .cafe-cup .coffee {
            width: 100%;
            height: 0%;
            background-color: #8B4513;  /* Darker coffee color */
            position: absolute;
            bottom: 0;
            border-radius: 0 0 30px 30px;
            animation: fill 2s infinite;
        }

           .steam {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 5px;
        }

        .steam-line {
            width: 2px;
            height: 20px;
            background-color: #333;
            opacity: 0;
            animation: rise 2s infinite;
        }

        .steam-line:nth-child(1) {
            animation-delay: 0.2s;
            transform: rotate(-5deg);
        }

        .steam-line:nth-child(2) {
            height: 25px;
        }

        .steam-line:nth-child(3) {
            animation-delay: 0.4s;
            transform: rotate(5deg);
        }

        @keyframes rise {
            0% {
                opacity: 0;
                transform: translateY(0) scaleX(1);
            }
            50% {
                opacity: 0.7;
                transform: translateY(-5px) scaleX(0.9);
            }
            100% {
                opacity: 0;
                transform: translateY(-10px) scaleX(0.8);
            }
        }

        h1 {
            color: #333;
            margin-top: 20px;
        }

        .loading-text {
            color: #666;
            margin-top: 10px;
            font-size: 16px;
        }

        @keyframes fill {
            0% { height: 0%; }
            50% { height: 80%; }
            100% { height: 0%; }
        }

        @keyframes steam {
            0% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0); }
        }

        @keyframes rise {
            0% { opacity: 0; transform: translate(-50%, 0); }
            50% { opacity: 1; transform: translate(-50%, -20px); }
            100% { opacity: 0; transform: translate(-50%, -40px); }
        }

        .progress {
            width: 200px;
            height: 4px;
            background-color: #6f4e37;
            border-radius: 2px;
            margin: 20px auto;
            overflow: hidden;
        }

        .progress-bar {
            width: 0%;
            height: 100%;
            background-color: #90EE90;
            animation: progress 5s linear forwards;
        }

        @keyframes progress {
            0% { width: 0%; }
            100% { width: 100%; }
        }
    </style>
    <script>
        // Redirect after 5 seconds
        setTimeout(function() {
            window.location.href = 'main.php';  // redirect to main.php
        }, 5000);

        // Update loading text with countdown
        let timeLeft = 5;
        const countdownElement = document.getElementById('countdown');
        
        const countdown = setInterval(function() {
            timeLeft--;
            if (timeLeft > 0) {
                countdownElement.textContent = timeLeft;
            } else {
                clearInterval(countdown);
            }
        }, 1000);
    </script>
</head>
<body>
    <div class="loading-container">
        <div class="cafe-cup">
            <div class="handle"></div>
            <div class="coffee"></div>
            <div class="steam">
                <div class="steam-line"></div>
                <div class="steam-line"></div>
                <div class="steam-line"></div>
            </div>
            <div class="saucer"></div>
        </div>
        <h1>Kafèa-Kiosk</h1>
        <div class="progress">
            <div class="progress-bar"></div>
        </div>
        <p class="loading-text">Take a Sip for <span id="countdown">5</span> seconds while waiting...</p>
    </div>
</body>
</html>