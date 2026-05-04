<?php
/**
 * admin/add_shop.php
 * ==================
 * ADD NEW SHOP BRANCH PAGE
 *
 * Admins use this dedicated page to create a new bakery branch.
 * It includes a "Detect My Location" button that uses the browser's
 * GPS to auto-fill the latitude and longitude fields.
 *
 * HOW IT WORKS:
 *   1. If the form is submitted (POST), validate and INSERT into shops table
 *   2. Load the list of shopkeepers to display in the dropdown
 *   3. Render the form — GPS detection happens via JavaScript
 */

// ─── Access Control ────────────────────────────────────────────────────────────
$required_role = 'admin';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$message = ''; // Will hold 'success:...' or 'error:...' after form submission

// ─── Handle Form Submission ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';

    // Only act if the hidden input named 'action' equals 'save'
    if ($action === 'save') {
        // ── Read and Clean Form Fields ─────────────────────────────────────────
        $name     = trim($_POST['name']     ?? '');   // Branch name (required)
        $address  = trim($_POST['address']  ?? '');   // Full address (required)
        $phone    = trim($_POST['phone']    ?? '');   // Contact phone (optional)
        $lat      = (float)($_POST['lat']   ?? 0);   // GPS latitude (required)
        $lng      = (float)($_POST['lng']   ?? 0);   // GPS longitude (required)
        $ownerId  = (int)($_POST['owner_id']?? 0);   // Assigned shopkeeper user ID (0 = none)
        // Checkbox: if ticked the shop is active (visible to customers), else inactive
        $active   = isset($_POST['is_active']) ? 1 : 0;

        // ── Validate Required Fields ───────────────────────────────────────────
        if (empty($name) || empty($address) || $lat == 0 || $lng == 0) {
            $message = 'error:Name, address, latitude, and longitude are required.';
        } else {
            // ── Insert New Shop into Database ──────────────────────────────────
            // Use a prepared statement to prevent SQL injection
            $s = mysqli_prepare($conn,"INSERT INTO shops (name,address,phone,lat,lng,owner_id,is_active) VALUES (?,?,?,?,?,?,?)");
            // 'sssddii': s=string, d=double(decimal), i=integer
            mysqli_stmt_bind_param($s,'sssddii',$name,$address,$phone,$lat,$lng,$ownerId,$active);
            if (mysqli_stmt_execute($s)) {
                $message = 'success:Shop branch added successfully!';
            } else {
                $message = 'error:Failed to add shop branch.';
            }
        }
    }
}

// ─── Load Shopkeepers for the Dropdown ────────────────────────────────────────
// Only users with role='shopkeeper' can be assigned to manage a shop
$shopkeepers = mysqli_query($conn,"SELECT id,name FROM users WHERE role='shopkeeper' ORDER BY name");

