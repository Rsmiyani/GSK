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
 */

$required_role = 'shopkeeper';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

// Get the shopkeeper's shop ID from session (set during login)
$shopId = $_SESSION['shop_id'] ?? 0;

// If no shop assigned yet, show a message
$shopInfo = null;
if ($shopId) {
    $res = mysqli_query($conn, "SELECT * FROM shops WHERE id = $shopId");
    $shopInfo = mysqli_fetch_assoc($res);
}

// ─── Stats for This Shop ──────────────────────────────────────────────────────
$totalOrders   = 0;
$pendingOrders = 0;
$totalProducts = 0;
$todayOrders   = 0;
$todayRevenue  = 0;

if ($shopId) {
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM orders WHERE shop_id=$shopId");
    $totalOrders = mysqli_fetch_assoc($r)['c'];

    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM orders WHERE shop_id=$shopId AND status='pending'");
    $pendingOrders = mysqli_fetch_assoc($r)['c'];

    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM products WHERE shop_id=$shopId");
    $totalProducts = mysqli_fetch_assoc($r)['c'];

    $today = date('Y-m-d');
    $r = mysqli_query($conn, "SELECT COUNT(*) c, SUM(total_amount) rev FROM orders WHERE shop_id=$shopId AND DATE(created_at)='$today'");
    $row = mysqli_fetch_assoc($r);
    $todayOrders  = $row['c'];
    $todayRevenue = $row['rev'] ?? 0;
}

// ─── Fetch Recent 8 Orders ────────────────────────────────────────────────────
$recentOrders = [];
if ($shopId) {
    $res = mysqli_query($conn,
        "SELECT o.*, u.name AS customer_name FROM orders o JOIN users u ON o.customer_id=u.id
         WHERE o.shop_id=$shopId ORDER BY o.created_at DESC LIMIT 8"
    );
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

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="../assets/logo/image.png" alt="GSK Logo">
        <div><h2><?= $shopInfo ? htmlspecialchars(substr($shopInfo['name'],0,20)) : 'My Shop' ?></h2><span>Shopkeeper Portal</span></div>
    </div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Management</span>
        <a href="dashboard.php" class="active"><span class="nav-icon">🏠</span> Dashboard</a>
        <a href="products.php"><span class="nav-icon">🎂</span> My Products</a>
        <a href="orders.php"><span class="nav-icon">📦</span> Orders</a>
        <a href="analytics.php"><span class="nav-icon">📊</span> Analytics</a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php"><span>🚪</span> Logout</a></div>
</aside>

<div class="main-content">
    <div class="topbar">
        <div class="topbar-title">
            <h1>Shop Dashboard</h1>
            <p><?= $shopInfo ? htmlspecialchars($shopInfo['name']) : 'No shop assigned yet' ?></p>
        </div>
        <div class="topbar-user">
            <div class="user-info"><strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong><span>Shopkeeper</span></div>
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_name'],0,1)) ?></div>
        </div>
    </div>

    <div class="page-body">

        <?php if (!$shopId): ?>
        <div class="alert alert-info">ℹ️ No shop has been assigned to your account yet. Please contact the Admin.</div>
        <?php else: ?>

        <!-- ─── Stat Cards ──────────────────────────────────────────────────── -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon orange">⏳</div>
                <div class="stat-info"><h3><?= $pendingOrders ?></h3><p>Pending Orders</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">📦</div>
                <div class="stat-info"><h3><?= $todayOrders ?></h3><p>Today's Orders</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">💰</div>
                <div class="stat-info"><h3>₹<?= number_format($todayRevenue,0) ?></h3><p>Today's Revenue</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pink">🎂</div>
                <div class="stat-info"><h3><?= $totalProducts ?></h3><p>Products Listed</p></div>
            </div>
        </div>

        <!-- ─── Quick Actions ──────────────────────────────────────────────── -->
        <div class="table-card" style="margin-bottom:24px;">
            <h2 style="margin-bottom:16px;">🚀 Quick Actions</h2>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <a href="orders.php?filter=pending" class="btn btn-primary">⏳ View Pending Orders</a>
                <a href="products.php?action=add"   class="btn btn-outline">+ Add New Cake</a>
                <a href="orders.php"                class="btn btn-outline">📦 All Orders</a>
            </div>
        </div>

        <!-- ─── Recent Orders Table ────────────────────────────────────────── -->
        <div class="table-card">
            <div class="table-card-header">
                <h2>📋 Recent Orders</h2>
                <a href="orders.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            <?php if (count($recentOrders) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr><th>Order #</th><th>Customer</th><th>Type</th><th>Amount</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $o): ?>
                    <tr>
                        <td><strong>#<?= str_pad($o['id'],4,'0',STR_PAD_LEFT) ?></strong></td>
                        <td><?= htmlspecialchars($o['customer_name']) ?></td>
                        <td><span class="badge badge-<?= $o['order_type'] ?>"><?= $o['order_type'] === 'delivery' ? '🚚 Delivery' : '🏪 Pickup' ?></span></td>
                        <td><strong>₹<?= number_format($o['total_amount'],2) ?></strong></td>
                        <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                        <td><a href="orders.php?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm">Update</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>No orders yet</h3>
                <p>When customers place orders, they'll appear here.</p>
            </div>
            <?php endif; ?>
        </div>

        <?php endif; // end if $shopId ?>
    </div>
</div>
</body>
</html>
