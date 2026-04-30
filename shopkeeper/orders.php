<?php
/**
 * shopkeeper/orders.php
 * =====================
 * ORDER MANAGEMENT PAGE
 *
 * Shopkeeper sees all orders for their shop.
 * They can update order status: pending → preparing → ready → completed
 * They can also cancel an order.
 */

$required_role = 'shopkeeper';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$shopId = $_SESSION['shop_id'] ?? 0;
if (!$shopId) { header("Location: dashboard.php"); exit(); }

$message = '';

// ─── Handle Status Update (POST) ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId   = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';
    // Valid statuses the shopkeeper can set
    $allowed   = ['pending','preparing','ready','completed','cancelled'];

    if ($orderId && in_array($newStatus, $allowed)) {
        // Only update if the order belongs to this shop (security check!)
        $s = mysqli_prepare($conn, "UPDATE orders SET status=? WHERE id=? AND shop_id=?");
        mysqli_stmt_bind_param($s, 'sii', $newStatus, $orderId, $shopId);
        mysqli_stmt_execute($s)
            ? $message = 'success:Order #' . str_pad($orderId,4,'0',STR_PAD_LEFT) . ' status updated to ' . ucfirst($newStatus)
            : $message = 'error:Failed to update status.';
    }
}

// ─── Filter by Status (from URL) ─────────────────────────────────────────────
$filter = $_GET['filter'] ?? 'all';
$whereStatus = ($filter !== 'all') ? "AND o.status = '$filter'" : '';

// ─── Fetch Orders for This Shop ───────────────────────────────────────────────
$orders = mysqli_query($conn,
    "SELECT o.*, u.name AS customer_name, u.phone AS customer_phone
     FROM orders o JOIN users u ON o.customer_id = u.id
     WHERE o.shop_id = $shopId $whereStatus
     ORDER BY
       FIELD(o.status,'pending','preparing','ready','completed','cancelled'),
       o.created_at DESC"
);

// Fetch items for each order
$orderItems = [];
$tempOrders = [];
while ($row = mysqli_fetch_assoc($orders)) { $tempOrders[] = $row; }

foreach ($tempOrders as $o) {
    $iRes = mysqli_query($conn,
        "SELECT oi.quantity, p.name FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id={$o['id']}"
    );
    $orderItems[$o['id']] = [];
    while ($item = mysqli_fetch_assoc($iRes)) { $orderItems[$o['id']][] = $item; }
}

[$msgType,$msgText] = $message ? explode(':',$message,2) : ['',''];

// Next status options (what the shopkeeper can advance the order to)
$nextStatus = [
    'pending'   => ['preparing' => '🍳 Start Preparing', 'cancelled' => '❌ Cancel'],
    'preparing' => ['ready'     => '✅ Mark Ready',     'cancelled' => '❌ Cancel'],
    'ready'     => ['completed' => '🎉 Complete Order'],
    'completed' => [],
    'cancelled' => [],
];
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Orders - GSK Bakery</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<style>
/* Filter tab buttons */
.filter-tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;}
.filter-tab{padding:7px 16px;border-radius:20px;font-size:.82rem;font-weight:600;text-decoration:none;border:1px solid var(--border);color:var(--text-muted);background:white;transition:all .2s;}
.filter-tab.active,.filter-tab:hover{background:var(--accent);color:white;border-color:var(--accent);}
/* Order detail expandable */
.order-items-list{font-size:.83rem;color:var(--text-muted);margin-top:4px;}
</style>
</head><body class="dashboard-body">

<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="../assets/logo/image.png" alt="logo">
        <div><h2>My Shop</h2><span>Shopkeeper Portal</span></div>
    </div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Management</span>
        <a href="dashboard.php"><span class="nav-icon">🏠</span> Dashboard</a>
        <a href="products.php"><span class="nav-icon">🎂</span> My Products</a>
        <a href="orders.php" class="active"><span class="nav-icon">📦</span> Orders</a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php"><span>🚪</span> Logout</a></div>
</aside>

