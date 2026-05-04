<?php
/**
 * customer/my_orders.php
 * ======================
 * CUSTOMER ORDER HISTORY PAGE
 * 
 * This page allows customers to track their current orders and view their history.
 * It includes a visual timeline for active orders and a cancellation system.
 * 
 * FEATURES:
 *   - Status Tabs: Filter orders by 'All', 'Pending', 'Preparing', etc.
 *   - Live Timeline: Shows progress from Pending → Preparing → Ready → Completed.
 *   - Order Cancellation: Only allowed if the order is still 'pending'.
 *   - Detailed Breakdown: Shows products, flavors, weights, and shop info for each order.
 */

// ─── Access Control ────────────────────────────────────────────────────────────
$required_role = 'customer';
require_once '../includes/auth_check.php'; // Ensures customer-only access
require_once '../config/db.php';           // Database connection ($conn)

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userInitial = strtoupper(substr($userName, 0, 1));

// ─── Handle Order Cancellation ───────────────────────────────────────────────
/**
 * Cancellation logic:
 *   1. Check if the order belongs to the current user.
 *   2. Check if the current status is 'pending'.
 *   3. If both pass, update status to 'cancelled'.
 */
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id'])) {
    $cancelId = (int)$_POST['cancel_order_id'];
    
    // Security check: verify ownership and current status
    $checkRes = mysqli_query($conn, "SELECT status FROM orders WHERE id = $cancelId AND customer_id = $userId");
    $orderData = mysqli_fetch_assoc($checkRes);
    
    if ($orderData) {
        if ($orderData['status'] === 'pending') {
            $updateQ = "UPDATE orders SET status = 'cancelled' WHERE id = $cancelId AND customer_id = $userId";
            if (mysqli_query($conn, $updateQ)) {
                $success_msg = "Order #" . str_pad($cancelId, 4, '0', STR_PAD_LEFT) . " has been cancelled successfully.";
            } else {
                $error_msg = "Could not cancel order due to a system error.";
            }
        } else {
            // Cannot cancel if the shopkeeper has already started "preparing" the order
            $error_msg = "Order can no longer be cancelled as it is already " . ucfirst($orderData['status']) . ".";
        }
    } else {
        $error_msg = "Invalid order specified.";
    }
}

// ─── Fetch All Orders ────────────────────────────────────────────────────────
/**
 * Fetch all orders for this user, including shop details.
 * Orders are sorted by newest first (created_at DESC).
 */
$ordersRes = mysqli_query($conn,
    "SELECT o.*, s.name AS shop_name, s.address AS shop_address
     FROM orders o JOIN shops s ON o.shop_id = s.id
     WHERE o.customer_id = $userId
     ORDER BY o.created_at DESC"
);
$orders = [];
while ($row = mysqli_fetch_assoc($ordersRes)) { $orders[] = $row; }

/**
 * Fetch line items for each order.
 * We store them in a nested array: $orderItems[order_id] = [item1, item2, ...].
 */
$orderItems = [];
foreach ($orders as $order) {
    $itemsRes = mysqli_query($conn,
        "SELECT oi.*, p.name AS product_name, p.flavor AS product_flavor, p.image_url, oi.variant_weight
         FROM order_items oi 
         JOIN products p ON oi.product_id = p.id
         WHERE oi.order_id = {$order['id']}"
     );
    $orderItems[$order['id']] = [];
    while ($item = mysqli_fetch_assoc($itemsRes)) {
        $orderItems[$order['id']][] = $item;
    }
}

// ─── Status Mapping & Filtering ──────────────────────────────────────────────
// Progress percentage for the UI progress bar
$statusMap = ['pending'=>25, 'preparing'=>50, 'ready'=>75, 'completed'=>100, 'cancelled'=>0];

// Handle the ?status=... filter in the URL
$allowedStatusFilters = ['all', 'pending', 'preparing', 'ready', 'completed', 'cancelled'];
$currentFilter = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : 'all';
if (!in_array($currentFilter, $allowedStatusFilters, true)) {
    $currentFilter = 'all';
}

