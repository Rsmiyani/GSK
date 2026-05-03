<?php
/**
 * customer/checkout.php
 * =====================
 * CHECKOUT PAGE
 *
 * Customer chooses:
 *   A) 🚚 Delivery  → enters their delivery address
 *   B) 🏪 Pickup   → picks a date + time slot (fixed 30-min slots: 9am–9pm)
 *
 * On submit, this creates an order in the database and redirects to confirmation.
 */

$required_role = 'customer';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$userId = $_SESSION['user_id'];
$shopId = (int)($_GET['shop_id'] ?? $_POST['shop_id'] ?? 0);

if ($shopId === 0) { header("Location: cart.php"); exit(); }

// ─── Get Cart Items ───────────────────────────────────────────────────────────
$cartItems = mysqli_query($conn,
    "SELECT c.*, p.name, p.price FROM cart c JOIN products p ON c.product_id=p.id WHERE c.user_id=$userId AND c.shop_id=$shopId"
);
$cartRows = []; $subtotal = 0;
while ($row = mysqli_fetch_assoc($cartItems)) {
    $row['item_total'] = $row['price'] * $row['quantity'];
    $subtotal += $row['item_total'];
    $cartRows[] = $row;
}
if (count($cartRows) === 0) { header("Location: cart.php"); exit(); }

$gst = round($subtotal * 0.09, 2);
$sgst = round($subtotal * 0.09, 2);
$grandTotal = $subtotal + $gst + $sgst;

// ─── Get Shop Info ────────────────────────────────────────────────────────────
$shopRes = mysqli_query($conn, "SELECT * FROM shops WHERE id = $shopId");
$shop = mysqli_fetch_assoc($shopRes);

// ─── Generate Pickup Time Slots (every 30 min, 9am to 9pm) ───────────────────
$timeSlots = [];
$start = strtotime('09:00');
$end   = strtotime('21:00');
for ($t = $start; $t <= $end; $t += 1800) { // 1800 seconds = 30 minutes
    $timeSlots[] = date('H:i', $t);
}

