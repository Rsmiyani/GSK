<?php
/**
 * customer/cart.php
 * =================
 * SHOPPING CART PAGE
 *
 * Displays all items the customer has added to their cart.
 * Allows: increase qty, decrease qty, remove item, clear cart.
 * Has a "Proceed to Checkout" button at the bottom.
 */

$required_role = 'customer';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$userId = $_SESSION['user_id'];

// ─── Handle Cart Actions (POST) ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action']     ?? '';
    $cartId    = (int)($_POST['cart_id']    ?? 0);
    $productId = (int)($_POST['product_id'] ?? 0);

    if ($action === 'remove' && $productId) {
        // Remove one specific item from cart
        $s = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id=? AND product_id=?");
        mysqli_stmt_bind_param($s,'ii',$userId,$productId);
        mysqli_stmt_execute($s);
    } elseif ($action === 'increase' && $productId) {
        // Add 1 to the quantity
        $s = mysqli_prepare($conn, "UPDATE cart SET quantity=quantity+1 WHERE user_id=? AND product_id=?");
        mysqli_stmt_bind_param($s,'ii',$userId,$productId);
        mysqli_stmt_execute($s);
    } elseif ($action === 'decrease' && $productId) {
        // Subtract 1 — if quantity becomes 0, remove the item
        $s = mysqli_prepare($conn, "UPDATE cart SET quantity=quantity-1 WHERE user_id=? AND product_id=?");
        mysqli_stmt_bind_param($s,'ii',$userId,$productId);
        mysqli_stmt_execute($s);
        // Remove items with 0 quantity
        mysqli_query($conn, "DELETE FROM cart WHERE user_id=$userId AND quantity <= 0");
    } elseif ($action === 'clear') {
        // Remove everything from the cart
        mysqli_query($conn, "DELETE FROM cart WHERE user_id=$userId");
    }
    header("Location: cart.php");
    exit();
}

// ─── Fetch Cart Items ─────────────────────────────────────────────────────────
// Join cart with products to get name, price, image
$cartItems = mysqli_query($conn,
    "SELECT c.*, p.name, p.price, p.image_url, p.shop_id, s.name AS shop_name
     FROM cart c
     JOIN products p ON c.product_id = p.id
     JOIN shops s    ON c.shop_id    = s.id
     WHERE c.user_id = $userId"
);

