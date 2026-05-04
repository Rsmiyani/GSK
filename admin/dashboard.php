<?php
/**
 * admin/dashboard.php
 * ===================
 * ADMIN OVERVIEW DASHBOARD
 *
 * This is the first page the admin sees after login.
 * It shows platform-wide numbers (users, shops, orders, revenue)
 * and the 10 most recent orders across ALL shops.
 *
 * HOW IT WORKS:
 *   1. PHP queries the database for platform stats
 *   2. HTML renders stat cards and a recent orders table
 *   3. A JavaScript function handles the mobile hamburger menu
 */

// ─── Access Control ────────────────────────────────────────────────────────────
// Tell auth_check.php which role is required to see this page
$required_role = 'admin';
require_once '../includes/auth_check.php'; // Redirects non-admins away
require_once '../config/db.php';           // Opens MySQL connection ($conn)

// ─── Platform-Wide Statistics ─────────────────────────────────────────────────
// Each line runs one COUNT(*) query and reads the single result immediately.
// The ['c'] key is just the column alias we named it in the SQL ("SELECT COUNT(*) c").

$totalUsers    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM users"))['c'];
// Total number of registered user accounts (all roles combined)

$totalShops    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM shops"))['c'];
// Total number of shop branches in the system

$totalOrders   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM orders"))['c'];
// Total orders ever placed, regardless of status

$totalRevenue  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT SUM(total_amount) rev FROM orders WHERE status='completed'"))['rev'] ?? 0;
// Total money earned from COMPLETED orders only (cancelled/pending orders don't count).
// The ?? 0 default handles the case when there are no completed orders (SUM returns NULL).

$pendingOrders = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM orders WHERE status='pending'"))['c'];
// Orders waiting for the shopkeeper to start preparing

$totalCustomers= mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM users WHERE role='customer'"))['c'];
// Only count users with the 'customer' role (not admins or shopkeepers)

// ─── Recent Orders Query ──────────────────────────────────────────────────────
// JOIN across 3 tables:
//   orders         → the main order record
//   users (u)      → get the customer's name
//   shops (s)      → get the shop's name
// ORDER BY created_at DESC → newest orders first
// LIMIT 10         → only show the 10 most recent
$recentOrders = mysqli_query($conn,
    "SELECT o.*, u.name AS customer_name, s.name AS shop_name
     FROM orders o JOIN users u ON o.customer_id=u.id JOIN shops s ON o.shop_id=s.id
     ORDER BY o.created_at DESC LIMIT 10"
);
?>
<!DOCTYPE html><html lang="en"><head>
<!-- Page meta -->
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin Dashboard - GSK Bakery</title>
<!-- Google Fonts: Poppins for a clean, modern look -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
<!-- Shared styles (colors, buttons, badges, alerts) -->
<link rel="stylesheet" href="../assets/css/style.css">
<!-- Dashboard-specific styles (sidebar, topbar, stat cards, tables) -->
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head><body class="dashboard-body">

<!-- ═══ MOBILE SIDEBAR OVERLAY ═══════════════════════════════════════════════
     A semi-transparent dark layer that covers the page when the sidebar is open
     on mobile. Clicking it calls toggleSidebar() to close the sidebar.        -->
<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- ═══ SIDEBAR ══════════════════════════════════════════════════════════════
     The left navigation panel. On desktop it's always visible.
     On mobile it slides in from the left when the hamburger is clicked.        -->
<aside class="sidebar" id="sidebar">
    <!-- Brand / Logo area at the top of the sidebar -->
    <div class="sidebar-brand">
        <img src="../assets/logo/image.png" alt="logo">
        <div><h2>Admin Panel</h2><span>Ghanshyam Bakery</span></div>
    </div>

    <!-- Navigation links — each <a> goes to a different admin page -->
    <nav class="sidebar-nav">
        <span class="nav-section-label">Administration</span>
        <a href="dashboard.php" class="active"><span class="nav-icon">🏠</span> Dashboard</a>
        <a href="shops.php"><span class="nav-icon">🏪</span> Manage Shops</a>
        <a href="users.php"><span class="nav-icon">👥</span> Manage Users</a>
        <a href="orders.php"><span class="nav-icon">📦</span> All Orders</a>
        <a href="analytics.php"><span class="nav-icon">📊</span> Analytics</a>
    </nav>

    <!-- Logout link pinned to the bottom of the sidebar -->
    <div class="sidebar-footer"><a href="../logout.php"><span>🚪</span> Logout</a></div>
</aside>

