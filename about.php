<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Ghanshyam Bakery</title>
    
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Link to Google Fonts for elegant typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&family=Playfair+Display:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
    
    <style>
        .page-content {
            max-width: 1000px;
            margin: 60px auto;
            padding: 40px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(90, 45, 45, 0.08);
            border: 1px solid rgba(245, 198, 203, 0.4);
            text-align: center;
        }
        .page-content h2 {
            color: #5a2d2d;
            margin-bottom: 20px;
            font-size: 2.5rem;
            position: relative;
        }
        .page-content h3 {
            color: #5a2d2d;
            margin-top: 30px;
            margin-bottom: 10px;
            font-size: 1.3rem;
        }
        .page-content p {
            color: #5a2d2d;
            line-height: 1.8;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        .about-logo {
            width: 250px;
            height: 250px;
            border-radius: 50%;
            object-fit: cover;
            margin: 30px auto;
            display: block;
            background-color: #fff; /* Ensure transparent logos look good */
            border: 5px solid #f5c6cb;
            box-shadow: 0 4px 20px rgba(245, 198, 203, 0.8);
            transition: transform 0.3s ease;
        }
        .about-logo:hover {
            transform: scale(1.05) rotate(3deg);
        }
    </style>
</head>
<body>

    <!-- HEADER SECTION -->
    <header>
        <div class="logo">
            <a href="index.php" style="display:flex; align-items:center; gap:15px; text-decoration:none;">
                <img src="assets/logo/image.png" alt="Ghanshaym bakery and live cakeshop">
                <h1>Ghanshyam Bakery & Live Cake Shop</h1>
            </a>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php" style="color: #f5c6cb; text-shadow: 0 0 8px rgba(245, 198, 203, 0.8);">About Us</a></li>
                <li><a href="index.php#featured">Featured Cakes</a></li>
                <li><a href="login.php" class="btn-login">Login</a></li>
            </ul>
        </nav>
    </header>

    <!-- CONTENT SECTION -->
    <section class="container page-content">
        <h2>Our Story</h2>
        
        <img src="assets/logo/image.png" alt="Ghanshyam Bakery Logo" class="about-logo">
        
        <p>Welcome to <strong>Ghanshyam Bakery and Live Cake Shop</strong>.</p>

        <p>What started as a small, humble bakery out of a passion for crafting the perfect dessert has now blossomed into a multi-branch network delivering joy straight to your doorstep. For years, we have poured our hearts, souls, and the finest ingredients into every cake we bake.</p>
        
        <p>Today, our mission remains the same: ensuring every customer experiences the magic of freshly baked, live-crafted cakes. With our new digital platform, we want to make finding, personalizing, and ordering your favorite cake as effortless as taking that delicious first bite.</p>

        <h3>Our Live Cake Philosophy</h3>
        <p>Unlike traditional bakeries that rely on frozen stockpiles, the "Live Cake" experience means your cake is prepared right in front of you—fresh sponges, hand-whipped frostings, and customized toppings exactly how you want them.</p>
        
        <h3>Multi-branch Technology</h3>
        <p>We built this smart platform utilizing location-based routing so that no matter where you are celebrating, you can connect to the Ghanshyam Bakery branch nearest to you. Lightning-fast deliveries, or convenient scheduled pickups, the choice is entirely yours.</p>

    </section>

    <!-- FOOTER SECTION -->
    <footer id="contact">
        <div class="footer-content">
            <div class="footer-box">
                <img src="assets/logo/image.png" alt="Ghanshyam Bakery" class="footer-logo">
                <p>Bringing the finest, most delicious live cakes to multiple locations across the city.</p>
            </div>
            <div class="footer-box">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="index.php#featured">Featured Cakes</a></li>
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
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Ghanshaym bakery and live cakeshop. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>