<?php
require_once '../includes/auth.php';
if ($_SESSION['role'] !== 'farmer') {
    redirect('../index.php');
}

$farmer_id = $_SESSION['user_id'];

// Stats
$revenue = $conn->query("SELECT SUM(o.total_price) as total FROM orders o JOIN batches b ON o.batch_id = b.id WHERE b.farmer_id = $farmer_id AND o.status = 'delivered'")->fetch_assoc()['total'] ?? 0;
$total_orders = $conn->query("SELECT COUNT(*) as cnt FROM orders o JOIN batches b ON o.batch_id = b.id WHERE b.farmer_id = $farmer_id")->fetch_assoc()['cnt'] ?? 0;
$total_chickens = $conn->query("SELECT SUM(o.quantity) as total FROM orders o JOIN batches b ON o.batch_id = b.id WHERE b.farmer_id = $farmer_id AND o.status = 'delivered'")->fetch_assoc()['total'] ?? 0;

// Loss analytics
$loss_data = $conn->query("
    SELECT 
        COUNT(CASE WHEN DATEDIFF(CURDATE(), hatch_date) BETWEEN 31 AND 57 AND status = 'available' THEN 1 END) as loss_count,
        COUNT(CASE WHEN DATEDIFF(CURDATE(), hatch_date) BETWEEN 58 AND 64 AND status = 'available' THEN 1 END) as doubled_count,
        COUNT(CASE WHEN DATEDIFF(CURDATE(), hatch_date) > 65 AND status = 'available' THEN 1 END) as critical_count,
        COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), hatch_date) BETWEEN 31 AND 57 AND status = 'available' THEN quantity END), 0) as loss_birds,
        COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), hatch_date) > 65 AND status = 'available' THEN quantity END), 0) as critical_birds,
        COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), hatch_date) BETWEEN 58 AND 64 AND status = 'available' THEN quantity END), 0) as doubled_birds
    FROM batches 
    WHERE farmer_id = $farmer_id
")->fetch_assoc();

// Monthly sales
$monthly = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('M Y', strtotime("-$i months"));
    $monthQuery = date('Y-m', strtotime("-$i months"));
    $count = $conn->query("
        SELECT SUM(o.total_price) as total 
        FROM orders o 
        JOIN batches b ON o.batch_id = b.id 
        WHERE b.farmer_id = $farmer_id 
        AND o.status = 'delivered' 
        AND DATE_FORMAT(o.order_date, '%Y-%m') = '$monthQuery'
    ")->fetch_assoc()['total'] ?? 0;
    $monthly['labels'][] = $month;
    $monthly['values'][] = (float)$count;
}

// Top buyers
$top_buyers = $conn->query("
    SELECT u.name, SUM(o.total_price) as spent 
    FROM orders o 
    JOIN batches b ON o.batch_id = b.id 
    JOIN users u ON o.buyer_id = u.id 
    WHERE b.farmer_id = $farmer_id AND o.status = 'delivered'
    GROUP BY u.id 
    ORDER BY spent DESC 
    LIMIT 5
");

$page_title = "Sales Reports";
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2>Sales Reports</h2>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i> Print</button>
</div>

<!-- Stats -->
<div class="row g-4 mb-4">
    <div class="col-md-3"><div class="card text-center shadow-sm"><div class="card-body"><h1 class="display-6 text-success">Tsh <?= number_format($revenue, 0) ?></h1><p class="text-muted">Total Revenue</p></div></div></div>
    <div class="col-md-3"><div class="card text-center shadow-sm"><div class="card-body"><h1 class="display-6 text-primary"><?= $total_orders ?></h1><p class="text-muted">Total Orders</p></div></div></div>
    <div class="col-md-3"><div class="card text-center shadow-sm"><div class="card-body"><h1 class="display-6 text-info"><?= $total_chickens ?></h1><p class="text-muted">Chickens Sold</p></div></div></div>
    <div class="col-md-3"><div class="card text-center shadow-sm border-<?= $loss_data['critical_count'] > 0 ? 'danger' : ($loss_data['loss_count'] > 0 ? 'warning' : 'success') ?>">
        <div class="card-body"><h1 class="display-6 text-<?= $loss_data['critical_count'] > 0 ? 'danger' : ($loss_data['loss_count'] > 0 ? 'warning' : 'success') ?>"><?= $loss_data['loss_count'] + $loss_data['critical_count'] ?></h1>
        <p class="text-muted">At-Risk Batches</p>
        <small><?= $loss_data['loss_count'] ?> loss, <?= $loss_data['critical_count'] ?> critical</small></div>
    </div></div>
</div>

<!-- Loss Analytics -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center shadow-sm border-warning">
            <div class="card-body"><h3 class="text-warning"><?= $loss_data['loss_count'] ?></h3><p class="text-muted">Batches in Loss (31-57 days)</p><small><?= $loss_data['loss_birds'] ?> birds</small></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm border-info">
            <div class="card-body"><h3 class="text-info"><?= $loss_data['doubled_count'] ?></h3><p class="text-muted">Price Doubled (58-64 days)</p><small><?= $loss_data['doubled_birds'] ?> birds</small></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm border-danger">
            <div class="card-body"><h3 class="text-danger"><?= $loss_data['critical_count'] ?></h3><p class="text-muted">Critical Loss (65+ days)</p><small><?= $loss_data['critical_birds'] ?> birds</small></div>
        </div>
    </div>
</div>

<!-- Chart -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><h5 class="mb-0">Monthly Sales (Tsh)</h5></div>
            <div class="card-body"><canvas id="salesChart" height="200"></canvas></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><h5 class="mb-0">Top Buyers</h5></div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php if ($top_buyers->num_rows > 0): while ($buyer = $top_buyers->fetch_assoc()): ?>
                        <li class="list-group-item d-flex justify-content-between"><?= htmlspecialchars($buyer['name']) ?><span class="fw-bold">Tsh <?= number_format($buyer['spent'], 0) ?></span></li>
                    <?php endwhile; else: ?><li class="list-group-item text-muted">No buyers yet.</li><?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('salesChart'), {
        type: 'bar',
        data: { labels: <?= json_encode($monthly['labels']) ?>, datasets: [{ label: 'Revenue (Tsh)', data: <?= json_encode($monthly['values']) ?>, backgroundColor: 'rgba(13, 107, 107, 0.6)', borderColor: '#0d6b6b', borderWidth: 2, borderRadius: 4 }] },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => 'Tsh ' + v.toLocaleString() } } } }
    });
});
</script>
<?php
$content = ob_get_clean();
include '../layout.php';
?>