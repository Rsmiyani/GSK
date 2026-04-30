<?php
/**
 * admin/add_shop.php
 * Dedicated page to add a new shop branch.
 */
$required_role = 'admin';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';

    if ($action === 'save') {
        $name     = trim($_POST['name']     ?? '');
        $address  = trim($_POST['address']  ?? '');
        $phone    = trim($_POST['phone']    ?? '');
        $lat      = (float)($_POST['lat']   ?? 0);
        $lng      = (float)($_POST['lng']   ?? 0);
        $ownerId  = (int)($_POST['owner_id']?? 0);
        $active   = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name) || empty($address) || $lat == 0 || $lng == 0) {
            $message = 'error:Name, address, latitude, and longitude are required.';
        } else {
            $s = mysqli_prepare($conn,"INSERT INTO shops (name,address,phone,lat,lng,owner_id,is_active) VALUES (?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($s,'sssddii',$name,$address,$phone,$lat,$lng,$ownerId,$active);
            if (mysqli_stmt_execute($s)) {
                $message = 'success:Shop branch added successfully!';
            } else {
                $message = 'error:Failed to add shop branch.';
            }
        }
    }
}

$shopkeepers = mysqli_query($conn,"SELECT id,name FROM users WHERE role='shopkeeper' ORDER BY name");
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
        .location-status {
            font-size: 0.78rem;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 12px;
            display: none;
        }
        .location-status.detecting { display:block; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
        .location-status.success   { display:block; background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
        .location-status.error     { display:block; background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
        @keyframes highlight-pulse {
            0%   { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(233,30,140,0.2); }
            100% { border-color: var(--border); box-shadow: none; }
        }
        .auto-filled { animation: highlight-pulse 1.5s ease forwards; }
    </style>
</head>
<body class="dashboard-body">

<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand"><img src="../assets/logo/image.png" alt="logo"><div><h2>Admin Panel</h2><span>Ghanshyam Bakery</span></div></div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Administration</span>
        <a href="dashboard.php"><span class="nav-icon">🏠</span> Dashboard</a>
        <a href="shops.php" class="active"><span class="nav-icon">🏪</span> Manage Shops</a>
        <a href="users.php"><span class="nav-icon">👥</span> Manage Users</a>
        <a href="orders.php"><span class="nav-icon">📦</span> All Orders</a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php"><span>🚪</span> Logout</a></div>
</aside>

<div class="main-content">
    <div class="topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <button class="hamburger" id="hamburger" onclick="toggleSidebar()" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
            <div class="topbar-title">
                <h1>➕ Add New Branch</h1>
                <p><a href="shops.php" style="color:var(--accent);text-decoration:none;">&larr; Back to Shops</a></p>
            </div>
        </div>
        <div class="topbar-user">
            <div class="user-info"><strong><?=htmlspecialchars($_SESSION['user_name'])?></strong><span>Admin</span></div>
            <div class="avatar"><?=strtoupper(substr($_SESSION['user_name'],0,1))?></div>
        </div>
    </div>
    <div class="page-body">
        <?php if($msgText):?><div class="alert alert-<?=$msgType?>"><?=$msgType==='success'?'✅':'❌'?> <?=htmlspecialchars($msgText)?></div><?php endif;?>
        
        <div class="form-card" style="max-width: 600px; margin: 0 auto;">
            <h2>+ Add New Branch</h2>
            <form method="POST">
                <input type="hidden" name="action" value="save">
                
                <div class="form-group">
                    <label>Branch Name *</label>
                    <input type="text" name="name" placeholder="e.g. Ghanshyam Bakery - Main Branch" required>
                </div>
                
                <div class="form-group">
                    <label>Address *</label>
                    <textarea name="address" rows="2" placeholder="Full address with pincode..." style="width:100%;padding:11px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;resize:vertical;" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" placeholder="9111111111">
                </div>
                
                <div class="form-group">
                    <label>GPS Coordinates * <small style="color:var(--text-muted)">(latitude & longitude)</small></label>
                    <button type="button" class="detect-btn" id="detectBtn" onclick="detectLocation()">
                        📍 Detect My Location Automatically
                    </button>
                    <div id="locStatus" class="location-status"></div>
                    <div style="display:flex;gap:12px;margin-top:10px;">
                        <input type="number" step="any" name="lat" id="latInput" placeholder="Latitude (e.g. 28.53)" required>
                        <input type="number" step="any" name="lng" id="lngInput" placeholder="Longitude (e.g. 77.39)" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Assign Shopkeeper</label>
                    <select name="owner_id">
                        <option value="0">-- Select Shopkeeper --</option>
                        <?php while($sk = mysqli_fetch_assoc($shopkeepers)): ?>
                            <option value="<?= $sk['id'] ?>"><?= htmlspecialchars($sk['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group" style="display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" name="is_active" id="activeChk" value="1" checked style="width:18px;height:18px;accent-color:var(--accent);">
                    <label for="activeChk" style="margin:0;cursor:pointer;font-weight:600;">Active (Visible to customers)</label>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:10px;">Save Branch</button>
            </form>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
}

function detectLocation() {
    const btn = document.getElementById('detectBtn');
    const status = document.getElementById('locStatus');
    const latIn = document.getElementById('latInput');
    const lngIn = document.getElementById('lngInput');

    if (!navigator.geolocation) {
        status.className = 'location-status error';
        status.innerHTML = '❌ Geolocation is not supported by your browser.';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '⏳ Detecting...';
    status.className = 'location-status detecting';
    status.innerHTML = 'Please allow location access if prompted...';
    
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude.toFixed(6);
            const lng = position.coords.longitude.toFixed(6);
            const acc = Math.round(position.coords.accuracy || 0); // meters

            latIn.value = lat; lngIn.value = lng;

            latIn.classList.remove('auto-filled');
            lngIn.classList.remove('auto-filled');
            void latIn.offsetWidth;
            latIn.classList.add('auto-filled');
            lngIn.classList.add('auto-filled');

            // Show accuracy to the user and give a short interpretation
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

            btn.innerHTML = '📍 Update Location';
            btn.disabled = false;
        },
        (error) => {
            status.className = 'location-status error';
            switch(error.code) {
                case error.PERMISSION_DENIED: status.innerHTML = "❌ You denied the request for Geolocation."; break;
                case error.POSITION_UNAVAILABLE: status.innerHTML = "❌ Location information is unavailable."; break;
                case error.TIMEOUT: status.innerHTML = "❌ The request to get user location timed out."; break;
                default: status.innerHTML = "❌ An unknown error occurred."; break;
            }
            btn.innerHTML = '📍 Detect My Location Automatically';
            btn.disabled = false;
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}
</script>
</body>
</html>