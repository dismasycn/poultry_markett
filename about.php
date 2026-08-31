<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "About Us – Poultry Market";
ob_start();
?>
<div class="container py-5">
    <h1 class="mb-4">About Poultry Market</h1>
    <div class="row">
        <div class="col-md-8">
            <p class="lead">The Poultry Market Link System is a web-based platform designed to connect broiler farmers directly with buyers in Tanzania.</p>
            <p>Developed as a final-year project for the <strong>Bachelor of Science in Information Technology</strong> at the <strong>Institute of Finance Management (IFM)</strong>, this system aims to solve the marketing challenges faced by poultry farmers.</p>
            <h5 class="mt-4">Our Mission</h5>
            <p>To simplify poultry trading by providing a transparent, efficient, and accessible online marketplace that benefits both farmers and buyers.</p>
            <h5>Key Features</h5>
            <ul>
                <li>Real-time chicken batch management with automatic age tracking</li>
                <li>Search and filter by breed, location, and age</li>
                <li>Order placement with delivery options (self-pickup or farmer delivery)</li>
                <li>Built-in messaging system for direct communication</li>
                <li>Administrative oversight with advanced reporting</li>
            </ul>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-egg-fried display-1 text-success"></i>
                    <h5 class="mt-3">Project Team</h5>
                    <ul class="list-unstyled">
                        <li>Hatibu Seleman Makamba</li>
                        <li>Taison Nelson Rweyemamu</li>
                        <li>Lydia Michael Yongolo</li>
                        <li>Abdulshakuru Abdallah Athumani</li>
                        <li>Dismas Alex Nkomalago</li>
                    </ul>
                    <p class="text-muted small">Academic Year III – 2026</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include 'layout.php';
?>