// Apply filter to the $orders array
$filteredOrders = array_values(array_filter($orders, function ($order) use ($currentFilter) {
    if ($currentFilter === 'all') { return true; }
    return trim(strtolower($order['status'])) === $currentFilter;
}));

// ─── KPI Calculations (Summary Stats) ────────────────────────────────────────
$totalOrders = count($orders);
$activeOrders = 0;
$completedOrders = 0;
$cancelledOrders = 0;

foreach ($orders as $orderCountRow) {
    $normalizedStatus = trim(strtolower($orderCountRow['status']));
    if (in_array($normalizedStatus, ['pending', 'preparing', 'ready'], true)) {
        $activeOrders++;
    } elseif ($normalizedStatus === 'completed') {
        $completedOrders++;
    } elseif ($normalizedStatus === 'cancelled') {
        $cancelledOrders++;
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>My Orders - Ghanshyam Bakery</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                    "surface-container-low": "#f5f3ee", "on-secondary-fixed-variant": "#643c35", "on-tertiary-fixed": "#400009", "surface-dim": "#dbdad4",
                    "primary-fixed": "#fbdbde", "tertiary": "#be0630", "background": "#fbf9f3", "primary-fixed-dim": "#debfc2", "error-container": "#ffdad6",
                    "secondary": "#7f534b", "surface": "#fbf9f3", "primary": "#70585b", "surface-container-high": "#eae8e2", "surface-variant": "#e4e2dd",
                    "surface-container-highest": "#e4e2dd", "inverse-surface": "#30312d", "primary-container": "#fadadd", "secondary-fixed": "#ffdad4",
                    "surface-bright": "#fbf9f3", "outline": "#807475", "surface-container": "#f0eee8", "on-primary": "#ffffff", "secondary-container": "#fec4ba",
                    "on-surface": "#1b1c19", "on-secondary": "#ffffff", "outline-variant": "#d2c3c4", "on-tertiary": "#ffffff", "on-background": "#1b1c19",
                    "surface-container-lowest": "#ffffff", "tertiary-fixed-dim": "#ffb3b3", "on-secondary-container": "#7a4f47", "inverse-primary": "#debfc2",
                    "on-primary-container": "#765e61", "surface-tint": "#70585b", "tertiary-container": "#ffd9d8", "on-primary-fixed": "#281719",
                    "on-surface-variant": "#4f4445", "on-tertiary-container": "#c61235", "on-error": "#ffffff", "secondary-fixed-dim": "#f2b9af",
                    "error": "#ba1a1a", "on-error-container": "#93000a", "on-tertiary-fixed-variant": "#920022", "on-primary-fixed-variant": "#574144",
                    "on-secondary-fixed": "#31120d", "inverse-on-surface": "#f2f1eb", "tertiary-fixed": "#ffdad9"
            },
            "fontFamily": {
                    "headline-lg": ["Noto Serif"], "label-sm": ["Plus Jakarta Sans"], "body-md": ["Plus Jakarta Sans"],
                    "label-md": ["Plus Jakarta Sans"], "body-lg": ["Plus Jakarta Sans"], "headline-md": ["Noto Serif"], "headline-sm": ["Noto Serif"]
            }
          }
        }
      }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .ambient-shadow { box-shadow: 0 10px 30px -10px rgba(61, 28, 22, 0.08); }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-surface text-on-surface font-body-md antialiased min-h-screen">

