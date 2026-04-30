<?php
/**
 * customer/shop_detail.php
 * ========================
 * SHOP DETAIL / CAKE CATALOG PAGE
 *
 * Shows all cakes available at a specific shop.
 * Customers can add items to their cart from here.
 *
 * URL: shop_detail.php?shop_id=1
 */

$required_role = 'customer';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

// ─── Get Shop ID from URL ─────────────────────────────────────────────────────
// ?shop_id=1 is passed in the URL from the shops listing page
$shopId = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : 0;

if ($shopId === 0) {
    header("Location: shops.php");
    exit();
}

// ─── Fetch Shop Details ───────────────────────────────────────────────────────
$shopStmt = mysqli_prepare($conn, "SELECT * FROM shops WHERE id = ? AND is_active = 1");
mysqli_stmt_bind_param($shopStmt, 'i', $shopId);
mysqli_stmt_execute($shopStmt);
$shopResult = mysqli_stmt_get_result($shopStmt);
$shop = mysqli_fetch_assoc($shopResult);

// If shop not found, redirect back
if (!$shop) {
    header("Location: shops.php?error=Shop+not+found");
    exit();
}

// ─── Fetch Products (Cakes) for This Shop ────────────────────────────────────
$productsResult = mysqli_query($conn,
    "SELECT p.*, c.name AS category_name 
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id
     WHERE p.shop_id = $shopId AND p.is_available = 1 
     ORDER BY c.name ASC, p.name ASC"
);

$products = [];
$categories = [];
while ($row = mysqli_fetch_assoc($productsResult)) {
    $cat = $row['category_name'] ?? 'Uncategorized';
    if (!in_array($cat, $categories)) {
        $categories[] = $cat;
    }
    $row['category_name'] = $cat;
    $products[] = $row;
}

// ─── Success/Error Messages ───────────────────────────────────────────────────
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($shop['name']) ?> - Ghanshyam Bakery</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        /* Toast notification (appears after adding to cart) */
        .toast {
            position: fixed; bottom: 30px; right: 30px;
            background: #10b981; color: white;
            padding: 14px 22px; border-radius: 10px;
            font-weight: 600; font-size: 0.9rem;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            transform: translateX(200%);
            transition: transform 0.4s ease;
            z-index: 999;
        }
        .toast.show { transform: translateX(0); }
        /* Add to cart button loading state */
        .btn-cart.loading { opacity: 0.7; pointer-events: none; }
        
        /* Category Filter Styles */
        .category-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            overflow-x: auto;
            padding-bottom: 8px;
        }
        .cat-tab {
            padding: 8px 16px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text);
            transition: all 0.2s;
            white-space: nowrap;
        }
        .cat-tab:hover { background: #f8f9fa; }
        .cat-tab.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }
        /* Product category badge */
        .product-cat-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0,0,0,0.6);
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .product-card { position: relative; }
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
        <a href="shops.php" class="active"><span class="nav-icon">📍</span> Find Shops</a>
        <a href="cart.php"><span class="nav-icon">🛒</span> My Cart</a>
        <a href="my_orders.php"><span class="nav-icon">📦</span> My Orders</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../logout.php"><span>🚪</span> Logout</a>
    </div>
</aside>

