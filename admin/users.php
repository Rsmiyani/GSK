<?php
/**
 * admin/users.php — USER MANAGEMENT (Fully Responsive)
 * Admin can CREATE, CHANGE ROLE, and DELETE user accounts.
 * New: hamburger sidebar, responsive table, stacked form on mobile.
 */
$required_role = 'admin';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Change Role ──────────────────────────────────────────────────────────
    if ($action === 'change_role') {
        $uid     = (int)($_POST['user_id'] ?? 0);
        $newRole = $_POST['new_role'] ?? '';
        if ($uid === (int)$_SESSION['user_id']) {
            $message = 'error:You cannot change your own role.';
        } elseif (in_array($newRole,['customer','shopkeeper','admin'])) {
            $s = mysqli_prepare($conn,"UPDATE users SET role=? WHERE id=?");
            mysqli_stmt_bind_param($s,'si',$newRole,$uid);
            mysqli_stmt_execute($s) ? $message='success:Role updated to '.ucfirst($newRole).'.' : $message='error:Update failed.';
        }

    // ── Delete User ──────────────────────────────────────────────────────────
    } elseif ($action === 'delete') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid === (int)$_SESSION['user_id']) {
            $message = 'error:You cannot delete your own account.';
        } else {
            $s = mysqli_prepare($conn,"DELETE FROM users WHERE id=?");
            mysqli_stmt_bind_param($s,'i',$uid);
            mysqli_stmt_execute($s) ? $message='success:User deleted.' : $message='error:Delete failed.';
        }
    }
}

// ─── Fetch Data ───────────────────────────────────────────────────────────────
$users = mysqli_query($conn,
    "SELECT *, (SELECT COUNT(*) FROM orders WHERE customer_id=users.id) AS order_count
     FROM users ORDER BY FIELD(role,'admin','shopkeeper','customer'), created_at DESC"
);
$cAdmin = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM users WHERE role='admin'"))['c'];
$cShop  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM users WHERE role='shopkeeper'"))['c'];
$cCust  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM users WHERE role='customer'"))['c'];

