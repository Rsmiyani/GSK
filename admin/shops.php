<?php
/**
 * admin/shops.php - SHOP BRANCH MANAGEMENT
 * Admin can add, edit, delete, and toggle shop branches.
 * Each shop has GPS coordinates (lat/lng) for the nearby stores feature.
 * NEW: "Detect My Location" button auto-fills lat/lng using browser GPS.
 */
$required_role = 'admin';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$message = '';
$editShop = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';

    if ($action === 'save') {
        $sid      = (int)($_POST['shop_id'] ?? 0);
        $name     = trim($_POST['name']     ?? '');
        $address  = trim($_POST['address']  ?? '');
        $phone    = trim($_POST['phone']    ?? '');
        $lat      = (float)($_POST['lat']   ?? 0);
        $lng      = (float)($_POST['lng']   ?? 0);
        $ownerId  = (int)($_POST['owner_id']?? 0);
        $active   = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name) || empty($address) || $lat == 0 || $lng == 0) {
            $message = 'error:Name, address, latitude, and longitude are required.';
        } elseif ($sid === 0) {
            $s = mysqli_prepare($conn,"INSERT INTO shops (name,address,phone,lat,lng,owner_id,is_active) VALUES (?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($s,'sssddii',$name,$address,$phone,$lat,$lng,$ownerId,$active);
            mysqli_stmt_execute($s) ? $message='success:Shop branch added!' : $message='error:Failed to add.';
        } else {
            $s = mysqli_prepare($conn,"UPDATE shops SET name=?,address=?,phone=?,lat=?,lng=?,owner_id=?,is_active=? WHERE id=?");
            mysqli_stmt_bind_param($s,'sssddiii',$name,$address,$phone,$lat,$lng,$ownerId,$active,$sid);
            mysqli_stmt_execute($s) ? $message='success:Shop updated!' : $message='error:Update failed.';
        }
    } elseif ($action === 'delete') {
        $sid = (int)($_POST['shop_id'] ?? 0);
        $s = mysqli_prepare($conn,"DELETE FROM shops WHERE id=?");
        mysqli_stmt_bind_param($s,'i',$sid);
        mysqli_stmt_execute($s) ? $message='success:Shop deleted.' : $message='error:Delete failed.';
    } elseif ($action === 'toggle') {
        $sid = (int)($_POST['shop_id'] ?? 0);
        mysqli_query($conn,"UPDATE shops SET is_active=NOT is_active WHERE id=$sid");
        $message='success:Status toggled.';
    }
}

if (isset($_GET['edit'])) {
    $editId  = (int)$_GET['edit'];
    $res     = mysqli_query($conn,"SELECT * FROM shops WHERE id=$editId");
    $editShop= mysqli_fetch_assoc($res);
}

$shops = mysqli_query($conn,"SELECT s.*,u.name AS owner_name FROM shops s LEFT JOIN users u ON s.owner_id=u.id ORDER BY s.name");
$shopkeepers = mysqli_query($conn,"SELECT id,name FROM users WHERE role='shopkeeper' ORDER BY name");
[$msgType,$msgText] = $message ? explode(':',$message,2) : ['',''];
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Manage Shops - GSK Bakery</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<style>
/* Full-width layout — table always takes 100% */
.page-grid { display: flex; flex-direction: column; gap: 24px; }

/* Detect Location button */
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

/* Status indicator below the button */
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

/* Highlight fields when auto-filled */
@keyframes highlight-pulse {
    0%   { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(233,30,140,0.2); }
    100% { border-color: var(--border); box-shadow: none; }
}
.auto-filled { animation: highlight-pulse 1.5s ease forwards; }
</style>
</head><body class="dashboard-body">

