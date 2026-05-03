<?php
/**
 * customer/order_confirm.php
 * ==========================
 * ORDER CONFIRMATION PAGE
 *
 * Shows after a successful order placement.
 * Displays order ID, items ordered, total, and delivery/pickup details.
 */

$required_role = 'customer';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$userId  = $_SESSION['user_id'];
$orderId = (int)($_GET['order_id'] ?? 0);

if ($orderId === 0) { header("Location: dashboard.php"); exit(); }

// ─── Fetch Order Details ──────────────────────────────────────────────────────
$orderRes = mysqli_query($conn,
    "SELECT o.*, s.name AS shop_name, s.address AS shop_address, s.phone AS shop_phone
     FROM orders o JOIN shops s ON o.shop_id = s.id
     WHERE o.id = $orderId AND o.customer_id = $userId"
);
$order = mysqli_fetch_assoc($orderRes);

if (!$order) { header("Location: dashboard.php"); exit(); }

// ─── Fetch Items in this Order ────────────────────────────────────────────────
$itemsRes = mysqli_query($conn,
    "SELECT oi.*, p.name AS product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $orderId"
);
$items = [];
while ($row = mysqli_fetch_assoc($itemsRes)) { $items[] = $row; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed! - Ghanshyam Bakery</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .confirm-card {
            max-width: 640px; margin: 0 auto;
            background: white; border-radius: 20px;
            padding: 40px; border: 1px solid var(--border);
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            text-align: center;
        }
        .success-icon { font-size: 5rem; margin-bottom: 16px; }
        .order-id { background: var(--body-bg); border-radius: 10px; padding: 14px; margin: 20px 0; }
        .order-id span { font-size: 1.5rem; font-weight: 800; color: var(--accent); }
        .detail-row { display:flex; justify-content:space-between; padding: 10px 0; border-bottom: 1px solid var(--border); font-size:0.9rem; }
        .detail-row:last-child { border-bottom: none; }
        /* Order status timeline */
        .status-timeline { display:flex; justify-content:space-between; margin: 24px 0; gap: 8px; }
        .status-step { position:relative; flex:1; text-align:center; font-size:0.72rem; color:var(--text-muted); display:flex; flex-direction:column; align-items:center; }
        .status-step:not(:last-child)::after { content: ''; position: absolute; top: 14px; left: 50%; width: 100%; height: 3px; background: var(--border); z-index: -1; }
        .status-step.done:not(:last-child)::after { background: var(--accent); }
        .status-step .dot {
            width:28px; height:28px; border-radius:50%;
            background: var(--border); margin: 0 auto 6px;
            display:flex; align-items:center; justify-content:center; font-size:0.85rem;
            border: 2px solid white; box-shadow: 0 0 0 2px var(--body-bg); z-index: 2;
        }
        .status-step.done .dot { background: var(--accent); color: white; }
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
        <div class="topbar-title"><h1>Order Confirmed!</h1><p>Thank you for your order</p></div>
        <div class="topbar-user">
            <div class="user-info"><strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong><span>customer</span></div>
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_name'],0,1)) ?></div>
        </div>
    </div>

    <div class="page-body">
        <div class="confirm-card">
            <!-- Success Animation -->
            <div class="success-icon">🎉</div>
            <h2 style="font-size:1.5rem;font-weight:800;color:var(--text-dark);">Order Placed Successfully!</h2>
            <p style="color:var(--text-muted);margin-top:8px;">Your fresh cakes are being prepared with love.</p>

            <!-- Order ID -->
            <div class="order-id">
                <div style="font-size:0.8rem;color:var(--text-muted);margin-bottom:6px;">YOUR ORDER ID</div>
                <span>#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></span>
            </div>

            <!-- Status Timeline (visual representation) -->
            <div class="status-timeline">
                <div class="status-step done">
                    <div class="dot">✓</div>
                    <span>Order<br>Placed</span>
                </div>
                <div class="status-step">
                    <div class="dot">🍳</div>
                    <span>Preparing</span>
                </div>
                <div class="status-step">
                    <div class="dot">✅</div>
                    <span>Ready</span>
                </div>
                <div class="status-step">
                    <div class="dot"><?= $order['order_type'] === 'delivery' ? '🚚' : '🏪' ?></div>
                    <span><?= $order['order_type'] === 'delivery' ? 'Delivered' : 'Picked Up' ?></span>
                </div>
            </div>

            <!-- Order Details -->
            <div style="text-align:left;background:var(--body-bg);border-radius:12px;padding:20px;margin-bottom:20px;">
                <div class="detail-row"><span>Shop</span><strong><?= htmlspecialchars($order['shop_name']) ?></strong></div>
                <div class="detail-row"><span>Type</span>
                    <span class="badge badge-<?= $order['order_type'] ?>">
                        <?= $order['order_type'] === 'delivery' ? '🚚 Delivery' : '🏪 Pickup' ?>
                    </span>
                </div>
                <?php if ($order['order_type'] === 'delivery'): ?>
                <div class="detail-row"><span>Address</span><span><?= htmlspecialchars($order['delivery_address']) ?></span></div>
                <?php else: ?>
                <div class="detail-row"><span>Pickup Date</span><strong><?= date('d M Y', strtotime($order['pickup_date'])) ?></strong></div>
                <div class="detail-row"><span>Pickup Time</span><strong><?= date('h:i A', strtotime($order['pickup_time'])) ?></strong></div>
                <?php endif; ?>
                <div class="detail-row"><span>Items</span>
                    <span><?php foreach ($items as $i => $item): ?>
                        <?= htmlspecialchars($item['product_name']) ?> × <?= $item['quantity'] ?><?= $i < count($items)-1 ? ', ' : '' ?>
                    <?php endforeach; ?></span>
                </div>
                <div class="detail-row"><span>Total Amount</span>
                    <strong style="color:var(--accent);font-size:1.1rem">₹<?= number_format($order['total_amount'],2) ?></strong>
                </div>
            </div>

            <!-- Action Buttons -->
            <div style="display:flex;gap:12px;justify-content:center;">
                <a href="my_orders.php" class="btn btn-primary">📦 Track Orders</a>
                <a href="shops.php"     class="btn btn-outline">🛍️ Order More</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
