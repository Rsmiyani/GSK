<?php
/**
 * customer/shops.php
 * ==================
 * NEARBY SHOPS PAGE
 *
 * Shows all active bakery branches, sorted by distance from the customer's location.
 * The distance is calculated using the Haversine formula (a math formula for GPS distance).
 *
 * If the customer allows location access, shops are sorted by how close they are.
 * If not, all shops are shown alphabetically.
 */

// ─── Security ─────────────────────────────────────────────────────────────────
$required_role = 'customer';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

// ─── Haversine Distance Function ──────────────────────────────────────────────
/**
 * Calculates the straight-line distance between two GPS coordinates.
 * Uses the Haversine formula which accounts for Earth's curvature.
 *
 * @param float $lat1  User's latitude
 * @param float $lng1  User's longitude
 * @param float $lat2  Shop's latitude
 * @param float $lng2  Shop's longitude
 * @return float Distance in kilometers
 */
function haversine($lat1, $lng1, $lat2, $lng2) {
    $R = 6371; // Earth's radius in km
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return round($R * $c, 2); // returns distance rounded to 2 decimal places
}

// ─── Get User Location (sent via JavaScript) ──────────────────────────────────
// The browser JS sends lat/lng as URL parameters: shops.php?lat=22.3&lng=70.8
$userLat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$userLng = isset($_GET['lng']) ? (float)$_GET['lng'] : null;

// ─── Fetch All Active Shops ───────────────────────────────────────────────────
$result = mysqli_query($conn, "SELECT s.*, u.name AS owner_name FROM shops s LEFT JOIN users u ON s.owner_id = u.id WHERE s.is_active = 1");
$shops = [];
while ($row = mysqli_fetch_assoc($result)) {
    // If we have the user's location, calculate distance to each shop
    if ($userLat && $userLng) {
        $d = haversine($userLat, $userLng, $row['lat'], $row['lng']);
        $row['distance'] = $d; // numeric in km
        // Friendly label: show meters if under 1 km
        if ($d < 1 && $d > 0) {
            $row['distance_label'] = round($d * 1000) . ' m away';
        } elseif ($d == 0) {
            $row['distance_label'] = 'Nearby';
        } else {
            $row['distance_label'] = number_format($d, 2) . ' km away';
        }
    }
    $shops[] = $row;
}

