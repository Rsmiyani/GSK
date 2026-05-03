<?php
/**
 * shopkeeper/analytics.php
 * ========================
 * SHOPKEEPER ANALYTICS DASHBOARD
 *
 * Production-level analytics page showing:
 *   - KPI summary cards (revenue, orders, AOV, conversion)
 *   - Daily revenue trend (line chart)
 *   - Order status breakdown (doughnut chart)
 *   - Top selling products (horizontal bar chart)
 *   - Hourly order heatmap (bar chart)
 *   - Delivery vs Pickup split (pie chart)
 */

$required_role = 'shopkeeper';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$shopId = $_SESSION['shop_id'] ?? 0;
if (!$shopId) { header("Location: dashboard.php"); exit(); }

$shopRes = mysqli_query($conn, "SELECT * FROM shops WHERE id = $shopId");
$shopInfo = mysqli_fetch_assoc($shopRes);

// ─── Date Range Filter ───────────────────────────────────────────────────────
$range = $_GET['range'] ?? '30'; // default 30 days
$dateFrom = date('Y-m-d', strtotime("-{$range} days"));
$dateTo = date('Y-m-d');

// ─── KPI Stats ───────────────────────────────────────────────────────────────
$r = mysqli_query($conn, "SELECT COUNT(*) c, COALESCE(SUM(total_amount),0) rev FROM orders WHERE shop_id=$shopId AND status='completed' AND DATE(created_at) >= '$dateFrom'");
$kpi = mysqli_fetch_assoc($r);
$completedOrders = $kpi['c'];
$totalRevenue = $kpi['rev'];
$avgOrderValue = $completedOrders > 0 ? $totalRevenue / $completedOrders : 0;

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM orders WHERE shop_id=$shopId AND DATE(created_at) >= '$dateFrom'");
$allOrdersCount = mysqli_fetch_assoc($r)['c'];
$completionRate = $allOrdersCount > 0 ? round(($completedOrders / $allOrdersCount) * 100, 1) : 0;

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM orders WHERE shop_id=$shopId AND status='cancelled' AND DATE(created_at) >= '$dateFrom'");
$cancelledOrders = mysqli_fetch_assoc($r)['c'];
$cancelRate = $allOrdersCount > 0 ? round(($cancelledOrders / $allOrdersCount) * 100, 1) : 0;

// Today vs Yesterday comparison
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$r = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) rev FROM orders WHERE shop_id=$shopId AND status='completed' AND DATE(created_at)='$today'");
$todayRev = mysqli_fetch_assoc($r)['rev'];
$r = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) rev FROM orders WHERE shop_id=$shopId AND status='completed' AND DATE(created_at)='$yesterday'");
$yesterdayRev = mysqli_fetch_assoc($r)['rev'];
$revChange = $yesterdayRev > 0 ? round((($todayRev - $yesterdayRev) / $yesterdayRev) * 100, 1) : ($todayRev > 0 ? 100 : 0);

// ─── Daily Revenue Trend (last N days) ───────────────────────────────────────
$dailyRevenue = [];
$res = mysqli_query($conn,
    "SELECT DATE(created_at) AS day, COALESCE(SUM(total_amount),0) AS rev, COUNT(*) AS cnt
     FROM orders WHERE shop_id=$shopId AND status='completed' AND DATE(created_at) >= '$dateFrom'
     GROUP BY DATE(created_at) ORDER BY day"
);
while ($row = mysqli_fetch_assoc($res)) { $dailyRevenue[] = $row; }

// Fill missing days with 0
$dailyData = [];
for ($d = strtotime($dateFrom); $d <= strtotime($dateTo); $d += 86400) {
    $key = date('Y-m-d', $d);
    $dailyData[$key] = ['day' => $key, 'rev' => 0, 'cnt' => 0];
}
foreach ($dailyRevenue as $dr) { $dailyData[$dr['day']] = $dr; }
$dailyData = array_values($dailyData);

// ─── Order Status Breakdown ──────────────────────────────────────────────────
$statusBreakdown = [];
$res = mysqli_query($conn,
    "SELECT status, COUNT(*) cnt FROM orders WHERE shop_id=$shopId AND DATE(created_at) >= '$dateFrom' GROUP BY status"
);
while ($row = mysqli_fetch_assoc($res)) { $statusBreakdown[$row['status']] = $row['cnt']; }

// ─── Top Selling Products ────────────────────────────────────────────────────
$topProducts = [];
$res = mysqli_query($conn,
    "SELECT p.name, SUM(oi.quantity) AS qty, SUM(oi.quantity * oi.price) AS revenue
     FROM order_items oi
     JOIN orders o ON oi.order_id = o.id
     JOIN products p ON oi.product_id = p.id
     WHERE o.shop_id=$shopId AND o.status='completed' AND DATE(o.created_at) >= '$dateFrom'
     GROUP BY p.id ORDER BY qty DESC LIMIT 8"
);
while ($row = mysqli_fetch_assoc($res)) { $topProducts[] = $row; }

