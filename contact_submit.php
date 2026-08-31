<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    die('Invalid security token.');
}
$name = sanitize($_POST['name']);
$email = sanitize($_POST['email']);
$subject = sanitize($_POST['subject']);
$message = sanitize($_POST['message']);
$body = "From: $name ($email)\n\n$message";
if (sendEmail(ADMIN_EMAIL, "Contact: $subject", nl2br($body), $body)) {
    $_SESSION['message'] = "Your message was sent. We'll get back to you soon.";
    $_SESSION['msg_type'] = "success";
} else {
    $_SESSION['message'] = "Failed to send message. Please try again.";
    $_SESSION['msg_type'] = "danger";
}
redirect('contact.php');
?>