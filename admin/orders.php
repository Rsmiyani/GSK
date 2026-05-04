<?php
/**
 * admin/orders.php
 * ================
 * ALL ORDERS — ADMIN VIEW
 *
 * The admin can see every order placed across ALL shops.
 * A filter tab lets them narrow down by order status.
 *
 * HOW IT WORKS:
 *   1. Read the ?filter=... URL parameter (default: 'all')
 *   2. Build a WHERE clause based on that filter
 *   3. Query all matching orders with customer & shop names
 *   4. Display them in a table with clickable filter tabs
 */

// ─── Access Control ────────────────────────────────────────────────────────────
$required_role = 'admin';
require_once '../includes/auth_check.php'; // Only admins allowed
require_once '../config/db.php';           // Opens $conn

// ─── Read the Status Filter from the URL ──────────────────────────────────────
// $_GET['filter'] is set when a filter tab is clicked, e.g. orders.php?filter=pending
// If no filter in the URL, default to 'all' (show everything)
$filter = $_GET['filter'] ?? 'all';

// Build the SQL WHERE clause only when a specific status is selected
// e.g. "WHERE o.status='pending'" or "" (empty string for 'all')
$whereStatus = ($filter !== 'all') ? "WHERE o.status='$filter'" : '';

// ─── Fetch Orders ─────────────────────────────────────────────────────────────
// JOIN orders with users (to get customer name) and shops (to get shop name)
// The $whereStatus is inserted as a blank string when filter is 'all'
$orders = mysqli_query($conn,
    "SELECT o.*, u.name AS customer_name, s.name AS shop_name
     FROM orders o JOIN users u ON o.customer_id=u.id JOIN shops s ON o.shop_id=s.id
     $whereStatus ORDER BY o.created_at DESC"
);

// Collect all rows into a PHP array (so we can use count() on it later)
$allOrders = [];
while($row = mysqli_fetch_assoc($orders)) { $allOrders[] = $row; }

// ─── Total Revenue from Completed Orders ──────────────────────────────────────
// Always shows total revenue regardless of the current filter (platform-wide stat)
$totalRevenue = mysqli_fetch_assoc(mysqli_query($conn,"SELECT SUM(total_amount) t FROM orders WHERE status='completed'"))['t']??0;
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>All Orders - GSK Bakery</title>
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<style>
/* ── Filter Tab Styles ──────────────────────────────────────────────────────
   Pill-shaped clickable buttons to filter orders by status.
   The active tab gets the pink accent color.                                 */
.filter-tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;}
.filter-tab{padding:7px 16px;border-radius:20px;font-size:.82rem;font-weight:600;text-decoration:none;border:1px solid var(--border);color:var(--text-muted);background:white;transition:all .2s;}
.filter-tab.active,.filter-tab:hover{background:var(--accent);color:white;border-color:var(--accent);}
</style>
</head><body class="dashboard-body">

<!-- Dark overlay for mobile sidebar (tapping it closes the sidebar) -->
<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- ═══ SIDEBAR ══════════════════════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand"><img src="../assets/logo/image.png" alt="logo"><div><h2>Admin Panel</h2><span>Ghanshyam Bakery</span></div></div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Administration</span>
        <a href="dashboard.php"><span class="nav-icon">🏠</span> Dashboard</a>
        <a href="shops.php"><span class="nav-icon">🏪</span> Manage Shops</a>
        <a href="users.php"><span class="nav-icon">👥</span> Manage Users</a>
        <!-- This page is active, so it gets the 'active' CSS class -->
        <a href="orders.php" class="active"><span class="nav-icon">📦</span> All Orders</a>
        <a href="analytics.php"><span class="nav-icon">📊</span> Analytics</a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php"><span>🚪</span> Logout</a></div>
</aside>

