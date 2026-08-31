<?php
require_once '../includes/auth.php';
if ($_SESSION['role'] !== 'admin') redirect('../index.php');

$page_title = "Manage Orders";
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2>All Orders</h2>
</div>
<table id="ordersTable" class="table table-striped">
    <thead>
        <tr><th>ID</th><th>Buyer</th><th>Batch</th><th>Quantity</th><th>Total</th><th>Status</th><th>Date</th><th>Action</th></tr>
    </thead>
    <tbody>
        <?php
        $result = $conn->query("
            SELECT o.*, u.name as buyer_name, b.breed 
            FROM orders o 
            JOIN users u ON o.buyer_id = u.id 
            JOIN batches b ON o.batch_id = b.id 
            ORDER BY o.order_date DESC
        ");
        while ($row = $result->fetch_assoc()):
        ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['buyer_name']) ?></td>
            <td><?= htmlspecialchars($row['breed']) ?></td>
            <td><?= $row['quantity'] ?></td>
            <td>Tsh <?= number_format($row['total_price'], 2) ?></td>
            <td><span class="badge bg-<?= $row['status'] == 'pending' ? 'warning' : ($row['status'] == 'delivered' ? 'success' : 'secondary') ?>"><?= $row['status'] ?></span></td>
            <td><?= $row['order_date'] ?></td>
            <td>
                <form method="POST" action="update_order.php" class="d-inline">
                    <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="pending" <?= $row['status']=='pending'?'selected':'' ?>>Pending</option>
                        <option value="confirmed" <?= $row['status']=='confirmed'?'selected':'' ?>>Confirm</option>
                        <option value="in_transit" <?= $row['status']=='in_transit'?'selected':'' ?>>In Transit</option>
                        <option value="delivered" <?= $row['status']=='delivered'?'selected':'' ?>>Delivered</option>
                        <option value="cancelled" <?= $row['status']=='cancelled'?'selected':'' ?>>Cancel</option>
                    </select>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<script>
$(document).ready(function() {
    $('#ordersTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[6, 'desc']]
    });
});
</script>
<?php
$content = ob_get_clean();
include '../layout.php';
?>