<!-- ═══ TOP NAVBAR ═══════════════════════════════════════════════════════════ -->
<header class="bg-stone-50 border-b border-rose-100 shadow-sm shadow-amber-900/5 sticky top-0 z-50">
    <nav class="flex justify-between items-center w-full px-8 py-4 font-serif text-base tracking-tight">
        <div class="flex items-center gap-6">
            <img src="../assets/logo/image.png" alt="Logo" class="w-10 h-10 object-cover rounded-md"/>
            <div>
                <div class="text-2xl font-bold text-amber-900">Ghanshyam Bakery &amp; Live Cake Shop</div>
                <div class="text-sm text-stone-500">Bringing your nearest cake shop just a click away.</div>
            </div>
        </div>
        <div class="flex items-center gap-8">
            <div class="hidden md:flex gap-6">
                <a class="text-stone-500 font-medium hover:text-amber-700 transition-colors duration-300 ease-in-out" href="dashboard.php">Home</a>
                <a class="text-stone-500 font-medium hover:text-amber-700 transition-colors duration-300 ease-in-out" href="shops.php">Shop</a>
                <a class="text-stone-500 font-medium hover:text-amber-700 transition-colors duration-300 ease-in-out" href="cart.php">Cart</a>
                <a class="text-amber-900 border-b-2 border-amber-900 font-bold pb-1 duration-300 ease-in-out" href="my_orders.php">Orders</a>
            </div>
            <div class="flex items-center gap-4">
                <a href="cart.php" class="text-amber-950 p-2 hover:bg-rose-50 rounded-full transition-colors">
                    <span class="material-symbols-outlined">shopping_cart</span>
                </a>
                <a href="../logout.php" class="text-amber-950 p-2 hover:bg-rose-50 rounded-full transition-colors" title="Logout">
                    <span class="material-symbols-outlined">logout</span>
                </a>
            </div>
        </div>
    </nav>
</header>