// ─── Handle Form Submission ───────────────────────────────────────────────────
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderType = $_POST['order_type'] ?? '';
    $deliveryAddress = trim($_POST['delivery_address'] ?? '');
    $pickupDate = $_POST['pickup_date'] ?? '';
    $pickupTime = $_POST['pickup_time'] ?? '';

    // Validate based on order type
    if ($orderType === 'delivery' && empty($deliveryAddress)) {
        $error = 'Please enter your delivery address.';
    } elseif ($orderType === 'pickup' && (empty($pickupDate) || empty($pickupTime))) {
        $error = 'Please select a pickup date and time.';
    } elseif (!in_array($orderType, ['delivery', 'pickup'])) {
        $error = 'Please select delivery or pickup.';
    }

    if (empty($error)) {
        // ── Insert the Order ─────────────────────────────────────────────────
        $stmt = mysqli_prepare($conn,
            "INSERT INTO orders (customer_id, shop_id, order_type, delivery_address, pickup_date, pickup_time, total_amount, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
        );
        $pDate = $orderType === 'pickup' ? $pickupDate : null;
        $pTime = $orderType === 'pickup' ? $pickupTime : null;
        $dAddr = $orderType === 'delivery' ? $deliveryAddress : null;

        mysqli_stmt_bind_param($stmt, 'iissssd',
            $userId, $shopId, $orderType, $dAddr, $pDate, $pTime, $grandTotal
        );
        mysqli_stmt_execute($stmt);
        $orderId = mysqli_insert_id($conn); // Get the new order's ID
        mysqli_stmt_close($stmt);

        // ── Insert Each Cart Item as an Order Item ───────────────────────────
        $itemStmt = mysqli_prepare($conn,
            "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)"
        );
        foreach ($cartRows as $item) {
            mysqli_stmt_bind_param($itemStmt, 'iiid',
                $orderId, $item['product_id'], $item['quantity'], $item['price']
            );
            mysqli_stmt_execute($itemStmt);
        }
        mysqli_stmt_close($itemStmt);

        // ── Clear the Cart ───────────────────────────────────────────────────
        mysqli_query($conn, "DELETE FROM cart WHERE user_id=$userId");

        // ── Redirect to Confirmation Page ────────────────────────────────────
        header("Location: order_confirm.php?order_id=$orderId");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Ghanshyam Bakery</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        .checkout-layout { display: grid; grid-template-columns: 1fr 300px; gap: 24px; }
        .type-tabs { display: flex; gap: 12px; margin-bottom: 24px; }
        .type-tab {
            flex: 1; padding: 18px; border: 2px solid var(--border);
            border-radius: 12px; cursor: pointer; text-align: center;
            transition: all 0.2s; background: white;
        }
        .type-tab.selected { border-color: var(--accent); background: rgba(233,30,140,0.06); }
        .type-tab .tab-icon { font-size: 2rem; display: block; margin-bottom: 8px; }
        .type-tab h3 { font-size: 1rem; font-weight: 700; }
        .type-tab p  { font-size: 0.78rem; color: var(--text-muted); margin-top: 4px; }
        .delivery-section, .pickup-section { display: none; }
        .delivery-section.show, .pickup-section.show { display: block; }

        /* Address Mode Tabs */
        .addr-tabs { display: flex; gap: 8px; margin-bottom: 18px; flex-wrap: wrap; }
        .addr-tab {
            padding: 9px 16px; border-radius: 20px; font-size: 0.82rem; font-weight: 600;
            border: 1.5px solid var(--border); background: white; color: var(--text-muted);
            cursor: pointer; transition: all 0.2s;
        }
        .addr-tab.active { background: var(--accent); color: white; border-color: var(--accent); }
        .addr-tab:hover:not(.active) { border-color: var(--accent); color: var(--accent); }

        /* Map */
        #delivery-map { width: 100%; height: 320px; border-radius: 12px; border: 1.5px solid var(--border); margin-bottom: 12px; z-index: 1; }
        .map-search-bar { display: flex; gap: 8px; margin-bottom: 10px; }
        .map-search-bar input { flex: 1; padding: 10px 14px; border: 1.5px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 0.88rem; }
        .map-search-bar input:focus { outline: none; border-color: var(--accent); }
        .map-search-bar button { padding: 10px 16px; border: none; background: var(--accent); color: white; border-radius: 8px; cursor: pointer; font-size: 1rem; }
        .map-address-preview {
            display: flex; align-items: center; gap: 10px; padding: 12px 16px;
            background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25);
            border-radius: 10px; font-size: 0.85rem; color: #065f46;
        }
        .preview-icon { font-size: 1.2rem; }

        @media(max-width:768px) { .checkout-layout { grid-template-columns: 1fr; } }
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
    <div class="sidebar-footer"><a href="../logout.php"><span>🚪</span> Logout</a></div>
</aside>

