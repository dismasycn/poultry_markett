<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$farmer_id = (int)$_GET['id'];
if ($farmer_id <= 0) {
    redirect('index.php');
}

// Fetch farmer details
$stmt = $conn->prepare("
    SELECT u.id, u.name, u.phone, u.location, u.created_at, u.role,
           fp.farm_name, fp.farm_address
    FROM users u
    LEFT JOIN farmer_profiles fp ON u.id = fp.user_id
    WHERE u.id = ? AND u.role = 'farmer'
");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();

if (!$farmer) {
    $_SESSION['message'] = "Farmer not found.";
    $_SESSION['msg_type'] = "danger";
    redirect('browse.php');
}

// Fetch farmer's active batches
$batches = $conn->query("
    SELECT * FROM batches 
    WHERE farmer_id = $farmer_id AND status = 'available' AND quantity > 0
    ORDER BY created_at DESC
");

// Fetch farmer's stats
$total_batches = $conn->query("SELECT COUNT(*) FROM batches WHERE farmer_id = $farmer_id")->fetch_row()[0];
$total_sold = $conn->query("
    SELECT SUM(o.quantity) FROM orders o 
    JOIN batches b ON o.batch_id = b.id 
    WHERE b.farmer_id = $farmer_id AND o.status = 'delivered'
")->fetch_row()[0] ?? 0;
$total_revenue = $conn->query("
    SELECT SUM(o.total_price) FROM orders o 
    JOIN batches b ON o.batch_id = b.id 
    WHERE b.farmer_id = $farmer_id AND o.status = 'delivered'
")->fetch_row()[0] ?? 0;

$page_title = htmlspecialchars($farmer['name']) . " – Farmer Profile";
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2><i class="bi bi-person-badge"></i> <?= htmlspecialchars($farmer['name']) ?></h2>
    <div>
        <a href="../messages.php?with=<?= $farmer['id'] ?>" class="btn btn-primary">
            <i class="bi bi-chat"></i> Send Message
        </a>
        <a href="browse.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <!-- Farmer Details -->
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-person-circle display-1 text-muted mb-3"></i>
                <h4><?= htmlspecialchars($farmer['name']) ?></h4>
                <span class="badge bg-success">Farmer</span>
                <hr>
                <ul class="list-unstyled text-start">
                    <li class="mb-2">
                        <strong><i class="bi bi-telephone"></i> Phone:</strong><br>
                        <a href="tel:<?= htmlspecialchars($farmer['phone']) ?>" class="text-decoration-none">
                            <?= htmlspecialchars($farmer['phone']) ?>
                        </a>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $farmer['phone']) ?>" 
                           target="_blank" class="btn btn-sm btn-success ms-2">
                            <i class="bi bi-whatsapp"></i> WhatsApp
                        </a>
                    </li>
                    <li class="mb-2">
                        <strong><i class="bi bi-geo-alt"></i> Location:</strong><br>
                        <?= htmlspecialchars($farmer['location']) ?>
                    </li>
                    <?php if (!empty($farmer['farm_name'])): ?>
                        <li class="mb-2">
                            <strong><i class="bi bi-building"></i> Farm:</strong><br>
                            <?= htmlspecialchars($farmer['farm_name']) ?>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($farmer['farm_address'])): ?>
                        <li class="mb-2">
                            <strong><i class="bi bi-geo"></i> Farm Address:</strong><br>
                            <?= htmlspecialchars($farmer['farm_address']) ?>
                        </li>
                    <?php endif; ?>
                    <li class="mb-2">
                        <strong><i class="bi bi-clock"></i> Member Since:</strong><br>
                        <?= date('d M Y', strtotime($farmer['created_at'])) ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Farmer Stats & Batches -->
    <div class="col-md-8">
        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-4">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <h2 class="display-6 text-primary"><?= $total_batches ?></h2>
                        <p class="text-muted mb-0">Total Batches</p>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <h2 class="display-6 text-success"><?= $total_sold ?></h2>
                        <p class="text-muted mb-0">Chickens Sold</p>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <h2 class="display-6 text-warning">Tsh <?= number_format($total_revenue, 0) ?></h2>
                        <p class="text-muted mb-0">Revenue</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Batches -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-box-seam"></i> Active Batches</h5>
            </div>
            <div class="card-body">
                <?php if ($batches->num_rows > 0): ?>
                    <div class="row g-3">
                        <?php while ($batch = $batches->fetch_assoc()): 
                            $age = getAgeInDays($batch['hatch_date']);
                            $price = getCurrentPrice($batch['price_per_bird'], $batch['hatch_date']);
                        ?>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <?php if ($batch['image']): ?>
                                        <img src="<?= SITE_URL . $batch['image'] ?>" class="card-img-top" alt="<?= htmlspecialchars($batch['breed']) ?>" style="height:150px; object-fit:cover;">
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h6><?= htmlspecialchars($batch['breed']) ?></h6>
                                        <p class="small mb-1">
                                            <strong>Price:</strong> Tsh <?= number_format($price, 2) ?><br>
                                            <strong>Available:</strong> <?= $batch['quantity'] ?><br>
                                            <strong>Age:</strong> <?= $age ?> days
                                        </p>
                                        <a href="<?= SITE_URL ?>buyer/order.php?batch_id=<?= $batch['id'] ?>" class="btn btn-sm btn-success">Order Now</a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No active batches available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../layout.php';
?>