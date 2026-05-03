<?php
/**
 * admin/orders.php - ALL ORDERS ACROSS ALL BRANCHES
 * Admin can view and filter all orders on the platform.
 */
$required_role = 'admin';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$filter = $_GET['filter'] ?? 'all';
$whereStatus = ($filter !== 'all') ? "WHERE o.status='$filter'" : '';

$orders = mysqli_query($conn,
    "SELECT o.*, u.name AS customer_name, s.name AS shop_name
     FROM orders o JOIN users u ON o.customer_id=u.id JOIN shops s ON o.shop_id=s.id
     $whereStatus ORDER BY o.created_at DESC"
);
$allOrders = [];
while($row = mysqli_fetch_assoc($orders)) { $allOrders[] = $row; }

$totalRevenue = mysqli_fetch_assoc(mysqli_query($conn,"SELECT SUM(total_amount) t FROM orders WHERE status='completed'"))['t']??0;
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>All Orders - GSK Bakery</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<style>.filter-tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;}.filter-tab{padding:7px 16px;border-radius:20px;font-size:.82rem;font-weight:600;text-decoration:none;border:1px solid var(--border);color:var(--text-muted);background:white;transition:all .2s;}.filter-tab.active,.filter-tab:hover{background:var(--accent);color:white;border-color:var(--accent);}</style>
</head><body class="dashboard-body">

<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand"><img src="../assets/logo/image.png" alt="logo"><div><h2>Admin Panel</h2><span>Ghanshyam Bakery</span></div></div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Administration</span>
        <a href="dashboard.php"><span class="nav-icon">🏠</span> Dashboard</a>
        <a href="shops.php"><span class="nav-icon">🏪</span> Manage Shops</a>
        <a href="users.php"><span class="nav-icon">👥</span> Manage Users</a>
        <a href="orders.php" class="active"><span class="nav-icon">📦</span> All Orders</a>
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
                <h1>📦 All Orders</h1>
                <p><?=count($allOrders)?> order(s) | Revenue: ₹<?=number_format($totalRevenue,2)?></p>
            </div>
        </div>
        <div class="topbar-user">
            <div class="user-info"><strong><?=htmlspecialchars($_SESSION['user_name'])?></strong><span>Admin</span></div>
            <div class="avatar"><?=strtoupper(substr($_SESSION['user_name'],0,1))?></div>
        </div>
    </div>
    <div class="page-body">
        <div class="filter-tabs">
            <?php $filters=['all'=>'All','pending'=>'⏳ Pending','preparing'=>'🍳 Preparing','ready'=>'✅ Ready','completed'=>'🎉 Completed','cancelled'=>'❌ Cancelled'];?>
            <?php foreach($filters as $val=>$label):?>
            <a href="orders.php?filter=<?=$val?>" class="filter-tab <?=$filter===$val?'active':''?>"><?=$label?></a>
            <?php endforeach;?>
        </div>
        <div class="table-card">
            <div class="table-card-header"><h2>Orders (<?=count($allOrders)?>)</h2></div>
            <div class="table-responsive">
            <?php if(count($allOrders)>0):?>
            <table class="data-table" style="min-width:700px;">
                <thead><tr><th>Order #</th><th>Customer</th><th>Shop</th><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach($allOrders as $o):?>
                <tr>
                    <td><strong>#<?=str_pad($o['id'],4,'0',STR_PAD_LEFT)?></strong></td>
                    <td><?=htmlspecialchars($o['customer_name'])?></td>
                    <td><?=htmlspecialchars($o['shop_name'])?></td>
                    <td><span class="badge badge-<?=$o['order_type']?>"><?=$o['order_type']==='delivery'?'🚚':'🏪'?> <?=ucfirst($o['order_type'])?></span></td>
                    <td><strong>₹<?=number_format($o['total_amount'],2)?></strong></td>
                    <td><span class="badge badge-<?=$o['status']?>"><?=ucfirst($o['status'])?></span></td>
                    <td class="actions"><?=date('d M Y, h:i A',strtotime($o['created_at']))?></td>
                </tr>
                <?php endforeach;?>
                </tbody>
            </table>
            <?php else:?>
            <div class="empty-state"><div class="empty-icon">📭</div><h3>No orders <?=$filter!=='all'?"with status '$filter'":'yet'?></h3></div>
            <?php endif;?>
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
