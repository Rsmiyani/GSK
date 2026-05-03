<?php
/**
 * customer/order_confirm.php
 * ==========================
 * ORDER CONFIRMATION PAGE - Sweet Artisans Theme
 */

$required_role = 'customer';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userInitial = strtoupper(substr($userName, 0, 1));

$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId === 0) { header("Location: dashboard.php"); exit(); }

$orderRes = mysqli_query($conn,
    "SELECT o.*, s.name AS shop_name, s.address AS shop_address, s.phone AS shop_phone
     FROM orders o JOIN shops s ON o.shop_id = s.id
     WHERE o.id = $orderId AND o.customer_id = $userId"
);
$order = mysqli_fetch_assoc($orderRes);

if (!$order) { header("Location: dashboard.php"); exit(); }

$itemsRes = mysqli_query($conn,
    "SELECT oi.*, p.name AS product_name, p.flavor AS product_flavor 
     FROM order_items oi 
     JOIN products p ON oi.product_id = p.id 
     WHERE oi.order_id = $orderId"
);
$items = [];
while ($row = mysqli_fetch_assoc($itemsRes)) { $items[] = $row; }
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Order Confirmed - Sweet Artisans</title>
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
<body class="bg-surface text-on-surface font-body-md antialiased min-h-screen flex flex-col">

<!-- TopNavBar -->
<header class="bg-stone-50 border-b border-rose-100 shadow-sm shadow-amber-900/5 sticky top-0 z-50">
    <nav class="flex justify-between items-center w-full px-8 py-4 max-w-screen-2xl mx-auto font-serif text-base tracking-tight">
        <div class="flex items-center gap-12">
            <span class="text-2xl font-bold text-amber-900">Sweet Artisans</span>
            <div class="hidden lg:flex items-center bg-stone-100 rounded-full px-4 py-2 w-96 border border-rose-50">
                <span class="material-symbols-outlined text-stone-400 mr-2">search</span>
                <input class="bg-transparent border-none focus:ring-0 text-sm w-full placeholder-stone-400" placeholder="Search for artisanal cakes..." type="text"/>
            </div>
        </div>
        <div class="flex items-center gap-8">
            <div class="hidden md:flex gap-6">
                <a class="text-stone-500 font-medium hover:text-amber-700 transition-colors duration-300 ease-in-out" href="dashboard.php">Home</a>
                <a class="text-stone-500 font-medium hover:text-amber-700 transition-colors duration-300 ease-in-out" href="shops.php">Shop</a>
                <a class="text-stone-500 font-medium hover:text-amber-700 transition-colors duration-300 ease-in-out" href="cart.php">Cart</a>
                <a class="text-stone-500 font-medium hover:text-amber-700 transition-colors duration-300 ease-in-out" href="my_orders.php">Orders</a>
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

<div class="flex max-w-screen-2xl mx-auto w-full flex-1">
    <!-- SideNavBar -->
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
        <a class="text-stone-500 px-4 py-3 flex items-center gap-3 hover:bg-rose-50/50 rounded-lg transition-all" href="my_orders.php">
            <span class="material-symbols-outlined">receipt_long</span> My Orders
        </a>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8 overflow-x-hidden flex items-center justify-center">
        
        <div class="w-full max-w-2xl bg-white rounded-2xl p-8 md:p-12 border border-rose-50 ambient-shadow text-center">
            
            <div class="w-24 h-24 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <span class="material-symbols-outlined text-[56px]">check_circle</span>
            </div>
            
            <h1 class="font-headline-lg text-3xl text-amber-950 mb-2">Order Confirmed!</h1>
            <p class="text-body-md text-stone-500 mb-8">Your artisanal treats are being prepared with love.</p>
            
            <div class="bg-surface-container-low rounded-xl p-6 border border-rose-100 mb-8 text-left relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4">
                    <span class="px-3 py-1 rounded-full bg-primary-container text-on-primary-container text-[10px] uppercase font-bold tracking-wider">
                        <?= $order['order_type'] === 'delivery' ? '🚚 Delivery' : '🏪 Pickup' ?>
                    </span>
                </div>
                
                <p class="text-xs text-stone-500 font-bold tracking-wider uppercase mb-1">Order Number</p>
                <p class="font-headline-sm text-2xl text-secondary mb-6">#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs text-stone-500 font-bold tracking-wider uppercase mb-2">Shop Details</p>
                        <p class="font-bold text-amber-950"><?= htmlspecialchars($order['shop_name']) ?></p>
                        <p class="text-sm text-stone-600"><?= htmlspecialchars($order['shop_address']) ?></p>
                    </div>
                    
                    <div>
                        <p class="text-xs text-stone-500 font-bold tracking-wider uppercase mb-2">
                            <?= $order['order_type'] === 'delivery' ? 'Delivery To' : 'Pickup Schedule' ?>
                        </p>
                        <?php if ($order['order_type'] === 'delivery'): ?>
                            <p class="text-sm text-stone-600"><?= htmlspecialchars($order['delivery_address']) ?></p>
                        <?php else: ?>
                            <p class="font-bold text-amber-950"><?= date('d M Y', strtotime($order['pickup_date'])) ?></p>
                            <p class="text-sm text-stone-600">at <?= date('h:i A', strtotime($order['pickup_time'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t border-rose-100">
                    <p class="text-xs text-stone-500 font-bold tracking-wider uppercase mb-3">Order Items</p>
                    <div class="space-y-2">
                        <?php foreach ($items as $item): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-stone-600">
                                <span class="font-bold text-amber-950"><?= $item['quantity'] ?>x</span> 
                                <?= htmlspecialchars($item['product_name']) ?>
                                <?php if(!empty($item['product_flavor'])): ?>
                                    <span class="text-[10px] font-bold text-secondary bg-primary-container px-1.5 py-0.5 rounded ml-1"><?= htmlspecialchars($item['product_flavor']) ?></span>
                                <?php endif; ?>
                                <?php if($item['variant_weight']): ?>
                                    <span class="text-[10px] font-bold text-secondary bg-secondary-container px-1.5 py-0.5 rounded ml-1"><?= htmlspecialchars($item['variant_weight']) ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="font-medium text-amber-950">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="flex justify-between items-center mt-4 pt-4 border-t border-rose-100">
                        <span class="font-bold text-amber-950">Total Paid</span>
                        <span class="font-headline-sm text-xl text-secondary">₹<?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="my_orders.php" class="px-6 py-3 bg-secondary text-white rounded-lg font-label-md hover:bg-on-secondary-fixed-variant transition-colors shadow-sm">
                    Track Order Status
                </a>
                <a href="dashboard.php" class="px-6 py-3 border border-secondary text-secondary rounded-lg font-label-md hover:bg-rose-50 transition-colors">
                    Back to Home
                </a>
            </div>
            
        </div>
        
    </main>
</div>

<!-- Footer -->
<footer class="bg-stone-100 border-t border-stone-200 mt-auto">
    <div class="max-w-screen-2xl mx-auto w-full py-12 px-8 flex flex-col md:flex-row justify-between items-center gap-8 font-serif text-xs uppercase tracking-widest">
        <div class="flex flex-col items-center md:items-start gap-4">
            <span class="text-lg font-bold text-amber-900">Sweet Artisans</span>
            <p class="text-stone-500 normal-case tracking-normal text-sm max-w-xs text-center md:text-left">© 2024 Sweet Artisans Cake Studio. Artisanal excellence in every bite.</p>
        </div>
    </div>
</footer>

</body>
</html>