<!-- ═══ MAIN CONTENT ═════════════════════════════════════════════════════════ -->
<div class="main-content">
    <!-- Top bar with hamburger, page title, and logged-in admin info -->
    <div class="topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <button class="hamburger" id="hamburger" onclick="toggleSidebar()" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
            <div class="topbar-title">
                <h1>📦 All Orders</h1>
                <!-- Shows the count of orders found and the total revenue figure -->
                <p><?=count($allOrders)?> order(s) | Revenue: ₹<?=number_format($totalRevenue,2)?></p>
            </div>
        </div>
        <div class="topbar-user">
            <div class="user-info"><strong><?=htmlspecialchars($_SESSION['user_name'])?></strong><span>Admin</span></div>
            <div class="avatar"><?=strtoupper(substr($_SESSION['user_name'],0,1))?></div>
        </div>
    </div>

    <div class="page-body">

        <!-- ── Filter Tabs ────────────────────────────────────────────────────
             Each tab is a link to the same page with a different ?filter= value.
             The PHP checks if the current $filter matches the tab's value to
             add the 'active' CSS class (highlights the selected tab).          -->
        <div class="filter-tabs">
            <?php
            // Define all available filter options: URL value => Display label
            $filters=['all'=>'All','pending'=>'⏳ Pending','preparing'=>'🍳 Preparing','ready'=>'✅ Ready','completed'=>'🎉 Completed','cancelled'=>'❌ Cancelled'];
            ?>
            <?php foreach($filters as $val=>$label):?>
            <!-- Add 'active' class if this tab matches the current filter -->
            <a href="orders.php?filter=<?=$val?>" class="filter-tab <?=$filter===$val?'active':''?>"><?=$label?></a>
            <?php endforeach;?>
        </div>

        <!-- ── Orders Table ───────────────────────────────────────────────────
             Loops through $allOrders (collected from the DB query above).
             If empty, shows an "empty state" message instead.                  -->
        <div class="table-card">
            <div class="table-card-header"><h2>Orders (<?=count($allOrders)?>)</h2></div>
            <div class="table-responsive">
            <?php if(count($allOrders)>0):?>
            <!-- min-width ensures the table scrolls horizontally on small screens -->
            <table class="data-table" style="min-width:700px;">
                <thead><tr><th>Order #</th><th>Customer</th><th>Shop</th><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach($allOrders as $o):?>
                <tr>
                    <!-- Pad ID to 4 digits: ID 7 → #0007 -->
                    <td><strong>#<?=str_pad($o['id'],4,'0',STR_PAD_LEFT)?></strong></td>
                    <td><?=htmlspecialchars($o['customer_name'])?></td>
                    <td><?=htmlspecialchars($o['shop_name'])?></td>
                    <!-- Show delivery truck emoji for delivery, shop for pickup -->
                    <td><span class="badge badge-<?=$o['order_type']?>"><?=$o['order_type']==='delivery'?'🚚':'🏪'?> <?=ucfirst($o['order_type'])?></span></td>
                    <td><strong>₹<?=number_format($o['total_amount'],2)?></strong></td>
                    <!-- Badge color matches status: badge-pending, badge-completed, etc. -->
                    <td><span class="badge badge-<?=$o['status']?>"><?=ucfirst($o['status'])?></span></td>
                    <!-- Format MySQL datetime as "12 Jan 2025, 02:30 PM" -->
                    <td class="actions"><?=date('d M Y, h:i A',strtotime($o['created_at']))?></td>
                </tr>
                <?php endforeach;?>
                </tbody>
            </table>
            <?php else:?>
            <!-- Empty state: shown when no orders match the current filter -->
            <div class="empty-state"><div class="empty-icon">📭</div><h3>No orders <?=$filter!=='all'?"with status '$filter'":'yet'?></h3></div>
            <?php endif;?>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * toggleSidebar()
 * ===============
 * Toggles the mobile sidebar open/closed.
 * The CSS classes 'open' and 'show' are defined in dashboard.css.
 */
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
}
</script>
</body></html>
