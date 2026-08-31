<?php
// ============================================================
//  FUNCTIONS.PHP – COMPLETE VERSION (NO DUPLICATES)
// ============================================================

// ---------- SANITIZATION & HELPERS ----------
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function getAgeInDays($hatchDate) {
    return (new DateTime())->diff(new DateTime($hatchDate))->days;
}

function hashPassword($plain) {
    return password_hash($plain, PASSWORD_DEFAULT);
}

function verifyPassword($plain, $hash) {
    return password_verify($plain, $hash);
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ---------- EMAIL ----------
function sendEmail($to, $subject, $body, $altBody = '') {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your_email@gmail.com';
        $mail->Password   = 'your_app_password';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('your_email@gmail.com', 'Poultry Market');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);
        return $mail->send();
    } catch (Exception $e) {
        error_log("Email failed: " . $mail->ErrorInfo);
        return false;
    }
}

// ---------- MESSAGE UNREAD COUNT ----------
function getUnreadCount($user_id) {
    global $conn;
   // $stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    //$stmt->bind_param("i", $user_id);
  //  $stmt->execute();
  //  $stmt->bind_result($count);
  //  $stmt->fetch();
  //  return $count;
}

// ---------- DYNAMIC PRICING & BATCH STATUS (NEW) ----------

/**
 * Get batch pricing and status based on age
 * 
 * @param float $basePrice The base price per bird
 * @param string $hatchDate The hatch date (Y-m-d)
 * @return array {
 *      price: float,
 *      alert: string (none, loss, price_doubled, critical_loss),
 *      status_class: string (success, danger, info),
 *      status_text: string,
 *      age: int,
 *      is_loss: bool,
 *      is_critical: bool,
 *      is_doubled: bool
 * }
 */
function getBatchStatus($basePrice, $hatchDate) {
    $age = getAgeInDays($hatchDate);
    $price = $basePrice;
    $alert = 'none';
    $status_class = 'success';
    $status_text = 'Optimal';
    $is_loss = false;
    $is_critical = false;
    $is_doubled = false;

    if ($age <= 30) {
        // Optimal – no alerts
        $price = $basePrice;
        $alert = 'none';
        $status_class = 'success';
        $status_text = '✅ Optimal';
        $is_loss = false;
        $is_critical = false;
        $is_doubled = false;
        
    } elseif ($age <= 57) {
        // Loss alert – price remains same
        $price = $basePrice;
        $alert = 'loss';
        $status_class = 'danger';
        $status_text = '⚠️ Loss Alert';
        $is_loss = true;
        $is_critical = false;
        $is_doubled = false;
        
    } elseif ($age <= 64) {
        // Price doubled – no loss alert
        $price = $basePrice * 2;
        $alert = 'price_doubled';
        $status_class = 'info';
        $status_text = '💰 Price Doubled';
        $is_loss = false;
        $is_critical = false;
        $is_doubled = true;
        
    } else {
        // Critical loss alert – price remains doubled
        $price = $basePrice * 2;
        $alert = 'critical_loss';
        $status_class = 'danger';
        $status_text = '⚠️ CRITICAL Loss Alert';
        $is_loss = true;
        $is_critical = true;
        $is_doubled = true;
    }

    return [
        'price'          => $price,
        'alert'          => $alert,
        'status_class'   => $status_class,
        'status_text'    => $status_text,
        'age'            => $age,
        'is_loss'        => $is_loss,
        'is_critical'    => $is_critical,
        'is_doubled'     => $is_doubled,
        'base_price'     => $basePrice
    ];
}

/**
 * Get current price (for buyer display)
 */
function getCurrentPrice($basePrice, $hatchDate) {
    $status = getBatchStatus($basePrice, $hatchDate);
    return $status['price'];
}

/**
 * Get price breakdown (for display)
 */
function getPriceBreakdown($basePrice, $hatchDate) {
    $status = getBatchStatus($basePrice, $hatchDate);
    return [
        'base'      => $basePrice,
        'current'   => $status['price'],
        'increase'  => $status['price'] - $basePrice,
        'age'       => $status['age'],
        'is_increased' => ($status['price'] != $basePrice),
        'is_loss'   => $status['is_loss'],
        'is_critical' => $status['is_critical'],
        'is_doubled' => $status['is_doubled'],
        'alert'     => $status['alert'],
        'status_text' => $status['status_text'],
        'status_class' => $status['status_class']
    ];
}

/**
 * Get loss alert badge HTML (for farmer side)
 */
function getLossAlertBadge($age, $priceInfo) {
    if ($age <= 30) {
        return '<span class="badge bg-success">✅ Optimal</span>';
    } elseif ($age <= 57) {
        return '<span class="badge bg-danger">⚠️ Loss Alert</span>';
    } elseif ($age <= 64) {
        return '<span class="badge bg-info">💰 Price Doubled</span>';
    } else {
        return '<span class="badge bg-danger">⚠️ CRITICAL Loss Alert</span>';
    }
}

