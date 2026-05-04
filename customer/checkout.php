<?php
/**
 * customer/checkout.php
 * =====================
 * FINAL CHECKOUT PAGE
 * 
 * This page handles the final step of the order process:
 *   - Collection of delivery address OR pickup time.
 *   - Map-based location picking (using Leaflet & OpenStreetMap).
 *   - Automatic time slot generation for bakery pickup.
 *   - Database transaction (Inserting order + order_items).
 */

// ─── Access Control ────────────────────────────────────────────────────────────
$required_role = 'customer';
require_once '../includes/auth_check.php'; // Ensures customer-only access
require_once '../config/db.php';           // Database connection ($conn)

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userInitial = strtoupper(substr($userName, 0, 1));

// ─── Shop Identification ──────────────────────────────────────────────────────
$shopId = (int)($_GET['shop_id'] ?? $_POST['shop_id'] ?? 0);
if ($shopId === 0) { 
    header("Location: cart.php"); // No shop specified? Go back to cart.
    exit(); 
}

// ─── Fetch Cart Items for this Shop ───────────────────────────────────────────
/**
 * We fetch all items in the user's cart that belong to the selected shop.
 * We also JOIN with product_variants to get the weight-specific price.
 */
$cartItems = mysqli_query($conn,
    "SELECT c.*, p.name, COALESCE(pv.price, p.price) as effective_price 
     FROM cart c 
     JOIN products p ON c.product_id=p.id 
     LEFT JOIN product_variants pv ON c.product_id = pv.product_id AND c.variant_weight = pv.weight_label
     WHERE c.user_id=$userId AND c.shop_id=$shopId"
);

$cartRows = []; 
$subtotal = 0;

while ($row = mysqli_fetch_assoc($cartItems)) {
    $row['item_total'] = $row['effective_price'] * $row['quantity'];
    $subtotal += $row['item_total'];
    $cartRows[] = $row;
}

// If the cart is empty for this shop, redirect back
if (count($cartRows) === 0) { 
    header("Location: cart.php"); 
    exit(); 
}

// Calculate tax and totals
$gst = round($subtotal * 0.09, 2);
$sgst = round($subtotal * 0.09, 2);
$grandTotal = $subtotal + $gst + $sgst;

// ─── Shop Metadata ────────────────────────────────────────────────────────────
$shopRes = mysqli_query($conn, "SELECT * FROM shops WHERE id = $shopId");
$shop = mysqli_fetch_assoc($shopRes);

// ─── Generate Pickup Time Slots ──────────────────────────────────────────────
/**
 * Generates 30-minute intervals between 9 AM and 9 PM.
 * Customers use these to schedule their cake pickup.
 */
$timeSlots = [];
$start = strtotime('09:00');
$end   = strtotime('21:00');
for ($t = $start; $t <= $end; $t += 1800) { // 1800s = 30 min
    $timeSlots[] = date('H:i', $t);
}

// ─── Handle Order Submission (Final Step) ─────────────────────────────────────
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Capture Form Inputs
    $orderType = $_POST['order_type'] ?? '';
    $deliveryAddress = trim($_POST['delivery_address'] ?? '');
    $pickupDate = $_POST['pickup_date'] ?? '';
    $pickupTime = $_POST['pickup_time'] ?? '';

    // 2. Validation Logic
    if ($orderType === 'delivery' && empty($deliveryAddress)) {
        $error = 'Please provide a delivery address.';
    } elseif ($orderType === 'pickup' && (empty($pickupDate) || empty($pickupTime))) {
        $error = 'Please select a valid pickup date and time.';
    }

    if (empty($error)) {
        // 3. Insert into 'orders' table (Parent record)
        $stmt = mysqli_prepare($conn,
            "INSERT INTO orders (customer_id, shop_id, order_type, delivery_address, pickup_date, pickup_time, total_amount, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
        );
        $pDate = $orderType === 'pickup' ? $pickupDate : null;
        $pTime = $orderType === 'pickup' ? $pickupTime : null;
        $dAddr = $orderType === 'delivery' ? $deliveryAddress : null;

        mysqli_stmt_bind_param($stmt, 'iissssd', $userId, $shopId, $orderType, $dAddr, $pDate, $pTime, $grandTotal);
        mysqli_stmt_execute($stmt);
        $orderId = mysqli_insert_id($conn); // Get the ID of the new order
        mysqli_stmt_close($stmt);

        // 4. Insert into 'order_items' table (Child records)
        $itemStmt = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, quantity, price, variant_weight) VALUES (?, ?, ?, ?, ?)");
        foreach ($cartRows as $item) {
            mysqli_stmt_bind_param($itemStmt, 'iiids', $orderId, $item['product_id'], $item['quantity'], $item['effective_price'], $item['variant_weight']);
            mysqli_stmt_execute($itemStmt);
        }
        mysqli_stmt_close($itemStmt);

        // 5. Clean up: Clear the user's cart after a successful order
        mysqli_query($conn, "DELETE FROM cart WHERE user_id=$userId");

        // 6. Success: Redirect to confirmation page
        header("Location: order_confirm.php?order_id=$orderId");
        exit();
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Checkout - Ghanshyam Bakery</title>
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
    <!-- Leaflet JS for the map location picker -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />   
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script> 
</head>
<body class="bg-surface text-on-surface font-body-md antialiased min-h-screen flex flex-col">

