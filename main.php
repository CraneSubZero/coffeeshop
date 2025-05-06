<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kafèa-Kiosk | Premium Self-Service Coffee</title>
    <link rel="icon" href="assets/img/icon.png">
    <link rel="stylesheet" href="css/mainpage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        <div class="nav-end">
            <a href="signup.php" class="btn-outline">Sign Up</a>
            <a href="signin.php" class="btn-primary">Log In</a>
        </div>
    </div>

    <header class="hero">
        <div class="hero-content">
            <h1>Your Cup, Your Choice, Your Time</h1>
            <p>Premium self-service coffee at your fingertips</p>
            <div class="hero-buttons">
                <a href="#how-it-works" class="btn-outline-light">How It Works</a>
                <a href="signin.php" class="btn-outline-light">Order Now</a>
            </div>
        </div>
    </header>

    <section id="how-it-works" class="section steps">
        <h2>How Kafèa-Kiosk Works</h2>
        <div class="steps-container">
            <div class="step">
                <div class="step-number">1</div>
                <i class="fas fa-user-circle"></i>
                <h3>Sign In</h3>
                <p>To view menus</p>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <i class="fas fa-coffee"></i>
                <h3>Customize</h3>
                <p>Select from our premium coffee options and Bread & Pastries</p>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <i class="fas fa-mobile-alt"></i>
                <h3>Pay</h3>
                <p>Quick and secure digital payment</p>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <i class="fas fa-glass-cheers"></i>
                <h3>Enjoy</h3>
                <p>Your order in minutes</p>
            </div>
        </div>
    </section>

    <section id="menu" class="section menu">
       <h2>🔥 Hot Coffee Favorites ☕</h2>
       
        <div class="menu-items">
            <div class="menu-item" data-category="espresso">
                <img src="assets/menu/espresso.jpg" alt="Espresso">
                <h3>Espresso</h3>
                <p>Rich and intense single shot</p>
            </div>
            <div class="menu-item" data-category="espresso">
                <img src="assets/menu/cappuccino.jpeg" alt="Cappuccino">
                <h3>Cappuccino</h3>
                <p>Espresso with steamed milk foam</p>     
            </div>

            <div class="menu-item" data-category="espresso">
                <img src="assets/menu/latte.jpg" alt="Café Latte">
                <h3>Café Latte</h3>
                <p>Double espresso with velvety steamed milk (12oz/16oz)</p>
            </div>
            <div class="menu-item" data-category="espresso specialty">
                <img src="assets/menu/mocha.jpg" alt="Spiced Mocha">
                <h3>Spiced Mocha</h3>
                <p>Espresso + dark chocolate + cayenne & cinnamon (12oz)</p>
            </div>   
        </div>
    </section>

    <section id="iced-coffee" class="section menu">
    <h2>❄️ Chilled Coffee Creations 🧊</h2>
   
    <div class="menu-items">
        <div class="menu-item" data-category="iced-specialty">
            <img src="assets/menu/affogato.jpg" alt="Affogato">
            <h3>Affogato</h3>
            <p>Vanilla gelato "drowned" in a shot of hot espresso</p>
        </div>
        <div class="menu-item" data-category="iced-blended">
            <img src="assets/menu/frappuccino.jpg" alt="Caramel Frappuccino">
            <h3>Caramel Frappuccino</h3>
            <p>Blended coffee with milk, ice and caramel syrup</p>
        </div>

        <div class="menu-item" data-category="iced-classic">
            <img src="assets/menu/iced latte.jpg" alt="Iced Latte">
            <h3>Iced Latte</h3>
            <p>Espresso with chilled milk over ice (12oz/16oz)</p>
        </div>
        <div class="menu-item" data-category="iced-brew">
            <img src="assets/menu/iced brew.jpg" alt="Iced Brew">
            <h3>Iced Brew</h3>
            <p>Slow-drip cold brew concentrate served over ice (16oz)</p>
        </div>   
    </div>
</section>

