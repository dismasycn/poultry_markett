<?php
require_once '../includes/auth.php';
if ($_SESSION['role'] !== 'farmer') {
    die('Unauthorized');
}

$batch_id = (int)$_POST['batch_id'];
$farmer_id = $_SESSION['user_id'];

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    die('CSRF token invalid');
}
// Before DELETE, get image
$stmt = $conn->prepare("SELECT image FROM batches WHERE id = ? AND farmer_id = ?");
$stmt->bind_param("ii", $batch_id, $farmer_id);
$stmt->execute();
$result = $stmt->get_result();
$batch = $result->fetch_assoc();
if ($batch && $batch['image'] && file_exists('../' . $batch['image'])) {
    unlink('../' . $batch['image']);
}
// Then proceed with DELETE
// Delete only if it belongs to the farmer
$stmt = $conn->prepare("DELETE FROM batches WHERE id = ? AND farmer_id = ?");
$stmt->bind_param("ii", $batch_id, $farmer_id);
if ($stmt->execute()) {
        // 🔍 LOG ACTIVITY
    logActivity($_SESSION['user_id'], 'delete_batch', "Deleted batch ID: $batch_id - {$info['breed']} (Qty: {$info['quantity']})");
    $_SESSION['message'] = "Batch deleted successfully.";
    $_SESSION['msg_type'] = "success";
    //logActivity($_SESSION['user_id'], 'delete_batch', "Deleted batch ID: $batch_id");
} else {
    $_SESSION['message'] = "Failed to delete batch: " . $conn->error;
    $_SESSION['msg_type'] = "danger";
}
redirect('my_batches.php');
?>