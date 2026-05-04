<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - Ghanshyam Bakery</title>
    
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Link to Google Fonts for elegant typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&family=Playfair+Display:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
    
    <style>
        .page-content {
            max-width: 900px;
            margin: 60px auto;
            padding: 40px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(90, 45, 45, 0.08);
            border: 1px solid rgba(245, 198, 203, 0.4);
        }
        .page-content h2 {
            color: #5a2d2d;
            margin-bottom: 20px;
            text-align: center;
        }
        .page-content h3 {
            color: #5a2d2d;
            margin-top: 30px;
            margin-bottom: 10px;
            font-size: 1.3rem;
        }
        .page-content p, .page-content ul {
            color: #5a2d2d;
            line-height: 1.8;
            margin-bottom: 15px;
        }
        .page-content ul {
            margin-left: 20px;
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
        <button class="mobile-menu-btn" onclick="document.querySelector('nav ul').classList.toggle('show')" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="index.php#featured">Featured Cakes</a></li>
                <li><a href="login.php" class="btn-login">Login</a></li>
            </ul>
        </nav>
    </header>

    <!-- CONTENT SECTION -->
    <section class="container page-content">
        <h2>Terms and Conditions</h2>
        <p>Welcome to Ghanshyam Bakery & Live Cake Shop. By accessing and using our website, you agree to be bound by the following terms and conditions.</p>

        <h3>1. General Ordering Policy</h3>
        <ul>
            <li>All orders must be placed at least 24 hours in advance for custom cakes.</li>
            <li>We reserve the right to cancel any order due to unforeseen inventory or technical issues.</li>
        </ul>

        <h3>2. Delivery & Pickup</h3>
        <ul>
            <li>Delivery timeframes are estimates. We do our best to deliver within the chosen time slot, but traffic or weather delays may occur.</li>
            <li>For in-store pickups, please bring a valid order ID.</li>
        </ul>

        <h3>3. Cancellations & Refunds</h3>
        <ul>
            <li>Cancellations requested at least 12 hours before the scheduled time may be eligible for a full refund.</li>
            <li>Once an order is in the "Preparing" phase, it cannot be refunded.</li>
        </ul>

        <h3>4. Modifications</h3>
        <p>Ghanshyam Bakery reserves the right to modify these terms at any point without prior notice. Please review this page periodically.</p>
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