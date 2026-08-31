<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Log the logout activity
if (isLoggedIn()) {
    logActivity($_SESSION['user_id'], 'logout', 'User logged out');
}

// Destroy session and redirect
session_destroy();
header('Location: index.php');
exit;