<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand"><img src="../assets/logo/image.png" alt="logo"><div><h2>Admin Panel</h2><span>Ghanshyam Bakery</span></div></div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Administration</span>
        <a href="dashboard.php"><span class="nav-icon">🏠</span> Dashboard</a>
        <a href="shops.php" class="active"><span class="nav-icon">🏪</span> Manage Shops</a>
        <a href="users.php"><span class="nav-icon">👥</span> Manage Users</a>
        <a href="orders.php"><span class="nav-icon">📦</span> All Orders</a>
        <a href="analytics.php"><span class="nav-icon">📊</span> Analytics</a>
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
                <h1>🏪 Manage Shops</h1>
                <p>Add or edit bakery branches</p>
            </div>
        </div>
        <div class="topbar-user">
            <div class="user-info"><strong><?=htmlspecialchars($_SESSION['user_name'])?></strong><span>Admin</span></div>
            <div class="avatar"><?=strtoupper(substr($_SESSION['user_name'],0,1))?></div>
        </div>
    </div>
    <div class="page-body">
        <?php if($msgText):?><div class="alert alert-<?=$msgType?>"><?=$msgType==='success'?'✅':'❌'?> <?=htmlspecialchars($msgText)?></div><?php endif;?>
        <div class="page-grid">
            <!-- Shops Table -->
            <div class="table-card">
                <div class="table-card-header">
                    <h2>All Branches (<?=mysqli_num_rows($shops)?>)</h2>
                    <a href="add_shop.php" class="btn btn-primary btn-sm">+ Add Branch</a>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <colgroup>
                            <col style="width:35%">
                            <col style="width:25%">
                            <col style="width:15%">
                            <col style="width:13%">
                            <col style="width:12%">
                        </colgroup>
                        <thead><tr>
                            <th>Branch Name</th>
                            <th class="col-address">Address</th>
                            <th class="col-owner">Owner</th>
                            <th class="col-status">Status</th>
                            <th class="col-actions">Actions</th>
                        </tr></thead>
                    <tbody>
                    <?php mysqli_data_seek($shops,0); while($s=mysqli_fetch_assoc($shops)):?>
                    <tr>
                        <td>
                            <strong><?=htmlspecialchars($s['name'])?></strong>
                            <div style="font-size:.72rem;color:var(--text-muted)">📞 <?=htmlspecialchars($s['phone']??'N/A')?></div>
                            <div style="font-size:.72rem;color:var(--text-muted)">📍 <?=$s['lat']?>, <?=$s['lng']?></div>
                        </td>
                        <td style="font-size:.83rem"><?=htmlspecialchars($s['address'])?></td>
                        <td><?=htmlspecialchars($s['owner_name']??'Unassigned')?></td>
                        <td class="col-status">
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="shop_id" value="<?=$s['id']?>">
                                <button type="submit" class="badge <?=$s['is_active']?'badge-active':'badge-inactive'?>" style="cursor:pointer;border:none;"><?=$s['is_active']?'✅ Active':'❌ Inactive'?></button>
                            </form>
                        </td>
                        <td class="col-actions actions">
                            <div class="actions-wrap">
                                <a href="shops.php?edit=<?=$s['id']?>" class="btn btn-outline btn-sm">✏️</a>
                                <form method="POST" onsubmit="return confirm('Delete this shop branch? All its products will also be deleted!')" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="shop_id" value="<?=$s['id']?>">
                                    <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile;?>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Edit Form -->
            <?php if($editShop): ?>
            <div class="form-card">
                <h2>✏️ Edit Branch</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="shop_id" value="<?=$editShop['id']??0?>">
                    <div class="form-group"><label>Branch Name *</label><input type="text" name="name" value="<?=htmlspecialchars($editShop['name']??'')?>" placeholder="e.g. Ghanshyam Bakery - Main Branch" required></div>
                    <div class="form-group"><label>Address *</label><textarea name="address" rows="2" placeholder="Full address with pincode..." style="width:100%;padding:11px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;resize:vertical;" required><?=htmlspecialchars($editShop['address']??'')?></textarea></div>
                    <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?=htmlspecialchars($editShop['phone']??'')?>" placeholder="9111111111"></div>
                    
                    <div class="form-group">
                        <label>GPS Coordinates * <small style="color:var(--text-muted)">(latitude & longitude)</small></label>
                        <button type="button" class="detect-btn" id="detectBtn" onclick="detectLocation()">📍 Detect My Location Automatically</button>
                        <div id="locStatus" class="location-status"></div>
                        <div style="display:flex;gap:12px;margin-top:10px;">
                            <input type="number" step="any" name="lat" id="latInput" value="<?=$editShop['lat']??''?>" required>
                            <input type="number" step="any" name="lng" id="lngInput" value="<?=$editShop['lng']??''?>" required>
                        </div>
                    </div>

                    <div class="form-group"><label>Assign Shopkeeper</label>
                        <select name="owner_id">
                            <option value="0">-- Select Shopkeeper --</option>
                            <?php mysqli_data_seek($shopkeepers,0); while($sk=mysqli_fetch_assoc($shopkeepers)):?>
                                <option value="<?=$sk['id']?>" <?=($editShop['owner_id']==$sk['id'])?'selected':''?>><?=htmlspecialchars($sk['name'])?></option>
                            <?php endwhile;?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" name="is_active" id="activeChk" value="1" <?=$editShop['is_active']?'checked':''?> style="width:18px;height:18px;">
                        <label for="activeChk" style="margin:0;cursor:pointer;">Active</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width:100%;">Update Branch</button>
                    <a href="shops.php" class="btn btn-outline" style="width:100%;text-align:center;margin-top:10px;display:block;">Cancel</a>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
