<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Contact Us – Poultry Market";
ob_start();
?>
<div class="container py-5">
    <h1 class="mb-4">Contact Us</h1>
    <div class="row">
        <div class="col-md-6">
            <p class="lead">Have questions or feedback? We'd love to hear from you.</p>
            <form method="POST" action="contact_submit.php">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <div class="mb-3">
                    <label class="form-label">Your Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn btn-success">Send Message</button>
            </form>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5><i class="bi bi-geo-alt"></i> Location</h5>
                    <p class="text-muted">Institute of Finance Management<br>
                    Dar es Salaam, Tanzania</p>
                    <h5><i class="bi bi-envelope"></i> Email</h5>
                    <p class="text-muted"><a href="mailto:support@poultrymarket.com">support@poultrymarket.com</a></p>
                    <h5><i class="bi bi-phone"></i> Phone</h5>
                    <p class="text-muted">+255 123 456 789</p>
                    <hr>
                    <p class="text-muted small">Alternatively, use the built-in messaging system to reach farmers or admin directly.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include 'layout.php';
?>