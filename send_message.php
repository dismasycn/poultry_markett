<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$sender_id = $_SESSION['user_id'];
$receiver_id = (int)($_POST['receiver_id'] ?? 0);
$message = sanitize($_POST['message'] ?? '');

if ($receiver_id <= 0 || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

// Check if receiver exists
$check = $conn->prepare("SELECT id FROM users WHERE id = ?");
$check->bind_param("i", $receiver_id);
$check->execute();
if ($check->get_result()->num_rows == 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Receiver not found']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $sender_id, $receiver_id, $message);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message_id' => $stmt->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
?>