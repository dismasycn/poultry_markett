<?php
require_once '../includes/auth.php';
if ($_SESSION['role'] !== 'buyer') {
    redirect('../index.php');
}

$search = $_GET['search'] ?? '';
$location = $_GET['location'] ?? '';
$min_age = (int)($_GET['min_age'] ?? 0);
$max_age = (int)($_GET['max_age'] ?? 9999);

$query = "SELECT b.*, u.id as farmer_id, u.name as farmer_name, u.phone as farmer_phone, u.location as farmer_location,
          fp.farm_name, fp.farm_address
          FROM batches b 
          JOIN users u ON b.farmer_id = u.id 
          LEFT JOIN farmer_profiles fp ON u.id = fp.user_id
          WHERE b.status = 'available' AND b.quantity > 0";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (b.breed LIKE ? OR b.location LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}
if (!empty($location)) {
    $query .= " AND b.location LIKE ?";
    $params[] = "%$location%";
    $types .= "s";
}
$query .= " AND DATEDIFF(CURDATE(), b.hatch_date) BETWEEN ? AND ?";
$params[] = $min_age;
$params[] = $max_age;
$types .= "ii";
$query .= " ORDER BY b.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$batches = $stmt->get_result();

$page_title = "Browse Chickens";
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Available Chickens</h1>
</div>

<form method="GET" class="row g-3 mb-4">
    <div class="col-md-4">
        <input type="text" class="form-control" name="search" placeholder="Search by breed or location" value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-2">
        <input type="number" class="form-control" name="min_age" placeholder="Min age (days)" value="<?= $min_age ?>">
    </div>
    <div class="col-md-2">
        <input type="number" class="form-control" name="max_age" placeholder="Max age (days)" value="<?= $max_age ?>">
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-success w-100">Search</button>
    </div>
    <div class="col-md-2">
        <a href="browse.php" class="btn btn-outline-secondary w-100">Reset</a>
    </div>
</form>

