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
        "SELECT oi.*, p.name AS product_name
         FROM order_items oi JOIN products p ON oi.product_id = p.id
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
        /* Collapsible order row detail section */
        .order-detail { display: none; padding: 16px; background: var(--body-bg); border-radius: 0 0 10px 10px; }
        .order-detail.open { display: block; }
        .order-row { cursor: pointer; }
        .order-row:hover td { background: rgba(233,30,140,0.04); }
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
        <div class="table-card">
            <div class="table-card-header">
                <h2>Order History</h2>
                <span style="font-size:0.8rem;color:var(--text-muted);">Click a row to see items</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Shop</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <!-- Clickable Row: toggles the detail section below -->
                    <tr class="order-row" onclick="toggleDetail(<?= $order['id'] ?>)">
                        <td><strong>#<?= str_pad($order['id'],4,'0',STR_PAD_LEFT) ?></strong></td>
                        <td><?= htmlspecialchars($order['shop_name']) ?></td>
                        <td>
                            <span class="badge badge-<?= $order['order_type'] ?>">
                                <?= $order['order_type'] === 'delivery' ? '🚚 Delivery' : '🏪 Pickup' ?>
                            </span>
                        </td>
                        <td><strong>₹<?= number_format($order['total_amount'],2) ?></strong></td>
                        <td>
                            <span class="badge badge-<?= $order['status'] ?>">
                                <?= ($statusColors[$order['status']] ?? '') ?> <?= ucfirst($order['status']) ?>
                            </span>
                        </td>
                        <td><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></td>
                    </tr>
                    <!-- Expandable detail row -->
                    <tr>
                        <td colspan="6" style="padding:0;border:none;">
                            <div class="order-detail" id="detail-<?= $order['id'] ?>">
                                <strong>Items Ordered:</strong>
                                <ul style="margin-top:8px;padding-left:20px;">
                                    <?php foreach ($orderItems[$order['id']] as $item): ?>
                                    <li style="margin-bottom:4px;font-size:0.88rem;">
                                        <?= htmlspecialchars($item['product_name']) ?>
                                        × <?= $item['quantity'] ?>
                                        — <strong>₹<?= number_format($item['price'] * $item['quantity'], 2) ?></strong>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php if ($order['order_type'] === 'pickup'): ?>
                                <p style="margin-top:10px;font-size:0.85rem;">
                                    📅 Pickup: <strong><?= date('d M Y',strtotime($order['pickup_date'])) ?>
                                    at <?= date('h:i A',strtotime($order['pickup_time'])) ?></strong>
                                </p>
                                <?php else: ?>
                                <p style="margin-top:10px;font-size:0.85rem;">
                                    📍 Delivery to: <strong><?= htmlspecialchars($order['delivery_address']) ?></strong>
                                </p>
                                <?php endif; ?>
                                
                                <!-- Cancellation UI -->
                                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; align-items: center;">
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to cancel this order? This action cannot be undone.');">
                                            <input type="hidden" name="cancel_order_id" value="<?= $order['id'] ?>">
                                            <button type="submit" class="btn btn-danger" style="background-color: var(--danger-color, #dc3545); border: none; padding: 6px 14px; font-size: 0.85rem;">
                                                ❌ Cancel Order
                                            </button>
                                        </form>
                                    <?php elseif ($order['status'] === 'cancelled'): ?>
                                        <span style="font-size: 0.85rem; color: var(--danger-color, #dc3545); font-weight: 600;">⚠️ This order has been cancelled</span>
                                    <?php else: ?>
                                        <span style="font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 5px;">
                                            <button disabled class="btn btn-outline" style="opacity: 0.6; cursor: not-allowed; padding: 6px 14px; font-size: 0.85rem;">
                                                ❌ Cancel Order
                                            </button>
                                            <span style="margin-left: 10px;">Order can no longer be cancelled (Status: <?= ucfirst($order['status']) ?>)</span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
