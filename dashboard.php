<?php
require_once '../includes/auth.php';
if ($_SESSION['role'] !== 'farmer') {
    redirect('../index.php');
}

$farmer_id = $_SESSION['user_id'];

// ---------- STATS ----------
$total_batches = $conn->query("SELECT COUNT(*) FROM batches WHERE farmer_id = $farmer_id")->fetch_row()[0] ?? 0;
$total_orders = $conn->query("SELECT COUNT(*) FROM orders o JOIN batches b ON o.batch_id = b.id WHERE b.farmer_id = $farmer_id")->fetch_row()[0] ?? 0;
$pending_orders = $conn->query("SELECT COUNT(*) FROM orders o JOIN batches b ON o.batch_id = b.id WHERE b.farmer_id = $farmer_id AND o.status = 'pending'")->fetch_row()[0] ?? 0;
$delivered_orders = $conn->query("SELECT COUNT(*) FROM orders o JOIN batches b ON o.batch_id = b.id WHERE b.farmer_id = $farmer_id AND o.status = 'delivered'")->fetch_row()[0] ?? 0;
$total_revenue = $conn->query("SELECT SUM(o.total_price) FROM orders o JOIN batches b ON o.batch_id = b.id WHERE b.farmer_id = $farmer_id AND o.status = 'delivered'")->fetch_row()[0] ?? 0;

$current_month = date('Y-m');
$prev_month = date('Y-m', strtotime('-1 month'));
$revenue_this_month = $conn->query("SELECT SUM(o.total_price) FROM orders o JOIN batches b ON o.batch_id = b.id WHERE b.farmer_id = $farmer_id AND o.status = 'delivered' AND DATE_FORMAT(o.order_date, '%Y-%m') = '$current_month'")->fetch_row()[0] ?? 0;
$revenue_prev_month = $conn->query("SELECT SUM(o.total_price) FROM orders o JOIN batches b ON o.batch_id = b.id WHERE b.farmer_id = $farmer_id AND o.status = 'delivered' AND DATE_FORMAT(o.order_date, '%Y-%m') = '$prev_month'")->fetch_row()[0] ?? 0;
$revenue_growth = ($revenue_prev_month > 0) ? round(($revenue_this_month - $revenue_prev_month) / $revenue_prev_month * 100, 1) : 100;

// ---------- LOSS STATS ----------
$loss_stats = $conn->query("
    SELECT 
        COUNT(CASE WHEN DATEDIFF(CURDATE(), hatch_date) BETWEEN 31 AND 57 AND status = 'available' THEN 1 END) as loss_count,
        COUNT(CASE WHEN DATEDIFF(CURDATE(), hatch_date) BETWEEN 58 AND 64 AND status = 'available' THEN 1 END) as doubled_count,
        COUNT(CASE WHEN DATEDIFF(CURDATE(), hatch_date) > 65 AND status = 'available' THEN 1 END) as critical_count,
        COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), hatch_date) BETWEEN 31 AND 57 AND status = 'available' THEN quantity END), 0) as loss_birds,
        COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), hatch_date) > 65 AND status = 'available' THEN quantity END), 0) as critical_birds
    FROM batches 
    WHERE farmer_id = $farmer_id
")->fetch_assoc();

// ---------- REVENUE TREND ----------
$revenue_trend = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('M Y', strtotime("-$i months"));
    $month_query = date('Y-m', strtotime("-$i months"));
    $result = $conn->query("SELECT SUM(o.total_price) as total FROM orders o JOIN batches b ON o.batch_id = b.id WHERE b.farmer_id = $farmer_id AND o.status = 'delivered' AND DATE_FORMAT(o.order_date, '%Y-%m') = '$month_query'");
    $revenue_trend['labels'][] = $month;
    $revenue_trend['values'][] = (float)($result->fetch_assoc()['total'] ?? 0);
}

// ---------- ORDER STATUS ----------
$status_counts = [];
$statuses = ['pending', 'confirmed', 'in_transit', 'delivered', 'cancelled'];
foreach ($statuses as $status) {
    $count = $conn->query("SELECT COUNT(*) FROM orders o JOIN batches b ON o.batch_id = b.id WHERE b.farmer_id = $farmer_id AND o.status = '$status'")->fetch_row()[0];
    $status_counts[$status] = $count;
}

