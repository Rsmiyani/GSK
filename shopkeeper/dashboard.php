<?php
/**
 * shopkeeper/dashboard.php
 * ========================
 * SHOPKEEPER HOME DASHBOARD
 *
 * First page the shopkeeper sees after login.
 * Shows: today's order count, pending orders, products count, recent orders.
 *
 * IMPORTANT: The shopkeeper only sees data for THEIR shop.
 * Their shop ID is stored in $_SESSION['shop_id'] at login time.
 * (Set in login_process.php when role === 'shopkeeper')
 */

// ─── Access Control ────────────────────────────────────────────────────────────
$required_role = 'shopkeeper';
require_once '../includes/auth_check.php'; // Redirects non-shopkeepers away
require_once '../config/db.php';           // Opens MySQL connection ($conn)

// ─── Get This Shopkeeper's Shop ID ────────────────────────────────────────────
// shop_id was stored in the session during login (see login_process.php)
// If it's 0 (null/missing), the admin hasn't assigned a shop to this user yet
$shopId = $_SESSION['shop_id'] ?? 0;

// Fetch the shop's details (name, address, etc.) if a shop is assigned
$shopInfo = null;
if ($shopId) {
    $res = mysqli_query($conn, "SELECT * FROM shops WHERE id = $shopId");
    $shopInfo = mysqli_fetch_assoc($res); // Returns an array or null
}

// ─── Shop Statistics ──────────────────────────────────────────────────────────
// Initialize all stats to 0 in case no shop is assigned yet
$totalOrders   = 0;
$pendingOrders = 0;
$totalProducts = 0;
$todayOrders   = 0;
$todayRevenue  = 0;

if ($shopId) {
    // Total orders ever placed at this shop (all statuses)
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM orders WHERE shop_id=$shopId");
    $totalOrders = mysqli_fetch_assoc($r)['c'];

    // Orders currently waiting for the shopkeeper to start preparing
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM orders WHERE shop_id=$shopId AND status='pending'");
    $pendingOrders = mysqli_fetch_assoc($r)['c'];

    // How many cake products the shop currently has listed
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM products WHERE shop_id=$shopId");
    $totalProducts = mysqli_fetch_assoc($r)['c'];

    // Today's orders and revenue (orders placed on today's date only)
    $today = date('Y-m-d'); // e.g. "2025-01-12"
    $r = mysqli_query($conn, "SELECT COUNT(*) c, SUM(total_amount) rev FROM orders WHERE shop_id=$shopId AND DATE(created_at)='$today'");
    $row = mysqli_fetch_assoc($r);
    $todayOrders  = $row['c'];
    $todayRevenue = $row['rev'] ?? 0; // ?? 0 handles NULL when no orders today
}

