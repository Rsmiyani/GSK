<?php
/**
 * customer/shops.php
 * ==================
 * NEARBY SHOPS PAGE - Sweet Artisans Theme
 */

$required_role = 'customer';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userInitial = strtoupper(substr($userName, 0, 1));

// ─── Haversine Distance Function ──────────────────────────────────────────────
function haversine($lat1, $lng1, $lat2, $lng2) {
    $R = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return round($R * $c, 2);
}

$userLat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$userLng = isset($_GET['lng']) ? (float)$_GET['lng'] : null;

// ─── Fetch All Active Shops ───────────────────────────────────────────────────
$result = mysqli_query($conn, "SELECT s.*, u.name AS owner_name FROM shops s LEFT JOIN users u ON s.owner_id = u.id WHERE s.is_active = 1");
$shops = [];
while ($row = mysqli_fetch_assoc($result)) {
    if ($userLat && $userLng) {
        $d = haversine($userLat, $userLng, $row['lat'], $row['lng']);
        $row['distance'] = $d;
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

if ($userLat && $userLng) {
    usort($shops, fn($a, $b) => $a['distance'] <=> $b['distance']);
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Find Shops - Ghanshyam Bakery & Live Cake Shop</title>
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
        
        <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
            <div>
                <h1 class="font-headline-lg text-amber-950">Nearby Branches</h1>
                <p class="text-body-md text-stone-500 mt-2" id="location-status">Pick up your fresh delights from these locations</p>
            </div>
            <button onclick="detectLocation()" id="locate-btn" class="bg-primary-container text-on-primary-container px-5 py-2 rounded-lg font-label-md hover:bg-secondary hover:text-white transition-colors flex items-center gap-2 shadow-sm">
                <span class="material-symbols-outlined text-sm">my_location</span> Sort by Distance
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (count($shops) > 0): ?>
                <?php foreach ($shops as $shop): ?>
                <!-- Branch Card -->
                <div class="bg-white rounded-xl p-6 border border-rose-50 ambient-shadow flex flex-col gap-4 group hover:-translate-y-1 transition-transform">
                    <div class="flex justify-between items-start">
                        <div class="bg-rose-50 p-3 rounded-lg group-hover:bg-primary-container transition-colors">
                            <span class="material-symbols-outlined text-rose-400 group-hover:text-on-primary-container transition-colors">storefront</span>
                        </div>
                        <?php if (isset($shop['distance_label'])): ?>
                            <span class="text-label-sm bg-surface-container px-3 py-1 rounded-full text-secondary font-bold">
                                <?= htmlspecialchars($shop['distance_label']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <h3 class="font-headline-sm text-xl text-amber-950 mb-1"><?= htmlspecialchars($shop['name']) ?></h3>
                        <p class="text-label-sm text-stone-500 mb-2 truncate" title="<?= htmlspecialchars($shop['address']) ?>">
                            <?= htmlspecialchars($shop['address']) ?>
                        </p>
                        <p class="text-xs text-stone-400 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">call</span> <?= htmlspecialchars($shop['phone'] ?? 'N/A') ?>
                        </p>
                    </div>
                    
                    <div class="flex items-center gap-2 text-label-sm mt-2">
                        <span class="text-green-600 font-bold flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-500"></span> Open Now</span>
                    </div>
                    
                    <div class="mt-auto pt-4 flex gap-3">
                        <a href="shop_detail.php?id=<?= $shop['id'] ?>" class="flex-1 text-center py-2 bg-secondary text-white rounded-lg font-label-md hover:bg-on-secondary-fixed-variant transition-colors">
                            Visit Shop
                        </a>
                        
                        <?php
                            $dest = urlencode($shop['lat'] . ',' . $shop['lng']);
                            if ($userLat && $userLng) {
                                $origin = urlencode($userLat . ',' . $userLng);
                                $mapsUrl = "https://www.google.com/maps/dir/?api=1&origin={$origin}&destination={$dest}&travelmode=driving";
                            } else {
                                $mapsUrl = "https://www.google.com/maps/dir/?api=1&destination={$dest}&travelmode=driving";
                            }
                        ?>
                        <a href="<?= $mapsUrl ?>" target="_blank" class="px-4 py-2 border border-secondary text-secondary rounded-lg flex items-center justify-center hover:bg-rose-50 transition-colors" title="Get Directions">
                            <span class="material-symbols-outlined text-[18px]">directions</span>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full bg-white rounded-xl py-16 px-8 text-center border border-rose-50 ambient-shadow">
                    <span class="material-symbols-outlined text-4xl text-stone-300 mb-4">store_off</span>
                    <h3 class="font-headline-sm text-amber-950 mb-2">No shops found</h3>
                    <p class="text-stone-500">Check back later for new branch openings.</p>
                </div>
            <?php endif; ?>
        </div>
        
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
document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('lat') || !urlParams.has('lng')) {
        detectLocation(false); // Silent auto-detect
    }
});

function detectLocation(showStatus = true) {
    const btn = document.getElementById('locate-btn');
    const status = document.getElementById('location-status');

    if (!navigator.geolocation) {
        if(showStatus && status) status.textContent = '❌ Location not supported.';
        return;
    }

    if(showStatus) {
        if(btn) { btn.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">refresh</span> Detecting...'; btn.disabled = true; }
        if(status) status.textContent = 'Requesting location...';
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            window.location.href = `shops.php?lat=${lat}&lng=${lng}`;
        },
        function(error) {
            if(showStatus) {
                if(btn) { btn.innerHTML = '<span class="material-symbols-outlined text-sm">my_location</span> Retry Location'; btn.disabled = false; }
                if(status) status.textContent = '❌ Could not get location. Please allow access.';
            }
        }
    );
}
</script>
</body></html>