// Sort shops by distance if location is available
if ($userLat && $userLng) {
    usort($shops, fn($a, $b) => $a['distance'] <=> $b['distance']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Shops - Ghanshyam Bakery</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        /* Shop card grid layout */
        .shops-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 22px;
        }
        /* Each shop card */
        .shop-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--border);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .shop-card:hover { transform: translateY(-4px); box-shadow: 0 10px 28px rgba(0,0,0,0.10); }
        .shop-name { font-size: 1.05rem; font-weight: 700; margin-bottom: 6px; }
        .shop-address { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 12px; }
        .shop-meta { display: flex; align-items: center; justify-content: space-between; margin-top: 16px; }
        .distance-badge {
            background: rgba(233,30,140,0.10);
            color: var(--accent);
            font-weight: 700;
            font-size: 0.82rem;
            padding: 5px 12px;
            border-radius: 20px;
        }
        /* Location detection bar at top */
        .location-bar {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: white;
            border-radius: 12px;
            padding: 18px 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .location-bar p { font-size: 0.9rem; opacity: 0.85; }
        #location-status { font-size: 0.82rem; margin-top: 4px; opacity: 0.6; }
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
            <h1>📍 Find Shops</h1>
            <p>Discover Ghanshyam Bakery branches near you</p>
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

        <!-- Location Detection Bar -->
        <div class="location-bar">
            <div>
                <strong>📡 Sort by Distance</strong>
                <p id="location-status">Click the button to detect your location and sort shops by distance.</p>
            </div>
            <!-- This button triggers JavaScript to get the user's GPS location -->
            <button class="btn btn-primary" onclick="detectLocation()" id="locate-btn">
                📍 Use My Location
            </button>
        </div>

        <!-- Shop Cards Grid -->
        <div class="shops-grid">
            <?php if (count($shops) > 0): ?>
                <?php foreach ($shops as $shop): ?>
                <div class="shop-card">
                    <div style="font-size:2rem;margin-bottom:12px;">🏪</div>

                    <!-- Shop Name -->
                    <div class="shop-name"><?= htmlspecialchars($shop['name']) ?></div>

                    <!-- Shop Address -->
                    <div class="shop-address">📍 <?= htmlspecialchars($shop['address']) ?></div>

                    <!-- Phone -->
                    <div style="font-size:0.85rem;color:var(--text-muted);">
                        📞 <?= htmlspecialchars($shop['phone'] ?? 'N/A') ?>
                    </div>

                    <div class="shop-meta">
                        <!-- Show distance if location was detected -->
                        <?php if (isset($shop['distance_label'])): ?>
                        <span class="distance-badge">📏 <?= htmlspecialchars($shop['distance_label']) ?></span>
                        <?php else: ?>
                        <span class="badge badge-active">✅ Open</span>
                        <?php endif; ?>

                        <!-- Actions: Browse + Directions -->
                        <div style="display:flex;gap:8px;align-items:center;">
                            <a href="shop_detail.php?shop_id=<?= $shop['id'] ?>" class="btn btn-primary btn-sm" style="padding:8px 12px;">Browse Menu →</a>
                            <?php
                                // Build Google Maps directions URL. If we have user's coords, include origin so directions are calculated from user's location.
                                $dest = urlencode($shop['lat'] . ',' . $shop['lng']);
                                if ($userLat && $userLng) {
                                    $origin = urlencode($userLat . ',' . $userLng);
                                    $mapsUrl = "https://www.google.com/maps/dir/?api=1&origin={$origin}&destination={$dest}&travelmode=driving";
                                } else {
                                    // No origin: open directions to destination only (Google Maps will use device location if available)
                                    $mapsUrl = "https://www.google.com/maps/dir/?api=1&destination={$dest}&travelmode=driving";
                                }
                            ?>
                            <a href="<?= $mapsUrl ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" style="padding:8px 12px;border:1px solid var(--border);background:transparent;color:var(--text-dark);">
                                ➜ Directions
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state" style="grid-column:1/-1">
                    <div class="empty-icon">🏪</div>
                    <h3>No shops available</h3>
                    <p>Check back soon for new branches!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
/**
 * Automatically fetch location on page load if not already provided in URL
 */
document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('lat') || !urlParams.has('lng')) {
        detectLocation();
    }
});

/**
 * detectLocation()
 * ================
 * Uses the browser's built-in Geolocation API to get the user's GPS coordinates.
 * Then reloads the page with lat/lng in the URL so PHP can sort by distance.
 */
function detectLocation() {
    const btn = document.getElementById('locate-btn');
    const status = document.getElementById('location-status');

    // Check if browser supports geolocation
    if (!navigator.geolocation) {
        if(status) status.textContent = '❌ Your browser does not support location detection.';
        return;
    }

    if(btn) {
        btn.textContent = '⏳ Detecting...';
        btn.disabled = true;
    }
    if(status) status.textContent = 'Requesting location permission...';

    // Ask the browser for the user's GPS coordinates
    navigator.geolocation.getCurrentPosition(
        function(position) {
            // SUCCESS: we got the coordinates!
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            if(status) status.textContent = `✅ Location found! Sorting by distance...`;

            // Reload this page with lat/lng in the URL
            // PHP will then calculate distances and sort the shops
            window.location.href = `shops.php?lat=${lat}&lng=${lng}`;
        },
        function(error) {
            // ERROR: user denied or something went wrong
            if(btn) {
                btn.textContent = '📍 Retry Location';
                btn.disabled = false;
            }
            if(status) status.textContent = '❌ Could not get location automatically. Please allow location access in your browser or click Retry.';
        }
    );
}
</script>
</body>
</html>