<div class="main-content">
    <div class="topbar">
        <div class="topbar-title">
            <h1>🏪 <?= htmlspecialchars($shop['name']) ?></h1>
            <p>📍 <?= htmlspecialchars($shop['address']) ?></p>
        </div>
        <div class="topbar-user">
            <div class="user-info">
                <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                <span><?= $_SESSION['role'] ?></span>
            </div>
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
        </div>
    </div>

    <div class="page-body">

        <!-- Breadcrumb navigation: Home > Shops > This Shop -->
        <div style="font-size:0.85rem;color:var(--text-muted);margin-bottom:20px;">
            <a href="dashboard.php" style="color:var(--accent);text-decoration:none;">Dashboard</a> →
            <a href="shops.php"     style="color:var(--accent);text-decoration:none;">Shops</a> →
            <?= htmlspecialchars($shop['name']) ?>
        </div>

        <!-- Success message after adding to cart -->
        <?php if ($msg === 'added'): ?>
        <div class="alert alert-success">✅ Item added to your cart!</div>
        <?php endif; ?>

        <!-- View Cart shortcut button -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
            <h2 style="font-size:1.1rem;font-weight:700;">🍰 Our Menu</h2>
            <a href="cart.php" class="btn btn-primary">🛒 View Cart</a>
        </div>

        <!-- Category Filters -->
        <?php if (count($categories) > 0): ?>
        <div class="category-tabs" id="categoryTabs">
            <button class="cat-tab active" data-cat="all" onclick="filterCategory('all', this)">All</button>
            <?php foreach ($categories as $cat): ?>
                <button class="cat-tab" data-cat="<?= htmlspecialchars($cat) ?>" onclick="filterCategory('<?= htmlspecialchars($cat) ?>', this)">
                    <?= htmlspecialchars($cat) ?>
                </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ─── Cake Product Grid ──────────────────────────────────────────── -->
        <?php if (count($products) > 0): ?>
        <div class="products-grid" id="productGrid">
            <?php foreach ($products as $product): ?>
            <div class="product-card" data-category="<?= htmlspecialchars($product['category_name']) ?>">
                <!-- Category Badge -->
                <span class="product-cat-badge"><?= htmlspecialchars($product['category_name']) ?></span>
                <!-- Cake Image -->
                <img
                    src="<?= htmlspecialchars($product['image_url'] ?: 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=500&q=80') ?>"
                    alt="<?= htmlspecialchars($product['name']) ?>"
                    onerror="this.src='https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=500&q=80'"
                >
                <div class="product-card-body">
                    <!-- Cake Name -->
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <!-- Description -->
                    <p><?= htmlspecialchars($product['description']) ?></p>
                </div>
                <div class="product-card-footer">
                    <!-- Price -->
                    <span class="product-price">₹<?= number_format($product['price'], 2) ?></span>
                    <!-- Add to Cart button — sends product + shop ID to add_to_cart.php -->
                    <button
                        class="btn btn-primary btn-sm btn-cart"
                        onclick="addToCart(<?= $product['id'] ?>, <?= $shopId ?>, this)"
                    >
                        + Add to Cart
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">🎂</div>
            <h3>No items available right now</h3>
            <p>The shopkeeper hasn't added any products yet. Check back soon!</p>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- Toast notification popup (shows after adding to cart) -->
<div class="toast" id="toast">✅ Added to cart!</div>

<script>
/**
 * addToCart(productId, shopId, button)
 * =====================================
 * Sends an AJAX (background) request to add_to_cart.php
 * without reloading the whole page.
 *
 * AJAX = Asynchronous JavaScript And XML
 * It lets us talk to PHP in the background silently.
 */
function addToCart(productId, shopId, button) {
    // Disable button to prevent double-clicking
    button.classList.add('loading');
    button.textContent = '⏳ Adding...';

    // fetch() sends a request to add_to_cart.php in the background
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `product_id=${productId}&shop_id=${shopId}`
    })
    .then(response => response.json()) // Parse the JSON response from PHP
    .then(data => {
        if (data.success) {
            // Show the green toast notification
            showToast('✅ ' + data.message);
            button.textContent = '✅ Added!';
            setTimeout(() => {
                button.textContent = '+ Add to Cart';
                button.classList.remove('loading');
            }, 2000);
        } else {
            showToast('❌ ' + data.message, 'error');
            button.textContent = '+ Add to Cart';
            button.classList.remove('loading');
        }
    })
    .catch(() => {
        showToast('❌ Something went wrong.');
        button.textContent = '+ Add to Cart';
        button.classList.remove('loading');
    });
}

// Shows the toast notification for 3 seconds
function showToast(msg, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.style.background = type === 'error' ? '#ef4444' : '#10b981';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

/**
 * filterCategory(categoryName, tabElement)
 * ========================================
 * Filters the product grid to show only items matching the selected category.
 */
function filterCategory(categoryName, btn) {
    // Format UI buttons
    document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');

    // Filter items
    const cards = document.querySelectorAll('.product-card');
    cards.forEach(card => {
        if (categoryName === 'all' || card.getAttribute('data-category') === categoryName) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
</body>
</html>
