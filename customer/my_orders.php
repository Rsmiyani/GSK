<?php
/**
 * customer/my_orders.php
 * ======================
 * CUSTOMER ORDER HISTORY PAGE
 *
 * Shows all orders placed by this customer with:
 *   - Order number, shop, type, total, status, date
 *   - Expandable view of items inside each order
 */

$required_role = 'customer';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$userId = $_SESSION['user_id'];

// ─── Handle Order Cancellation ───────────────────────────────────────────────
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id'])) {
    $cancelId = (int)$_POST['cancel_order_id'];
    
    // Verify order belongs to user
    $checkRes = mysqli_query($conn, "SELECT status FROM orders WHERE id = $cancelId AND customer_id = $userId");
    $orderData = mysqli_fetch_assoc($checkRes);
    
    if ($orderData) {
        if ($orderData['status'] === 'pending') {
            $updateQ = "UPDATE orders SET status = 'cancelled' WHERE id = $cancelId AND customer_id = $userId";
            if (mysqli_query($conn, $updateQ)) {
                $success_msg = "Order #" . str_pad($cancelId, 4, '0', STR_PAD_LEFT) . " has been successfully cancelled.";
            } else {
                $error_msg = "Could not cancel order due to a system error. Please try again.";
            }
        } else {
            $error_msg = "Order can no longer be cancelled as it is in the '" . ucfirst($orderData['status']) . "' stage.";
        }
    } else {
        $error_msg = "Invalid order specified.";
    }
}

// ─── Fetch All Orders for This Customer ──────────────────────────────────────
// Most recent orders first (ORDER BY created_at DESC)
$ordersRes = mysqli_query($conn,
    "SELECT o.*, s.name AS shop_name
     FROM orders o JOIN shops s ON o.shop_id = s.id
     WHERE o.customer_id = $userId
     ORDER BY o.created_at DESC"
);
$orders = [];
while ($row = mysqli_fetch_assoc($ordersRes)) { $orders[] = $row; }

// ─── For each order, fetch its items ─────────────────────────────────────────
$orderItems = [];
foreach ($orders as $order) {
    $itemsRes = mysqli_query($conn,
        "SELECT oi.*, p.name AS product_name, p.image_url, c.name AS category_name
         FROM order_items oi 
         JOIN products p ON oi.product_id = p.id
         LEFT JOIN categories c ON p.category_id = c.id
         WHERE oi.order_id = {$order['id']}"
    );
    $orderItems[$order['id']] = [];
    while ($item = mysqli_fetch_assoc($itemsRes)) {
        $orderItems[$order['id']][] = $item;
    }
}

