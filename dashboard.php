<?php
require_once '../includes/auth.php';
if ($_SESSION['role'] !== 'admin') {
    redirect('../index.php');
}

// ---------- STATS ----------
$current_month = date('Y-m');
$prev_month = date('Y-m', strtotime('-1 month'));

$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$new_users_this_month = $conn->query("SELECT COUNT(*) FROM users WHERE DATE_FORMAT(created_at, '%Y-%m') = '$current_month'")->fetch_row()[0];
$prev_month_users = $conn->query("SELECT COUNT(*) FROM users WHERE DATE_FORMAT(created_at, '%Y-%m') = '$prev_month'")->fetch_row()[0];
$user_growth = ($prev_month_users > 0) ? round(($new_users_this_month - $prev_month_users) / $prev_month_users * 100, 1) : 100;

$total_batches = $conn->query("SELECT COUNT(*) FROM batches")->fetch_row()[0];
$new_batches_this_month = $conn->query("SELECT COUNT(*) FROM batches WHERE DATE_FORMAT(created_at, '%Y-%m') = '$current_month'")->fetch_row()[0];
$prev_month_batches = $conn->query("SELECT COUNT(*) FROM batches WHERE DATE_FORMAT(created_at, '%Y-%m') = '$prev_month'")->fetch_row()[0];
$batch_growth = ($prev_month_batches > 0) ? round(($new_batches_this_month - $prev_month_batches) / $prev_month_batches * 100, 1) : 100;

$total_orders = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$new_orders_this_month = $conn->query("SELECT COUNT(*) FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = '$current_month'")->fetch_row()[0];
$prev_month_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = '$prev_month'")->fetch_row()[0];
$order_growth = ($prev_month_orders > 0) ? round(($new_orders_this_month - $prev_month_orders) / $prev_month_orders * 100, 1) : 100;

$total_revenue = $conn->query("SELECT SUM(total_price) FROM orders WHERE status = 'delivered'")->fetch_row()[0] ?? 0;
$revenue_this_month = $conn->query("SELECT SUM(total_price) FROM orders WHERE status = 'delivered' AND DATE_FORMAT(order_date, '%Y-%m') = '$current_month'")->fetch_row()[0] ?? 0;
$revenue_prev_month = $conn->query("SELECT SUM(total_price) FROM orders WHERE status = 'delivered' AND DATE_FORMAT(order_date, '%Y-%m') = '$prev_month'")->fetch_row()[0] ?? 0;
$revenue_growth = ($revenue_prev_month > 0) ? round(($revenue_this_month - $revenue_prev_month) / $revenue_prev_month * 100, 1) : 100;

$pending_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetch_row()[0];
$available_batches = $conn->query("SELECT COUNT(*) FROM batches WHERE status = 'available' AND quantity > 0")->fetch_row()[0];

// ---------- CHART DATA ----------
$revenue_trend = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('M Y', strtotime("-$i months"));
    $month_query = date('Y-m', strtotime("-$i months"));
    $result = $conn->query("SELECT SUM(total_price) as total FROM orders WHERE status = 'delivered' AND DATE_FORMAT(order_date, '%Y-%m') = '$month_query'");
    $revenue_trend['labels'][] = $month;
    $revenue_trend['values'][] = (float)($result->fetch_assoc()['total'] ?? 0);
}

$status_counts = [];
$statuses = ['pending', 'confirmed', 'in_transit', 'delivered', 'cancelled'];
foreach ($statuses as $status) {
    $count = $conn->query("SELECT COUNT(*) FROM orders WHERE status = '$status'")->fetch_row()[0];
    $status_counts[$status] = $count;
}

