<?php
require_once '../includes/auth.php';
if ($_SESSION['role'] !== 'admin') {
    die('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    $user_id = (int)$_POST['user_id'];
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $location = sanitize($_POST['location']);
    $new_password = $_POST['password'] ?? '';

    // Prevent admin from editing self via this form (optional)
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['message'] = "You cannot edit yourself here. Use Profile Settings.";
        $_SESSION['msg_type'] = "danger";
        redirect('users.php');
    }

    // Validate
    $errors = [];
    if (empty($name)) $errors[] = "Name required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required.";
    if (!empty($new_password) && strlen($new_password) < 6) $errors[] = "New password must be at least 6 characters.";

    if (empty($errors)) {
        $query = "UPDATE users SET name = ?, email = ?, phone = ?, location = ?";
        $params = [$name, $email, $phone, $location];
        $types = "ssss";
        if (!empty($new_password)) {
            $hashed = hashPassword($new_password);
            $query .= ", password = ?";
            $params[] = $hashed;
            $types .= "s";
        }
        $query .= " WHERE id = ?";
        $params[] = $user_id;
        $types .= "i";

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
             // 🔍 LOG ACTIVITY
    logActivity($_SESSION['user_id'], 'admin_user_edit', "Edited user ID: $user_id - $name");
            $_SESSION['message'] = "User updated successfully.";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['message'] = "Error: " . $conn->error;
            $_SESSION['msg_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = implode('<br>', $errors);
        $_SESSION['msg_type'] = "danger";
    }
    redirect('users.php');
}
?>