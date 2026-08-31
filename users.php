<?php
require_once '../includes/auth.php';
if ($_SESSION['role'] !== 'admin') {
    redirect('../index.php');
}

// Handle role update via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    $user_id = (int)$_POST['user_id'];
    $new_role = $_POST['role'];
    if (!in_array($new_role, ['admin', 'farmer', 'buyer'])) {
        $new_role = 'buyer';
    }
    // Prevent admin from changing own role
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['message'] = "You cannot change your own role.";
        $_SESSION['msg_type'] = "danger";
        redirect('users.php');
    }
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $new_role, $user_id);
    if ($stmt->execute()) {
        // 🔍 LOG ACTIVITY
        logActivity($_SESSION['user_id'], 'admin_role_change', "Changed role of {$old_role['name']} to $new_role");
        $_SESSION['message'] = "User role updated successfully.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating role: " . $conn->error;
        $_SESSION['msg_type'] = "danger";
    }
    redirect('users.php');
}

// Handle user deletion via GET
if (isset($_GET['delete'])) {
    if (!verifyCSRFToken($_GET['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    $user_id = (int)$_GET['delete'];
    // Prevent self-deletion
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['message'] = "You cannot delete your own account.";
        $_SESSION['msg_type'] = "danger";
        redirect('users.php');
    }
    // Check if user has any batches or orders – optional: prevent deletion with dependencies
    $check = $conn->query("SELECT COUNT(*) FROM batches WHERE farmer_id = $user_id");
    $batch_count = $check->fetch_row()[0];
    if ($batch_count > 0) {
        $_SESSION['message'] = "Cannot delete this user because they have active batches. Please reassign or delete batches first.";
        $_SESSION['msg_type'] = "danger";
        redirect('users.php');
    }
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        // 🔍 LOG ACTIVITY
    logActivity($_SESSION['user_id'], 'admin_user_delete', "Deleted user: {$user_info['name']} ({$user_info['email']})");
        $_SESSION['message'] = "User deleted successfully.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting user: " . $conn->error;
        $_SESSION['msg_type'] = "danger";
    }
    redirect('users.php');
}

// Fetch all users
$result = $conn->query("SELECT id, name, email, phone, location, role, created_at FROM users ORDER BY created_at DESC");

$page_title = "Manage Users";
$csrf_token = generateCSRFToken();
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2>Manage Users</h2>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-person-plus"></i> Add User
    </button>
</div>

<!-- User Table -->
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $result->fetch_assoc()): 
                        $is_self = ($user['id'] == $_SESSION['user_id']);
                        $roleClass = match($user['role']) {
                            'admin' => 'bg-danger text-white',
                            'farmer' => 'bg-success text-white',
                            default => 'bg-secondary text-white'
                        };
                    ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($user['location'] ?? '-') ?></td>
                        <td>
                            <form method="POST" class="role-form">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <select name="role" class="form-select form-select-sm" <?= $is_self ? 'disabled' : '' ?> onchange="this.form.submit()">
                                    <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
                                    <option value="farmer" <?= $user['role']=='farmer'?'selected':'' ?>>Farmer</option>
                                    <option value="buyer" <?= $user['role']=='buyer'?'selected':'' ?>>Buyer</option>
                                </select>
                                <input type="hidden" name="update_role" value="1">
                            </form>
                        </td>
                        <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <?php if (!$is_self): ?>
                                    <a href="#" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal" 
                                       data-id="<?= $user['id'] ?>" 
                                       data-name="<?= htmlspecialchars($user['name']) ?>" 
                                       data-email="<?= htmlspecialchars($user['email']) ?>"
                                       data-phone="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                       data-location="<?= htmlspecialchars($user['location'] ?? '') ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="users.php?delete=<?= $user['id'] ?>&csrf_token=<?= $csrf_token ?>" class="btn btn-outline-danger btn-sm" 
                                       onclick="return confirm('Delete user <?= htmlspecialchars($user['name']) ?> permanently?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-secondary">You</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="add_user.php">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <div class="mb-2">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="buyer">Buyer</option>
                            <option value="farmer">Farmer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="edit_user.php">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-2">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" id="edit_location" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" name="password" class="form-control" minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // DataTable
    $('#usersTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[0, 'desc']]
    });

    // Edit modal: populate fields
    $('#editUserModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        $('#edit_user_id').val(button.data('id'));
        $('#edit_name').val(button.data('name'));
        $('#edit_email').val(button.data('email'));
        $('#edit_phone').val(button.data('phone'));
        $('#edit_location').val(button.data('location'));
    });
});
</script>
<?php
$content = ob_get_clean();
include '../layout.php';
?>