<?php
require_once '../includes/auth.php';
if ($_SESSION['role'] !== 'buyer') {
    redirect('../index.php');
}

$buyer_id = $_SESSION['user_id'];
$order_id = (int)$_GET['order_id'];

// Fetch the original order details
$stmt = $conn->prepare("
    SELECT o.*, b.id as batch_id, b.farmer_id, b.quantity as available_qty, b.price_per_bird, b.breed
    FROM orders o
    JOIN batches b ON o.batch_id = b.id
    WHERE o.id = ? AND o.buyer_id = ?
");
$stmt->bind_param("ii", $order_id, $buyer_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['message'] = "Order not found or unauthorized.";
    $_SESSION['msg_type'] = "danger";
    redirect('my_orders.php');
}

// Check if the batch still exists and is available
if ($order['available_qty'] < $order['quantity']) {
    $_SESSION['message'] = "Sorry, this batch now has only {$order['available_qty']} chickens available. You ordered {$order['quantity']}.";
    $_SESSION['msg_type'] = "warning";
    redirect('my_orders.php');
}

// Check if batch is still available
$batch_check = $conn->query("SELECT status FROM batches WHERE id = {$order['batch_id']} AND status = 'available'");
if ($batch_check->num_rows == 0) {
    $_SESSION['message'] = "This batch is no longer available for re-order.";
    $_SESSION['msg_type'] = "danger";
    redirect('my_orders.php');
}

// Create new order
$total = $order['quantity'] * $order['price_per_bird'];
$stmt = $conn->prepare("
    INSERT INTO orders (buyer_id, batch_id, quantity, total_price, delivery_method, delivery_address, notes)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
    "iiidsss",
    $buyer_id,
    $order['batch_id'],
    $order['quantity'],
    $total,
    $order['delivery_method'],
    $order['delivery_address'],
    $order['notes']
);

if ($stmt->execute()) {
    // Reserve the batch
    $conn->query("UPDATE batches SET status = 'reserved' WHERE id = {$order['batch_id']}");
    
    // Notify the farmer
    $farmer = $conn->query("SELECT email, name FROM users WHERE id = {$order['farmer_id']}")->fetch_assoc();
    $subject = "Re-order placed for your chickens!";
    $body = "
        <h3>Re-order Received</h3>
        <p>Hello {$farmer['name']},</p>
        <p>A buyer has placed a <strong>re-order</strong> for your <strong>{$order['breed']}</strong> chickens.</p>
        <ul>
            <li>Quantity: {$order['quantity']}</li>
            <li>Total: Tsh " . number_format($total, 2) . "</li>
            <li>Delivery: " . str_replace('_', ' ', $order['delivery_method']) . "</li>
        </ul>
        <p>Please log in to manage this order.</p>
    ";
    sendEmail($farmer['email'], $subject, $body);

    $_SESSION['message'] = "Re-order placed successfully!";
    $_SESSION['msg_type'] = "success";
} else {
    $_SESSION['message'] = "Failed to place re-order: " . $conn->error;
    $_SESSION['msg_type'] = "danger";
}

redirect('my_orders.php');
?>