<div class="main-content">
    <div class="topbar">
        <div class="topbar-title">
            <h1>✅ Checkout</h1>
            <p><?= htmlspecialchars($shop['name']) ?></p>
        </div>
        <div class="topbar-user">
            <div class="user-info"><strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong><span>customer</span></div>
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_name'],0,1)) ?></div>
        </div>
    </div>

    <div class="page-body">
        <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="shop_id" value="<?= $shopId ?>">
            <div class="checkout-layout">

                <!-- ─── Left: Delivery / Pickup Options ───────────────────── -->
                <div>
                    <!-- Step 1: Choose Order Type -->
                    <div class="table-card" style="margin-bottom:20px;">
                        <h2 style="margin-bottom:20px;font-size:1.05rem;font-weight:700;">Step 1: Choose Delivery Method</h2>
                        <div class="type-tabs">
                            <!-- Delivery Tab -->
                            <div class="type-tab" id="tab-delivery" onclick="selectType('delivery')">
                                <span class="tab-icon">🚚</span>
                                <h3>Home Delivery</h3>
                                <p>Get it delivered to your door</p>
                            </div>
                            <!-- Pickup Tab -->
                            <div class="type-tab" id="tab-pickup" onclick="selectType('pickup')">
                                <span class="tab-icon">🏪</span>
                                <h3>Store Pickup</h3>
                                <p>Pick up at your convenience</p>
                            </div>
                        </div>
                        <!-- Hidden input that stores the selected type -->
                        <input type="hidden" name="order_type" id="order_type" required>
                    </div>

                    <!-- Step 2a: Delivery Address (shown only when Delivery is selected) -->
                    <div class="table-card delivery-section" id="delivery-section">
                        <h2 style="margin-bottom:16px;font-size:1.05rem;font-weight:700;">Step 2: Delivery Address</h2>

                        <!-- Address Mode Tabs -->
                        <div class="addr-tabs">
                            <button type="button" class="addr-tab active" onclick="switchAddrMode('manual',this)">✍️ Type Address</button>
                            <button type="button" class="addr-tab" onclick="switchAddrMode('map',this)">🗺️ Pick on Map</button>
                        </div>

                        <!-- Mode 1: Manual typing -->
                        <div class="addr-mode" id="mode-manual">
                            <div class="form-group">
                                <label>Your Full Delivery Address</label>
                                <textarea id="addr-textarea" name="delivery_address" rows="3"
                                    placeholder="House No., Street, Area, City, Pincode"
                                    style="width:100%;padding:12px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;resize:vertical;"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Mode 2: Map picker -->
                        <div class="addr-mode" id="mode-map" style="display:none;">
                            <p style="font-size:0.82rem;color:var(--text-muted);margin-bottom:10px;">🖱️ Click on the map or drag the pin to select your delivery location.</p>
                            <div class="map-search-bar">
                                <input type="text" id="map-search-input" placeholder="Search for a place..." />
                                <button type="button" id="map-search-btn" onclick="searchPlace()">🔍</button>
                            </div>
                            <div id="delivery-map"></div>
                            <div class="map-address-preview" id="map-address-preview" style="display:none;">
                                <span class="preview-icon">📍</span>
                                <span id="map-address-text"></span>
                            </div>
                        </div>

                    </div>

                    <!-- Step 2b: Pickup Date + Time (shown only when Pickup is selected) -->
                    <div class="table-card pickup-section" id="pickup-section">
                        <h2 style="margin-bottom:16px;font-size:1.05rem;font-weight:700;">Step 2: Schedule Your Pickup</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Pickup Date</label>
                                <!-- min = today's date so they can't pick past dates -->
                                <input type="date" name="pickup_date" min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="form-group">
                                <label>Pickup Time Slot</label>
                                <select name="pickup_time">
                                    <option value="">-- Select Time --</option>
                                    <?php foreach ($timeSlots as $slot): ?>
                                    <option value="<?= $slot ?>"><?= date('h:i A', strtotime($slot)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ─── Right: Order Summary ────────────────────────────────── -->
                <div class="summary-card" style="background:white;border-radius:16px;padding:24px;border:1px solid var(--border);">
                    <h2 style="font-size:1rem;font-weight:700;margin-bottom:16px;">Order Summary</h2>
                    <?php foreach ($cartRows as $item): ?>
                    <div style="display:flex;justify-content:space-between;font-size:0.85rem;padding:6px 0;border-bottom:1px solid var(--border);">
                        <span><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></span>
                        <span>₹<?= number_format($item['item_total'],2) ?></span>
                    </div>
                    <?php endforeach; ?>
                    
                    <div style="display:flex;justify-content:space-between;font-size:0.85rem;margin-top:14px;padding-top:10px;border-top:1px solid var(--border);">
                        <span>Subtotal</span>
                        <span>₹<?= number_format($subtotal,2) ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:0.85rem;padding-top:6px;">
                        <span>GST (9%)</span>
                        <span>₹<?= number_format($gst,2) ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:0.85rem;padding-top:6px;">
                        <span>SGST (9%)</span>
                        <span>₹<?= number_format($sgst,2) ?></span>
                    </div>

                    <div style="display:flex;justify-content:space-between;font-weight:800;font-size:1.1rem;margin-top:14px;padding-top:10px;border-top:2px solid var(--border);">
                        <span>Total Amount</span>
                        <span style="color:var(--accent)">₹<?= number_format($grandTotal,2) ?></span>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:20px;" id="place-btn" disabled>
                        Select Delivery Method First
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// ─── Delivery / Pickup Toggle ────────────────────────────────────────────────
function selectType(type) {
    document.getElementById('order_type').value = type;
    document.getElementById('tab-delivery').classList.remove('selected');
    document.getElementById('tab-pickup').classList.remove('selected');
    document.getElementById('tab-' + type).classList.add('selected');
    document.getElementById('delivery-section').classList.toggle('show', type === 'delivery');
    document.getElementById('pickup-section').classList.toggle('show', type === 'pickup');
    const btn = document.getElementById('place-btn');
    btn.disabled = false;
    btn.textContent = '🎂 Place Order';

    // Initialize map if switching to delivery and map mode is active
    if (type === 'delivery' && document.getElementById('mode-map').style.display !== 'none') {
        setTimeout(initMap, 200);
    }
}

// ─── Address Mode Switcher ───────────────────────────────────────────────────
function switchAddrMode(mode, btn) {
    document.querySelectorAll('.addr-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.addr-mode').forEach(m => m.style.display = 'none');
    document.getElementById('mode-' + mode).style.display = 'block';
    if (mode === 'map') setTimeout(initMap, 200);
}

// ─── MAP MODE (Leaflet + OpenStreetMap) ──────────────────────────────────────
let map = null, marker = null, mapReady = false;

function initMap() {
    if (mapReady && map) { map.invalidateSize(); return; }
    // Default: Rajkot, Gujarat
    const defaultLat = <?= $shop['lat'] ?? 22.3039 ?>;
    const defaultLng = <?= $shop['lng'] ?? 70.8022 ?>;

    map = L.map('delivery-map').setView([defaultLat, defaultLng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors', maxZoom: 19
    }).addTo(map);

    marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

    // On marker drag end → reverse geocode
    marker.on('dragend', function() {
        const ll = marker.getLatLng();
        reverseGeocode(ll.lat, ll.lng);
    });

    // On map click → move marker & reverse geocode
    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        reverseGeocode(e.latlng.lat, e.latlng.lng);
    });

    mapReady = true;
}

