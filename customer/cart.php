<?php
/**
 * customer/cart.php
 * =================
 * SHOPPING CART PAGE - Sweet Artisans Theme
 */

$required_role = 'customer';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userInitial = strtoupper(substr($userName, 0, 1));

// ─── Handle Cart Actions (POST) ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action']     ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);
    $variantWt = trim($_POST['variant_weight'] ?? '');

    if ($action === 'remove' && $productId) {
        $s = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id=? AND product_id=? AND variant_weight=?");
        mysqli_stmt_bind_param($s,'iis',$userId,$productId,$variantWt);
        mysqli_stmt_execute($s);
    } elseif ($action === 'increase' && $productId) {
        $s = mysqli_prepare($conn, "UPDATE cart SET quantity=quantity+1 WHERE user_id=? AND product_id=? AND variant_weight=?");
        mysqli_stmt_bind_param($s,'iis',$userId,$productId,$variantWt);
        mysqli_stmt_execute($s);
    } elseif ($action === 'decrease' && $productId) {
        $s = mysqli_prepare($conn, "UPDATE cart SET quantity=quantity-1 WHERE user_id=? AND product_id=? AND variant_weight=?");
        mysqli_stmt_bind_param($s,'iis',$userId,$productId,$variantWt);
        mysqli_stmt_execute($s);
        mysqli_query($conn, "DELETE FROM cart WHERE user_id=$userId AND quantity <= 0");
    } elseif ($action === 'clear') {
        mysqli_query($conn, "DELETE FROM cart WHERE user_id=$userId");
    }
    header("Location: cart.php");
    exit();
}

$cartItems = mysqli_query($conn,
    "SELECT c.*, p.name, p.flavor, p.image_url, p.shop_id, s.name AS shop_name,
            COALESCE(pv.price, p.price) as effective_price
     FROM cart c
     JOIN products p ON c.product_id = p.id
     JOIN shops s    ON c.shop_id    = s.id
     LEFT JOIN product_variants pv ON c.product_id = pv.product_id AND c.variant_weight = pv.weight_label
     WHERE c.user_id = $userId"
);

$subtotal = 0;
$cartRows = [];
while ($row = mysqli_fetch_assoc($cartItems)) {
    $row['item_total'] = $row['effective_price'] * $row['quantity'];
    $subtotal += $row['item_total'];
    $cartRows[] = $row;
}

$gst = round($subtotal * 0.09, 2);
$sgst = round($subtotal * 0.09, 2);
$grandTotal = $subtotal + $gst + $sgst;

