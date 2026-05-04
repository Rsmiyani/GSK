<?php
/**
 * customer/add_to_cart.php
 * ========================
 * AJAX HANDLER: ADD TO CART
 *
 * This script is called via JavaScript fetch() from the shop listing pages.
 * It manages the user's persistent cart in the database.
 *
 * KEY LOGIC:
 *   - Authentication check (Customer only).
 *   - Single-shop constraint: Users cannot mix items from different bakery branches in one cart.
 *   - Upsert logic: If the item exists (same product + weight), we increase quantity.
 *   - JSON response: Communicates success/failure back to the frontend.
 */

// ─── Environment Setup ────────────────────────────────────────────────────────
$required_role = 'customer';
require_once '../includes/auth_check.php'; // Ensure user is logged in as a customer
require_once '../config/db.php';           // Database connection ($conn)

// Set header so the browser knows we are sending a JSON object, not HTML
header('Content-Type: application/json');

// Only allow POST requests (Security measure)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// ─── Input Sanitization ───────────────────────────────────────────────────────
$userId    = $_SESSION['user_id'];
$productId = (int)($_POST['product_id'] ?? 0);
$shopId    = (int)($_POST['shop_id']    ?? 0);
$variantWt = trim($_POST['variant_weight'] ?? ''); // e.g. "500g" or empty for non-variant products

// Basic validation
if ($productId === 0 || $shopId === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product or shop data.']);
    exit();
}

// ─── Single Shop Constraint ───────────────────────────────────────────────────
/**
 * To avoid complex logistics, a customer can only add items from ONE branch at a time.
 * We check if the cart already contains items from a different shop_id.
 */
$existingShop = mysqli_query($conn, "SELECT DISTINCT shop_id FROM cart WHERE user_id = $userId LIMIT 1");
if ($existingRow = mysqli_fetch_assoc($existingShop)) {
    if ($existingRow['shop_id'] != $shopId) {
        // Conflict: The user is trying to order from Shop B while Shop A items are still in the cart.
        echo json_encode([
            'success' => false,
            'message' => 'Your cart contains items from a different branch. Please clear your cart before adding treats from this shop.'
        ]);
        exit();
    }
}

// ─── Database Upsert (Insert or Update) ───────────────────────────────────────
/**
 * We use the "INSERT ... ON DUPLICATE KEY UPDATE" pattern.
 * This works because the 'cart' table has a composite unique index on (user_id, product_id, variant_weight).
 * 
 * Flow:
 *   1. Try to insert a new row with quantity = 1.
 *   2. If a matching row exists, MySQL triggers the UPDATE clause instead, increasing quantity by 1.
 */
$stmt = mysqli_prepare($conn,
    "INSERT INTO cart (user_id, product_id, shop_id, variant_weight, quantity)
     VALUES (?, ?, ?, ?, 1)
     ON DUPLICATE KEY UPDATE quantity = quantity + 1"
);
mysqli_stmt_bind_param($stmt, 'iiis', $userId, $productId, $shopId, $variantWt);

if (mysqli_stmt_execute($stmt)) {
    // Return success to trigger the frontend "Added" toast/animation
    echo json_encode(['success' => true, 'message' => 'Treat added to cart!']);
} else {
    // Return error if database insertion fails (e.g. connection lost)
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}

mysqli_stmt_close($stmt);
?>
