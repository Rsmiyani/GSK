<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghanshaym bakery and live cakeshop</title>
    
    <!-- Link to our external CSS file - controls how the page looks -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Link to Google Fonts for elegant typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&family=Playfair+Display:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
</head>
<body>

    <!-- HEADER SECTION -->
    <!-- Contains the Logo and the Navigation Bar -->
    <header>
        <div class="logo">
            <!-- Brand Name of your Dad's business -->
            <img src="assets/logo/image.png" alt="Ghanshaym bakery and live cakeshop">
            <h1>Ghanshyam Bakery & Live Cake Shop</h1>
        </div>
        <!-- Mobile Hamburger Button (visible only on small screens via CSS) -->
        <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Toggle menu" onclick="document.querySelector('nav ul').classList.toggle('show')">
            <span></span><span></span><span></span>
        </button>
        <nav>
            <ul id="mainNav">
                <!-- Navigation links to jump to different parts of the landing page -->
                <li><a href="#home">Home</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="#featured">Featured Cakes</a></li>
                <!-- Login link: This will go to a generalized login page for Customer/Admin/Shopkeeper -->
                <li><a href="login.php" class="btn-login">Login</a></li>
            </ul>
        </nav>
    </header>

    <!-- HERO SECTION (IMAGE SLIDER) -->
    <!-- This block cycles through different images and quotes -->
    <section class="hero" id="home">
        
        <!-- Slide 1 (active by default) -->
        <div class="slide active" style="background-image: url('assets/Hero-Section/img1.jpg');">
            <div class="hero-content">
                <h2>Freshly Baked Cakes, Delivered to You</h2>
                <p>“Order online for fast delivery or schedule a convenient store pickup.”</p>
            </div>
        </div>

        <!-- Slide 2 -->
        <div class="slide" style="background-image: url('assets/Hero-Section/img2.jpg');">
            <div class="hero-content">
                <h2>Aesthetically Crafted Edible Art</h2>
                <p>“Every bite is a masterpiece crafted with premium ingredients and love.”</p>
            </div>
        </div>

        <!-- Slide 3 -->
        <div class="slide" style="background-image: url('assets/Hero-Section/img3.jpg');">
            <div class="hero-content">
                <h2>Celebrate Every Sweet Moment</h2>
                <p>“Make your special occasions unforgettable with our signature custom cakes.”</p>
            </div>
        </div>

        <!-- Slide 4 -->
        <div class="slide" style="background-image: url('assets/Hero-Section/img4.jpg');">
            <div class="hero-content">
                <h2>From Our Oven to Your Doorstep</h2>
                <p>“Experience the joy of warm, freshly baked goods no matter where you are.”</p>
            </div>
        </div>

        <!-- Slide 5 -->
        <div class="slide" style="background-image: url('assets/Hero-Section/img5.jpg');">
            <div class="hero-content">
                <h2>The Perfect Slice of Happiness</h2>
                <p>“Because there is always a reason to share a slice of cake with loved ones.”</p>
            </div>
        </div>

        <!-- Master Call to Action layer (Always visible above the sliding images) -->
        <div class="hero-cta">
            <!-- Call to Action button: This will trigger the Location Detection API in javascript -->
            <button id="findStoresBtn" class="btn-primary">Find Nearby Stores</button>
            <p id="locationStatus" class="location-status"></p>
        </div>

    </section>

    <!-- ABOUT SECTION -->
    <!-- Removed to favor the dedicated about.php page -->
    <!-- You can put something else here later if needed -->

    <!-- HOW IT WORKS SECTION (Adding Details) -->
    <!-- Explaining the smart ordering system visually -->
    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <h2>How Our Smart Bakery Works</h2>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-icon">📍</div>
                    <h3>1. Find Nearby</h3>
                    <p>Use our location system to instantly discover the Ghanshyam Bakery branches closest to you.</p>
                </div>
                <div class="step-card">
                    <div class="step-icon">🍰</div>
                    <h3>2. Pick Your Cake</h3>
                    <p>Browse our catalog of fresh, live cakes and customize your order right from your phone.</p>
                </div>
                <div class="step-card">
                    <div class="step-icon">⏰</div>
                    <h3>3. Delivery or Pickup</h3>
                    <p>Choose lightning-fast home delivery or schedule a convenient time to pick up your cake in-store.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURED CAKES SECTION -->
    <!-- Displaying some popular or best-selling cakes to attract customers -->
    <section class="featured-cakes" id="featured">
        <div class="container">
            <h2>Featured Cakes</h2>
            <!-- Grid container to hold multiple cake items -->
            <div class="cake-grid">
                
                <!-- Cake Item 1 -->
                <div class="cake-card">
                    <!-- Using placeholder images for now -->
                    <img src="https://images.unsplash.com/photo-1578985545062-69928b1d9587?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60" alt="Chocolate Truffle Cake">
                    <h3>Chocolate Truffle</h3>
                    <p>Rich, dark, and incredibly delicious.</p>
                    <span class="price">₹550.00</span>
                </div>

                <!-- Cake Item 2 -->
                <div class="cake-card">
                    <img src="https://images.unsplash.com/photo-1565958011703-44f9829ba187?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60" alt="Red Velvet Cake">
                    <h3>Red Velvet</h3>
                    <p>Classic red velvet with cream cheese frosting.</p>
                    <span class="price">₹650.00</span>
                </div>

                <!-- Cake Item 3 -->
                <div class="cake-card">
                    <img src="https://images.unsplash.com/photo-1464349095431-e9a21285b5f3?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60" alt="Vanilla Strawberry Cake">
                    <h3>Vanilla Strawberry</h3>
                    <p>Light vanilla sponge with fresh strawberries.</p>
                    <span class="price">₹450.00</span>
                </div>

            </div>
        </div>
    </section>

    <!-- FOOTER SECTION (Enhanced with details) -->
    <footer id="contact">
        <div class="footer-content">
            <div class="footer-box">
                <img src="assets/logo/image.png" alt="Ghanshyam Bakery" class="footer-logo">
                <p>Bringing the finest, most delicious live cakes to multiple locations across the city.</p>
            </div>
            <div class="footer-box">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <li><a href="#featured">Featured Cakes</a></li>
                    <li><a href="login.php">Store Login</a></li>
                </ul>
            </div>
            <div class="footer-box">
                <h3>Policies</h3>
                <ul>
                    <li><a href="terms.php">Terms & Conditions</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                </ul>
            </div>
            <div class="footer-box">
                <h3>Contact Us</h3>
                <p>📞 +91 98765 43210</p>
                <p>📧 order@ghanshyambakery.com</p>
                <p>📍 Available at multiple live shop locations</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Ghanshaym bakery and live cakeshop. All rights reserved.</p>
        </div>
    </footer>

    <!-- Link to external JS file: Contains the functionality for finding nearby stores -->
    <script src="assets/js/script.js"></script>
</body>
</html>