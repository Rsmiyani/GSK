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
        "SELECT oi.quantity, oi.price, p.name, p.image_url, c.name AS category_name
         FROM order_items oi 
         JOIN products p ON oi.product_id=p.id 
         LEFT JOIN categories c ON p.category_id=c.id
         WHERE oi.order_id={$o['id']}"
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

/* Modern Card-based Order Layout */
.order-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    margin-bottom: 24px;
    overflow: hidden;
    border: 1px solid var(--border-color);
}

.order-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 16px 20px; background: #f8f9fa; border-bottom: 1px solid var(--border-color);
    flex-wrap: wrap; gap: 10px; cursor: default;
}
.order-header-left h3 { margin: 0; font-size: 1.1rem; color: var(--text-color); }
.order-meta { font-size: 0.85rem; color: var(--text-muted); margin-top: 4px; display: flex; gap: 15px; }

.order-body { padding: 20px; display: flex; flex-direction: column; gap: 20px; }

/* Status Timeline */
.status-timeline { display: flex; justify-content: space-between; position: relative; margin: 10px 0 20px 0; padding: 0 10px; }
.status-timeline::before { content: ''; position: absolute; top: 15px; left: 25px; right: 25px; height: 3px; background: #e2e8f0; z-index: 1; border-radius: 3px; }
.status-timeline[data-progress="50"]::before { background: linear-gradient(to right, var(--primary-color) 50%, #e2e8f0 50%); }
.status-timeline[data-progress="100"]::before { background: var(--primary-color); }
.timeline-step { position: relative; z-index: 2; text-align: center; flex: 1; }
.timeline-icon { width: 34px; height: 34px; border-radius: 50%; background: #fff; border: 3px solid #e2e8f0; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px auto; color: #94a3b8; font-size: 14px; transition: all 0.3s; box-shadow: 0 0 0 4px white; }
.timeline-step.active .timeline-icon { border-color: var(--primary-color); background: var(--primary-color); color: white; }
.timeline-step.completed .timeline-icon { border-color: var(--primary-color); color: var(--primary-color); }
.timeline-label { font-size: 0.75rem; font-weight: 600; color: var(--text-muted); }
.timeline-step.active .timeline-label { color: var(--primary-color); }
.timeline-cancelled { width: 100%; text-align: center; color: #ef4444; font-weight: 600; padding: 15px; background: #fee2e2; border-radius: 8px; border: 1px dashed #f87171; }

/* Items Grid */
.order-items-grid { display: grid; gap: 15px; }
.item-card { display: flex; align-items: center; gap: 15px; padding: 12px; background: #fdfdfd; border: 1px solid var(--border-color); border-radius: 10px; }
.item-image { width: 70px; height: 70px; border-radius: 8px; object-fit: cover; background: #eee; }
.item-details { flex: 1; }
.item-title { font-weight: 600; color: var(--text-color); margin-bottom: 3px; font-size: 0.95rem; }
.item-category { font-size: 0.75rem; color: var(--text-muted); background: #e2e8f0; padding: 2px 8px; border-radius: 12px; display: inline-block; margin-bottom: 5px; }
.item-math { font-size: 0.85rem; color: var(--text-muted); }
.item-total { font-weight: 700; color: var(--text-color); }

/* Footer / Summary */
.order-footer { border-top: 1px solid var(--border-color); padding-top: 15px; display: flex; justify-content: space-between; align-items: stretch; flex-wrap: wrap; gap: 15px; }

/* Customer Box */
.customer-box { flex: 1; min-width: 250px; background: rgba(0,0,0,0.02); border-left: 3px solid var(--primary-color); padding: 15px; border-radius: 8px; font-size: 0.85rem; color: var(--text-color); }
.customer-box h4 { font-size: 0.95rem; margin-bottom: 8px; color: var(--text-color); border-bottom: 1px solid rgba(0,0,0,0.05); padding-bottom: 5px; }

/* Summary Box */
.order-summary-box { min-width: 220px; background: #fafafa; padding: 15px; border-radius: 8px; border: 1px solid #eaeaea; }
.summary-line { display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 6px; color: var(--text-muted); }
.summary-line.grand-total { border-top: 1px dashed #ccc; padding-top: 8px; margin-top: 8px; font-size: 1.1rem; font-weight: 700; color: var(--text-color); }

/* Action Buttons */
.order-actions { margin-top: 15px; padding-top: 15px; border-top: 1px dashed rgba(0,0,0,0.1); display: flex; gap: 10px; flex-wrap: wrap; }
@media(max-width: 768px) { .order-header { flex-direction: column; align-items: flex-start;} .order-meta { flex-direction: column; gap:5px;} }
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

        <div class="orders-list">
            <?php if(count($tempOrders)>0): ?>
                <?php foreach($tempOrders as $o): 
                    // Reverse calculation for GST/SGST (18% tax bracket)
                    $subtotal = $o['total_amount'] / 1.18;
                    $gst = $subtotal * 0.09;
                    $sgst = $subtotal * 0.09;
                    
                    // Status timeline progress logic
                    $progress = 0; $steps = ['pending'=>0, 'preparing'=>1, 'ready'=>2, 'completed'=>3];
                    if(isset($steps[$o['status']])) {
                        $currStep = $steps[$o['status']];
                        $progress = ($currStep / 3) * 100;
                    }
                ?>
                <div class="order-card">
                    <!-- Order Header -->
                    <div class="order-header">
                        <div class="order-header-left">
                            <h3>Order #<?=str_pad($o['id'],6,'0',STR_PAD_LEFT)?></h3>
                            <div class="order-meta">
                                <span>📅 <?=date('d M Y, h:i A',strtotime($o['created_at']))?></span>
                                <span><span class="badge badge-<?=$o['order_type']?>"><?=$o['order_type']==='delivery'?'🚚 Delivery':'🏪 Pickup'?></span></span>
                            </div>
                        </div>
                        <div class="order-header-right" style="text-align:right;">
                            <div style="font-size:1.1rem; font-weight:700; color:var(--text-color); margin-bottom:5px;">₹<?=number_format($o['total_amount'],2)?></div>
                            <span class="badge badge-<?=$o['status']?>"><?=ucfirst($o['status'])?></span>
                        </div>
                    </div>

                    <!-- Order Body -->
                    <div class="order-body">
                        <!-- Status Timeline -->
                        <?php if ($o['status'] === 'cancelled'): ?>
                            <div class="timeline-cancelled">❌ This order was cancelled.</div>
                        <?php else: ?>
                            <div class="status-timeline" data-progress="<?= $progress ?>">
                                <?php 
                                    $allSteps = [
                                        ['key'=>'pending','label'=>'Placed','icon'=>'📋'],
                                        ['key'=>'preparing','label'=>'Preparing','icon'=>'🍳'],
                                        ['key'=>'ready','label'=>'Ready/Out','icon'=>'🚚'],
                                        ['key'=>'completed','label'=>'Completed','icon'=>'🎉']
                                    ];
                                    foreach($allSteps as $idx => $s):
                                        $isPast = $steps[$o['status']] >= $idx;
                                        $isCurrent = $steps[$o['status']] === $idx;
                                        $class = $isCurrent ? 'active' : ($isPast ? 'completed' : '');
                                ?>
                                <div class="timeline-step <?= $class ?>">
                                    <div class="timeline-icon"><?= $s['icon'] ?></div>
                                    <div class="timeline-label"><?= $s['label'] ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Items Grid -->
                        <div class="order-items-grid">
                            <?php foreach($orderItems[$o['id']] as $item): ?>
                            <div class="item-card">
                                <?php if (!empty($item['image_url'])): ?>
                                    <img src="../assets/product_images/<?= htmlspecialchars($item['image_url']) ?>" alt="img" class="item-image" loading="lazy">
                                <?php else: ?>
                                    <div class="item-image" style="display:flex;align-items:center;justify-content:center;background:#f3f4f6;color:#9ca3af;">🎂</div>
                                <?php endif; ?>
                                <div class="item-details">
                                    <div class="item-title"><?=htmlspecialchars($item['name'])?></div>
                                    <?php if(!empty($item['category_name'])): ?>
                                        <span class="item-category"><?= htmlspecialchars($item['category_name']) ?></span><br>
                                    <?php endif; ?>
                                    <span class="item-math">₹<?=number_format($item['price'],2)?> × <?=$item['quantity']?></span>
                                </div>
                                <div class="item-total">₹<?=number_format($item['price'] * $item['quantity'],2)?></div>
                            </div>
                            <?php endforeach;?>
                        </div>

                        <!-- Footer -->
                        <div class="order-footer">
                            <!-- Customer & Delivery Details -->
                            <div class="customer-box">
                                <h4>Customer Details</h4>
                                <p style="margin:4px 0;"><strong><?=htmlspecialchars($o['customer_name'])?></strong></p>
                                <?php if($o['customer_phone']):?><p style="margin:4px 0; color:var(--text-muted);">📞 <?=htmlspecialchars($o['customer_phone'])?></p><?php endif;?>
                                
                                <div style="margin-top:10px; padding-top:10px; border-top:1px dashed rgba(0,0,0,0.1);">
                                    <?php if($o['order_type']==='pickup'):?>
                                        <strong>🏪 Pickup Scheduled:</strong><br>
                                        📅 <?=date('d M Y',strtotime($o['pickup_date']))?><br>
                                        ⏰ <?=date('h:i A',strtotime($o['pickup_time']))?>
                                    <?php else:?>
                                        <strong>🚚 Delivery Address:</strong><br>
                                        <?=nl2br(htmlspecialchars($o['delivery_address']))?>
                                    <?php endif;?>
                                </div>

                                <!-- Action Buttons -->
                                <?php if(!empty($nextStatus[$o['status']])): ?>
                                <div class="order-actions">
                                    <?php foreach($nextStatus[$o['status']] as $status => $label): ?>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="order_id" value="<?=$o['id']?>">
                                        <input type="hidden" name="new_status" value="<?=$status?>">
                                        <button type="submit" class="btn btn-sm <?=$status==='cancelled'?'btn-danger':($status==='completed'?'btn-success':'btn-primary')?>"
                                            onclick="return confirm('Set order to <?=$status?>?')"><?=$label?></button>
                                    </form>
                                    <?php endforeach;?>
                                </div>
                                <?php endif;?>
                            </div>
                            
                            <!-- Financial Summary -->
                            <div class="order-summary-box">
                                <div class="summary-line"><span>Subtotal (<?=count($orderItems[$o['id']])?> items)</span> <span>₹<?=number_format($subtotal,2)?></span></div>
                                <div class="summary-line"><span>CGST (9%)</span> <span>₹<?=number_format($gst,2)?></span></div>
                                <div class="summary-line"><span>SGST (9%)</span> <span>₹<?=number_format($sgst,2)?></span></div>
                                <div class="summary-line grand-total"><span>Grand Total</span> <span>₹<?=number_format($o['total_amount'],2)?></span></div>
                            </div>
                        </div>

                    </div>
                </div>
                <?php endforeach;?>
            <?php else: ?>
                <div class="empty-state"><div class="empty-icon">📭</div><h3>No orders <?=$filter!=='all'?"with status '$filter'":'yet'?></h3></div>
            <?php endif;?>
        </div>
    </div>
</div>
</body></html>
