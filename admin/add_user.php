<?php
/**
 * admin/add_user.php
 * ==================
 * ADD NEW USER PAGE (Admin or Shopkeeper only)
 *
 * Admins use this page to create accounts for:
 *   - Shopkeepers (to manage a specific branch)
 *   - Other Admins (for trusted staff)
 *
 * Customer accounts are NOT created here — customers sign up themselves
 * on the public signup.php page.
 *
 * HOW IT WORKS:
 *   1. Validate all form fields (name, email, password, role)
 *   2. Check if the email is already registered
 *   3. Hash the password with bcrypt
 *   4. Insert the new user into the database
 */

// ─── Access Control ────────────────────────────────────────────────────────────
$required_role = 'admin';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$message = ''; // Holds result message (success or error)

// ─── Handle Form Submission ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // The hidden input 'action' must equal 'create_user' for us to proceed
    if ($action === 'create_user') {

        // ── Step 1: Read and clean all form fields ─────────────────────────
        $newName     = trim($_POST['new_name']     ?? '');
        $newEmail    = trim($_POST['new_email']    ?? '');
        $newPhone    = trim($_POST['new_phone']    ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        // Role is set by the JavaScript tab selector (hidden input #selectedRole)
        $newRole     = trim($_POST['new_role']     ?? 'shopkeeper');

        // ── Step 2: Validate fields ────────────────────────────────────────
        if (empty($newName) || empty($newEmail) || empty($newPassword)) {
            $message = 'error:Name, email, and password are required.';
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            // filter_var checks that the email format is valid (e.g., a@b.com)
            $message = 'error:Enter a valid email address.';
        } elseif (strlen($newPassword) < 6) {
            // Enforce minimum password length
            $message = 'error:Password must be at least 6 characters.';
        } elseif (!in_array($newRole, ['shopkeeper','admin'])) {
            // Only allow these two roles — reject anything else (security check)
            $message = 'error:Invalid role selected.';
        } else {
            // ── Step 3: Check if email already exists ──────────────────────
            $chk = mysqli_prepare($conn, "SELECT id FROM users WHERE email=?");
            mysqli_stmt_bind_param($chk,'s',$newEmail);
            mysqli_stmt_execute($chk);
            mysqli_stmt_store_result($chk); // Buffer result to use num_rows

            if (mysqli_stmt_num_rows($chk) > 0) {
                $message = 'error:This email is already registered.';
            } else {
                // ── Step 4: Hash the password ──────────────────────────────
                // NEVER store plain text passwords.
                // password_hash() uses bcrypt which is a one-way secure hash.
                $hash = password_hash($newPassword, PASSWORD_BCRYPT);

                // ── Step 5: Insert the new user ────────────────────────────
                $ins  = mysqli_prepare($conn,"INSERT INTO users (name,email,phone,password,role) VALUES (?,?,?,?,?)");
                mysqli_stmt_bind_param($ins,'sssss',$newName,$newEmail,$newPhone,$hash,$newRole);
                if(mysqli_stmt_execute($ins)) {
                    // ucfirst() capitalizes the role: "shopkeeper" → "Shopkeeper"
                    $message = "success:".ucfirst($newRole)." account created for $newEmail!";
                } else {
                    $message = 'error:Insert failed. '.mysqli_error($conn);
                }
            }
        }
    }
}

