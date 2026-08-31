<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Privacy Policy – Poultry Market";
ob_start();
?>
<div class="container py-5">
    <h1 class="mb-4">Privacy Policy</h1>
    <div class="card shadow-sm">
        <div class="card-body">
            <p class="lead">Your privacy is important to us.</p>
            <h5>Information We Collect</h5>
            <p>When you register, we collect your name, email, phone, location, and role (farmer/buyer). We store your order history and messages for operational purposes.</p>
            <h5>How We Use Your Data</h5>
            <ul>
                <li>To connect you with farmers or buyers</li>
                <li>To process orders and deliveries</li>
                <li>To send order notifications</li>
                <li>To improve our platform</li>
            </ul>
            <h5>Data Sharing</h5>
            <p>We do not sell your data to third parties. Your contact details are visible only to the users you interact with (e.g., the farmer you order from).</p>
            <h5>Security</h5>
            <p>We use hashed passwords, CSRF tokens, and secure connections (HTTPS) to protect your data.</p>
            <h5>Your Rights</h5>
            <p>You can request deletion of your account and data by contacting us. We retain order data for 7 years for auditing purposes.</p>
            <p class="text-muted small">Last updated: <?= date('d M Y') ?></p>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include 'layout.php';
?>