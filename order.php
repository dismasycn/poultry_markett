<?php
require_once '../includes/auth.php';
if ($_SESSION['role'] !== 'buyer') {
    redirect('../index.php');
}

$buyer_id = $_SESSION['user_id'];
$batch_id = (int)$_GET['batch_id'];

// Fetch batch details (ensure available and has stock)
$stmt = $conn->prepare("
    SELECT b.*, u.name as farmer_name, u.phone as farmer_phone, u.email as farmer_email 
    FROM batches b 
    JOIN users u ON b.farmer_id = u.id 
    WHERE b.id = ? AND b.status = 'available' AND b.quantity > 0
");
$stmt->bind_param("i", $batch_id);
$stmt->execute();
$batch = $stmt->get_result()->fetch_assoc();

if (!$batch) {
    $_SESSION['message'] = "This batch is no longer available.";
    $_SESSION['msg_type'] = "danger";
    redirect('browse.php');
}

define('DELIVERY_FEE', 10000);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid security token.";
    } else {
        $quantity = (int)$_POST['quantity'];
        $delivery_method = $_POST['delivery_method'];
        $delivery_address = sanitize($_POST['delivery_address']);
        $notes = sanitize($_POST['notes']);

        // Validation
        if ($quantity < 1) $errors[] = "Quantity must be at least 1.";
        if ($quantity > $batch['quantity']) $errors[] = "Not enough stock. Available: {$batch['quantity']}.";
        if (!in_array($delivery_method, ['seller_delivery', 'self_pickup'])) $delivery_method = 'self_pickup';
        if ($delivery_method == 'seller_delivery' && empty($delivery_address)) $errors[] = "Delivery address is required.";

        if (empty($errors)) {
            // Start transaction
            $conn->begin_transaction();
            try {
                // Lock the batch row to prevent race conditions
                $lock_stmt = $conn->prepare("SELECT quantity, status FROM batches WHERE id = ? FOR UPDATE");
                $lock_stmt->bind_param("i", $batch_id);
                $lock_stmt->execute();
                $current = $lock_stmt->get_result()->fetch_assoc();

                if (!$current || $current['status'] != 'available' || $current['quantity'] < $quantity) {
                    throw new Exception("Batch is no longer available or has insufficient stock.");
                }

                // Calculate totals
                $currentPrice = getCurrentPrice($batch['price_per_bird'], $batch['hatch_date']);
$subtotal = $quantity * $currentPrice;
$delivery_fee = ($delivery_method == 'seller_delivery') ? DELIVERY_FEE : 0;
$total = $subtotal + $delivery_fee;

                // Insert order
               $stmt = $conn->prepare("
    INSERT INTO orders (buyer_id, batch_id, quantity, total_price, delivery_fee, delivery_method, delivery_address, notes, payment_status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
");
                $stmt->bind_param("iiiddsss", $buyer_id, $batch_id, $quantity, $total, $delivery_fee, $delivery_method, $delivery_address, $notes);
                $stmt->execute();

                // Update batch quantity and status
                $new_qty = $current['quantity'] - $quantity;
                $new_status = ($new_qty == 0) ? 'sold' : 'available';
                $update_stmt = $conn->prepare("UPDATE batches SET quantity = ?, status = ? WHERE id = ?");
                $update_stmt->bind_param("isi", $new_qty, $new_status, $batch_id);
                $update_stmt->execute();

                $conn->commit();

                // Send email notification to farmer
                $subject = "New order for your chickens!";
                $body = "
                    <h3>New Order Received</h3>
                    <p>Hello {$batch['farmer_name']},</p>
                    <p>A buyer has placed an order for your <strong>{$batch['breed']}</strong> chickens.</p>
                    <ul>
                        <li><strong>Quantity:</strong> $quantity</li>
                        <li><strong>Subtotal:</strong> Tsh " . number_format($subtotal, 2) . "</li>
                        " . ($delivery_fee > 0 ? "<li><strong>Delivery Fee:</strong> Tsh " . number_format($delivery_fee, 2) . "</li>" : "") . "
                        <li><strong>Total:</strong> Tsh " . number_format($total, 2) . "</li>
                        <li><strong>Delivery Method:</strong> " . str_replace('_', ' ', $delivery_method) . "</li>
                        " . ($delivery_address ? "<li><strong>Address:</strong> $delivery_address</li>" : "") . "
                        " . ($notes ? "<li><strong>Notes:</strong> $notes</li>" : "") . "
                    </ul>
                    <p>Please log in to manage this order.</p>
                    <p><a href='" . SITE_URL . "farmer/orders.php'>View Orders</a></p>
                ";
              //  sendEmail($batch['farmer_email'], $subject, $body);

              // After successful insert
$_SESSION['message'] = "Order placed! Please complete payment.";
$_SESSION['msg_type'] = "info";
redirect('payment.php?order_id=' . $stmt->insert_id);

            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = $e->getMessage();
            }
        }
    }
}

$csrf_token = generateCSRFToken();
$page_title = "Place Order";
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2><i class="bi bi-cart-plus"></i> Place Order</h2>
    <a href="browse.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Browse</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?= implode('<br>', $errors) ?></div>
<?php endif; ?>

<div class="row">
    <!-- Batch Summary -->
    <div class="col-md-5">
        <div class="card shadow-sm mb-3">
            <?php if ($batch['image']): ?>
                <img src="<?= SITE_URL . $batch['image'] ?>" class="card-img-top" alt="<?= htmlspecialchars($batch['breed']) ?>" style="height:220px; object-fit:cover;">
            <?php else: ?>
                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:220px;">
                    <span class="text-muted"><i class="bi bi-image fs-1"></i></span>
                </div>
            <?php endif; ?>
            <div class="card-body">
                <h4 class="card-title"><?= htmlspecialchars($batch['breed']) ?></h4>
                <ul class="list-unstyled">
                    <li><strong>Farmer:</strong> <?= htmlspecialchars($batch['farmer_name']) ?> (<?= htmlspecialchars($batch['farmer_phone']) ?>)</li>
                    <li><strong>Location:</strong> <?= htmlspecialchars($batch['location']) ?></li>
                    <li><strong>Price per bird:</strong> Tsh <?= number_format(getCurrentPrice($batch['price_per_bird'], $batch['hatch_date']), 2) ?>
<?php
$priceInfo = getPriceBreakdown($batch['price_per_bird'], $batch['hatch_date']);
if ($priceInfo['is_increased']): ?>
    <br><small class="text-danger">Base: Tsh <?= number_format($priceInfo['base'], 2) ?> + Tsh <?= number_format($priceInfo['increase'], 2) ?> age increase</small>
<?php endif; ?></li>
                    <li><strong>Available quantity:</strong> <?= $batch['quantity'] ?></li>
                    <li><strong>Age:</strong> <?= getAgeInDays($batch['hatch_date']) ?> days</li>
                </ul>
            </div>
        </div>
    </div>
    

    <!-- Order Form -->
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Order Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="orderForm">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                    <!-- Quantity -->
                    <div class="mb-3">
                        <label for="quantity" class="form-label fw-bold">Quantity <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" type="button" id="decrementQty">−</button>
                            <input type="number" class="form-control text-center" id="quantity" name="quantity" 
                                   min="1" max="<?= $batch['quantity'] ?>" value="1" required>
                            <button class="btn btn-outline-secondary" type="button" id="incrementQty">+</button>
                        </div>
                        <small class="text-muted">Max: <?= $batch['quantity'] ?></small>
                        <div id="priceDisplay" class="mt-2 fw-bold text-success">Total: Tsh <?= number_format($batch['price_per_bird'], 2) ?></div>
                    </div>

                    <!-- Delivery Method -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Delivery Method <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="delivery_method" value="self_pickup" id="self_pickup" checked>
                                <label class="form-check-label" for="self_pickup">
                                    <i class="bi bi-person-walking"></i> Self-pickup (No fee)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="delivery_method" value="seller_delivery" id="seller_delivery">
                                <label class="form-check-label" for="seller_delivery">
                                    <i class="bi bi-truck"></i> Farmer delivers (+ Tsh <?= number_format(DELIVERY_FEE, 0) ?> fee)
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Address (conditionally shown) -->
                    <div class="mb-3" id="addressField" style="display:none;">
                        <label for="delivery_address" class="form-label">Delivery Address <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="delivery_address" name="delivery_address" rows="2" placeholder="Enter full delivery address"><?= htmlspecialchars($_SESSION['location'] ?? '') ?></textarea>
                    </div>

                    <!-- Delivery Fee Info -->
                    <div class="mb-3" id="deliveryFeeDisplay" style="display:none;">
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i> Delivery fee of <strong>Tsh <?= number_format(DELIVERY_FEE, 0) ?></strong> will be added.
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes (optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Special instructions, preferred time, etc."></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success btn-lg w-50" id="confirmBtn">
                            <i class="bi bi-check-circle"></i> Review Order
                        </button>
                        <a href="browse.php" class="btn btn-secondary btn-lg w-50">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Please review your order details:</p>
                <ul>
                    <li><strong>Batch:</strong> <?= htmlspecialchars($batch['breed']) ?></li>
                    <li><strong>Quantity:</strong> <span id="confirmQty">1</span></li>
                    <li><strong>Subtotal:</strong> <span id="confirmSubtotal">Tsh <?= number_format($batch['price_per_bird'], 2) ?></span></li>
                    <li id="confirmFeeRow" style="display:none;"><strong>Delivery Fee:</strong> <span id="confirmFee">Tsh <?= number_format(DELIVERY_FEE, 0) ?></span></li>
                    <li><strong>Total:</strong> <span id="confirmTotal">Tsh <?= number_format($batch['price_per_bird'], 2) ?></span></li>
                    <li><strong>Delivery Method:</strong> <span id="confirmDelivery">Self-pickup</span></li>
                </ul>
                <p class="text-muted">Once placed, this order cannot be cancelled online. Please contact the farmer for changes.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Go Back</button>
                <button type="button" class="btn btn-success" id="confirmSubmit">Place Order</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('orderForm');
    const qtyInput = document.getElementById('quantity');
    const priceDisplay = document.getElementById('priceDisplay');
    const unitPrice = <?= $batch['price_per_bird'] ?>;
    const maxQty = <?= $batch['quantity'] ?>;
    const deliveryFee = <?= DELIVERY_FEE ?>;

    const deliveryRadios = document.querySelectorAll('input[name="delivery_method"]');
    const addressField = document.getElementById('addressField');
    const addressInput = document.getElementById('delivery_address');
    const deliveryFeeDisplay = document.getElementById('deliveryFeeDisplay');

    // Update price and totals
    function updatePrice() {
        let qty = parseInt(qtyInput.value) || 0;
        if (qty < 1) qty = 1;
        if (qty > maxQty) qty = maxQty;
        qtyInput.value = qty;

        const subtotal = qty * unitPrice;
        const selectedDelivery = document.querySelector('input[name="delivery_method"]:checked');
        const isDelivery = selectedDelivery && selectedDelivery.value === 'seller_delivery';
        const fee = isDelivery ? deliveryFee : 0;
        const total = subtotal + fee;

        // Update price display
        let displayText = 'Subtotal: Tsh ' + subtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (isDelivery) {
            displayText += ' + Delivery: Tsh ' + fee.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
        displayText += ' = <strong>Total: Tsh ' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong>';
        priceDisplay.innerHTML = displayText;

        // Update modal preview
        document.getElementById('confirmQty').textContent = qty;
        document.getElementById('confirmSubtotal').textContent = 'Tsh ' + subtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (isDelivery) {
            document.getElementById('confirmFeeRow').style.display = 'list-item';
            document.getElementById('confirmFee').textContent = 'Tsh ' + fee.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        } else {
            document.getElementById('confirmFeeRow').style.display = 'none';
        }
        document.getElementById('confirmTotal').textContent = 'Tsh ' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    // Increment/Decrement buttons
    document.getElementById('incrementQty').addEventListener('click', function() {
        let val = parseInt(qtyInput.value) || 0;
        if (val < maxQty) qtyInput.value = val + 1;
        updatePrice();
    });
    document.getElementById('decrementQty').addEventListener('click', function() {
        let val = parseInt(qtyInput.value) || 0;
        if (val > 1) qtyInput.value = val - 1;
        updatePrice();
    });
    qtyInput.addEventListener('change', updatePrice);
    qtyInput.addEventListener('input', updatePrice);

    // Delivery method toggle
    deliveryRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'seller_delivery') {
                addressField.style.display = 'block';
                addressInput.setAttribute('required', 'required');
                deliveryFeeDisplay.style.display = 'block';
                document.getElementById('confirmDelivery').textContent = 'Farmer delivers';
            } else {
                addressField.style.display = 'none';
                addressInput.removeAttribute('required');
                deliveryFeeDisplay.style.display = 'none';
                document.getElementById('confirmDelivery').textContent = 'Self-pickup';
            }
            updatePrice();
        });
    });
    // Initial state (self-pickup)
    addressField.style.display = 'none';
    deliveryFeeDisplay.style.display = 'none';

    // Confirmation modal
    const confirmBtn = document.getElementById('confirmBtn');
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    const confirmSubmit = document.getElementById('confirmSubmit');

    confirmBtn.addEventListener('click', function(e) {
        e.preventDefault();
        // Validate quantity
        const qty = parseInt(qtyInput.value) || 0;
        if (qty < 1 || qty > maxQty) {
            alert('Please enter a valid quantity between 1 and ' + maxQty);
            return;
        }
        // Validate address if needed
        const deliveryMethod = document.querySelector('input[name="delivery_method"]:checked').value;
        if (deliveryMethod === 'seller_delivery') {
            const addr = addressInput.value.trim();
            if (!addr) {
                alert('Please enter a delivery address.');
                return;
            }
        }
        // Show modal
        updatePrice();
        confirmModal.show();
    });

    confirmSubmit.addEventListener('click', function() {
        // Submit the form
        form.submit();
    });

    // Allow Enter key on form
    form.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            confirmBtn.click();
        }
    });

    // Initial price update
    updatePrice();
});
</script>
<?php
$content = ob_get_clean();
include '../layout.php';
?>