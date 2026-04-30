<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ghanshyam Bakery and Live Cake Shop</title>
    
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
            <!-- Brand Name of your business -->
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

    <!-- LOGIN HERO AREA -->
    <main class="login-main">
        <!-- Floating Card Container -->
        <div class="login-content">
            <div class="login-card">
                
                <!-- Card Header (Logo & Name) -->
                <div class="login-card-header">
                    <img src="assets/logo/image.png" alt="Bakery Symbol" class="mini-logo">
                    <h2>Ghanshyam Bakery</h2>
                </div>

                <!-- Welcome Message -->
                <div class="login-welcome">
                    <h3>Welcome Back</h3>
                    <p>The scent of fresh cakes awaits you.</p>
                </div>

                <!-- Decorative Divider -->
                <div class="scallop-divider">
                    <span class="line"></span>
                    <span class="cookie-icon">🎂</span>
                    <span class="line"></span>
                </div>

                <!-- Input Form -->
                <form action="login_process.php" method="POST" class="auth-form">
                    
                    <!-- Email input -->
                    <div class="form-group">
                        <label for="email">EMAIL ADDRESS</label>
                        <input type="email" id="email" name="email" placeholder="bonjour@example.com" required>
                    </div>

                    <!-- Password input -->
                    <div class="form-group">
                        <div class="password-top">
                            <label for="password">PASSWORD</label>
                            <a href="#" class="forgot-link">Forgot Password?</a>
                        </div>
                        <div class="input-wrap" style="position:relative;">
                            <input type="password" id="password" name="password"
                                   placeholder="••••••••" required
                                   style="padding-right:48px;">
                            <!-- Eye icon toggle: shows/hides the password -->
                            <button type="button"
                                    id="toggleLoginPwd"
                                    onclick="togglePassword('password','toggleLoginPwd')"
                                    title="Show / hide password"
                                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                                           border:none;background:none;cursor:pointer;padding:4px;
                                           color:#aaa;font-size:1.15rem;line-height:1;transition:color .2s;">
                                <!-- Eye SVG (password hidden state) -->
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

                    <!-- Submit Button uses the same Neon Pink theme -->
                    <button type="submit" class="btn-primary flex-center">
                        SIGN IN 
                        <svg class="login-icon" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                            <path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5a2 2 0 0 0-2 2v4h2V5h14v14H5v-4H3v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"/>
                        </svg>
                    </button>
                </form>

                <!-- Social Divider -->
                <div class="social-divider">
                    <span class="line"></span>
                    <span class="text">OR CONTINUE WITH</span>
                    <span class="line"></span>
                </div>

                <!-- Social Logins -->
                <div class="social-buttons">
                    <button type="button" class="btn-social">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" width="20" height="20">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"></path>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"></path>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"></path>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"></path>
                        </svg>
                        Google
                    </button>
                    <button type="button" class="btn-social">
                        <svg class="w-5 h-5" fill="#1877F2" viewBox="0 0 24 24" width="20" height="20">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"></path>
                        </svg>
                        Facebook
                    </button>
                </div>

            </div>

            <!-- Sign Up Link -->
            <div class="signup-wrap">
                <p>New to our bakery? <a href="signup.php">Sign Up</a></p>
            </div>
        </div>

    </main>

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
 * Switches a password field between visible text and hidden dots.
 * Swaps the SVG eye icon to reflect the current state.
 *
 * @param {string} inputId - id of the <input> element
 * @param {string} btnId   - id of the toggle button
 */
function togglePassword(inputId, btnId) {
    const input = document.getElementById(inputId);
    const btn   = document.getElementById(btnId);
    const icon  = document.getElementById('eye-icon-' + inputId);

    if (input.type === 'password') {
        // Reveal password
        input.type = 'text';
        btn.style.color = '#e91e8c';
        // Eye-slash icon (password is now visible)
        icon.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
            <line x1="1" y1="1" x2="23" y2="23"/>
        `;
    } else {
        // Hide password
        input.type = 'password';
        btn.style.color = '#aaa';
        // Restore open-eye icon
        icon.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
        `;
    }
}
</script>

</body>
</html>