<div class="main-content">
    <div class="topbar">
        <div class="topbar-title"><h1>📦 Orders</h1><p>Manage incoming orders for your shop</p></div>
        <div class="topbar-user">
            <div class="user-info"><strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong><span>Shopkeeper</span></div>
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_name'],0,1)) ?></div>
        </div>
    </div>
    <div class="page-body">
        <?php if($msgText):?><div class="alert alert-<?=$msgType?>"><?=$msgType==='success'?'✅':'❌'?> <?=htmlspecialchars($msgText)?></div><?php endif;?>

        <!-- Status Filter Tabs -->
        <div class="filter-tabs">
            <?php $filters = ['all'=>'All','pending'=>'⏳ Pending','preparing'=>'🍳 Preparing','ready'=>'✅ Ready','completed'=>'🎉 Completed','cancelled'=>'❌ Cancelled']; ?>
            <?php foreach($filters as $val=>$label): ?>
            <a href="orders.php?filter=<?=$val?>" class="filter-tab <?=$filter===$val?'active':''?>"><?=$label?></a>
            <?php endforeach; ?>
        </div>

        <div class="table-card">
            <div class="table-card-header">
                <h2>Orders (<?= count($tempOrders) ?>)</h2>
            </div>
            <?php if(count($tempOrders)>0): ?>
            <table class="data-table">
                <thead><tr><th>Order #</th><th>Customer</th><th>Items</th><th>Type</th><th>Amount</th><th>Status</th><th>Update Status</th></tr></thead>
                <tbody>
                <?php foreach($tempOrders as $o): ?>
                <tr>
                    <td>
                        <strong>#<?=str_pad($o['id'],4,'0',STR_PAD_LEFT)?></strong>
                        <div style="font-size:.72rem;color:var(--text-muted)"><?=date('d M, h:i A',strtotime($o['created_at']))?></div>
                    </td>
                    <td>
                        <strong><?=htmlspecialchars($o['customer_name'])?></strong>
                        <?php if($o['customer_phone']):?><div style="font-size:.75rem;color:var(--text-muted)">📞 <?=htmlspecialchars($o['customer_phone'])?></div><?php endif;?>
                    </td>
                    <td>
                        <!-- List of cakes in this order -->
                        <div class="order-items-list">
                            <?php foreach($orderItems[$o['id']] as $item): ?>
                            <div>• <?=htmlspecialchars($item['name'])?> ×<?=$item['quantity']?></div>
                            <?php endforeach;?>
                        </div>
                        <?php if($o['order_type']==='pickup'):?>
                        <div style="font-size:.72rem;margin-top:4px;color:#7c3aed">📅 <?=date('d M',strtotime($o['pickup_date']))?> at <?=date('h:i A',strtotime($o['pickup_time']))?></div>
                        <?php else:?>
                        <div style="font-size:.72rem;margin-top:4px;color:#db2777">📍 <?=htmlspecialchars(substr($o['delivery_address'],0,30))?>...</div>
                        <?php endif;?>
                    </td>
                    <td><span class="badge badge-<?=$o['order_type']?>"><?=$o['order_type']==='delivery'?'🚚':'🏪'?> <?=ucfirst($o['order_type'])?></span></td>
                    <td><strong>₹<?=number_format($o['total_amount'],2)?></strong></td>
                    <td><span class="badge badge-<?=$o['status']?>"><?=ucfirst($o['status'])?></span></td>
                    <td>
                        <!-- Show buttons for valid next status transitions -->
                        <?php if(!empty($nextStatus[$o['status']])): ?>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <?php foreach($nextStatus[$o['status']] as $status=>$label): ?>
                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?=$o['id']?>">
                                <input type="hidden" name="new_status" value="<?=$status?>">
                                <button type="submit" class="btn btn-sm <?=$status==='cancelled'?'btn-danger':($status==='completed'?'btn-success':'btn-primary')?>"
                                    onclick="return confirm('Set order to <?=$status?>?')"><?=$label?></button>
                            </form>
                            <?php endforeach;?>
                        </div>
                        <?php else: ?>
                        <span style="font-size:.8rem;color:var(--text-muted)">—</span>
                        <?php endif;?>
                    </td>
                </tr>
                <?php endforeach;?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state"><div class="empty-icon">📭</div><h3>No orders <?=$filter!=='all'?"with status '$filter'":'yet'?></h3></div>
            <?php endif;?>
        </div>
    </div>
</div>
</body></html>
