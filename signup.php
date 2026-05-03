<?php
/**
 * signup.php
 * =========
 * PREMIUM SIGNUP PAGE - Precise Layout for Maximum Screen Fit
 */
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Sign Up - Ghanshyam Bakery & Live Cake Shop</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
              "surface-container-low": "#f5f3ee", "on-secondary-fixed-variant": "#643c35", "on-tertiary-fixed": "#400009", "surface-dim": "#dbdad4",
              "primary-fixed": "#fbdbde", "tertiary": "#be0630", "background": "#fbf9f3", "primary-fixed-dim": "#debfc2", "error-container": "#ffdad6",
              "secondary": "#7f534b", "surface": "#fbf9f3", "primary": "#70585b", "surface-container-high": "#eae8e2", "surface-variant": "#e4e2dd",
              "surface-container-highest": "#e4e2dd", "inverse-surface": "#30312d", "primary-container": "#fadadd", "secondary-fixed": "#ffdad4",
              "surface-bright": "#fbf9f3", "outline": "#807475", "surface-container": "#f0eee8", "on-primary": "#ffffff", "secondary-container": "#fec4ba",
              "on-surface": "#1b1c19", "on-secondary": "#ffffff", "outline-variant": "#d2c3c4", "on-tertiary": "#ffffff", "on-background": "#1b1c19",
              "surface-container-lowest": "#ffffff", "tertiary-fixed-dim": "#ffb3b3", "on-secondary-container": "#7a4f47", "inverse-primary": "#debfc2",
              "on-primary-container": "#765e61", "surface-tint": "#70585b", "tertiary-container": "#ffd9d8", "on-primary-fixed": "#281719",
              "on-surface-variant": "#4f4445", "on-tertiary-container": "#c61235", "on-error": "#ffffff", "secondary-fixed-dim": "#f2b9af",
              "error": "#ba1a1a", "on-error-container": "#93000a", "on-tertiary-fixed-variant": "#920022", "on-primary-fixed-variant": "#574144",
              "on-secondary-fixed": "#31120d", "inverse-on-surface": "#f2f1eb", "tertiary-fixed": "#ffdad9"
            },
            "fontFamily": {
              "headline": ["Noto Serif", "serif"],
              "sans": ["Plus Jakarta Sans", "sans-serif"]
            }
          }
        }
      }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .slider-item { transition: opacity 1.5s ease-in-out; position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; }
        .slider-item.active { opacity: 1; }
        body, html { height: 100%; overflow: hidden; }
        .hero-content h2 { transform: translateY(20px); opacity: 0; transition: all 1s ease-out 0.5s; }
        .slider-item.active .hero-content h2 { transform: translateY(0); opacity: 1; }
        .hero-content p { transform: translateY(20px); opacity: 0; transition: all 1s ease-out 0.8s; }
        .slider-item.active .hero-content p { transform: translateY(0); opacity: 1; }
        .error-placeholder { min-height: 38px; }
    </style>
</head>
<body class="bg-surface font-sans text-on-surface antialiased">

<!-- Floating Back Button -->
<a href="index.php" class="fixed top-8 right-8 z-50 flex items-center gap-2 px-5 py-2.5 bg-stone-100/90 backdrop-blur-lg border border-stone-200 text-amber-950 rounded-full hover:bg-stone-200 hover:shadow-md transition-all group shadow-sm">
    <span class="material-symbols-outlined text-[20px] group-hover:-translate-x-1 transition-transform">arrow_back</span>
    <span class="text-sm font-bold tracking-tight">Back to Home</span>
</a>

