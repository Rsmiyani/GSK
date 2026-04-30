<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Ghanshyam Bakery and Live Cake Shop</title>
    
    <!-- Link to our external CSS file - controls how the page looks -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Link to Google Fonts for elegant typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&family=Playfair+Display:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
</head>
<body class="login-page">

    <!-- HEADER SECTION -->
    <header>
        <div class="logo">
            <a href="index.php" style="display:flex; align-items:center; gap:15px; text-decoration:none;">
                <img src="assets/logo/image.png" alt="Ghanshyam Bakery Logo">
                <h1>Ghanshyam Bakery & Live Cake Shop</h1>
            </a>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="index.php#featured">Featured Cakes</a></li>
            </ul>
        </nav>
    </header>

    <!-- SIGNUP MAIN AREA -->
    <main class="login-main">
        <div class="login-content">
            <div class="login-card">
                
                <!-- Card Header (Logo & Name) -->
                <div class="login-card-header">
                    <img src="assets/logo/image.png" alt="Bakery Symbol" class="mini-logo">
                    <h2>Join Our Bakery</h2>
                </div>

                <!-- Welcome Message -->
                <div class="login-welcome">
                    <h3>Create an Account</h3>
                    <p>Register to order fresh live cakes for pickup or delivery.</p>
                </div>

                <!-- Input Form -->
                <form action="signup_process.php" method="POST" class="auth-form">
                    
                    <!-- Full Name input -->
                    <div class="form-group">
                        <label for="name">FULL NAME</label>
                        <input type="text" id="name" name="name" placeholder="John Doe" required>
                    </div>

                    <!-- Phone input -->
                    <div class="form-group">
                        <label for="phone">PHONE NUMBER</label>
                        <input type="tel" id="phone" name="phone" placeholder="+91 98765 43210" required>
                    </div>

                    <!-- Email input -->
                    <div class="form-group">
                        <label for="email">EMAIL ADDRESS</label>
                        <input type="email" id="email" name="email" placeholder="bonjour@example.com" required>
                    </div>

                    <!-- Password input -->
                    <div class="form-group">
                        <label for="password">PASSWORD</label>
                        <div style="position:relative;">
                            <input type="password" id="password" name="password"
                                   placeholder="••••••••" required
                                   style="padding-right:48px;">
                            <button type="button"
                                    id="togglePassword"
                                    onclick="togglePassword('password','togglePassword')"
                                    title="Show / hide password"
                                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                                           border:none;background:none;cursor:pointer;padding:4px;
                                           color:#aaa;line-height:1;transition:color .2s;">
                                <svg id="eye-icon-password" xmlns="http://www.w3.org/2000/svg"
                                     viewBox="0 0 24 24" width="20" height="20"
                                     fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm Password input -->
                    <div class="form-group">
                        <label for="confirm_password">CONFIRM PASSWORD</label>
                        <div style="position:relative;">
                            <input type="password" id="confirm_password" name="confirm_password"
                                   placeholder="••••••••" required
                                   style="padding-right:48px;">
                            <button type="button"
                                    id="toggleConfirm"
                                    onclick="togglePassword('confirm_password','toggleConfirm')"
                                    title="Show / hide password"
                                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                                           border:none;background:none;cursor:pointer;padding:4px;
                                           color:#aaa;line-height:1;transition:color .2s;">
                                <svg id="eye-icon-confirm_password" xmlns="http://www.w3.org/2000/svg"
                                     viewBox="0 0 24 24" width="20" height="20"
                                     fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-primary flex-center">
                        CREATE ACCOUNT 
                    </button>
                </form>

                <!-- Login Link -->
                <div class="signup-wrap">
                    <p>Already have an account? <a href="login.php">Sign In</a></p>
                </div>
            </div>
        </div>
    </main>

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
                <p>📍 Available at multiple live shop locations</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Ghanshaym bakery and live cakeshop. All rights reserved.</p>
        </div>
    </footer>


<script>
/**
 * togglePassword(inputId, btnId)
 * ================================
 * Switches any password field between visible text and hidden dots.
 * Swaps the eye SVG icon to show current state.
 * This same function works for BOTH the password and confirm-password fields.
 */
function togglePassword(inputId, btnId) {
    const input = document.getElementById(inputId);
    const btn   = document.getElementById(btnId);
    const icon  = document.getElementById('eye-icon-' + inputId);

    if (input.type === 'password') {
        input.type      = 'text';
        btn.style.color = '#e91e8c'; // pink = active
        // Switch to eye-slash (password is visible)
        icon.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
            <line x1="1" y1="1" x2="23" y2="23"/>
        `;
    } else {
        input.type      = 'password';
        btn.style.color = '#aaa'; // grey = inactive
        // Restore open eye (password is hidden)
        icon.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
        `;
    }
}
</script>

</body>
</html>