// ---------- ACTIVITY LOGS ----------
// ============================================================
//  ACTIVITY LOGGING FUNCTIONS
// ============================================================

/**
 * Log user activity
 * @param int $user_id The user ID (0 for guest)
 * @param string $action Short action code (e.g., 'login', 'order_placed')
 * @param string $description Optional description
 * @param string $ip Optional IP address (auto-detected if not provided)
 * @param string $user_agent Optional user agent (auto-detected if not provided)
 */
function logActivity($user_id, $action, $description = '', $ip = null, $user_agent = null) {
    global $conn;
    
    // Auto-detect IP if not provided
    if ($ip === null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        // Handle proxies
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
    }
    
    // Auto-detect user agent if not provided
    if ($user_agent === null) {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    // Truncate description if too long
    if (strlen($description) > 500) {
        $description = substr($description, 0, 497) . '...';
    }
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $action, $description, $ip, $user_agent);
    return $stmt->execute();
}

/**
 * Get recent activity logs with user details
 * @param int $limit Number of logs to fetch
 * @param string $filter Optional filter by action
 * @param int $user_id Optional filter by user ID
 * @return array
 */
function getActivityLogs($limit = 50, $filter = '', $user_id = 0) {
    global $conn;
    
    $query = "
        SELECT l.*, u.name as user_name, u.role as user_role
        FROM activity_logs l 
        LEFT JOIN users u ON l.user_id = u.id 
        WHERE 1=1
    ";
    $params = [];
    $types = "";
    
    if (!empty($filter)) {
        $query .= " AND l.action LIKE ?";
        $params[] = "%$filter%";
        $types .= "s";
    }
    
    if ($user_id > 0) {
        $query .= " AND l.user_id = ?";
        $params[] = $user_id;
        $types .= "i";
    }
    
    $query .= " ORDER BY l.created_at DESC LIMIT ?";
    $params[] = $limit;
    $types .= "i";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get activity statistics
 * @return array
 */
function getActivityStats() {
    global $conn;
    
    $stats = [];
    
    // Total logs
    $stats['total'] = $conn->query("SELECT COUNT(*) FROM activity_logs")->fetch_row()[0] ?? 0;
    
    // Today's logs
    $stats['today'] = $conn->query("SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()")->fetch_row()[0] ?? 0;
    
    // Unique users who performed actions
    $stats['unique_users'] = $conn->query("SELECT COUNT(DISTINCT user_id) FROM activity_logs WHERE user_id IS NOT NULL")->fetch_row()[0] ?? 0;
    
    // Most common actions
    $actions_result = $conn->query("
        SELECT action, COUNT(*) as count 
        FROM activity_logs 
        GROUP BY action 
        ORDER BY count DESC 
        LIMIT 5
    ");
    $stats['top_actions'] = $actions_result->fetch_all(MYSQLI_ASSOC);
    
    return $stats;
}

/**
 * Get action label for display
 */
function getActionLabel($action) {
    $labels = [
        'login' => 'Login',
        'logout' => 'Logout',
        'register' => 'Registration',
        'add_batch' => 'Added Batch',
        'edit_batch' => 'Edited Batch',
        'delete_batch' => 'Deleted Batch',
        'order_placed' => 'Placed Order',
        'order_cancelled' => 'Cancelled Order',
        'order_status_update' => 'Updated Order Status',
        'payment_success' => 'Payment Success',
        'payment_failed' => 'Payment Failed',
        'profile_update' => 'Updated Profile',
        'password_change' => 'Changed Password',
        'admin_user_add' => 'Added User',
        'admin_user_edit' => 'Edited User',
        'admin_user_delete' => 'Deleted User',
        'admin_role_change' => 'Changed User Role',
        'batch_status_update' => 'Updated Batch Status',
    ];
    return $labels[$action] ?? ucfirst(str_replace('_', ' ', $action));
}

/**
 * Get action badge class for styling
 */
function getActionBadgeClass($action) {
    $badges = [
        'login' => 'bg-success',
        'logout' => 'bg-secondary',
        'register' => 'bg-primary',
        'add_batch' => 'bg-success',
        'edit_batch' => 'bg-warning text-dark',
        'delete_batch' => 'bg-danger',
        'order_placed' => 'bg-info text-dark',
        'order_cancelled' => 'bg-danger',
        'order_status_update' => 'bg-primary',
        'payment_success' => 'bg-success',
        'payment_failed' => 'bg-danger',
        'profile_update' => 'bg-secondary',
        'password_change' => 'bg-warning text-dark',
        'admin_user_add' => 'bg-primary',
        'admin_user_edit' => 'bg-warning text-dark',
        'admin_user_delete' => 'bg-danger',
        'admin_role_change' => 'bg-info text-dark',
    ];
    return $badges[$action] ?? 'bg-secondary';
}

?>