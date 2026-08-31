<?php
require_once '../includes/auth.php';
$role = $_SESSION['role'];
if (!in_array($role, ['admin', 'farmer'])) {
    die('Unauthorized');
}

$order_id = (int)$_POST['order_id'];
$new_status = $_POST['status'];

$allowed = ['pending', 'confirmed', 'in_transit', 'delivered', 'cancelled'];
if (!in_array($new_status, $allowed)) {
    die('Invalid status');
}

// If farmer, ensure the order belongs to their batch
if ($role == 'farmer') {
    $farmer_id = $_SESSION['user_id'];
    $check = $conn->prepare("
        SELECT o.id FROM orders o 
        JOIN batches b ON o.batch_id = b.id 
        WHERE o.id = ? AND b.farmer_id = ?
    ");
    $check->bind_param("ii", $order_id, $farmer_id);
    $check->execute();
    if ($check->get_result()->num_rows == 0) {
        die('Unauthorized for this order.');
    }
}

// Fetch current order status and batch info
$order_query = $conn->prepare("SELECT status, batch_id, quantity FROM orders WHERE id = ?");
$order_query->bind_param("i", $order_id);
$order_query->execute();
$order = $order_query->get_result()->fetch_assoc();
if (!$order) {
    die('Order not found.');
}

$old_status = $order['status'];

// Start transaction
$conn->begin_transaction();
try {
    // If cancelling, restore batch quantity
    if ($new_status == 'cancelled' && $old_status != 'cancelled') {
        // Lock batch to prevent race
        $lock = $conn->prepare("SELECT quantity, status FROM batches WHERE id = ? FOR UPDATE");
        $lock->bind_param("i", $order['batch_id']);
        $lock->execute();
        $batch = $lock->get_result()->fetch_assoc();
        
        if ($batch) {
            $new_qty = $batch['quantity'] + $order['quantity'];
            $new_batch_status = ($new_qty > 0) ? 'available' : 'sold';
            $update_batch = $conn->prepare("UPDATE batches SET quantity = ?, status = ? WHERE id = ?");
            $update_batch->bind_param("isi", $new_qty, $new_batch_status, $order['batch_id']);
            $update_batch->execute();
        }
    }

    // Update order status
    $update_order = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $update_order->bind_param("si", $new_status, $order_id);
    $update_order->execute();

    $conn->commit();
    // 🔍 LOG ACTIVITY
    logActivity($_SESSION['user_id'], 'order_status_update', "Order #$order_id: $old_status → $new_status");
    $_SESSION['message'] = "Order status updated.";
    $_SESSION['msg_type'] = "success";
    //logActivity($_SESSION['user_id'], 'order_status_update', "Order $order_id changed from $old_status to $new_status");
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['msg_type'] = "danger";
}

// Redirect back
$referer = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
header("Location: $referer");
exit;
?>