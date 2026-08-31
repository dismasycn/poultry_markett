<?php
require_once '../includes/auth.php';
if ($_SESSION['role'] !== 'farmer') {
    redirect('../index.php');
}

$id = (int)$_GET['id'];
$farmer_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM batches WHERE id = ? AND farmer_id = ?");
$stmt->bind_param("ii", $id, $farmer_id);
$stmt->execute();
$batch = $stmt->get_result()->fetch_assoc();
if (!$batch) {
    $_SESSION['message'] = "Batch not found or unauthorized.";
    redirect('my_batches.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid security token.";
    } else {
        $breed = sanitize($_POST['breed']);
        $quantity = (int)$_POST['quantity'];
        $price = (float)$_POST['price'];
        $hatch_date = $_POST['hatch_date'];
        $location = sanitize($_POST['location']);
        $status = sanitize($_POST['status']);

        if (empty($breed)) $errors[] = "Breed is required.";
        if ($quantity < 1) $errors[] = "Quantity must be at least 1.";
        if ($price < 0) $errors[] = "Price cannot be negative.";
        if (!strtotime($hatch_date)) $errors[] = "Invalid hatch date.";
        if ($hatch_date > date('Y-m-d')) $errors[] = "Hatch date cannot be in the future.";
        if (empty($location)) $errors[] = "Location is required.";
        if (!in_array($status, ['available', 'reserved', 'sold'])) $status = 'available';

        // Image upload
        $image_path = $batch['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $max_size = 2 * 1024 * 1024;
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if ($file['size'] > $max_size) $errors[] = "Image size must be less than 2MB.";
            if (!in_array($file['type'], $allowed_types)) $errors[] = "Only JPG, PNG, GIF, and WEBP images are allowed.";
            if (empty($errors)) {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_name = uniqid() . '.' . $extension;
                $upload_dir = '../assets/uploads/batches/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $destination = $upload_dir . $new_name;
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    if ($batch['image'] && file_exists('../' . $batch['image'])) unlink('../' . $batch['image']);
                    $image_path = 'assets/uploads/batches/' . $new_name;
                } else $errors[] = "Failed to upload image.";
            }
        }
        if (isset($_POST['remove_image']) && $_POST['remove_image'] == 1) {
            if ($batch['image'] && file_exists('../' . $batch['image'])) unlink('../' . $batch['image']);
            $image_path = null;
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE batches SET breed=?, quantity=?, price_per_bird=?, hatch_date=?, location=?, status=?, image=? WHERE id=? AND farmer_id=?");
            $stmt->bind_param("sidssssii", $breed, $quantity, $price, $hatch_date, $location, $status, $image_path, $id, $farmer_id);
            if ($stmt->execute()) {
                  // 🔍 LOG ACTIVITY
    logActivity($_SESSION['user_id'], 'edit_batch', "Edited batch ID: $id - $breed (Qty: $quantity)");
                $_SESSION['message'] = "Batch updated successfully!";
                $_SESSION['msg_type'] = "success";
                redirect('my_batches.php');
            } else $errors[] = "Database error: " . $conn->error;
        }
    }
}

$csrf_token = generateCSRFToken();
$page_title = "Edit Batch";
$priceInfo = getPriceBreakdown($batch['price_per_bird'], $batch['hatch_date']);
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2>Edit Batch</h2>
</div>

<?php if (!empty($errors)): ?><div class="alert alert-danger"><?= implode('<br>', $errors) ?></div><?php endif; ?>

<!-- Loss Alert Banner -->
<?php if ($priceInfo['is_loss']): ?>
    <div class="alert alert-<?= $priceInfo['is_critical'] ? 'danger' : 'warning' ?>">
        <i class="bi bi-exclamation-triangle"></i>
        <strong><?= $priceInfo['is_critical'] ? '🚨 CRITICAL:' : '⚠️' ?></strong> 
        <?= $priceInfo['status_text'] ?>
        <?php if ($priceInfo['is_doubled']): ?>
            <br><small>Current price: <strong>Tsh <?= number_format($priceInfo['current'], 2) ?></strong> (doubled from Tsh <?= number_format($priceInfo['base'], 2) ?>)</small>
        <?php endif; ?>
        <?php if ($priceInfo['age'] > 65): ?>
            <br><small>Your chickens are <?= $priceInfo['age'] ?> days old. Consider selling urgently!</small>
        <?php endif; ?>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Breed</label>
            <input type="text" name="breed" class="form-control" required value="<?= htmlspecialchars($batch['breed']) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Quantity</label>
            <input type="number" name="quantity" class="form-control" min="1" required value="<?= $batch['quantity'] ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Base Price per Bird (Tsh)</label>
            <input type="number" step="0.01" name="price" class="form-control" min="0" required value="<?= $batch['price_per_bird'] ?>">
            <small class="text-muted">Current selling price: <strong>Tsh <?= number_format($priceInfo['current'], 2) ?></strong></small>
        </div>
        <div class="col-md-6">
            <label class="form-label">Hatch Date</label>
            <input type="date" name="hatch_date" class="form-control" max="<?= date('Y-m-d') ?>" required value="<?= $batch['hatch_date'] ?>">
            <small class="text-muted">Age: <?= $priceInfo['age'] ?> days</small>
        </div>
        <div class="col-md-6">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control" required value="<?= htmlspecialchars($batch['location']) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="available" <?= $batch['status']=='available'?'selected':'' ?>>Available</option>
                <option value="reserved" <?= $batch['status']=='reserved'?'selected':'' ?>>Reserved</option>
                <option value="sold" <?= $batch['status']=='sold'?'selected':'' ?>>Sold</option>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label">Current Image</label>
            <?php if ($batch['image']): ?>
                <div class="mb-2"><img src="<?= SITE_URL . $batch['image'] ?>" alt="Batch image" style="max-width:200px; max-height:200px; border:1px solid #ddd; padding:5px;">
                <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_image" value="1" id="removeImage"><label class="form-check-label text-danger" for="removeImage">Remove current image</label></div></div>
            <?php else: ?><p class="text-muted">No image uploaded.</p><?php endif; ?>
        </div>
        <div class="col-12">
            <label class="form-label">Upload New Image (optional)</label>
            <input type="file" name="image" class="form-control" accept="image/*">
            <small class="text-muted">Max 2MB. Leave empty to keep current image.</small>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-success">Update Batch</button>
            <a href="my_batches.php" class="btn btn-secondary">Cancel</a>
        </div>
    </div>
</form>
<?php
$content = ob_get_clean();
include '../layout.php';
?>