// ─── Parse Message into Type + Text ───────────────────────────────────────────
// $message format: "success:Branch added!" or "error:Something failed."
// explode splits on ':' so: $msgType='success', $msgText='Branch added!'
[$msgType,$msgText] = $message ? explode(':',$message,2) : ['',''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Add New Branch - GSK Bakery</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        /* ── GPS Detect Button ──────────────────────────────────────────────
           A dashed-border button that triggers location detection via JS.      */
        .detect-btn {
            width: 100%;
            padding: 11px;
            border: 2px dashed var(--accent);
            border-radius: 8px;
            background: rgba(233,30,140,0.05);
            color: var(--accent);
            font-weight: 700;
            font-size: 0.88rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 12px;
        }
        .detect-btn:hover { background: rgba(233,30,140,0.12); }
        .detect-btn:disabled { opacity: 0.6; cursor: not-allowed; }

        /* ── Status Box Under the Detect Button ────────────────────────────
           Hidden by default; shown with a class (detecting/success/error).    */
        .location-status {
            font-size: 0.78rem;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 12px;
            display: none; /* Hidden until JS sets a class */
        }
        .location-status.detecting { display:block; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
        .location-status.success   { display:block; background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
        .location-status.error     { display:block; background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }

        /* ── Auto-fill Animation ────────────────────────────────────────────
           When JS fills lat/lng fields automatically, a pink pulse animation
           briefly highlights them so the admin knows they were auto-filled.   */
        @keyframes highlight-pulse {
            0%   { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(233,30,140,0.2); }
            100% { border-color: var(--border); box-shadow: none; }
        }
        .auto-filled { animation: highlight-pulse 1.5s ease forwards; }
    </style>
</head>
<body class="dashboard-body">

<!-- Dark overlay for mobile sidebar -->
<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- ═══ SIDEBAR ═══════════════════════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand"><img src="../assets/logo/image.png" alt="logo"><div><h2>Admin Panel</h2><span>Ghanshyam Bakery</span></div></div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Administration</span>
        <a href="dashboard.php"><span class="nav-icon">🏠</span> Dashboard</a>
        <!-- Shops is highlighted as active since we're adding a shop -->
        <a href="shops.php" class="active"><span class="nav-icon">🏪</span> Manage Shops</a>
        <a href="users.php"><span class="nav-icon">👥</span> Manage Users</a>
        <a href="orders.php"><span class="nav-icon">📦</span> All Orders</a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php"><span>🚪</span> Logout</a></div>
</aside>

<!-- ═══ MAIN CONTENT ═════════════════════════════════════════════════════════ -->
<div class="main-content">
    <div class="topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <button class="hamburger" id="hamburger" onclick="toggleSidebar()" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
            <div class="topbar-title">
                <h1>➕ Add New Branch</h1>
                <!-- Back link to the shops list -->
                <p><a href="shops.php" style="color:var(--accent);text-decoration:none;">&larr; Back to Shops</a></p>
            </div>
        </div>
        <div class="topbar-user">
            <div class="user-info"><strong><?=htmlspecialchars($_SESSION['user_name'])?></strong><span>Admin</span></div>
            <div class="avatar"><?=strtoupper(substr($_SESSION['user_name'],0,1))?></div>
        </div>
    </div>

    <div class="page-body">
        <!-- ── Alert Message ──────────────────────────────────────────────────
             Only rendered if $msgText is not empty.
             Green ✅ for success, Red ❌ for error.                           -->
        <?php if($msgText):?><div class="alert alert-<?=$msgType?>"><?=$msgType==='success'?'✅':'❌'?> <?=htmlspecialchars($msgText)?></div><?php endif;?>

        <!-- ── Add Branch Form ────────────────────────────────────────────────
             Centered card with max-width of 600px for readability.            -->
        <div class="form-card" style="max-width: 600px; margin: 0 auto;">
            <h2>+ Add New Branch</h2>
            <form method="POST">
                <!-- Hidden field tells the PHP handler which action to perform -->
                <input type="hidden" name="action" value="save">

                <!-- Branch name field -->
                <div class="form-group">
                    <label>Branch Name *</label>
                    <input type="text" name="name" placeholder="e.g. Ghanshyam Bakery - Main Branch" required>
                </div>

                <!-- Address textarea (multi-line input) -->
                <div class="form-group">
                    <label>Address *</label>
                    <textarea name="address" rows="2" placeholder="Full address with pincode..." style="width:100%;padding:11px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;resize:vertical;" required></textarea>
                </div>

                <!-- Phone number (optional) -->
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" placeholder="9111111111">
                </div>

                <!-- GPS Coordinates section -->
                <div class="form-group">
                    <label>GPS Coordinates * <small style="color:var(--text-muted)">(latitude &amp; longitude)</small></label>
                    <!-- Clicking this button triggers the detectLocation() JavaScript function -->
                    <button type="button" class="detect-btn" id="detectBtn" onclick="detectLocation()">
                        📍 Detect My Location Automatically
                    </button>
                    <!-- Status box updated by JavaScript to show detecting / success / error -->
                    <div id="locStatus" class="location-status"></div>
                    <!-- Two side-by-side number inputs for lat and lng -->
                    <div style="display:flex;gap:12px;margin-top:10px;">
                        <input type="number" step="any" name="lat" id="latInput" placeholder="Latitude (e.g. 28.53)" required>
                        <input type="number" step="any" name="lng" id="lngInput" placeholder="Longitude (e.g. 77.39)" required>
                    </div>
                </div>

                <!-- Assign Shopkeeper dropdown -->
                <div class="form-group">
                    <label>Assign Shopkeeper</label>
                    <select name="owner_id">
                        <option value="0">-- Select Shopkeeper --</option>
                        <!-- Loop through all shopkeeper accounts to populate options -->
                        <?php while($sk = mysqli_fetch_assoc($shopkeepers)): ?>
                            <option value="<?= $sk['id'] ?>"><?= htmlspecialchars($sk['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Active/Inactive checkbox: checked = visible to customers -->
                <div class="form-group" style="display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" name="is_active" id="activeChk" value="1" checked style="width:18px;height:18px;accent-color:var(--accent);">
                    <label for="activeChk" style="margin:0;cursor:pointer;font-weight:600;">Active (Visible to customers)</label>
                </div>

                <!-- Submit button spans full width -->
                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:10px;">Save Branch</button>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * toggleSidebar()
 * ===============
 * Opens or closes the mobile sidebar by toggling CSS classes.
 */
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
}

/**
 * detectLocation()
 * ================
 * Uses the browser's built-in Geolocation API to get the device's
 * current GPS coordinates and auto-fill the lat/lng form fields.
 *
 * Browser Geolocation API: navigator.geolocation.getCurrentPosition()
 *   - Takes a success callback and an error callback
 *   - The browser shows a "Allow Location?" popup to the user
 *
 * Accuracy levels:
 *   > 200 m  → Poor (Wi-Fi based, indoor)
 *   50–200 m → Moderate (cell tower based)
 *   < 50 m   → Good (GPS chip active)
 */
function detectLocation() {
    // Get references to the HTML elements we'll update
    const btn    = document.getElementById('detectBtn');
    const status = document.getElementById('locStatus');
    const latIn  = document.getElementById('latInput');
    const lngIn  = document.getElementById('lngInput');

    // Check if this browser supports geolocation at all
    if (!navigator.geolocation) {
        status.className = 'location-status error';
        status.innerHTML = '❌ Geolocation is not supported by your browser.';
        return;
    }

    // ── Show "detecting" state ─────────────────────────────────────────────
    btn.disabled = true;                          // Prevent double-click
    btn.innerHTML = '⏳ Detecting...';
    status.className = 'location-status detecting';
    status.innerHTML = 'Please allow location access if prompted...';

    // ── Request GPS coordinates from the browser ───────────────────────────
    navigator.geolocation.getCurrentPosition(

        // SUCCESS: runs when we successfully get coordinates
        (position) => {
            // toFixed(6) rounds to 6 decimal places (accurate to ~0.1 meters)
            const lat = position.coords.latitude.toFixed(6);
            const lng = position.coords.longitude.toFixed(6);
            const acc = Math.round(position.coords.accuracy || 0); // accuracy in meters

            // Fill the form fields with the detected coordinates
            latIn.value = lat;
            lngIn.value = lng;

            // Restart the animation so the highlight plays even if already triggered
            latIn.classList.remove('auto-filled');
            lngIn.classList.remove('auto-filled');
            void latIn.offsetWidth; // Force browser reflow so animation restarts
            latIn.classList.add('auto-filled');
            lngIn.classList.add('auto-filled');

            // Show success message with GPS accuracy interpretation
            status.className = 'location-status success';
            let msg = `✅ Location detected — Lat: ${lat}, Lng: ${lng} (±${acc} m)`;
            if (acc > 200) {
                msg += ' — Accuracy poor; try again outdoors or allow GPS.';
            } else if (acc > 50) {
                msg += ' — Moderate accuracy; ok for approximate placement.';
            } else {
                msg += ' — Good accuracy.';
            }
            status.innerHTML = msg;

            // Re-enable the button with updated label
            btn.innerHTML = '📍 Update Location';
            btn.disabled = false;
        },

        // ERROR: runs if the user denies permission or location is unavailable
        (error) => {
            status.className = 'location-status error';
            // error.code tells us exactly why it failed
            switch(error.code) {
                case error.PERMISSION_DENIED:    status.innerHTML = "❌ You denied the request for Geolocation."; break;
                case error.POSITION_UNAVAILABLE: status.innerHTML = "❌ Location information is unavailable."; break;
                case error.TIMEOUT:              status.innerHTML = "❌ The request to get user location timed out."; break;
                default:                         status.innerHTML = "❌ An unknown error occurred."; break;
            }
            btn.innerHTML = '📍 Detect My Location Automatically';
            btn.disabled = false;
        },

        // Options passed to getCurrentPosition:
        {
            enableHighAccuracy: true, // Use GPS chip if available (more precise than Wi-Fi)
            timeout: 10000,           // Give up after 10 seconds
            maximumAge: 0             // Don't use a cached location; always get fresh data
        }
    );
}
</script>
</body>
</html>