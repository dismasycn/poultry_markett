<?php
require_once '../includes/auth.php';
if ($_SESSION['role'] !== 'admin') {
    redirect('../index.php');
}

// Handle batch deletion via GET
if (isset($_GET['delete'])) {
    if (!verifyCSRFToken($_GET['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    $batch_id = (int)$_GET['delete'];
    
    // Get image path to delete file
    $img_query = $conn->prepare("SELECT image FROM batches WHERE id = ?");
    $img_query->bind_param("i", $batch_id);
    $img_query->execute();
    $img = $img_query->get_result()->fetch_assoc();
    if ($img && $img['image'] && file_exists('../' . $img['image'])) {
        unlink('../' . $img['image']);
    }
    
    $stmt = $conn->prepare("DELETE FROM batches WHERE id = ?");
    $stmt->bind_param("i", $batch_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Batch deleted successfully.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting batch: " . $conn->error;
        $_SESSION['msg_type'] = "danger";
    }
    redirect('batches.php');
}

// Handle status update via POST (inline)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    $batch_id = (int)$_POST['batch_id'];
    $new_status = $_POST['status'];
    if (!in_array($new_status, ['available', 'reserved', 'sold'])) {
        $new_status = 'available';
    }
    $stmt = $conn->prepare("UPDATE batches SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $batch_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Batch status updated.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating status.";
        $_SESSION['msg_type'] = "danger";
    }
    redirect('batches.php');
}

// Filters
$filter_status = $_GET['status'] ?? '';
$filter_farmer = $_GET['farmer'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT b.*, u.name as farmer_name, u.email as farmer_email 
          FROM batches b 
          JOIN users u ON b.farmer_id = u.id 
          WHERE 1=1";
$params = [];
$types = "";

if (!empty($filter_status)) {
    $query .= " AND b.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}
if (!empty($filter_farmer)) {
    $query .= " AND u.name LIKE ?";
    $params[] = "%$filter_farmer%";
    $types .= "s";
}
if (!empty($search)) {
    $query .= " AND (b.breed LIKE ? OR b.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}
$query .= " ORDER BY b.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$batches = $stmt->get_result();

// Get list of farmers for filter dropdown
$farmers = $conn->query("SELECT id, name FROM users WHERE role = 'farmer' ORDER BY name");

$csrf_token = generateCSRFToken();
$page_title = "Manage Batches";
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2>Manage Batches</h2>
    <a href="../farmer/add_batch.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add Batch (as farmer)</a>
</div>

<!-- Filters -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search breed/location" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="available" <?= $filter_status=='available'?'selected':'' ?>>Available</option>
                    <option value="reserved" <?= $filter_status=='reserved'?'selected':'' ?>>Reserved</option>
                    <option value="sold" <?= $filter_status=='sold'?'selected':'' ?>>Sold</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="farmer" class="form-select">
                    <option value="">All Farmers</option>
                    <?php while ($f = $farmers->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($f['name']) ?>" <?= $filter_farmer==$f['name']?'selected':'' ?>>
                            <?= htmlspecialchars($f['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="batches.php" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Batch Table -->
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="batchesTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Breed</th>
                        <th>Qty</th>
                        <th>Price (Tsh)</th>
                        <th>Age (days)</th>
                        <th>Location</th>
                        <th>Farmer</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($batches->num_rows > 0): ?>
                        <?php while ($row = $batches->fetch_assoc()): 
                            $age = getAgeInDays($row['hatch_date']);
                            $statusClass = match($row['status']) {
                                'available' => 'bg-success',
                                'reserved'  => 'bg-warning text-dark',
                                'sold'      => 'bg-secondary',
                                default     => 'bg-secondary'
                            };
                        ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td>
                                <?php if ($row['image']): ?>
                                    <img src="<?= SITE_URL . $row['image'] ?>" alt="Batch" style="width:50px; height:50px; object-fit:cover; border-radius:5px;">
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['breed']) ?></td>
                            <td><?= $row['quantity'] ?></td>
                            <td>
    <?php
    $priceInfo = getPriceBreakdown($row['price_per_bird'], $row['hatch_date']);
    ?>
    Tsh <?= number_format($priceInfo['current'], 2) ?>
    <?php if ($priceInfo['is_increased']): ?>
        <br><small class="text-muted">Base: Tsh <?= number_format($priceInfo['base'], 2) ?></small>
    <?php endif; ?>
</td>
                            <td><?= $age ?></td>
                            <td><?= htmlspecialchars($row['location']) ?></td>
                            <td>
                                <a href="users.php?search=<?= urlencode($row['farmer_name']) ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($row['farmer_name']) ?>
                                </a>
                            </td>
                            <td>
                                <form method="POST" class="status-form">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <input type="hidden" name="batch_id" value="<?= $row['id'] ?>">
                                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="available" <?= $row['status']=='available'?'selected':'' ?>>Available</option>
                                        <option value="reserved" <?= $row['status']=='reserved'?'selected':'' ?>>Reserved</option>
                                        <option value="sold" <?= $row['status']=='sold'?'selected':'' ?>>Sold</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>
                            <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="../farmer/edit_batch.php?id=<?= $row['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="batches.php?delete=<?= $row['id'] ?>&csrf_token=<?= $csrf_token ?>" 
                                       class="btn btn-outline-danger" 
                                       onclick="return confirm('Delete batch <?= htmlspecialchars($row['breed']) ?> permanently?')" 
                                       title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="11" class="text-center text-muted">No batches found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#batchesTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: [1, 8, 10] }
        ]
    });
});
</script>
<?php
$content = ob_get_clean();
include '../layout.php';
?>