<!-- ═══ MAIN CONTENT AREA ════════════════════════════════════════════════════ -->
<div class="main-content">

    <!-- ── Top Bar ──────────────────────────────────────────────────────────
         Shows the hamburger button (mobile), the page title, and the
         currently logged-in admin's name + initials avatar.                    -->
    <div class="topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <!-- Hamburger button: three horizontal lines, only shown on mobile -->
            <button class="hamburger" id="hamburger" onclick="toggleSidebar()" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
            <div class="topbar-title">
                <h1>Admin Dashboard</h1>
                <p>Full system overview</p>
            </div>
        </div>
        <!-- Admin's name and first-letter avatar in the top-right corner -->
        <div class="topbar-user">
            <div class="user-info"><strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong><span>Admin</span></div>
            <!-- strtoupper(substr(..., 0, 1)) gets the first letter of the name, uppercased -->
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_name'],0,1)) ?></div>
        </div>
    </div>

    <!-- ── Page Body ─────────────────────────────────────────────────────── -->
    <div class="page-body">

        <!-- ── Stat Cards Grid ────────────────────────────────────────────
             Four cards showing the key platform numbers at a glance.
             Each card has an icon (colored circle), a number, and a label.     -->
        <div class="stats-grid">
            <!-- Total customers card -->
            <div class="stat-card"><div class="stat-icon blue">👥</div><div class="stat-info"><h3><?=$totalCustomers?></h3><p>Customers</p></div></div>
            <!-- Total shop branches card -->
            <div class="stat-card"><div class="stat-icon pink">🏪</div><div class="stat-info"><h3><?=$totalShops?></h3><p>Shop Branches</p></div></div>
            <!-- Orders waiting to be processed card -->
            <div class="stat-card"><div class="stat-icon orange">⏳</div><div class="stat-info"><h3><?=$pendingOrders?></h3><p>Pending Orders</p></div></div>
            <!-- Total revenue from completed orders — number_format adds commas (e.g. 1,25,000) -->
            <div class="stat-card"><div class="stat-icon green">💰</div><div class="stat-info"><h3>₹<?=number_format($totalRevenue,0)?></h3><p>Total Revenue</p></div></div>
        </div>

        <!-- ── Quick Actions ──────────────────────────────────────────────
             Shortcut buttons so the admin can jump to common tasks quickly.    -->
        <div class="table-card" style="margin-bottom:24px;">
            <h2 style="margin-bottom:16px;">🚀 Quick Actions</h2>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <a href="shops.php?action=add" class="btn btn-primary">+ Add New Branch</a>
                <a href="users.php" class="btn btn-outline">👥 Manage Users</a>
                <a href="orders.php" class="btn btn-outline">📦 View All Orders</a>
            </div>
        </div>

        <!-- ── Recent Orders Table ────────────────────────────────────────
             Shows the last 10 orders placed across all shops.
             The PHP while loop reads one row at a time from the $recentOrders
             result set and builds one <tr> per order.                          -->
        <div class="table-card">
            <div class="table-card-header">
                <h2>📋 Recent Orders (All Branches)</h2>
                <a href="orders.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            <div class="table-responsive">
            <?php if(mysqli_num_rows($recentOrders)>0): ?>
            <table class="data-table" style="min-width:600px;">
                <!-- Table header row -->
                <thead><tr><th>Order #</th><th>Customer</th><th>Shop</th><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php while($o=mysqli_fetch_assoc($recentOrders)): ?>
                <tr>
                    <!-- str_pad pads the ID with leading zeros: 7 becomes #0007 -->
                    <td><strong>#<?=str_pad($o['id'],4,'0',STR_PAD_LEFT)?></strong></td>
                    <!-- htmlspecialchars prevents XSS: converts < > & characters to safe HTML entities -->
                    <td><?=htmlspecialchars($o['customer_name'])?></td>
                    <td><?=htmlspecialchars($o['shop_name'])?></td>
                    <!-- Show a delivery truck icon for delivery orders, shop for pickup -->
                    <td><span class="badge badge-<?=$o['order_type']?>"><?=$o['order_type']==='delivery'?'🚚':'🏪'?> <?=ucfirst($o['order_type'])?></span></td>
                    <!-- number_format(x, 2) formats the price with 2 decimal places -->
                    <td><strong>₹<?=number_format($o['total_amount'],2)?></strong></td>
                    <!-- The badge color (badge-pending, badge-completed, etc.) comes from style.css -->
                    <td><span class="badge badge-<?=$o['status']?>"><?=ucfirst($o['status'])?></span></td>
                    <!-- date() formats the MySQL datetime string into a human-readable date -->
                    <td class="actions"><?=date('d M Y',strtotime($o['created_at']))?></td>
                </tr>
                <?php endwhile;?>
                </tbody>
            </table>
            <?php else: ?><div class="empty-state"><div class="empty-icon">📭</div><h3>No orders yet</h3></div><?php endif;?>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * toggleSidebar()
 * ===============
 * Opens or closes the sidebar on mobile screens.
 *
 * How it works:
 *   - 'open' class on #sidebar slides it into view (see dashboard.css)
 *   - 'show' class on #overlay makes the dark background appear
 * Calling this function again removes both classes, closing everything.
 */
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
}
</script>
</body></html>
