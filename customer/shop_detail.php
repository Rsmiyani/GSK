<?php
/**
 * customer/shop_detail.php
 * ========================
 * SHOP DETAIL / CAKE CATALOG PAGE - Sweet Artisans Theme
 */

$required_role = 'customer';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userInitial = strtoupper(substr($userName, 0, 1));

// ─── Get Shop ID from URL ─────────────────────────────────────────────────────
$shopId = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : 0;
if ($shopId === 0) {
    // some links use id instead of shop_id
    $shopId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
}

if ($shopId === 0) {
    header("Location: shops.php");
    exit();
}

$shopStmt = mysqli_prepare($conn, "SELECT * FROM shops WHERE id = ? AND is_active = 1");
mysqli_stmt_bind_param($shopStmt, 'i', $shopId);
mysqli_stmt_execute($shopStmt);
$shopResult = mysqli_stmt_get_result($shopStmt);
$shop = mysqli_fetch_assoc($shopResult);

if (!$shop) {
    header("Location: shops.php?error=Shop+not+found");
    exit();
}

$productsResult = mysqli_query($conn,
    "SELECT p.*, c.name AS category_name 
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id
     WHERE p.shop_id = $shopId AND p.is_available = 1 
     ORDER BY c.name ASC, p.name ASC"
);

$products = [];
$categories = [];
$productIdsWithVariants = [];

while ($row = mysqli_fetch_assoc($productsResult)) {
    $cat = $row['category_name'] ?? 'Uncategorized';
    if (!in_array($cat, $categories)) {
        $categories[] = $cat;
    }
    $row['category_name'] = $cat;
    $row['variants'] = [];
    if ($row['has_variants']) {
        $productIdsWithVariants[] = $row['id'];
    }
    $products[$row['id']] = $row;
}

