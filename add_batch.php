<?php
require_once '../includes/auth.php';
if ($_SESSION['role'] !== 'farmer') {
    redirect('../index.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid security token.";
    } else {
        $breed = sanitize($_POST['breed']);
        $quantity = (int)$_POST['quantity'];
        $price = (float)$_POST['price'];
        $hatch_date = $_POST['hatch_date'];
        $location = sanitize($_POST['location']);

        // Validation
        if (empty($breed)) $errors[] = "Breed is required.";
        if ($quantity < 1) $errors[] = "Quantity must be at least 1.";
        if ($price < 0) $errors[] = "Price cannot be negative.";
        if (empty($hatch_date) || !strtotime($hatch_date)) $errors[] = "Invalid hatch date.";
        if ($hatch_date > date('Y-m-d')) $errors[] = "Hatch date cannot be in the future.";
        if (empty($location)) $errors[] = "Location is required.";

        // Image upload handling
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $max_size = 2 * 1024 * 1024; // 2MB
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if ($file['size'] > $max_size) {
                $errors[] = "Image size must be less than 2MB.";
            }
            if (!in_array($file['type'], $allowed_types)) {
                $errors[] = "Only JPG, PNG, GIF, and WEBP images are allowed.";
            }
            if (empty($errors)) {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_name = uniqid() . '.' . $extension;
                $upload_dir = '../assets/uploads/batches/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $destination = $upload_dir . $new_name;
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $image_path = 'assets/uploads/batches/' . $new_name;
                } else {
                    $errors[] = "Failed to upload image. Check folder permissions.";
                }
            }
        }

        if (empty($errors)) {
            $farmer_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("INSERT INTO batches (farmer_id, breed, quantity, price_per_bird, hatch_date, location, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isidsss", $farmer_id, $breed, $quantity, $price, $hatch_date, $location, $image_path);
            if ($stmt->execute()) {
                  // 🔍 LOG ACTIVITY
    logActivity($_SESSION['user_id'], 'add_batch', "Added batch: $breed (Qty: $quantity, Price: Tsh $price)");
                $_SESSION['message'] = "Batch added successfully!";
                $_SESSION['msg_type'] = "success";
                //logActivity($_SESSION['user_id'], 'add_batch', "Added batch: {$breed} (Qty: $quantity)");
                redirect('my_batches.php');
            } else {
                $errors[] = "Database error: " . $conn->error;
            }
        }
    }
}

$csrf_token = generateCSRFToken();
$page_title = "Add New Batch";
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Add New Chicken Batch</h1>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?= implode('<br>', $errors) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    <div class="row g-3">
        <div class="col-md-6">
            <label for="breed" class="form-label">Breed <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="breed" name="breed" required value="<?= htmlspecialchars($_POST['breed'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="quantity" name="quantity" min="1" required value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label for="price" class="form-label">Price per Bird (Tsh) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" class="form-control" id="price" name="price" min="0" required value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label for="hatch_date" class="form-label">Hatch Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="hatch_date" name="hatch_date" max="<?= date('Y-m-d') ?>" required value="<?= htmlspecialchars($_POST['hatch_date'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label for="location" class="form-label">Farm Location <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="location" name="location" placeholder="e.g., Dar es Salaam, Ubungo" required value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label for="image" class="form-label">Batch Image (optional)</label>
            <input type="file" class="form-control" id="image" name="image" accept="image/*">
            <small class="text-muted">Max 2MB. JPG, PNG, GIF, WEBP allowed.</small>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-success">Add Batch</button>
            <a href="my_batches.php" class="btn btn-secondary">Cancel</a>
        </div>
    </div>
</form>
<?php
$content = ob_get_clean();
include '../layout.php';
?>