<section id="pastries" class="section menu">
    <h2>🥐 Fresh Baked Pastries 🧈</h2>
   
    <div class="menu-items">
        <div class="menu-item" data-category="pastry">
            <img src="assets/menu/croissants.jpg" alt="Croissant">
            <h3>Croissant</h3>
            <p>Buttery, flaky French-style pastry</p>
        </div>
        <div class="menu-item" data-category="pastry">
            <img src="assets/menu/danish.jpg" alt="Danish">
            <h3>Danish</h3>
            <p>Sweet pastry with fruit or cream cheese filling</p>
        </div>

        <div class="menu-item" data-category="pastry">
            <img src="assets/menu/muffin.jpg" alt="Muffin">
            <h3>Muffin</h3>
            <p>Fresh-baked daily (Blueberry/Chocolate Chip)</p>
        </div>
        <div class="menu-item" data-category="pastry">
            <img src="assets/menu/cinnamon roll.jpg" alt="Cinnamon Roll">
            <h3>Cinnamon Roll</h3>
            <p>Soft dough with cinnamon sugar, topped with glaze</p>
        </div>   
    </div>
</section>

<section id="breads" class="section menu">
    <h2>🍞 Artisan Breads 🥖</h2>
   
    <div class="menu-items">
        <div class="menu-item" data-category="bread">
            <img src="assets/menu/baguette.jpg" alt="Baguette">
            <h3>Baguette</h3>
            <p>Classic French long loaf with crisp crust</p>
        </div>
        <div class="menu-item" data-category="bread">
            <img src="assets/menu/ciabatta.jpg" alt="Ciabatta">
            <h3>Ciabatta</h3>
            <p>Italian white bread with porous texture</p>
        </div>

        <div class="menu-item" data-category="bread">
            <img src="assets/menu/focaccia.jpg" alt="Focaccia">
            <h3>Focaccia</h3>
            <p>Olive oil-infused Italian flatbread with rosemary</p>
        </div>
        <div class="menu-item" data-category="bread">
            <img src="assets/menu/rye bread.jpg" alt="Rye Bread">
            <h3>Rye Bread</h3>
            <p>Hearty dark bread with distinctive flavor</p>
        </div>   
    </div>
</section>

    <section class="section features">
        <h2>Why Choose Kafèa-Kiosk</h2>
        <div class="features-grid">
            <div class="feature">
                <i class="fas fa-clock"></i>
                <h3>24/7 Service</h3>
                <p>Get your coffee fix anytime, day or night</p>
            </div>
            <div class="feature">
                <i class="fas fa-cogs"></i>
                <h3>Full Customization</h3>
                <p>Control strength, milk, sweetness and more</p>
            </div>
            <div class="feature">
                <i class="fas fa-bolt"></i>
                <h3>Fast Service</h3>
                <p>No waiting in line - your coffee in under 2 minutes</p>
            </div>
            <div class="feature">
                <i class="fas fa-leaf"></i>
                <h3>Premium Beans</h3>
                <p>Ethically sourced specialty grade coffee</p>
            </div>
        </div>
    </section>

    <section id="about" class="section about">
        <div class="about-content">
            <h2>About Kafèa-Kiosk</h2>
            <p>Kafèa Kiosk is a self-service café that blends innovation and tradition by allowing customers to personalize their coffee using high-tech machines and premium, locally sourced ingredients. Inspired by students’ love for coffee as a study aid, the founders designed the kiosk to promote alertness, reduce stress, and provide a relaxing experience.</p>
            <p> The café emphasizes convenience through user-friendly self-service kiosks, reducing wait times and enhancing customer flow. It also supports sustainability by using eco-friendly materials and offering compostable cups. A unique feature of Kafèa Kiosk is its mini book room, creating a cozy atmosphere for coffee lovers and readers alike.</p>
            <a href="#" class="btn-outline">Learn More</a>
        </div>
        <div class="about-image">
            <img src="assets/img/store.jpg" alt="Kafèa-Kiosk Machine">
        </div>
    </section>

  <section id="contact" class="section contact">
    <h2>Contact Us</h2>
    <div class="contact-container">
        <div class="contact-info">
            <h3>Get in Touch</h3>
            <p><i class="fas fa-map-marker-alt"></i> Bacolod City, Negros Occidental</p>
            <p><i class="fas fa-phone"></i> 1234 546 8790</p>
            <p><i class="fas fa-envelope"></i> info@kafeakiosk.com</p>
        </div>
        <div class="contact-image">
            <img src="assets/img/qrcode.png" alt="Contact Kafèa-Kiosk">
        </div>
    </div>
</section>

    <footer class="footer">
      
        <div class="footer-bottom">
            <p>&copy; 2025 Kafèa-Kiosk. All rights reserved.</p>
        </div>
    </footer>

    <script src="js/mainpage.js"></script>
</body>

</html>