<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    die('Unauthorized');
}

$user_id = $_SESSION['user_id'];
$with = (int)($_GET['with'] ?? 0);

if ($with <= 0) {
    echo '<div class="text-center text-muted py-3">Invalid conversation.</div>';
    exit;
}

// Fetch messages
$stmt = $conn->prepare("
    SELECT m.*, u.name as sender_name, u.role as sender_role 
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE (m.sender_id = ? AND m.receiver_id = ?) 
       OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.created_at ASC
");
$stmt->bind_param("iiii", $user_id, $with, $with, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo '<div class="text-center text-muted py-4">No messages yet. Say hello! 👋</div>';
} else {
    while ($msg = $result->fetch_assoc()) {
        $is_me = ($msg['sender_id'] == $user_id);
        $time = date('H:i', strtotime($msg['created_at']));
        $date = date('d M Y', strtotime($msg['created_at']));
        $message = nl2br(htmlspecialchars($msg['message']));
        ?>
        <div class="d-flex <?= $is_me ? 'justify-content-end' : 'justify-content-start' ?> mb-2">
            <?php if (!$is_me): ?>
                <div class="me-2">
                    <i class="bi bi-person-circle fs-4 text-muted"></i>
                </div>
            <?php endif; ?>
            <div class="p-2 rounded <?= $is_me ? 'bg-primary text-white' : 'bg-light text-dark' ?>" 
                 style="max-width:70%; word-wrap:break-word;">
                <?php if (!$is_me): ?>
                    <small class="text-muted d-block mb-1">
                        <strong><?= htmlspecialchars($msg['sender_name']) ?></strong>
                    </small>
                <?php endif; ?>
                <?= $message ?>
                <div class="small <?= $is_me ? 'text-white-50' : 'text-muted' ?>" style="font-size:0.65rem; margin-top:2px;">
                    <?= $date ?> <?= $time ?>
                    <?php if ($is_me): ?>
                        <i class="bi bi-check2 <?= $msg['is_read'] ? 'text-success' : '' ?>"></i>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
?>