// Calculate grand total
$subtotal = 0;
$cartRows = [];
while ($row = mysqli_fetch_assoc($cartItems)) {
    $row['item_total'] = $row['price'] * $row['quantity']; // qty × price
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - Ghanshyam Bakery</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .cart-layout { display: grid; grid-template-columns: 1fr 320px; gap: 24px; }
        .cart-item {
            display: flex; align-items: center; gap: 16px;
            padding: 16px 0; border-bottom: 1px solid var(--border);
        }
        .cart-item:last-child { border-bottom: none; }
        .cart-item img { width: 70px; height: 70px; border-radius: 10px; object-fit: cover; }
        .cart-item-info { flex: 1; }
        .cart-item-info h4 { font-size: 0.95rem; font-weight: 700; }
        .cart-item-info p  { font-size: 0.8rem; color: var(--text-muted); }
        .qty-controls { display: flex; align-items: center; gap: 10px; }
        .qty-btn {
            width: 30px; height: 30px; border-radius: 6px;
            border: 1px solid var(--border);
            background: white; cursor: pointer; font-size: 1rem;
            display: flex; align-items: center; justify-content: center;
        }
        .qty-btn:hover { background: var(--body-bg); }
        .qty-num { font-weight: 700; min-width: 28px; text-align: center; }
        .summary-card {
            background: white; border-radius: 16px;
            padding: 24px; border: 1px solid var(--border);
            height: fit-content; position: sticky; top: 80px;
        }
        .summary-row {
            display: flex; justify-content: space-between;
            font-size: 0.9rem; padding: 8px 0;
            border-bottom: 1px solid var(--border);
        }
        .summary-row:last-of-type { border-bottom: none; }
        .summary-total { font-weight: 800; font-size: 1.1rem; }
        @media(max-width:768px) { .cart-layout { grid-template-columns: 1fr; } }
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
        <a href="cart.php" class="active"><span class="nav-icon">🛒</span> My Cart</a>
        <a href="my_orders.php"><span class="nav-icon">📦</span> My Orders</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../logout.php"><span>🚪</span> Logout</a>
    </div>
</aside>

<div class="main-content">
    <div class="topbar">
        <div class="topbar-title">
            <h1>🛒 My Cart</h1>
            <p><?= count($cartRows) ?> item(s) from <?= htmlspecialchars($shopName ?: 'N/A') ?></p>
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
        <?php if (count($cartRows) > 0): ?>
        <div class="cart-layout">

            <!-- ─── Cart Items List ─────────────────────────────────────────── -->
            <div class="table-card">
                <div class="table-card-header">
                    <h2>Cart Items</h2>
                    <!-- Clear entire cart button -->
                    <form method="POST" onsubmit="return confirm('Clear entire cart?')">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btn-danger btn-sm">🗑️ Clear Cart</button>
                    </form>
                </div>

                <?php foreach ($cartRows as $item): ?>
                <div class="cart-item">
                    <!-- Cake Image -->
                    <img src="<?= htmlspecialchars($item['image_url'] ?: 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=500&q=80') ?>"
                         alt="<?= htmlspecialchars($item['name']) ?>">

                    <!-- Cake Details -->
                    <div class="cart-item-info">
                        <h4><?= htmlspecialchars($item['name']) ?></h4>
                        <p>₹<?= number_format($item['price'], 2) ?> each</p>
                    </div>

                    <!-- Quantity Controls -->
                    <div class="qty-controls">
                        <!-- Decrease button -->
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="decrease">
                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                            <button type="submit" class="qty-btn">−</button>
                        </form>

                        <span class="qty-num"><?= $item['quantity'] ?></span>

                        <!-- Increase button -->
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="increase">
                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                            <button type="submit" class="qty-btn">+</button>
                        </form>
                    </div>

                    <!-- Subtotal for this item -->
                    <div style="min-width:90px;text-align:right;">
                        <strong style="color:var(--accent)">₹<?= number_format($item['item_total'], 2) ?></strong>
                    </div>

                    <!-- Remove this item -->
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                        <button type="submit" class="qty-btn" style="color:#ef4444;border-color:#ef4444;">✕</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ─── Order Summary Card ──────────────────────────────────────── -->
            <div class="summary-card">
                <h2 style="font-size:1.05rem;font-weight:700;margin-bottom:20px;">Order Summary</h2>

                <!-- Each item in summary -->
                <?php foreach ($cartRows as $item): ?>
                <div class="summary-row">
                    <span><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></span>
                    <span>₹<?= number_format($item['item_total'], 2) ?></span>
                </div>
                <?php endforeach; ?>

                <div class="summary-row" style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border);">
                    <span>Subtotal</span>
                    <span>₹<?= number_format($subtotal, 2) ?></span>
                </div>
                <div class="summary-row">
                    <span>GST (9%)</span>
                    <span>₹<?= number_format($gst, 2) ?></span>
                </div>
                <div class="summary-row">
                    <span>SGST (9%)</span>
                    <span>₹<?= number_format($sgst, 2) ?></span>
                </div>

                <!-- Grand Total -->
                <div class="summary-row summary-total" style="margin-top:12px;padding-top:12px;border-top:2px solid var(--border);">
                    <span>Total Amount</span>
                    <span style="color:var(--accent)">₹<?= number_format($grandTotal, 2) ?></span>
                </div>

                <!-- Checkout Button: passes shop_id to checkout page -->
                <a href="checkout.php?shop_id=<?= $shopId ?>"
                   class="btn btn-primary"
                   style="width:100%;justify-content:center;margin-top:20px;">
                    Proceed to Checkout →
                </a>

                <a href="shops.php" class="btn btn-outline"
                   style="width:100%;justify-content:center;margin-top:10px;">
                    ← Continue Shopping
                </a>
            </div>
        </div>

        <?php ob_start(); // Buffer to format else statement nicely ?>
        <?php else: ?>
        <!-- Improved Empty Cart UI -->
        <div class="empty-cart-container">
            <div class="empty-cart-visual">
                <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--accent); opacity: 0.8; margin: 0 auto; display: block;">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
            </div>
            <h3 style="font-size: 1.5rem; font-weight: 700; margin: 16px 0 8px; color: var(--text);">Your cart is empty!</h3>
            <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 300px; margin: 0 auto 24px;">Looks like you haven't added anything delicious yet. Let's fix that!</p>
            <a href="shops.php" class="btn btn-primary" style="padding: 12px 32px; font-size: 1rem; border-radius: 30px;">
                🍩 Browse Menu
            </a>
        </div>
        <style>
            .empty-cart-container {
                background: white; border-radius: 20px; padding: 60px 20px;
                text-align: center; border: 1px dashed var(--border);
                box-shadow: 0 4px 16px rgba(0,0,0,0.03); max-width: 600px; margin: 40px auto;
            }
            .empty-cart-visual {
                background: rgba(233, 30, 140, 0.05); padding: 30px;
                border-radius: 50%; display: inline-block; margin-bottom: 10px;
            }
        </style>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