// Status badge colors (defined in dashboard.css)
$statusColors = [
    'pending'   => '⏳',
    'preparing' => '🍳',
    'ready'     => '✅',
    'completed' => '🎉',
    'cancelled' => '❌',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Ghanshyam Bakery</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        :root { --primary-color: var(--accent); }
        /* Modern Card-based Order Layout */
        .order-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 24px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid var(--border-color);
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .order-header-left h3 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--text-color);
        }
        
        .order-meta {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 4px;
            display: flex;
            gap: 15px;
        }
        
        .order-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Status Timeline */
        .status-timeline {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            position: relative;
            margin: 12px 0 22px 0;
            padding: 6px 6px 0;
            gap: 8px;
        }

        .timeline-step {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        
        .timeline-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 22px;
            left: 50%;
            width: 100%;
            height: 4px;
            background: #e2e8f0;
            z-index: -1;
            border-radius: 999px;
        }

        .timeline-step.completed:not(:last-child)::after {
            background: var(--success);
        }

        .timeline-step.active:not(:last-child)::after {
            background: linear-gradient(to right, var(--accent) 50%, #e2e8f0 50%);
        }
        .timeline-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #fff;
            border: 2px solid #dbe4ef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 16px;
            transition: all 0.25s ease;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06), 0 0 0 5px white;
        }
        .timeline-step.active .timeline-icon {
            border-color: transparent;
            background: linear-gradient(135deg, var(--accent), #fb7185);
            color: white;
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 12px 26px rgba(233, 30, 140, 0.24), 0 0 0 5px white;
        }
        .timeline-step.completed .timeline-icon {
            border-color: #bbf7d0;
            background: #ecfdf5;
            color: #059669;
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.12), 0 0 0 5px white;
        }
        .timeline-label {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-muted);
            line-height: 1.25;
        }
        .timeline-step.active .timeline-label { color: var(--accent); }
        .timeline-step.completed .timeline-label { color: #047857; }
        
        .timeline-cancelled { width: 100%; text-align: center; color: #ef4444; font-weight: 600; padding: 15px; background: #fee2e2; border-radius: 8px; border: 1px dashed #f87171; }

        /* Items Grid */
        .order-items-grid {
            display: grid;
            gap: 15px;
        }
        
        .item-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            background: #fdfdfd;
            border: 1px solid var(--border-color);
            border-radius: 10px;
        }
        
        .item-image {
            width: 70px;
            height: 70px;
            border-radius: 8px;
            object-fit: cover;
            background: #eee;
        }
        
        .item-details { flex: 1; }
        .item-title { font-weight: 600; color: var(--text-color); margin-bottom: 3px; font-size: 0.95rem; }
        .item-category { font-size: 0.75rem; color: var(--text-muted); background: #e2e8f0; padding: 2px 8px; border-radius: 12px; display: inline-block; margin-bottom: 5px; }
        .item-math { font-size: 0.85rem; color: var(--text-muted); }
        .item-total { font-weight: 700; color: var(--text-color); }

        /* Summary Section */
        .order-footer {
            border-top: 1px solid var(--border-color);
            padding-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .order-delivery-info {
            flex: 1;
            min-width: 250px;
            font-size: 0.85rem;
            color: var(--text-muted);
            background: rgba(0,0,0,0.02);
            padding: 12px 15px;
            border-radius: 8px;
            border-left: 3px solid var(--primary-color);
        }
        
        .order-summary-box {
            min-width: 220px;
            background: #fafafa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #eaeaea;
        }
        .summary-line { display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 6px; color: var(--text-muted); }
        .summary-line.grand-total { border-top: 1px dashed #ccc; padding-top: 8px; margin-top: 8px; font-size: 1.1rem; font-weight: 700; color: var(--text-color); }
        
        /* Expand/Collapse logic */
        .order-body-wrapper { display: none; }
        .order-body-wrapper.open { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from{opacity:0; transform:translateY(-5px);} to{opacity:1; transform:translateY(0);} }
        
        @media(max-width: 768px) {
             .order-header { flex-direction: column; align-items: flex-start;}
             .order-meta { flex-direction: column; gap:5px;}
        }
    </style>
</head>
<body class="dashboard-body">

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="../assets/logo/image.png" alt="GSK Logo">
        <div><h2>Ghanshyam Bakery</h2><span>Customer Portal</span></div>
    </div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Main Menu</span>
        <a href="dashboard.php"><span class="nav-icon">🏠</span> Dashboard</a>
        <a href="shops.php"><span class="nav-icon">📍</span> Find Shops</a>
        <a href="cart.php"><span class="nav-icon">🛒</span> My Cart</a>
        <a href="my_orders.php" class="active"><span class="nav-icon">📦</span> My Orders</a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php"><span>🚪</span> Logout</a></div>
</aside>

<div class="main-content">
    <div class="topbar">
        <div class="topbar-title">
            <h1>📦 My Orders</h1>
            <p><?= count($orders) ?> order(s) total</p>
        </div>
        <div class="topbar-user">
            <div class="user-info"><strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong><span>customer</span></div>
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_name'],0,1)) ?></div>
        </div>
    </div>

    <div class="page-body">
        <?php if ($success_msg): ?>
            <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                ✅ <?= htmlspecialchars($success_msg) ?>
            </div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                ⚠️ <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <?php if (count($orders) > 0): ?>
        <div class="orders-list" style="display:flex; flex-direction:column; gap:16px;">
            <?php foreach ($orders as $order): 
                // Reverse calculation for GST/SGST (18% tax bracket)
                // Subtotal * 1.18 = Total
                $subtotal = $order['total_amount'] / 1.18;
                $gst = $subtotal * 0.09;
                $sgst = $subtotal * 0.09;
                
                // Status timeline progress logic
                $progress = 0; $steps = ['pending'=>0, 'preparing'=>1, 'ready'=>2, 'completed'=>3];
                if(isset($steps[$order['status']])) {
                    $currStep = $steps[$order['status']];
                    $progress = ($currStep / 3) * 100;
                }
            ?>
            
            <div class="order-card">
                <!-- Outer clickable header -->
                <div class="order-header" onclick="toggleDetail(<?= $order['id'] ?>)" style="cursor:pointer;">
                    <div class="order-header-left">
                        <h3>Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h3>
                        <div class="order-meta">
                            <span>📅 <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></span>
                            <span>🏪 <?= htmlspecialchars($order['shop_name']) ?></span>
                            <span><span class="badge badge-<?= $order['order_type'] ?>"><?= $order['order_type'] === 'delivery' ? '🚚 Delivery' : '🏪 Pickup' ?></span></span>
                        </div>
                    </div>
                    <div class="order-header-right" style="text-align:right;">
                        <div style="font-size:1.1rem; font-weight:700; color:var(--text-color); margin-bottom:5px;">₹<?= number_format($order['total_amount'], 2) ?></div>
                        <span class="badge badge-<?= $order['status'] ?>">
                            <?= ($statusColors[$order['status']] ?? '') ?> <?= ucfirst($order['status']) ?>
                        </span>
                        <span style="font-size:0.75rem; color:#cbd5e1; margin-left:10px;">▼ expand</span>
                    </div>
                </div>

                <!-- Expandable Body -->
                <div class="order-body-wrapper" id="detail-<?= $order['id'] ?>">
                    <div class="order-body">
                        
                        <!-- Status Timeline -->
                        <?php if ($order['status'] === 'cancelled'): ?>
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
                                        $isPast = $steps[$order['status']] >= $idx;
                                        $isCurrent = $steps[$order['status']] === $idx;
                                        $class = $isCurrent ? 'active' : ($isPast ? 'completed' : '');
                                ?>
                                <div class="timeline-step <?= $class ?>">
                                    <div class="timeline-icon"><?= $s['icon'] ?></div>
                                    <div class="timeline-label"><?= $s['label'] ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Items List -->
                        <div class="order-items-title" style="font-weight:600; font-size:0.9rem; margin-bottom:10px; color:var(--text-color); border-bottom:1px solid var(--border-color); padding-bottom:5px;">Items in this order:</div>
                        <div class="order-items-grid">
                            <?php foreach ($orderItems[$order['id']] as $item): ?>
                            <div class="item-card">
                                <?php if (!empty($item['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($item['image_url']) ?>" onerror="this.src='https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=80&q=60'" alt="img" class="item-image" loading="lazy">
                                <?php else: ?>
                                    <div class="item-image" style="display:flex;align-items:center;justify-content:center;background:#f3f4f6;color:#9ca3af;">🎂</div>
                                <?php endif; ?>
                                
                                <div class="item-details">
                                    <div class="item-title"><?= htmlspecialchars($item['product_name']) ?></div>
                                    <?php if(!empty($item['category_name'])): ?>
                                        <span class="item-category"><?= htmlspecialchars($item['category_name']) ?></span><br>
                                    <?php endif; ?>
                                    <span class="item-math">₹<?= number_format($item['price'], 2) ?> × <?= $item['quantity'] ?></span>
                                </div>
                                <div class="item-total">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Footer / Summary / Delivery Info -->
                        <div class="order-footer">
                            <div class="order-delivery-info">
                                <strong><?= $order['order_type'] === 'pickup' ? 'Pickup Details' : 'Delivery Details' ?></strong><br>
                                <?php if ($order['order_type'] === 'pickup'): ?>
                                    <div style="margin-top:4px;">
                                        📅 Date: <strong><?= date('d F Y',strtotime($order['pickup_date'])) ?></strong><br>
                                        ⏰ Time: <strong><?= date('h:i A',strtotime($order['pickup_time'])) ?></strong>
                                    </div>
                                <?php else: ?>
                                    <div style="margin-top:4px; line-height:1.4;">
                                        📍 <strong><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></strong>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Cancellation UI -->
                                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed rgba(0,0,0,0.1);">
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to cancel this order? This action cannot be undone.');">
                                            <input type="hidden" name="cancel_order_id" value="<?= $order['id'] ?>">
                                            <button type="submit" class="btn btn-danger" style="background-color: var(--danger-color, #dc3545); border: none; padding: 6px 14px; font-size: 0.85rem;">
                                                ❌ Cancel Order
                                            </button>
                                        </form>
                                    <?php elseif ($order['status'] === 'cancelled'): ?>
                                        <span style="font-size: 0.85rem; color: var(--danger-color, #dc3545); font-weight: 600;">⚠️ Order Cancelled</span>
                                    <?php else: ?>
                                        <span style="font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 5px;">
                                            <button disabled class="btn btn-outline" style="opacity: 0.6; cursor: not-allowed; padding: 6px 14px; font-size: 0.85rem;">❌ Cancel Order</button>
                                            <span style="margin-left: 10px;">Cannot be cancelled (<?= ucfirst($order['status']) ?>)</span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="order-summary-box">
                                <div class="summary-line"><span>Subtotal (<?= count($orderItems[$order['id']]) ?> items)</span> <span>₹<?= number_format($subtotal, 2) ?></span></div>
                                <div class="summary-line"><span>CGST (9%)</span> <span>₹<?= number_format($gst, 2) ?></span></div>
                                <div class="summary-line"><span>SGST (9%)</span> <span>₹<?= number_format($sgst, 2) ?></span></div>
                                <div class="summary-line grand-total"><span>Grand Total</span> <span>₹<?= number_format($order['total_amount'], 2) ?></span></div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="table-card">
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h3>No orders yet</h3>
                <p>Find a nearby shop and place your first order!</p>
                <a href="shops.php" class="btn btn-primary" style="margin-top:16px;">Browse Shops</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Toggles the order item detail panel open/closed
function toggleDetail(orderId) {
    const detail = document.getElementById('detail-' + orderId);
    detail.classList.toggle('open');
}
</script>
</body>
</html>
