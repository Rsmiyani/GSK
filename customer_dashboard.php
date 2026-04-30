<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Ghanshyam Bakery</title>
    
    <!-- Link to our external CSS file - controls how the page looks -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Link to Google Fonts for elegant typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&family=Playfair+Display:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">

    <!-- HEADER SECTION FOR LOGGED-IN CUSTOMER -->
    <header>
        <div class="logo">
            <a href="customer_dashboard.php" style="display:flex; align-items:center; gap:15px; text-decoration:none;">
                <img src="assets/logo/image.png" alt="Ghanshyam Bakery Logo">
                <h1>Ghanshyam Bakery & Live Cake Shop</h1>
            </a>
        </div>
        <nav>
            <ul>
                <li><a href="customer_dashboard.php">Dashboard</a></li>
                <li><a href="index.php#featured">Shop Cakes</a></li>
                <li><a href="login.php" class="btn-login" style="background-color: #5a2d2d; color:#fff5e6 !important;">Logout</a></li>
            </ul>
        </nav>
    </header>

    <!-- DASHBOARD CONTAINER -->
    <div class="dashboard-container">
        
        <!-- SIDEBAR -->
        <aside class="dashboard-sidebar">
            <div class="user-info">
                <div class="avatar">
                    <span>JD</span>
                </div>
                <h3>John Doe</h3>
                <p>john.doe@example.com</p>
                <p class="loyalty">Loyalty Points: <strong>120</strong></p>
            </div>
            <ul class="sidebar-links">
                <li><a href="#" class="active">Overview</a></li>
                <li><a href="#">Order History</a></li>
                <li><a href="#">My Favorites</a></li>
                <li><a href="#">Find Nearest Branch</a></li>
                <li><a href="#">Account Settings</a></li>
            </ul>
        </aside>

        <!-- MAIN DASHBOARD CONTENT -->
        <main class="dashboard-content">
            <div class="dashboard-header">
                <h2>Welcome Back, John! 🎂</h2>
                <p>Your current cravings and fresh bake orders are managed right here.</p>
            </div>

            <!-- STATS WIDGETS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon">📦</div>
                    <div class="info">
                        <h4>Total Orders</h4>
                        <p>5 Cakes</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon">🏃</div>
                    <div class="info">
                        <h4>Active Orders</h4>
                        <p>1 In Progress</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon">📍</div>
                    <div class="info">
                        <h4>Primary Branch</h4>
                        <p>Downtown Bakery</p>
                    </div>
                </div>
            </div>

            <!-- RECENT ORDERS SECTION -->
            <div class="recent-orders">
                <h3>Recent Orders</h3>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Item</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#GB-1025</td>
                            <td>Chocolate Truffle (1kg)</td>
                            <td>Oct 20, 2026</td>
                            <td><span class="status completed">Delivered</span></td>
                            <td>₹450</td>
                        </tr>
                        <tr>
                            <td>#GB-1038</td>
                            <td>Live Fruit Forest (500g)</td>
                            <td>Nov 1, 2026</td>
                            <td><span class="status pending">Baking...</span></td>
                            <td>₹350</td>
                        </tr>
                        <tr>
                            <td>#GB-1011</td>
                            <td>Red Velvet Anniversary</td>
                            <td>Sept 15, 2026</td>
                            <td><span class="status completed">Picked Up</span></td>
                            <td>₹600</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <!-- SIMPLE FOOTER FOR DASHBOARD -->
    <div class="dashboard-footer">
        <p>&copy; 2026 Ghanshaym bakery and live cakeshop. All rights reserved.</p>
    </div>

</body>
</html>