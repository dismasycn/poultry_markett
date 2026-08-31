<?php
require_once '../includes/auth.php';
if ($_SESSION['role'] !== 'admin') {
    die('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $phone = sanitize($_POST['phone']);
    $location = sanitize($_POST['location']);
    $role = $_POST['role'];

    // Validate
    $errors = [];
    if (empty($name)) $errors[] = "Name required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if (!in_array($role, ['admin','farmer','buyer'])) $role = 'buyer';

    // Check duplicate email
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $errors[] = "Email already registered.";
    }

    if (empty($errors)) {
        $hashed = hashPassword($password);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, location, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $hashed, $phone, $location, $role);
        if ($stmt->execute()) {
             // 🔍 LOG ACTIVITY
    logActivity($_SESSION['user_id'], 'admin_user_add', "Added user: $name ($email) as $role");
            $_SESSION['message'] = "User added successfully.";
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