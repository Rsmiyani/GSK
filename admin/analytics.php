<?php
/**
 * admin/analytics.php
 * ====================
 * ADMIN ANALYTICS DASHBOARD
 *
 * Platform-wide analytics showing:
 *   - KPI cards (revenue, orders, shops, customers)
 *   - Daily revenue trend across all shops (stacked area chart)
 *   - Revenue by shop (bar chart)
 *   - Order status breakdown (doughnut)
 *   - Top performing shops ranking table
 *   - Customer growth trend
 *   - Delivery vs Pickup split
 */

$required_role = 'admin';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

// ─── Date Range Filter ───────────────────────────────────────────────────────
$range = $_GET['range'] ?? '30';
$dateFrom = date('Y-m-d', strtotime("-{$range} days"));
$dateTo = date('Y-m-d');

// ─── Platform-Wide KPIs ──────────────────────────────────────────────────────
$r = mysqli_query($conn, "SELECT COUNT(*) c, COALESCE(SUM(total_amount),0) rev FROM orders WHERE status='completed' AND DATE(created_at) >= '$dateFrom'");
$kpi = mysqli_fetch_assoc($r);
$totalRevenue = $kpi['rev'];
$completedOrders = $kpi['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM orders WHERE DATE(created_at) >= '$dateFrom'");
$allOrdersCount = mysqli_fetch_assoc($r)['c'];
$avgOrderValue = $completedOrders > 0 ? $totalRevenue / $completedOrders : 0;

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM shops WHERE is_active=1");
$activeShops = mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE role='customer'");
$totalCustomers = mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(DISTINCT customer_id) c FROM orders WHERE DATE(created_at) >= '$dateFrom'");
$activeCustomers = mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM orders WHERE status='cancelled' AND DATE(created_at) >= '$dateFrom'");
$cancelledOrders = mysqli_fetch_assoc($r)['c'];
$cancelRate = $allOrdersCount > 0 ? round(($cancelledOrders / $allOrdersCount) * 100, 1) : 0;

// Today vs Yesterday revenue comparison
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$r = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) rev FROM orders WHERE status='completed' AND DATE(created_at)='$today'");
$todayRev = mysqli_fetch_assoc($r)['rev'];
$r = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) rev FROM orders WHERE status='completed' AND DATE(created_at)='$yesterday'");
$yesterdayRev = mysqli_fetch_assoc($r)['rev'];
$revChange = $yesterdayRev > 0 ? round((($todayRev - $yesterdayRev) / $yesterdayRev) * 100, 1) : ($todayRev > 0 ? 100 : 0);

// ─── Daily Revenue by Shop (for stacked chart) ──────────────────────────────
$shops = [];
$res = mysqli_query($conn, "SELECT id, name FROM shops WHERE is_active=1 ORDER BY name");
while ($row = mysqli_fetch_assoc($res)) { $shops[] = $row; }

// Build date array
$dates = [];
for ($d = strtotime($dateFrom); $d <= strtotime($dateTo); $d += 86400) {
    $dates[] = date('Y-m-d', $d);
}

// Get daily revenue per shop
$shopDailyData = [];
foreach ($shops as $shop) {
    $shopDailyData[$shop['id']] = array_fill_keys($dates, 0);
}
$res = mysqli_query($conn,
    "SELECT DATE(created_at) AS day, shop_id, COALESCE(SUM(total_amount),0) AS rev
     FROM orders WHERE status='completed' AND DATE(created_at) >= '$dateFrom'
     GROUP BY DATE(created_at), shop_id ORDER BY day"
);
while ($row = mysqli_fetch_assoc($res)) {
    if (isset($shopDailyData[$row['shop_id']])) {
        $shopDailyData[$row['shop_id']][$row['day']] = (float)$row['rev'];
    }
}

// Total daily revenue
$totalDailyRevenue = array_fill_keys($dates, 0);
$totalDailyOrders = array_fill_keys($dates, 0);
$res = mysqli_query($conn,
    "SELECT DATE(created_at) AS day, COALESCE(SUM(total_amount),0) rev, COUNT(*) cnt
     FROM orders WHERE status='completed' AND DATE(created_at) >= '$dateFrom'
     GROUP BY DATE(created_at) ORDER BY day"
);
while ($row = mysqli_fetch_assoc($res)) {
    $totalDailyRevenue[$row['day']] = (float)$row['rev'];
    $totalDailyOrders[$row['day']] = (int)$row['cnt'];
}

// ─── Revenue by Shop (for bar chart) ─────────────────────────────────────────
$shopRevenue = [];
$res = mysqli_query($conn,
    "SELECT s.name, COALESCE(SUM(o.total_amount),0) AS rev, COUNT(o.id) AS cnt
     FROM shops s LEFT JOIN orders o ON s.id=o.shop_id AND o.status='completed' AND DATE(o.created_at) >= '$dateFrom'
     WHERE s.is_active=1 GROUP BY s.id ORDER BY rev DESC"
);
while ($row = mysqli_fetch_assoc($res)) { $shopRevenue[] = $row; }

