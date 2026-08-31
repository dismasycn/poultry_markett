<?php
require_once '../includes/auth.php';
if ($_SESSION['role'] !== 'farmer') {
    redirect('../index.php');
}

$farmer_id = $_SESSION['user_id'];

$result = $conn->query("
    SELECT o.*, u.name as buyer_name, u.phone as buyer_phone, b.breed, b.price_per_bird, b.hatch_date, b.farmer_id
    FROM orders o
    JOIN batches b ON o.batch_id = b.id
    JOIN users u ON o.buyer_id = u.id
    WHERE b.farmer_id = $farmer_id
    ORDER BY o.order_date DESC
");

$page_title = "Orders Received";
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2>Orders Received</h2>
</div>

<?php if ($result->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped" id="ordersTable">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Buyer</th>
                    <th>Breed</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                    <th>Delivery Fee</th>
                    <th>Total</th>
                    <th>Delivery</th>
                    <th>Status</th>
                    <th>Alert</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): 
                    // Get price info – if something fails, use default to avoid errors
                    try {
                        $priceInfo = getPriceBreakdown($row['price_per_bird'], $row['hatch_date']);
                    } catch (Exception $e) {
                        $priceInfo = [
                            'status_class' => 'secondary',
                            'status_text' => 'N/A',
                            'is_loss' => false,
                            'is_critical' => false,
                        ];
                    }
                ?>
                    <tr>
                        <td>#<?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['buyer_name']) ?><br><small><?= $row['buyer_phone'] ?></small></td>
                        <td><?= htmlspecialchars($row['breed']) ?></td>
                        <td><?= $row['quantity'] ?></td>
                        <td>Tsh <?= number_format($row['total_price'] - $row['delivery_fee'], 2) ?></td>
                        <td><?= $row['delivery_fee'] > 0 ? 'Tsh '.number_format($row['delivery_fee'], 2) : '-' ?></td>
                        <td><strong>Tsh <?= number_format($row['total_price'], 2) ?></strong></td>
                        <td><?= str_replace('_', ' ', $row['delivery_method']) ?></td>
                        <td><span class="badge bg-<?= $row['status']=='pending'?'warning':($row['status']=='delivered'?'success':'secondary') ?>"><?= $row['status'] ?></span></td>
                        <td>
                            <span class="badge <?= $priceInfo['status_class'] ?>"><?= $priceInfo['status_text'] ?></span>
                            <?php if ($priceInfo['is_loss']): ?>
                                <br><small class="text-danger"><i class="bi bi-exclamation-triangle"></i> <?= $priceInfo['is_critical'] ? 'Critical loss!' : 'Loss incurred!' ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d M Y', strtotime($row['order_date'])) ?></td>
                        <td>
                            <form method="POST" action="../admin/update_order.php" class="d-inline">
                                <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="pending" <?= $row['status']=='pending'?'selected':'' ?>>Pending</option>
                                    <option value="confirmed" <?= $row['status']=='confirmed'?'selected':'' ?>>Confirm</option>
                                    <option value="in_transit" <?= $row['status']=='in_transit'?'selected':'' ?>>In Transit</option>
                                    <option value="delivered" <?= $row['status']=='delivered'?'selected':'' ?>>Delivered</option>
                                    <option value="cancelled" <?= $row['status']=='cancelled'?'selected':'' ?>>Cancel</option>
                                </select>
                            </form>
                            <a href="../messages.php?with=<?= $row['buyer_id'] ?>" class="btn btn-sm btn-outline-info mt-1" title="Message Buyer"><i class="bi bi-chat"></i></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-info">No orders received yet.</div>
<?php endif; ?>

<script>
$(document).ready(function() {
    $('#ordersTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[10, 'desc']],
        columnDefs: [
            // Ensure all columns are correctly aligned – you can adjust as needed
            { orderable: false, targets: [11] } // disable ordering on Action column
        ]
    });
});
</script>
<?php
$content = ob_get_clean();
include '../layout.php';
?>