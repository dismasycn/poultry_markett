<?php
require_once '../includes/auth.php';
if ($_SESSION['role'] !== 'admin') {
    redirect('../index.php');
}

// Get filters
$filter = $_GET['filter'] ?? '';
$user_id = (int)($_GET['user_id'] ?? 0);
$limit = (int)($_GET['limit'] ?? 100);

// Get logs
$logs = getActivityLogs($limit, $filter, $user_id);
$stats = getActivityStats();

// Get users for filter dropdown
$users = $conn->query("SELECT id, name, role FROM users ORDER BY name");

$page_title = "Activity Logs – Admin";
$csrf_token = generateCSRFToken();
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2><i class="bi bi-clock-history"></i> Activity Logs</h2>
    <div>
        <button onclick="window.location.reload()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer"></i> Print
        </button>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h2 class="text-primary"><?= number_format($stats['total']) ?></h2>
                <p class="text-muted mb-0">Total Activities</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h2 class="text-success"><?= number_format($stats['today']) ?></h2>
                <p class="text-muted mb-0">Today's Activities</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h2 class="text-info"><?= number_format($stats['unique_users']) ?></h2>
                <p class="text-muted mb-0">Active Users</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h2 class="text-warning"><?= count($stats['top_actions']) ?></h2>
                <p class="text-muted mb-0">Action Types</p>
            </div>
        </div>
    </div>
</div>

<!-- Top Actions -->
<div class="row g-3 mb-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Most Common Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($stats['top_actions'] as $action): ?>
                        <span class="badge <?= getActionBadgeClass($action['action']) ?> p-2">
                            <?= getActionLabel($action['action']) ?>: <?= $action['count'] ?>
                        </span>
                    <?php endforeach; ?>
                    <?php if (empty($stats['top_actions'])): ?>
                        <span class="text-muted">No activities recorded yet.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Action Filter</label>
                <input type="text" name="filter" class="form-control" placeholder="Search actions..." value="<?= htmlspecialchars($filter) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">User</label>
                <select name="user_id" class="form-select">
                    <option value="0">All Users</option>
                    <?php while ($u = $users->fetch_assoc()): ?>
                        <option value="<?= $u['id'] ?>" <?= $user_id == $u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['name']) ?> (<?= $u['role'] ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Limit</label>
                <select name="limit" class="form-select">
                    <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
                    <option value="200" <?= $limit == 200 ? 'selected' : '' ?>>200</option>
                    <option value="500" <?= $limit == 500 ? 'selected' : '' ?>>500</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Apply</button>
            </div>
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Activity Logs</h5>
        <span class="badge bg-secondary"><?= count($logs) ?> records</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="logsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= $log['id'] ?></td>
                                <td><?= date('d M Y H:i:s', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <?php if ($log['user_id']): ?>
                                        <strong><?= htmlspecialchars($log['user_name'] ?? 'Unknown') ?></strong>
                                        <br><small class="text-muted"><?= $log['user_role'] ?? '' ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Guest</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= getActionBadgeClass($log['action']) ?>">
                                        <?= getActionLabel($log['action']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($log['description'] ?? '') ?></td>
                                <td><code><?= htmlspecialchars($log['ip_address'] ?? '') ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No activity logs found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#logsTable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: [4, 5] }
        ]
    });
});
</script>
<?php
$content = ob_get_clean();
include '../layout.php';
?>