// ---------- RECENT ORDERS ----------
$recent_orders = $conn->query("
    SELECT o.*, u.name as buyer_name, u.phone as buyer_phone, b.breed
    FROM orders o
    JOIN batches b ON o.batch_id = b.id
    JOIN users u ON o.buyer_id = u.id
    WHERE b.farmer_id = $farmer_id
    ORDER BY o.order_date DESC
    LIMIT 10
");

// ---------- TOP BUYERS ----------
$top_buyers = $conn->query("
    SELECT u.name, u.phone, COUNT(o.id) as order_count, SUM(o.total_price) as total_spent
    FROM orders o
    JOIN batches b ON o.batch_id = b.id
    JOIN users u ON o.buyer_id = u.id
    WHERE b.farmer_id = $farmer_id AND o.status = 'delivered'
    GROUP BY u.id
    ORDER BY total_spent DESC
    LIMIT 5
");

$page_title = "Farmer Dashboard";
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
    <span class="badge bg-success"><?= date('l, d M Y') ?></span>
</div>

<!-- Loss Alerts -->
<?php if ($loss_stats['loss_count'] > 0): ?>
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> <strong>⚠️ Loss Alert:</strong> You have <strong><?= $loss_stats['loss_count'] ?></strong> batch(es) (<?= $loss_stats['loss_birds'] ?> birds) aged 31-57 days. <a href="my_batches.php" class="alert-link">Sell them now!</a></div>
<?php endif; ?>
<?php if ($loss_stats['critical_count'] > 0): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-octagon"></i> <strong>🚨 CRITICAL Loss Alert:</strong> You have <strong><?= $loss_stats['critical_count'] ?></strong> batch(es) (<?= $loss_stats['critical_birds'] ?> birds) aged 65+ days. <a href="my_batches.php" class="alert-link">Act immediately!</a></div>
<?php endif; ?>

<!-- Loss Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center shadow-sm border-warning"><div class="card-body"><h2 class="text-warning"><?= $loss_stats['loss_count'] ?></h2><p class="text-muted mb-0">Batches in Loss (31-57 days)</p><small class="text-muted"><?= $loss_stats['loss_birds'] ?> birds</small></div></div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm border-info"><div class="card-body"><h2 class="text-info"><?= $loss_stats['doubled_count'] ?></h2><p class="text-muted mb-0">Batches Doubled (58-64 days)</p><small class="text-muted">Price × 2</small></div></div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm border-danger"><div class="card-body"><h2 class="text-danger"><?= $loss_stats['critical_count'] ?></h2><p class="text-muted mb-0">Critical Loss (65+ days)</p><small class="text-muted"><?= $loss_stats['critical_birds'] ?> birds</small></div></div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body"><div class="d-flex justify-content-between"><div><h6 class="text-muted text-uppercase fw-bold">Batches</h6><h2 class="mb-0"><?= $total_batches ?></h2></div><div class="bg-success bg-opacity-10 rounded p-3"><i class="bi bi-box-seam fs-2 text-success"></i></div></div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body"><div class="d-flex justify-content-between"><div><h6 class="text-muted text-uppercase fw-bold">Orders</h6><h2 class="mb-0"><?= $total_orders ?></h2><span class="badge bg-warning text-dark">Pending: <?= $pending_orders ?></span></div><div class="bg-warning bg-opacity-10 rounded p-3"><i class="bi bi-cart fs-2 text-warning"></i></div></div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body"><div class="d-flex justify-content-between"><div><h6 class="text-muted text-uppercase fw-bold">Revenue</h6><h2 class="mb-0">Tsh <?= number_format($total_revenue, 0) ?></h2><small class="text-<?= $revenue_growth >= 0 ? 'success' : 'danger' ?>"><i class="bi bi-arrow-<?= $revenue_growth >= 0 ? 'up' : 'down' ?>"></i> <?= abs($revenue_growth) ?>%</small></div><div class="bg-info bg-opacity-10 rounded p-3"><i class="bi bi-currency-dollar fs-2 text-info"></i></div></div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body"><div class="d-flex justify-content-between"><div><h6 class="text-muted text-uppercase fw-bold">Delivered</h6><h2 class="mb-0"><?= $delivered_orders ?></h2><small class="text-muted">Completed orders</small></div><div class="bg-primary bg-opacity-10 rounded p-3"><i class="bi bi-check2-circle fs-2 text-primary"></i></div></div></div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><h5 class="mb-0"><i class="bi bi-graph-up"></i> Revenue Trend (Last 6 Months)</h5></div>
            <div class="card-body"><canvas id="revenueChart" height="180"></canvas></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><h5 class="mb-0"><i class="bi bi-pie-chart"></i> Order Status</h5></div>
            <div class="card-body"><canvas id="statusChart" height="180"></canvas></div>
        </div>
    </div>
</div>

<!-- Top Buyers & Quick Actions -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center"><h5 class="mb-0"><i class="bi bi-trophy"></i> Top 5 Buyers</h5><a href="orders.php" class="btn btn-sm btn-outline-secondary">View All</a></div>
            <div class="card-body">
                <?php if ($top_buyers->num_rows > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php while ($b = $top_buyers->fetch_assoc()): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div><strong><?= htmlspecialchars($b['name']) ?></strong><br><small class="text-muted"><?= $b['phone'] ?? 'No phone' ?></small></div>
                                <span><span class="badge bg-secondary rounded-pill me-1"><?= $b['order_count'] ?> orders</span><span class="badge bg-success rounded-pill">Tsh <?= number_format($b['total_spent'], 0) ?></span></span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?><p class="text-muted">No buyers yet.</p><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5></div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a href="add_batch.php" class="btn btn-success btn-sm"><i class="bi bi-plus-circle"></i> Add Batch</a>
                    <a href="my_batches.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-box-seam"></i> My Batches</a>
                    <a href="orders.php" class="btn btn-outline-warning btn-sm"><i class="bi bi-cart"></i> Orders Received</a>
                    <a href="reports.php" class="btn btn-outline-info btn-sm"><i class="bi bi-bar-chart"></i> Reports</a>
                    <a href="../messages.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chat"></i> Messages</a>
                    <a href="../profile.php" class="btn btn-outline-dark btn-sm"><i class="bi bi-gear"></i> Settings</a>
                </div>
                <?php if ($pending_orders > 0): ?>
                    <div class="alert alert-warning mt-3 mb-0 py-2"><i class="bi bi-exclamation-triangle"></i> You have <strong><?= $pending_orders ?></strong> pending order(s) – <a href="orders.php" class="alert-link">check now</a>.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Orders</h5>
        <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="ordersTable">
                <thead><tr><th>Order ID</th><th>Buyer</th><th>Breed</th><th>Qty</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                    <?php if ($recent_orders->num_rows > 0): while ($o = $recent_orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $o['id'] ?></td>
                            <td><?= htmlspecialchars($o['buyer_name']) ?><br><small class="text-muted"><?= $o['buyer_phone'] ?></small></td>
                            <td><?= htmlspecialchars($o['breed']) ?></td>
                            <td><?= $o['quantity'] ?></td>
                            <td>Tsh <?= number_format($o['total_price'], 2) ?></td>
                            <td><span class="badge bg-<?= $o['status']=='pending'?'warning':($o['status']=='delivered'?'success':'secondary') ?>"><?= $o['status'] ?></span></td>
                            <td><?= date('d M Y', strtotime($o['order_date'])) ?></td>
                        </tr>
                    <?php endwhile; else: ?><tr><td colspan="7" class="text-center text-muted">No orders received yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: { labels: <?= json_encode($revenue_trend['labels']) ?>, datasets: [{ label: 'Revenue (Tsh)', data: <?= json_encode($revenue_trend['values']) ?>, borderColor: '#0d6b6b', backgroundColor: 'rgba(13,107,107,0.1)', tension: 0.3, fill: true, pointBackgroundColor: '#0d6b6b' }] },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: function(value) { return 'Tsh ' + value.toLocaleString(); } } } } }
    });
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: { labels: <?= json_encode(array_keys($status_counts)) ?>, datasets: [{ data: <?= json_encode(array_values($status_counts)) ?>, backgroundColor: ['#ffc107','#0d6efd','#6f42c1','#198754','#dc3545'] }] },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
    $('#ordersTable').DataTable({ responsive: true, pageLength: 5, order: [[6, 'desc']], dom: 't<"row"<"col-sm-6"i><"col-sm-6"p>>' });
});
</script>
<?php
$content = ob_get_clean();
include '../layout.php';
?>