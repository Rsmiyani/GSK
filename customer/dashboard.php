<?php
/**
 * customer/dashboard.php
 * ======================
 * CUSTOMER HOME DASHBOARD
 *
 * This is the first page customers see after login.
 * It shows:
 *   - A welcome message
 *   - Their recent orders
 *   - A button to find nearby shops
 *   - Quick stats (total orders, pending orders)
 */

// ─── Security: Only logged-in customers can see this page ─────────────────────
$required_role = 'customer';
require_once '../includes/auth_check.php';  // Check login + role
require_once '../config/db.php';             // Connect to database

// ─── Fetch Customer Stats ─────────────────────────────────────────────────────
$uid = $_SESSION['user_id']; // Current user's ID from session

// Count total orders placed by this customer
$totalOrders = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders WHERE customer_id = $uid");
if ($row = mysqli_fetch_assoc($res)) $totalOrders = $row['cnt'];

// Count pending orders (not yet completed)
$pendingOrders = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders WHERE customer_id = $uid AND status IN ('pending','preparing','ready')");
if ($row = mysqli_fetch_assoc($res)) $pendingOrders = $row['cnt'];

// Fetch last 5 orders for the "Recent Orders" table
$recentOrders = mysqli_query($conn,
    "SELECT o.id, o.order_type, o.total_amount, o.status, o.created_at, s.name AS shop_name
     FROM orders o
     JOIN shops s ON o.shop_id = s.id
     WHERE o.customer_id = $uid
     ORDER BY o.created_at DESC
     LIMIT 5"
);

// Show welcome message for new users
$isNew = isset($_GET['welcome']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Ghanshyam Bakery</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <!-- Main site CSS + Dashboard CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body class="dashboard-body">

<!-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR NAVIGATION
     Left panel with links to all customer pages
════════════════════════════════════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
    <!-- Brand Logo at top -->
    <div class="sidebar-brand">
        <img src="../assets/logo/image.png" alt="GSK Logo">
        <div>
            <h2>Ghanshyam Bakery</h2>
            <span>Customer Portal</span>
        </div>
    </div>

    <!-- Navigation Links -->
    <nav class="sidebar-nav">
        <span class="nav-section-label">Main Menu</span>
        <!-- 'active' class highlights the current page -->
        <a href="dashboard.php" class="active">
            <span class="nav-icon">🏠</span> Dashboard
        </a>
        <a href="shops.php">
            <span class="nav-icon">📍</span> Find Shops
        </a>
        <a href="cart.php">
            <span class="nav-icon">🛒</span> My Cart
        </a>
        <a href="my_orders.php">
            <span class="nav-icon">📦</span> My Orders
        </a>
    </nav>

    <!-- Logout at the bottom of the sidebar -->
    <div class="sidebar-footer">
        <a href="../logout.php">
            <span>🚪</span> Logout
        </a>
    </div>
</aside>

<!-- ═══════════════════════════════════════════════════════════════════════════
     MAIN CONTENT AREA
     Everything to the right of the sidebar
════════════════════════════════════════════════════════════════════════════ -->
<div class="main-content">

    <!-- Top Bar: page title + user info -->
    <div class="topbar">
        <div class="topbar-title">
            <h1>Welcome Back! 👋</h1>
            <p>Ready to order something delicious?</p>
        </div>
        <div class="topbar-user">
            <div class="user-info">
                <!-- Display the logged-in user's name from the session -->
                <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                <span><?= $_SESSION['role'] ?></span>
            </div>
            <!-- Avatar: first letter of user's name -->
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="page-body">

        <!-- Welcome Alert for new users -->
        <?php if ($isNew): ?>
        <div class="alert alert-success">
            🎉 Welcome to Ghanshyam Bakery! Your account is ready. Start by finding a shop near you!
        </div>
        <?php endif; ?>

        <!-- ─── Stat Cards ─────────────────────────────────────────────────── -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon pink">📦</div>
                <div class="stat-info">
                    <h3><?= $totalOrders ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">⏳</div>
                <div class="stat-info">
                    <h3><?= $pendingOrders ?></h3>
                    <p>Active Orders</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">🏪</div>
                <div class="stat-info">
                    <h3>3</h3>
                    <p>Nearby Branches</p>
                </div>
            </div>
        </div>

        <!-- ─── Quick Action Buttons ──────────────────────────────────────── -->
        <div class="table-card" style="margin-bottom:24px;">
            <h2 style="margin-bottom:16px;">🚀 Quick Actions</h2>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <a href="shops.php" class="btn btn-primary">📍 Find Nearby Shops</a>
                <a href="cart.php"  class="btn btn-outline">🛒 View My Cart</a>
                <a href="my_orders.php" class="btn btn-outline">📦 Track My Orders</a>
            </div>
        </div>

        <!-- ─── Recent Orders Table ───────────────────────────────────────── -->
        <div class="table-card">
            <div class="table-card-header">
                <h2>📋 Recent Orders</h2>
                <a href="my_orders.php" class="btn btn-outline btn-sm">View All</a>
            </div>

            <?php if (mysqli_num_rows($recentOrders) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Shop</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = mysqli_fetch_assoc($recentOrders)): ?>
                    <tr>
                        <td><strong>#<?= $order['id'] ?></strong></td>
                        <td><?= htmlspecialchars($order['shop_name']) ?></td>
                        <td>
                            <span class="badge badge-<?= $order['order_type'] ?>">
                                <?= $order['order_type'] === 'delivery' ? '🚚 Delivery' : '🏪 Pickup' ?>
                            </span>
                        </td>
                        <td><strong>₹<?= number_format($order['total_amount'], 2) ?></strong></td>
                        <td>
                            <!-- Dynamic badge color based on order status -->
                            <span class="badge badge-<?= $order['status'] ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </td>
                        <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <!-- Show this when no orders exist yet -->
            <div class="empty-state">
                <div class="empty-icon">🎂</div>
                <h3>No orders yet!</h3>
                <p>Find a nearby shop and place your first order.</p>
                <a href="shops.php" class="btn btn-primary" style="margin-top:16px;">Browse Shops</a>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- end .page-body -->
</div><!-- end .main-content -->

</body>
</html>
