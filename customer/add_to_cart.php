<?php
/**
 * customer/add_to_cart.php
 * ========================
 * ADD TO CART HANDLER (AJAX)
 *
 * Called via JavaScript fetch() from shop_detail.php.
 * Adds or updates an item in the cart table.
 *
 * Returns JSON response: { "success": true/false, "message": "..." }
 */

// ─── Start Session and Include Files ──────────────────────────────────────────
$required_role = 'customer';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

// Tell the browser we're returning JSON data
header('Content-Type: application/json');

// ─── Only handle POST ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// ─── Get Data from the AJAX Request ──────────────────────────────────────────
$userId    = $_SESSION['user_id'];
$productId = (int)($_POST['product_id'] ?? 0);
$shopId    = (int)($_POST['shop_id']    ?? 0);
$variantWt = trim($_POST['variant_weight'] ?? '');
$quantity  = 1; // Always add 1 at a time from the catalog

// Validate
if ($productId === 0 || $shopId === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit();
}

// ─── Check Cart Doesn't Mix Shops ────────────────────────────────────────────
// Customers can only order from ONE shop at a time (like Swiggy/Zomato rules)
$existingShop = mysqli_query($conn, "SELECT DISTINCT shop_id FROM cart WHERE user_id = $userId LIMIT 1");
if ($existingRow = mysqli_fetch_assoc($existingShop)) {
    if ($existingRow['shop_id'] != $shopId) {
        // Cart has items from a different shop
        echo json_encode([
            'success' => false,
            'message' => 'Your cart has items from another shop. Clear cart first.'
        ]);
        exit();
    }
}

// ─── Insert or Update Cart ────────────────────────────────────────────────────
// INSERT IGNORE: inserts if the item doesn't exist
// ON DUPLICATE KEY UPDATE: if same user + product + variant already in cart, just increase qty
$stmt = mysqli_prepare($conn,
    "INSERT INTO cart (user_id, product_id, shop_id, variant_weight, quantity)
     VALUES (?, ?, ?, ?, 1)
     ON DUPLICATE KEY UPDATE quantity = quantity + 1"
);
mysqli_stmt_bind_param($stmt, 'iiis', $userId, $productId, $shopId, $variantWt);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Item added to cart!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add item. Try again.']);
}
mysqli_stmt_close($stmt);
?>