// ─── Hourly Distribution ────────────────────────────────────────────────────
$hourlyData = array_fill(0, 24, 0);
$res = mysqli_query($conn,
    "SELECT HOUR(created_at) AS hr, COUNT(*) cnt FROM orders
     WHERE shop_id=$shopId AND DATE(created_at) >= '$dateFrom'
     GROUP BY HOUR(created_at)"
);
while ($row = mysqli_fetch_assoc($res)) { $hourlyData[(int)$row['hr']] = $row['cnt']; }

// ─── Delivery vs Pickup ─────────────────────────────────────────────────────
$deliveryCount = 0; $pickupCount = 0;
$res = mysqli_query($conn,
    "SELECT order_type, COUNT(*) cnt FROM orders WHERE shop_id=$shopId AND DATE(created_at) >= '$dateFrom' GROUP BY order_type"
);
while ($row = mysqli_fetch_assoc($res)) {
    if ($row['order_type'] === 'delivery') $deliveryCount = $row['cnt'];
    else $pickupCount = $row['cnt'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - <?= htmlspecialchars($shopInfo['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .analytics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 24px; }
        .kpi-card { background: var(--card-bg); border-radius: 14px; padding: 22px 24px; border: 1px solid var(--border); box-shadow: 0 2px 8px rgba(0,0,0,0.04); transition: transform 0.2s; }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
        .kpi-label { font-size: 0.78rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .kpi-value { font-size: 1.8rem; font-weight: 800; color: var(--text-dark); line-height: 1.2; }
        .kpi-change { font-size: 0.78rem; font-weight: 600; margin-top: 6px; }
        .kpi-change.up { color: var(--success); }
        .kpi-change.down { color: var(--danger); }
        .kpi-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-bottom: 12px; }
        .charts-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px; }
        .chart-card { background: var(--card-bg); border-radius: 14px; padding: 24px; border: 1px solid var(--border); box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .chart-card h3 { font-size: 1rem; font-weight: 700; margin-bottom: 16px; color: var(--text-dark); }
        .chart-card canvas { max-height: 300px; }
        .range-tabs { display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; }
        .range-tab { padding: 7px 16px; border-radius: 20px; font-size: .82rem; font-weight: 600; text-decoration: none; border: 1px solid var(--border); color: var(--text-muted); background: white; transition: all .2s; }
        .range-tab.active, .range-tab:hover { background: var(--accent); color: white; border-color: var(--accent); }
        .product-list { list-style: none; padding: 0; }
        .product-list li { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 0.88rem; }
        .product-list li:last-child { border-bottom: none; }
        .product-rank { width: 28px; height: 28px; border-radius: 50%; background: rgba(233,30,140,0.1); color: var(--accent); font-weight: 700; font-size: 0.75rem; display: flex; align-items: center; justify-content: center; margin-right: 12px; flex-shrink: 0; }
        .product-info { flex: 1; }
        .product-info .name { font-weight: 600; color: var(--text-dark); }
        .product-info .qty { font-size: 0.75rem; color: var(--text-muted); }
        .product-rev { font-weight: 700; color: var(--accent); white-space: nowrap; }
        @media(max-width:768px) { .charts-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="dashboard-body">

<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="../assets/logo/image.png" alt="GSK Logo">
        <div><h2><?= htmlspecialchars(substr($shopInfo['name'],0,20)) ?></h2><span>Shopkeeper Portal</span></div>
    </div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Management</span>
        <a href="dashboard.php"><span class="nav-icon">🏠</span> Dashboard</a>
        <a href="products.php"><span class="nav-icon">🎂</span> My Products</a>
        <a href="orders.php"><span class="nav-icon">📦</span> Orders</a>
        <a href="analytics.php" class="active"><span class="nav-icon">📊</span> Analytics</a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php"><span>🚪</span> Logout</a></div>
</aside>

<div class="main-content">
    <div class="topbar">
        <div class="topbar-title">
            <h1>📊 Analytics</h1>
            <p><?= htmlspecialchars($shopInfo['name']) ?> • Last <?= $range ?> days</p>
        </div>
        <div class="topbar-user">
            <div class="user-info"><strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong><span>Shopkeeper</span></div>
            <div class="avatar"><?= strtoupper(substr($_SESSION['user_name'],0,1)) ?></div>
        </div>
    </div>

    <div class="page-body">
        <!-- Date Range Filter -->
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
                <div class="kpi-change <?= $revChange >= 0 ? 'up' : 'down' ?>">
                    <?= $revChange >= 0 ? '▲' : '▼' ?> <?= abs($revChange) ?>% vs yesterday
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:rgba(59,130,246,0.12);">📦</div>
                <div class="kpi-label">Total Orders</div>
                <div class="kpi-value"><?= $allOrdersCount ?></div>
                <div class="kpi-change up"><?= $completedOrders ?> completed</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:rgba(233,30,140,0.12);">🧾</div>
                <div class="kpi-label">Avg Order Value</div>
                <div class="kpi-value">₹<?= number_format($avgOrderValue, 0) ?></div>
                <div class="kpi-change up"><?= $completionRate ?>% completion rate</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:rgba(239,68,68,0.12);">❌</div>
                <div class="kpi-label">Cancelled</div>
                <div class="kpi-value"><?= $cancelledOrders ?></div>
                <div class="kpi-change <?= $cancelRate <= 10 ? 'up' : 'down' ?>"><?= $cancelRate ?>% cancel rate</div>
            </div>
        </div>

        <!-- Revenue Trend + Status Breakdown -->
        <div class="charts-row">
            <div class="chart-card">
                <h3>📈 Daily Revenue Trend</h3>
                <canvas id="revenueChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>📊 Order Status</h3>
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <!-- Top Products + Hourly Heatmap -->
        <div class="charts-row">
            <div class="chart-card">
                <h3>🏆 Top Selling Products</h3>
                <?php if (count($topProducts) > 0): ?>
                <ul class="product-list">
                    <?php foreach ($topProducts as $i => $p): ?>
                    <li>
                        <span class="product-rank"><?= $i + 1 ?></span>
                        <div class="product-info">
                            <div class="name"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="qty"><?= $p['qty'] ?> units sold</div>
                        </div>
                        <span class="product-rev">₹<?= number_format($p['revenue'], 0) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div class="empty-state" style="padding:30px 0;"><p>No sales data yet.</p></div>
                <?php endif; ?>
            </div>
            <div class="chart-card">
                <h3>⏰ Orders by Hour</h3>
                <canvas id="hourlyChart"></canvas>
                <div style="margin-top:16px; border-top:1px solid var(--border); padding-top:16px;">
                    <h3 style="font-size:0.95rem; margin-bottom:12px;">🚚 Delivery vs Pickup</h3>
                    <canvas id="typeChart" style="max-height:180px;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const chartColors = {
    pink: '#e91e8c', blue: '#3b82f6', green: '#10b981', orange: '#f59e0b',
    red: '#ef4444', purple: '#8b5cf6', pinkLight: 'rgba(233,30,140,0.1)'
};

// ─── Revenue Trend Chart ─────────────────────────────────────────────────────
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(fn($d) => date('d M', strtotime($d['day'])), $dailyData)) ?>,
        datasets: [{
            label: 'Revenue (₹)',
            data: <?= json_encode(array_map(fn($d) => (float)$d['rev'], $dailyData)) ?>,
            borderColor: chartColors.pink, backgroundColor: chartColors.pinkLight,
            borderWidth: 2.5, fill: true, tension: 0.4, pointRadius: 2, pointHoverRadius: 6
        },{
            label: 'Orders',
            data: <?= json_encode(array_map(fn($d) => (int)$d['cnt'], $dailyData)) ?>,
            borderColor: chartColors.blue, backgroundColor: 'transparent',
            borderWidth: 2, borderDash: [5,5], tension: 0.4, pointRadius: 0, yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true, interaction: { intersect: false, mode: 'index' },
        plugins: { legend: { position: 'top', labels: { usePointStyle: true, padding: 16 } } },
        scales: {
            y:  { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: v => '₹' + v } },
            y1: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { stepSize: 1 } },
            x:  { grid: { display: false }, ticks: { maxTicksLimit: 10 } }
        }
    }
});