[$msgType,$msgText] = $message ? explode(':',$message,2) : ['',''];
$roleColors = ['admin'=>'badge-pending','shopkeeper'=>'badge-preparing','customer'=>'badge-active'];
$roleEmoji  = ['admin'=>'🔑','shopkeeper'=>'🏪','customer'=>'👤'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users - GSK Bakery</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<style>
/* ── Page-specific styles ─────────────────────────────────────────────────── */

/* Full-width single-column layout */
.users-layout {
    display: block;
    width: 100%;
}

/* Sticky create form on desktop */
.create-form-sticky { position: sticky; top: 84px; }

/* Role selector tabs */
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
.role-tab.admin-tab.active { border-color: #f59e0b; background: rgba(245,158,11,0.08); color: #92400e; }
.role-tab .tab-icon { display: block; font-size: 1.3rem; margin-bottom: 4px; }

/* Context note under role tabs */
.role-note { font-size: 0.75rem; padding: 9px 12px; border-radius: 6px; margin-top: 8px; display: none; }
.role-note.show { display: block; }
.note-admin { background: rgba(245,158,11,0.10); border-left: 3px solid #f59e0b; color: #92400e; }
.note-shopkeeper { background: rgba(59,130,246,0.10); border-left: 3px solid #3b82f6; color: #1e40af; }
.note-customer { background: rgba(16,185,129,0.10); border-left: 3px solid #10b981; color: #065f46; }

/* User row avatar circle */
.user-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), #c2186e);
    display: flex; align-items: center; justify-content: center;
    color: white; font-weight: 800; font-size: 0.88rem; flex-shrink: 0;
}

/* Password field wrapper for show/hide toggle */
.pwd-wrap { position: relative; }
.pwd-wrap input { padding-right: 44px; }
.pwd-toggle {
    position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
    border: none; background: none; cursor: pointer; font-size: 1rem;
    padding: 4px; line-height: 1;
}

/* ── Responsive overrides ─────────────────────────────────────────────────── */
@media (max-width: 1024px) {
    .users-layout { display: block; }
    .create-form-sticky { position: static; }
}
@media (max-width: 768px) {
    /* Show only essential columns on mobile */
    .col-phone, .col-orders, .col-joined { display: none; }
    .role-tabs { flex-direction: row; }
    .role-tab { min-width: 70px; padding: 10px 4px; font-size: 0.72rem; }
}
</style>
</head>
<body class="dashboard-body">

<!-- Dark overlay: tapping it closes the sidebar on mobile -->
<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- ═══ SIDEBAR ═══════════════════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="../assets/logo/image.png" alt="GSK">
        <div><h2>Admin Panel</h2><span>Ghanshyam Bakery</span></div>
    </div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Administration</span>
        <a href="dashboard.php"><span class="nav-icon">🏠</span> Dashboard</a>
        <a href="shops.php"><span class="nav-icon">🏪</span> Manage Shops</a>
        <a href="users.php" class="active"><span class="nav-icon">👥</span> Manage Users</a>
        <a href="orders.php"><span class="nav-icon">📦</span> All Orders</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../logout.php"><span>🚪</span> Logout</a>
    </div>
</aside>

<!-- ═══ MAIN CONTENT ══════════════════════════════════════════════════════ -->
<div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <!-- Hamburger: only visible on mobile -->
            <button class="hamburger" id="hamburger" onclick="toggleSidebar()" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
            <div class="topbar-title">
                <h1>👥 Manage Users</h1>
                <p>Create, manage roles &amp; delete accounts</p>
            </div>
        </div>
        <div class="topbar-user">
            <div class="user-info">
                <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                <span>Admin</span>
            </div>
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_name'],0,1)) ?></div>
        </div>
    </div>

    <div class="page-body">

        <!-- Alert message -->
        <?php if ($msgText): ?>
        <div class="alert alert-<?= $msgType ?>">
            <?= $msgType === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($msgText) ?>
        </div>
        <?php endif; ?>

        <!-- Role count cards -->
        <div class="stats-grid" style="margin-bottom:22px;">
            <div class="stat-card">
                <div class="stat-icon orange">🔑</div>
                <div class="stat-info"><h3><?= $cAdmin ?></h3><p>Admins</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">🏪</div>
                <div class="stat-info"><h3><?= $cShop ?></h3><p>Shopkeepers</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">👤</div>
                <div class="stat-info"><h3><?= $cCust ?></h3><p>Customers</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pink">👥</div>
                <div class="stat-info"><h3><?= $cAdmin+$cShop+$cCust ?></h3><p>Total Users</p></div>
            </div>
        </div>

        <!-- Two-column layout: table + form -->
        <div class="users-layout">

            <!-- ════ LEFT: Users Table ════════════════════════════════════ -->
            <div class="table-card">
                <div class="table-card-header">
                    <h2>All Users (<?= mysqli_num_rows($users) ?>)</h2>
                    <a href="add_user.php" class="btn btn-primary btn-sm">+ Add User</a>
                </div>

                <!-- Scrollable wrapper so table doesn't break on small screens -->
                <div class="table-responsive">
                    <table class="data-table">
                        <colgroup>
                            <col style="width:28%">
                            <col style="width:13%">
                            <col style="width:14%">
                            <col style="width:8%">
                            <col style="width:13%">
                            <col style="width:24%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th class="col-role">Role</th>
                                <th class="col-phone">Phone</th>
                                <th class="col-orders">Orders</th>
                                <th class="col-joined">Joined</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php mysqli_data_seek($users,0); while ($u = mysqli_fetch_assoc($users)): ?>
                        <tr>
                            <!-- User: avatar + name + email -->
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div class="user-avatar">
                                        <?= strtoupper(substr($u['name'],0,1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;font-size:.88rem;">
                                            <?= htmlspecialchars($u['name']) ?>
                                        </div>
                                        <div style="font-size:.75rem;color:var(--text-muted);">
                                            <?= htmlspecialchars($u['email']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>


                            <!-- Role Badge -->
                            <td>
                                <span class="badge <?= $roleColors[$u['role']] ?? '' ?>">
                                    <?= ($roleEmoji[$u['role']] ?? '') . ' ' . ucfirst($u['role']) ?>
                                </span>
                            </td>

                            <!-- Phone (hidden on mobile) -->
                            <td class="col-phone" style="font-size:.82rem;">
                                <?= htmlspecialchars($u['phone'] ?? '—') ?>
                            </td>

                            <!-- Orders count (hidden on mobile) -->
                            <td class="col-orders">
                                <span style="font-weight:700;"><?= $u['order_count'] ?></span>
                            </td>

                            <!-- Joined date (hidden on mobile) -->
                            <td class="col-joined" style="font-size:.78rem;white-space:nowrap;">
                                <?= date('d M Y', strtotime($u['created_at'])) ?>
                            </td>

                            <!-- Actions -->
                            <td class="col-actions actions">
                                <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                <div class="actions-wrap">
                                    <!-- Role change dropdown -->
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="change_role">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <select name="new_role"
                                                onchange="if(confirm('Change role to '+this.value+'?'))this.form.submit();else this.value='<?= $u['role'] ?>';"
                                                style="padding:5px 8px;border:1px solid var(--border);border-radius:6px;font-size:.75rem;cursor:pointer;background:white;">
                                            <option value="customer"   <?= $u['role']==='customer'   ?'selected':''?>><?= $roleEmoji['customer'] ?> Customer</option>
                                            <option value="shopkeeper" <?= $u['role']==='shopkeeper' ?'selected':''?>><?= $roleEmoji['shopkeeper'] ?> Shopkeeper</option>
                                            <option value="admin"      <?= $u['role']==='admin'      ?'selected':''?>><?= $roleEmoji['admin'] ?> Admin</option>
                                        </select>
                                    </form>
                                    <!-- Delete -->
                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($u['name'])) ?>\'s account permanently?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete user">🗑️</button>
                                    </form>
                                </div>
                                <?php else: ?>
                                <span style="font-size:.75rem;color:var(--text-muted);font-style:italic;">You</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div><!-- end .table-responsive -->
            </div>

        </div><!-- end .users-layout -->
    </div><!-- end .page-body -->
</div><!-- end .main-content -->

<script>
/* ─── Sidebar Toggle (hamburger menu for mobile) ─────────────────────────── */
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
}
</script>
</body>
</html>
