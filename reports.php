<?php
require_once '../includes/auth.php';
if ($_SESSION['role'] !== 'admin') {
    redirect('../index.php');
}

// Include Dompdf autoload if installed via Composer
// require_once '../vendor/autoload.php'; // uncomment when using Composer

// Date range filter
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// ---------- STATS ----------
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_farmers = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'farmer'")->fetch_row()[0];
$total_buyers = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'buyer'")->fetch_row()[0];
$total_batches = $conn->query("SELECT COUNT(*) FROM batches")->fetch_row()[0];
$total_orders = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$total_revenue = $conn->query("SELECT SUM(total_price) FROM orders WHERE status = 'delivered'")->fetch_row()[0] ?? 0;
$pending_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetch_row()[0];
$delivered_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetch_row()[0];
$avg_order_value = ($delivered_orders > 0) ? $total_revenue / $delivered_orders : 0;

// ---------- REVENUE TREND (last 12 months) ----------
$revenue_data = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('M Y', strtotime("-$i months"));
    $month_query = date('Y-m', strtotime("-$i months"));
    $result = $conn->query("
        SELECT SUM(total_price) as total 
        FROM orders 
        WHERE status = 'delivered' 
        AND DATE_FORMAT(order_date, '%Y-%m') = '$month_query'
    ");
    $revenue_data['labels'][] = $month;
    $revenue_data['values'][] = (float)($result->fetch_assoc()['total'] ?? 0);
}

// ---------- ORDER STATUS DISTRIBUTION ----------
$status_counts = [];
$statuses = ['pending', 'confirmed', 'in_transit', 'delivered', 'cancelled'];
foreach ($statuses as $status) {
    $count = $conn->query("SELECT COUNT(*) FROM orders WHERE status = '$status'")->fetch_row()[0];
    $status_counts[$status] = $count;
}