// ─── Order Status Breakdown ──────────────────────────────────────────────────
$statusBreakdown = [];
$res = mysqli_query($conn,
    "SELECT status, COUNT(*) cnt FROM orders WHERE DATE(created_at) >= '$dateFrom' GROUP BY status"
);
while ($row = mysqli_fetch_assoc($res)) { $statusBreakdown[$row['status']] = $row['cnt']; }

// ─── Top Shops Table ─────────────────────────────────────────────────────────
$topShops = [];
$res = mysqli_query($conn,
    "SELECT s.id, s.name, s.address,
            COUNT(o.id) AS total_orders,
            SUM(CASE WHEN o.status='completed' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN o.status='cancelled' THEN 1 ELSE 0 END) AS cancelled,
            COALESCE(SUM(CASE WHEN o.status='completed' THEN o.total_amount ELSE 0 END),0) AS revenue
     FROM shops s
     LEFT JOIN orders o ON s.id = o.shop_id AND DATE(o.created_at) >= '$dateFrom'
     WHERE s.is_active=1
     GROUP BY s.id ORDER BY revenue DESC"
);
while ($row = mysqli_fetch_assoc($res)) { $topShops[] = $row; }

// ─── Delivery vs Pickup ─────────────────────────────────────────────────────
$deliveryCount = 0; $pickupCount = 0;
$res = mysqli_query($conn,
    "SELECT order_type, COUNT(*) cnt FROM orders WHERE DATE(created_at) >= '$dateFrom' GROUP BY order_type"
);
while ($row = mysqli_fetch_assoc($res)) {
    if ($row['order_type'] === 'delivery') $deliveryCount = $row['cnt'];
    else $pickupCount = $row['cnt'];
}

// ─── New Customers per Day ───────────────────────────────────────────────────
$newCustomers = array_fill_keys($dates, 0);
$res = mysqli_query($conn,
    "SELECT DATE(created_at) AS day, COUNT(*) cnt FROM users
     WHERE role='customer' AND DATE(created_at) >= '$dateFrom'
     GROUP BY DATE(created_at) ORDER BY day"
);
while ($row = mysqli_fetch_assoc($res)) {
    if (isset($newCustomers[$row['day']])) $newCustomers[$row['day']] = (int)$row['cnt'];
}

