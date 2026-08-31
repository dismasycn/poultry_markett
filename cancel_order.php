<?php
require_once '../includes/auth.php';
if ($_SESSION['role'] !== 'buyer') die('Unauthorized');

$order_id = (int)$_GET['id'];
$buyer_id = $_SESSION['user_id'];

$conn->begin_transaction();
try {
    // Get order and batch info
    $stmt = $conn->prepare("SELECT batch_id, quantity, status FROM orders WHERE id = ? AND buyer_id = ? AND status = 'pending' FOR UPDATE");
    $stmt->bind_param("ii", $order_id, $buyer_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    if (!$order) {
        throw new Exception("Order not found or cannot be cancelled.");
    }

    // Get current batch quantity
    $batch_stmt = $conn->prepare("SELECT quantity, status FROM batches WHERE id = ? FOR UPDATE");
    $batch_stmt->bind_param("i", $order['batch_id']);
    $batch_stmt->execute();
    $batch = $batch_stmt->get_result()->fetch_assoc();
    if (!$batch) {
        throw new Exception("Batch not found.");
    }

    // Restore quantity
    $new_qty = $batch['quantity'] + $order['quantity'];
    $new_status = ($new_qty > 0) ? 'available' : 'sold';
    $update = $conn->prepare("UPDATE batches SET quantity = ?, status = ? WHERE id = ?");
    $update->bind_param("isi", $new_qty, $new_status, $order['batch_id']);
    $update->execute();

    // Update order status
    $conn->query("UPDATE orders SET status = 'cancelled' WHERE id = $order_id");

    $conn->commit();
    // 🔍 LOG ACTIVITY
    logActivity($_SESSION['user_id'], 'order_cancelled', "Cancelled order #$order_id");
    $_SESSION['message'] = "Order cancelled and quantity restored.";
    $_SESSION['msg_type'] = "success";
   // logActivity($_SESSION['user_id'], 'order_cancelled', "Cancelled order ID: $order_id");
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['msg_type'] = "danger";
}
redirect('my_orders.php');
?>