if (!empty($productIdsWithVariants)) {
    $idList = implode(',', $productIdsWithVariants);
    $varRes = mysqli_query($conn, "SELECT * FROM product_variants WHERE product_id IN ($idList) ORDER BY CASE weight_label WHEN '500g' THEN 1 WHEN '1kg' THEN 2 WHEN '2kg' THEN 3 WHEN '3kg' THEN 4 WHEN '4kg' THEN 5 WHEN '5kg' THEN 6 WHEN '6kg' THEN 7 ELSE 8 END");
    while ($v = mysqli_fetch_assoc($varRes)) {
        $products[$v['product_id']]['variants'][] = $v;
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= htmlspecialchars($shop['name']) ?> - Ghanshyam Bakery &amp; Live Cake Shop</title>
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
        
        /* Toast notification */
        .toast {
            position: fixed; bottom: 30px; right: 30px;
            background: var(--color-on-primary-container, #765e61); color: white;
            padding: 14px 22px; border-radius: 10px;
            font-weight: 600; font-size: 0.9rem;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            transform: translateX(200%);
            transition: transform 0.4s ease;
            z-index: 999;
        }
        .toast.show { transform: translateX(0); }
        .btn-cart.loading { opacity: 0.7; pointer-events: none; }
    </style>
</head>
<body class="bg-surface text-on-surface font-body-md antialiased min-h-screen flex flex-col">

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
                <a class="text-stone-500 font-medium hover:text-amber-700 transition-colors duration-300 ease-in-out" href="dashboard.php">Home</a>
                <a class="text-amber-900 border-b-2 border-amber-900 font-bold pb-1 duration-300 ease-in-out" href="shops.php">Shop</a>
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
        <a class="text-stone-500 px-4 py-3 flex items-center gap-3 hover:bg-rose-50/50 rounded-lg transition-all" href="dashboard.php">
            <span class="material-symbols-outlined">home</span> Home
        </a>
        <a class="bg-rose-50 text-amber-900 font-semibold rounded-lg px-4 py-3 flex items-center gap-3 scale-[0.98] active:scale-95 duration-200" href="shops.php">
            <span class="material-symbols-outlined">storefront</span> Browse Shops
        </a>
        <a class="text-stone-500 px-4 py-3 flex items-center gap-3 hover:bg-rose-50/50 rounded-lg transition-all" href="my_orders.php">
            <span class="material-symbols-outlined">receipt_long</span> My Orders
        </a>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8 overflow-x-hidden">
        
        <div class="mb-8">
            <div class="flex items-center gap-2 text-sm text-stone-500 mb-4">
                <a href="dashboard.php" class="hover:text-secondary transition-colors">Home</a>
                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                <a href="shops.php" class="hover:text-secondary transition-colors">Shops</a>
                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                <span class="text-amber-950 font-medium"><?= htmlspecialchars($shop['name']) ?></span>
            </div>
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 bg-white p-6 rounded-xl border border-rose-50 ambient-shadow mb-8">
                <div>
                    <h1 class="font-headline-lg text-amber-950 mb-1">🏪 <?= htmlspecialchars($shop['name']) ?></h1>
                    <p class="text-body-md text-stone-500 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[18px]">location_on</span> <?= htmlspecialchars($shop['address']) ?>
                    </p>
                </div>
                <a href="cart.php" class="bg-secondary text-white px-5 py-2 rounded-lg font-label-md hover:bg-on-secondary-fixed-variant transition-colors shadow-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">shopping_cart</span> View Cart
                </a>
            </div>
            
            <!-- Category Filters -->
            <?php if (count($categories) > 0): ?>
            <div class="flex gap-3 mb-8 overflow-x-auto hide-scrollbar pb-2">
                <button class="cat-tab px-5 py-2 rounded-full font-label-md transition-colors bg-secondary text-white shadow-sm" data-cat="all" onclick="filterCategory('all', this)">All Treats</button>
                <?php foreach ($categories as $cat): ?>
                    <button class="cat-tab px-5 py-2 rounded-full font-label-md transition-colors bg-white text-stone-600 border border-stone-200 hover:bg-rose-50" data-cat="<?= htmlspecialchars($cat) ?>" onclick="filterCategory('<?= htmlspecialchars($cat) ?>', this)">
                        <?= htmlspecialchars($cat) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="productGrid">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $product): ?>
                <!-- Product Card -->
                <div id="product_<?= $product['id'] ?>" class="product-card bg-white rounded-xl overflow-hidden border border-rose-50 ambient-shadow group flex flex-col" data-category="<?= htmlspecialchars($product['category_name']) ?>">
                    <div class="aspect-video bg-rose-50 flex items-center justify-center relative overflow-hidden">
                        <img class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" 
                             src="<?= htmlspecialchars($product['image_url'] ?: 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=500&q=80') ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             onerror="this.src='https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=500&q=80'">
                        <div class="absolute top-3 left-3">
                            <span class="bg-primary-container text-on-primary-container px-3 py-1 rounded-full text-[10px] uppercase tracking-wider font-bold shadow-sm">
                                <?= htmlspecialchars($product['category_name']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="p-5 flex-1 flex flex-col">
                        <h3 class="font-headline-sm text-lg text-amber-950 mb-2"><?= htmlspecialchars($product['name']) ?></h3>
                        <p class="text-sm text-stone-500 mb-6 flex-1"><?= htmlspecialchars($product['description']) ?></p>
                        
                        <div class="flex flex-col mt-auto gap-4">
                            <?php if ($product['has_variants'] && !empty($product['variants'])): ?>
                                <select id="variant_select_<?= $product['id'] ?>" class="w-full text-sm border-stone-200 rounded-lg focus:ring-secondary focus:border-secondary text-stone-600" onchange="updatePrice(<?= $product['id'] ?>)">
                                    <?php foreach ($product['variants'] as $i => $v): ?>
                                        <option value="<?= htmlspecialchars($v['weight_label']) ?>" data-price="<?= $v['price'] ?>">
                                            <?= htmlspecialchars($v['weight_label']) ?> - ₹<?= number_format($v['price'], 2) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                            
                            <div class="flex justify-between items-center">
                                <?php if ($product['has_variants'] && !empty($product['variants'])): ?>
                                    <span class="font-headline-sm text-xl text-secondary" id="price_display_<?= $product['id'] ?>">₹<?= number_format($product['variants'][0]['price'], 2) ?></span>
                                <?php else: ?>
                                    <span class="font-headline-sm text-xl text-secondary">₹<?= number_format($product['price'], 2) ?></span>
                                <?php endif; ?>
                                
                                <button class="btn-cart flex items-center justify-center gap-1 bg-surface-container hover:bg-secondary hover:text-white text-secondary px-4 py-2 rounded-lg font-label-md transition-colors" onclick="addToCart(<?= $product['id'] ?>, <?= $shopId ?>, this, <?= $product['has_variants'] ? 'true' : 'false' ?>)">
                                    <span class="material-symbols-outlined text-[18px]">add_shopping_cart</span> Add
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full bg-white rounded-xl py-16 px-8 text-center border border-rose-50 ambient-shadow">
                    <span class="material-symbols-outlined text-4xl text-stone-300 mb-4">cake</span>
                    <h3 class="font-headline-sm text-amber-950 mb-2">No items available right now</h3>
                    <p class="text-stone-500">The shopkeeper hasn't added any products yet. Check back soon!</p>
                </div>
            <?php endif; ?>
        </div>
        
    </main>
</div>

<!-- Toast notification popup -->
<div class="toast" id="toast">✅ Added to cart!</div>

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
function updatePrice(productId) {
    const select = document.getElementById('variant_select_' + productId);
    if (!select) return;
    const option = select.options[select.selectedIndex];
    const price = parseFloat(option.getAttribute('data-price'));
    const display = document.getElementById('price_display_' + productId);
    if (display) {
        display.innerText = '₹' + price.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
}

function addToCart(productId, shopId, button, hasVariants) {
    const originalText = button.innerHTML;
    button.classList.add('loading');
    button.innerHTML = '<span class="material-symbols-outlined text-[18px] animate-spin">refresh</span> Adding...';

    let bodyData = `product_id=${productId}&shop_id=${shopId}`;
    if (hasVariants) {
        const select = document.getElementById('variant_select_' + productId);
        if (select) {
            bodyData += `&variant_weight=${encodeURIComponent(select.value)}`;
        }
    }

    fetch('add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: bodyData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('✅ ' + data.message, 'success');
            button.innerHTML = '<span class="material-symbols-outlined text-[18px]">check</span> Added';
            button.classList.replace('bg-surface-container', 'bg-green-100');
            button.classList.replace('text-secondary', 'text-green-700');
            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.replace('bg-green-100', 'bg-surface-container');
                button.classList.replace('text-green-700', 'text-secondary');
                button.classList.remove('loading');
            }, 2000);
        } else {
            showToast('❌ ' + data.message, 'error');
            button.innerHTML = originalText;
            button.classList.remove('loading');
        }
    })
    .catch(() => {
        showToast('❌ Something went wrong.', 'error');
        button.innerHTML = originalText;
        button.classList.remove('loading');
    });
}

function showToast(msg, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.style.background = type === 'error' ? '#ba1a1a' : '#70585b';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

function filterCategory(categoryName, btn) {
    document.querySelectorAll('.cat-tab').forEach(t => {
        t.className = 'cat-tab px-5 py-2 rounded-full font-label-md transition-colors bg-white text-stone-600 border border-stone-200 hover:bg-rose-50';
    });
    btn.className = 'cat-tab px-5 py-2 rounded-full font-label-md transition-colors bg-secondary text-white shadow-sm';

    const cards = document.querySelectorAll('.product-card');
    cards.forEach(card => {
        if (categoryName === 'all' || card.getAttribute('data-category') === categoryName) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
</body>
</html>