// Chart colors per shop
$chartPalette = ['#e91e8c','#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444','#06b6d4','#84cc16','#ec4899','#6366f1'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .analytics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 24px; }
        .kpi-card { background: var(--card-bg); border-radius: 14px; padding: 20px 22px; border: 1px solid var(--border); box-shadow: 0 2px 8px rgba(0,0,0,0.04); transition: transform 0.2s; }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
        .kpi-label { font-size: 0.75rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .kpi-value { font-size: 1.6rem; font-weight: 800; color: var(--text-dark); line-height: 1.2; }
        .kpi-sub { font-size: 0.75rem; font-weight: 600; margin-top: 4px; }
        .kpi-sub.up { color: var(--success); }
        .kpi-sub.down { color: var(--danger); }
        .kpi-sub.neutral { color: var(--text-muted); }
        .kpi-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-bottom: 10px; }
        .charts-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px; }
        .chart-card { background: var(--card-bg); border-radius: 14px; padding: 24px; border: 1px solid var(--border); box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .chart-card h3 { font-size: 1rem; font-weight: 700; margin-bottom: 16px; color: var(--text-dark); }
        .chart-card canvas { max-height: 300px; }
        .range-tabs { display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; }
        .range-tab { padding: 7px 16px; border-radius: 20px; font-size: .82rem; font-weight: 600; text-decoration: none; border: 1px solid var(--border); color: var(--text-muted); background: white; transition: all .2s; }
        .range-tab.active, .range-tab:hover { background: var(--accent); color: white; border-color: var(--accent); }
        .shop-rank-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .shop-rank-table th { background: var(--body-bg); color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.5px; padding: 10px 14px; text-align: left; border-bottom: 2px solid var(--border); }
        .shop-rank-table td { padding: 12px 14px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        .shop-rank-table tr:hover td { background: rgba(233,30,140,0.03); }
        .rank-badge { width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem; }
        .rank-1 { background: #fef3c7; color: #92400e; }
        .rank-2 { background: #e5e7eb; color: #374151; }
        .rank-3 { background: #fce7d6; color: #9a3412; }
        .rank-other { background: var(--body-bg); color: var(--text-muted); }
        .mini-bar { height: 6px; border-radius: 3px; background: var(--body-bg); overflow: hidden; min-width: 80px; }
        .mini-bar-fill { height: 100%; border-radius: 3px; background: var(--accent); transition: width 0.5s; }
        @media(max-width:768px) { .charts-row { grid-template-columns: 1fr; } .analytics-grid { grid-template-columns: repeat(2, 1fr); } }
    </style>
</head>
<body class="dashboard-body">

<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand"><img src="../assets/logo/image.png" alt="logo"><div><h2>Admin Panel</h2><span>Ghanshyam Bakery</span></div></div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Administration</span>
        <a href="dashboard.php"><span class="nav-icon">🏠</span> Dashboard</a>
        <a href="shops.php"><span class="nav-icon">🏪</span> Manage Shops</a>
        <a href="users.php"><span class="nav-icon">👥</span> Manage Users</a>
        <a href="orders.php"><span class="nav-icon">📦</span> All Orders</a>
        <a href="analytics.php" class="active"><span class="nav-icon">📊</span> Analytics</a>
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
                <h1>📊 Platform Analytics</h1>
                <p>All shops combined • Last <?= $range ?> days</p>
            </div>
        </div>
        <div class="topbar-user">
            <div class="user-info"><strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong><span>Admin</span></div>
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_name'],0,1)) ?></div>
        </div>
    </div>

    <div class="page-body">
        <!-- Date Range -->
        <div class="range-tabs">
            <?php foreach ([7=>'7 Days', 15=>'15 Days', 30=>'30 Days', 90=>'90 Days', 365=>'1 Year'] as $d => $lbl): ?>
            <a href="analytics.php?range=<?= $d ?>" class="range-tab <?= $range == $d ? 'active' : '' ?>"><?= $lbl ?></a>
            <?php endforeach; ?>
        </div>

        <!-- KPI Cards -->
        <div class="analytics-grid">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:rgba(16,185,129,0.12);">💰</div>
                <div class="kpi-label">Total Revenue</div>
                <div class="kpi-value">₹<?= number_format($totalRevenue, 0) ?></div>
                <div class="kpi-sub <?= $revChange >= 0 ? 'up' : 'down' ?>"><?= $revChange >= 0 ? '▲' : '▼' ?> <?= abs($revChange) ?>% vs yesterday</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:rgba(59,130,246,0.12);">📦</div>
                <div class="kpi-label">Total Orders</div>
                <div class="kpi-value"><?= $allOrdersCount ?></div>
                <div class="kpi-sub up"><?= $completedOrders ?> completed</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:rgba(233,30,140,0.12);">🧾</div>
                <div class="kpi-label">Avg Order Value</div>
                <div class="kpi-value">₹<?= number_format($avgOrderValue, 0) ?></div>
                <div class="kpi-sub neutral"><?= $cancelRate ?>% cancel rate</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:rgba(139,92,246,0.12);">🏪</div>
                <div class="kpi-label">Active Shops</div>
                <div class="kpi-value"><?= $activeShops ?></div>
                <div class="kpi-sub neutral"><?= $activeCustomers ?> active customers</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:rgba(245,158,11,0.12);">👥</div>
                <div class="kpi-label">Total Customers</div>
                <div class="kpi-value"><?= $totalCustomers ?></div>
                <div class="kpi-sub up"><?= $activeCustomers ?> ordered recently</div>
            </div>
        </div>

        <!-- Revenue Trend + Status -->
        <div class="charts-row">
            <div class="chart-card">
                <h3>📈 Daily Revenue Trend (All Shops)</h3>
                <canvas id="revenueTrendChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>📊 Order Status</h3>
                <canvas id="statusChart" style="max-height:220px;"></canvas>
                <div style="margin-top:20px; border-top:1px solid var(--border); padding-top:16px;">
                    <h3 style="font-size:0.95rem; margin-bottom:12px;">🚚 Delivery vs Pickup</h3>
                    <canvas id="typeChart" style="max-height:180px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Revenue by Shop + Customer Growth -->
        <div class="charts-row">
            <div class="chart-card">
                <h3>🏪 Revenue by Shop</h3>
                <canvas id="shopRevenueChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>👥 New Customers</h3>
                <canvas id="customerChart" style="max-height:260px;"></canvas>
            </div>
        </div>

        <!-- Top Shops Table -->
        <div class="chart-card" style="margin-bottom:24px;">
            <h3>🏆 Shop Performance Ranking</h3>
            <div style="overflow-x:auto;">
            <table class="shop-rank-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Shop</th>
                        <th>Orders</th>
                        <th>Completed</th>
                        <th>Cancelled</th>
                        <th>Revenue</th>
                        <th>Share</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $maxRev = count($topShops) > 0 ? max(array_column($topShops, 'revenue')) : 1;
                foreach ($topShops as $i => $shop):
                    $pct = $maxRev > 0 ? round(($shop['revenue'] / $maxRev) * 100) : 0;
                    $rankClass = $i < 3 ? 'rank-' . ($i + 1) : 'rank-other';
                ?>
                <tr>
                    <td><span class="rank-badge <?= $rankClass ?>"><?= $i + 1 ?></span></td>
                    <td>
                        <strong><?= htmlspecialchars($shop['name']) ?></strong>
                        <div style="font-size:0.72rem;color:var(--text-muted);"><?= htmlspecialchars(substr($shop['address'],0,40)) ?></div>
                    </td>
                    <td><?= $shop['total_orders'] ?></td>
                    <td><span style="color:var(--success);font-weight:600;"><?= $shop['completed'] ?></span></td>
                    <td><span style="color:var(--danger);font-weight:600;"><?= $shop['cancelled'] ?></span></td>
                    <td><strong>₹<?= number_format($shop['revenue'], 0) ?></strong></td>
                    <td><div class="mini-bar"><div class="mini-bar-fill" style="width:<?= $pct ?>%;"></div></div></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<script>
const C = { pink:'#e91e8c', blue:'#3b82f6', green:'#10b981', orange:'#f59e0b', red:'#ef4444', purple:'#8b5cf6', cyan:'#06b6d4' };

// ─── Revenue Trend ───────────────────────────────────────────────────────────
new Chart(document.getElementById('revenueTrendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(fn($d) => date('d M', strtotime($d)), $dates)) ?>,
        datasets: [
            <?php foreach ($shops as $i => $shop): ?>
            {
                label: '<?= addslashes(substr($shop['name'],0,25)) ?>',
                data: <?= json_encode(array_values($shopDailyData[$shop['id']])) ?>,
                borderColor: '<?= $chartPalette[$i % count($chartPalette)] ?>',
                backgroundColor: '<?= $chartPalette[$i % count($chartPalette)] ?>18',
                borderWidth: 2, fill: true, tension: 0.4, pointRadius: 1, pointHoverRadius: 5
            },
            <?php endforeach; ?>
        ]
    },
    options: {
        responsive: true, interaction: { intersect: false, mode: 'index' },
        plugins: { legend: { position: 'top', labels: { usePointStyle: true, padding: 14, font: { size: 11 } } } },
        scales: {
            y: { beginAtZero: true, stacked: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: v => '₹' + v } },
            x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } }
        }
    }
});

// ─── Revenue by Shop ─────────────────────────────────────────────────────────
new Chart(document.getElementById('shopRevenueChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(fn($s) => substr($s['name'],0,20), $shopRevenue)) ?>,
        datasets: [{
            label: 'Revenue (₹)',
            data: <?= json_encode(array_map(fn($s) => (float)$s['rev'], $shopRevenue)) ?>,
            backgroundColor: <?= json_encode(array_map(fn($i) => $chartPalette[$i % count($chartPalette)] . 'CC', range(0, count($shopRevenue)-1))) ?>,
            borderRadius: 8, borderSkipped: false
        }]
    },
    options: {
        responsive: true, indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { callback: v => '₹' + v }, grid: { color: 'rgba(0,0,0,0.04)' } }, y: { grid: { display: false } } }
    }
});