// ─── Status Breakdown ────────────────────────────────────────────────────────
const statusData = <?= json_encode($statusBreakdown) ?>;
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: Object.keys(statusData).map(s => s.charAt(0).toUpperCase() + s.slice(1)),
        datasets: [{ data: Object.values(statusData),
            backgroundColor: [chartColors.orange, chartColors.blue, chartColors.green, '#059669', chartColors.red],
            borderWidth: 0, hoverOffset: 8
        }]
    },
    options: {
        responsive: true, cutout: '65%',
        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12, font: { size: 11 } } } }
    }
});

// ─── Hourly Chart ────────────────────────────────────────────────────────────
new Chart(document.getElementById('hourlyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(fn($h) => sprintf('%02d:00', $h), range(0, 23))) ?>,
        datasets: [{ label: 'Orders', data: <?= json_encode(array_values($hourlyData)) ?>,
            backgroundColor: (ctx) => ctx.raw > 0 ? chartColors.pink + '88' : 'rgba(0,0,0,0.05)',
            borderRadius: 4, borderSkipped: false
        }]
    },
    options: {
        responsive: true, plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,0.04)' } }, x: { grid: { display: false }, ticks: { maxTicksLimit: 8, font: { size: 10 } } } }
    }
});

// ─── Delivery vs Pickup ──────────────────────────────────────────────────────
new Chart(document.getElementById('typeChart'), {
    type: 'doughnut',
    data: {
        labels: ['🚚 Delivery', '🏪 Pickup'],
        datasets: [{ data: [<?= $deliveryCount ?>, <?= $pickupCount ?>],
            backgroundColor: [chartColors.pink, chartColors.purple], borderWidth: 0, hoverOffset: 6
        }]
    },
    options: { responsive: true, cutout: '55%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 10, font: { size: 11 } } } } }
});
</script>
</body>
</html>
