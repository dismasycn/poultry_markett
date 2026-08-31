<?php
require_once '../includes/auth.php';
if ($_SESSION['role'] !== 'buyer') {
    redirect('../index.php');
}

$buyer_id = $_SESSION['user_id'];

// ---------- STATS ----------
$total_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE buyer_id = $buyer_id")->fetch_row()[0] ?? 0;
$pending_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE buyer_id = $buyer_id AND status = 'pending'")->fetch_row()[0] ?? 0;
$delivered_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE buyer_id = $buyer_id AND status = 'delivered'")->fetch_row()[0] ?? 0;
$total_chickens = $conn->query("SELECT SUM(quantity) FROM orders WHERE buyer_id = $buyer_id AND status IN ('delivered','in_transit','confirmed')")->fetch_row()[0] ?? 0;

// ---------- CHART DATA ----------
$chartData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('M Y', strtotime("-$i months"));
    $monthQuery = date('Y-m', strtotime("-$i months"));
    $count = $conn->query("SELECT COUNT(*) FROM orders WHERE buyer_id = $buyer_id AND DATE_FORMAT(order_date, '%Y-%m') = '$monthQuery'")->fetch_row()[0];
    $chartData['labels'][] = $month;
    $chartData['values'][] = $count;
}

// ---------- RECENT ORDERS ----------
$recent_orders = $conn->query("
    SELECT o.*, b.breed, b.price_per_bird, u.name as farmer_name, u.id as farmer_id
    FROM orders o
    JOIN batches b ON o.batch_id = b.id
    JOIN users u ON b.farmer_id = u.id
    WHERE o.buyer_id = $buyer_id
    ORDER BY o.order_date DESC
    LIMIT 5
");

// ---------- AVAILABLE BATCHES ----------
$available_batches = $conn->query("SELECT COUNT(*) FROM batches WHERE status = 'available' AND quantity > 0")->fetch_row()[0] ?? 0;

$page_title = "Buyer Dashboard";
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['name']) ?> 👋</h2>
    <div>
        <a href="browse.php" class="btn btn-success"><i class="bi bi-search"></i> Browse Chickens</a>
        <?php if ($available_batches > 0): ?>
            <span class="badge bg-success ms-2"><?= $available_batches ?> batches available</span>
        <?php endif; ?>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card text-center shadow-sm"><div class="card-body"><h1 class="display-6 text-primary"><?= $total_orders ?></h1><p class="text-muted mb-0">Total Orders</p></div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm"><div class="card-body"><h1 class="display-6 text-warning"><?= $pending_orders ?></h1><p class="text-muted mb-0">Pending</p></div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm"><div class="card-body"><h1 class="display-6 text-success"><?= $delivered_orders ?></h1><p class="text-muted mb-0">Delivered</p></div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm"><div class="card-body"><h1 class="display-6 text-info"><?= $total_chickens ?></h1><p class="text-muted mb-0">Chickens Bought</p></div></div>
    </div>
</div>

<!-- Chart & Quick Tips -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><h5 class="mb-0"><i class="bi bi-graph-up"></i> Your Order Trends (Last 6 Months)</h5></div>
            <div class="card-body"><canvas id="orderChart" height="150"></canvas></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><h5 class="mb-0"><i class="bi bi-lightbulb"></i> Quick Tips</h5></div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2">✅ Chickens are best at <strong>3–4 weeks</strong> old</li>
                    <li class="mb-2">📦 Choose <strong>self-pickup</strong> to save delivery costs</li>
                    <li class="mb-2">💬 Message farmers directly for bulk deals</li>
                    <li>🔄 <strong>Re-order</strong> from your past orders below</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders Table -->
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Orders</h5>
        <a href="my_orders.php" class="btn btn-sm btn-outline-success">View All</a>
    </div>
    <div class="card-body">
        <?php if ($recent_orders->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover" id="ordersTable">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Breed</th>
                            <th>Qty</th>
                            <th>Total (Tsh)</th>
                            <th>Farmer</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $recent_orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['breed']) ?></td>
                            <td><?= $row['quantity'] ?></td>
                            <td><?= number_format($row['total_price'], 2) ?></td>
                            <td><?= htmlspecialchars($row['farmer_name']) ?></td>
                            <td><span class="badge bg-<?= $row['status']=='pending'?'warning':($row['status']=='delivered'?'success':'secondary') ?>"><?= $row['status'] ?></span></td>
                            <td><?= date('d M Y', strtotime($row['order_date'])) ?></td>
                            <td>
                                <?php if ($row['status'] == 'delivered'): ?>
                                    <a href="reorder.php?order_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-success">Re-order</a>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                                <a href="../messages.php?with=<?= $row['farmer_id'] ?>" class="btn btn-sm btn-outline-info" title="Message Farmer"><i class="bi bi-chat"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4"><p class="text-muted">You haven't placed any orders yet.</p><a href="browse.php" class="btn btn-success">Start Shopping</a></div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Links -->
<div class="row mt-4">
    <div class="col-md-4">
        <div class="card shadow-sm"><div class="card-body"><h5><i class="bi bi-chat-dots text-success"></i> Messages</h5><p>Check your inbox for updates from farmers.</p><a href="../messages.php" class="btn btn-outline-primary btn-sm">Go to Messages</a></div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm"><div class="card-body"><h5><i class="bi bi-stars text-success"></i> Top Farmers</h5><p>See who has the best reviews (coming soon).</p><a href="#" class="btn btn-outline-secondary btn-sm" disabled>Coming Soon</a></div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm"><div class="card-body"><h5><i class="bi bi-info-circle text-success"></i> Need Help?</h5><p>Contact support or check our FAQ.</p><a href="#" class="btn btn-outline-secondary btn-sm" disabled>Support</a></div></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('orderChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartData['labels']) ?>,
            datasets: [{ label: 'Orders Placed', data: <?= json_encode($chartData['values']) ?>, backgroundColor: 'rgba(13,107,107,0.6)', borderColor: '#0d6b6b', borderWidth: 2, borderRadius: 4 }]
        },
        options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
    $('#ordersTable').DataTable({
        responsive: true,
        pageLength: 5,
        order: [[6, 'desc']],
        dom: 't<"row"<"col-sm-6"i><"col-sm-6"p>>'
    });
});
</script>
<?php
$content = ob_get_clean();
include '../layout.php';
?>