// ─── Status Breakdown ────────────────────────────────────────────────────────
const sd = <?= json_encode($statusBreakdown) ?>;
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: Object.keys(sd).map(s => s.charAt(0).toUpperCase() + s.slice(1)),
        datasets: [{ data: Object.values(sd),
            backgroundColor: [C.orange, C.blue, C.green, '#059669', C.red], borderWidth: 0, hoverOffset: 8
        }]
    },
    options: { responsive: true, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 10, font: { size: 11 } } } } }
});

// ─── Delivery vs Pickup ──────────────────────────────────────────────────────
new Chart(document.getElementById('typeChart'), {
    type: 'doughnut',
    data: {
        labels: ['🚚 Delivery', '🏪 Pickup'],
        datasets: [{ data: [<?= $deliveryCount ?>, <?= $pickupCount ?>], backgroundColor: [C.pink, C.purple], borderWidth: 0 }]
    },
    options: { responsive: true, cutout: '55%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 10, font: { size: 11 } } } } }
});

// ─── Customer Growth ─────────────────────────────────────────────────────────
new Chart(document.getElementById('customerChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(fn($d) => date('d M', strtotime($d)), $dates)) ?>,
        datasets: [{ label: 'New Customers', data: <?= json_encode(array_values($newCustomers)) ?>,
            backgroundColor: C.purple + '88', borderRadius: 4, borderSkipped: false
        }]
    },
    options: {
        responsive: true, plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,0.04)' } }, x: { grid: { display: false }, ticks: { maxTicksLimit: 8, font: { size: 10 } } } }
    }
});

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
}
</script>
</body>
</html>