// ─── Fetch Recent 8 Orders ────────────────────────────────────────────────────
// JOIN with users table to get the customer's name alongside each order.
// LIMIT 8 keeps the dashboard lean — use orders.php for the full list.
$recentOrders = [];
if ($shopId) {
    $res = mysqli_query($conn,
        "SELECT o.*, u.name AS customer_name FROM orders o JOIN users u ON o.customer_id=u.id
         WHERE o.shop_id=$shopId ORDER BY o.created_at DESC LIMIT 8"
    );
    // Collect rows into a PHP array so we can use count() on it
    while ($row = mysqli_fetch_assoc($res)) { $recentOrders[] = $row; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopkeeper Dashboard - Ghanshyam Bakery</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body class="dashboard-body">

<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- ═══ SIDEBAR ══════════════════════════════════════════════════════════════
     Left navigation panel. The shop name is shown dynamically.               -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="../assets/logo/image.png" alt="GSK Logo">
        <!-- Show the shop's name (up to 20 chars) if assigned, else 'My Shop' -->
        <div><h2><?= $shopInfo ? htmlspecialchars(substr($shopInfo['name'],0,20)) : 'My Shop' ?></h2><span>Shopkeeper Portal</span></div>
    </div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Management</span>
        <!-- Dashboard is the current active page -->
        <a href="dashboard.php" class="active"><span class="nav-icon">🏠</span> Dashboard</a>
        <a href="products.php"><span class="nav-icon">🎂</span> My Products</a>
        <a href="orders.php"><span class="nav-icon">📦</span> Orders</a>
        <a href="analytics.php"><span class="nav-icon">📊</span> Analytics</a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php"><span>🚪</span> Logout</a></div>
</aside>

<!-- ═══ MAIN CONTENT ═════════════════════════════════════════════════════════ -->
<div class="main-content">
    <!-- ── Top Bar ──────────────────────────────────────────────────────────
         Shows the shop's full name as the subtitle.                           -->
    <div class="topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <button class="hamburger" id="hamburger" onclick="toggleSidebar()" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
            <div class="topbar-title">
                <h1>Shop Dashboard</h1>
                <!-- Display shop name, or a warning if no shop is assigned -->
                <p><?= $shopInfo ? htmlspecialchars($shopInfo['name']) : 'No shop assigned yet' ?></p>
            </div>
        </div>
        <div class="topbar-user">
            <div class="user-info"><strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong><span>Shopkeeper</span></div>
            <!-- First letter of the name as an avatar -->
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_name'],0,1)) ?></div>
        </div>
    </div>

    <div class="page-body">

        <?php if (!$shopId): ?>
        <!-- ── No Shop Assigned Message ───────────────────────────────────────
             Shown if the admin hasn't linked this shopkeeper to a shop yet.   -->
        <div class="alert alert-info">ℹ️ No shop has been assigned to your account yet. Please contact the Admin.</div>

        <?php else: ?>

        <!-- ── Stat Cards ────────────────────────────────────────────────────
             Four summary numbers at the top of the page.
             Each card has a colored icon circle, a big number, and a label.   -->
        <div class="stats-grid">
            <!-- Pending Orders — orders waiting to be processed -->
            <div class="stat-card">
                <div class="stat-icon orange">⏳</div>
                <div class="stat-info"><h3><?= $pendingOrders ?></h3><p>Pending Orders</p></div>
            </div>
            <!-- Today's Orders — orders placed on today's date -->
            <div class="stat-card">
                <div class="stat-icon blue">📦</div>
                <div class="stat-info"><h3><?= $todayOrders ?></h3><p>Today's Orders</p></div>
            </div>
            <!-- Today's Revenue — total from all orders placed today (any status) -->
            <div class="stat-card">
                <div class="stat-icon green">💰</div>
                <!-- number_format(x, 0) formats with commas but no decimal places -->
                <div class="stat-info"><h3>₹<?= number_format($todayRevenue,0) ?></h3><p>Today's Revenue</p></div>
            </div>
            <!-- Total Products — how many cakes the shop has listed -->
            <div class="stat-card">
                <div class="stat-icon pink">🎂</div>
                <div class="stat-info"><h3><?= $totalProducts ?></h3><p>Products Listed</p></div>
            </div>
        </div>

        <!-- ── Quick Actions ──────────────────────────────────────────────────
             Shortcut buttons for the most common shopkeeper tasks.            -->
        <div class="table-card" style="margin-bottom:24px;">
            <h2 style="margin-bottom:16px;">🚀 Quick Actions</h2>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <!-- Link to orders filtered to show only pending ones -->
                <a href="orders.php?filter=pending" class="btn btn-primary">⏳ View Pending Orders</a>
                <a href="products.php?action=add"   class="btn btn-outline">+ Add New Cake</a>
                <a href="orders.php"                class="btn btn-outline">📦 All Orders</a>
            </div>
        </div>

        <!-- ── Recent Orders Table ────────────────────────────────────────────
             Shows the 8 most recent orders.
             If no orders exist yet, shows an empty state message.             -->
        <div class="table-card">
            <div class="table-card-header">
                <h2>📋 Recent Orders</h2>
                <!-- Link to the full orders page -->
                <a href="orders.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            <?php if (count($recentOrders) > 0): ?>
            <table class="data-table">
                <thead>
                    <!-- Column headers for the orders table -->
                    <tr><th>Order #</th><th>Customer</th><th>Type</th><th>Amount</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $o): ?>
                    <tr>
                        <!-- Pad the ID with zeros to always show 4 digits: 7 → #0007 -->
                        <td><strong>#<?= str_pad($o['id'],4,'0',STR_PAD_LEFT) ?></strong></td>
                        <!-- htmlspecialchars prevents XSS from user-supplied names -->
                        <td><?= htmlspecialchars($o['customer_name']) ?></td>
                        <!-- Show delivery/pickup badge with appropriate icon -->
                        <td><span class="badge badge-<?= $o['order_type'] ?>"><?= $o['order_type'] === 'delivery' ? '🚚 Delivery' : '🏪 Pickup' ?></span></td>
                        <!-- Format money with 2 decimal places -->
                        <td><strong>₹<?= number_format($o['total_amount'],2) ?></strong></td>
                        <!-- Badge color depends on status (CSS class: badge-pending, etc.) -->
                        <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                        <!-- Link to the specific order on the orders page -->
                        <td><a href="orders.php?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm">Update</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <!-- Empty state: shown when there are no orders yet -->
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>No orders yet</h3>
                <p>When customers place orders, they'll appear here.</p>
            </div>
            <?php endif; ?>
        </div>

        <?php endif; // end if $shopId — closes the "no shop assigned" check ?>
    </div>
</div>
<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
}
</script>
</body>
</html>
