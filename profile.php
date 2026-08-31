<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch current user data
$stmt = $conn->prepare("SELECT name, email, phone, location, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    $_SESSION['message'] = "User not found.";
    $_SESSION['msg_type'] = "danger";
    redirect('logout.php');
}

// If farmer, fetch farm profile (or create if missing)
$farm_profile = null;
if ($role == 'farmer') {
    $profile_stmt = $conn->prepare("SELECT farm_name, farm_address FROM farmer_profiles WHERE user_id = ?");
    $profile_stmt->bind_param("i", $user_id);
    $profile_stmt->execute();
    $farm_profile = $profile_stmt->get_result()->fetch_assoc();
    
    // If no profile exists, create one with defaults
    if (!$farm_profile) {
        $insert = $conn->prepare("INSERT INTO farmer_profiles (user_id, farm_name, farm_address) VALUES (?, '', '')");
        $insert->bind_param("i", $user_id);
        $insert->execute();
        $farm_profile = ['farm_name' => '', 'farm_address' => ''];
    }
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid security token. Please try again.";
    } else {
        $action = $_POST['action'] ?? 'update_profile';

        if ($action === 'update_profile') {
            // Update personal fields
            $name = sanitize($_POST['name']);
            $phone = sanitize($_POST['phone']);
            $location = sanitize($_POST['location']);

            if (empty($name)) {
                $errors[] = "Name is required.";
            }

            // If farmer, also update farm details
            $farm_name = $role == 'farmer' ? sanitize($_POST['farm_name']) : null;
            $farm_address = $role == 'farmer' ? sanitize($_POST['farm_address']) : null;

            if ($role == 'farmer' && empty($farm_name)) {
                $errors[] = "Farm name is required.";
            }

            if (empty($errors)) {
                // Update user table
                $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, location = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $phone, $location, $user_id);
                if ($stmt->execute()) {
                     // 🔍 LOG ACTIVITY
    logActivity($_SESSION['user_id'], 'profile_update', "Updated profile: name=$name, phone=$phone, location=$location");
                    $_SESSION['name'] = $name; // Update session name
                    $user['name'] = $name;
                    $user['phone'] = $phone;
                    $user['location'] = $location;
                    $success = true;

                    // If farmer, update farm profile
                    if ($role == 'farmer') {
                        $update_farm = $conn->prepare("UPDATE farmer_profiles SET farm_name = ?, farm_address = ? WHERE user_id = ?");
                        $update_farm->bind_param("ssi", $farm_name, $farm_address, $user_id);
                        if ($update_farm->execute()) {
                            $farm_profile['farm_name'] = $farm_name;
                            $farm_profile['farm_address'] = $farm_address;
                        } else {
                            $errors[] = "Failed to update farm profile: " . $conn->error;
                        }
                    }
                } else {
                    $errors[] = "Database error: " . $conn->error;
                }
            }
        } elseif ($action === 'change_password') {
            // Change password
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            $pass_check = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $pass_check->bind_param("i", $user_id);
            $pass_check->execute();
            $stored_hash = $pass_check->get_result()->fetch_assoc()['password'];

            if (!verifyPassword($current_password, $stored_hash)) {
                $errors[] = "Current password is incorrect.";
            }
            if (strlen($new_password) < 6) {
                $errors[] = "New password must be at least 6 characters.";
            }
            if ($new_password !== $confirm_password) {
                $errors[] = "Passwords do not match.";
            }

            if (empty($errors)) {
                $hashed = hashPassword($new_password);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed, $user_id);
                if ($stmt->execute()) {
                    // 🔍 LOG ACTIVITY
    logActivity($_SESSION['user_id'], 'password_change', "Changed password");
                    $success = true;
                    $_SESSION['message'] = "Password changed successfully.";
                    $_SESSION['msg_type'] = "success";
                } else {
                    $errors[] = "Database error: " . $conn->error;
                }
            }
        }
    }
}

$csrf_token = generateCSRFToken();
$page_title = "My Profile – Poultry Market";
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2><i class="bi bi-person-circle"></i> My Profile</h2>
    <span class="badge bg-success"><?= ucfirst($role) ?></span>
</div>

