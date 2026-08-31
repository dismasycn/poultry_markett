<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
if (!isLoggedIn() || $_SESSION['role'] !== 'buyer') {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$order_id = (int)($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    $_SESSION['message'] = "Invalid order.";
    $_SESSION['msg_type'] = "danger";
    redirect('my_orders.php');
}

// Fetch order details with batch info
$stmt = $conn->prepare("
    SELECT o.*, b.breed, b.price_per_bird, b.hatch_date, u.name as farmer_name, u.phone as farmer_phone
    FROM orders o 
    JOIN batches b ON o.batch_id = b.id 
    JOIN users u ON b.farmer_id = u.id 
    WHERE o.id = ? AND o.buyer_id = ? AND o.payment_status = 'pending'
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    $_SESSION['message'] = "Order not found or already paid.";
    $_SESSION['msg_type'] = "danger";
    redirect('my_orders.php');
}
// Free result set to avoid "Commands out of sync"
$result->free();

$payment_success = false;
$payment_method = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? 'mpesa';
    // Simulate payment processing - always succeeds
    $payment_success = true;
    // Update order payment status
    $update = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ? AND buyer_id = ?");
    $update->bind_param("ii", $order_id, $user_id);
    if ($update->execute()) {
        // 🔍 LOG ACTIVITY – using fetched order data
        logActivity(
            $user_id,
            'payment_success',
            "Order #$order_id: {$order['breed']} x {$order['quantity']} paid via $payment_method"
        );
        $_SESSION['message'] = "Payment successful! Your order is now confirmed.";
        $_SESSION['msg_type'] = "success";
        redirect('my_orders.php');
    } else {
        $payment_success = false;
        logActivity($user_id, 'payment_failed', "Order #$order_id payment failed via $payment_method");
        $_SESSION['message'] = "Payment failed. Please try again.";
        $_SESSION['msg_type'] = "danger";
    }
}

$page_title = "Complete Payment – Poultry Market";
ob_start();
?>
<div class="container d-flex align-items-center justify-content-center" style="min-height: 80vh;">
    <div class="row w-100 justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-success text-white text-center py-3 border-0">
                    <h4 class="fw-bold mb-0"><i class="bi bi-credit-card"></i> Complete Payment</h4>
                    <p class="mb-0 small">Order #<?= $order['id'] ?></p>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <h5>Order Summary</h5>
                        <ul class="list-unstyled">
                            <li><strong>Breed:</strong> <?= htmlspecialchars($order['breed']) ?></li>
                            <li><strong>Quantity:</strong> <?= $order['quantity'] ?></li>
                            <li><strong>Subtotal:</strong> Tsh <?= number_format($order['total_price'] - $order['delivery_fee'], 2) ?></li>
                            <?php if ($order['delivery_fee'] > 0): ?>
                                <li><strong>Delivery Fee:</strong> Tsh <?= number_format($order['delivery_fee'], 2) ?></li>
                            <?php endif; ?>
                            <li><strong>Total:</strong> <span class="fw-bold text-success">Tsh <?= number_format($order['total_price'], 2) ?></span></li>
                            <li><strong>Farmer:</strong> <?= htmlspecialchars($order['farmer_name']) ?></li>
                            <li><strong>Phone:</strong> <?= htmlspecialchars($order['farmer_phone']) ?></li>
                        </ul>
                    </div>
                    <hr>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Select Payment Method</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" value="mpesa" id="mpesa" checked>
                                    <label class="form-check-label" for="mpesa"><i class="bi bi-phone"></i> M‑Pesa</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" value="airtel" id="airtel">
                                    <label class="form-check-label" for="airtel"><i class="bi bi-phone"></i> Airtel Money</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" value="card" id="card">
                                    <label class="form-check-label" for="card"><i class="bi bi-credit-card"></i> Card</label>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle"></i> This is a mock payment. No real money will be charged.
                        </div>
                        <button type="submit" class="btn btn-success btn-lg w-100 fw-semibold">
                            <i class="bi bi-check-circle"></i> Pay Now
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include '../layout.php';
?>