<div class="flex h-screen w-full overflow-hidden">
    <!-- Left Side: Image Slider -->
    <div class="hidden lg:block relative w-7/12 h-full bg-stone-200 overflow-hidden">
        <div id="imageSlider" class="absolute inset-0">
            <!-- Slide 1 -->
            <div class="slider-item absolute inset-0 active">
                <img src="assets/login-page/4.jpg" class="w-full h-full object-cover" alt="Bakery 4">
                <div class="absolute inset-0 bg-black/40 flex items-center justify-center text-center p-16">
                    <div class="hero-content max-w-xl">
                        <h2 class="font-headline text-5xl md:text-6xl text-white mb-6 drop-shadow-2xl">Join Our Bakery<br>Family Today</h2>
                        <p class="text-white/90 text-xl md:text-2xl italic font-medium drop-shadow-lg">“Register to experience the magic of freshly baked delights.”</p>
                    </div>
                </div>
            </div>
            <!-- Slide 2 -->
            <div class="slider-item absolute inset-0">
                <img src="assets/login-page/5.jpg" class="w-full h-full object-cover" alt="Bakery 5">
                <div class="absolute inset-0 bg-black/40 flex items-center justify-center text-center p-16">
                    <div class="hero-content max-w-xl">
                        <h2 class="font-headline text-5xl md:text-6xl text-white mb-6 drop-shadow-2xl">Pure Ingredients,<br>Pure Love</h2>
                        <p class="text-white/90 text-xl md:text-2xl italic font-medium drop-shadow-lg">“Every member gets a front-row seat to our live baking show.”</p>
                    </div>
                </div>
            </div>
            <!-- Slide 3 -->
            <div class="slider-item absolute inset-0">
                <img src="assets/login-page/6.jpg" class="w-full h-full object-cover" alt="Bakery 6">
                <div class="absolute inset-0 bg-black/40 flex items-center justify-center text-center p-16">
                    <div class="hero-content max-w-xl">
                        <h2 class="font-headline text-5xl md:text-6xl text-white mb-6 drop-shadow-2xl">A Sweet Reward<br>in Every Bite</h2>
                        <p class="text-white/90 text-xl md:text-2xl italic font-medium drop-shadow-lg">“Unlock exclusive flavors and faster checkout across all branches.”</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side: Signup Form -->
    <div class="w-full lg:w-5/12 h-full flex items-center justify-center p-6 sm:p-12 bg-background relative z-10">
        <div class="w-full max-w-md space-y-5">
            <div class="flex flex-col items-center lg:items-start">
                <a href="index.php" class="flex items-center gap-3 mb-5">
                    <img src="assets/logo/image.png" alt="Logo" class="w-10 h-10 object-contain">
                    <span class="font-headline text-xl text-amber-950 font-bold tracking-tight">Ghanshyam Bakery</span>
                </a>
                <h2 class="font-headline text-3xl text-amber-950 mb-0.5">Create Account</h2>
                <p class="text-stone-500 text-sm">Become a member and start your journey</p>
            </div>

            <div class="error-placeholder">
                <?php if (isset($_GET['error'])): ?>
                    <div class="bg-red-50 border border-red-100 text-red-700 px-4 py-2.5 rounded-xl text-xs flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px]">error</span>
                        <?= htmlspecialchars($_GET['error']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <form action="signup_process.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3.5">
                <div class="md:col-span-2">
                    <label for="name" class="block text-[10px] font-bold text-amber-950 mb-1.5 uppercase tracking-widest">Full Name</label>
                    <input type="text" id="name" name="name" required 
                           class="w-full px-4 py-2.5 rounded-xl border border-stone-200 focus:ring-2 focus:ring-secondary/20 focus:border-secondary outline-none transition-all placeholder:text-stone-400 text-sm"
                           placeholder="John Doe">
                </div>

                <div>
                    <label for="phone" class="block text-[10px] font-bold text-amber-950 mb-1.5 uppercase tracking-widest">Phone</label>
                    <input type="tel" id="phone" name="phone" required 
                           class="w-full px-4 py-2.5 rounded-xl border border-stone-200 focus:ring-2 focus:ring-secondary/20 focus:border-secondary outline-none transition-all placeholder:text-stone-400 text-sm"
                           placeholder="+91 00000 00000">
                </div>

                <div>
                    <label for="email" class="block text-[10px] font-bold text-amber-950 mb-1.5 uppercase tracking-widest">Email</label>
                    <input type="email" id="email" name="email" required 
                           class="w-full px-4 py-2.5 rounded-xl border border-stone-200 focus:ring-2 focus:ring-secondary/20 focus:border-secondary outline-none transition-all placeholder:text-stone-400 text-sm"
                           placeholder="john@example.com">
                </div>

                <div>
                    <label for="password" class="block text-[10px] font-bold text-amber-950 mb-1.5 uppercase tracking-widest">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-2.5 rounded-xl border border-stone-200 focus:ring-2 focus:ring-secondary/20 focus:border-secondary outline-none transition-all placeholder:text-stone-400 text-sm"
                               placeholder="••••••••">
                        <button type="button" onclick="togglePwd('password', 'eye1')" class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 hover:text-secondary">
                            <span class="material-symbols-outlined text-[18px]" id="eye1">visibility</span>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="confirm_password" class="block text-[10px] font-bold text-amber-950 mb-1.5 uppercase tracking-widest">Confirm</label>
                    <div class="relative">
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="w-full px-4 py-2.5 rounded-xl border border-stone-200 focus:ring-2 focus:ring-secondary/20 focus:border-secondary outline-none transition-all placeholder:text-stone-400 text-sm"
                               placeholder="••••••••">
                        <button type="button" onclick="togglePwd('confirm_password', 'eye2')" class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 hover:text-secondary">
                            <span class="material-symbols-outlined text-[18px]" id="eye2">visibility</span>
                        </button>
                    </div>
                </div>

                <div class="md:col-span-2 pt-2">
                    <button type="submit" class="w-full bg-secondary text-white py-3.5 rounded-xl font-bold hover:bg-on-secondary-fixed-variant transition-all shadow-lg shadow-secondary/10 flex items-center justify-center gap-2 text-sm tracking-wide">
                        CREATE ACCOUNT
                        <span class="material-symbols-outlined text-[20px]">person_add</span>
                    </button>
                </div>
            </form>

            <div class="relative py-2">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-stone-200"></div></div>
                <div class="relative flex justify-center text-[10px] uppercase"><span class="bg-background px-4 text-stone-400 font-bold tracking-[0.2em]">Already a member?</span></div>
            </div>

            <p class="text-center text-xs text-stone-500">
                <a href="login.php" class="font-bold text-secondary hover:underline">Sign In to Your Account</a>
            </p>
        </div>
    </div>
</div>

<script>
    // Image Slider Logic
    const slides = document.querySelectorAll('.slider-item');
    let currentSlide = 0;

    function nextSlide() {
        slides[currentSlide].classList.remove('active');
        currentSlide = (currentSlide + 1) % slides.length;
        slides[currentSlide].classList.add('active');
    }

    setInterval(nextSlide, 5000);

    // Toggle Password Visibility
    function togglePwd(id, eyeId) {
        const input = document.getElementById(id);
        const icon = document.getElementById(eyeId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.textContent = 'visibility_off';
        } else {
            input.type = 'password';
            icon.textContent = 'visibility';
        }
    }
</script>

</body>
</html>