function searchPlace() {
    const q = document.getElementById('map-search-input').value.trim();
    if (!q) return;
    fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(q) + '&limit=1')
        .then(r => r.json())
        .then(data => {
            if (data.length > 0) {
                const lat = parseFloat(data[0].lat), lng = parseFloat(data[0].lon);
                map.setView([lat, lng], 16);
                marker.setLatLng([lat, lng]);
                reverseGeocode(lat, lng);
            } else {
                alert('Location not found. Try a different search term.');
            }
        }).catch(() => alert('Search failed. Check your internet connection.'));
}

// Enter key in search
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('map-search-input');
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); searchPlace(); }
        });
    }
});

function reverseGeocode(lat, lng) {
    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng)
        .then(r => r.json())
        .then(data => {
            const addr = data.display_name || (lat.toFixed(6) + ', ' + lng.toFixed(6));
            setAddress(addr);
            document.getElementById('map-address-preview').style.display = 'flex';
            document.getElementById('map-address-text').textContent = addr;
        }).catch(() => {
            const fallback = lat.toFixed(6) + ', ' + lng.toFixed(6);
            setAddress(fallback);
        });
}


// ─── Shared: Set the address textarea value ──────────────────────────────────
function setAddress(addr) {
    document.getElementById('addr-textarea').value = addr;
}
</script>
</body>
</html>