// ─── Parse the message into type and text ────────────────────────────────────
// "success:Account created!" → $msgType='success', $msgText='Account created!'
[$msgType,$msgText] = $message ? explode(':',$message,2) : ['',''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add User - GSK Bakery</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<style>
/* ── Role Selector Tabs ─────────────────────────────────────────────────────
   Two clickable card-style tabs: Shopkeeper and Admin.
   Clicking a tab updates the hidden input 'new_role' via JavaScript.         */
.role-tabs { display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
.role-tab {
    flex: 1; min-width: 80px; padding: 12px 6px;
    border: 2px solid var(--border); border-radius: 10px;
    cursor: pointer; text-align: center; font-size: 0.78rem;
    font-weight: 600; background: white; color: var(--text-muted);
    transition: all 0.2s;
}
.role-tab:hover  { border-color: var(--accent); color: var(--accent); }
.role-tab.active { border-color: var(--accent); background: rgba(233,30,140,0.08); color: var(--accent); }
/* Admin tab uses a gold/amber color instead of the default pink */
.role-tab.admin-tab.active { border-color: #f59e0b; background: rgba(245,158,11,0.08); color: #92400e; }
.role-tab .tab-icon { display: block; font-size: 1.3rem; margin-bottom: 4px; }

/* ── Context Note Under Role Tabs ───────────────────────────────────────────
   A short info box that changes based on which role tab is selected.         */
.role-note { font-size: 0.75rem; padding: 9px 12px; border-radius: 6px; margin-top: 8px; display: none; }
.role-note.show { display: block; } /* Made visible by JavaScript */
.note-admin      { background: rgba(245,158,11,0.10); border-left: 3px solid #f59e0b; color: #92400e; }
.note-shopkeeper { background: rgba(59,130,246,0.10); border-left: 3px solid #3b82f6; color: #1e40af; }
</style>
</head>
<body class="dashboard-body">

<!-- Dark overlay for mobile sidebar -->
<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- ═══ SIDEBAR ═══════════════════════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand"><img src="../assets/logo/image.png" alt="GSK"><div><h2>Admin Panel</h2><span>Ghanshyam Bakery</span></div></div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Administration</span>
        <a href="dashboard.php"><span class="nav-icon">🏠</span> Dashboard</a>
        <a href="shops.php"><span class="nav-icon">🏪</span> Manage Shops</a>
        <!-- Users is active since we're adding a user -->
        <a href="users.php" class="active"><span class="nav-icon">👥</span> Manage Users</a>
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
                <h1>➕ Add New User</h1>
                <p><a href="users.php" style="color:var(--accent);text-decoration:none;">&larr; Back to Users</a></p>
            </div>
        </div>
        <div class="topbar-user">
            <div class="user-info"><strong><?=htmlspecialchars($_SESSION['user_name'])?></strong><span>Admin</span></div>
            <div class="avatar"><?=strtoupper(substr($_SESSION['user_name'],0,1))?></div>
        </div>
    </div>

    <div class="page-body">
        <!-- ── Success / Error Alert ──────────────────────────────────────── -->
        <?php if ($msgText): ?>
        <div class="alert alert-<?= $msgType ?>">
            <?= $msgType === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($msgText) ?>
        </div>
        <?php endif; ?>

        <!-- ── Create User Form ──────────────────────────────────────────────
             Centered, max 600px wide for comfortable reading.                 -->
        <div class="form-card" style="max-width: 600px; margin: 0 auto;">
            <h2>➕ Create New User</h2>
            <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:18px;">
                Add a shopkeeper or another admin. (Customer creation has been removed)
            </p>

            <form method="POST" id="createForm">
                <!-- Hidden: tells PHP which action to perform -->
                <input type="hidden" name="action" value="create_user">
                <!-- Hidden: stores the selected role; updated by JavaScript setRole() -->
                <input type="hidden" name="new_role" id="selectedRole" value="shopkeeper">

                <!-- ── Role Selector ─────────────────────────────────────────
                     Two tabs (Shopkeeper / Admin). Clicking a tab:
                       1. Calls setRole() in JavaScript
                       2. Updates the hidden #selectedRole input value
                       3. Switches which context note is visible              -->
                <div class="form-group">
                    <label>Select Role *</label>
                    <div class="role-tabs">
                        <!-- Shopkeeper tab — active by default -->
                        <div class="role-tab active" id="tab-shopkeeper" onclick="setRole('shopkeeper')">
                            <span class="tab-icon">🏪</span>Shopkeeper
                        </div>
                        <!-- Admin tab -->
                        <div class="role-tab admin-tab" id="tab-admin" onclick="setRole('admin')">
                            <span class="tab-icon">🔑</span>Admin
                        </div>
                    </div>
                    <!-- Shopkeeper note is shown by default (class "show") -->
                    <div class="role-note note-shopkeeper show" id="note-shopkeeper">
                        🏪 Manages one shop branch — products &amp; orders. Assign their shop in Manage Shops.
                    </div>
                    <!-- Admin note is hidden by default, shown when Admin tab is clicked -->
                    <div class="role-note note-admin" id="note-admin">
                        ⚠️ <strong>Full admin access.</strong> Only create this for trusted people.
                    </div>
                </div>

                <!-- Full Name field (persists value on error using $_POST) -->
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="new_name" value="<?= htmlspecialchars($_POST['new_name'] ?? '') ?>" placeholder="e.g. Suresh Patel" required>
                </div>

                <!-- Email Address field -->
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="new_email" value="<?= htmlspecialchars($_POST['new_email'] ?? '') ?>" placeholder="e.g. suresh@example.com" required>
                </div>

                <!-- Phone field — optional -->
                <div class="form-group">
                    <label>Phone <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
                    <input type="tel" name="new_phone" value="<?= htmlspecialchars($_POST['new_phone'] ?? '') ?>" placeholder="9876543210">
                </div>

                <!-- Temporary password — the user can change it after logging in -->
                <div class="form-group">
                    <label>Temporary Password *</label>
                    <input type="text" name="new_password" value="" placeholder="Must be at least 6 characters" required>
                    <p style="font-size:0.75rem;margin-top:6px;color:var(--text-muted);">
                        They can change this password later after logging in.
                    </p>
                </div>

                <!-- Submit button -->
                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:10px;">Create Account</button>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * toggleSidebar()
 * ===============
 * Opens or closes the mobile sidebar.
 */
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
}

/**
 * setRole(role)
 * =============
 * Called when a role tab is clicked.
 * Updates:
 *   1. The hidden 'new_role' input (so PHP knows which role to save)
 *   2. Which tab has the 'active' class (visual highlight)
 *   3. Which context note box is visible
 *
 * @param {string} role - 'shopkeeper' or 'admin'
 */
function setRole(role) {
    // Update the hidden form input that gets submitted
    document.getElementById('selectedRole').value = role;

    // Remove 'active' from all tabs, then add it only to the clicked one
    document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + role).classList.add('active');

    // Hide all role notes, then show only the one matching the selected role
    document.querySelectorAll('.role-note').forEach(n => n.classList.remove('show'));
    const note = document.getElementById('note-' + role);
    if (note) note.classList.add('show');
}
</script>
</body>
</html>