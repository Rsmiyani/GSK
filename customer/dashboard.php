<?php
/**
 * customer/dashboard.php
 * ======================
 * CUSTOMER HOME DASHBOARD - Sweet Artisans Theme
 */

$required_role = 'customer';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$uid = $_SESSION['user_id'];

// Get active order (if any)
$activeOrder = null;
$res = mysqli_query($conn, "
    SELECT o.*, s.name as shop_name,
           (SELECT p.image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id LIMIT 1) as product_image
    FROM orders o 
    JOIN shops s ON o.shop_id = s.id 
    WHERE o.customer_id = $uid AND LOWER(o.status) IN ('pending', 'preparing', 'ready')
    ORDER BY o.created_at DESC LIMIT 1
");
if ($row = mysqli_fetch_assoc($res)) {
    $activeOrder = $row;
}

// Get nearby/active shops (up to 3)
$shops = [];
$res = mysqli_query($conn, "SELECT * FROM shops WHERE is_active=1 LIMIT 3");
while ($row = mysqli_fetch_assoc($res)) {
    $shops[] = $row;
}

// Get top 3 most sold products for "Recommended"
$products = [];
$res = mysqli_query($conn, "
    SELECT p.*, s.name as shop_name, COALESCE(SUM(oi.quantity), 0) as total_sold
    FROM products p 
    JOIN shops s ON p.shop_id = s.id 
    LEFT JOIN order_items oi ON p.id = oi.product_id
    WHERE p.is_available=1 AND s.is_active=1
    GROUP BY p.id
    ORDER BY total_sold DESC 
    LIMIT 3
");
while ($row = mysqli_fetch_assoc($res)) {
    $products[] = $row;
}

// Get user info
$userName = $_SESSION['user_name'];
$userInitial = strtoupper(substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Customer Dashboard - Ghanshyam Bakery & Live Cake Shop</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                    "surface-container-low": "#f5f3ee",
                    "on-secondary-fixed-variant": "#643c35",
                    "on-tertiary-fixed": "#400009",
                    "surface-dim": "#dbdad4",
                    "primary-fixed": "#fbdbde",
                    "tertiary": "#be0630",
                    "background": "#fbf9f3",
                    "primary-fixed-dim": "#debfc2",
                    "error-container": "#ffdad6",
                    "secondary": "#7f534b",
                    "surface": "#fbf9f3",
                    "primary": "#70585b",
                    "surface-container-high": "#eae8e2",
                    "surface-variant": "#e4e2dd",
                    "surface-container-highest": "#e4e2dd",
                    "inverse-surface": "#30312d",
                    "primary-container": "#fadadd",
                    "secondary-fixed": "#ffdad4",
                    "surface-bright": "#fbf9f3",
                    "outline": "#807475",
                    "surface-container": "#f0eee8",
                    "on-primary": "#ffffff",
                    "secondary-container": "#fec4ba",
                    "on-surface": "#1b1c19",
                    "on-secondary": "#ffffff",
                    "outline-variant": "#d2c3c4",
                    "on-tertiary": "#ffffff",
                    "on-background": "#1b1c19",
                    "surface-container-lowest": "#ffffff",
                    "tertiary-fixed-dim": "#ffb3b3",
                    "on-secondary-container": "#7a4f47",
                    "inverse-primary": "#debfc2",
                    "on-primary-container": "#765e61",
                    "surface-tint": "#70585b",
                    "tertiary-container": "#ffd9d8",
                    "on-primary-fixed": "#281719",
                    "on-surface-variant": "#4f4445",
                    "on-tertiary-container": "#c61235",
                    "on-error": "#ffffff",
                    "secondary-fixed-dim": "#f2b9af",
                    "error": "#ba1a1a",
                    "on-error-container": "#93000a",
                    "on-tertiary-fixed-variant": "#920022",
                    "on-primary-fixed-variant": "#574144",
                    "on-secondary-fixed": "#31120d",
                    "inverse-on-surface": "#f2f1eb",
                    "tertiary-fixed": "#ffdad9"
            },
            "borderRadius": { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
            "spacing": { "margin": "32px", "gutter": "24px", "unit": "8px", "container-max": "1440px" },
            "fontFamily": {
                    "headline-lg": ["Noto Serif"], "label-sm": ["Plus Jakarta Sans"], "body-md": ["Plus Jakarta Sans"],
                    "label-md": ["Plus Jakarta Sans"], "body-lg": ["Plus Jakarta Sans"], "headline-md": ["Noto Serif"], "headline-sm": ["Noto Serif"]
            },
            "fontSize": {
                    "headline-lg": ["36px", {"lineHeight": "1.2", "fontWeight": "700"}],
                    "label-sm": ["12px", {"lineHeight": "1", "letterSpacing": "0.05em", "fontWeight": "500"}],
                    "body-md": ["16px", {"lineHeight": "1.6", "fontWeight": "400"}],
                    "label-md": ["14px", {"lineHeight": "1", "letterSpacing": "0.02em", "fontWeight": "600"}],
                    "body-lg": ["18px", {"lineHeight": "1.6", "fontWeight": "400"}],
                    "headline-md": ["28px", {"lineHeight": "1.3", "fontWeight": "600"}],
                    "headline-sm": ["22px", {"lineHeight": "1.4", "fontWeight": "600"}]
            }
          },
        },
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

<!-- TopNavBar -->
<header class="bg-stone-50 border-b border-rose-100 shadow-sm shadow-amber-900/5 sticky top-0 z-50">
    <nav class="flex justify-between items-center w-full px-8 py-4 font-serif text-base tracking-tight">
        <div class="flex items-center gap-6">
            <img src="../assets/logo/image.png" alt="Ghanshyam Bakery Logo" class="w-10 h-10 object-cover rounded-md"/>
            <div>
                <div class="text-2xl font-bold text-amber-900">Ghanshyam Bakery &amp; Live Cake Shop</div>
                <div class="text-sm text-stone-500">Bringing your nearest cake shop just a click away.</div>
            </div>
        </div>
            <div class="flex items-center gap-8">
            <div class="hidden md:flex gap-6">
                <a class="text-amber-900 border-b-2 border-amber-900 font-bold pb-1 duration-300 ease-in-out" href="dashboard.php">Home</a>
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

<div class="flex w-full">
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
        <a class="bg-rose-50 text-amber-900 font-semibold rounded-lg px-4 py-3 flex items-center gap-3 scale-[0.98] active:scale-95 duration-200" href="dashboard.php">
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
    <main class="flex-1 p-margin overflow-x-hidden">
        
        <!-- Live Order Status Widget -->
        <?php if ($activeOrder): ?>
        <section class="mb-12">
            <div class="bg-white rounded-xl p-8 border border-rose-100 ambient-shadow relative overflow-hidden flex flex-col md:flex-row items-center gap-8">
                <div class="absolute top-0 right-0 w-32 h-32 bg-secondary-container/20 rounded-bl-full"></div>
                <div class="relative z-10 w-full md:w-1/3">
                    <div class="aspect-square rounded-lg bg-surface-container-low flex items-center justify-center p-4">
                        <img class="w-full h-full object-cover rounded-md shadow-md" src="<?= htmlspecialchars($activeOrder['product_image'] ?: 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=400&q=80') ?>" alt="Cake">
                    </div>
                </div>
                <div class="flex-1 space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1 rounded-full bg-primary-container text-on-primary-container text-label-sm uppercase">Live Order Status</span>
                        <span class="text-secondary font-semibold flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span>
                            <?= ucfirst($activeOrder['status']) ?>
                        </span>
                    </div>
                    <h2 class="font-headline-md text-on-surface">Your order from <?= htmlspecialchars($activeOrder['shop_name']) ?> is <?= $activeOrder['status'] ?></h2>
                    
                    <?php 
                        $statusMap = ['pending'=>25, 'preparing'=>50, 'ready'=>75, 'completed'=>100];
                        $progress = $statusMap[trim(strtolower($activeOrder['status']))] ?? 0;
                    ?>
                    <div class="w-full h-3 bg-surface-container-high rounded-full overflow-hidden">
                        <div class="h-full bg-secondary transition-all duration-1000" style="width: <?= $progress ?>%"></div>
                    </div>
                    <div class="grid grid-cols-4 text-label-sm text-stone-500 font-medium text-center -mx-4">
                        <span class="<?= $progress>=25 ? 'text-secondary font-bold' : '' ?>">Pending</span>
                        <span class="<?= $progress>=50 ? 'text-secondary font-bold' : '' ?>">Preparing</span>
                        <span class="<?= $progress>=75 ? 'text-secondary font-bold' : '' ?>">Ready</span>
                        <span class="<?= $progress>=100 ? 'text-secondary font-bold' : '' ?>">Completed</span>
                    </div>
                    <p class="text-body-md text-on-surface-variant">Order #<?= str_pad($activeOrder['id'], 4, '0', STR_PAD_LEFT) ?> • Total: ₹<?= number_format($activeOrder['total_amount'], 2) ?></p>
                    <a href="my_orders.php" class="mt-2 inline-block px-4 py-2 bg-secondary text-white rounded-lg font-label-md hover:bg-on-secondary-fixed-variant transition-colors">View Details</a>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Nearby Branches Carousel -->
        <section class="mb-12">
            <div class="flex justify-between items-end mb-6">
                <div>
                    <h2 class="font-headline-sm text-on-surface">Nearby Branches</h2>
                    <p class="text-body-md text-on-surface-variant">Pick up your fresh delights from these locations</p>
                </div>
                <a href="shops.php" class="text-secondary font-label-md flex items-center gap-1 hover:underline">
                    View All Branches <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>
            <div class="flex gap-gutter overflow-x-auto hide-scrollbar pb-4 -mx-4 px-4">
                <?php foreach($shops as $shop): ?>
                <div class="min-w-[320px] bg-white rounded-xl p-6 border border-rose-50 ambient-shadow flex flex-col gap-4">
                    <div class="flex justify-between items-start">
                        <div class="bg-primary-container p-3 rounded-lg">
                            <span class="material-symbols-outlined text-on-primary-container">storefront</span>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-headline-sm text-lg text-amber-950"><?= htmlspecialchars($shop['name']) ?></h3>
                        <p class="text-label-sm text-stone-500 truncate" title="<?= htmlspecialchars($shop['address']) ?>"><?= htmlspecialchars($shop['address']) ?></p>
                    </div>
                    <div class="flex items-center gap-2 text-label-sm">
                        <span class="text-green-600 font-bold">Open</span>
                    </div>
                    <a href="shop_detail.php?id=<?= $shop['id'] ?>" class="text-center mt-2 w-full py-2 bg-secondary text-white rounded-lg font-label-md hover:bg-on-secondary-fixed-variant transition-colors">Visit Shop</a>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Recommended Section (Bento Style) -->
        <section>
            <div class="mb-6">
                <h2 class="font-headline-sm text-on-surface">Recommended for You</h2>
                <p class="text-body-md text-on-surface-variant">Based on our freshest creations</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $p): ?>
                    <?php
                        $description = trim((string)($p['description'] ?? ''));
                        $shortDescription = $description !== '' ? (mb_strlen($description) > 86 ? mb_substr($description, 0, 86) . '...' : $description) : 'Freshly prepared and ready to order.';
                        $categoryLabel = trim((string)($p['category_name'] ?? ''));
                        $shopLabel = trim((string)($p['shop_name'] ?? ''));
                        $metaLabel = $categoryLabel !== '' ? $categoryLabel : $shopLabel;
                    ?>
                    <article class="bg-white rounded-2xl overflow-hidden border border-rose-50 ambient-shadow group flex flex-col h-full">
                        <div class="relative aspect-[4/3] overflow-hidden bg-stone-100">
                            <img class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                 src="<?= htmlspecialchars($p['image_url'] ?: 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=600&q=80') ?>"
                                 alt="<?= htmlspecialchars($p['name']) ?>"
                                 onerror="this.src='https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=600&q=80'"/>
                            <?php if ($metaLabel !== ''): ?>
                                <div class="absolute top-4 left-4">
                                    <span class="inline-flex items-center rounded-full bg-white/90 px-3 py-1 text-[11px] font-semibold text-amber-900 shadow-sm backdrop-blur">
                                        <?= htmlspecialchars($metaLabel) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-5 flex flex-col gap-4 flex-1">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <h3 class="font-headline-sm text-xl text-amber-950 truncate"><?= htmlspecialchars($p['name']) ?></h3>
                                    <?php if (!empty($p['flavor'])): ?><p class="text-xs font-semibold text-secondary mt-1 truncate">Flavor: <?= htmlspecialchars($p['flavor']) ?></p><?php endif; ?>
                                    <p class="text-sm text-stone-500 mt-1 truncate"><?= htmlspecialchars($shopLabel) ?></p>
                                </div>
                                <span class="shrink-0 font-headline-sm text-xl text-secondary">
                                    <?php if ($p['has_variants']): ?>
                                        <span class="text-xs text-stone-500 font-sans block text-right">Starts at</span>
                                    <?php endif; ?>
                                    ₹<?= number_format($p['price'], 2) ?>
                                </span>
                            </div>
                            <p class="text-sm leading-6 text-stone-600 flex-1"><?= htmlspecialchars($shortDescription) ?></p>
                             <div class="flex items-center gap-3 pt-1 mt-auto">
                                <?php if ($p['has_variants']): ?>
                                    <a href="shop_detail.php?id=<?= $p['shop_id'] ?>#product_<?= $p['id'] ?>" class="flex-1 inline-flex items-center justify-center gap-2 bg-secondary text-white py-3 rounded-xl font-label-md hover:bg-on-secondary-fixed-variant transition-colors shadow-md shadow-secondary/15">
                                        <span class="material-symbols-outlined text-[18px]">weight</span>
                                        Select Weight
                                    </a>
                                <?php else: ?>
                                    <button type="button" onclick="addProductToCart(this, <?= (int)$p['id'] ?>, <?= (int)$p['shop_id'] ?>)" class="flex-1 inline-flex items-center justify-center gap-2 bg-secondary text-white py-3 rounded-xl font-label-md hover:bg-on-secondary-fixed-variant transition-colors shadow-md shadow-secondary/15">
                                        <span class="material-symbols-outlined text-[18px]">add_shopping_cart</span>
                                        Add to Cart
                                    </button>
                                <?php endif; ?>
                                <a href="shop_detail.php?id=<?= $p['shop_id'] ?>" class="w-11 h-11 rounded-xl border border-secondary text-secondary hover:bg-rose-50 transition-colors flex items-center justify-center" title="View shop">
                                    <span class="material-symbols-outlined text-[18px]">storefront</span>
                                </a>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<!-- Footer -->
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
<script>
function addProductToCart(button, productId, shopId) {
    const originalHtml = button.innerHTML;
    button.disabled = true;
    button.classList.add('opacity-75');
    button.innerHTML = '<span class="material-symbols-outlined text-[18px] animate-spin">refresh</span> Adding...';

    fetch('add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ product_id: productId, shop_id: shopId, quantity: 1 })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.innerHTML = '<span class="material-symbols-outlined text-[18px]">check</span> Added';
            setTimeout(() => {
                button.innerHTML = originalHtml;
                button.disabled = false;
                button.classList.remove('opacity-75');
            }, 1400);
        } else {
            button.innerHTML = originalHtml;
            button.disabled = false;
            button.classList.remove('opacity-75');
            alert(data.message || 'Unable to add item to cart.');
        }
    })
    .catch(() => {
        button.innerHTML = originalHtml;
        button.disabled = false;
        button.classList.remove('opacity-75');
        alert('Unable to add item to cart.');
    });
}
</script>
</body></html>