<div class="flex w-full">
    <!-- ═══ SIDEBAR NAVIGATION ══════════════════════════════════════════════════ -->
    <aside class="hidden lg:flex flex-col h-[calc(100vh-80px)] w-72 bg-white border-r border-rose-50 shadow-lg shadow-amber-900/5 sticky top-20 p-6 space-y-2 font-serif text-sm font-medium">
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-full bg-secondary-container flex items-center justify-center text-amber-950 font-bold text-lg">
                    <?= $userInitial ?>
                </div>
                <div>
                    <p class="font-bold text-amber-950"><?= htmlspecialchars($userName) ?></p>
                    <p class="text-xs text-stone-500">Customer</p>
                </div>
            </div>
        </div>
        <p class="text-[10px] uppercase tracking-widest text-stone-400 font-bold px-4 mb-2">Navigation</p>
        <a class="text-stone-500 px-4 py-3 flex items-center gap-3 hover:bg-rose-50/50 rounded-lg transition-all" href="dashboard.php">
            <span class="material-symbols-outlined">home</span> Home
        </a>
        <a class="text-stone-500 px-4 py-3 flex items-center gap-3 hover:bg-rose-50/50 rounded-lg transition-all" href="shops.php">
            <span class="material-symbols-outlined">storefront</span> Browse Shops
        </a>
        <a class="bg-rose-50 text-amber-900 font-semibold rounded-lg px-4 py-3 flex items-center gap-3 scale-[0.98] active:scale-95 duration-200" href="my_orders.php">
            <span class="material-symbols-outlined">receipt_long</span> My Orders
        </a>
    </aside>

    <!-- ═══ MAIN CONTENT: ORDER CENTER ══════════════════════════════════════════ -->
    <main class="flex-1 p-8 overflow-x-hidden">
        
        <!-- Welcome Banner & Summary Cards -->
        <div class="bg-white rounded-2xl border border-rose-50 ambient-shadow overflow-hidden mb-8">
            <div class="bg-gradient-to-r from-amber-50 via-rose-50 to-stone-50 p-6 md:p-8 flex flex-col md:flex-row md:items-end md:justify-between gap-6">
                <div>
                    <p class="text-label-sm uppercase tracking-[0.2em] text-stone-500 mb-3">Order Center</p>
                    <h1 class="font-headline-lg text-amber-950">My Orders</h1>
                    <p class="text-body-md text-stone-600 mt-3 max-w-2xl">Track every cake journey, from preparation to doorstep delivery, in one calm view.</p>
                </div>
                <!-- Stat Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 w-full md:w-auto">
                    <div class="bg-white/80 backdrop-blur rounded-xl border border-rose-100 px-4 py-3 min-w-[108px] shadow-sm">
                        <p class="text-[10px] uppercase tracking-widest text-stone-400">Total</p>
                        <p class="text-2xl font-bold text-amber-950 mt-1"><?= $totalOrders ?></p>
                    </div>
                    <div class="bg-white/80 backdrop-blur rounded-xl border border-rose-100 px-4 py-3 min-w-[108px] shadow-sm">
                        <p class="text-[10px] uppercase tracking-widest text-stone-400">Active</p>
                        <p class="text-2xl font-bold text-secondary mt-1"><?= $activeOrders ?></p>
                    </div>
                    <div class="bg-white/80 backdrop-blur rounded-xl border border-rose-100 px-4 py-3 min-w-[108px] shadow-sm">
                        <p class="text-[10px] uppercase tracking-widest text-stone-400">Done</p>
                        <p class="text-2xl font-bold text-green-700 mt-1"><?= $completedOrders ?></p>
                    </div>
                    <div class="bg-white/80 backdrop-blur rounded-xl border border-rose-100 px-4 py-3 min-w-[108px] shadow-sm">
                        <p class="text-[10px] uppercase tracking-widest text-stone-400">Cancelled</p>
                        <p class="text-2xl font-bold text-red-600 mt-1"><?= $cancelledOrders ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="flex flex-wrap items-center gap-3 mb-8">
            <?php
                $filterLabels = [
                    'all' => 'All Orders',
                    'pending' => 'Pending',
                    'preparing' => 'Preparing',
                    'ready' => 'Ready',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled'
                ];
            ?>
            <?php foreach ($filterLabels as $filterKey => $filterLabel): ?>
                <?php $isActiveFilter = $currentFilter === $filterKey; ?>
                <a href="?status=<?= urlencode($filterKey) ?>"
                   class="px-4 py-2 rounded-full text-sm font-semibold border transition-all <?= $isActiveFilter ? 'bg-secondary text-white border-secondary shadow-md shadow-secondary/20' : 'bg-white text-stone-600 border-stone-200 hover:bg-rose-50 hover:border-rose-200' ?>">
                    <?= htmlspecialchars($filterLabel) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Status Messages (Success/Error) -->
        <?php if ($success_msg): ?>
            <div class="mb-6 p-4 bg-green-50 text-green-800 rounded-lg border border-green-200 flex items-center gap-3">
                <span class="material-symbols-outlined">check_circle</span> <?= htmlspecialchars($success_msg) ?>
            </div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="mb-6 p-4 bg-red-50 text-red-800 rounded-lg border border-red-200 flex items-center gap-3">
                <span class="material-symbols-outlined">error</span> <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <!-- Order Cards Loop -->
        <?php if (empty($filteredOrders)): ?>
            <!-- Empty State -->
            <div class="bg-white rounded-2xl py-16 px-8 text-center border border-rose-50 ambient-shadow">
                <div class="w-20 h-20 bg-gradient-to-br from-rose-50 to-stone-100 rounded-full flex items-center justify-center mx-auto mb-6 border border-rose-100">
                    <span class="material-symbols-outlined text-5xl text-secondary">cake</span>
                </div>
                <h3 class="font-headline-sm text-amber-950 mb-2"><?= $currentFilter === 'all' ? 'No orders yet' : 'No orders in this status' ?></h3>
                <p class="text-stone-500 mb-6 max-w-md mx-auto">
                    <?= $currentFilter === 'all'
                        ? 'You haven\'t placed any orders with Ghanshyam Bakery &amp; Live Cake Shop yet.'
                        : 'Try switching to another status filter to see more orders.' ?>
                </p>
                <a href="shops.php" class="inline-block px-6 py-3 bg-secondary text-white rounded-lg font-label-md hover:bg-on-secondary-fixed-variant transition-colors shadow-md shadow-secondary/20">Start Browsing</a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($filteredOrders as $order): ?>
                <?php 
                    $normalizedStatus = trim(strtolower($order['status']));
                    $progress = $statusMap[$normalizedStatus] ?? 0;
                    $items = $orderItems[$order['id']] ?? [];
                    $isCancelled = $normalizedStatus === 'cancelled';
                    
                    // Colors based on status
                    $statusTone = $isCancelled ? 'bg-red-50 text-red-700 border-red-100' : 'bg-primary-container text-on-primary-container border-primary-container';
                    $statusLabel = ucfirst($normalizedStatus);
                    $statusSteps = ['pending', 'preparing', 'ready', 'completed'];
                ?>
                <!-- Individual Order Card -->
                <div class="bg-white rounded-2xl overflow-hidden border border-rose-100 ambient-shadow group hover:-translate-y-0.5 transition-transform duration-300">
                    <!-- Order Header Section -->
                    <div class="bg-gradient-to-r from-surface-container-low via-white to-rose-50 p-6 border-b border-rose-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <h3 class="font-headline-sm text-lg text-amber-950">Order #<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></h3>
                                <span class="px-3 py-1 rounded-full text-label-sm uppercase font-bold border <?= $statusTone ?>">
                                    <?= $order['order_type'] === 'delivery' ? '🚚 Delivery' : '🏪 Pickup' ?>
                                </span>
                            </div>
                            <!-- Metadata Labels -->
                            <div class="flex flex-wrap items-center gap-2 text-label-sm text-stone-500 mt-3">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-white border border-stone-200 shadow-sm">
                                    <span class="material-symbols-outlined text-[16px] text-secondary">schedule</span>
                                    <?= date('M d, Y - h:i A', strtotime($order['created_at'])) ?>
                                </span>
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-white border border-stone-200 shadow-sm">
                                    <span class="material-symbols-outlined text-[16px] text-secondary">storefront</span>
                                    <?= htmlspecialchars($order['shop_name']) ?>
                                </span>
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-white border border-stone-200 shadow-sm">
                                    <span class="material-symbols-outlined text-[16px] text-secondary">inventory_2</span>
                                    <?= count($items) ?> item<?= count($items) === 1 ? '' : 's' ?>
                                </span>
                            </div>
                        </div>
                        <!-- Order Total Display -->
                        <div class="text-right bg-white rounded-xl border border-rose-100 px-4 py-3 shadow-sm min-w-[160px]">
                            <p class="text-sm text-stone-500 mb-1">Total Amount</p>
                            <p class="font-headline-sm text-xl text-amber-950">₹<?= number_format($order['total_amount'], 2) ?></p>
                            <p class="text-xs text-stone-400 mt-1"><?= $statusLabel ?></p>
                        </div>
                    </div>

                    <!-- Order Progress & Items -->
                    <div class="p-6 md:p-7">
                        <!-- Progress Timeline (for non-cancelled orders) -->
                        <?php if (!$isCancelled): ?>
                            <div class="mb-8 rounded-2xl bg-stone-50 border border-rose-100 p-4 md:p-5">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-label-sm font-bold text-amber-950 uppercase tracking-widest">Status timeline</span>
                                    <span class="text-label-sm font-bold text-secondary bg-white border border-rose-100 px-3 py-1 rounded-full shadow-sm"><?= $statusLabel ?></span>
                                </div>
                                <div class="relative pt-4">
                                    <!-- Gray background line -->
                                    <div class="absolute left-4 right-4 top-8 h-[2px] bg-surface-container-high"></div>
                                    <!-- Animated progress line -->
                                    <div class="absolute left-4 top-8 h-[2px] bg-gradient-to-r from-secondary via-rose-400 to-secondary transition-all duration-1000" style="width: <?= $progress ?>%;"></div>
                                    
                                    <!-- Progress Dots Loop -->
                                    <div class="grid grid-cols-4 gap-2 relative">
                                        <?php foreach ($statusSteps as $index => $step): ?>
                                            <?php
                                                $stepPosition = ($index + 1) * 25;
                                                $isStepActive = $progress >= $stepPosition;
                                                $isCurrentStep = $normalizedStatus === $step;
                                            ?>
                                            <div class="flex flex-col items-center text-center gap-3">
                                                <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center bg-white z-10 <?= $isStepActive ? 'border-secondary text-secondary shadow-sm' : 'border-stone-200 text-stone-400' ?> <?= $isCurrentStep ? 'ring-4 ring-rose-100' : '' ?>">
                                                    <?php if ($isStepActive): ?>
                                                        <span class="material-symbols-outlined text-[16px]">check</span>
                                                    <?php else: ?>
                                                        <span class="text-[11px] font-bold"><?= $index + 1 ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="px-3 py-2 rounded-xl text-[11px] sm:text-label-sm font-medium border w-full <?= $isStepActive ? 'bg-white border-secondary text-secondary shadow-sm' : 'bg-white/70 border-stone-200 text-stone-400' ?>">
                                                    <?= ucfirst($step) ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Cancelled State Message -->
                            <div class="mb-6 p-4 bg-error-container text-on-error-container rounded-2xl border border-red-200 text-center font-bold shadow-sm">
                                This order was cancelled.
                            </div>
                        <?php endif; ?>

                        <!-- Products List in this Order -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($items as $item): ?>
                            <div class="flex items-center gap-4 p-4 bg-stone-50 rounded-2xl border border-stone-100 shadow-sm">
                                <img src="<?= htmlspecialchars($item['image_url']?:'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=80&q=60') ?>" 
                                     class="w-16 h-16 rounded-xl object-cover border border-stone-200" alt="Item">
                                <div class="flex-1">
                                    <p class="font-bold text-amber-950 text-sm mb-1">
                                        <?= htmlspecialchars($item['product_name']) ?>
                                        <!-- Item Metadata Badges -->
                                        <?php if(!empty($item['product_flavor'])): ?>
                                            <span class="text-[10px] font-bold text-secondary bg-primary-container px-1.5 py-0.5 rounded ml-1"><?= htmlspecialchars($item['product_flavor']) ?></span>
                                        <?php endif; ?>
                                        <?php if($item['variant_weight']): ?>
                                            <span class="text-[10px] font-bold text-secondary bg-secondary-container px-1.5 py-0.5 rounded ml-1"><?= htmlspecialchars($item['variant_weight']) ?></span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-xs text-stone-500">Qty: <?= $item['quantity'] ?> × ₹<?= number_format($item['price'], 2) ?></p>
                                </div>
                                <div class="font-bold text-amber-950 bg-white px-3 py-2 rounded-xl border border-stone-200 shadow-sm">
                                    ₹<?= number_format($item['quantity'] * $item['price'], 2) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Cancel Action (Only visible if status is still 'pending') -->
                        <?php if ($normalizedStatus === 'pending'): ?>
                            <div class="mt-6 flex justify-end border-t border-rose-50 pt-4">
                                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this order? This cannot be undone.');">
                                    <input type="hidden" name="cancel_order_id" value="<?= $order['id'] ?>">
                                    <button type="submit" class="px-4 py-2 border border-red-300 text-red-600 rounded-lg text-sm font-bold hover:bg-red-50 transition-colors">
                                        Cancel Order
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- ═══ FOOTER ═══════════════════════════════════════════════════════════════ -->
<footer class="bg-stone-100 border-t border-stone-200 mt-auto">
    <div class="w-full py-12 px-8 flex flex-col md:flex-row justify-between items-center gap-8 font-serif text-xs uppercase tracking-widest">
        <div class="flex flex-col items-center md:items-start gap-4">
            <span class="text-lg font-bold text-amber-900">Ghanshyam Bakery &amp; Live Cake Shop</span>
            <p class="text-stone-500 normal-case tracking-normal text-sm max-w-xs text-center md:text-left">© 2024 Ghanshyam Bakery &amp; Live Cake Shop. Artisanal excellence in every bite.</p>
        </div>
        <div class="flex gap-4">
            <div class="w-10 h-10 rounded-full border border-stone-200 flex items-center justify-center text-amber-900 hover:bg-rose-50 transition-colors cursor-pointer">
                <span class="material-symbols-outlined text-sm">share</span>
            </div>
            <div class="w-10 h-10 rounded-full border border-stone-200 flex items-center justify-center text-amber-900 hover:bg-rose-50 transition-colors cursor-pointer">
                <span class="material-symbols-outlined text-sm">mail</span>
            </div>
        </div>
    </div>
</footer>
</body></html>