<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    <?php if ($batches->num_rows > 0): ?>
        <?php while ($batch = $batches->fetch_assoc()): 
            $age = getAgeInDays($batch['hatch_date']);
            $priceInfo = getPriceBreakdown($batch['price_per_bird'], $batch['hatch_date']);
            $ageClass = ($age <= 30) ? 'bg-success' : (($age <= 57) ? 'bg-warning text-dark' : 'bg-info');
            $whatsapp_number = preg_replace('/[^0-9]/', '', $batch['farmer_phone']);
        ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <?php if ($batch['image']): ?>
                        <img src="<?= SITE_URL . $batch['image'] ?>" class="card-img-top" alt="<?= htmlspecialchars($batch['breed']) ?>" style="height:180px; object-fit:cover;">
                    <?php else: ?>
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:180px;">
                            <span class="text-muted"><i class="bi bi-image fs-1"></i></span>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($batch['breed']) ?>
                            <?php if ($priceInfo['is_doubled']): ?>
                                <span class="badge bg-info">Premium</span>
                            <?php endif; ?>
                        </h5>
                        <p class="card-text small">
                            <strong>Available:</strong> <?= $batch['quantity'] ?><br>
                            <strong>Price:</strong> Tsh <?= number_format($priceInfo['current'], 2) ?>
                            <?php if ($priceInfo['is_doubled']): ?>
                                <br><small class="text-info"><i class="bi bi-arrow-up"></i> Mature premium birds</small>
                            <?php endif; ?>
                            <br><strong>Age:</strong> <span class="badge <?= $ageClass ?>"><?= $age ?> days</span><br>
                            <strong>Location:</strong> <?= htmlspecialchars($batch['location']) ?>
                        </p>

                        <div class="farmer-details mt-2 p-2 bg-light rounded border">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><i class="bi bi-person"></i> <?= htmlspecialchars($batch['farmer_name']) ?></strong>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" 
                                            data-bs-target="#farmerModal<?= $batch['id'] ?>" 
                                            title="View Full Profile">
                                        <i class="bi bi-person"></i> View Profile
                                    </button>
                                </div>
                            </div>
                            <div class="mt-1">
                                <small>
                                    <i class="bi bi-telephone"></i> 
                                    <a href="tel:<?= htmlspecialchars($batch['farmer_phone']) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($batch['farmer_phone']) ?>
                                    </a>
                                    <?php if (!empty($whatsapp_number)): ?>
                                        <a href="https://wa.me/<?= $whatsapp_number ?>" 
                                           target="_blank" class="btn btn-sm btn-success ms-1" 
                                           title="Chat on WhatsApp">
                                            <i class="bi bi-whatsapp"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($batch['farm_name'])): ?>
                                        <br><i class="bi bi-building"></i> <?= htmlspecialchars($batch['farm_name']) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-white border-top-0 d-flex gap-1">
                        <a href="order.php?batch_id=<?= $batch['id'] ?>" class="btn btn-success btn-sm flex-grow-1">
                            <i class="bi bi-cart"></i> Order Now
                        </a>
                        <a href="../messages.php?with=<?= $batch['farmer_id'] ?>" class="btn btn-outline-primary btn-sm" title="Send Message">
                            <i class="bi bi-chat"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Farmer Profile Modal -->
            <div class="modal fade" id="farmerModal<?= $batch['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title"><i class="bi bi-person-badge"></i> <?= htmlspecialchars($batch['farmer_name']) ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-5 text-center">
                                    <i class="bi bi-person-circle display-1 text-muted mb-3"></i>
                                    <h4><?= htmlspecialchars($batch['farmer_name']) ?></h4>
                                    <span class="badge bg-success">Farmer</span>
                                    <hr>
                                    <ul class="list-unstyled text-start">
                                        <li class="mb-2">
                                            <strong><i class="bi bi-telephone"></i> Phone:</strong><br>
                                            <a href="tel:<?= htmlspecialchars($batch['farmer_phone']) ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($batch['farmer_phone']) ?>
                                            </a>
                                            <?php if (!empty($whatsapp_number)): ?>
                                                <a href="https://wa.me/<?= $whatsapp_number ?>" 
                                                   target="_blank" class="btn btn-success btn-sm ms-2">
                                                    <i class="bi bi-whatsapp"></i> WhatsApp
                                                </a>
                                            <?php endif; ?>
                                        </li>
                                        <li class="mb-2">
                                            <strong><i class="bi bi-geo-alt"></i> Location:</strong><br>
                                            <?= htmlspecialchars($batch['farmer_location']) ?>
                                        </li>
                                        <?php if (!empty($batch['farm_name'])): ?>
                                            <li class="mb-2">
                                                <strong><i class="bi bi-building"></i> Farm Name:</strong><br>
                                                <?= htmlspecialchars($batch['farm_name']) ?>
                                            </li>
                                        <?php endif; ?>
                                        <?php if (!empty($batch['farm_address'])): ?>
                                            <li class="mb-2">
                                                <strong><i class="bi bi-geo"></i> Farm Address:</strong><br>
                                                <?= htmlspecialchars($batch['farm_address']) ?>
                                            </li>
                                        <?php endif; ?>
                                        <li class="mb-2">
                                            <strong><i class="bi bi-clock"></i> Member Since:</strong><br>
                                            <?php 
                                            $member_query = $conn->query("SELECT created_at FROM users WHERE id = {$batch['farmer_id']}");
                                            $member = $member_query->fetch_assoc();
                                            echo date('d M Y', strtotime($member['created_at']));
                                            ?>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-7">
                                    <div class="card shadow-sm mb-3">
                                        <div class="card-header bg-white">
                                            <h6 class="mb-0"><i class="bi bi-box-seam"></i> Current Batch</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-unstyled small">
                                                <li><strong>Breed:</strong> <?= htmlspecialchars($batch['breed']) ?>
                                                    <?php if ($priceInfo['is_doubled']): ?>
                                                        <span class="badge bg-info">Premium</span>
                                                    <?php endif; ?>
                                                </li>
                                                <li><strong>Quantity:</strong> <?= $batch['quantity'] ?> available</li>
                                                <li><strong>Price:</strong> Tsh <?= number_format($priceInfo['current'], 2) ?>
                                                    <?php if ($priceInfo['is_doubled']): ?>
                                                        <br><small class="text-info">(Base: Tsh <?= number_format($priceInfo['base'], 2) ?> × 2)</small>
                                                    <?php endif; ?>
                                                </li>
                                                <li><strong>Age:</strong> <?= $age ?> days</li>
                                                <li><strong>Location:</strong> <?= htmlspecialchars($batch['location']) ?></li>
                                            </ul>
                                        </div>
                                    </div>

                                    <div class="card shadow-sm">
                                        <div class="card-header bg-white">
                                            <h6 class="mb-0"><i class="bi bi-bar-chart"></i> Farmer Stats</h6>
                                        </div>
                                        <div class="card-body">
                                            <?php
                                            $stats = $conn->query("
                                                SELECT 
                                                    COUNT(DISTINCT b.id) as total_batches,
                                                    COALESCE(SUM(o.quantity), 0) as total_sold,
                                                    COALESCE(SUM(o.total_price), 0) as total_revenue
                                                FROM batches b
                                                LEFT JOIN orders o ON b.id = o.batch_id AND o.status = 'delivered'
                                                WHERE b.farmer_id = {$batch['farmer_id']}
                                            ")->fetch_assoc();
                                            ?>
                                            <div class="row text-center">
                                                <div class="col-4">
                                                    <h5 class="text-primary"><?= $stats['total_batches'] ?></h5>
                                                    <small class="text-muted">Batches</small>
                                                </div>
                                                <div class="col-4">
                                                    <h5 class="text-success"><?= $stats['total_sold'] ?></h5>
                                                    <small class="text-muted">Sold</small>
                                                </div>
                                                <div class="col-4">
                                                    <h5 class="text-warning">Tsh <?= number_format($stats['total_revenue'], 0) ?></h5>
                                                    <small class="text-muted">Revenue</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="../messages.php?with=<?= $batch['farmer_id'] ?>" class="btn btn-primary">
                                <i class="bi bi-chat"></i> Send Message
                            </a>
                            <a href="order.php?batch_id=<?= $batch['id'] ?>" class="btn btn-success">
                                <i class="bi bi-cart"></i> Order Now
                            </a>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info"><i class="bi bi-info-circle"></i> No chickens found matching your criteria.</div>
        </div>
    <?php endif; ?>
</div>

<style>
.farmer-details {
    background-color: var(--bg-body) !important;
    border-color: var(--border-color) !important;
}
[data-theme="dark"] .farmer-details {
    background-color: #2d3238 !important;
}
</style>
<?php
$content = ob_get_clean();
include '../layout.php';
?>