$shopName = count($cartRows) > 0 ? $cartRows[0]['shop_name'] : '';
$shopId   = count($cartRows) > 0 ? $cartRows[0]['shop_id']   : 0;
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>My Cart - Ghanshyam Bakery &amp; Live Cake Shop</title>
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
                <a class="text-stone-500 font-medium hover:text-amber-700 transition-colors duration-300 ease-in-out" href="shops.php">Shop</a>
                <a class="text-amber-900 border-b-2 border-amber-900 font-bold pb-1 duration-300 ease-in-out" href="cart.php">Cart</a>
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
        <a class="text-stone-500 px-4 py-3 flex items-center gap-3 hover:bg-rose-50/50 rounded-lg transition-all" href="shops.php">
            <span class="material-symbols-outlined">storefront</span> Browse Shops
        </a>
        <a class="text-stone-500 px-4 py-3 flex items-center gap-3 hover:bg-rose-50/50 rounded-lg transition-all" href="my_orders.php">
            <span class="material-symbols-outlined">receipt_long</span> My Orders
        </a>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8 overflow-x-hidden">
        
        <div class="mb-8 flex justify-between items-end">
            <div>
                <h1 class="font-headline-lg text-amber-950">My Cart</h1>
                <p class="text-body-md text-stone-500 mt-2">
                    <?php if (count($cartRows) > 0): ?>
                        <?= count($cartRows) ?> item(s) from <span class="font-bold text-secondary"><?= htmlspecialchars($shopName) ?></span>
                    <?php else: ?>
                        Your cart is empty
                    <?php endif; ?>
                </p>
            </div>
            
            <?php if (count($cartRows) > 0): ?>
            <form method="POST" onsubmit="return confirm('Clear entire cart?')">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="text-error font-label-md flex items-center gap-1 hover:underline p-2 rounded-lg hover:bg-error-container transition-colors">
                    <span class="material-symbols-outlined text-sm">delete</span> Clear Cart
                </button>
            </form>
            <?php endif; ?>
        </div>

        <?php if (count($cartRows) > 0): ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Cart Items -->
                <div class="lg:col-span-2 space-y-4">
                    <?php foreach ($cartRows as $item): ?>
                    <div class="bg-white rounded-xl p-4 border border-rose-50 ambient-shadow flex flex-col sm:flex-row items-start sm:items-center gap-4 group">
                        <img src="<?= htmlspecialchars($item['image_url'] ?: 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=500&q=80') ?>"
                             alt="Cake" class="w-24 h-24 rounded-lg object-cover bg-stone-50 border border-stone-100">
                        
                        <div class="flex-1">
                            <h3 class="font-headline-sm text-lg text-amber-950 mb-1">
                                <?= htmlspecialchars($item['name']) ?>
                                <?php if (!empty($item['flavor'])): ?>
                                    <span class="text-xs font-semibold text-secondary bg-primary-container px-2 py-0.5 rounded ml-2"><?= htmlspecialchars($item['flavor']) ?></span>
                                <?php endif; ?>
                                <?php if($item['variant_weight']): ?>
                                    <span class="text-sm font-bold text-secondary bg-secondary-container px-2 py-0.5 rounded ml-2"><?= htmlspecialchars($item['variant_weight']) ?></span>
                                <?php endif; ?>
                            </h3>
                            <p class="text-label-sm text-stone-500 mb-4">₹<?= number_format($item['effective_price'], 2) ?> each</p>
                            
                            <div class="flex items-center gap-4">
                                <div class="flex items-center bg-surface-container rounded-lg border border-rose-100">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="decrease">
                                        <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                        <input type="hidden" name="variant_weight" value="<?= htmlspecialchars($item['variant_weight']) ?>">
                                        <button type="submit" class="w-8 h-8 flex items-center justify-center text-secondary hover:bg-rose-50 rounded-l-lg transition-colors">−</button>
                                    </form>
                                    <span class="w-8 text-center font-bold text-amber-950 text-sm"><?= $item['quantity'] ?></span>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="increase">
                                        <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                        <input type="hidden" name="variant_weight" value="<?= htmlspecialchars($item['variant_weight']) ?>">
                                        <button type="submit" class="w-8 h-8 flex items-center justify-center text-secondary hover:bg-rose-50 rounded-r-lg transition-colors">+</button>
                                    </form>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                    <input type="hidden" name="variant_weight" value="<?= htmlspecialchars($item['variant_weight']) ?>">
                                    <button type="submit" class="text-stone-400 hover:text-error text-sm font-bold transition-colors">Remove</button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="text-right sm:text-left mt-2 sm:mt-0 w-full sm:w-auto flex sm:flex-col justify-between items-center sm:items-end">
                            <p class="text-sm text-stone-500 sm:hidden">Total:</p>
                            <span class="font-headline-sm text-xl text-amber-950">₹<?= number_format($item['item_total'], 2) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-stone-50 rounded-xl p-6 border border-rose-100 ambient-shadow sticky top-28">
                        <h2 class="font-headline-sm text-xl text-amber-950 mb-6">Order Summary</h2>
                        
                        <div class="space-y-4 mb-6">
                            <?php foreach ($cartRows as $item): ?>
                            <div class="flex justify-between text-sm">
                                <span class="text-stone-600 truncate mr-2 flex-1">
                                    <?= htmlspecialchars($item['name']) ?> 
                                    <?= !empty($item['flavor']) ? '[' . htmlspecialchars($item['flavor']) . '] ' : '' ?>
                                    <?= $item['variant_weight'] ? "(".htmlspecialchars($item['variant_weight']).")" : "" ?>
                                    × <?= $item['quantity'] ?>
                                </span>
                                <span class="text-amber-950 font-bold">₹<?= number_format($item['item_total'], 2) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="border-t border-rose-100 pt-4 space-y-3 mb-6">
                            <div class="flex justify-between text-sm">
                                <span class="text-stone-500">Subtotal</span>
                                <span class="text-amber-950 font-bold">₹<?= number_format($subtotal, 2) ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-stone-500">GST (9%)</span>
                                <span class="text-amber-950 font-bold">₹<?= number_format($gst, 2) ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-stone-500">SGST (9%)</span>
                                <span class="text-amber-950 font-bold">₹<?= number_format($sgst, 2) ?></span>
                            </div>
                        </div>
                        
                        <div class="border-t border-rose-200 pt-4 mb-8">
                            <div class="flex justify-between items-end">
                                <span class="font-bold text-amber-950">Total Amount</span>
                                <span class="font-headline-sm text-2xl text-secondary">₹<?= number_format($grandTotal, 2) ?></span>
                            </div>
                        </div>
                        
                        <a href="checkout.php?shop_id=<?= $shopId ?>" class="w-full flex justify-center py-3 bg-secondary text-white rounded-lg font-label-md hover:bg-on-secondary-fixed-variant transition-colors mb-3 shadow-md shadow-secondary/20">
                            Proceed to Checkout
                        </a>
                        <a href="shops.php" class="w-full flex justify-center py-3 border border-secondary text-secondary rounded-lg font-label-md hover:bg-rose-50 transition-colors">
                            Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl py-16 px-8 text-center border border-rose-50 ambient-shadow max-w-2xl mx-auto">
                <div class="w-24 h-24 bg-rose-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="material-symbols-outlined text-4xl text-secondary">shopping_cart</span>
                </div>
                <h3 class="font-headline-md text-2xl text-amber-950 mb-3">Your cart is empty</h3>
                <p class="text-stone-500 mb-8 max-w-sm mx-auto">Looks like you haven't added anything delicious yet. Let's fix that!</p>
                <a href="shops.php" class="inline-block px-8 py-3 bg-secondary text-white rounded-lg font-label-md hover:bg-on-secondary-fixed-variant transition-colors shadow-md shadow-secondary/20">
                    Browse Menu
                </a>
            </div>
        <?php endif; ?>
        
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
</body></html>