<!-- ═══ TOP NAVBAR ═══════════════════════════════════════════════════════════ -->
<header class="bg-stone-50 border-b border-rose-100 shadow-sm shadow-amber-900/5 sticky top-0 z-50">
    <nav class="flex justify-between items-center w-full px-8 py-4 font-serif text-base tracking-tight">
        <div class="flex items-center gap-6">
            <img src="../assets/logo/image.png" alt="Logo" class="w-10 h-10 object-cover rounded-md"/>
            <div>
                <div class="text-2xl font-bold text-amber-900">Ghanshyam Bakery &amp; Live Cake Shop</div>
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
        <a class="text-amber-900 font-semibold bg-rose-50 rounded-lg px-4 py-3 flex items-center gap-3 scale-[0.98] active:scale-95 duration-200" href="cart.php">
            <span class="material-symbols-outlined">shopping_cart</span> My Cart
        </a>
    </aside>

    <!-- ═══ MAIN CONTENT: CHECKOUT FLOW ═════════════════════════════════════════ -->
    <main class="flex-1 p-8 overflow-x-hidden">
        
        <div class="mb-8">
            <h1 class="font-headline-lg text-amber-950">Checkout</h1>
            <p class="text-body-md text-stone-500 mt-2">Complete your order from <span class="font-bold text-secondary"><?= htmlspecialchars($shop['name']) ?></span></p>
        </div>

        <!-- Error feedback -->
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-error-container text-on-error-container rounded-lg border border-red-200 flex items-center gap-3 font-medium">
                <span class="material-symbols-outlined">error</span> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="grid grid-cols-1 lg:grid-cols-5 gap-8">
            <input type="hidden" name="shop_id" value="<?= $shopId ?>">
            
            <!-- ── Column 1 & 2 & 3: Details Forms ── -->
            <div class="lg:col-span-3 space-y-6">
                <!-- 1. Order Type Toggle -->
                <div class="bg-white rounded-xl p-6 border border-rose-50 ambient-shadow">
                    <h2 class="font-headline-sm text-xl text-amber-950 mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary">local_shipping</span> Order Type
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="cursor-pointer">
                            <input type="radio" name="order_type" value="delivery" class="peer sr-only" checked onclick="toggleSection('delivery')">
                            <div class="p-4 rounded-lg border-2 border-surface-variant peer-checked:border-secondary peer-checked:bg-rose-50 transition-all text-center">
                                <span class="material-symbols-outlined text-3xl mb-2 text-stone-400 peer-checked:text-secondary block">two_wheeler</span>
                                <span class="font-bold text-amber-950 block">Delivery</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="order_type" value="pickup" class="peer sr-only" onclick="toggleSection('pickup')">
                            <div class="p-4 rounded-lg border-2 border-surface-variant peer-checked:border-secondary peer-checked:bg-rose-50 transition-all text-center">
                                <span class="material-symbols-outlined text-3xl mb-2 text-stone-400 peer-checked:text-secondary block">storefront</span>
                                <span class="font-bold text-amber-950 block">Store Pickup</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 2. Delivery Section (Address / Map) -->
                <div id="delivery_section" class="bg-white rounded-xl p-6 border border-rose-50 ambient-shadow block">
                    <h2 class="font-headline-sm text-xl text-amber-950 mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary">home_pin</span> Delivery Address
                    </h2>
                    
                    <!-- Tabs to switch between Typing and Map Picking -->
                    <div class="flex gap-2 mb-4 bg-surface-container rounded-lg p-1">
                        <button type="button" class="addr-tab flex-1 py-2 text-sm font-bold rounded-md bg-white text-secondary shadow-sm transition-colors" onclick="switchAddrMode('manual', this)">✍️ Type Address</button>
                        <button type="button" class="addr-tab flex-1 py-2 text-sm font-bold rounded-md text-stone-500 hover:text-stone-700 transition-colors" onclick="switchAddrMode('map', this)">🗺️ Pick on Map</button>
                    </div>

                    <!-- Mode A: Manual Address -->
                    <div class="addr-mode space-y-4 block" id="mode-manual">
                        <textarea id="addr-textarea" name="delivery_address" rows="3" class="w-full rounded-lg border-stone-200 focus:border-secondary focus:ring focus:ring-secondary/20 p-3" placeholder="Flat No, Building Name, Street..."></textarea>
                    </div>

                    <!-- Mode B: Map Selection -->
                    <div class="addr-mode hidden space-y-4" id="mode-map">
                        <div class="flex gap-2">
                            <input type="text" id="map-search-input" class="w-full rounded-lg border-stone-200 p-2 text-sm" placeholder="Search for your locality..." />
                            <button type="button" class="bg-secondary text-white px-4 rounded-lg font-bold" onclick="searchPlace()">Search</button>
                        </div>
                        <div id="delivery-map" class="w-full h-64 rounded-lg border border-rose-100 z-0"></div>
                        <div id="map-address-preview" class="hidden p-3 bg-green-50 border border-green-200 rounded-lg items-start gap-2">
                            <span class="text-sm font-bold text-green-800" id="map-address-text"></span>
                        </div>
                    </div>
                </div>

                <!-- 3. Pickup Section (Schedule) -->
                <div id="pickup_section" class="bg-white rounded-xl p-6 border border-rose-50 ambient-shadow hidden">
                    <h2 class="font-headline-sm text-xl text-amber-950 mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary">schedule</span> Pickup Schedule
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <input type="date" name="pickup_date" class="w-full rounded-lg border-stone-200 p-3" min="<?= date('Y-m-d') ?>">
                        <select name="pickup_time" class="w-full rounded-lg border-stone-200 p-3">
                            <option value="">-- Select Time --</option>
                            <?php foreach ($timeSlots as $ts): ?>
                                <option value="<?= $ts ?>"><?= date("h:i A", strtotime($ts)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- 4. Payment Indicator -->
                <div class="bg-white rounded-xl p-6 border border-rose-50 ambient-shadow">
                    <h2 class="font-headline-sm text-xl text-amber-950 mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary">payments</span> Payment Method
                    </h2>
                    <div class="p-4 rounded-lg border-2 border-secondary bg-rose-50 flex items-center gap-3">
                        <p class="font-bold text-amber-950">Cash on Delivery / Pay at Pickup</p>
                    </div>
                </div>
            </div>

            <!-- ── Column 4 & 5: Sticky Summary & Submit ── -->
            <div class="lg:col-span-2">
                <div class="bg-stone-50 rounded-xl p-6 border border-rose-100 ambient-shadow sticky top-28">
                    <h2 class="font-headline-sm text-xl text-amber-950 mb-6">Final Summary</h2>
                    
                    <!-- Item List -->
                    <div class="space-y-4 mb-6 max-h-60 overflow-y-auto pr-2">
                        <?php foreach ($cartRows as $item): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-stone-600 truncate mr-2 flex-1"><?= $item['quantity'] ?>x <?= htmlspecialchars($item['name']) ?></span>
                            <span class="text-amber-950 font-bold">₹<?= number_format($item['item_total'], 2) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Taxes -->
                    <div class="border-t border-rose-100 pt-4 space-y-3 mb-6">
                        <div class="flex justify-between text-sm"><span>GST (9%)</span><span>₹<?= number_format($gst, 2) ?></span></div>
                        <div class="flex justify-between text-sm"><span>SGST (9%)</span><span>₹<?= number_format($sgst, 2) ?></span></div>
                    </div>
                    
                    <!-- Grand Total -->
                    <div class="border-t border-rose-200 pt-4 mb-8">
                        <div class="flex justify-between items-end">
                            <span class="font-bold text-amber-950">Total Amount</span>
                            <span class="font-headline-sm text-2xl text-secondary">₹<?= number_format($grandTotal, 2) ?></span>
                        </div>
                    </div>
                    
                    <!-- Primary Call to Action -->
                    <button type="submit" class="w-full py-3 bg-secondary text-white rounded-lg font-bold hover:bg-on-secondary-fixed-variant transition-colors shadow-md text-lg">
                        Confirm Order
                    </button>
                    <a href="cart.php" class="w-full flex justify-center py-3 mt-3 text-stone-500 hover:text-amber-950">← Back to Cart</a>
                </div>
            </div>
        </form>
    </main>
</div>

<!-- ═══ CLIENT-SIDE SCRIPTS ══════════════════════════════════════════════════ -->
<script>
/**
 * toggleSection(type)
 * ===================
 * Switches between 'Delivery' and 'Pickup' form fields.
 * Also disables irrelevant inputs so they aren't sent to PHP.
 */
function toggleSection(type) {
    const delivery = document.getElementById('delivery_section');
    const pickup = document.getElementById('pickup_section');
    
    if (type === 'delivery') {
        delivery.style.display = 'block';
        pickup.style.display = 'none';
        document.querySelector('[name="pickup_date"]').disabled = true;
        document.querySelector('[name="pickup_time"]').disabled = true;
        document.querySelector('[name="delivery_address"]').disabled = false;
        if (map) setTimeout(() => map.invalidateSize(), 200);
    } else {
        delivery.style.display = 'none';
        pickup.style.display = 'block';
        document.querySelector('[name="delivery_address"]').disabled = true;
        document.querySelector('[name="pickup_date"]').disabled = false;
        document.querySelector('[name="pickup_time"]').disabled = false;
    }
}

/**
 * switchAddrMode(mode, btn)
 * =========================
 * Switches between manual address input and map picker.
 */
function switchAddrMode(mode, btn) {
    document.querySelectorAll('.addr-tab').forEach(t => t.className = 'addr-tab flex-1 py-2 text-sm font-bold rounded-md text-stone-500');
    btn.className = 'addr-tab flex-1 py-2 text-sm font-bold rounded-md bg-white text-secondary shadow-sm';
    
    document.getElementById('mode-manual').className = 'addr-mode space-y-4 ' + (mode === 'manual' ? 'block' : 'hidden');
    document.getElementById('mode-map').className = 'addr-mode space-y-4 ' + (mode === 'map' ? 'block' : 'hidden');
    
    if (mode === 'map') setTimeout(initMap, 200);
}

// ── Map Picker Logic (Leaflet.js) ──
let map = null, marker = null, mapReady = false;

function initMap() {
    if (mapReady && map) { map.invalidateSize(); return; }
    // Default coordinates (either shop location or a default city center)
    const defaultLat = <?= $shop['lat'] ?? 22.3039 ?>;
    const defaultLng = <?= $shop['lng'] ?? 70.8022 ?>;

    map = L.map('delivery-map').setView([defaultLat, defaultLng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

    // Update address when user drags the pin
    marker.on('dragend', function() {
        const ll = marker.getLatLng();
        reverseGeocode(ll.lat, ll.lng);
    });

    // Update address when user clicks anywhere on the map
    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        reverseGeocode(e.latlng.lat, e.latlng.lng);
    });

    mapReady = true;
}

/**
 * reverseGeocode(lat, lng)
 * ========================
 * Uses OpenStreetMap's Nominatim API to convert GPS coordinates into a text address.
 */
function reverseGeocode(lat, lng) {
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
        .then(r => r.json())
        .then(data => {
            const addr = data.display_name || (lat.toFixed(6) + ', ' + lng.toFixed(6));
            document.getElementById('addr-textarea').value = addr;
            document.getElementById('map-address-preview').classList.replace('hidden', 'flex');
            document.getElementById('map-address-text').textContent = addr;
        });
}

function searchPlace() {
    const q = document.getElementById('map-search-input').value.trim();
    if (!q) return;
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&limit=1`)
        .then(r => r.json())
        .then(data => {
            if (data.length > 0) {
                const lat = parseFloat(data[0].lat), lng = parseFloat(data[0].lon);
                map.setView([lat, lng], 16);
                marker.setLatLng([lat, lng]);
                reverseGeocode(lat, lng);
            }
        });
}
</script>
</body>
</html>
