<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$selected_user = isset($_GET['with']) ? (int)$_GET['with'] : 0;

// Fetch conversations with last message preview and unread count
$conversations = $conn->query("
    SELECT 
        u.id as user_id,
        u.name as user_name,
        u.role as user_role,
        MAX(m.created_at) as last_message_time,
        COUNT(CASE WHEN m.is_read = 0 AND m.receiver_id = $user_id THEN 1 END) as unread_count,
        (SELECT message FROM messages 
         WHERE (sender_id = u.id AND receiver_id = $user_id) 
            OR (sender_id = $user_id AND receiver_id = u.id)
         ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT sender_id FROM messages 
         WHERE (sender_id = u.id AND receiver_id = $user_id) 
            OR (sender_id = $user_id AND receiver_id = u.id)
         ORDER BY created_at DESC LIMIT 1) as last_sender_id
    FROM messages m
    JOIN users u ON (u.id = m.sender_id OR u.id = m.receiver_id)
    WHERE (m.sender_id = $user_id OR m.receiver_id = $user_id) AND u.id != $user_id
    GROUP BY u.id
    ORDER BY last_message_time DESC
");

// Auto-select first conversation if none selected
if ($selected_user == 0 && $conversations->num_rows > 0) {
    $first = $conversations->fetch_assoc();
    $selected_user = $first['user_id'];
    $conversations->data_seek(0);
}

// Mark messages as read when viewing a conversation
if ($selected_user > 0) {
    $conn->query("UPDATE messages SET is_read = 1 WHERE sender_id = $selected_user AND receiver_id = $user_id");
}

// Get partner details
$partner = null;
if ($selected_user > 0) {
    $stmt = $conn->prepare("SELECT id, name, role, phone, location FROM users WHERE id = ?");
    $stmt->bind_param("i", $selected_user);
    $stmt->execute();
    $partner = $stmt->get_result()->fetch_assoc();
}

$unread_count = getUnreadCount($user_id);
$page_title = "Messages – Poultry Market";
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2><i class="bi bi-chat-dots"></i> Messages</h2>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newMessageModal">
        <i class="bi bi-plus-circle"></i> New Message
    </button>
</div>

<div class="row g-0">
    <!-- Conversation List -->
    <div class="col-md-4 border-end" style="max-height: 600px; overflow-y: auto;">
        <div class="list-group list-group-flush">
            <?php if ($conversations->num_rows > 0): ?>
                <?php while ($conv = $conversations->fetch_assoc()): 
                    $is_active = ($selected_user == $conv['user_id']);
                    $unread_badge = $conv['unread_count'] > 0 ? 
                        '<span class="badge bg-danger rounded-pill ms-2">'.$conv['unread_count'].'</span>' : '';
                    $last_msg = htmlspecialchars(substr($conv['last_message'] ?? '', 0, 45));
                    $last_msg = $last_msg ?: 'No messages yet';
                    if (strlen($conv['last_message'] ?? '') > 45) $last_msg .= '...';
                    $is_from_me = ($conv['last_sender_id'] == $user_id);
                    $time = date('d M', strtotime($conv['last_message_time']));
                ?>
                    <a href="?with=<?= $conv['user_id'] ?>" 
                       class="list-group-item list-group-item-action <?= $is_active ? 'active' : '' ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($conv['user_name']) ?></strong>
                                <span class="badge bg-secondary ms-1"><?= $conv['user_role'] ?></span>
                                <?= $unread_badge ?>
                                <br>
                                <small class="text-muted">
                                    <?php if ($is_from_me): ?>
                                        <i class="bi bi-arrow-right"></i>
                                    <?php else: ?>
                                        <i class="bi bi-arrow-left"></i>
                                    <?php endif; ?>
                                    <?= $last_msg ?>
                                </small>
                            </div>
                            <small class="text-muted"><?= $time ?></small>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No conversations yet.</p>
                    <p class="small">Click <strong>"New Message"</strong> to start chatting.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chat Window -->
    <div class="col-md-8">
        <?php if ($selected_user > 0 && $partner): ?>
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                    <div>
                        <i class="bi bi-person-circle fs-4"></i>
                        <strong><?= htmlspecialchars($partner['name']) ?></strong>
                        <span class="badge bg-secondary ms-1"><?= $partner['role'] ?></span>
                        <?php if ($partner['phone']): ?>
                            <small class="text-muted ms-2">
                                <i class="bi bi-telephone"></i> <?= htmlspecialchars($partner['phone']) ?>
                            </small>
                        <?php endif; ?>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-success" id="refreshMessages" title="Refresh">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                        <?php if ($partner['role'] == 'farmer'): ?>
                            <a href="../farmer/public_profile.php?id=<?= $partner['id'] ?>" 
                               class="btn btn-sm btn-outline-info" title="View Profile">
                                <i class="bi bi-person"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body" id="messageContainer" 
                     style="height:450px; overflow-y:auto; background-color: var(--bg-body);">
                    <div id="messageList">
                        <div class="text-center text-muted py-3">Loading messages...</div>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <form id="messageForm" class="d-flex gap-2">
                        <input type="hidden" name="receiver_id" value="<?= $selected_user ?>">
                        <input type="text" name="message" class="form-control" 
                               placeholder="Type your message..." required autocomplete="off">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Send
                        </button>
                    </form>
                </div>
            </div>
        <?php elseif ($selected_user > 0 && !$partner): ?>
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5">
                    <i class="bi bi-exclamation-triangle fs-1 text-warning"></i>
                    <p class="text-muted mt-3">User not found or has been deleted.</p>
                    <a href="messages.php" class="btn btn-secondary">Go Back</a>
                </div>
            </div>
        <?php else: ?>
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5">
                    <i class="bi bi-chat fs-1 text-muted"></i>
                    <p class="text-muted mt-3">Select a conversation from the left to start messaging.</p>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                        <i class="bi bi-plus-circle"></i> Start New Conversation
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- New Message Modal -->
<div class="modal fade" id="newMessageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> New Conversation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Select User <span class="text-danger">*</span></label>
                    <select id="messageUserSelect" class="form-select" style="width:100%;">
                        <option value="">Search for a user...</option>
                        <?php
                        $all_users = $conn->query("
                            SELECT id, name, role, phone 
                            FROM users 
                            WHERE id != $user_id 
                            ORDER BY name
                        ");
                        while ($u = $all_users->fetch_assoc()): ?>
                            <option value="<?= $u['id'] ?>">
                                <?= htmlspecialchars($u['name']) ?> 
                                (<?= $u['role'] ?>)
                                <?= $u['phone'] ? ' - ' . htmlspecialchars($u['phone']) : '' ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Message <span class="text-danger">*</span></label>
                    <textarea id="newMessageText" class="form-control" rows="3" 
                              placeholder="Type your message..."></textarea>
                </div>
                <div class="text-muted small">
                    <i class="bi bi-info-circle"></i> Press <kbd>Ctrl+Enter</kbd> to send.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="sendNewMessageBtn">
                    <i class="bi bi-send"></i> Send Message
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.list-group-item.active {
    background-color: var(--bg-sidebar);
    border-color: var(--border-color);
    color: var(--text-primary);
}
.list-group-item.active .text-muted {
    color: var(--text-secondary) !important;
}
[data-theme="dark"] .bg-light {
    background-color: #3a4047 !important;
    color: #e9ecef !important;
}
[data-theme="dark"] .bg-light .text-muted {
    color: #adb5bd !important;
}
</style>

<script>
$(document).ready(function() {
    const partnerId = <?= $selected_user ?: 0 ?>;

    // Load messages
    function loadMessages() {
        if (partnerId === 0) {
            $('#messageList').html('<div class="text-center text-muted py-3">Select a conversation.</div>');
            return;
        }

        $('#messageList').html('<div class="text-center text-muted py-3">Loading...</div>');

        $.ajax({
            url: 'get_messages.php',
            type: 'GET',
            data: { with: partnerId },
            dataType: 'html',
            success: function(data) {
                $('#messageList').html(data);
                scrollToBottom();
            },
            error: function(xhr) {
                console.error('Error loading messages:', xhr.responseText);
                $('#messageList').html('<div class="text-center text-danger py-3">Error loading messages.</div>');
            }
        });
    }

    function scrollToBottom() {
        const container = document.getElementById('messageContainer');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }

    // Send message (main form)
    $('#messageForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const messageInput = form.find('input[name="message"]');
        const msg = messageInput.val().trim();
        if (!msg || partnerId === 0) return;

        const btn = form.find('button[type="submit"]');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: 'send_message.php',
            type: 'POST',
            data: {
                receiver_id: partnerId,
                message: msg
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    messageInput.val('');
                    loadMessages();
                } else {
                    alert('Error: ' + (response.error || 'Could not send message.'));
                }
            },
            error: function(xhr) {
                alert('Server error. Please try again.');
                console.error(xhr.responseText);
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bi bi-send"></i> Send');
            }
        });
    });

    // Send message from modal
    $('#sendNewMessageBtn').on('click', function() {
        const userId = $('#messageUserSelect').val();
        const message = $('#newMessageText').val().trim();
        if (!userId) { alert('Please select a user.'); return; }
        if (!message) { alert('Please enter a message.'); return; }

        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: 'send_message.php',
            type: 'POST',
            data: {
                receiver_id: userId,
                message: message
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#newMessageModal').modal('hide');
                    window.location.href = 'messages.php?with=' + userId;
                } else {
                    alert('Error: ' + (response.error || 'Could not send message.'));
                }
            },
            error: function(xhr) {
                alert('Server error. Please try again.');
                console.error(xhr.responseText);
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bi bi-send"></i> Send Message');
            }
        });
    });

    // Enter key in modal textarea
    $(document).on('keydown', '#newMessageText', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            $('#sendNewMessageBtn').click();
        }
    });

    // Enter key in chat input
    $(document).on('keydown', 'input[name="message"]', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            $('#messageForm').submit();
        }
    });

    // Auto-refresh every 8 seconds
    let refreshInterval = setInterval(loadMessages, 8000);

    // Manual refresh
    $('#refreshMessages').on('click', function() {
        loadMessages();
        clearInterval(refreshInterval);
        refreshInterval = setInterval(loadMessages, 8000);
    });

    // Load messages on page load
    if (partnerId > 0) {
        loadMessages();
        setTimeout(scrollToBottom, 500);
    }

    // Cleanup interval
    $(window).on('beforeunload', function() {
        clearInterval(refreshInterval);
    });
});
</script>
<?php
$content = ob_get_clean();
include 'layout.php';
?>