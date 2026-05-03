<?php
/**
 * admin/dashboard.php - ADMIN OVERVIEW DASHBOARD
 * Shows platform-wide stats: total users, shops, orders, and revenue.
 */
$required_role = 'admin';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

// Platform-wide statistics
$totalUsers    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM users"))['c'];
$totalShops    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM shops"))['c'];
$totalOrders   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM orders"))['c'];
$totalRevenue  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT SUM(total_amount) rev FROM orders WHERE status='completed'"))['rev'] ?? 0;
$pendingOrders = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM orders WHERE status='pending'"))['c'];
$totalCustomers= mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM users WHERE role='customer'"))['c'];

// Recent 10 orders across all shops
$recentOrders = mysqli_query($conn,
    "SELECT o.*, u.name AS customer_name, s.name AS shop_name
     FROM orders o JOIN users u ON o.customer_id=u.id JOIN shops s ON o.shop_id=s.id
     ORDER BY o.created_at DESC LIMIT 10"
);
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin Dashboard - GSK Bakery</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head><body class="dashboard-body">

<!-- Mobile Sidebar Overlay -->
<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="../assets/logo/image.png" alt="logo">
        <div><h2>Admin Panel</h2><span>Ghanshyam Bakery</span></div>
    </div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Administration</span>
        <a href="dashboard.php" class="active"><span class="nav-icon">🏠</span> Dashboard</a>
        <a href="shops.php"><span class="nav-icon">🏪</span> Manage Shops</a>
        <a href="users.php"><span class="nav-icon">👥</span> Manage Users</a>
        <a href="orders.php"><span class="nav-icon">📦</span> All Orders</a>
        <a href="analytics.php"><span class="nav-icon">📊</span> Analytics</a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php"><span>🚪</span> Logout</a></div>
</aside>

<div class="main-content">
    <div class="topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <button class="hamburger" id="hamburger" onclick="toggleSidebar()" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
            <div class="topbar-title">
                <h1>Admin Dashboard</h1>
                <p>Full system overview</p>
            </div>
        </div>
        <div class="topbar-user">
            <div class="user-info"><strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong><span>Admin</span></div>
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_name'],0,1)) ?></div>
        </div>
    </div>
    <div class="page-body">

        <!-- Platform Stats -->
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon blue">👥</div><div class="stat-info"><h3><?=$totalCustomers?></h3><p>Customers</p></div></div>
            <div class="stat-card"><div class="stat-icon pink">🏪</div><div class="stat-info"><h3><?=$totalShops?></h3><p>Shop Branches</p></div></div>
            <div class="stat-card"><div class="stat-icon orange">⏳</div><div class="stat-info"><h3><?=$pendingOrders?></h3><p>Pending Orders</p></div></div>
            <div class="stat-card"><div class="stat-icon green">💰</div><div class="stat-info"><h3>₹<?=number_format($totalRevenue,0)?></h3><p>Total Revenue</p></div></div>
        </div>

        <!-- Quick Links -->
        <div class="table-card" style="margin-bottom:24px;">
            <h2 style="margin-bottom:16px;">🚀 Quick Actions</h2>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <a href="shops.php?action=add" class="btn btn-primary">+ Add New Branch</a>
                <a href="users.php" class="btn btn-outline">👥 Manage Users</a>
                <a href="orders.php" class="btn btn-outline">📦 View All Orders</a>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="table-card">
            <div class="table-card-header">
                <h2>📋 Recent Orders (All Branches)</h2>
                <a href="orders.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            <div class="table-responsive">
            <?php if(mysqli_num_rows($recentOrders)>0): ?>
            <table class="data-table" style="min-width:600px;">
                <thead><tr><th>Order #</th><th>Customer</th><th>Shop</th><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php while($o=mysqli_fetch_assoc($recentOrders)): ?>
                <tr>
                    <td><strong>#<?=str_pad($o['id'],4,'0',STR_PAD_LEFT)?></strong></td>
                    <td><?=htmlspecialchars($o['customer_name'])?></td>
                    <td><?=htmlspecialchars($o['shop_name'])?></td>
                    <td><span class="badge badge-<?=$o['order_type']?>"><?=$o['order_type']==='delivery'?'🚚':'🏪'?> <?=ucfirst($o['order_type'])?></span></td>
                    <td><strong>₹<?=number_format($o['total_amount'],2)?></strong></td>
                    <td><span class="badge badge-<?=$o['status']?>"><?=ucfirst($o['status'])?></span></td>
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
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
}
</script>
</body></html>