/**
 * detectLocation()
 * ================
 * Uses the browser's built-in navigator.geolocation API to get the
 * current GPS coordinates (latitude & longitude) of the device.
 *
 * On success:
 *   - Fills the lat/lng input fields automatically
 *   - Shows a green success message
 *   - Provides a Google Maps link to visually verify the location
 *   - Animates the fields so admin knows they were auto-filled
 *
 * On error:
 *   - Shows a red error message explaining what went wrong
 *   - Re-enables the button so admin can try again
 */
function detectLocation() {
    const btn    = document.getElementById('detectBtn');
    const status = document.getElementById('locationStatus');
    const latInput = document.getElementById('latInput');
    const lngInput = document.getElementById('lngInput');
    const mapsDiv  = document.getElementById('mapsLink');
    const mapsAnchor = document.getElementById('mapsAnchor');

    // ── Check if browser supports geolocation ──────────────────────────────
    if (!navigator.geolocation) {
        showStatus('error', '❌ Your browser does not support location detection. Please enter coordinates manually.');
        return;
    }

    // ── Show detecting state ───────────────────────────────────────────────
    btn.disabled    = true;
    btn.innerHTML   = '⏳ Detecting location...';
    showStatus('detecting', '🔍 Requesting location permission... Please allow access in the browser popup.');
    mapsDiv.style.display = 'none';

    // ── Request GPS coordinates from the browser ───────────────────────────
    // The browser will ask the user to allow/deny location access
    navigator.geolocation.getCurrentPosition(

        // SUCCESS callback — runs when location is successfully obtained
        function(position) {
            // Extract latitude and longitude from the position object
            // toFixed(8) gives 8 decimal places for maximum GPS precision
            const lat = position.coords.latitude.toFixed(8);
            const lng = position.coords.longitude.toFixed(8);
            const acc = Math.round(position.coords.accuracy); // accuracy in meters

            // Auto-fill the form fields
            latInput.value = lat;
            lngInput.value = lng;

            // Animate the fields to show they were auto-filled
            [latInput, lngInput].forEach(input => {
                input.classList.remove('auto-filled');
                void input.offsetWidth; // trigger reflow so animation restarts
                input.classList.add('auto-filled');
            });

            // Show success message with accuracy info
            showStatus('success',
                `✅ Location detected! Lat: ${lat}, Lng: ${lng} &nbsp;|&nbsp; Accuracy: ±${acc} meters`
            );

            // Show Google Maps verification link
            const mapsUrl = `https://www.google.com/maps?q=${lat},${lng}&z=17`;
            mapsAnchor.href = mapsUrl;
            mapsDiv.style.display = 'block';

            // Re-enable button
            btn.disabled  = false;
            btn.innerHTML = '✅ Location Detected — Click to Re-detect';
        },

        // ERROR callback — runs if location access is denied or fails
        function(error) {
            // error.code tells us WHY it failed
            const errorMessages = {
                1: '❌ Location access denied. Please allow location in your browser settings and try again.',
                2: '❌ Location unavailable. Make sure your device GPS is on.',
                3: '❌ Location request timed out. Please try again.',
            };
            const msg = errorMessages[error.code] || '❌ Unknown error. Please enter coordinates manually.';

            showStatus('error', msg);
            btn.disabled  = false;
            btn.innerHTML = '📍 Detect My Location Automatically';
        },

        // Options: high accuracy mode, 10 second timeout
        {
            enableHighAccuracy: true,  // Use GPS chip if available (more accurate)
            timeout: 10000,            // Give up after 10 seconds
            maximumAge: 0              // Always get fresh location (no cache)
        }
    );
}

/**
 * showStatus(type, message)
 * =========================
 * Shows a colored status message below the detect button.
 * type: 'detecting' (blue) | 'success' (green) | 'error' (red)
 */
function showStatus(type, message) {
    const status = document.getElementById('locationStatus');
    status.className = 'location-status ' + type; // applies CSS class
    status.innerHTML = message;
}
</script>

</body></html>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
}
</script>
