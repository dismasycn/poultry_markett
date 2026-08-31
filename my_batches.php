<?php
require_once '../includes/auth.php';
if ($_SESSION['role'] !== 'farmer') {
    redirect('../index.php');
}

$farmer_id = $_SESSION['user_id'];
$result = $conn->query("SELECT * FROM batches WHERE farmer_id = $farmer_id ORDER BY created_at DESC");

$page_title = "My Batches";
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2>My Batches</h2>
    <a href="add_batch.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add New Batch</a>
</div>

<?php
// Show loss alerts
$loss_count = $conn->query("
    SELECT COUNT(*) as count FROM batches 
    WHERE farmer_id = $farmer_id AND status = 'available' 
    AND DATEDIFF(CURDATE(), hatch_date) BETWEEN 31 AND 57
")->fetch_assoc()['count'];

$critical_count = $conn->query("
    SELECT COUNT(*) as count FROM batches 
    WHERE farmer_id = $farmer_id AND status = 'available' 
    AND DATEDIFF(CURDATE(), hatch_date) > 65
")->fetch_assoc()['count'];

if ($loss_count > 0): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        <strong>⚠️ Loss Alert:</strong> You have <strong><?= $loss_count ?></strong> batch(es) aged 31-57 days. 
        <a href="#batchTable" class="alert-link">View below</a>
    </div>
<?php endif; ?>

<?php if ($critical_count > 0): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-octagon"></i>
        <strong>🚨 CRITICAL Loss Alert:</strong> You have <strong><?= $critical_count ?></strong> batch(es) aged 65+ days. 
        <a href="#batchTable" class="alert-link">Act immediately!</a>
    </div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped" id="batchTable">
        <thead>
            <tr>
                <th>#</th>
                <th>Image</th>
                <th>Breed</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Hatch Date</th>
                <th>Age</th>
                <th>Status</th>
                <th>Alert</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; while ($row = $result->fetch_assoc()): 
                $age = getAgeInDays($row['hatch_date']);
                $priceInfo = getPriceBreakdown($row['price_per_bird'], $row['hatch_date']);
            ?>
            <tr>
                <td><?= $i++ ?></td>
                <td>
                    <?php if ($row['image']): ?>
                        <img src="<?= SITE_URL . $row['image'] ?>" width="50" height="50" style="object-fit:cover; border-radius:5px;">
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['breed']) ?></td>
                <td><?= $row['quantity'] ?></td>
                <td>
                    <strong>Tsh <?= number_format($priceInfo['current'], 2) ?></strong>
                    <?php if ($priceInfo['is_doubled']): ?>
                        <br><small class="text-info">(Base: Tsh <?= number_format($priceInfo['base'], 2) ?> × 2)</small>
                    <?php endif; ?>
                </td>
                <td><?= date('d M Y', strtotime($row['hatch_date'])) ?></td>
                <td><?= $age ?> days</td>
                <td><span class="badge bg-<?= $row['status']=='available'?'success':($row['status']=='sold'?'secondary':'warning') ?>"><?= $row['status'] ?></span></td>
                <td>
                    <span class="badge <?= $priceInfo['status_class'] ?>">
                        <?= $priceInfo['status_text'] ?>
                    </span>
                    <?php if ($priceInfo['is_loss']): ?>
                        <br><small class="text-danger"><i class="bi bi-exclamation-triangle"></i> 
                            <?= $priceInfo['is_critical'] ? 'Critical loss!' : 'Loss incurred!' ?>
                        </small>
                    <?php endif; ?>
                    <?php if ($priceInfo['is_doubled'] && !$priceInfo['is_loss']): ?>
                        <br><small class="text-info"><i class="bi bi-arrow-up"></i> Price doubled</small>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="edit_batch.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                    <form method="POST" action="delete_batch.php" style="display:inline;" onsubmit="return confirm('Are you sure? This cannot be undone.')">
                        <input type="hidden" name="batch_id" value="<?= $row['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<script>
$(document).ready(function() {
    $('#batchTable').DataTable({ responsive: true, pageLength: 10 });
});
</script>
<?php
$content = ob_get_clean();
include '../layout.php';
?>