// ---------- TOP 5 FARMERS BY REVENUE ----------
$top_farmers = $conn->query("
    SELECT u.name, SUM(o.total_price) as revenue, COUNT(o.id) as orders_count
    FROM orders o
    JOIN batches b ON o.batch_id = b.id
    JOIN users u ON b.farmer_id = u.id
    WHERE o.status = 'delivered'
    GROUP BY u.id
    ORDER BY revenue DESC
    LIMIT 5
");

// ---------- TOP 5 BUYERS BY SPENDING ----------
$top_buyers = $conn->query("
    SELECT u.name, SUM(o.total_price) as spent, COUNT(o.id) as orders_count
    FROM orders o
    JOIN users u ON o.buyer_id = u.id
    WHERE o.status = 'delivered'
    GROUP BY u.id
    ORDER BY spent DESC
    LIMIT 5
");

// ---------- ENHANCEMENT: TOP BREEDS SOLD ----------
$top_breeds = $conn->query("
    SELECT b.breed, SUM(o.quantity) as total_sold
    FROM orders o
    JOIN batches b ON o.batch_id = b.id
    WHERE o.status = 'delivered'
    GROUP BY b.breed
    ORDER BY total_sold DESC
    LIMIT 5
");

// ---------- ENHANCEMENT: SALES BY LOCATION ----------
$location_sales = $conn->query("
    SELECT b.location, SUM(o.total_price) as revenue
    FROM orders o
    JOIN batches b ON o.batch_id = b.id
    WHERE o.status = 'delivered'
    GROUP BY b.location
    ORDER BY revenue DESC
    LIMIT 5
");

// ---------- ENHANCEMENT: REVENUE BY FARMER (top 10) ----------
$farmer_revenue_pie = $conn->query("
    SELECT u.name, SUM(o.total_price) as revenue
    FROM orders o
    JOIN batches b ON o.batch_id = b.id
    JOIN users u ON b.farmer_id = u.id
    WHERE o.status = 'delivered'
    GROUP BY u.id
    ORDER BY revenue DESC
    LIMIT 10
");

// ---------- RECENT ORDERS (filtered by date) ----------
$recent_orders = $conn->query("
    SELECT o.*, u.name as buyer_name, b.breed, b.location
    FROM orders o
    JOIN users u ON o.buyer_id = u.id
    JOIN batches b ON o.batch_id = b.id
    WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
    ORDER BY o.order_date DESC
    LIMIT 20
");

// ---------- PDF EXPORT ----------
if (isset($_GET['pdf'])) {
    // Render HTML content for PDF (we'll reuse the page but with a special layout)
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Poultry Market Report</title>
        <style>
            body { font-family: DejaVu Sans, sans-serif; padding: 20px; }
            h1, h2 { color: #198754; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #198754; color: white; }
            .stats { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px; }
            .stat-box { background: #f8f9fa; padding: 10px 20px; border-radius: 8px; border-left: 4px solid #198754; }
        </style>
    </head>
    <body>
        <h1>Poultry Market - Full Report</h1>
        <p>Generated: <?= date('d M Y H:i') ?> | Period: <?= date('d M Y', strtotime($start_date)) ?> – <?= date('d M Y', strtotime($end_date)) ?></p>

        <div class="stats">
            <div class="stat-box"><strong>Users:</strong> <?= $total_users ?></div>
            <div class="stat-box"><strong>Farmers:</strong> <?= $total_farmers ?></div>
            <div class="stat-box"><strong>Buyers:</strong> <?= $total_buyers ?></div>
            <div class="stat-box"><strong>Batches:</strong> <?= $total_batches ?></div>
            <div class="stat-box"><strong>Orders:</strong> <?= $total_orders ?></div>
            <div class="stat-box"><strong>Revenue:</strong> Tsh <?= number_format($total_revenue, 2) ?></div>
            <div class="stat-box"><strong>Avg Order:</strong> Tsh <?= number_format($avg_order_value, 2) ?></div>
        </div>

        <h2>Top 5 Breeds Sold</h2>
        <ul>
        <?php while ($row = $top_breeds->fetch_assoc()): ?>
            <li><?= $row['breed'] ?> – <?= $row['total_sold'] ?> birds sold</li>
        <?php endwhile; ?>
        </ul>

        <h2>Sales by Location</h2>
        <ul>
        <?php while ($row = $location_sales->fetch_assoc()): ?>
            <li><?= $row['location'] ?> – Tsh <?= number_format($row['revenue'], 2) ?></li>
        <?php endwhile; ?>
        </ul>

        <h2>Top Farmers by Revenue</h2>
        <ul>
        <?php while ($row = $top_farmers->fetch_assoc()): ?>
            <li><?= $row['name'] ?> – Tsh <?= number_format($row['revenue'], 2) ?> (<?= $row['orders_count'] ?> orders)</li>
        <?php endwhile; ?>
        </ul>

        <h2>Recent Orders</h2>
        <table>
            <thead><tr><th>ID</th><th>Buyer</th><th>Breed</th><th>Qty</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php while ($row = $recent_orders->fetch_assoc()): ?>
                <tr>
                    <td>#<?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['buyer_name']) ?></td>
                    <td><?= htmlspecialchars($row['breed']) ?></td>
                    <td><?= $row['quantity'] ?></td>
                    <td>Tsh <?= number_format($row['total_price'], 2) ?></td>
                    <td><?= $row['status'] ?></td>
                    <td><?= date('d M Y', strtotime($row['order_date'])) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    $html = ob_get_clean();
    
    // Use Dompdf if installed
    if (class_exists('Dompdf\Dompdf')) {
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('report_' . date('Y-m-d') . '.pdf', array('Attachment' => 0));
    } else {
        // Fallback: output HTML
        echo $html;
        exit;
    }
    exit;
}

$page_title = "Reports & Analytics";
$csrf_token = generateCSRFToken();
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2><i class="bi bi-bar-chart"></i> Reports & Analytics</h2>
    <div>
        <a href="?pdf=1&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-danger btn-sm">
            <i class="bi bi-file-pdf"></i> PDF Export
        </a>
        <a href="?export=1&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-success btn-sm">
            <i class="bi bi-download"></i> CSV Export
        </a>
        <button onclick="window.print()" class="btn btn-secondary btn-sm">
            <i class="bi bi-printer"></i> Print
        </button>
    </div>
</div>

<!-- Date Range Filter -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-0 bg-primary text-white">
            <div class="card-body">
                <h1 class="display-6"><?= $total_users ?></h1>
                <p class="mb-0">Total Users</p>
                <small>Farmers: <?= $total_farmers ?>, Buyers: <?= $total_buyers ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-0 bg-success text-white">
            <div class="card-body">
                <h1 class="display-6"><?= $total_batches ?></h1>
                <p class="mb-0">Total Batches</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-0 bg-warning text-dark">
            <div class="card-body">
                <h1 class="display-6"><?= $total_orders ?></h1>
                <p class="mb-0">Total Orders</p>
                <small>Pending: <?= $pending_orders ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-0 bg-info text-white">
            <div class="card-body">
                <h1 class="display-6">Tsh <?= number_format($total_revenue, 0) ?></h1>
                <p class="mb-0">Total Revenue</p>
                <small>Avg order: Tsh <?= number_format($avg_order_value, 0) ?></small>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 1 -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Monthly Revenue (Last 12 Months)</h5>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Order Status</h5>
            </div>
            <div class="card-body">
                <canvas id="statusChart" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Revenue by Farmer</h5>
            </div>
            <div class="card-body">
                <canvas id="farmerPieChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 2 -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">🏆 Top Breeds Sold</h5>
            </div>
            <div class="card-body">
                <canvas id="breedChart" height="180"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">📍 Sales by Location</h5>
            </div>
            <div class="card-body">
                <canvas id="locationChart" height="180"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Top Performers Row -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">🏆 Top 5 Farmers by Revenue</h5>
            </div>
            <div class="card-body">
                <?php if ($top_farmers->num_rows > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php while ($f = $top_farmers->fetch_assoc()): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($f['name']) ?>
                                <span>
                                    <span class="badge bg-secondary rounded-pill me-2"><?= $f['orders_count'] ?> orders</span>
                                    <span class="badge bg-success rounded-pill">Tsh <?= number_format($f['revenue'], 0) ?></span>
                                </span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted">No data available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">👑 Top 5 Buyers by Spending</h5>
            </div>
            <div class="card-body">
                <?php if ($top_buyers->num_rows > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php while ($b = $top_buyers->fetch_assoc()): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($b['name']) ?>
                                <span>
                                    <span class="badge bg-secondary rounded-pill me-2"><?= $b['orders_count'] ?> orders</span>
                                    <span class="badge bg-primary rounded-pill">Tsh <?= number_format($b['spent'], 0) ?></span>
                                </span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted">No data available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders Table -->
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Orders (<?= date('d M Y', strtotime($start_date)) ?> – <?= date('d M Y', strtotime($end_date)) ?>)</h5>
        <span class="badge bg-secondary"><?= $recent_orders->num_rows ?> orders</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="ordersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Buyer</th>
                        <th>Breed</th>
                        <th>Qty</th>
                        <th>Total (Tsh)</th>
                        <th>Status</th>
                        <th>Delivery</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_orders->num_rows > 0): ?>
                        <?php while ($row = $recent_orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['buyer_name']) ?></td>
                                <td><?= htmlspecialchars($row['breed']) ?></td>
                                <td><?= $row['quantity'] ?></td>
                                <td><?= number_format($row['total_price'], 2) ?></td>
                                <td><span class="badge bg-<?= $row['status']=='pending'?'warning':($row['status']=='delivered'?'success':'secondary') ?>"><?= $row['status'] ?></span></td>
                                <td><?= str_replace('_', ' ', $row['delivery_method']) ?></td>
                                <td><?= date('d M Y', strtotime($row['order_date'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted">No orders in this date range.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js & DataTable scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($revenue_data['labels']) ?>,
            datasets: [{
                label: 'Revenue (Tsh)',
                data: <?= json_encode($revenue_data['values']) ?>,
                backgroundColor: 'rgba(13, 110, 253, 0.6)',
                borderColor: '#0d6efd',
                borderWidth: 2,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => 'Tsh ' + v.toLocaleString() } } }
        }
    });

    // Status Chart
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($status_counts)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($status_counts)) ?>,
                backgroundColor: ['#ffc107', '#0d6efd', '#6f42c1', '#198754', '#dc3545']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    // Farmer Revenue Pie (top 10)
    const farmerLabels = <?= json_encode(array_column($farmer_revenue_pie->fetch_all(MYSQLI_ASSOC), 'name')) ?>;
    const farmerData = <?= json_encode(array_column($farmer_revenue_pie->fetch_all(MYSQLI_ASSOC), 'revenue')) ?>;
    new Chart(document.getElementById('farmerPieChart'), {
        type: 'pie',
        data: {
            labels: farmerLabels.length ? farmerLabels : ['No data'],
            datasets: [{
                data: farmerData.length ? farmerData : [1],
                backgroundColor: ['#198754', '#0d6efd', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#20c997', '#e83e8c', '#6610f2', '#d63384']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } } }
    });

    // Top Breeds Chart
    const breedLabels = <?= json_encode(array_column($top_breeds->fetch_all(MYSQLI_ASSOC), 'breed')) ?>;
    const breedData = <?= json_encode(array_column($top_breeds->fetch_all(MYSQLI_ASSOC), 'total_sold')) ?>;
    new Chart(document.getElementById('breedChart'), {
        type: 'bar',
        data: {
            labels: breedLabels.length ? breedLabels : ['No data'],
            datasets: [{
                label: 'Birds Sold',
                data: breedData.length ? breedData : [0],
                backgroundColor: 'rgba(25, 135, 84, 0.6)',
                borderColor: '#198754',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // Sales by Location Chart
    const locLabels = <?= json_encode(array_column($location_sales->fetch_all(MYSQLI_ASSOC), 'location')) ?>;
    const locData = <?= json_encode(array_column($location_sales->fetch_all(MYSQLI_ASSOC), 'revenue')) ?>;
    new Chart(document.getElementById('locationChart'), {
        type: 'bar',
        data: {
            labels: locLabels.length ? locLabels : ['No data'],
            datasets: [{
                label: 'Revenue (Tsh)',
                data: locData.length ? locData : [0],
                backgroundColor: 'rgba(13, 110, 253, 0.6)',
                borderColor: '#0d6efd',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => 'Tsh ' + v.toLocaleString() } } }
        }
    });

    // DataTable
    $('#ordersTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[7, 'desc']],
        dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>t<"row"<"col-sm-6"i><"col-sm-6"p>>'
    });
});
</script>
<?php
$content = ob_get_clean();
include '../layout.php';
?>