// ---------- RECENT ORDERS (latest 10) ----------
$recent_orders = $conn->query("
    SELECT o.*, u.name as buyer_name, b.breed
    FROM orders o
    JOIN users u ON o.buyer_id = u.id
    JOIN batches b ON o.batch_id = b.id
    ORDER BY o.order_date DESC
    LIMIT 10
");

// ---------- RECENT USERS ----------
$recent_users = $conn->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");

$page_title = "Admin Dashboard";
$csrf_token = generateCSRFToken();
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
    <span class="badge bg-success"><?= date('l, d M Y') ?></span>
</div>

<!-- Summary Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase fw-bold">Users</h6>
                        <h2 class="mb-0"><?= $total_users ?></h2>
                        <small class="text-<?= $user_growth >= 0 ? 'success' : 'danger' ?>"><i class="bi bi-arrow-<?= $user_growth >= 0 ? 'up' : 'down' ?>"></i> <?= abs($user_growth) ?>%</small>
                    </div>
                    <div class="bg-primary bg-opacity-10 rounded p-3"><i class="bi bi-people fs-2 text-primary"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase fw-bold">Batches</h6>
                        <h2 class="mb-0"><?= $total_batches ?></h2>
                        <small class="text-<?= $batch_growth >= 0 ? 'success' : 'danger' ?>"><i class="bi bi-arrow-<?= $batch_growth >= 0 ? 'up' : 'down' ?>"></i> <?= abs($batch_growth) ?>%</small>
                        <div><small class="text-muted">Available: <?= $available_batches ?></small></div>
                    </div>
                    <div class="bg-success bg-opacity-10 rounded p-3"><i class="bi bi-box-seam fs-2 text-success"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase fw-bold">Orders</h6>
                        <h2 class="mb-0"><?= $total_orders ?></h2>
                        <small class="text-<?= $order_growth >= 0 ? 'success' : 'danger' ?>"><i class="bi bi-arrow-<?= $order_growth >= 0 ? 'up' : 'down' ?>"></i> <?= abs($order_growth) ?>%</small>
                        <div><span class="badge bg-warning text-dark">Pending: <?= $pending_orders ?></span></div>
                    </div>
                    <div class="bg-warning bg-opacity-10 rounded p-3"><i class="bi bi-cart fs-2 text-warning"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase fw-bold">Revenue</h6>
                        <h2 class="mb-0">Tsh <?= number_format($total_revenue, 0) ?></h2>
                        <small class="text-<?= $revenue_growth >= 0 ? 'success' : 'danger' ?>"><i class="bi bi-arrow-<?= $revenue_growth >= 0 ? 'up' : 'down' ?>"></i> <?= abs($revenue_growth) ?>%</small>
                    </div>
                    <div class="bg-info bg-opacity-10 rounded p-3"><i class="bi bi-currency-dollar fs-2 text-info"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
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

<!-- Quick Actions & Recent Users -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5></div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a href="users.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-people"></i> Users</a>
                    <a href="batches.php" class="btn btn-outline-success btn-sm"><i class="bi bi-box"></i> Batches</a>
                    <a href="orders.php" class="btn btn-outline-warning btn-sm"><i class="bi bi-cart"></i> Orders</a>
                    <a href="reports.php" class="btn btn-outline-info btn-sm"><i class="bi bi-bar-chart"></i> Reports</a>
                    <a href="../messages.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chat"></i> Messages</a>
                    <a href="../profile.php" class="btn btn-outline-dark btn-sm"><i class="bi bi-gear"></i> Settings</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Registrations</h5></div>
            <div class="card-body">
                <?php if ($recent_users->num_rows > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php while ($u = $recent_users->fetch_assoc()): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div><strong><?= htmlspecialchars($u['name']) ?></strong> <span class="badge bg-secondary"><?= $u['role'] ?></span><br><small class="text-muted"><?= htmlspecialchars($u['email']) ?></small></div>
                                <span class="text-muted small"><?= date('d M Y', strtotime($u['created_at'])) ?></span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?><p class="text-muted">No new registrations.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders Table -->
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Orders</h5>
        <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="ordersTable">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Buyer</th>
                        <th>Breed</th>
                        <th>Qty</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_orders->num_rows > 0): ?>
                        <?php while ($o = $recent_orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $o['id'] ?></td>
                                <td><?= htmlspecialchars($o['buyer_name']) ?></td>
                                <td><?= htmlspecialchars($o['breed']) ?></td>
                                <td><?= $o['quantity'] ?></td>
                                <td>Tsh <?= number_format($o['total_price'], 2) ?></td>
                                <td><span class="badge bg-<?= $o['status']=='pending'?'warning':($o['status']=='delivered'?'success':'secondary') ?>"><?= $o['status'] ?></span></td>
                                <td><?= date('d M Y', strtotime($o['order_date'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-muted">No orders yet.</td></tr>
                    <?php endif; ?>
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