<div class="row">
    <!-- Profile Update Card -->
    <div class="col-md-<?= $role == 'farmer' ? '7' : '6' ?>">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Personal Information</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($user['name']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                        <small class="text-muted">Email cannot be changed. Contact support if needed.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($user['location'] ?? '') ?>">
                    </div>

                    <!-- Farm-specific fields (only for farmers) -->
                    <?php if ($role == 'farmer'): ?>
                        <hr>
                        <h5 class="mb-3"><i class="bi bi-tractor"></i> Farm Details</h5>
                        <div class="mb-3">
                            <label class="form-label">Farm Name <span class="text-danger">*</span></label>
                            <input type="text" name="farm_name" class="form-control" required value="<?= htmlspecialchars($farm_profile['farm_name'] ?? '') ?>">
                            <small class="text-muted">This name appears on your chicken listings.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Farm Address</label>
                            <textarea name="farm_address" class="form-control" rows="2" placeholder="Full address of your farm"><?= htmlspecialchars($farm_profile['farm_address'] ?? '') ?></textarea>
                            <small class="text-muted">Buyers can see this when placing orders.</small>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Card -->
    <div class="col-md-<?= $role == 'farmer' ? '5' : '6' ?>">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Change Password</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <div class="input-group">
                            <input type="password" name="current_password" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">Minimum 6 characters.</small>
                        <div class="mt-2">
                            <div class="progress" style="height: 4px;">
                                <div id="passwordStrength" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                            </div>
                            <small id="strengthText" class="text-muted">Password strength: <span id="strengthLabel">Weak</span></small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-key"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Quick Links -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5><i class="bi bi-link-45deg"></i> Quick Actions</h5>
                <div class="d-flex flex-wrap gap-2">
                    <?php if ($role == 'farmer'): ?>
                        <a href="farmer/add_batch.php" class="btn btn-sm btn-outline-success">Add Batch</a>
                        <a href="farmer/my_batches.php" class="btn btn-sm btn-outline-primary">My Batches</a>
                        <a href="farmer/orders.php" class="btn btn-sm btn-outline-warning">Orders Received</a>
                        <a href="farmer/dashboard.php" class="btn btn-sm btn-outline-secondary">Dashboard</a>
                    <?php elseif ($role == 'buyer'): ?>
                        <a href="buyer/browse.php" class="btn btn-sm btn-outline-success">Browse Chickens</a>
                        <a href="buyer/my_orders.php" class="btn btn-sm btn-outline-primary">My Orders</a>
                        <a href="buyer/dashboard.php" class="btn btn-sm btn-outline-secondary">Dashboard</a>
                    <?php elseif ($role == 'admin'): ?>
                        <a href="admin/dashboard.php" class="btn btn-sm btn-outline-primary">Dashboard</a>
                        <a href="admin/users.php" class="btn btn-sm btn-outline-secondary">Manage Users</a>
                        <a href="admin/reports.php" class="btn btn-sm btn-outline-info">Reports</a>
                    <?php endif; ?>
                    <a href="messages.php" class="btn btn-sm btn-outline-secondary">Messages</a>
                    <a href="logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(btn) {
    const input = btn.parentElement.querySelector('input');
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

// Password strength meter for new password field
document.querySelector('input[name="new_password"]').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('passwordStrength');
    const strengthLabel = document.getElementById('strengthLabel');
    let strength = 0;
    let label = 'Weak';
    let color = '#dc3545';

    if (password.length >= 6) strength += 1;
    if (password.length >= 10) strength += 1;
    if (/[A-Z]/.test(password) && /[a-z]/.test(password)) strength += 1;
    if (/\d/.test(password)) strength += 1;
    if (/[^A-Za-z0-9]/.test(password)) strength += 1;

    if (strength >= 4) { label = 'Strong'; color = '#198754'; }
    else if (strength >= 3) { label = 'Good'; color = '#ffc107'; }
    else if (strength >= 2) { label = 'Fair'; color = '#fd7e14'; }
    else { label = 'Weak'; color = '#dc3545'; }

    const percentage = (strength / 5) * 100;
    strengthBar.style.width = percentage + '%';
    strengthBar.style.backgroundColor = color;
    strengthLabel.textContent = label;
    strengthLabel.style.color = color;
});
</script>
<?php
$content = ob_get_clean();
include 'layout.php';
?>