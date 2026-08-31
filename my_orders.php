<?php
require_once '../includes/auth.php';
if ($_SESSION['role'] !== 'buyer') redirect('../index.php');
$buyer_id = $_SESSION['user_id'];

$result = $conn->query("
    SELECT o.*, b.breed, b.price_per_bird, b.location, u.name as farmer_name
    FROM orders o
    JOIN batches b ON o.batch_id = b.id
    JOIN users u ON b.farmer_id = u.id
    WHERE o.buyer_id = $buyer_id
    ORDER BY o.order_date DESC
");

$page_title = "My Orders";
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2>My Orders</h2>
</div>
<div class="table-responsive">
    <table class="table table-striped" id="ordersTable">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Breed</th>
                <th>Qty</th>
                <th>Subtotal</th>
                <th>Delivery Fee</th>
                <th>Total</th>
                <th>Farmer</th>
                <th>Delivery</th>
                <th>Status</th>
                <th>Date</th>
                <th>Action</th>
                <th colspan="2">Payment Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td>#<?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['breed']) ?></td>
                <td><?= $row['quantity'] ?></td>
                <td><?= number_format($row['total_price'] - $row['delivery_fee'], 2) ?></td>
                <td><?= $row['delivery_fee'] > 0 ? number_format($row['delivery_fee'], 2) : '-' ?></td>
                <td><?= number_format($row['total_price'], 2) ?></td>
                <td><?= htmlspecialchars($row['farmer_name']) ?></td>
                <td><?= str_replace('_', ' ', $row['delivery_method']) ?></td>
                <td><span class="badge bg-<?= $row['status']=='pending'?'warning':($row['status']=='delivered'?'success':'secondary') ?>"><?= $row['status'] ?></span></td>
                <td><?= date('d M Y', strtotime($row['order_date'])) ?></td>
                <td>
                    <?php if ($row['status'] == 'pending'): ?>
                        <a href="cancel_order.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this order? This will restore the quantity to the batch.')">Cancel</a>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td><span class="badge bg-<?= $row['payment_status'] == 'paid' ? 'success' : ($row['payment_status'] == 'pending' ? 'warning' : 'danger') ?>"><?= $row['payment_status'] ?></span></td>
                <td>
    <?php if ($row['payment_status'] == 'pending'): ?>
        <a href="payment.php?order_id=<?= $row['id'] ?>" class="btn btn-sm btn-success">Pay Now</a>
    <?php else: ?>
        <span class="text-muted">Paid</span>
    <?php endif; ?>
    <?php if ($row['status'] == 'pending' && $row['payment_status'] == 'paid'): ?>
        <a href="cancel_order.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this order?')">Cancel</a>
    <?php endif; ?>
</td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<script>
$(document).ready(function() {
    $('#ordersTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[9, 'desc']]
    });
});
</script>
<?php
$content = ob_get_clean();
include '../layout.php';
?>